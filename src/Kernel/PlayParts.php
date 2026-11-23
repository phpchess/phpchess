<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Domain\Notify\Notices;
use PhpChess\Domain\Play\Challenges;
use PhpChess\Domain\Play\Games;
use PhpChess\Domain\Play\Vacations;
use PhpChess\Domain\Rating\Ratings;
use PhpChess\Infra\Database;
use PhpChess\Infra\Notify\NoticeRepository;
use PhpChess\Infra\Play\ChallengeRepository;
use PhpChess\Infra\Play\GameRepository;
use PhpChess\Infra\Play\VacationRepository;
use PhpChess\Infra\Rating\RatingRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Builds the play services (C2-1) from a database connection.
 *
 * Both a web request (Services) and the background jobs (Kernel::jobRunner)
 * need the same Games, Challenges and Ratings; this class puts them
 * together by hand in one place, so the two can't drift apart.
 */
final class PlayParts
{
    private ?Games $games = null;

    public function __construct(
        private readonly Database $database,
        private readonly SettingsRepository $settings,
    ) {
    }

    public function gameRepository(): GameRepository
    {
        return new GameRepository($this->database);
    }

    public function challengeRepository(): ChallengeRepository
    {
        return new ChallengeRepository($this->database);
    }

    public function noticeRepository(): NoticeRepository
    {
        return new NoticeRepository($this->database);
    }

    public function ratingRepository(): RatingRepository
    {
        return new RatingRepository($this->database);
    }

    public function ratings(): Ratings
    {
        return new Ratings($this->ratingRepository(), $this->settings);
    }

    public function notices(): Notices
    {
        return new Notices($this->noticeRepository());
    }

    public function games(): Games
    {
        return $this->games ??= new Games(
            $this->database,
            $this->gameRepository(),
            new VacationRepository($this->database),
            $this->ratings(),
            $this->notices(),
            $this->settings,
        );
    }

    public function challenges(): Challenges
    {
        return new Challenges($this->database, $this->challengeRepository(), $this->games(), $this->notices(), $this->settings);
    }

    public function vacations(): Vacations
    {
        return new Vacations(new VacationRepository($this->database), $this->games());
    }
}
