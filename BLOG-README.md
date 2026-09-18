# Rekrutify — deployment & blog

## Deploying

Copy this whole folder onto the server. **There is nothing to edit.** Nothing in
the project names the folder it lives in, so the same files work unchanged at a
domain root (`https://rekrutify.com/`) or in a subfolder
(`http://localhost/rekrutify/`). Both layouts are tested.

Requirements: Apache with `mod_rewrite` (standard everywhere, and already on in
XAMPP) and PHP 8. Tested on PHP 8.2 and 8.3.

The one thing to do before going live is change the admin password — see
"Changing the admin password" below.

### What needs what

| Part | Needs |
| --- | --- |
| Every marketing page, the blog listing, the articles | Nothing — plain HTML files |
| Clean URLs (`/blog`, `/blog/<slug>`) and the 404 page | Apache + `mod_rewrite` |
| Adding or editing a blog post | PHP |

If a host has no `mod_rewrite` the site still works — `blog.html` and
`blog/<slug>.html` are real files. Set `PRETTY_URLS` to `false` in
`includes/config.php` so links point straight at them.

On nginx, the `.htaccess` files are ignored: add `location` deny rules for
`includes/`, `data/` and `tools/`, or `config.php` and the post files become
readable over the web.

## How it stays location-independent

Four things, so nothing has to be reconfigured per environment:

- `base_url()` in `includes/config.php` works the site's URL prefix out at
  request time by comparing the project folder with `DOCUMENT_ROOT`. Every
  PHP-rendered link and asset is built on it.
- `.htaccess` deliberately sets no `RewriteBase`, so its rules resolve relative
  to the file's own directory.
- The 404 page is `404.php`, not static HTML. Apache serves the error page's
  body at whatever URL was missing, so a 404 at `/blog/a/b/c` would resolve
  relative asset paths against that deeper path and render completely unstyled.
  PHP computes the correct paths at request time instead. The rule that routes
  missing URLs there tests `DOCUMENT_ROOT` + `REQUEST_URI` rather than
  `REQUEST_FILENAME`, because Apache splits `/about.html/res` into a real file
  plus path-info, which would otherwise skip the rule.
- The static pages only ever use `./relative` links, which stay correct because
  each is served from its own URL.

## Reading the blog — no PHP required

Just open `blog.html`, or put the folder on any web host. Nothing to start.

With Apache (XAMPP) you also get clean addresses, still served from the static
files: `/blog` and `/blog/staff-augmentation-2026`.

## Adding or editing a post — PHP required

1. Start **Apache** in the XAMPP Control Panel (MySQL is not needed).
2. Go to <http://localhost/rekrutify/admin/> and sign in
   (username `admin`, plus the password set in `includes/config.php`).
3. **New post** → fill in the form → **Save**.

Saving writes the post file *and* regenerates `blog.html` and `blog/*.html`.
You can stop Apache afterwards; the blog keeps working.

If you ever edit the JSON files by hand, press **Rebuild pages** on the posts
list — or run `php tools/build.php`. The panel warns you when the generated
pages are older than the post files.
The form fields map one-to-one onto what the page shows:

| Field | Where it appears |
| --- | --- |
| Title | Page banner, browser title, and the card on the blog page |
| URL slug | The address, `post.php?slug=…`. Auto-filled from the title |
| Date | The card and the article byline |
| Position | Order on the blog page — lower first. A new post defaults to 0, so it goes to the top |
| Category / Author | The byline next to the folder and person icons |
| Cover image | The banner image; pick an existing one or upload a new one |
| Short description | The blurb on the card, and the page's meta description |
| Key takeaways | The green "Key Takeaways" box. Leave empty and the box disappears |
| Body | The article itself |
| Further reading | The checklist at the bottom. Leave empty and the section disappears |
| Published | Untick to keep it off the blog (the URL then returns 404) |

### Writing the body

The body is HTML, matching what the existing nine articles use:

```html
<h2>A main section heading</h2>
<p>A paragraph. Use <strong>bold</strong> for emphasis.</p>
<h3>A sub-heading</h3>
<p>A link looks like this:
   <a class="accent-color" href="./pricing.html">our pricing</a>.</p>
```

Keep `class="accent-color"` on links so they pick up the site's green.
Link to another article with `./post.php?slug=<slug>`.

**Further reading** is one entry per line, as `Label | url`:

```
Why US Companies are Hiring Tech Talent in Pakistan | ./post.php?slug=hiring-tech-talent-pakistan
```

## Changing the admin password

```bash
php tools/make-password.php "your new password"
```

Paste the line it prints into `includes/config.php`, replacing the existing
`ADMIN_PASSWORD_HASH` line. Change `ADMIN_USER` there too if you want. The panel
shows a warning banner while the shipped default is still in place.

Two other things worth knowing:

- `includes/`, `data/` and `tools/` each carry an `.htaccess` that blocks direct
  web access. Those work on Apache (including XAMPP). On nginx you must add the
  equivalent `location` deny rules yourself, or `config.php` and the post files
  become readable over the web.
- The body field accepts raw HTML on purpose, so whoever logs into the panel can
  inject scripts into the public site. Only give the login to people you trust.

## Where the content came from

The nine original articles were copied from the live rekrutify.com pages and
verified character-for-character against them. Two links were changed: the live
site's `/extra/security` pages don't exist in this project, so those became `#`,
matching how the footer already handles Terms/Privacy/Security.

## Don't edit blog.html or blog/*.html by hand

They are generated. Anything you type into them is overwritten the next time a
post is saved or the pages are rebuilt. Edit the post in the admin panel (or the
JSON in `data/posts/`) and rebuild instead.
