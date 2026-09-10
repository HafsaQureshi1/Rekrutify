<?php
/**
 * Admin chrome. Deliberately plain and self-contained — it does not load the
 * public site's 480KB stylesheet, so the panel stays fast and can never be
 * broken by a change to the marketing site's CSS.
 */

declare(strict_types=1);

function admin_header(string $title, bool $showNav = true): void
{
    ?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($title) ?> — Rekrutify Blog Admin</title>
<link rel="icon" href="<?= h(base_url()) ?>assets/images/favicon.ico">
<style>
  :root {
    --bg: #0f1211; --panel: #171b1a; --line: #272d2b; --line-soft: #1f2523;
    --text: #d1d1d1; --muted: #8b8b8b; --accent: #21a37a; --accent-2: #0b1512;
    --danger: #d9534f; --radius: 10px;
  }
  * { box-sizing: border-box; }
  body { margin: 0; background: var(--bg); color: var(--text);
    font: 15px/1.55 "Plus Jakarta Sans", -apple-system, "Segoe UI", system-ui, sans-serif; }
  a { color: var(--accent); }
  .wrap { max-width: 1000px; margin: 0 auto; padding: 28px 20px 64px; }
  .topbar { border-bottom: 1px solid var(--line); background: #121615; }
  .topbar .wrap { display: flex; align-items: center; gap: 18px; padding: 16px 20px; }
  .brand { font-weight: 700; letter-spacing: .04em; margin-right: auto; }
  .brand span { color: var(--accent); }
  h1 { font-size: 26px; margin: 0 0 4px; }
  h2 { font-size: 18px; margin: 28px 0 10px; }
  .sub { color: var(--muted); margin: 0 0 24px; }
  .panel { background: var(--panel); border: 1px solid var(--line); border-radius: var(--radius); padding: 22px; }
  label { display: block; font-weight: 600; margin: 16px 0 6px; }
  label .hint { display: block; font-weight: 400; color: var(--muted); font-size: 13px; margin-top: 2px; }
  input[type=text], input[type=password], input[type=date], select, textarea {
    width: 100%; padding: 10px 12px; background: #0f1312; color: var(--text);
    border: 1px solid var(--line); border-radius: 8px; font: inherit; }
  textarea { min-height: 120px; resize: vertical; font-family: ui-monospace, "Cascadia Code", Consolas, monospace; font-size: 13.5px; }
  input:focus, textarea:focus, select:focus { outline: 2px solid var(--accent); outline-offset: -1px; }
  .row { display: flex; gap: 16px; flex-wrap: wrap; }
  .row > * { flex: 1 1 220px; }
  .btn { display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; border-radius: 999px;
    border: 1px solid transparent; background: var(--accent); color: #06120d; font-weight: 700;
    cursor: pointer; text-decoration: none; font-size: 15px; }
  .btn:hover { filter: brightness(1.08); }
  .btn-ghost { background: transparent; border-color: var(--line); color: var(--text); font-weight: 600; }
  .btn-danger { background: transparent; border-color: var(--danger); color: var(--danger); font-weight: 600; }
  .actions { display: flex; gap: 12px; align-items: center; margin-top: 26px; padding-top: 20px; border-top: 1px solid var(--line-soft); }
  table { width: 100%; border-collapse: collapse; }
  th, td { text-align: left; padding: 12px 10px; border-bottom: 1px solid var(--line-soft); vertical-align: middle; }
  th { color: var(--muted); font-size: 12px; text-transform: uppercase; letter-spacing: .06em; }
  td.title a { font-weight: 600; text-decoration: none; }
  .thumb { width: 64px; height: 42px; object-fit: cover; border-radius: 6px; display: block; }
  .tag { display: inline-block; font-size: 12px; padding: 3px 9px; border-radius: 999px;
    border: 1px solid var(--line); color: var(--muted); }
  .tag.live { border-color: #1f5c47; color: var(--accent); }
  .tag.draft { border-color: #5c4a1f; color: #d0a63c; }
  .notice { padding: 12px 16px; border-radius: 8px; margin-bottom: 20px; border: 1px solid; }
  .notice.ok { border-color: #1f5c47; background: #21a37a14; color: var(--accent); }
  .notice.warn { border-color: #5c4a1f; background: #d0a63c14; color: #d0a63c; }
  .notice.err { border-color: #6b2c2a; background: #d9534f14; color: #e8807c; }
  .login { max-width: 380px; margin: 12vh auto; }
  .muted { color: var(--muted); }
  .empty { text-align: center; padding: 48px 20px; color: var(--muted); }
  code { background: #0f1312; padding: 2px 6px; border-radius: 4px; font-size: 13px; }
</style>
</head>
<body>
<?php if ($showNav): ?>
<div class="topbar">
  <div class="wrap">
    <div class="brand">REKRUTIFY <span>Blog Admin</span></div>
    <a href="./index.php" class="btn-ghost btn">All posts</a>
    <a href="./edit.php" class="btn">New post</a>
    <a href="<?= h(blog_url()) ?>" target="_blank" class="btn-ghost btn">View blog</a>
    <a href="./logout.php" class="btn-ghost btn">Log out</a>
  </div>
</div>
<?php endif; ?>
<div class="wrap">
<?php if ($showNav && using_default_password()): ?>
  <div class="notice warn">
    <strong>This panel still uses the default password.</strong>
    Run <code>php tools/make-password.php "your new password"</code> and paste the
    result into <code>includes/config.php</code> before putting this online.
  </div>
<?php endif;
}

function admin_footer(): void
{
    echo "</div>\n</body>\n</html>\n";
}
