<?php
/**
 * The rules of this site (A46), written from the admin's settings, so
 * players always read the rules that apply now. The admin reaches the same
 * page from the play settings, to check what players will read.
 *
 * Variables: $timeControls (list of "5+3" texts), $maxLiveGames,
 * $maxLiveGamesPerPlayer, $maximumDays, $vacationDays, $openDays,
 * $directDays, $inviteDays, $deliveryWaitMs, $lagAllowanceMs,
 * $onlineSeconds (int), $abortRule (string), $basePath, $t.
 */

?>
<h1><?= e($t->text('rules.title')) ?></h1>
<p class="hint"><?= e($t->text('rules.intro')) ?></p>

<!-- ===== One game, any pace ===== -->
<h2><?= e($t->text('rules.pace_title')) ?></h2>
<ul>
    <li><?= e($t->text('rules.correspondence', ['max' => $maximumDays])) ?></li>
    <li><?= e($t->text('rules.timed', ['controls' => implode(', ', $timeControls)])) ?></li>
    <li><?= e($t->text('rules.change_pace', ['seconds' => $onlineSeconds])) ?></li>
</ul>

<!-- ===== The clock in timed games ===== -->
<h2><?= e($t->text('rules.clock_title')) ?></h2>
<ul>
    <li><?= e($t->text('rules.clock_start')) ?></li>
    <li><?= e($t->text('rules.clock_fair', ['wait' => $deliveryWaitMs / 1000, 'lag' => $lagAllowanceMs])) ?></li>
    <li><?= e($t->text('rules.flag')) ?></li>
    <li><?= e($t->text('rules.disconnect')) ?></li>
    <li><?= e($t->text('rules.premove')) ?></li>
    <li><?= e($t->text('rules.live_limits', ['site' => $maxLiveGames, 'player' => $maxLiveGamesPerPlayer])) ?></li>
</ul>

<!-- ===== Correspondence ===== -->
<h2><?= e($t->text('rules.correspondence_title')) ?></h2>
<ul>
    <li><?= e($t->text('rules.deadline')) ?></li>
    <li><?= e($t->text('rules.vacation', ['days' => $vacationDays])) ?></li>
</ul>

<!-- ===== Starting and ending ===== -->
<h2><?= e($t->text('rules.games_title')) ?></h2>
<ul>
    <li><?= e($t->text('rules.challenges', ['open' => $openDays, 'direct' => $directDays, 'invite' => $inviteDays])) ?></li>
    <li><?= e($t->text('rules.lobby', ['seconds' => $onlineSeconds])) ?></li>
    <li><?= e($t->text('rules.abort.' . $abortRule)) ?></li>
    <li><?= e($t->text('rules.automatic_draws')) ?></li>
</ul>

<p class="button-row"><a class="button button-secondary" href="<?= e($basePath) ?>/play"><?= e($t->text('play.title')) ?></a></p>
