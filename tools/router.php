<?php
/**
 * Router for PHP's built-in server, which ignores .htaccess.
 *
 *     php -S localhost:8000 -t . tools/router.php
 *
 * It reproduces the two rewrites from .htaccess so /blog and /blog/<slug> work
 * the same way they do under Apache. Not used in production — on XAMPP or any
 * real Apache host, .htaccess does this instead.
 */

declare(strict_types=1);

$path = parse_url((string) $_SERVER['REQUEST_URI'], PHP_URL_PATH) ?: '/';
$root = __DIR__ . '/..';

// Serve anything that actually exists (assets, .html pages, .php scripts).
$candidate = realpath($root . $path);
if ($candidate !== false && is_file($candidate)) {
    return false;
}

if (preg_match('~^/blog/?$~', $path)) {
    require $root . '/blog.php';
    return true;
}

if (preg_match('~^/blog/([A-Za-z0-9_-]+)/?$~', $path, $m)) {
    $_GET['slug'] = $m[1];
    $_SERVER['SCRIPT_NAME'] = '/post.php';
    require $root . '/post.php';
    return true;
}

return false;
