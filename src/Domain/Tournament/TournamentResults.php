<?php

declare(strict_types=1);

namespace PhpChess\Domain\Tournament;

use PhpChess\Domain\Play\Game;
use PhpChess\Infra\Tournament\TournamentRepository;

/**
 * Copies a game's result into its tournament board (K3, T-07).
 *
 * Games::end() calls gameEnded() for every game that ends, inside the
 * game's lock, and Games::correctResult() calls it when an organiser or
 * admin corrects a result. So a tournament never writes a result itself:
 * its scores always come from the games (K3). A game that is not a
 * tournament game is simply ignored.
 *
 * The reason tells forfeits apart from games played to the end: a game
 * ended at the round deadline (forfeit rule, A102) or by a withdrawal
 * (A108) is a forfeit, not a win on the board.
 *
 * Works only with the tournament tables, so Games can call it without a
 * circle (the round services call Games, Games calls this).
 */
final class TournamentResults
{
    // --- End reasons written by the tournament services (shown on the game page) ---
    /** The round deadline passed and the forfeit rule decided the game (A102). */
    public const string REASON_ROUND_DEADLINE = 'round_deadline';
    /** The round deadline passed and nobody had moved: a double forfeit (A102). */
    public const string REASON_NO_MOVES = 'round_deadline_no_moves';
    /** A player withdrew from the tournament (A108). */
    public const string REASON_WITHDRAWN = 'tournament_withdrawal';
    /** An organiser or admin decided the result (rule "organiser decides", a dispute, a correction). */
    public const string REASON_ORGANISER = 'organiser_decision';

    public function __construct(private readonly TournamentRepository $tournaments)
    {
    }

    /**
     * Writes the board's outcome and score from the game's result. Called
     * inside the game's transaction, so the game and its board are saved
     * together.
     */
    public function gameEnded(Game $game, string $result, string $reason): void
    {
        // Most games are not tournament games; this first question also works before migration 12.
        if ($this->tournaments->tournamentIdOfGame($game->id) === null) {
            return;
        }
        $pairing = $this->tournaments->findPairingByGame($game->id);
        if ($pairing === null) {
            return;
        }
        $isForfeit = $reason === self::REASON_ROUND_DEADLINE || $reason === self::REASON_WITHDRAWN;

        [$outcome, $whiteHalves, $blackHalves] = match (true) {
            $reason === self::REASON_NO_MOVES => [Pairing::OUTCOME_DOUBLE_FORFEIT, 0, 0],
            $result === Game::RESULT_ABORTED => [Pairing::OUTCOME_ABORTED, 0, 0],
            $result === Game::RESULT_WHITE_WON => [$isForfeit ? Pairing::OUTCOME_WHITE_FORFEIT_WIN : Pairing::OUTCOME_WHITE_WON, 2, 0],
            $result === Game::RESULT_BLACK_WON => [$isForfeit ? Pairing::OUTCOME_BLACK_FORFEIT_WIN : Pairing::OUTCOME_BLACK_WON, 0, 2],
            default => [$isForfeit ? Pairing::OUTCOME_ADJUDICATED_DRAW : Pairing::OUTCOME_DRAW, 1, 1],
        };

        // The pairing knows who is White; the game must agree (a replay keeps the colours).
        if ($game->whiteId !== $pairing->whiteId) {
            [$whiteHalves, $blackHalves] = [$blackHalves, $whiteHalves];
            $outcome = self::swappedOutcome($outcome);
        }
        $this->tournaments->writeOutcome($pairing->id, $outcome, $whiteHalves, $blackHalves);
    }

    /** The same outcome seen from the other side, for a game played with the colours reversed. */
    private static function swappedOutcome(string $outcome): string
    {
        return match ($outcome) {
            Pairing::OUTCOME_WHITE_WON => Pairing::OUTCOME_BLACK_WON,
            Pairing::OUTCOME_BLACK_WON => Pairing::OUTCOME_WHITE_WON,
            Pairing::OUTCOME_WHITE_FORFEIT_WIN => Pairing::OUTCOME_BLACK_FORFEIT_WIN,
            Pairing::OUTCOME_BLACK_FORFEIT_WIN => Pairing::OUTCOME_WHITE_FORFEIT_WIN,
            default => $outcome,
        };
    }
}
