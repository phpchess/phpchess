<?php

declare(strict_types=1);

namespace PhpChess\Http\Web\Play;

use PhpChess\Domain\Play\PlayRefused;
use PhpChess\Domain\Play\Vacations;
use PhpChess\Http\Request;
use PhpChess\Http\Response;
use PhpChess\Kernel\Services;

/**
 * Vacation (G-08, A29): whole days off that pause the clocks of all the
 * player's correspondence games.
 *
 * Addresses (Routes.php), members only:
 *   GET  /account/vacation          planned days and days left this year
 *   POST /account/vacation          take days off: first day, number of days
 *   POST /account/vacation/cancel   cancel the planned days from tomorrow on
 */
final class VacationController
{
    public function __construct(
        private readonly Services $services,
        private readonly PlayPages $pages,
    ) {
    }

    public function show(Request $request): Response
    {
        $user = $this->pages->user();
        $vacations = $this->services->play()->vacations();
        $now = $this->services->now;

        return $this->services->pages()->render($request, 'play/vacation', $this->pages->text('vacation.title'), [
            'upcomingDays' => $vacations->upcoming($user->id, $now),
            'daysLeft' => $vacations->daysLeftThisYear($user->id, $now),
            'daysPerYear' => Vacations::DAYS_PER_YEAR,
            'today' => gmdate('Y-m-d', $now),
        ]);
    }

    public function take(Request $request): Response
    {
        try {
            $this->services->play()->vacations()->take(
                $this->pages->user()->id,
                $request->formValue('first_day'),
                (int) $request->formValue('days', '0'),
                $this->services->now,
            );
        } catch (PlayRefused $refusal) {
            return $this->pages->refused($request, '/account/vacation', $refusal);
        }

        return $this->pages->done($request, '/account/vacation', 'vacation.saved');
    }

    public function cancel(Request $request): Response
    {
        $this->services->play()->vacations()->cancelPlanned($this->pages->user()->id, $this->services->now);

        return $this->pages->done($request, '/account/vacation', 'vacation.cancelled');
    }
}
