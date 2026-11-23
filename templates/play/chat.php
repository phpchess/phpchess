<?php
/**
 * Game chat (G-12), below the game: the lines so far, and for the two
 * players a form to add one. In "phrases" mode the form is a row of
 * buttons with fixed phrases; in "free" mode a text field.
 * Phrases are stored as "phrase:<name>" and shown in the reader's language.
 *
 * Variables: $lines (list of array{username, body, created_at}), $mode ("phrases" or "free"),
 * $phrases (list of string), $maxLength (int), $game (Game), $viewerId (int),
 * $basePath, $csrfToken, $t.
 */
?>
<section class="game-chat" aria-labelledby="game-chat-title">
    <h2 id="game-chat-title"><?= e($t->text('chat.title')) ?></h2>
    <?php if ($lines === []) : ?>
        <p class="muted"><?= e($t->text('chat.empty')) ?></p>
    <?php else : ?>
        <ol class="chat-lines">
            <?php foreach ($lines as $line) : ?>
                <li>
                    <strong><?= e($line['username']) ?>:</strong>
                    <?php if (str_starts_with($line['body'], 'phrase:')) : ?>
                        <?= e($t->text('chat.phrase.' . substr($line['body'], strlen('phrase:')))) ?>
                    <?php else : ?>
                        <?= e($line['body']) ?>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    <?php endif; ?>

    <?php if ($game->isPlayer($viewerId)) : ?>
        <form method="post" action="<?= e($basePath) ?>/games/<?= e($game->id) ?>/chat" class="chat-form">
            <input type="hidden" name="_csrf" value="<?= e($csrfToken) ?>">
            <?php if ($mode === 'phrases') : ?>
                <div class="button-row">
                    <?php foreach ($phrases as $phrase) : ?>
                        <button type="submit" name="text" value="<?= e($phrase) ?>" class="button button-secondary button-small"><?= e($t->text('chat.phrase.' . $phrase)) ?></button>
                    <?php endforeach; ?>
                </div>
            <?php else : ?>
                <label for="chat-text" class="visually-hidden"><?= e($t->text('chat.write')) ?></label>
                <input type="text" id="chat-text" name="text" maxlength="<?= e($maxLength) ?>" required placeholder="<?= e($t->text('chat.write')) ?>">
                <button type="submit" class="button"><?= e($t->text('chat.send')) ?></button>
            <?php endif; ?>
        </form>
    <?php endif; ?>
</section>
