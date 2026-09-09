<?php
/**
 * A single article, served by PHP.
 *
 * The public site normally uses the generated blog/<slug>.html file; this is
 * the admin's live preview and the fallback if the static files have not been
 * built yet. Both come from the same template.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/render.php';

$slug = (string) ($_GET['slug'] ?? '');
$post = $slug !== '' ? get_post($slug) : null;

if ($post === null || !$post['published']) {
    http_response_code(404);
    echo render_page(
        'not-found.php',
        [],
        'Post not found - Rekrutify - Talent Without Borders',
        'The article you were looking for is not available.'
    );
    exit;
}

echo render_article($post);
