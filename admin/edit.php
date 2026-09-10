<?php
/** Admin: create or edit one article. */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';
require_login();

$originalSlug = (string) ($_GET['slug'] ?? '');
$isNew = $originalSlug === '';
$errors = [];

// Blank post used for the "new article" form.
$post = [
    'slug' => '', 'title' => '', 'date' => date('F j, Y'), 'sort_date' => date('Y-m-d'),
    'order' => 0,
    'category' => '', 'author' => DEFAULT_AUTHOR, 'image' => '', 'image_alt' => '',
    'excerpt' => '', 'takeaways' => [], 'body' => '', 'further_reading' => [], 'published' => true,
];

if (!$isNew) {
    $existing = get_post($originalSlug);
    if ($existing === null) {
        admin_header('Not found');
        echo '<div class="notice err">No article with the slug <code>' . h($originalSlug) . '</code>.</div>';
        echo '<a class="btn" href="./index.php">Back to all posts</a>';
        admin_footer();
        exit;
    }
    $post = $existing;
}

/** "Label | url" per line -> [{label,href}] */
function parse_links(string $raw): array
{
    $out = [];
    foreach (preg_split('/\R/', $raw) ?: [] as $line) {
        $line = trim($line);
        if ($line === '') {
            continue;
        }
        $parts = explode('|', $line, 2);
        $label = trim($parts[0]);
        $href = isset($parts[1]) ? trim($parts[1]) : '#';
        if ($label !== '') {
            $out[] = ['label' => $label, 'href' => $href === '' ? '#' : $href];
        }
    }
    return $out;
}

/** [{label,href}] -> "Label | url" per line */
function format_links(array $links): string
{
    return implode("\n", array_map(
        static fn(array $l): string => $l['label'] . ' | ' . $l['href'],
        $links
    ));
}

/** One non-empty trimmed item per line. */
function parse_lines(string $raw): array
{
    return array_values(array_filter(array_map('trim', preg_split('/\R/', $raw) ?: [])));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    if (($_POST['action'] ?? '') === 'delete' && !$isNew) {
        delete_post($originalSlug);
        build_static_site();
        header('Location: ./index.php?msg=' . rawurlencode('Deleted "' . $post['title'] . '". The blog pages have been rebuilt.'));
        exit;
    }

    $post['title'] = trim((string) ($_POST['title'] ?? ''));
    $post['slug'] = trim((string) ($_POST['slug'] ?? '')) ?: slugify($post['title']);
    $post['date'] = trim((string) ($_POST['date'] ?? ''));
    $post['sort_date'] = trim((string) ($_POST['sort_date'] ?? ''));
    $post['order'] = (int) ($_POST['order'] ?? 0);
    $post['category'] = trim((string) ($_POST['category'] ?? ''));
    $post['author'] = trim((string) ($_POST['author'] ?? '')) ?: DEFAULT_AUTHOR;
    $post['excerpt'] = trim((string) ($_POST['excerpt'] ?? ''));
    $post['body'] = trim((string) ($_POST['body'] ?? ''));
    $post['image'] = trim((string) ($_POST['image'] ?? ''));
    $post['image_alt'] = trim((string) ($_POST['image_alt'] ?? '')) ?: $post['title'];
    $post['takeaways'] = parse_lines((string) ($_POST['takeaways'] ?? ''));
    $post['further_reading'] = parse_links((string) ($_POST['further_reading'] ?? ''));
    $post['published'] = !empty($_POST['published']);

    // When a date is picked, derive the display date from it automatically.
    if ($post['sort_date'] !== '' && ($ts = strtotime($post['sort_date'])) !== false) {
        if ($post['date'] === '' || !empty($_POST['sync_date'])) {
            $post['date'] = date('F j, Y', $ts);
        }
    }

    // --- Optional image upload -------------------------------------------
    if (!empty($_FILES['image_file']['name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
        $file = $_FILES['image_file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'Image upload failed (error code ' . (int) $file['error'] . ').';
        } elseif ($file['size'] > 8 * 1024 * 1024) {
            $errors[] = 'Image is larger than 8 MB.';
        } else {
            // Trust the file's actual content, never its name or client type.
            $info = @getimagesize($file['tmp_name']);
            $allowed = [IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png', IMAGETYPE_WEBP => 'webp', IMAGETYPE_GIF => 'gif'];
            if ($info === false || !isset($allowed[$info[2]])) {
                $errors[] = 'That file is not a JPEG, PNG, WebP or GIF image.';
            } else {
                $base = slugify(pathinfo((string) $file['name'], PATHINFO_FILENAME)) ?: 'blog-image';
                $name = $base . '.' . $allowed[$info[2]];
                $i = 2;
                while (is_file(IMAGES_DIR . '/' . $name)) {
                    $name = $base . '-' . $i++ . '.' . $allowed[$info[2]];
                }
                if (move_uploaded_file($file['tmp_name'], IMAGES_DIR . '/' . $name)) {
                    $post['image'] = $name;
                } else {
                    $errors[] = 'Could not save the uploaded image into assets/images.';
                }
            }
        }
    }

    // --- Validation -------------------------------------------------------
    if ($post['title'] === '') {
        $errors[] = 'Title is required.';
    }
    if (!is_valid_slug($post['slug'])) {
        $errors[] = 'The URL slug must be lowercase letters, numbers and dashes only.';
    }
    if ($post['body'] === '') {
        $errors[] = 'The article body is empty.';
    }
    if ($post['image'] !== '' && !is_file(IMAGES_DIR . '/' . $post['image'])) {
        $errors[] = 'No image named "' . $post['image'] . '" exists in assets/images.';
    }
    // Renaming onto an existing post, or creating a duplicate, would overwrite it.
    if ($post['slug'] !== $originalSlug && get_post($post['slug']) !== null) {
        $errors[] = 'Another article already uses the slug "' . $post['slug'] . '".';
    }

    if (!$errors) {
        if (save_post($post)) {
            if (!$isNew && $post['slug'] !== $originalSlug) {
                delete_post($originalSlug);
            }
            // Regenerate the static pages so the public blog is up to date
            // without PHP needing to run again.
            $build = build_static_site();
            $flash = $build['errors'] ? 'built-with-errors' : 'saved';
            header('Location: ./edit.php?slug=' . rawurlencode($post['slug']) . '&' . $flash . '=1');
            exit;
        }
        $errors[] = 'Could not write the post file. Check that data/posts is writable.';
    }
}

$images = [];
foreach (glob(IMAGES_DIR . '/*.{png,jpg,jpeg,webp,gif}', GLOB_BRACE) ?: [] as $f) {
    $images[] = basename($f);
}
sort($images);

admin_header($isNew ? 'New post' : 'Edit post');
?>
<h1><?= $isNew ? 'New article' : 'Edit article' ?></h1>
<p class="sub">
  <?php if ($isNew): ?>
    Fill this in and save — the article appears on the blog immediately, no page to create.
  <?php else: ?>
    <a href="<?= h(post_url($post["slug"])) ?>" target="_blank">View this post on the site &rarr;</a>
  <?php endif; ?>
</p>

<?php if (!empty($_GET['saved'])): ?>
  <div class="notice ok">Saved, and the static blog pages were rebuilt — the public site is up to date even with PHP switched off.</div>
<?php endif; ?>

<?php if (!empty($_GET['built-with-errors'])): ?>
  <div class="notice warn">
    Saved, but the static pages could not all be written. Check that
    <code>blog.html</code> and the <code>blog/</code> folder are writable, then use
    <strong>Rebuild pages</strong> on the posts list.
  </div>
<?php endif; ?>

<?php if ($errors): ?>
  <div class="notice err">
    <strong>Not saved:</strong>
    <ul style="margin:8px 0 0 18px; padding:0"><?php foreach ($errors as $e): ?><li><?= h($e) ?></li><?php endforeach; ?></ul>
  </div>
<?php endif; ?>

<form method="post" enctype="multipart/form-data" class="panel">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">

  <label for="title">Title</label>
  <input id="title" name="title" type="text" required value="<?= h($post['title']) ?>">

  <label for="slug">URL slug
    <span class="hint">Lowercase letters, numbers and dashes. The page will be <code>post.php?slug=…</code>. Leave blank to build it from the title.</span>
  </label>
  <input id="slug" name="slug" type="text" value="<?= h($post['slug']) ?>" placeholder="<?= h($isNew ? 'auto-generated-from-title' : '') ?>">

  <div class="row">
    <div>
      <label for="sort_date">Date</label>
      <input id="sort_date" name="sort_date" type="date" value="<?= h($post['sort_date']) ?>">
    </div>
    <div>
      <label for="date">Displayed as
        <span class="hint">Shown on the card and the article. Tick below to regenerate it from the date.</span>
      </label>
      <input id="date" name="date" type="text" value="<?= h($post['date']) ?>" placeholder="April 14, 2026">
      <label style="font-weight:400; margin-top:8px">
        <input type="checkbox" name="sync_date" value="1" style="width:auto"> Rebuild from the date field
      </label>
    </div>
  </div>

  <div class="row">
    <div>
      <label for="category">Category</label>
      <input id="category" name="category" type="text" value="<?= h($post['category']) ?>" placeholder="Remote Work" list="categories">
      <datalist id="categories">
        <?php
        $cats = [];
        foreach (all_posts(true) as $other) {
            if ($other['category'] !== '') {
                $cats[$other['category']] = true;
            }
        }
        foreach (array_keys($cats) as $c) {
            echo '<option value="' . h($c) . '">';
        }
        ?>
      </datalist>
    </div>
    <div>
      <label for="author">Author</label>
      <input id="author" name="author" type="text" value="<?= h($post['author']) ?>">
    </div>
    <div>
      <label for="order">Position on the blog page
        <span class="hint">Lower shows first. Leave at 0 and a new article goes to the top.</span>
      </label>
      <input id="order" name="order" type="text" inputmode="numeric" value="<?= h((string) $post['order']) ?>">
    </div>
  </div>

  <h2>Cover image</h2>
  <div class="row">
    <div>
      <label for="image">Use an existing image</label>
      <select id="image" name="image">
        <option value="">— none —</option>
        <?php foreach ($images as $img): ?>
          <option value="<?= h($img) ?>"<?= $img === $post['image'] ? ' selected' : '' ?>><?= h($img) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div>
      <label for="image_file">…or upload a new one
        <span class="hint">JPEG, PNG, WebP or GIF, up to 8 MB. An upload wins over the choice on the left.</span>
      </label>
      <input id="image_file" name="image_file" type="file" accept="image/*">
    </div>
  </div>
  <label for="image_alt">Image alt text</label>
  <input id="image_alt" name="image_alt" type="text" value="<?= h($post['image_alt']) ?>">

  <h2>Listing card</h2>
  <label for="excerpt">Short description
    <span class="hint">The blurb under the title on the blog grid, and the page's meta description.</span>
  </label>
  <textarea id="excerpt" name="excerpt" style="min-height:70px"><?= h($post['excerpt']) ?></textarea>

  <h2>Article</h2>
  <label for="takeaways">Key takeaways
    <span class="hint">One bullet per line. Leave empty to hide the box entirely.</span>
  </label>
  <textarea id="takeaways" name="takeaways" style="min-height:110px"><?= h(implode("\n", $post['takeaways'])) ?></textarea>

  <label for="body">Body
    <span class="hint">HTML: <code>&lt;h2&gt;</code> and <code>&lt;h3&gt;</code> for headings, <code>&lt;p&gt;</code> for paragraphs,
      <code>&lt;a class="accent-color" href="…"&gt;</code> for links, <code>&lt;strong&gt;</code> for bold.</span>
  </label>
  <textarea id="body" name="body" style="min-height:420px" required><?= h($post['body']) ?></textarea>

  <label for="further_reading">Further reading
    <span class="hint">One per line, as <code>Label | url</code>. Leave empty to hide the section.</span>
  </label>
  <textarea id="further_reading" name="further_reading" style="min-height:110px"><?= h(format_links($post['further_reading'])) ?></textarea>

  <label style="font-weight:600; margin-top:22px">
    <input type="checkbox" name="published" value="1" style="width:auto"<?= $post['published'] ? ' checked' : '' ?>>
    Published <span class="hint" style="display:inline">— unticked keeps it off the blog and returns 404.</span>
  </label>

  <div class="actions">
    <button class="btn" type="submit">Save</button>
    <a class="btn btn-ghost" href="./index.php">Cancel</a>
    <?php if (!$isNew): ?>
      <span style="margin-left:auto">
        <button class="btn btn-danger" type="submit" name="action" value="delete"
          onclick="return confirm('Delete &quot;<?= h(addslashes($post['title'])) ?>&quot; permanently?')">Delete</button>
      </span>
    <?php endif; ?>
  </div>
</form>

<script>
  // Suggest a slug from the title while typing a brand-new post.
  (function () {
    var title = document.getElementById('title'), slug = document.getElementById('slug');
    if (!title || !slug || slug.value !== '') return;
    title.addEventListener('input', function () {
      slug.value = title.value.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
    });
  })();
</script>

<?php admin_footer(); ?>
