<?php

declare(strict_types=1);

namespace PhpChess\Http;

/**
 * One incoming HTTP request (ARCHITECTURE.md §5).
 *
 * Built once by public/index.php from PHP's globals, then passed to the kernel,
 * the router and the controllers. Code never reads $_GET, $_POST or $_SERVER
 * directly; it asks this object, so tests can build any request they need.
 *
 * The "base path" is the part of the URL where phpChess is installed, for
 * example "/chess" for an install in a subfolder, or "" in the web root (INS-02).
 * The "path" is the rest, always starting with "/": "/api/v1/status".
 */
final class Request
{
    /**
     * @param array<string, string> $query      the URL parameters (?a=b)
     * @param array<string, string> $form       the posted form fields
     * @param array<string, string> $headers    request headers, names in lower case
     * @param array<string, string> $cookies    the cookies sent
     */
    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $basePath = '',
        public readonly array $query = [],
        public readonly array $form = [],
        public readonly array $headers = [],
        public readonly array $cookies = [],
        public readonly string $clientIp = '127.0.0.1',
        public readonly bool $isHttps = false,
        public readonly string $host = 'localhost',
    ) {
    }

    // --- Building the request from PHP's globals ---

    /** Reads the current request from $_SERVER, $_GET, $_POST and $_COOKIE. */
    public static function fromGlobals(): self
    {
        $server = $_SERVER;
        $method = strtoupper(self::stringValue($server['REQUEST_METHOD'] ?? 'GET'));
        $requestUri = self::stringValue($server['REQUEST_URI'] ?? '/');
        $scriptName = self::stringValue($server['SCRIPT_NAME'] ?? '/index.php');

        $urlPath = (string) parse_url($requestUri, PHP_URL_PATH);
        $basePath = self::findBasePath($urlPath, $scriptName);
        $path = substr($urlPath, strlen($basePath));
        if ($path === '') {
            $path = '/';
        }

        $headers = [];
        foreach ($server as $name => $value) {
            if (is_string($name) && str_starts_with($name, 'HTTP_')) {
                $headerName = strtolower(str_replace('_', '-', substr($name, 5)));
                $headers[$headerName] = self::stringValue($value);
            }
        }

        $isHttps = self::stringValue($server['HTTPS'] ?? '') !== ''
            && self::stringValue($server['HTTPS'] ?? '') !== 'off';

        return new self(
            method: $method,
            path: $path,
            basePath: $basePath,
            query: self::stringArray($_GET),
            form: self::stringArray($_POST),
            headers: $headers,
            cookies: self::stringArray($_COOKIE),
            clientIp: self::stringValue($server['REMOTE_ADDR'] ?? '127.0.0.1'),
            isHttps: $isHttps,
            host: self::stringValue($server['HTTP_HOST'] ?? 'localhost'),
        );
    }

    /**
     * Works out where phpChess is installed from the URL and the script name.
     *
     * The script is always ".../public/index.php". When the root .htaccess sends
     * the request on to public/, the URL does not contain "/public", so the base
     * path is the folder above it. When the domain points at public/ itself,
     * or public/ is opened directly, the script's folder is the base path.
     */
    public static function findBasePath(string $urlPath, string $scriptName): string
    {
        $scriptFolder = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');

        if ($scriptFolder !== '' && str_starts_with($urlPath . '/', $scriptFolder . '/')) {
            return $scriptFolder;
        }

        if (str_ends_with($scriptFolder, '/public')) {
            return substr($scriptFolder, 0, -strlen('/public'));
        }

        return '';
    }

    // --- Reading values ---

    /** One URL parameter, or the default when it is missing. */
    public function queryValue(string $name, string $default = ''): string
    {
        return $this->query[$name] ?? $default;
    }

    /** One posted form field, or the default when it is missing. */
    public function formValue(string $name, string $default = ''): string
    {
        return $this->form[$name] ?? $default;
    }

    /** One header (name in any case), or the default when it is missing. */
    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /** True for requests to the JSON API under /api/v1/ (API-01). */
    public function isApi(): bool
    {
        return str_starts_with($this->path, '/api/');
    }

    /** The full address of the install, such as "https://example.org/chess". */
    public function baseUrl(): string
    {
        $scheme = $this->isHttps ? 'https' : 'http';

        return $scheme . '://' . $this->host . $this->basePath;
    }

    // --- Helpers for the untyped globals ---

    private static function stringValue(mixed $value): string
    {
        return is_scalar($value) ? (string) $value : '';
    }

    /**
     * Keeps only plain string values; arrays such as "a[]=1" are dropped,
     * because no phpChess form uses them.
     *
     * @param array<mixed> $values
     * @return array<string, string>
     */
    private static function stringArray(array $values): array
    {
        $result = [];
        foreach ($values as $name => $value) {
            if (is_scalar($value)) {
                $result[(string) $name] = (string) $value;
            }
        }

        return $result;
    }
}
