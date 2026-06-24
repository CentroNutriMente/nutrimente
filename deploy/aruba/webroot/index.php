<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Aruba Hosting Linux — subfolder split front controller
|--------------------------------------------------------------------------
| Only the contents of Laravel's public/ folder live here in the docroot
| (the "www.saraalessandripsicologa.it" folder). The framework itself
| (app/, config/, vendor/, storage/, .env, ...) lives in "nutrimente_app".
|
| Layout for this account: the FTP root above the docroot is NOT writable, so
| "nutrimente_app" lives INSIDE the docroot, next to this index.php. It is kept
| unreachable over HTTP by its own deny-all .htaccess (nutrimente_app/.htaccess).
*/
$app_base = __DIR__.'/nutrimente_app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $app_base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $app_base.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $app_base.'/bootstrap/app.php';

// This docroot IS the real public directory. Without this, Laravel would
// look for the Vite build manifest under nutrimente_app/public/ and the
// compiled JS/CSS would 404.
$app->usePublicPath(__DIR__);

$app->handleRequest(Request::capture());
