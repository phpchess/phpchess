<?php

declare(strict_types=1);

namespace PhpChess\Install;

use Closure;

/**
 * Self-test: are the private folders unreachable from the web? (INS-04, installer step 2)
 *
 * Only public/ may be served. Every other folder holds a deny .htaccess, and
 * the root .htaccess sends everything to public/ (A17). Some hosts ignore
 * .htaccess files; then config/ (database password) and storage/ (uploads,
 * logs) would be open to anyone.
 *
 * The test writes a small file with a random word into storage/ and config/,
 * asks this same web server for those files over HTTP, and looks for the word
 * in the answer. Found = exposed: the installer stops and shows the fix.
 * The files are removed again afterwards.
 *
 * The HTTP request is passed in as a function, so tests can fake the server.
 */
final class ProtectionSelfTest
{
    public const string RESULT_PROTECTED = 'protected';
    public const string RESULT_EXPOSED = 'exposed';
    public const string RESULT_UNKNOWN = 'unknown';

    private const string CANARY_FILE = 'protection-selftest.txt';

    /** The folders tested; each must have its own deny .htaccess. */
    public const array PRIVATE_FOLDERS = ['storage', 'config'];

    /**
     * @param Closure(string): ?string $fetchUrl  returns the body for a URL, or null when it can't connect
     */
    public function __construct(
        private readonly string $appRoot,
        private readonly Closure $fetchUrl,
    ) {
    }

    // --- Running the test ---

    /**
     * Runs the test against the install's address.
     *
     * @param string $installRootUrl  the address of the install folder, such as "https://example.org/chess"
     * @return array{result: string, exposedUrls: list<string>, testedUrls: list<string>}
     */
    public function run(string $installRootUrl): array
    {
        $secretWord = 'phpchess-canary-' . bin2hex(random_bytes(8));
        $testedUrls = [];
        $exposedUrls = [];
        $answeredCount = 0;

        foreach (self::PRIVATE_FOLDERS as $folder) {
            $canaryPath = $this->appRoot . '/' . $folder . '/' . self::CANARY_FILE;
            if (@file_put_contents($canaryPath, $secretWord) === false) {
                continue;
            }

            $url = rtrim($installRootUrl, '/') . '/' . $folder . '/' . self::CANARY_FILE;
            $testedUrls[] = $url;
            try {
                $body = ($this->fetchUrl)($url);
            } finally {
                @unlink($canaryPath);
            }

            if ($body === null) {
                continue;
            }
            $answeredCount++;
            if (str_contains($body, $secretWord)) {
                $exposedUrls[] = $url;
            }
        }

        $result = self::RESULT_PROTECTED;
        if ($exposedUrls !== []) {
            $result = self::RESULT_EXPOSED;
        } elseif ($answeredCount === 0) {
            $result = self::RESULT_UNKNOWN;
        }

        return ['result' => $result, 'exposedUrls' => $exposedUrls, 'testedUrls' => $testedUrls];
    }

    // --- The real HTTP request ---

    /**
     * Fetches a URL from this server and returns the body (any status), or null
     * when no connection could be made. Uses cURL when present, else PHP streams.
     *
     * Certificate checks are off for this one request: it goes to our own
     * server, often with a certificate made for another name, and only its
     * body is compared with the random word.
     */
    public static function fetchWithHttp(string $url): ?string
    {
        if (function_exists('curl_init')) {
            $curl = curl_init($url);
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 5,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
            ]);
            $body = curl_exec($curl);
            curl_close($curl);

            return is_string($body) ? $body : null;
        }

        $context = stream_context_create([
            'http' => ['timeout' => 5, 'ignore_errors' => true, 'follow_location' => 0],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $body = @file_get_contents($url, false, $context);

        return is_string($body) ? $body : null;
    }

    // --- Help text ---

    /** The nginx configuration for a VPS, shown when the test fails (INS-04). */
    public static function nginxSnippet(string $installFolder): string
    {
        $root = rtrim($installFolder, '/');

        return "# phpChess: serve only the public/ folder\n"
            . "root {$root}/public;\n"
            . "index index.php;\n\n"
            . "location / {\n"
            . "    try_files \$uri /index.php?\$query_string;\n"
            . "}\n\n"
            . "location ~ \\.php$ {\n"
            . "    include fastcgi_params;\n"
            . "    fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;\n"
            . "    fastcgi_pass unix:/run/php/php8.3-fpm.sock;\n"
            . "}\n";
    }
}
