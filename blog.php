<?php
/**
 * Blog listing, served by PHP.
 *
 * The public site normally uses the generated blog.html instead — this exists
 * so the admin can preview changes immediately, and so the blog still works if
 * the static files have not been built yet. Both use the same template.
 */

declare(strict_types=1);

require_once __DIR__ . '/includes/render.php';

echo render_listing(all_posts());
