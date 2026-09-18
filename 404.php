<?php
/**
 * The site's 404 page.
 *
 * .htaccess routes any URL that is not a real file or folder here, using a path
 * relative to itself — so nothing in this project needs to know whether the site
 * lives at a domain root or in a subfolder like /rekrutify/.
 *
 * This is PHP rather than static HTML for one reason: Apache serves the error
 * page's HTML at whatever URL was missing, so a 404 at /blog/anything/deep would
 * resolve relative asset paths against that deeper path and render unstyled.
 * base_url() works the real location out at request time, so every link and
 * asset below is correct from any depth, at any install location.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/render.php';

http_response_code(404);

echo render_page(
    'error-404.php',
    [],
    'Error 404 - Rekrutify - Talent Without Borders',
    'The page you were looking for could not be found.'
);
