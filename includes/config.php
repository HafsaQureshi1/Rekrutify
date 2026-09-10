<?php
/**
 * Site configuration for the PHP blog.
 *
 * The static pages (index.html, about.html, ...) are untouched and keep working
 * exactly as before. Only the blog listing, the blog posts and the admin panel
 * are PHP.
 */

declare(strict_types=1);

/** Where the post JSON files live — one file per post, named <slug>.json. */
const POSTS_DIR = __DIR__ . '/../data/posts';

/** Where post images are uploaded to (must stay inside assets/images). */
const IMAGES_DIR = __DIR__ . '/../assets/images';

/** Default author shown on a post when the form leaves it blank. */
const DEFAULT_AUTHOR = 'Rekrutify Team';

/**
 * Admin login.
 *
 * ADMIN_USER is the username. ADMIN_PASSWORD_HASH is a password_hash() digest —
 * never a plain password. Generate your own before putting this online:
 *
 *     php tools/make-password.php "your new password"
 *
 * and paste the result below. The placeholder hash corresponds to the password
 * "change-me-now" and MUST be replaced.
 */
const ADMIN_USER = 'admin';
const ADMIN_PASSWORD_HASH = '$2y$12$8nHxuFP.VnQ1h/AnEggxF.iNliFLngaW3zHN5brmmSUwNtyEZPbMa';
/**
 * Pretty article URLs.
 *
 * true  → /blog/staff-augmentation-2026   (matches the live site; needs the
 *         .htaccess in this folder and Apache's mod_rewrite, which XAMPP has on)
 * false → post.php?slug=staff-augmentation-2026  (works on any server at all)
 *
 * Both forms always keep working — this only decides which one the site links
 * to. If pretty links 404 for you, set this to false and everything still runs.
 */
const PRETTY_URLS = true;

/**
 * Where generated static pages are written.
 *
 * The admin panel rebuilds these on every save, so the public blog is plain
 * HTML that needs no PHP at all — it works from a static host, and by opening
 * the files directly. PHP is only needed to *change* content.
 */
const STATIC_LISTING = __DIR__ . '/../blog.html';
const STATIC_POSTS_DIR = __DIR__ . '/../blog';

/**
 * Link style for the page currently being rendered.
 *
 * null            → links are absolute, built on base_url(). Used when PHP is
 *                   serving the page (blog.php, post.php, the admin panel).
 * '' or '../' etc → links are relative to the page being written to disk. Used
 *                   by the static builder, so the output works over file://.
 */
function url_prefix(?string $set = null, bool $reset = false): ?string
{
    static $prefix = null;
    if ($reset) {
        $prefix = null;
    } elseif ($set !== null) {
        $prefix = $set;
    }
    return $prefix;
}

/** Build a URL to something in the site root, honouring the current mode. */
function url(string $path): string
{
    $path = ltrim($path, '/');
    $prefix = url_prefix();
    return $prefix === null ? base_url() . $path : $prefix . $path;
}

/**
 * The site's root URL path, worked out from wherever this is installed:
 * "/rekrutify/" under htdocs\rekrutify, "/" at a domain root. Every asset and
 * link is built on top of this, so the pages render identically whether the URL
 * is /blog.php or the two-levels-deep /blog/some-slug.
 */
function base_url(): string
{
    static $base = null;
    if ($base !== null) {
        return $base;
    }

    $appDir = str_replace('\\', '/', dirname(__DIR__));           // the project folder
    $docRoot = rtrim(str_replace('\\', '/', (string) ($_SERVER['DOCUMENT_ROOT'] ?? '')), '/');

    // Normal case: the project sits somewhere under the web root, so the URL
    // prefix is just the difference between the two paths. This is deliberately
    // anchored on the project folder rather than on the running script, so
    // admin/index.php produces the same base as blog.php in the root.
    if ($docRoot !== '' && stripos($appDir . '/', $docRoot . '/') === 0) {
        $base = substr($appDir, strlen($docRoot)) . '/';
        return $base = ($base === '/' ? '/' : $base);
    }

    // Fallback for setups where DOCUMENT_ROOT doesn't line up (php -S, symlinks):
    // walk up from the running script by however deep it sits in the project.
    $script = str_replace('\\', '/', (string) ($_SERVER['SCRIPT_NAME'] ?? '/index.php'));
    $depth = substr_count(trim(str_replace($appDir, '', str_replace('\\', '/', (string) ($_SERVER['SCRIPT_FILENAME'] ?? ''))), '/'), '/');
    $dir = dirname($script);
    for ($i = 0; $i < $depth; $i++) {
        $dir = dirname($dir);
    }
    $dir = str_replace('\\', '/', $dir);

    return $base = rtrim($dir, '/') . '/';
}

/** Escape for HTML output. Used everywhere user/admin input is printed. */
function h(?string $s): string
{
    return htmlspecialchars((string) $s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
