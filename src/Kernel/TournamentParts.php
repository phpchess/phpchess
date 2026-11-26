<?php

declare(strict_types=1);

namespace PhpChess\Kernel;

use PhpChess\Domain\Tournament\LeagueDivisions;
use PhpChess\Domain\Tournament\TournamentAccess;
use PhpChess\Domain\Tournament\TournamentDesk;
use PhpChess\Domain\Tournament\TournamentRounds;
use PhpChess\Domain\Tournament\Tournaments;
use PhpChess\Infra\Database;
use PhpChess\Infra\Identity\UserRepository;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * Builds the tournament services (C4-1) from the play services.
 *
 * Like PlayParts: a web request (Services) and the job runner
 * (Kernel::jobRunner) need the same tournament services, so they are put
 * together by hand in this one place.
 */
final class TournamentParts
{
    public function __construct(
        private readonly Database $database,
        private readonly PlayParts $play,
    ) {
    }

    public function repository(): TournamentRepository
    {
        return new TournamentRepository($this->database);
    }

    public function access(): TournamentAccess
    {
        return new TournamentAccess($this->repository());
    }

    public function tournaments(): Tournaments
    {
        return new Tournaments($this->repository(), $this->access(), $this->play->games(), new UserRepository($this->database), $this->play->computerGames());
    }

    public function rounds(): TournamentRounds
    {
        return new TournamentRounds($this->database, $this->repository(), $this->play->games(), $this->play->gameRepository(), $this->play->ratings(), $this->access());
    }

    public function desk(): TournamentDesk
    {
        return new TournamentDesk($this->repository(), $this->access(), $this->rounds(), $this->play->games(), $this->play->gameRepository(), new UserRepository($this->database));
    }

    public function league(): LeagueDivisions
    {
        return new LeagueDivisions($this->repository(), $this->access(), $this->rounds());
    }
}
