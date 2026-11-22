<?php

/**
 * Front controller: the only PHP file the web server runs for pages (ARCHITECTURE.md §5).
 *
 * Every address of the site comes here, through the .htaccess files (or the
 * nginx snippet from the installer). It reads the request, lets the Kernel
 * answer it, sends the answer, and then gives the Kernel a moment for
 * background jobs (pseudo-cron, INS-08) after the visitor has the page.
 */

declare(strict_types=1);

use PhpChess\Http\Request;
use PhpChess\Kernel\Kernel;
use PhpChess\Kernel\Session;

// --- Loading the code ---

$appRoot = dirname(__DIR__);
require $appRoot . '/vendor/autoload.php';

// --- Answering the request ---

$kernel = new Kernel($appRoot);
$kernel->registerErrorHandler();

$request = Request::fromGlobals();
$session = Session::start($request->basePath, $request->isHttps);

$response = $kernel->handle($request, $session);
session_write_close();
$response->send();

// --- After the answer: background jobs ---

// Hands the finished page to the visitor now, so the jobs never slow them down.
if (function_exists('fastcgi_finish_request')) {
    fastcgi_finish_request();
} else {
    flush();
}
$kernel->afterResponse();
