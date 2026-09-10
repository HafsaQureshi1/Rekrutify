<?php
/** Admin: login form when logged out, list of posts when logged in. */

declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/layout.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !is_logged_in()) {
    check_csrf();
    if (attempt_login((string) ($_POST['user'] ?? ''), (string) ($_POST['password'] ?? ''))) {
        header('Location: ./index.php');
        exit;
    }
    $error = 'Wrong username or password.';
}

if (!is_logged_in()) {
    admin_header('Sign in', false);
    ?>
    <div class="login">
      <h1>Blog Admin</h1>
      <p class="sub">Sign in to add or edit articles.</p>
      <?php if ($error !== ''): ?><div class="notice err"><?= h($error) ?></div><?php endif; ?>
      <form method="post" class="panel">
        <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
        <label for="user">Username</label>
        <input id="user" name="user" type="text" autocomplete="username" required autofocus>
        <label for="password">Password</label>
        <input id="password" name="password" type="password" autocomplete="current-password" required>
        <div class="actions"><button class="btn" type="submit">Sign in</button></div>
      </form>
    </div>
    <?php
    admin_footer();
    exit;
}

// Manual rebuild, for when the JSON files have been edited outside the panel.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'rebuild') {
    check_csrf();
    $build = build_static_site();
    $msg = $build['errors']
        ? 'Rebuild finished with problems: ' . implode(' ', $build['errors'])
        : 'Rebuilt ' . count($build['written']) . ' page(s)'
            . ($build['removed'] ? ', removed ' . count($build['removed']) : '') . '.';
    header('Location: ./index.php?msg=' . rawurlencode($msg));
    exit;
}

$posts = all_posts(true);
$flash = (string) ($_GET['msg'] ?? '');

// Warn if the generated pages are missing or older than the newest post file.
$staleReason = '';
if (!is_file(STATIC_LISTING)) {
    $staleReason = 'The static blog pages have not been generated yet.';
} else {
    $newest = 0;
    foreach (glob(POSTS_DIR . '/*.json') ?: [] as $f) {
        $newest = max($newest, (int) filemtime($f));
    }
    if ($newest > (int) filemtime(STATIC_LISTING)) {
        $staleReason = 'A post file has changed since the pages were last built.';
    }
}

admin_header('All posts');
?>
<h1>Articles</h1>
<p class="sub"><?= count($posts) ?> post<?= count($posts) === 1 ? '' : 's' ?>. Newest first — the blog page uses this same order.</p>

<?php if ($flash !== ''): ?>
  <div class="notice ok"><?= h($flash) ?></div>
<?php endif; ?>

<?php if ($staleReason !== ''): ?>
  <div class="notice warn">
    <strong><?= h($staleReason) ?></strong>
    The public blog reads the generated files, so press Rebuild pages to bring it up to date.
  </div>
<?php endif; ?>

<form method="post" style="margin-bottom:20px">
  <input type="hidden" name="csrf" value="<?= h(csrf_token()) ?>">
  <input type="hidden" name="action" value="rebuild">
  <button class="btn btn-ghost" type="submit">Rebuild pages</button>
  <span class="muted" style="margin-left:10px; font-size:13px">
    Regenerates <code>blog.html</code> and <code>blog/*.html</code>. Saving a post does this for you.
  </span>
</form>

<div class="panel">
<?php if (!$posts): ?>
  <div class="empty">No articles yet. <a href="./edit.php">Write the first one</a>.</div>
<?php else: ?>
  <table>
    <thead>
      <tr><th></th><th>Title</th><th>Date</th><th>Category</th><th>Status</th><th></th></tr>
    </thead>
    <tbody>
    <?php foreach ($posts as $p): ?>
      <tr>
        <td>
          <?php if ($p['image'] !== '' && is_file(IMAGES_DIR . '/' . $p['image'])): ?>
            <img class="thumb" src="<?= h(post_image_url($p["image"])) ?>" alt="">
          <?php endif; ?>
        </td>
        <td class="title">
          <a href="<?= h('./edit.php?slug=' . rawurlencode($p['slug'])) ?>"><?= h($p['title']) ?></a>
          <div class="muted" style="font-size:13px"><?= h($p['slug']) ?></div>
        </td>
        <td class="muted"><?= h($p['date']) ?></td>
        <td class="muted"><?= h($p['category']) ?></td>
        <td>
          <?php if ($p['published']): ?>
            <span class="tag live">Published</span>
          <?php else: ?>
            <span class="tag draft">Draft</span>
          <?php endif; ?>
        </td>
        <td style="text-align:right; white-space:nowrap">
          <a class="btn btn-ghost" href="<?= h(post_url($p['slug'])) ?>" target="_blank">View</a>
          <a class="btn btn-ghost" href="<?= h('./edit.php?slug=' . rawurlencode($p['slug'])) ?>">Edit</a>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
<?php endif; ?>
</div>

<?php admin_footer(); ?>
