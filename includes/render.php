<?php
/**
 * Page rendering, shared by the PHP entry points and the static builder.
 *
 * Exactly the same templates produce both, so a page served by PHP and the
 * generated .html file are byte-for-byte identical apart from the link style
 * (absolute when PHP serves it, relative in the generated files so they work
 * with no server at all).
 */

declare(strict_types=1);

require_once __DIR__ . '/posts.php';

/** Wrap a template in the site's header and footer and return the HTML. */
function render_page(string $template, array $vars, string $title, string $desc): string
{
    $pageTitle = $title;
    $pageDesc = $desc;
    extract($vars, EXTR_SKIP);

    ob_start();
    require __DIR__ . '/header.php';
    require __DIR__ . '/../templates/' . $template;
    require __DIR__ . '/footer.php';
    return (string) ob_get_clean();
}

/** The blog listing page. */
function render_listing(array $posts): string
{
    return render_page(
        'listing.php',
        ['posts' => $posts],
        'Blog - Rekrutify - Talent Without Borders',
        'Our Blog - Rekrutify. Latest insights and industry trends on staff augmentation, offshore hiring, remote teams and global talent.'
    );
}

/** One article page. */
function render_article(array $post): string
{
    return render_page(
        'article.php',
        ['post' => $post],
        $post['title'] . ' - Rekrutify',
        $post['excerpt'] !== '' ? $post['excerpt'] : $post['title']
    );
}

/**
 * Regenerate every static blog page from the post files.
 *
 * Writes blog.html in the site root and one blog/<slug>.html per published
 * post, then deletes any leftover article files whose post is gone or has been
 * unpublished. Returns a short report for the admin panel.
 */
function build_static_site(): array
{
    $posts = all_posts();
    $written = [];
    $removed = [];
    $errors = [];

    if (!is_dir(STATIC_POSTS_DIR) && !mkdir(STATIC_POSTS_DIR, 0775, true) && !is_dir(STATIC_POSTS_DIR)) {
        return ['written' => [], 'removed' => [], 'errors' => ['Could not create the blog/ folder.']];
    }

    // Articles live one level down, so their links need a "../" prefix.
    url_prefix('../');
    foreach ($posts as $post) {
        $file = STATIC_POSTS_DIR . '/' . $post['slug'] . '.html';
        if (file_put_contents($file, render_article($post), LOCK_EX) === false) {
            $errors[] = 'Could not write ' . basename($file);
        } else {
            $written[] = 'blog/' . $post['slug'] . '.html';
        }
    }

    // The listing sits in the site root, so its links need no prefix.
    url_prefix('');
    if (file_put_contents(STATIC_LISTING, render_listing($posts), LOCK_EX) === false) {
        $errors[] = 'Could not write blog.html';
    } else {
        $written[] = 'blog.html';
    }

    url_prefix(null, true);

    // Drop article files that no longer correspond to a published post.
    $keep = [];
    foreach ($posts as $post) {
        $keep[$post['slug'] . '.html'] = true;
    }
    foreach (glob(STATIC_POSTS_DIR . '/*.html') ?: [] as $file) {
        if (!isset($keep[basename($file)]) && unlink($file)) {
            $removed[] = 'blog/' . basename($file);
        }
    }

    return ['written' => $written, 'removed' => $removed, 'errors' => $errors];
}
