<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Tournament;

use PhpChess\Domain\ActionRefused;
use PhpChess\Domain\Identity\User;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Http\Web\MemberPages;
use PhpChess\Http\Web\Play\PlayPages;
use PhpChess\Kernel\Services;

/**
 * Helpers shared by the tournament pages (C4-1): finding the tournament of
 * an address with the access check (K14, A106), and answering after a
 * button (back to the page with a notice).
 *
 * Used by TournamentsController (players) and TournamentManageController
 * (organisers).
 */
final class TournamentPages
{
    public function __construct(
        private readonly Services $services,
        private readonly MemberPages $memberPages,
    ) {
    }

    public function user(): User
    {
        return $this->memberPages->user();
    }

    /**
     * The tournament of /tournaments/{id}, or null when it does not exist or
     * the member may not see it (a closed tournament, A106): both answer
     * 404, so a hidden tournament is not revealed.
     *
     * @param array<string, string> $parts
     */
    public function visibleTournament(array $parts): ?Tournament
    {
        $tournament = $this->services->tournaments()->repository()->find(PlayPages::idFrom($parts));
        if ($tournament === null || !$this->services->tournaments()->access()->maySee($tournament, $this->user())) {
            return null;
        }

        return $tournament;
    }

    /**
     * The tournament for a manage page: only for its organisers and admins.
     *
     * @param array<string, string> $parts
     */
    public function managedTournament(array $parts): ?Tournament
    {
        $tournament = $this->visibleTournament($parts);
        if ($tournament === null || !$this->services->tournaments()->access()->mayManage($tournament, $this->user())) {
            return null;
        }

        return $tournament;
    }

    /**
     * Runs one button and goes back to $path with "done" or the refusal.
     *
     * @param callable(): void $action
     */
    public function run(Request $request, string $path, string $doneKey, callable $action): Response
    {
        try {
            $action();
        } catch (ActionRefused $refusal) {
            return $this->memberPages->refused($request, $path, $refusal);
        }

        return $this->memberPages->done($request, $path, $doneKey);
    }

    /**
     * @param array<string, mixed> $variables
     */
    public function render(Request $request, string $template, string $title, array $variables = []): Response
    {
        return $this->services->pages()->render($request, $template, $title, $variables);
    }

    public function notFound(Request $request): Response
    {
        return $this->memberPages->notFound($request);
    }

    /**
     * @param array<string, string|int> $values
     */
    public function text(string $key, array $values = []): string
    {
        return $this->memberPages->text($key, $values);
    }
}
