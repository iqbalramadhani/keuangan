<?php
declare(strict_types=1);

/**
 * public/index.php — the ONLY file served by the web server.
 * Bootstraps the app and dispatches the request.
 */

// Bootstrap (composer-less autoload + config + DB + session).
$root = dirname(__DIR__);
require $root . '/app/Bootstrap.php';

/** @var PDO $db */
$db = \App\Bootstrap::boot();

use App\Core\App;

// Force safe cookie behaviour for the Secure flag regardless of detect.
if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: SAMEORIGIN');
    header('Referrer-Policy: same-origin');
    header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
}

$app = new App($db, \App\Bootstrap::$config);

// Inject CSRF into the meta tag for JS access (layouts also rely on input fields).
$app->run();
