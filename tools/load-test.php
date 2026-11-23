<?php

/**
 * Load test for timed play (C3-1): many live games polling at once.
 *
 * What it measures (written down in docs/proofs/c3-1-load-test.md):
 *   - the cost of one poll on the server (short "unchanged" answer and full answer);
 *   - the cost of one move;
 *   - move delivery time: from the moment a move was answered to the moment
 *     the opponent's page shows it;
 *   - time lost to polling: how much more a clock lost than the player
 *     really thought (should be zero or below: lag compensation);
 *   - database queries per request, and requests per second for the site.
 *
 * How it works:
 *   The main process installs phpChess into a temporary folder on the test
 *   database (table prefix "phpc_load_"), creates two players per game and
 *   starts the timed games. Then it starts one worker process per game. A
 *   worker plays both sides of its game through the real Kernel (routing,
 *   login session, CSRF, controllers, database), exactly as the board page
 *   does: it polls at the interval the server asks for, and when it is a
 *   player's turn it "thinks" 1 to 4 seconds and plays a random legal move.
 *   Each worker prints its numbers as one JSON line; the main process adds
 *   them up and removes the tables and the folder.
 *
 *   Not included: starting PHP for each request on a real web server (with
 *   OPcache a few milliseconds); the document adds that to the estimate.
 *
 * Run (the test database settings are the PHPCHESS_TEST_DB_* variables, as for the tests):
 *     php tools/load-test.php [--games=30] [--seconds=30] [--control=10+0]
 */

declare(strict_types=1);

use PhpChess\Domain\Chess\MoveGenerator;
use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Identity\PasswordRules;
use PhpChess\Domain\Identity\User;
use PhpChess\Infra\Database;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Migration\MigrationRunner;
use PhpChess\Infra\System\SettingsRepository;
use PhpChess\Kernel\Config;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\PlayParts;
use PhpChess\Tests\Support\RecordingMailer;
use PhpChess\Tests\Support\RecordingPushSender;
use PhpChess\Tests\Support\TestBrowser;
use PhpChess\Tests\Support\TestInstallFolder;

require dirname(__DIR__) . '/vendor/autoload.php';

const LOAD_TEST_PREFIX = 'phpc_load_';
const LOAD_TEST_PASSWORD = 'load-test-password-42';

// --- Command line ---

$options = getopt('', ['games::', 'seconds::', 'control::', 'worker::', 'folder::']);
$gameCount = max(1, (int) ($options['games'] ?? 30));
$seconds = max(5, (int) ($options['seconds'] ?? 30));
$timeControl = (string) ($options['control'] ?? '10+0');

if (isset($options['worker'])) {
    runWorker((int) $options['worker'], (string) ($options['folder'] ?? ''), $seconds);
    exit(0);
}

// --- Main process: set up, run the workers, add up ---

$config = loadTestConfig();
$database = Database::connect($config);
dropLoadTables($database);
$folder = new TestInstallFolder();
file_put_contents($folder->path . '/config/config.php', $config->withInstallState(true, '')->toFileContents());
(new MigrationRunner($database, MigrationRunner::loadFolder($folder->path . '/migrations')))->runPending();

$settings = new SettingsRepository($database);
$settings->set('play.max_live_games', $gameCount);
$users = new UserRepository($database);
$passwordHash = PasswordRules::hash(LOAD_TEST_PASSWORD);
$play = new PlayParts($database, $settings);
$gameIds = [];
for ($index = 1; $index <= $gameCount; $index++) {
    $whiteId = $users->create("loadwhite{$index}", "loadwhite{$index}@example.test", $passwordHash, User::ROLE_PLAYER, User::STATE_ACTIVE);
    $blackId = $users->create("loadblack{$index}", "loadblack{$index}@example.test", $passwordHash, User::ROLE_PLAYER, User::STATE_ACTIVE);
    $gameIds[] = $play->games()->start($whiteId, $blackId, 3, false, '', time(), $timeControl, (int) floor(microtime(true) * 1000));
}
echo "Started {$gameCount} games of {$timeControl}; running {$seconds} s ...\n";

$questionsBefore = globalQuestions($database);
$processes = [];
foreach ($gameIds as $gameId) {
    $command = escapeshellarg(PHP_BINARY) . ' ' . escapeshellarg(__FILE__)
        . " --worker={$gameId} --seconds={$seconds} --folder=" . escapeshellarg($folder->path);
    $process = proc_open($command, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if ($process === false) {
        fwrite(STDERR, "Could not start a worker.\n");
        exit(1);
    }
    $processes[] = ['process' => $process, 'out' => $pipes[1], 'err' => $pipes[2]];
}

$results = [];
foreach ($processes as $worker) {
    $output = (string) stream_get_contents($worker['out']);
    $errors = (string) stream_get_contents($worker['err']);
    proc_close($worker['process']);
    $decoded = json_decode(trim($output), true);
    if (!is_array($decoded)) {
        fwrite(STDERR, "A worker failed: " . trim($output . ' ' . $errors) . "\n");
        continue;
    }
    $results[] = $decoded;
}
$questions = globalQuestions($database) - $questionsBefore;

printReport($results, $seconds, $questions, $gameCount, $timeControl);

dropLoadTables($database);
$folder->remove();

// --- The worker: one game, both players ---

function runWorker(int $gameId, string $folder, int $seconds): void
{
    // One database connection for all of this worker's requests. A web server
    // opens one per request; on Windows thousands of short connections use up
    // the local ports (TIME_WAIT), so the test keeps one. Opening a local
    // connection costs about 1 ms; the estimate in the document adds it.
    $config = loadTestConfig();
    $database = Database::connect($config);
    $kernel = new Kernel($folder, connectDatabase: fn (Config $unused): Database => $database, mailer: new RecordingMailer(), pushSender: new RecordingPushSender());
    $game = (new PlayParts($database, new SettingsRepository($database)))->gameRepository()->find($gameId);
    if ($game === null) {
        echo json_encode(['error' => 'no game']);

        return;
    }
    $names = (new UserRepository($database))->usernamesOf([$game->whiteId, $game->blackId]);

    // One logged-in "browser" per player, with what their page knows.
    $players = [];
    foreach (['w' => $game->whiteId, 'b' => $game->blackId] as $colour => $userId) {
        $browser = new TestBrowser($kernel, '/chess', '10.0.' . ($gameId % 250) . '.' . ($colour === 'w' ? 1 : 2));
        $browser->submit('/login', '/login', ['name_or_email' => $names[$userId], 'password' => LOAD_TEST_PASSWORD]);
        $browser->get('/games/' . $gameId);   // the board page: also gives the CSRF token
        $players[$colour] = [
            'browser' => $browser, 'ply' => 0, 'version' => '', 'state' => null,
            'nextPollAt' => 0.0, 'moveAt' => null, 'receivedAt' => null, 'clockAtReceipt' => null,
        ];
    }

    $timings = ['poll_unchanged' => [], 'poll_full' => [], 'move' => []];
    $deliveries = [];
    $timeLost = [];
    $movedAt = [];    // ply => when the mover had the answer (ms)
    $endAt = microtime(true) + $seconds;

    while (microtime(true) < $endAt) {
        $nowMs = microtime(true) * 1000;
        foreach (['w', 'b'] as $colour) {
            $player = &$players[$colour];

            // --- Moving, when it is this player's turn and the thinking time is over ---
            if ($player['moveAt'] !== null && $nowMs >= $player['moveAt'] && is_array($player['state'])) {
                $position = Position::fromFen((string) $player['state']['fen']);
                $moves = MoveGenerator::legalMoves($position);
                if ($moves !== []) {
                    $move = $moves[array_rand($moves)];
                    $started = microtime(true);
                    $response = $player['browser']->postJson('/games/' . $gameId . '/move', ['ply' => (string) $player['ply'], 'move' => $move->toUci()]);
                    $timings['move'][] = (microtime(true) - $started) * 1000;
                    $answer = json_decode($response->body, true);
                    if (is_array($answer) && isset($answer['ply'])) {
                        $thinkMs = $started * 1000 - (float) $player['receivedAt'];
                        $clockKey = $colour === 'w' ? 'white_ms' : 'black_ms';
                        $increment = 0;   // the load test's time controls may have an increment; take it from the control
                        if (is_string($answer['time_control'] ?? null)) {
                            $increment = (int) explode('+', $answer['time_control'])[1] * 1000;
                        }
                        if (is_array($answer['clock'] ?? null) && $player['clockAtReceipt'] !== null) {
                            $clockLoss = $player['clockAtReceipt'] - ((int) $answer['clock'][$clockKey] - $increment);
                            $timeLost[] = $clockLoss - $thinkMs;
                        }
                        $player['ply'] = (int) $answer['ply'];
                        $player['version'] = (string) $answer['version'];
                        $player['state'] = $answer;
                        $movedAt[(int) $answer['ply']] = microtime(true) * 1000;
                    }
                }
                $player['moveAt'] = null;
            }

            // --- Polling, at the interval the server asked for ---
            if ($nowMs >= $player['nextPollAt']) {
                $started = microtime(true);
                $response = $player['browser']->get('/games/' . $gameId . '/state', ['since' => (string) $player['ply'], 'v' => $player['version']]);
                $elapsedMs = (microtime(true) - $started) * 1000;
                $answer = json_decode($response->body, true);
                if (!is_array($answer)) {
                    continue;
                }
                $player['nextPollAt'] = microtime(true) * 1000 + (int) ($answer['next_poll_ms'] ?? 1000);
                if (($answer['unchanged'] ?? false) === true) {
                    $timings['poll_unchanged'][] = $elapsedMs;
                    continue;
                }
                $timings['poll_full'][] = $elapsedMs;
                $newPly = (int) $answer['ply'];
                if ($newPly > $player['ply'] && isset($movedAt[$newPly])) {
                    $deliveries[] = microtime(true) * 1000 - $movedAt[$newPly];
                }
                $player['ply'] = $newPly;
                $player['version'] = (string) $answer['version'];
                $player['state'] = $answer;
                $isMyTurn = $answer['state'] === 'running' && $answer['side_to_move'] === $colour;
                if ($isMyTurn && $player['moveAt'] === null) {
                    $player['receivedAt'] = microtime(true) * 1000;
                    $player['clockAtReceipt'] = is_array($answer['clock'] ?? null) ? (int) $answer['clock'][$colour === 'w' ? 'white_ms' : 'black_ms'] : null;
                    $player['moveAt'] = $player['receivedAt'] + random_int(1000, 4000);
                }
            }
            unset($player);
        }
        usleep(20_000);
    }

    echo json_encode(['timings' => $timings, 'deliveries' => $deliveries, 'time_lost' => $timeLost]);
}

// --- Helpers ---

function loadTestConfig(): Config
{
    $name = (string) getenv('PHPCHESS_TEST_DB_NAME');
    if ($name === '') {
        fwrite(STDERR, "Set the PHPCHESS_TEST_DB_* variables (see tests/Support/TestDatabase.php).\n");
        exit(1);
    }

    return new Config(
        databaseHost: (string) (getenv('PHPCHESS_TEST_DB_HOST') ?: '127.0.0.1'),
        databasePort: (int) (getenv('PHPCHESS_TEST_DB_PORT') ?: '3306'),
        databaseName: $name,
        databaseUser: (string) getenv('PHPCHESS_TEST_DB_USER'),
        databasePassword: (string) getenv('PHPCHESS_TEST_DB_PASSWORD'),
        tablePrefix: LOAD_TEST_PREFIX,
    );
}

/** Removes every table of the load test (prefix phpc_load_), children first. */
function dropLoadTables(Database $database): void
{
    $database->run('SET FOREIGN_KEY_CHECKS = 0');
    foreach ($database->fetchAll("SHOW TABLES LIKE 'phpc\\_load\\_%'") as $row) {
        $table = (string) array_values($row)[0];
        $database->run('DROP TABLE IF EXISTS `' . str_replace('`', '', $table) . '`');
    }
    $database->run('SET FOREIGN_KEY_CHECKS = 1');
}

/** The server's count of statements so far (for queries per request). */
function globalQuestions(Database $database): int
{
    $row = $database->fetchRow("SHOW GLOBAL STATUS LIKE 'Questions'");

    return $row === null ? 0 : (int) ($row['Value'] ?? 0);
}

/**
 * @param list<float> $values
 */
function percentile(array $values, float $share): float
{
    if ($values === []) {
        return 0.0;
    }
    sort($values);

    return $values[(int) min(count($values) - 1, floor($share * count($values)))];
}

/**
 * @param list<array<string, mixed>> $results
 */
function printReport(array $results, int $seconds, int $questions, int $gameCount, string $timeControl): void
{
    $all = ['poll_unchanged' => [], 'poll_full' => [], 'move' => []];
    $deliveries = [];
    $timeLost = [];
    foreach ($results as $result) {
        foreach ($all as $kind => $unused) {
            $all[$kind] = [...$all[$kind], ...array_map('floatval', (array) ($result['timings'][$kind] ?? []))];
        }
        $deliveries = [...$deliveries, ...array_map('floatval', (array) ($result['deliveries'] ?? []))];
        $timeLost = [...$timeLost, ...array_map('floatval', (array) ($result['time_lost'] ?? []))];
    }
    $requests = count($all['poll_unchanged']) + count($all['poll_full']) + count($all['move']);

    printf("\nGames: %d of %s, %d s, PHP %s\n", $gameCount, $timeControl, $seconds, PHP_VERSION);
    printf("Requests: %d (%.1f per second for the site)\n", $requests, $requests / $seconds);
    printf("Database statements: %d (%.1f per request)\n", $questions, $requests === 0 ? 0 : $questions / $requests);
    foreach ($all as $kind => $values) {
        printf("%-15s n=%5d  median %6.1f ms  p95 %6.1f ms  max %6.1f ms\n", $kind, count($values), percentile($values, 0.5), percentile($values, 0.95), $values === [] ? 0 : max($values));
    }
    printf("Move delivery   n=%5d  median %6.0f ms  p95 %6.0f ms\n", count($deliveries), percentile($deliveries, 0.5), percentile($deliveries, 0.95));
    printf("Time lost       n=%5d  median %6.0f ms  p95 %6.0f ms  max %6.0f ms  (clock loss minus real thinking time)\n", count($timeLost), percentile($timeLost, 0.5), percentile($timeLost, 0.95), $timeLost === [] ? 0 : max($timeLost));
}
