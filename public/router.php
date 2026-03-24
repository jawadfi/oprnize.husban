<?php

/**
 * PHP built-in server router for Laravel.
 * Serves static files directly; passes all other requests to index.php.
 * Used by `php -S` on Render.com (start.sh / Procfile).
 */
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// Serve existing static files (js, css, images, fonts, etc.) directly
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    return false;
}

// Route everything else through Laravel
require __DIR__ . '/index.php';
