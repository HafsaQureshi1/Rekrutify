<?php
/**
 * Post storage.
 *
 * One JSON file per post under data/posts/<slug>.json. No database needed, so
 * the blog runs on any PHP host and on XAMPP with nothing to configure. The
 * shape below is the whole contract between the admin panel and the templates:
 *
 *   slug            string   URL key, e.g. "staff-augmentation-2026"
 *   title           string   post + banner heading
 *   date            string   display date, e.g. "April 14, 2026"
 *   sort_date       string   Y-m-d, used only for ordering
 *   category        string   shown next to the folder icon
 *   author          string   shown next to the user icon
 *   image           string   filename inside assets/images
 *   image_alt       string
 *   excerpt         string   the blurb on the blog listing card
 *   takeaways       string[] bullets in the "Key Takeaways" box (may be empty)
 *   body            string   article HTML (h2/h3/p/ul, as on the live site)
 *   further_reading array    [{href,label}] links under "Further Reading"
 *   published       bool     false hides it from the listing and 404s the post
 */

declare(strict_types=1);

require_once __DIR__ . '/config.php';

/** A slug is only ever lowercase letters, digits and dashes. */
function is_valid_slug(string $slug): bool
{
    return (bool) preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug);
}

/** Turn a title into a slug. */
function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text) ?? '';
    return trim($text, '-');
}

/** Full path of a post file. Returns null when the slug is not well-formed. */
function post_path(string $slug): ?string
{
    return is_valid_slug($slug) ? POSTS_DIR . '/' . $slug . '.json' : null;
}

/** Normalise a decoded post, filling in anything the file left out. */
function normalise_post(array $p, string $slug): array
{
    $p['slug'] = $slug;
    $p['title'] = (string) ($p['title'] ?? '');
    $p['date'] = (string) ($p['date'] ?? '');
    $p['sort_date'] = (string) ($p['sort_date'] ?? '');
    $p['order'] = isset($p['order']) && $p['order'] !== '' ? (int) $p['order'] : 0;
    $p['category'] = (string) ($p['category'] ?? '');
    $p['author'] = (string) ($p['author'] ?? DEFAULT_AUTHOR);
    $p['image'] = (string) ($p['image'] ?? '');
    $p['image_alt'] = (string) ($p['image_alt'] ?? $p['title']);
    $p['excerpt'] = (string) ($p['excerpt'] ?? '');
    $p['body'] = (string) ($p['body'] ?? '');
    $p['takeaways'] = array_values(array_filter((array) ($p['takeaways'] ?? [])));
    $p['published'] = (bool) ($p['published'] ?? true);

    $links = [];
    foreach ((array) ($p['further_reading'] ?? []) as $link) {
        if (!empty($link['label'])) {
            $links[] = ['href' => (string) ($link['href'] ?? '#'), 'label' => (string) $link['label']];
        }
    }
    $p['further_reading'] = $links;

    return $p;
}

/** Load one post, or null if it does not exist / is unreadable. */
function get_post(string $slug): ?array
{
    $path = post_path($slug);
    if ($path === null || !is_file($path)) {
        return null;
    }
    $data = json_decode((string) file_get_contents($path), true);
    return is_array($data) ? normalise_post($data, $slug) : null;
}

/**
 * All posts, in the order the blog listing shows them.
 *
 * `order` wins: lower numbers come first, which is how the nine original
 * articles keep the exact sequence the live site used (it is not date order —
 * the April 14 post sits above the April 16 one). A post with no `order`, such
 * as anything the admin panel creates, is treated as 0 and therefore appears at
 * the top. Ties, and posts that share an order, fall back to newest first by
 * sort_date, then the display date, then the file's modification time.
 */
function all_posts(bool $includeUnpublished = false): array
{
    $posts = [];
    foreach (glob(POSTS_DIR . '/*.json') ?: [] as $file) {
        $slug = basename($file, '.json');
        $post = get_post($slug);
        if ($post === null) {
            continue;
        }
        if (!$includeUnpublished && !$post['published']) {
            continue;
        }
        $stamp = $post['sort_date'] !== '' ? strtotime($post['sort_date']) : strtotime($post['date']);
        $post['_stamp'] = $stamp !== false ? $stamp : filemtime($file);
        $posts[] = $post;
    }

    usort($posts, static function (array $a, array $b): int {
        return [$a['order'], -$a['_stamp']] <=> [$b['order'], -$b['_stamp']];
    });

    return $posts;
}

/** Write a post to disk. Returns true on success. */
function save_post(array $post): bool
{
    $slug = (string) ($post['slug'] ?? '');
    $path = post_path($slug);
    if ($path === null) {
        return false;
    }
    if (!is_dir(POSTS_DIR)) {
        mkdir(POSTS_DIR, 0775, true);
    }
    unset($post['_stamp']);
    $json = json_encode(
        normalise_post($post, $slug),
        JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
    );

    return $json !== false && file_put_contents($path, $json, LOCK_EX) !== false;
}

/** Delete a post. Returns true if it is gone afterwards. */
function delete_post(string $slug): bool
{
    $path = post_path($slug);
    return $path !== null && is_file($path) ? unlink($path) : false;
}

/**
 * URL of a single post.
 *
 * Static mode points at the generated file, so the link works with no server at
 * all. PHP mode uses the pretty route when it is switched on.
 */
function post_url(string $slug): string
{
    if (url_prefix() !== null) {
        return url('blog/' . rawurlencode($slug) . '.html');
    }
    return PRETTY_URLS
        ? base_url() . 'blog/' . rawurlencode($slug)
        : base_url() . 'post.php?slug=' . rawurlencode($slug);
}

/** URL of the blog listing. */
function blog_url(): string
{
    if (url_prefix() !== null) {
        return url('blog.html');
    }
    return PRETTY_URLS ? base_url() . 'blog' : base_url() . 'blog.php';
}

/** Public URL of a post image. */
function post_image_url(string $image): string
{
    return url('assets/images/' . rawurlencode($image));
}

/**
 * Rewrite the "./page.html" and "./post.php?slug=..." links stored inside an
 * article body so they resolve from any URL depth.
 *
 * Article bodies are written relative to the site root ("./contact.html"),
 * which is correct at /blog.php but wrong at /blog/some-slug — the browser
 * would look for /blog/contact.html. This rebases them, and upgrades any
 * stored post.php links to pretty URLs when those are switched on.
 */
function rebase_link(string $href): string
{
    if (preg_match('~^\./post\.php\?slug=([A-Za-z0-9_\-]+)$~', $href, $m)) {
        return post_url($m[1]);
    }
    if (str_starts_with($href, './')) {
        return url(substr($href, 2));
    }
    return $href;
}

function rebase_body_links(string $html): string
{
    $html = preg_replace_callback(
        '~href="\./post\.php\?slug=([A-Za-z0-9_\-]+)"~',
        static fn(array $m): string => 'href="' . post_url($m[1]) . '"',
        $html
    ) ?? $html;

    // Everything else that was written as "./something" hangs off the site root.
    return str_replace('href="./', 'href="' . url(''), $html);
}
