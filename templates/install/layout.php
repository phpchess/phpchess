<?php
/**
 * The installer's page frame (INS-02): header, step counter, content.
 *
 * Variables: $title (string), $stepNumber (int, 0 = no step shown),
 * $basePath (string), $contentHtml (string, HTML already rendered and escaped).
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> - phpChess installer</title>
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/base.css">
</head>
<body>
<main class="page">
    <header class="page-header">
        <p class="brand">phpChess 6 installer</p>
        <?php if ($stepNumber > 0) : ?>
            <p class="step-counter">Step <?= e($stepNumber) ?> of 6</p>
        <?php endif; ?>
    </header>
    <h1><?= e($title) ?></h1>
    <?= $contentHtml ?>
</main>
</body>
</html>
