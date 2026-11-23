<?php

declare(strict_types=1);

namespace PhpChess\Infra\Notify;

use Nyholm\Psr7\Response;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * A very small HTTP client on PHP's curl, for the web-push library (N-03).
 *
 * web-push hands over a finished request (address, headers, encrypted
 * body); this class sends it with curl and returns the answer. It is the
 * only place in phpChess that opens a connection to another server, and it
 * is only used while the "push notifications" switch is on (K9).
 * Our own class instead of Guzzle keeps the dependencies small (A16).
 */
final class CurlHttpClient implements ClientInterface
{
    private const int TIMEOUT_SECONDS = 10;

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $headers = [];
        foreach ($request->getHeaders() as $name => $values) {
            $headers[] = $name . ': ' . implode(', ', $values);
        }

        $method = $request->getMethod() === '' ? 'POST' : $request->getMethod();
        $handle = curl_init((string) $request->getUri());
        if ($handle === false) {
            throw new CurlRequestFailed('curl could not start.');
        }
        curl_setopt_array($handle, [
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_POSTFIELDS => (string) $request->getBody(),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HEADER => false,
            CURLOPT_TIMEOUT => self::TIMEOUT_SECONDS,
            CURLOPT_PROTOCOLS => CURLPROTO_HTTPS,
        ]);
        $body = curl_exec($handle);
        if (!is_string($body)) {
            $problem = curl_error($handle);
            throw new CurlRequestFailed('The push service could not be reached: ' . $problem);
        }
        $status = (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE);

        return new Response($status, [], $body);
    }
}
