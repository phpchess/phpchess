<?php

declare(strict_types=1);

namespace PhpChess\Domain\Lichess;

/**
 * The sections and topics a study theme is picked from (A95).
 *
 * They follow Lichess, so players who know lichess.org/learn and
 * lichess.org/practice find the same order here:
 *   - Learn (beginners): chess pieces, fundamentals, intermediate, advanced;
 *   - Practice: checkmates, tactics, pawn and rook endgames.
 * Two sections take a name the admin types, because there are too many to
 * list: Openings ("London System") and Famous games.
 *
 * Names on screen come from the language file: "study_section.<section>"
 * and "study_topic.<section>.<topic>".
 */
final class StudyTopics
{
    /** Section => its topics, in Lichess's order. */
    public const array SECTIONS = [
        // lichess.org/learn
        'chess_pieces' => ['rook', 'bishop', 'queen', 'king', 'knight', 'pawn'],
        'fundamentals' => ['capture', 'protection', 'combat', 'check_in_one', 'out_of_check', 'mate_in_one'],
        'intermediate' => ['board_setup', 'castling', 'en_passant', 'stalemate'],
        'advanced' => ['piece_value', 'check_in_two'],
        // lichess.org/practice
        'checkmates' => ['piece_checkmates_1', 'checkmate_patterns_1', 'checkmate_patterns_2', 'checkmate_patterns_3', 'checkmate_patterns_4', 'piece_checkmates_2', 'knight_bishop_mate'],
        'fundamental_tactics' => ['pin', 'skewer', 'fork', 'discovered_attacks', 'double_check', 'overloaded_pieces', 'zwischenzug', 'x_ray'],
        'advanced_tactics' => ['zugzwang', 'interference', 'greek_gift', 'deflection', 'attraction', 'underpromotion', 'desperado', 'counter_check', 'undermining', 'clearance'],
        'pawn_endgames' => ['key_squares', 'opposition', 'seventh_rank_rook_pawn'],
        'rook_endgames' => ['seventh_rank_rook_pawn', 'basic', 'intermediate', 'practical'],
        // the admin names these
        'openings' => [],
        'famous_games' => [],
    ];

    /** The sections where the admin types the theme's name. */
    public const array OWN_NAME_SECTIONS = ['openings', 'famous_games'];

    /** True when the section is known and the topic belongs to it ('' for a named section). */
    public static function isValid(string $section, string $topic): bool
    {
        if (!array_key_exists($section, self::SECTIONS)) {
            return false;
        }
        if (in_array($section, self::OWN_NAME_SECTIONS, true)) {
            return $topic === '';
        }

        return in_array($topic, self::SECTIONS[$section], true);
    }

    /** The language key of a topic's name. */
    public static function topicKey(string $section, string $topic): string
    {
        return 'study_topic.' . $section . '.' . $topic;
    }
}
