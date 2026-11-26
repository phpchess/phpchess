<?php
/**
 * One challenge as a tile (F2, decision A84: icons over text).
 *
 * From top to bottom:
 *   - a micro board of the start position (MicroBoard.php), the viewer's colour at the bottom;
 *   - the other player's name: "name vs" when they play White, "vs name" when
 *     they play Black, just the name when the colour is decided at random;
 *   - icons: the pace (a clock with "5+3", or an envelope with the days per
 *     move), a star when rated, and the colour the viewer gets;
 *   - icon buttons: accept, decline or take back.
 * Every icon keeps its words for screen readers (aria-label) and as a hint
 * when pointing at it (title).
 *
 * Variables: $challenge (Challenge), $viewerId (int), $usernames (array of id => name),
 * $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Chess\Position;
use PhpChess\Domain\Play\Challenge;
use PhpChess\Http\MicroBoard;

// ===== Who the other player is, and which colour each side gets =====
$isMine = $challenge->challengerId === $viewerId;
if ($isMine) {
    $otherName = $challenge->opponentId !== null ? ($usernames[$challenge->opponentId] ?? '?') : '?';
    $otherColour = match ($challenge->colour) {
        Challenge::COLOUR_WHITE => Challenge::COLOUR_BLACK,
        Challenge::COLOUR_BLACK => Challenge::COLOUR_WHITE,
        default => Challenge::COLOUR_RANDOM,
    };
} else {
    $otherName = $usernames[$challenge->challengerId] ?? '?';
    $otherColour = $challenge->colour;
}
$myColour = match ($otherColour) {
    Challenge::COLOUR_WHITE => Challenge::COLOUR_BLACK,
    Challenge::COLOUR_BLACK => Challenge::COLOUR_WHITE,
    default => Challenge::COLOUR_RANDOM,
};
$nameLine = match ($otherColour) {
    Challenge::COLOUR_WHITE => $t->text('tile.name_vs', ['name' => $otherName]),
    Challenge::COLOUR_BLACK => $t->text('tile.vs_name', ['name' => $otherName]),
    default => $otherName,
};

// ===== The position: the challenge's own start position, or the normal one =====
try {
    $position = $challenge->startFen !== null ? Position::fromFen($challenge->startFen) : Position::start();
} catch (Throwable) {
    $position = Position::start();
}
$boardHtml = MicroBoard::svg($position, $myColour === Challenge::COLOUR_BLACK, $t->text('game.board'));

// ===== The icons =====
$isLive = $challenge->timeControl !== null;
$paceValue = $isLive ? (string) $challenge->timeControl : (string) $challenge->daysPerMove;
$paceWords = $isLive
    ? $t->text('play.pace_timed', ['control' => $challenge->timeControl])
    : $t->text('challenge.summary', ['days' => $challenge->daysPerMove]);
$colourWords = $t->text('tile.you_play.' . $myColour);
?>
<li class="tile challenge-tile<?= $isMine ? ' tile-mine' : '' ?>">
    <?= $boardHtml ?>
    <span class="tile-name"><?= e($nameLine) ?></span>
    <span class="tile-icons">
        <span class="tile-icon" title="<?= e($paceWords) ?>" aria-label="<?= e($paceWords) ?>" role="img">
            <?= $template->render('partials/icon', ['icon' => $isLive ? 'live' : 'correspondence', 'size' => 18]) ?><span aria-hidden="true"><?= e($paceValue) ?></span>
        </span>
        <?php if ($challenge->isRated) : ?>
            <span class="tile-icon" title="<?= e($t->text('play.rated')) ?>" aria-label="<?= e($t->text('play.rated')) ?>" role="img"><?= $template->render('partials/icon', ['icon' => 'rated', 'size' => 18]) ?></span>
        <?php endif; ?>
        <span class="tile-icon" title="<?= e($colourWords) ?>" aria-label="<?= e($colourWords) ?>" role="img"><?= $template->render('partials/icon', ['icon' => $myColour, 'size' => 18]) ?></span>
        <?php if ($challenge->startFen !== null) : ?>
            <span class="tile-icon" title="<?= e($t->text('challenge.custom_position')) ?>" aria-label="<?= e($t->text('challenge.custom_position')) ?>" role="img"><?= $template->render('partials/icon', ['icon' => 'games', 'size' => 18]) ?></span>
        <?php endif; ?>
    </span>

    <!-- ===== Icon buttons ===== -->
    <span class="tile-actions">
        <?php if ($isMine) : ?>
            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/cancel">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button-secondary icon-button" title="<?= e($t->text('challenge.cancel')) ?>" aria-label="<?= e($t->text('challenge.cancel')) ?>"><?= $template->render('partials/icon', ['icon' => 'close']) ?></button>
            </form>
        <?php else : ?>
            <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/accept">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="icon-button" title="<?= e($t->text('challenge.accept')) ?>" aria-label="<?= e($t->text('challenge.accept')) ?>"><?= $template->render('partials/icon', ['icon' => 'check']) ?></button>
            </form>
            <?php if ($challenge->kind === Challenge::KIND_DIRECT) : ?>
                <form method="post" action="<?= e($basePath) ?>/challenges/<?= e($challenge->id) ?>/decline">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button-secondary icon-button" title="<?= e($t->text('challenge.decline')) ?>" aria-label="<?= e($t->text('challenge.decline')) ?>"><?= $template->render('partials/icon', ['icon' => 'close']) ?></button>
                </form>
            <?php endif; ?>
        <?php endif; ?>
    </span>
</li>
