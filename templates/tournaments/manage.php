<?php
/**
 * The organiser's desk (T-01 to T-08, ADM-06, A107 to A109): everything an
 * organiser does, in four easy views.
 *
 * - Players:  start, add players (by name, computer, guest logins, the
 *             invitation link), and per player: away, withdraw, replace, remove.
 * - Rounds:   the current round (start, pair again, close now, pause), and
 *             every board with its tools: decide, more time, start now, replay.
 * - Disputes: what players asked the organiser to look at, with the game.
 * - Settings: the settings, co-organisers, the next League season, cancel.
 *
 * Variables: $tournament (Tournament), $view (string), $data (TournamentView::of),
 * $disputes (list of dispute arrays), $disputeNames (id => name),
 * $organiserNames (id => name), $computerPlayers (list of ComputerPlayer),
 * $timeControls (list of string), $maximumDays (int), $tieBreakKeys (list of string),
 * $inviteUrl (string), $settingsValues (the settings form's values), $calendarRounds
 * (the planned rounds, A114), $query (the query box's values), $queryFound (what it
 * found, or null), $classes (school classes to choose from) for the player query (A116),
 * $basePath, $csrfToken, $t, $template.
 */

use PhpChess\Domain\Tournament\PlayerQuery;
use PhpChess\Domain\Tournament\Tournament;
use PhpChess\Domain\Tournament\TournamentDesk;
use PhpChess\Domain\Tournament\TournamentPlayer;

$tournamentPath = $basePath . '/tournaments/' . $tournament->id;
$tabs = ['players' => 'players', 'rounds' => 'games', 'disputes' => 'flag', 'settings' => 'admin'];
$openDisputes = count(array_filter($disputes, fn (array $dispute): bool => $dispute['state'] === 'open'));
$currentRound = null;
foreach ($data['rounds'] as $round) {
    if ($round['number'] === $tournament->currentRound) {
        $currentRound = $round;
    }
}
$partVariables = ['tournament' => $tournament, 'data' => $data, 'viewerId' => 0, 'basePath' => $basePath, 'csrfToken' => $csrfToken, 't' => $t];
?>
<h1><?= e($tournament->name) ?></h1>
<p class="muted">
    <?= e($t->text('tournament.format.' . $tournament->format)) ?>
    · <?= e($t->text('tournament.status.' . $tournament->status)) ?>
    <?php if ($tournament->isPaused) : ?>· <strong><?= e($t->text('tournament.is_paused')) ?></strong><?php endif; ?>
    · <a href="<?= e($tournamentPath) ?>"><?= e($t->text('tournament.players_page')) ?></a>
</p>

<nav class="tab-bar" aria-label="<?= e($t->text('tournament.views')) ?>">
    <?php foreach ($tabs as $tab => $icon) : ?>
        <a href="<?= e($tournamentPath) ?>/manage?view=<?= e($tab) ?>"<?= $view === $tab ? ' aria-current="page"' : '' ?>>
            <?= $template->render('partials/icon', ['icon' => $icon]) ?><span><?= e($t->text('tournament.manage_view.' . $tab)) ?></span>
            <?php if ($tab === 'disputes' && $openDisputes > 0) : ?><span class="nav-badge"><?= e($openDisputes) ?></span><?php endif; ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php // =============================== Players =============================== ?>
<?php if ($view === 'players') : ?>
    <div class="button-row">
        <?php if ($tournament->isOpenForRegistration()) : ?>
            <form method="post" action="<?= e($tournamentPath) ?>/start" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'play']) ?> <?= e($t->text('tournament.start')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($tournament->format === Tournament::FORMAT_LEAGUE) : ?>
            <a class="button button-large button-secondary" href="<?= e($tournamentPath) ?>/league"><?= $template->render('partials/icon', ['icon' => 'league']) ?> <?= e($t->text('league.editor_title')) ?></a>
        <?php endif; ?>
    </div>

    <?php if (!$tournament->isOver()) : ?>
        <h2><?= e($t->text('tournament.add_players')) ?></h2>
        <?php // The player query (A116): a pop-up with JavaScript, a normal section without. ?>
        <p><a class="button button-large" href="<?= e($tournamentPath) ?>/manage?view=players&amp;find=1#player-query" data-opens-dialog="player-query">
            <?= $template->render('partials/icon', ['icon' => 'search']) ?> <?= e($t->text('tournament.find_players')) ?></a></p>
        <dialog id="player-query" class="query-dialog"<?= $queryFound !== null ? ' open' : '' ?>>
            <h3><?= e($t->text('tournament.find_players')) ?></h3>
            <form method="get" action="<?= e($tournamentPath) ?>/manage#player-query" class="form">
                <input type="hidden" name="view" value="players">
                <input type="hidden" name="find" value="1">
                <div class="form-row">
                    <label><?= e($t->text('query.rating_from')) ?><input type="number" name="rating_from" min="0" max="4000" value="<?= e($query['rating_from']) ?>"></label>
                    <label><?= e($t->text('query.rating_to')) ?><input type="number" name="rating_to" min="0" max="4000" value="<?= e($query['rating_to']) ?>"></label>
                </div>
                <fieldset class="choice-group">
                    <legend><?= e($t->text('query.place')) ?></legend>
                    <?php foreach (PlayerQuery::PLACES as $place) : ?>
                        <label class="choice"><input type="radio" name="place" value="<?= e($place) ?>"<?= $query['place'] === $place ? ' checked' : '' ?>>
                            <?= e($t->text('query.place.' . ($place === '' ? 'all' : $place))) ?></label>
                    <?php endforeach; ?>
                </fieldset>
                <div class="form-row">
                    <label><?= e($t->text('query.games_from')) ?><input type="number" name="games_from" min="0" value="<?= e($query['games_from']) ?>"></label>
                    <label><?= e($t->text('query.games_to')) ?><input type="number" name="games_to" min="0" value="<?= e($query['games_to']) ?>"></label>
                </div>
                <div class="form-row">
                    <label><?= e($t->text('query.joined_after')) ?><input type="date" name="joined_after" value="<?= e($query['joined_after']) ?>"></label>
                    <label><?= e($t->text('query.joined_before')) ?><input type="date" name="joined_before" value="<?= e($query['joined_before']) ?>"></label>
                </div>
                <div class="form-row">
                    <label><?= e($t->text('query.active_days')) ?><input type="number" name="active_days" min="1" max="3650" value="<?= e($query['active_days']) ?>"></label>
                    <?php if ($classes !== []) : ?>
                        <label><?= e($t->text('query.class')) ?>
                            <select name="class_id">
                                <option value=""><?= e($t->text('query.any_class')) ?></option>
                                <?php foreach ($classes as $class) : ?>
                                    <option value="<?= e($class['id']) ?>"<?= $query['class_id'] === (string) $class['id'] ? ' selected' : '' ?>><?= e($class['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                    <?php endif; ?>
                </div>
                <label><?= e($t->text('query.name_contains')) ?><input type="text" name="name_contains" maxlength="40" value="<?= e($query['name_contains']) ?>" autocomplete="off"></label>
                <div class="button-row">
                    <button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'search']) ?> <?= e($t->text('query.find')) ?></button>
                    <a class="button button-quiet" href="<?= e($tournamentPath) ?>/manage?view=players" data-closes-dialog="player-query"><?= e($t->text('query.close')) ?></a>
                </div>
            </form>

            <?php if ($queryFound !== null) : ?>
                <?php $shown = array_slice($queryFound, 0, 50); ?>
                <p><strong><?= e($t->text('query.found', ['count' => count($queryFound)])) ?></strong>
                    <?php if (count($queryFound) > count($shown)) : ?><span class="muted"><?= e($t->text('query.first_shown', ['count' => count($shown)])) ?></span><?php endif; ?></p>
                <?php if ($queryFound !== []) : ?>
                    <form method="post" action="<?= e($tournamentPath) ?>/players/add-found" class="form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <?php foreach ($query as $field => $fieldValue) : ?>
                            <input type="hidden" name="<?= e($field) ?>" value="<?= e($fieldValue) ?>">
                        <?php endforeach; ?>
                        <ul class="pick-list">
                            <?php foreach ($shown as $found) : ?>
                                <li><label class="checkbox"><input type="checkbox" name="pick_<?= e($found['id']) ?>" value="yes">
                                    <?= e($found['username']) ?> <span class="muted"><?= e($found['rating']) ?> · <?= e($t->text('query.games_count', ['count' => $found['games']])) ?></span></label></li>
                            <?php endforeach; ?>
                        </ul>
                        <div class="button-row">
                            <button type="submit" name="which" value="all" class="button button-large"><?= e($t->text('query.add_all', ['count' => count($queryFound)])) ?></button>
                            <button type="submit" name="which" value="ticked" class="button"><?= e($t->text('query.add_ticked')) ?></button>
                        </div>
                    </form>
                <?php endif; ?>
            <?php endif; ?>
        </dialog>
        <script src="<?= e($basePath) ?>/assets/js/tournament/player-query.js" defer></script>
        <form method="post" action="<?= e($tournamentPath) ?>/players/add" class="form form-row">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <label><?= e($t->text('tournament.username')) ?><input type="text" name="username" required autocomplete="off"></label>
            <button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'add']) ?> <?= e($t->text('tournament.add')) ?></button>
        </form>
        <?php if ($computerPlayers !== [] && $tournament->format !== Tournament::FORMAT_LEAGUE) : ?>
            <form method="post" action="<?= e($tournamentPath) ?>/players/computer" class="form form-row">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <label><?= e($t->text('tournament.computer')) ?>
                    <select name="computer_player_id">
                        <?php foreach ($computerPlayers as $computer) : ?>
                            <option value="<?= e($computer->id) ?>"><?= e($computer->name) ?> (<?= e($computer->elo) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'computer']) ?> <?= e($t->text('tournament.add')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($inviteUrl !== '') : ?>
            <p><?= e($t->text('tournament.invitation_link')) ?></p>
            <p class="copyable"><?= e($inviteUrl) ?></p>
            <form method="post" action="<?= e($tournamentPath) ?>/invitation" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button button-secondary"><?= e($t->text('tournament.invitation_renew')) ?></button>
            </form>
        <?php endif; ?>
        <details class="more-settings">
            <summary><?= e($t->text('tournament.guests_title')) ?></summary>
            <p class="hint"><?= e($t->text('tournament.guests_hint')) ?></p>
            <form method="post" action="<?= e($tournamentPath) ?>/guests" class="form form-row">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <label><?= e($t->text('tournament.guest_prefix')) ?><input type="text" name="prefix" maxlength="12" pattern="[A-Za-z0-9]+" required></label>
                <label><?= e($t->text('tournament.guest_count')) ?><input type="number" name="count" min="1" max="40" value="10"></label>
                <button type="submit" class="button button-secondary"><?= e($t->text('tournament.guests_create')) ?></button>
            </form>
        </details>
    <?php endif; ?>

    <h2><?= e($t->text('tournament.player_count', ['count' => count($data['players'])])) ?></h2>
    <ul class="card-list">
        <?php foreach ($data['players'] as $player) : ?>
            <?php $awayRounds = $data['absences'][$player->userId] ?? []; ?>
            <li class="card">
                <p class="card-title">
                    <?= e($player->seed) ?>. <?= e($player->username) ?>
                    <?php if ($player->rating > 0) : ?><span class="muted">(<?= e($player->rating) ?>)</span><?php endif; ?>
                    <?php if ($player->division > 0) : ?><span class="badge"><?= e($t->text('league.division_number', ['division' => $player->division])) ?></span><?php endif; ?>
                    <?php if ($player->isComputer) : ?><span class="badge"><?= e($t->text('tournament.computer')) ?></span><?php endif; ?>
                    <?php if ($player->isGuest) : ?><span class="badge"><?= e($t->text('tournament.guest')) ?></span><?php endif; ?>
                    <?php if ($player->state !== TournamentPlayer::STATE_ACTIVE) : ?><span class="badge"><?= e($t->text('tournament.player_state.' . $player->state)) ?></span><?php endif; ?>
                </p>
                <?php if ($awayRounds !== []) : ?>
                    <p class="muted"><?= e($t->text('tournament.away_rounds', ['rounds' => implode(', ', $awayRounds)])) ?></p>
                <?php endif; ?>
                <div class="card-actions">
                    <?php if ($tournament->isOpenForRegistration()) : ?>
                        <form method="post" action="<?= e($tournamentPath) ?>/players/<?= e($player->userId) ?>/remove" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <button type="submit" class="button button-secondary icon-button" aria-label="<?= e($t->text('tournament.remove')) ?>" title="<?= e($t->text('tournament.remove')) ?>"><?= $template->render('partials/icon', ['icon' => 'remove']) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($player->isActive() && !$tournament->isOver()) : ?>
                        <?php $nextRound = $data['firstOpenRound']; ?>
                        <form method="post" action="<?= e($tournamentPath) ?>/absence" class="inline-form">
                            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                            <input type="hidden" name="user_id" value="<?= e($player->userId) ?>">
                            <input type="hidden" name="round" value="<?= e($nextRound) ?>">
                            <input type="hidden" name="back" value="manage">
                            <input type="hidden" name="away" value="<?= in_array($nextRound, $awayRounds, true) ? 'no' : 'yes' ?>">
                            <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'away']) ?>
                                <?= e($t->text(in_array($nextRound, $awayRounds, true) ? 'tournament.back_for_round' : 'tournament.away_for_round', ['round' => $nextRound])) ?></button>
                        </form>
                    <?php endif; ?>
                    <?php if ($player->isActive() && $tournament->isRunning()) : ?>
                        <details class="inline-details">
                            <summary class="button button-secondary"><?= e($t->text('tournament.withdraw')) ?></summary>
                            <form method="post" action="<?= e($tournamentPath) ?>/players/<?= e($player->userId) ?>/withdraw" class="form">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <p><?= e($t->text('tournament.withdraw_warning')) ?></p>
                                <button type="submit" class="button button-danger"><?= e($t->text('tournament.withdraw_confirm')) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                    <?php if ($tournament->hasFixedTables() && !$tournament->isOver()) : ?>
                        <details class="inline-details">
                            <summary class="button button-secondary"><?= e($t->text('tournament.replace')) ?></summary>
                            <form method="post" action="<?= e($tournamentPath) ?>/players/<?= e($player->userId) ?>/replace" class="form form-row">
                                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                                <label><?= e($t->text('tournament.replace_with')) ?><input type="text" name="username" required autocomplete="off"></label>
                                <button type="submit" class="button"><?= e($t->text('tournament.replace')) ?></button>
                            </form>
                        </details>
                    <?php endif; ?>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>

<?php // =============================== Rounds =============================== ?>
<?php elseif ($view === 'rounds') : ?>
    <div class="button-row">
        <?php if ($currentRound !== null && $currentRound['state'] === 'paired') : ?>
            <form method="post" action="<?= e($tournamentPath) ?>/round/start" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'play']) ?> <?= e($t->text('tournament.round_start', ['round' => $tournament->currentRound])) ?></button>
            </form>
            <form method="post" action="<?= e($tournamentPath) ?>/round/repair" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => 'random']) ?> <?= e($t->text('tournament.round_repair')) ?></button>
            </form>
        <?php endif; ?>
        <?php if ($currentRound !== null && $currentRound['state'] === 'running') : ?>
            <p class="muted">
                <?= e($t->text('tournament.round_running', ['round' => $tournament->currentRound])) ?>
                <?php if ($currentRound['deadlineAt'] !== null) : ?>
                    <?= e($t->text('tournament.deadline', ['date' => gmdate('Y-m-d H:i', $currentRound['deadlineAt'])])) ?>
                <?php endif; ?>
            </p>
            <?php if ($tournament->forfeitRule !== 'organiser') : ?>
                <details class="inline-details">
                    <summary class="button button-secondary"><?= e($t->text('tournament.round_close')) ?></summary>
                    <form method="post" action="<?= e($tournamentPath) ?>/round/close" class="form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <p><?= e($t->text('tournament.round_close_warning')) ?></p>
                        <button type="submit" class="button button-danger"><?= e($t->text('tournament.round_close')) ?></button>
                    </form>
                </details>
            <?php endif; ?>
        <?php endif; ?>
        <?php if ($tournament->isRunning()) : ?>
            <form method="post" action="<?= e($tournamentPath) ?>/pause" class="inline-form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <input type="hidden" name="paused" value="<?= $tournament->isPaused ? 'no' : 'yes' ?>">
                <button type="submit" class="button button-secondary"><?= $template->render('partials/icon', ['icon' => $tournament->isPaused ? 'play' : 'pause']) ?>
                    <?= e($t->text($tournament->isPaused ? 'tournament.resume' : 'tournament.pause')) ?></button>
            </form>
        <?php endif; ?>
    </div>
    <?php for ($round = max(1, $tournament->currentRound); $round >= 1; $round--) : ?>
        <?php if (isset($data['boardsByRound'][$round])) : ?>
            <h2><?= e($t->text('tournament.round_number', ['round' => $round])) ?></h2>
            <?= $template->render('tournaments/boards', $partVariables + ['round' => $round, 'isManager' => true]) ?>
        <?php endif; ?>
    <?php endfor; ?>
    <?php if ($tournament->currentRound === 0) : ?>
        <p class="muted"><?= e($t->text('tournament.not_started')) ?></p>
    <?php endif; ?>

<?php // =============================== Disputes =============================== ?>
<?php elseif ($view === 'disputes') : ?>
    <?php if ($disputes === []) : ?>
        <p class="muted"><?= e($t->text('tournament.no_disputes')) ?></p>
    <?php endif; ?>
    <ul class="card-list">
        <?php foreach ($disputes as $dispute) : ?>
            <?php
            $board = null;
            foreach ($data['boardsByRound'] as $boards) {
                foreach ($boards as $candidate) {
                    if ($candidate->id === $dispute['pairingId']) {
                        $board = $candidate;
                    }
                }
            }
            ?>
            <li class="card<?= $dispute['state'] === 'open' ? ' card-mine' : '' ?>">
                <p class="card-title">
                    <?php if ($board !== null) : ?>
                        <?= e($t->text('tournament.round_number', ['round' => $board->round])) ?>, #<?= e($board->board) ?>:
                        <?= e($data['names'][$board->whiteId] ?? '?') ?> – <?= e($board->blackId === null ? '' : ($data['names'][$board->blackId] ?? '?')) ?>
                        <?php if ($board->counts()) : ?><strong><?= e($board->scoreText()) ?></strong><?php endif; ?>
                    <?php endif; ?>
                </p>
                <p><?= e($t->text('tournament.dispute_by', ['name' => $disputeNames[$dispute['raisedBy']] ?? '?'])) ?></p>
                <p class="keeps-lines"><?= e($dispute['reason']) ?></p>
                <?php if ($board !== null && $board->gameId !== null) : ?>
                    <p><a class="button button-secondary" href="<?= e($basePath) ?>/games/<?= e($board->gameId) ?>"><?= $template->render('partials/icon', ['icon' => 'tv']) ?> <?= e($t->text('tournament.open_game')) ?></a></p>
                <?php endif; ?>
                <?php if ($dispute['state'] === 'open') : ?>
                    <form method="post" action="<?= e($tournamentPath) ?>/disputes/<?= e($dispute['id']) ?>/answer" class="form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <fieldset class="choice-group">
                            <legend><?= e($t->text('tournament.dispute_answer')) ?></legend>
                            <?php foreach ([TournamentDesk::DISPUTE_KEEP, TournamentDesk::DISPUTE_CORRECT, TournamentDesk::DISPUTE_REPLAY] as $answer) : ?>
                                <label class="choice"><input type="radio" name="answer" value="<?= e($answer) ?>"<?= $answer === TournamentDesk::DISPUTE_KEEP ? ' checked' : '' ?>><?= e($t->text('tournament.dispute_answer.' . $answer)) ?></label>
                            <?php endforeach; ?>
                        </fieldset>
                        <label><?= e($t->text('tournament.dispute_new_result')) ?>
                            <select name="decision">
                                <?php foreach (TournamentDesk::DECISIONS as $decision) : ?>
                                    <option value="<?= e($decision) ?>"><?= e($t->text('tournament.decision.' . $decision)) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </label>
                        <label><?= e($t->text('tournament.dispute_note')) ?><textarea name="note" rows="2" maxlength="2000"></textarea></label>
                        <button type="submit" class="button"><?= e($t->text('tournament.dispute_answer_send')) ?></button>
                    </form>
                <?php else : ?>
                    <p class="muted"><?= e($t->text('tournament.dispute_answer.' . $dispute['decision'])) ?> <?= e($dispute['decisionNote']) ?></p>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>

<?php // =============================== Settings =============================== ?>
<?php else : ?>
    <form method="post" action="<?= e($tournamentPath) ?>/settings" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <?php if (!$tournament->isOpenForRegistration()) : ?>
            <p class="hint"><?= e($t->text('tournament.settings_locked')) ?></p>
        <?php endif; ?>
        <?= $template->render('tournaments/settings_fields', [
            'values' => $settingsValues,
            'calendarRounds' => $calendarRounds,
            'timeControls' => $timeControls,
            'maximumDays' => $maximumDays,
            'lockFormat' => !$tournament->isOpenForRegistration(),
            't' => $t,
        ]) ?>
        <button type="submit" class="button"><?= e($t->text('tournament.save')) ?></button>
    </form>

    <h2><?= e($t->text('tournament.organisers')) ?></h2>
    <ul class="text-list">
        <li><?= e($organiserNames[$tournament->createdBy] ?? '?') ?> <span class="muted">(<?= e($t->text('tournament.creator')) ?>)</span></li>
        <?php foreach ($data['organiserIds'] as $organiserId) : ?>
            <li>
                <?= e($organiserNames[$organiserId] ?? '?') ?>
                <form method="post" action="<?= e($tournamentPath) ?>/organisers/<?= e($organiserId) ?>/remove" class="inline-form">
                    <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                    <button type="submit" class="button button-secondary icon-button" aria-label="<?= e($t->text('tournament.remove')) ?>" title="<?= e($t->text('tournament.remove')) ?>"><?= $template->render('partials/icon', ['icon' => 'remove']) ?></button>
                </form>
            </li>
        <?php endforeach; ?>
    </ul>
    <form method="post" action="<?= e($tournamentPath) ?>/organisers/add" class="form form-row">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <label><?= e($t->text('tournament.username')) ?><input type="text" name="username" required autocomplete="off"></label>
        <button type="submit" class="button button-secondary"><?= e($t->text('tournament.organiser_add')) ?></button>
    </form>

    <?php if ($tournament->format === Tournament::FORMAT_LEAGUE && $tournament->status === Tournament::STATUS_FINISHED) : ?>
        <h2><?= e($t->text('league.next_season')) ?></h2>
        <form method="post" action="<?= e($tournamentPath) ?>/next-season" class="inline-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <button type="submit" class="button button-large"><?= $template->render('partials/icon', ['icon' => 'league']) ?> <?= e($t->text('league.next_season_create')) ?></button>
        </form>
    <?php endif; ?>

    <?php if (!$tournament->isOver()) : ?>
        <h2><?= e($t->text('tournament.cancel')) ?></h2>
        <details class="inline-details">
            <summary class="button button-danger"><?= e($t->text('tournament.cancel')) ?></summary>
            <form method="post" action="<?= e($tournamentPath) ?>/cancel" class="form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <p><?= e($t->text('tournament.cancel_warning')) ?></p>
                <button type="submit" class="button button-danger"><?= e($t->text('tournament.cancel_confirm')) ?></button>
            </form>
        </details>
    <?php endif; ?>
<?php endif; ?>
