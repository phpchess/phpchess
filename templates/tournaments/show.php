<?php
/**
 * One tournament (T-01, T-06 to T-08, A102, A106 to A108): the page for
 * players and watchers.
 *
 * From the top:
 * 1. name, facts and the forfeit rule in plain words (A102: visible rule);
 * 2. my buttons: join / leave, my game, "I'll be away", withdraw, manage;
 * 3. tabs (easy views, A107): Overview, Standings, Rounds, Table, Calendar, Log.
 *
 * The calendar (A114) is also shown on the overview while players can
 * join, so they know the dates before joining; check-in (A117) is a big
 * button while its window is open.
 *
 * Variables: $tournament (Tournament), $view (string), $data (from
 * TournamentView::of), $viewerId (int), $isManager (bool), $inviteToken
 * (string, on the invitation page), $calendarRounds (A114), $checkIn (array{round: int,
 * closesAt: int, isCheckedIn: bool}|null, A117), $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentPlayer;
use PhpChess\Http\Web\Tournament\TournamentView;

$tournamentPath = $basePath . '/tournaments/' . $tournament->id;
$isPlayer = $data['isPlayer'];
$isActivePlayer = $data['myState'] === TournamentPlayer::STATE_ACTIVE;
$tabs = ['overview' => 'trophy', 'standings' => 'stats', 'rounds' => 'games', 'table' => 'circle', 'calendar' => 'calendar', 'log' => 'news'];
$partVariables = ['tournament' => $tournament, 'data' => $data, 'viewerId' => $viewerId, 'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t];
?>
<?php // ===== 1. Name and facts ===== ?>
<h1><?= e($tournament->name) ?></h1>
<p class="muted">
    <?= e($t->text('tournament.format.' . $tournament->format)) ?>
    · <?= e($tournament->isTimed() ? $tournament->timeControl : $t->text('new_game.days', ['days' => $tournament->daysPerMove])) ?>
    · <?= e($t->text('tournament.status.' . $tournament->status)) ?>
    <?php if ($tournament->isPaused) : ?>· <strong><?= e($t->text('tournament.is_paused')) ?></strong><?php endif; ?>
    <?php if ($tournament->currentRound > 0) : ?>· <?= e($t->text('tournament.round_number', ['round' => $tournament->currentRound])) ?><?php endif; ?>
    · <?= e($t->text('tournament.player_count', ['count' => count($data['players'])])) ?>
    · <?= e($tournament->isRated ? $t->text('play.rated') : $t->text('tournament.unrated')) ?>
</p>
<?php if ($tournament->description !== '') : ?>
    <p class="keeps-lines"><?= e($tournament->description) ?></p>
<?php endif; ?>
<?php if (!$tournament->isTimed()) : ?>
    <p class="notice-warning" role="note">
        <?= $template->render('partials/icon', ['icon' => 'rules']) ?>
        <?= e($t->text('tournament.rule_box', ['days' => $tournament->roundDays, 'rule' => $t->text(TournamentView::forfeitRuleKey($tournament->forfeitRule))])) ?>
    </p>
<?php endif; ?>

<?php // ===== 2. My buttons ===== ?>
<?php if ($checkIn !== null && $isActivePlayer) : ?>
    <?php if ($checkIn['isCheckedIn']) : ?>
        <p class="notice-success"><?= $template->render('partials/icon', ['icon' => 'check_in']) ?> <?= e($t->text('tournament.checked_in', ['round' => $checkIn['round']])) ?></p>
    <?php else : ?>
        <form method="post" action="<?= e($tournamentPath) ?>/check-in" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="round" value="<?= e($checkIn['round']) ?>">
            <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'check_in']) ?>
                <?= e($t->text('tournament.check_in_button', ['round' => $checkIn['round'], 'time' => date('H:i', $checkIn['closesAt'])])) ?></button>
        </form>
    <?php endif; ?>
<?php endif; ?>
<div class="button-row">
    <?php foreach ($data['myBoards'] as $board) : ?>
        <?php if ($board->gameId !== null) : ?>
            <a class="button button-large" href="<?= e($basePath) ?>/games/<?= e($board->gameId) ?>">
                <?= $template->render('partials/icon', ['icon' => 'play']) ?> <?= e($t->text('tournament.my_game', ['round' => $board->round])) ?></a>
        <?php endif; ?>
    <?php endforeach; ?>

    <?php if (!$isPlayer && ($tournament->isOpenForRegistration() || ($tournament->isRunning() && $tournament->format === Tournament::FORMAT_SWISS)) && $tournament->registration !== Tournament::REGISTRATION_ORGANISER) : ?>
        <?php if ($tournament->registration === Tournament::REGISTRATION_OPEN || $inviteToken !== '') : ?>
            <form method="post" action="<?= e($tournamentPath) ?>/join" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="token" value="<?= e($inviteToken) ?>">
                <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'add']) ?> <?= e($t->text('tournament.join')) ?></button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isPlayer && $tournament->isOpenForRegistration()) : ?>
        <form method="post" action="<?= e($tournamentPath) ?>/leave" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="button button-secondary"><?= e($t->text('tournament.leave')) ?></button>
        </form>
    <?php endif; ?>

    <?php if ($isActivePlayer && !$tournament->isOver()) : ?>
        <?php $awayRounds = $data['absences'][$viewerId] ?? []; ?>
        <?php $nextRound = $data['firstOpenRound']; ?>
        <form method="post" action="<?= e($tournamentPath) ?>/absence" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <input type="hidden" name="round" value="<?= e($nextRound) ?>">
            <input type="hidden" name="away" value="<?= in_array($nextRound, $awayRounds, true) ? 'no' : 'yes' ?>">
            <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'away']) ?>
                <?= e($t->text(in_array($nextRound, $awayRounds, true) ? 'tournament.back_for_round' : 'tournament.away_for_round', ['round' => $nextRound])) ?></button>
        </form>
        <?php if ($tournament->isRunning()) : ?>
            <details class="inline-details">
                <summary class="button button-secondary"><?= e($t->text('tournament.withdraw')) ?></summary>
                <form method="post" action="<?= e($tournamentPath) ?>/withdraw" class="form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <p><?= e($t->text('tournament.withdraw_warning')) ?></p>
                    <button type="submit" class="button button-danger"><?= e($t->text('tournament.withdraw_confirm')) ?></button>
                </form>
            </details>
        <?php endif; ?>
    <?php endif; ?>

    <?php if ($isManager) : ?>
        <a class="button button-secondary" href="<?= e($tournamentPath) ?>/manage"><?= $template->render('partials/icon', ['icon' => 'admin']) ?> <?= e($t->text('tournament.manage')) ?></a>
    <?php endif; ?>
    <a class="button button-secondary icon-button" href="<?= e($tournamentPath) ?>/pgn" aria-label="<?= e($t->text('tournament.pgn')) ?>" title="<?= e($t->text('tournament.pgn')) ?>">
        <?= $template->render('partials/icon', ['icon' => 'download']) ?></a>
</div>

<?php // ===== 3. Tabs ===== ?>
<nav class="tab-bar" aria-label="<?= e($t->text('tournament.views')) ?>">
    <?php foreach ($tabs as $tab => $icon) : ?>
        <a href="<?= e($tournamentPath) ?>?view=<?= e($tab) ?>"<?= $view === $tab ? ' aria-current="page"' : '' ?>>
            <?= $template->render('partials/icon', ['icon' => $icon]) ?><span><?= e($t->text('tournament.view.' . $tab)) ?></span></a>
    <?php endforeach; ?>
</nav>

<?php if ($view === 'overview') : ?>
    <?php if ($tournament->isOpenForRegistration() && $calendarRounds !== []) : ?>
        <h2><?= e($t->text('tournament.calendar')) ?></h2>
        <?= $template->render('tournaments/calendar', ['rounds' => $calendarRounds, 't' => $t]) ?>
    <?php endif; ?>
    <?php if ($tournament->currentRound > 0) : ?>
        <h2><?= e($t->text('tournament.round_number', ['round' => $tournament->currentRound])) ?></h2>
        <?= $template->render('tournaments/boards', $partVariables + ['round' => $tournament->currentRound, 'isManager' => false]) ?>
    <?php endif; ?>
    <h2><?= e($t->text('tournament.view.standings')) ?></h2>
    <?= $template->render('tournaments/standings', $partVariables) ?>
<?php elseif ($view === 'standings') : ?>
    <?= $template->render('tournaments/standings', $partVariables) ?>
<?php elseif ($view === 'rounds') : ?>
    <?php for ($round = max(1, $tournament->currentRound); $round >= 1; $round--) : ?>
        <?php if (isset($data['boardsByRound'][$round])) : ?>
            <h2><?= e($t->text('tournament.round_number', ['round' => $round])) ?></h2>
            <?= $template->render('tournaments/boards', $partVariables + ['round' => $round, 'isManager' => false]) ?>
        <?php endif; ?>
    <?php endfor; ?>
<?php elseif ($view === 'table') : ?>
    <?= $template->render('tournaments/table', $partVariables) ?>
<?php elseif ($view === 'calendar') : ?>
    <?php if ($calendarRounds === []) : ?>
        <p class="muted"><?= e($t->text($tournament->isTimed() ? 'tournament.calendar_timed' : 'tournament.calendar_none')) ?></p>
    <?php else : ?>
        <?= $template->render('tournaments/calendar', ['rounds' => $calendarRounds, 'icsUrl' => $tournamentPath . '/calendar.ics', 't' => $t]) ?>
    <?php endif; ?>
<?php else : ?>
    <?= $template->render('tournaments/log', $partVariables) ?>
<?php endif; ?>
