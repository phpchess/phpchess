<?php
/**
 * The start page (a placeholder until chunk C1-2 builds the real one).
 *
 * Variables: $basePath (string), $version (string).
 */
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>phpChess</title>
    <link rel="stylesheet" href="<?= e($basePath) ?>/assets/css/base.css">
</head>
<body>
<main class="page">
    <h1>phpChess</h1>
    <p class="notice-success">phpChess <?= e($version) ?> is installed and running.</p>
    <p>Accounts, the admin area and chess play arrive in the next development steps.</p>
</main>
</body>
</html>
