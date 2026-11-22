<?php
/**
 * A plain error page for refusals and failures (404, 405, 419, 500).
 *
 * Variables: $status (int), $message (string), $basePath (string).
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Error <?= e($status) ?> - phpChess</title>
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/base.css">
</head>
<body>
<main class="page">
    <h1>Error <?= e($status) ?></h1>
    <p><?= e($message) ?></p>
    <p><a href="<?= e($basePath) ?>/">Back to the start page</a></p>
</main>
</body>
</html>
