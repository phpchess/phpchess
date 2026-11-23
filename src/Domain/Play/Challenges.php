<?php

declare(strict_types=1);

namespace PhpChess\Domain\Play;

use PhpChess\Domain\Notify\Notices;
use PhpChess\Infra\Database;
use PhpChess\Infra\Play\ChallengeRepository;
use PhpChess\Infra\System\SettingsRepository;

/**
 * Challenges: open, direct and invite links; the one-tap "Play" button
 * (G-05, G-06, G-20, G-21, A28, A35).
 *
 * Called by the ChallengeController. Accepting a challenge starts the game
 * through Games::start(), in the same transaction as taking the challenge,
 * so a challenge never makes two games (an open challenge can't be accepted
 * twice).
 *
 * Admin settings used here (A28, A35, A37):
 *   play.max_days_per_move   1..31, default 30
 *   challenge.open_days      default 7
 *   challenge.direct_days    default 14
 *   challenge.invite_days    default 7 (and an invite link is used once)
 *   play.fen_in_direct       players may give a start position in direct
 *                            and invite challenges, default on
 */
final class Challenges
{
    public function __construct(
        private readonly Database $database,
        private readonly ChallengeRepository $challenges,
        private readonly Games $games,
        private readonly Notices $notices,
        private readonly SettingsRepository $settings,
    ) {
    }

    // --- Creating ---

    /** Creates an open challenge anyone may accept. Returns its id. */
    public function createOpen(int $challengerId, string $colour, int $daysPerMove, bool $isRated, int $now): int
    {
        return $this->challenges->create(
            Challenge::KIND_OPEN,
            $challengerId,
            null,
            null,
            self::checkedColour($colour),
            $this->checkedDays($daysPerMove),
            $isRated,
            null,
            $now,
            $now + $this->expiryDays('challenge.open_days', 7) * Clocks::SECONDS_PER_DAY,
        );
    }

    /** Sends a challenge to one player, who is told about it (N-01). Returns its id. */
    public function createDirect(int $challengerId, int $opponentId, string $colour, int $daysPerMove, bool $isRated, string $startFen, int $now): int
    {
        if ($opponentId === $challengerId) {
            throw new PlayRefused('play.not_against_yourself');
        }
        $challengeId = $this->challenges->create(
            Challenge::KIND_DIRECT,
            $challengerId,
            $opponentId,
            null,
            self::checkedColour($colour),
            $this->checkedDays($daysPerMove),
            $isRated,
            $this->checkedFen($startFen),
            $now,
            $now + $this->expiryDays('challenge.direct_days', 14) * Clocks::SECONDS_PER_DAY,
        );
        $this->notices->tellAboutChallenge($opponentId, Notices::CHALLENGE_RECEIVED, $challengeId, null, $challengerId, $now);

        return $challengeId;
    }

    /**
     * Creates an invite link (G-21). Returns the secret token for the link;
     * only its hash is stored, like other one-time tokens.
     *
     * @return array{challenge_id: int, token: string}
     */
    public function createInvite(int $challengerId, string $colour, int $daysPerMove, bool $isRated, string $startFen, int $now): array
    {
        $token = bin2hex(random_bytes(16));
        $challengeId = $this->challenges->create(
            Challenge::KIND_INVITE,
            $challengerId,
            null,
            hash('sha256', $token),
            self::checkedColour($colour),
            $this->checkedDays($daysPerMove),
            $isRated,
            $this->checkedFen($startFen),
            $now,
            $now + $this->expiryDays('challenge.invite_days', 7) * Clocks::SECONDS_PER_DAY,
        );

        return ['challenge_id' => $challengeId, 'token' => $token];
    }

    // --- Accepting ---

    /**
     * Accepts a challenge and starts the game; returns the game id. Throws
     * PlayRefused when it is your own, not meant for you, or already taken
     * or expired.
     */
    public function accept(int $challengeId, int $userId, int $now): int
    {
        return $this->database->transaction(function () use ($challengeId, $userId, $now): int {
            $challenge = $this->challenges->find($challengeId);
            if ($challenge === null || !$challenge->isWaiting($now)) {
                throw new PlayRefused('challenge.no_longer_open');
            }
            if ($challenge->challengerId === $userId) {
                throw new PlayRefused('challenge.own');
            }
            if ($challenge->kind === Challenge::KIND_DIRECT && $challenge->opponentId !== $userId) {
                throw new PlayRefused('challenge.not_for_you');
            }
            if (!$this->challenges->take($challenge->id, $userId, $now)) {
                throw new PlayRefused('challenge.no_longer_open');
            }

            [$whiteId, $blackId] = self::colours($challenge, $userId);
            $gameId = $this->games->start($whiteId, $blackId, $challenge->daysPerMove, $challenge->isRated, $challenge->startFen ?? '', $now);
            $this->challenges->setGame($challenge->id, $gameId);
            $this->notices->tellAboutChallenge($challenge->challengerId, Notices::CHALLENGE_ACCEPTED, $challenge->id, $gameId, $userId, $now);

            return $gameId;
        });
    }

    /** The waiting invite challenge for a link token, or null (unknown, used or expired). */
    public function findInvite(string $token, int $now): ?Challenge
    {
        if (preg_match('/^[0-9a-f]{32}$/', $token) !== 1) {
            return null;
        }
        $challenge = $this->challenges->findByInviteTokenHash(hash('sha256', $token));

        return $challenge !== null && $challenge->isWaiting($now) ? $challenge : null;
    }

    /** The receiver says no to a direct challenge. */
    public function decline(int $challengeId, int $userId): void
    {
        $challenge = $this->challenges->find($challengeId);
        if ($challenge === null || $challenge->kind !== Challenge::KIND_DIRECT || $challenge->opponentId !== $userId) {
            throw new PlayRefused('challenge.not_for_you');
        }
        $this->challenges->close($challengeId, Challenge::STATE_DECLINED);
    }

    /** The sender takes a challenge back. */
    public function cancel(int $challengeId, int $userId): void
    {
        $challenge = $this->challenges->find($challengeId);
        if ($challenge === null || $challenge->challengerId !== $userId) {
            throw new PlayRefused('challenge.not_yours');
        }
        $this->challenges->close($challengeId, Challenge::STATE_CANCELLED);
    }

    // --- The one-tap "Play" button (G-20) ---

    /**
     * Pairs the player with whoever is waiting with the same days per move
     * and rated setting; when nobody is, opens a challenge for them.
     *
     * @return array{game_id: int|null, challenge_id: int|null}
     */
    public function quickPlay(int $userId, int $daysPerMove, bool $isRated, int $now): array
    {
        $daysPerMove = $this->checkedDays($daysPerMove);
        foreach ($this->challenges->waitingOpen($now) as $challenge) {
            if ($challenge->challengerId === $userId) {
                return ['game_id' => null, 'challenge_id' => $challenge->id];
            }
            if ($challenge->daysPerMove !== $daysPerMove || $challenge->isRated !== $isRated) {
                continue;
            }
            try {
                return ['game_id' => $this->accept($challenge->id, $userId, $now), 'challenge_id' => null];
            } catch (PlayRefused) {
                // Someone else was first; try the next one.
                continue;
            }
        }

        return ['game_id' => null, 'challenge_id' => $this->createOpen($userId, Challenge::COLOUR_RANDOM, $daysPerMove, $isRated, $now)];
    }

    // --- Settings and checks ---

    /** The admin's highest days per move (A28). */
    public function maximumDays(): int
    {
        return Clocks::limitDays($this->settings->getInt('play.max_days_per_move', Clocks::DEFAULT_MAX_DAYS), Clocks::HIGHEST_ALLOWED_DAYS);
    }

    /** True when players may give a start position (A37). */
    public function isFenAllowed(): bool
    {
        return $this->settings->getBool('play.fen_in_direct', true);
    }

    private function checkedDays(int $daysPerMove): int
    {
        if ($daysPerMove < 1 || $daysPerMove > $this->maximumDays()) {
            throw new PlayRefused('challenge.days_out_of_range', ['max' => $this->maximumDays()]);
        }

        return $daysPerMove;
    }

    private function checkedFen(string $startFen): ?string
    {
        if (trim($startFen) === '') {
            return null;
        }
        if (!$this->isFenAllowed()) {
            throw new PlayRefused('challenge.fen_not_allowed');
        }

        return Games::checkedStartPosition($startFen)->toFen();
    }

    private static function checkedColour(string $colour): string
    {
        return in_array($colour, Challenge::COLOURS, true) ? $colour : Challenge::COLOUR_RANDOM;
    }

    private function expiryDays(string $setting, int $default): int
    {
        return max(1, min(365, $this->settings->getInt($setting, $default)));
    }

    /**
     * White and black for a challenge: the challenger's colour choice, or a coin toss.
     *
     * @return array{int, int}
     */
    private static function colours(Challenge $challenge, int $acceptingUserId): array
    {
        $challengerPlaysWhite = match ($challenge->colour) {
            Challenge::COLOUR_WHITE => true,
            Challenge::COLOUR_BLACK => false,
            default => random_int(0, 1) === 1,
        };

        return $challengerPlaysWhite ? [$challenge->challengerId, $acceptingUserId] : [$acceptingUserId, $challenge->challengerId];
    }
}
