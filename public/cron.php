<?php

/**
 * The job runner's entry point for a real cron (INS-08, ARCHITECTURE.md §8).
 *
 * Runs from the command line only, for example in the hosting panel's cron
 * settings, every minute:
 *
 *     php /home/you/chess/public/cron.php
 *
 * Opened in a browser it refuses (403): jobs are never started by a visitor
 * directly. Without cron, the pseudo-cron in index.php does the same work.
 */

declare(strict_types=1);

use PhpChess\Kernel\Kernel;

if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    echo 'The job runner runs from the command line only.';
    exit;
}

$appRoot = dirname(__DIR__);
require $appRoot . '/vendor/autoload.php';

$kernel = new Kernel($appRoot);
$kernel->registerErrorHandler();

foreach ($kernel->jobRunner()->runFromCron(time()) as $line) {
    echo gmdate('Y-m-d H:i:s') . ' ' . $line . "\n";
}
