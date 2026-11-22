<?php

/**
 * Install test: drives the web installer of a running site, like a person would (INS-02).
 *
 * Used by CI on every release zip, once in a web root and once in a subfolder
 * (ARCHITECTURE.md §10). It opens the installer, clicks through all six steps
 * over real HTTP, then checks that the site answers, the installer is locked,
 * and the private folders can't be opened. Exit code 1 on any failure.
 *
 * Run:
 *     php tools/install-test.php <site-url> <db-host> <db-name> <db-user> <db-password> [prefix]
 * Example:
 *     php tools/install-test.php http://localhost/chess 127.0.0.1 phpchess_support root secret phpc_
 */

declare(strict_types=1);

// --- Settings from the command line ---

if (count($argv) < 6) {
    fwrite(STDERR, "Usage: php tools/install-test.php <site-url> <db-host> <db-name> <db-user> <db-password> [prefix]\n");
    exit(1);
}
[, $siteUrl, $databaseHost, $databaseName, $databaseUser, $databasePassword] = $argv;
$tablePrefix = $argv[6] ?? 'phpc_';
$siteUrl = rtrim($siteUrl, '/');
$cookieFile = sys_get_temp_dir() . '/phpchess-install-test-' . getmypid() . '.cookies';

// --- A tiny browser: keeps cookies and the last CSRF token ---

$lastCsrfToken = '';

/**
 * Sends one request and returns [status, body].
 *
 * @param array<string, string>|null $form        null for GET, the fields for POST
 * @param non-empty-string          $cookieFile  where the cookies are kept between requests
 * @return array{0: int, 1: string}
 */
function sendRequest(string $url, ?array $form, string $cookieFile): array
{
    global $lastCsrfToken;

    $curl = curl_init($url);
    curl_setopt_array($curl, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_COOKIEJAR => $cookieFile,
        CURLOPT_COOKIEFILE => $cookieFile,
        CURLOPT_TIMEOUT => 30,
    ]);
    if ($form !== null) {
        $form['_csrf'] = $lastCsrfToken;
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($form));
    }

    $body = (string) curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    curl_close($curl);

    if (preg_match('/name="_csrf" value="([0-9a-f]+)"/', $body, $found) === 1) {
        $lastCsrfToken = $found[1];
    }

    return [$status, $body];
}

function expectText(string $what, string $body, string $expectedText): void
{
    if (!str_contains($body, $expectedText)) {
        fwrite(STDERR, "FAILED: {$what}: expected \"{$expectedText}\". Page was:\n" . strip_tags($body) . "\n");
        exit(1);
    }
    echo "ok: {$what}\n";
}

// --- The six steps ---

[, $page] = sendRequest($siteUrl . '/', null, $cookieFile);
expectText('the site opens the installer', $page, 'Step 1');

[, $page] = sendRequest($siteUrl . '/install/preflight', [], $cookieFile);
expectText('pre-flight passed', $page, 'Step 2');

[, $page] = sendRequest($siteUrl . '/install/protection', [], $cookieFile);
expectText('protection self-test passed', $page, 'Step 3');

[, $page] = sendRequest($siteUrl . '/install/database', [
    'database_host' => $databaseHost,
    'database_port' => '3306',
    'database_name' => $databaseName,
    'database_user' => $databaseUser,
    'database_password' => $databasePassword,
    'table_prefix' => $tablePrefix,
], $cookieFile);
expectText('database saved', $page, 'Step 4');

[, $page] = sendRequest($siteUrl . '/install/admin', [
    'username' => 'admin',
    'email' => 'admin@example.test',
    'password' => 'install-test-password',
    'password_again' => 'install-test-password',
], $cookieFile);
expectText('admin details accepted', $page, 'Step 5');

for ($batch = 1; $batch <= 20 && !str_contains($page, 'Step 6'); $batch++) {
    [, $page] = sendRequest($siteUrl . '/install/migrations', [], $cookieFile);
}
expectText('migrations done', $page, 'Step 6');

[, $page] = sendRequest($siteUrl . '/install/finish', [], $cookieFile);
expectText('install finished', $page, 'phpChess is installed');
expectText('the installer removed itself', $page, 'removed itself');

// --- After the install ---

[$status] = sendRequest($siteUrl . '/install', null, $cookieFile);
if ($status !== 404) {
    fwrite(STDERR, "FAILED: the installer should be gone (404), got {$status}\n");
    exit(1);
}
echo "ok: installer gone\n";

[, $page] = sendRequest($siteUrl . '/api/v1/status', null, $cookieFile);
expectText('API answers', $page, '"server":"phpChess"');

foreach (['/config/config.php', '/storage/logs/', '/src/Version.php', '/vendor/autoload.php', '/composer.json'] as $privatePath) {
    [$status, $body] = sendRequest($siteUrl . $privatePath, null, $cookieFile);
    if ($status === 200 && !str_contains($body, 'There is no page at this address')) {
        fwrite(STDERR, "FAILED: {$privatePath} can be opened from the web\n");
        exit(1);
    }
}
echo "ok: private files are not served\n";

@unlink($cookieFile);
echo "Install test passed for {$siteUrl}\n";
