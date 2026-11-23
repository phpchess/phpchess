<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Account;

use PhpChess\Domain\ActionRefused;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Infra\Identity\ApiTokenRepository;
use PhpChess\Kernel\Services;

/**
 * API access tokens per device (ID-10), for apps that use the API (API-01).
 *
 * Addresses (Routes.php), members only:
 *   GET  /account/devices              my tokens: device, made, last used
 *   POST /account/devices              make a token; it is shown once, on the answer page
 *   POST /account/devices/{id}/revoke  revoke a token; it stops working at once
 */
final class DevicesController
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $pages,
    ) {
    }

    public function list(Request $request, string $newToken = ''): Response
    {
        return $this->pages->render($request, 'account/devices', 'devices.title', [
            'tokens' => $this->services->apiTokens()->listFor($this->pages->user()->id),
            'newToken' => $newToken,
        ]);
    }

    public function create(Request $request): Response
    {
        $user = $this->pages->user();
        $deviceName = trim($request->formValue('device_name'));
        if ($deviceName === '' || mb_strlen($deviceName) > 80) {
            return $this->pages->refused($request, '/account/devices', new ActionRefused('devices.name_rules'));
        }
        if ($this->services->apiTokens()->countFor($user->id) >= ApiTokenRepository::MAX_PER_USER) {
            return $this->pages->refused($request, '/account/devices', new ActionRefused('devices.too_many', ['max' => ApiTokenRepository::MAX_PER_USER]));
        }
        $token = $this->services->apiTokens()->create($user->id, $deviceName);

        // Shown on this answer only: the token is not stored, just its hash.
        return $this->list($request, $token)->withHeader('Cache-Control', 'no-store');
    }

    /**
     * @param array<string, string> $parts
     */
    public function revoke(Request $request, array $parts): Response
    {
        $this->services->apiTokens()->revoke(MemberPages::idFrom($parts), $this->pages->user()->id);

        return $this->pages->done($request, '/account/devices', 'devices.revoked');
    }
}
