<?php

declare(strict_types=1);

namespace PhpChess\Tests\Infra;

use PhpChess\Domain\Play\Game;
use PhpChess\Domain\System\Archive;
use PhpChess\Http\UploadedFile;
use PhpChess\Infra\System\ArchiveRepository;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\InstalledSite;
use PhpChess\Tests\Support\Values;
use PHPUnit\Framework\TestCase;

/**
 * The archive (SCH-07, A42, A43): nothing goes because of its age; old rows
 * leave the database only after the admin wrote and kept the archive file,
 * and the file can be read back in. The daily check tells the admins when
 * reads get slower than the cut-off.
 */
final class ArchiveTest extends TestCase
{
    private InstalledSite $site;
    private PlayParts $play;
    private Archive $archive;

    protected function setUp(): void
    {
        $this->site = new InstalledSite($this);
        $this->play = new PlayParts($this->site->database, $this->site->settings());
        $this->archive = new Archive(new ArchiveRepository($this->site->database), $this->site->settings(), $this->site->folder->path . '/storage/archives');
        $this->site->createUser('anna');
        $this->site->createUser('ben');
    }

    protected function tearDown(): void
    {
        if (isset($this->site)) {
            $this->site->remove();
        }
    }

    public function testOldRowsGoOnlyAfterTheFileWasWrittenAndComeBackFromIt(): void
    {
        $annaId = $this->userId('anna');
        $benId = $this->userId('ben');
        $old = strtotime('2025-03-01 12:00:00 UTC');
        $oldGame = $this->play->games()->start($annaId, $benId, 3, true, '', $old);
        $this->play->games()->move($oldGame, $annaId, 0, 'e2e4', $old);
        $this->play->games()->resign($oldGame, $benId, $old);
        $running = $this->play->games()->start($annaId, $benId, 3, true, '', $old);
        $this->site->loggedInBrowser('anna')->submit('/messages/ben', '/messages/ben', ['body' => 'Recent message']);

        // Removing before writing the file is refused.
        try {
            $this->archive->remove('2026-01-01');
            self::fail('Removing without an archive file must be refused.');
        } catch (\PhpChess\Domain\ActionRefused $refusal) {
            self::assertSame('archive.write_first', $refusal->languageKey);
        }

        self::assertSame(1, $this->archive->preview('2026-01-01')['games']);
        $written = $this->archive->write('2026-01-01', time());
        self::assertGreaterThanOrEqual(3, $written['rows']);
        $lines = gzfile($written['path']);
        self::assertNotFalse($lines);
        self::assertStringContainsString('"phpchess_archive":1', $lines[0]);

        $this->archive->remove('2026-01-01');
        self::assertNull($this->play->gameRepository()->find($oldGame));
        self::assertNotNull($this->play->gameRepository()->find($running), 'Running games are never archived.');
        self::assertSame(1, Values::int($this->site->database->fetchValue('SELECT COUNT(*) FROM ' . $this->site->database->table('messages'))));

        $restored = $this->archive->restore($written['path']);
        self::assertGreaterThanOrEqual(3, $restored['restored']);
        $game = $this->play->gameRepository()->find($oldGame);
        self::assertNotNull($game);
        self::assertSame(Game::STATE_FINISHED, $game->state);
        self::assertCount(1, $this->play->gameRepository()->moves($oldGame));
    }

    public function testTheAdminPagesWriteDownloadAndRestore(): void
    {
        $old = strtotime('2025-03-01 12:00:00 UTC');
        $gameId = $this->play->games()->start($this->userId('anna'), $this->userId('ben'), 3, true, '', $old);
        $this->play->games()->resign($gameId, $this->userId('ben'), $old);

        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        $admin->get('/admin/archive?before=2026-01-01');
        $admin->post('/admin/archive/write', ['before' => '2026-01-01']);
        $download = $admin->get('/admin/archive/download', ['before' => '2026-01-01']);
        self::assertSame(200, $download->status);
        self::assertSame('application/gzip', $download->header('Content-Type'));

        $admin->get('/admin/archive?before=2026-01-01');
        $admin->post('/admin/archive/remove', ['before' => '2026-01-01']);
        self::assertNotNull($this->play->gameRepository()->find($gameId), 'Without the tick nothing is removed.');
        $admin->post('/admin/archive/remove', ['before' => '2026-01-01', 'kept_file' => 'yes']);
        self::assertNull($this->play->gameRepository()->find($gameId));

        $file = sys_get_temp_dir() . '/phpchess-archive-' . bin2hex(random_bytes(4)) . '.gz';
        file_put_contents($file, $download->body);
        $admin->get('/admin/archive');
        $admin->postWithFiles('/admin/archive/restore', [], ['archive' => new UploadedFile($file, (int) filesize($file))]);
        unlink($file);
        self::assertNotNull($this->play->gameRepository()->find($gameId));
    }

    public function testAFileWithStrangeTablesOrColumnsRestoresNothing(): void
    {
        $file = sys_get_temp_dir() . '/phpchess-archive-' . bin2hex(random_bytes(4)) . '.gz';
        file_put_contents($file, gzencode(
            '{"phpchess_archive":1,"before":"2026-01-01"}' . "\n"
            . '{"t":"users","r":{"id":99,"username":"intruder","role":"admin"}}' . "\n"
            . '{"t":"messages","r":{"id":5,"body":"x","evil`) VALUES (1); DROP TABLE x; --":1}}' . "\n",
        ));
        $result = $this->archive->restore($file);
        unlink($file);

        self::assertSame(['restored' => 0, 'skipped' => 2], $result);
        self::assertFalse($this->site->users()->usernameExists('intruder'));
    }

    public function testTheDailyCheckTellsTheAdminsWhenReadsAreSlow(): void
    {
        $this->site->settings()->set('archive.cutoff_ms', 50);
        $kernel = new Kernel($this->site->folder->path, now: time(), mailer: $this->site->mailer);
        $kernel->jobRunner()->runFromCron(time());
        self::assertGreaterThan(0, $this->site->settings()->getInt('archive.last_check_at'));

        // Pretend the last check was slow: the dashboard and the archive page say so.
        $this->site->settings()->set('archive.too_slow', true);
        $admin = $this->site->loggedInBrowser(InstalledSite::ADMIN_NAME, true);
        self::assertStringContainsString('An archive is suggested.', $admin->get('/admin/archive')->body);
    }

    private function userId(string $username): int
    {
        return (int) $this->site->users()->findByUsernameOrEmail($username)?->id;
    }
}
