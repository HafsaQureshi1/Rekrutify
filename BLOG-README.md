# Rekrutify blog — static pages, PHP admin

**The public blog is plain HTML and needs no PHP.** `blog.html` and
`blog/<slug>.html` are real files on disk, so the blog works on any static host,
and by opening the files directly. Visitors never touch PHP.

**PHP is only needed to change content.** When you save a post in the admin
panel it rewrites those HTML files for you, so the public site is immediately up
to date — then you can stop PHP again.

```
blog.html                generated listing        <- what visitors see
blog/<slug>.html         generated articles       <- what visitors see
data/posts/<slug>.json   your content             <- the source of truth
templates/               the markup both use
includes/                config, data layer, page shell, the builder
blog.php, post.php       live PHP preview of the same templates (optional)
admin/                   the panel: login, list, add/edit/delete, rebuild
tools/build.php          rebuilds the static pages from the command line
tools/make-password.php  generates a password hash for config.php
tools/router.php         only for `php -S`; Apache uses .htaccess instead
```

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

## URLs

Articles use `/blog/<slug>`, matching the live site. That comes from the
`.htaccess` in this folder plus Apache's `mod_rewrite`, which your XAMPP already
has enabled (`AllowOverride All` is set for `htdocs`).

If you ever move to a host without `mod_rewrite`, set `PRETTY_URLS` to `false`
in `includes/config.php`. The site then links to `post.php?slug=...` instead and
keeps working — both forms are always accepted, so old links never break.

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
