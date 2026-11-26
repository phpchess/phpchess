<?php

declare(strict_types=1);

namespace PhpChess\Infra\Lichess;

use Closure;
use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\System\FeatureSwitches;
use PhpChess\Infra\System\SettingsRepository;

/**
 * The one door to lichess.org (C5-1, contract K9, A74, A79, A81).
 *
 * Every outside request of the Lichess module goes through get(). It
 * refuses before anything leaves the server unless the "external content"
 * switch is on (selfTest() is the one exception: the admin's "Test
 * connection" button must work while the switch is still off, A80).
 *
 * The rules it keeps:
 *   - only https://lichess.org, with the certificate checked; redirects are
 *     not followed, so a reply can't send us to another host (A81);
 *   - public, read-only addresses only, and no login or token (A77, A78);
 *   - a User-Agent naming phpChess (A79);
 *   - one request at a time for the whole site (a lock file), and after a
 *     "429 Too Many Requests" a full minute of rest (A79);
 *   - a reply larger than MAX_REPLY_BYTES is cut off and refused.
 *
 * The browser never talks to Lichess: pages ask our server, which asks here.
 * So the site's Content-Security-Policy stays 'self' (K9).
 *
 * Tests give a fake $transport so nothing leaves the machine.
 */
final class LichessClient
{
    public const string HOST = 'https://lichess.org';

    public const int MAX_REPLY_BYTES = 1048576;

    public const int TIMEOUT_SECONDS = 15;

    /** Settings: when Lichess asked us to rest until, and the last self-test. */
    public const string REST_UNTIL_SETTING = 'lichess.rest_until';
    public const string TEST_PASSED_AT_SETTING = 'lichess.test_passed_at';
    public const string TEST_RESULT_SETTING = 'lichess.test_result';

    /** @var Closure(string $url, string $accept): array{status: int, body: string} */
    private readonly Closure $transport;

    /**
     * @param Closure(string $url, string $accept): array{status: int, body: string}|null $transport  null: real curl
     */
    public function __construct(
        private readonly FeatureSwitches $switches,
        private readonly SettingsRepository $settings,
        private readonly string $lockFile,
        private readonly int $now,
        ?Closure $transport = null,
    ) {
        $this->transport = $transport ?? Closure::fromCallable([self::class, 'curlGet']);
    }

    // --- The addresses we use (public, read-only) ---

    /** One game as PGN, without clocks or engine evaluations. */
    public function gamePgn(string $gameId): string
    {
        return $this->get('/game/export/' . rawurlencode($gameId) . '?clocks=false&evals=false&literate=false', 'application/x-chess-pgn');
    }

    /** A player's newest games as PGN, at most $count. */
    public function userGamesPgn(string $username, int $count): string
    {
        return $this->get(
            '/api/games/user/' . rawurlencode($username) . '?max=' . $count . '&clocks=false&evals=false&literate=false',
            'application/x-chess-pgn',
        );
    }

    /** The games shown on Lichess TV now, one per channel (JSON text). */
    public function tvChannels(): string
    {
        return $this->get('/api/tv/channels', 'application/json');
    }

    // --- The self-test (A80) ---

    /**
     * Asks Lichess for one small, public reply and stores the outcome. The
     * switch can only be turned on after this has passed. Returns true when
     * Lichess answered.
     */
    public function selfTest(): bool
    {
        try {
            $reply = $this->send('/api/tv/channels', 'application/json');
            $passed = $reply['status'] === 200 && is_array(json_decode($reply['body'], true));
            $result = $passed ? 'ok' : 'HTTP ' . $reply['status'];
        } catch (ActionRefused $refusal) {
            $passed = false;
            $result = $refusal->languageKey;
        }
        $this->settings->set(self::TEST_RESULT_SETTING, $result);
        $this->settings->set(self::TEST_PASSED_AT_SETTING, $passed ? $this->now : 0);

        return $passed;
    }

    /** True when the last self-test passed. */
    public function hasPassedSelfTest(): bool
    {
        return $this->settings->getInt(self::TEST_PASSED_AT_SETTING) > 0;
    }

    // --- Sending ---

    /**
     * One GET request to lichess.org. Throws ActionRefused when the switch
     * is off, when Lichess asked us to rest, or when the reply is not 200.
     */
    private function get(string $path, string $accept): string
    {
        if (!$this->switches->isOn(FeatureSwitches::EXTERNAL_CONTENT)) {
            throw new ActionRefused('lichess.switched_off');
        }
        $reply = $this->send($path, $accept);
        if ($reply['status'] === 404) {
            throw new ActionRefused('lichess.not_found');
        }
        if ($reply['status'] !== 200) {
            throw new ActionRefused('lichess.unavailable');
        }

        return $reply['body'];
    }

    /**
     * Sends one request, one at a time, and keeps the rest rule.
     *
     * @return array{status: int, body: string}
     */
    private function send(string $path, string $accept): array
    {
        if ($this->settings->getInt(self::REST_UNTIL_SETTING) > $this->now) {
            throw new ActionRefused('lichess.resting');
        }

        // One request at a time for the whole site: wait at most 20 seconds for the lock.
        $lock = fopen($this->lockFile, 'c');
        if ($lock === false) {
            throw new ActionRefused('lichess.unavailable');
        }
        try {
            $waitedSeconds = 0;
            while (!flock($lock, LOCK_EX | LOCK_NB)) {
                if ($waitedSeconds >= 20) {
                    throw new ActionRefused('lichess.busy');
                }
                sleep(1);
                $waitedSeconds++;
            }
            $reply = ($this->transport)(self::HOST . $path, $accept);
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        if ($reply['status'] === 429) {
            $this->settings->set(self::REST_UNTIL_SETTING, $this->now + 60);
            throw new ActionRefused('lichess.resting');
        }

        return $reply;
    }

    /**
     * The real transport: curl, https only, certificate checked, no
     * redirects, a time limit and a size limit.
     *
     * @return array{status: int, body: string}
     */
    private static function curlGet(string $url, string $accept): array
    {
        if (!str_starts_with($url, self::HOST . '/') || !function_exists('curl_init')) {
            throw new ActionRefused('lichess.unavailable');
        }

        $body = '';
        $tooLarge = false;
        $handle = curl_init($url);
        curl_setopt_array($handle, [
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_USERAGENT => 'phpChess/6 (self-hosted chess server)',
            CURLOPT_HTTPHEADER => ['Accept: ' . $accept],
            // Collect the reply piece by piece, and stop when it gets too large.
            CURLOPT_WRITEFUNCTION => static function ($curl, string $piece) use (&$body, &$tooLarge): int {
                if (strlen($body) + strlen($piece) > self::MAX_REPLY_BYTES) {
                    $tooLarge = true;

                    return 0; // tells curl to stop
                }
                $body .= $piece;

                return strlen($piece);
            },
        ]);
        curl_exec($handle);
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);
        curl_close($handle);

        if ($tooLarge) {
            throw new ActionRefused('lichess.too_large');
        }
        if ($status === 0) {
            throw new ActionRefused('lichess.unavailable');
        }

        return ['status' => $status, 'body' => $body];
    }
}
