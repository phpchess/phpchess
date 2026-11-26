<?php
/**
 * The admin's Lichess and study page (C5-1: A74, A78, A80, A81, A87).
 *
 * Steps on the page, in order:
 *   1. the connection test: "external content" can only be switched on
 *      after it passed (A80);
 *   2. study themes: make one, see how many games each has, remove one;
 *   3. bring games in: upload a PGN file (works offline), or, while
 *      "external content" is on, one Lichess game or a Lichess player's
 *      newest games. Every game goes through the content scanner (A81);
 *      rejected games are listed in the audit log.
 *   The link to the Lichess database helps find games to stage (A87).
 *   4. players' own games: limits and the paste switch (B37).
 *
 * Variables: $themes (list of array{id: int, title: string, section: string, topic: string, games: int}),
 * $sections (array<string, list<string>>, StudyTopics::SECTIONS), $ownNameSections (list of string),
 * $lichessIsOn (bool), $testPassedAt (int, 0 = not passed), $testResult (string),
 * $proxy (ProxySettings), $content (LichessContent), $contentKinds (list of string),
 * $maxGames (int), $maxBytes (int), $maxOwnGames (int), $importsPerHour (int), $pasteIsOpen (bool), $ownGameDays (int),
 * $lichessDatabaseLink (string), $warnings, $currentAdminPath,
 * $basePath, $csrfToken, $t, $template.
 */
?>
<?= $template->render('admin/header', ['warnings' => $warnings, 'currentAdminPath' => $currentAdminPath, 'basePath' => $basePath, 't' => $t, 'csrfToken' => $csrfToken]) ?>

<h1><?= e($t->text('admin.lichess.title')) ?></h1>
<p><?= e($t->text('admin.lichess.explain')) ?></p>

<?php // ===== 0. The Internet connection (A94) ===== ?>
<section aria-labelledby="proxy-title">
    <h2 id="proxy-title"><?= e($t->text('admin.lichess.proxy_title')) ?></h2>
    <p class="hint"><?= e($t->text('admin.lichess.proxy_explain')) ?></p>
    <form method="post" action="<?= e($basePath) ?>/admin/lichess/proxy" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <fieldset class="choice-group">
            <label class="choice"><input type="radio" name="proxy_mode" value="direct"<?= $proxy->usesProxy() ? '' : ' checked' ?>> <span><?= e($t->text('admin.lichess.proxy_direct')) ?></span></label>
            <label class="choice"><input type="radio" name="proxy_mode" value="proxy"<?= $proxy->usesProxy() ? ' checked' : '' ?>> <span><?= e($t->text('admin.lichess.proxy_through')) ?></span></label>
        </fieldset>
        <label><?= e($t->text('admin.lichess.proxy_host')) ?> <input type="text" name="proxy_host" maxlength="253" value="<?= e($proxy->host()) ?>" placeholder="proxy.school.local" autocapitalize="off" spellcheck="false"></label>
        <label><?= e($t->text('admin.lichess.proxy_port')) ?> <input type="number" name="proxy_port" min="1" max="65535" value="<?= e($proxy->port()) ?>"></label>
        <label><?= e($t->text('admin.lichess.proxy_user')) ?> <input type="text" name="proxy_user" maxlength="200" autocomplete="off" autocapitalize="off" spellcheck="false"></label>
        <label><?= e($t->text('admin.lichess.proxy_password')) ?> <input type="password" name="proxy_password" maxlength="200" autocomplete="new-password"></label>
        <?php if ($proxy->hasLogin()) : ?>
            <p class="hint"><?= e($t->text('admin.lichess.proxy_login_saved')) ?></p>
            <label class="checkbox"><input type="checkbox" name="proxy_clear_login" value="on"> <?= e($t->text('admin.lichess.proxy_clear_login')) ?></label>
        <?php endif; ?>
        <p class="button-row"><button type="submit" class="button"><?= e($t->text('admin.lichess.save')) ?></button></p>
    </form>
</section>

<?php // ===== 1. The connection test ===== ?>
<section aria-labelledby="test-title">
    <h2 id="test-title">1. <?= e($t->text('admin.lichess.test_title')) ?></h2>
    <p>
        <?php if ($testPassedAt > 0) : ?>
            <?= e($t->text('admin.lichess.test_last_passed', ['date' => gmdate('Y-m-d H:i', $testPassedAt)])) ?>
        <?php elseif ($testResult !== '') : ?>
            <?= e($t->text('admin.lichess.test_last_failed', ['result' => $testResult])) ?>
        <?php else : ?>
            <?= e($t->text('admin.lichess.test_never')) ?>
        <?php endif; ?>
    </p>
    <?php if ($proxy->usesProxy()) : ?>
        <p class="muted"><?= e($t->text('admin.lichess.via_proxy', ['proxy' => $proxy->description()])) ?></p>
    <?php endif; ?>
    <form method="post" action="<?= e($basePath) ?>/admin/lichess/test" class="inline-form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <button type="submit" class="button button-secondary"><?= e($t->text('admin.lichess.test_now')) ?></button>
    </form>

    <?php // The switch and what it brings, in one place (A96); the switches page shows the same switch. ?>
    <h3><?= e($t->text('admin.lichess.content_title')) ?></h3>
    <form method="post" action="<?= e($basePath) ?>/admin/lichess/content" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <label class="checkbox"><input type="checkbox" name="external_content" value="on"<?= $lichessIsOn ? ' checked' : '' ?><?= $testPassedAt > 0 || $lichessIsOn ? '' : ' disabled' ?>> <strong><?= e($t->text('admin.lichess.external_content')) ?></strong></label>
        <?php if ($testPassedAt === 0 && !$lichessIsOn) : ?>
            <p class="hint"><?= e($t->text('admin.switches.needs.lichess_test')) ?></p>
        <?php endif; ?>
        <?php foreach ($contentKinds as $kind) : ?>
            <label class="checkbox"><input type="checkbox" name="show_<?= e($kind) ?>" value="on"<?= $content->isChosen($kind) ? ' checked' : '' ?>> <?= e($t->text('admin.lichess.kind.' . $kind)) ?></label>
        <?php endforeach; ?>
        <p class="button-row"><button type="submit" class="button"><?= e($t->text('admin.lichess.save')) ?></button></p>
    </form>
</section>

<?php // ===== 2. Study themes ===== ?>
<section aria-labelledby="themes-title">
    <h2 id="themes-title">2. <?= e($t->text('study.themes')) ?></h2>
    <?php if ($themes !== []) : ?>
        <ul class="text-list">
            <?php foreach ($themes as $theme) : ?>
                <li>
                    <a href="<?= e($basePath) ?>/learn/themes/<?= e($theme['id']) ?>"><?= e($theme['title']) ?></a>
                    <span class="muted">(<?= e($t->text('study.game_count', ['count' => $theme['games']])) ?>)</span>
                    <form method="post" action="<?= e($basePath) ?>/admin/lichess/themes/<?= e($theme['id']) ?>/remove" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                        <button type="submit" class="icon-button" aria-label="<?= e($t->text('admin.lichess.remove_theme')) ?>" title="<?= e($t->text('admin.lichess.remove_theme')) ?>">
                            <?= $template->render('partials/icon', ['icon' => 'remove']) ?>
                        </button>
                    </form>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
    <form method="post" action="<?= e($basePath) ?>/admin/lichess/themes" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <?php // The Lichess sections and topics (A95); Openings and Famous games take a name. ?>
        <label>
            <?= e($t->text('admin.lichess.theme_topic')) ?>
            <select name="topic" required>
                <?php foreach ($sections as $section => $topics) : ?>
                    <optgroup label="<?= e($t->text('study_section.' . $section)) ?>">
                        <?php if (in_array($section, $ownNameSections, true)) : ?>
                            <option value="<?= e($section) ?>:"><?= e($t->text('study_section.' . $section)) ?>: <?= e($t->text('admin.lichess.own_name')) ?></option>
                        <?php endif; ?>
                        <?php foreach ($topics as $topic) : ?>
                            <option value="<?= e($section . ':' . $topic) ?>"><?= e($t->text('study_topic.' . $section . '.' . $topic)) ?></option>
                        <?php endforeach; ?>
                    </optgroup>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <?= e($t->text('admin.lichess.theme_title')) ?>
            <input type="text" name="title" maxlength="100">
            <span class="hint"><?= e($t->text('admin.lichess.theme_hint')) ?></span>
        </label>
        <p class="button-row"><button type="submit" class="button"><?= e($t->text('admin.lichess.add_theme')) ?></button></p>
    </form>
</section>

<?php // ===== 3. Bring games in ===== ?>
<?php if ($themes !== []) : ?>
    <?php
    // The same theme choice in every form below, as HTML made here (every value escaped).
    $themeOptions = '';
    foreach ($themes as $theme) {
        $themeOptions .= '<option value="' . e($theme['id']) . '">' . e($theme['title']) . '</option>';
    }
    $themeChoiceHtml = '<label>' . e($t->text('admin.lichess.into_theme')) . ' <select name="theme_id" required>' . $themeOptions . '</select></label>';
    ?>
    <section aria-labelledby="import-title">
        <h2 id="import-title">3. <?= e($t->text('admin.lichess.import_title')) ?></h2>
        <p class="hint"><?= e($t->text('admin.lichess.scanner_explain', ['max' => $maxGames])) ?></p>

        <h3><?= e($t->text('admin.lichess.upload_title')) ?></h3>
        <form method="post" action="<?= e($basePath) ?>/admin/lichess/upload" enctype="multipart/form-data" class="form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <?= $themeChoiceHtml ?>
            <label><?= e($t->text('admin.lichess.pgn_file')) ?> <input type="file" name="pgn_file" accept=".pgn,text/plain" required></label>
            <p class="button-row"><button type="submit" class="button"><?= $template->render('partials/icon', ['icon' => 'upload', 'size' => 20]) ?> <?= e($t->text('admin.lichess.upload')) ?></button></p>
        </form>

        <h3><?= e($t->text('admin.lichess.paste_title')) ?></h3>
        <form method="post" action="<?= e($basePath) ?>/admin/lichess/paste" class="form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <?= $themeChoiceHtml ?>
            <label><?= e($t->text('study.paste_label')) ?> <textarea name="pgn" rows="8" required maxlength="<?= e($maxBytes) ?>" spellcheck="false" autocapitalize="off"></textarea></label>
            <p class="button-row"><button type="submit" class="button"><?= e($t->text('study.paste')) ?></button></p>
        </form>

        <?php if ($lichessIsOn) : ?>
            <p>
                <a href="<?= e($lichessDatabaseLink) ?>" target="_blank" rel="noopener noreferrer"><?= $template->render('partials/icon', ['icon' => 'link', 'size' => 20]) ?> <?= e($t->text('admin.lichess.database_link')) ?></a>
            </p>

            <h3><?= e($t->text('admin.lichess.game_title')) ?></h3>
            <form method="post" action="<?= e($basePath) ?>/admin/lichess/import-game" class="form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <?= $themeChoiceHtml ?>
                <label><?= e($t->text('study.import_link')) ?> <input type="url" name="link" required maxlength="80" inputmode="url"></label>
                <p class="button-row"><button type="submit" class="button"><?= e($t->text('study.import')) ?></button></p>
            </form>

            <h3><?= e($t->text('admin.lichess.study_title')) ?></h3>
            <form method="post" action="<?= e($basePath) ?>/admin/lichess/import-study" class="form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <?= $themeChoiceHtml ?>
                <label><?= e($t->text('admin.lichess.study_link')) ?> <input type="url" name="link" required maxlength="80" inputmode="url" placeholder="https://lichess.org/study/abcdEFGH"></label>
                <p class="button-row"><button type="submit" class="button"><?= e($t->text('study.import')) ?></button></p>
            </form>

            <h3><?= e($t->text('admin.lichess.player_title')) ?></h3>
            <form method="post" action="<?= e($basePath) ?>/admin/lichess/import-player" class="form">
                <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
                <?= $themeChoiceHtml ?>
                <label><?= e($t->text('admin.lichess.username')) ?> <input type="text" name="username" required maxlength="30" pattern="[A-Za-z0-9_\-]{2,30}"></label>
                <label><?= e($t->text('admin.lichess.count')) ?> <input type="number" name="count" min="1" max="<?= e($maxGames) ?>" value="10" required></label>
                <p class="button-row"><button type="submit" class="button"><?= e($t->text('study.import')) ?></button></p>
            </form>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php // ===== 4. Players' own games (B37) ===== ?>
<section aria-labelledby="own-settings-title">
    <h2 id="own-settings-title">4. <?= e($t->text('admin.lichess.settings_title')) ?></h2>
    <form method="post" action="<?= e($basePath) ?>/admin/lichess/settings" class="form">
        <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
        <label><?= e($t->text('admin.lichess.max_own_games')) ?> <input type="number" name="max_own_games" min="1" max="500" value="<?= e($maxOwnGames) ?>" required></label>
        <label><?= e($t->text('admin.lichess.imports_per_hour')) ?> <input type="number" name="imports_per_hour" min="1" max="100" value="<?= e($importsPerHour) ?>" required></label>
        <label><?= e($t->text('admin.lichess.own_game_days')) ?> <input type="number" name="own_game_days" min="0" max="3650" value="<?= e($ownGameDays) ?>" required></label>
        <label class="checkbox"><input type="checkbox" name="paste_for_players" value="on"<?= $pasteIsOpen ? ' checked' : '' ?>> <?= e($t->text('admin.lichess.paste_for_players')) ?></label>
        <p class="button-row"><button type="submit" class="button"><?= e($t->text('admin.lichess.save')) ?></button></p>
    </form>
</section>
