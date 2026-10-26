# Rekrutify — Handover for the next Claude session

Read this first. It tells you what the project is, how it is built, the rules the
user expects you to follow, and the traps earlier sessions already fell into.

---

## 1. What this project is

A rebuild of **rekrutify.com** (the live site is a React/Vite SPA) as a
**plain HTML + CSS + vanilla JS** site. It has no build step, no framework and no npm.
The **blog** is the one exception. It has a small PHP engine that *generates*
static HTML.

- **Working folder (the only real copy):** `C:\xampp\htdocs\rekrutify`
- **Local URL:** `http://localhost/rekrutify/` (XAMPP Apache. Start Apache in the XAMPP Control Panel. MySQL is not needed.)
- **Stale duplicate, do NOT edit:** `C:\Users\Hafsa\test\rekrutify`. Editing it once caused silent drift between the two copies.
- It is **not** a git repo.
- The user writes in Roman Urdu/English. Reply the same way, short and plain.

### Goal / standard
The user wants a **100% faithful clone** of the live site: the same fonts, colours,
sizes, animations, transitions and scroll behaviour. When they send a **screenshot**,
it means: "clone this page from the live site into this project."

---

## 2. File map

```
index.html                 homepage
about.html, team.html, partner.html, pricing.html, contact.html,
casestudy.html, testimonials.html, faqs.html, serviceoverview.html
medical-billing.html, customer-support.html, it-services.html,
digital-marketing.html     the 4 service pages (copy from client's "services content.docx")
design-services.html       5th service page, shown as "Design & 3D Services" (filename unchanged): custom noomoagency.com-style scroll design (see §6)
terms-of-service.html, privacy-policy.html, security.html   legal pages
404.php                    404 page for Apache (computes paths at request time)
404.html                   GENERATED 404 for static hosts (Vercel), root-absolute links
blog.html, blog/*.html     GENERATED blog listing + 9 articles. Never hand-edit.
blog.php, post.php         PHP-served versions of listing/article (fallback/preview)

assets/css/style.css       the live site's full stylesheet. Nearly every selector already exists here.
assets/css/custom.css      replacements for the runtime libraries (.reveal animations, marquee, etc.)
assets/css/fonts.css       font declarations
assets/css/design.css      design-services.html only. Everything is prefixed .dsn-/ds-
assets/js/main.js          the whole site's JS in one IIFE (navbar, sidebar, theme, reveal, sliders)
assets/js/design.js        design-services.html only (Lenis + Three.js motion engine)
assets/js/vendor/          lenis.min.js, three.min.js (local, no CDN)
assets/images/             42 images/icons. The pool is thin (see §8).
assets/webfonts/           Font Awesome

includes/config.php        admin user + bcrypt password hash, PRETTY_URLS, base_url(), url(), url_prefix(), h()
includes/header.php/footer.php  shell copied verbatim from contact.html
includes/posts.php         post loading/saving (JSON)
includes/render.php        renders templates/ and build_static_site() (writes blog.html, blog/*.html, 404.html)
templates/                 listing.php, article.php, error-404.php, not-found.php
data/posts/<slug>.json     one JSON file per blog post (the source of truth for the blog)
admin/                     session-authenticated panel: login, list, add/edit/delete, image upload, Rebuild button
tools/build.php            CLI rebuild:  C:\xampp\php\php.exe tools/build.php
tools/make-password.php    generates a new admin password hash
tools/router.php           reproduces .htaccess rewrites for `php -S`

.htaccess                  clean blog URLs + 404 routing (no RewriteBase, on purpose)
vercel.json / .vercelignore   static deploy on Vercel (PHP, data and admin are excluded)
README.md, BLOG-README.md  older docs. BLOG-README is accurate for deploy and the admin panel.
```

---

## 3. Conventions you must follow

1. **Get real markup from the live site. Don't eyeball screenshots.**
   - Deep links like `https://rekrutify.com/services/medical-billing` are **broken**. The SPA
     requests its bundle from the wrong path and renders an empty body.
   - Do this instead: open `https://rekrutify.com/`, wait for it to hydrate, then `.click()`
     the in-page link to reach the route, then read the DOM.
2. **Check `style.css` before writing any CSS.** Grep for the selectors first. The live
   stylesheet already covers almost every page.
3. **Animations:** convert the live site's `class="animate__animated ..." style="opacity:0"`
   to the project convention `class="reveal" data-anim="fadeInUp"` (driven by `custom.css` +
   `main.js`). Check each element's real animate class. Some differ: the Terms page banner
   uses `fadeInRight`.
4. **Page-specific JS** goes into `assets/js/main.js` inside the existing IIFE, guarded by an
   element check so it does nothing on other pages. Verify with `node --check assets/js/main.js`.
5. **New page:** splice the head, navbar, sidebar and footer shell out of an existing page. Don't retype it.
   Every page `<head>` has the small inline `js`/watchdog script. Keep it.
6. **Links stay relative** (`./page.html`). Never root-relative (`/page.html`), because the
   site must work in a subfolder and at a domain root. The one intentional exception is the
   generated `404.html`, which is root-absolute for Vercel.
7. **Verify by measuring, not by screenshots.** Compare section top offsets and heights
   with the live page at a **1280px** viewport, after images and fonts have settled. The live
   SPA reports wrong sizes until fonts settle, so re-measure both sides under the same
   conditions before you call it a mismatch.
8. **Don't change things the user didn't ask for.** Report pre-existing issues; don't fix them silently.
9. **Feature lists from the client go into existing pages.** Don't create a new page per point.
10. **Don't build zip files unless the user asks.** Keep client-facing docs plain, must-haves only.
11. Yellow highlighting in the client's content docs marks **SEO keywords**. Do NOT
    reproduce the highlight on the site. The phrase only needs to appear in the text.

---

## 4. Blog system (PHP → static)

- Source of truth: `data/posts/<slug>.json`. Fields include title, slug, date, **order**,
  category, author, image, excerpt, takeaways, body (HTML), further reading, published.
- **Ordering uses `order` ascending, NOT date.** The live listing is not in date order. New
  posts default to 0, so they land on top. Don't reintroduce date sorting.
- Saving or deleting in `admin/` calls `build_static_site()`, which rewrites `blog.html`,
  `blog/<slug>.html` and `404.html`, and deletes pages for removed or unpublished posts.
  Use the "Rebuild pages" button or `tools/build.php` after editing JSON by hand.
- `url()` + `url_prefix()` switch between absolute links (PHP-served) and relative links
  (generated files, so they also work over `file://`). **Templates must never call
  `base_url()` directly.** Doing so breaks the generated pages.
- Admin: `http://localhost/rekrutify/admin/`, user `admin`. The password is the **user's
  own** (bcrypt hash in `includes/config.php`). Don't change it or ask for it. To test the
  logged-in flow, copy the site to a temp dir and put a known hash there. When writing a hash
  into a file, use `preg_replace_callback`, because `$2`/`$10` in the hash get eaten as backreferences.
- `includes/`, `data/` and `tools/` each have an `.htaccess` that denies web access.

### PHP versions
- `php` on PATH = PHP 8.3 (`C:\php83`). XAMPP Apache runs **PHP 8.2.12**.
- Lint and test with `C:\xampp\php\php.exe`.

---

## 5. Server / deploy rules (location-independent)

Nothing in the project hard-codes its install folder. The same files work at
`http://localhost/rekrutify/` and at a domain root. Both layouts were tested.

- `.htaccess`: `DirectorySlash Off` (because `blog/` is a real folder), `/blog` → `blog.html`,
  `/blog/<slug>` → `blog/<slug>.html`, and everything missing → `404.php`.
- The 404 rule tests `%{DOCUMENT_ROOT}%{REQUEST_URI}`, **not** `%{REQUEST_FILENAME}`. Apache
  splits `/about.html/res` into a real file plus path-info, so the `REQUEST_FILENAME` version
  never fires.
- No `RewriteBase`, on purpose.
- **Vercel:** static only. `vercel.json` has the blog rewrites. `.vercelignore` drops
  `admin`, `includes`, `templates`, `tools`, `data`, `*.php`, `*.md` and `.htaccess`, so the
  password hash is never published. Vercel serves the generated root-absolute `404.html`.
- Verifying a 404 or any served page: grepping the HTML text is **not** enough. One 404
  passed the grep while it rendered unstyled. Resolve every `href`/`src` against the request
  URL and fetch each one.

---

## 6. design-services.html (custom page, not a clone)

This is a 5th service page, modelled on the **noomoagency.com** scroll sequence. It has a
branded intro, a giant headline with 3D chrome pixel objects, an all-caps manifesto, a pinned
showcase with travelling words, a card that grows edge to edge, floating paper cards, a
hairline table, a chip-based brief form and an FAQ.

- Only `design.css` and `design.js` touch it. Everything is prefixed `.dsn-` / `ds-`, and the
  colours use the site's tokens, so it follows the light/dark switch.
- Progressive enhancement via classes on `<html>`:
  - `ds-motion`: motion allowed (set by the inline head script, removed after 3s if design.js never boots)
  - `ds-gl`: WebGL available, so the Three.js canvases show
  - `dsn-ready`: the intro has finished
  - With no JS or with reduced motion, it is a fully static, readable page.
- The intro plays once per session (`sessionStorage` key `dsnIntro`).
- All scroll effects are tied to scroll **position**, so they reverse when the visitor scrolls up.
- Lenis and Three.js are vendored locally in `assets/js/vendor/`.

---

## 7. Status: what exists / what doesn't

**Done:** homepage, about, team, partner, pricing, contact, case study, testimonials, FAQs,
service overview, 4 service pages (medical billing, customer support, IT, digital marketing,
all with client copy and their own hero and image trio), design services, terms, privacy
policy, security, 404, the blog (9 articles copied character-for-character from live) and the admin panel.

**Known placeholders / quirks (left alone deliberately):**
- Many `href="#"` placeholders remain (47 on the homepage), e.g. social links and things with no target page.
- The `section-banner` markup differs between pages (`contact.html` uses the
  `.digital-process-banner` variant, 381px; others use `.banner-layout-wrapper.banner-inner`, 384px).
- `links.txt` is an empty file.
- The homepage measures ~11585px tall at 1280px once settled. The README's 11591px figure is an older, less-settled reading, not a regression.
- `blog.php` / `post.php` still exist, but public links point at the generated static pages.

---

## 8. Images

- Only about **6 genuinely distinct landscape photos** exist. `who we are.webp`,
  `who-we-are.webp` and `who-we-are-2.webp` are byte-identical copies of `young-business-team-working-EH8KQ2R`.
- The `blog-*.png` files are 1024×1024 infographics with baked-in text. They can't be used as wide heroes.
- Truly unique imagery needs new photos from the client.

---

## 9. Tooling gotchas (in-app Browser pane)

- The pane sometimes stops rendering: animations freeze, IntersectionObserver goes dead,
  scrolling does nothing, and screenshots come back black. When that happens every `.reveal`
  element sits at opacity 0, even on an untouched `index.html`. That looks like a broken page
  but isn't one. Confirm with a throwaway IO probe, then open a fresh tab.
  Geometry measurements stay reliable in that state.
- Screenshots after scrolling can come back blank. To capture a lower section, hide the
  sections above it instead of scrolling.
- Swiper on the live site does not re-initialise on a client-side route change. Measure
  slider geometry on the live **homepage**.

---

## 10. Quick checklist for any change

1. Edit only in `C:\xampp\htdocs\rekrutify`.
2. Grep `style.css` before adding CSS.
3. `node --check assets/js/main.js` (and `design.js` if you touched it).
4. For PHP: `C:\xampp\php\php.exe -l <file>`, and rebuild the blog if templates or posts changed.
5. Open `http://localhost/rekrutify/<page>` and measure against live at 1280px.
6. Keep links relative. Never put the install path in any file.
7. Tell the user what changed and what you deliberately didn't touch.

---

## 11. Changes made 2026-10-26 (client round)

- **Prices removed** from the pricing cards (index, pricing, serviceoverview). Two blog posts still mention $6/hour in their text. That was left alone on purpose.
- **Pricing popup:** every button with `data-plan="..."` opens an enquiry popup that main.js builds (`.plan-modal`, styles in custom.css). It posts nowhere, like the contact form. The href stays `./contact.html` as the no-JS fallback.
- **Trial is now 1 week** everywhere (it was 2 weeks).
- **Pakistan / PK removed** everywhere. The article slug is now `hiring-offshore-tech-talent` and it uses a neutral photo, because the old image had Pakistan text baked in.
- **"Design Services" is now "Design & 3D Services"** in all visible text. The URL is still `design-services.html`.
- **Calendly:** links carry `data-calendly`. The real URL goes in ONE place: `CALENDLY_URL` in `assets/js/main.js`. It is set to `https://calendly.com/rekrutifyllc/30min`. Used on the contact page, the "Book a Free Consultation" links and inside the pricing popup.
- **Home hero play button removed.** A demo video section (`.section-demo-video`) now sits between the hero and the partners section. It currently shows a stock team-at-work clip (YouTube id `jTpwuXwcSuc`, autoplay muted loop). The client rejected the earlier explainer video.
- Local preview without XAMPP: `C:\xampp\php\php.exe -S 127.0.0.1:8960 -t . tools/router.php`
- **Spacing + type scale** (end of custom.css): wider side gutters on desktop (`--site-gutter`), `.hero-container` capped at 1180px, and h1–h5 scaled down with clamp(). The client found the original clone cramped against the edges and oversized.
- **Alignment + compact layout (same round):** every content section shares one left/right edge (`--site-pad`, centred to `--site-width` 1180px). The **header, hero and footer stay full width**, as in the original; the client asked for that explicitly. A "compact desktop" block in custom.css trims paddings and gaps so most sections fit one 1366×768 screen. (Services cards were briefly 3 per row; reverted to the original 2 per row at the client's request.) The pricing popup is smaller (max 500px).
- **Later in the same round:** the header and the **home hero are back to the original exactly**: 100px h1, 120px padding, original gaps and button sizes. Compact rules skip them via `body > :not(.section-banner):not(.navbar-wrapper)`. Section width is back to the original container (1280px, 1360px from 1400px up). Heading scale is fixed px per breakpoint: h2 28/34/40, h3 22/26/28, h4 18/20/21, h5 17/18/19, h6 16/16/17.
- **Premium spacing pass (same day):** benchmarked Toptal, Deel, Turing, Andela and Remote at 1440px. They use ~1200px content, 80–120px side space, 80–120px section padding, h2 44–54px and body copy 16–20px. Applied: `--site-width` 1200px, gutter clamp(20px, 5vw, 80px), `.section` padding 96px (112px from 1400px), h2 44px (≥1200), h3 24px, body `p` 17px/1.65. The header, hero and footer are still untouched.
- **Service cards are original:** 2 per row, with the original title (28px), text (18px), icon box and button. Only the card width follows the 1200px container. Overrides are at the end of custom.css ("Service cards: keep the original card exactly").
- **Side space reduced a little (client):** `--site-width` 1280px, gutter clamp(20px, 4vw, 64px). Result: 55px at 1366, 72px at 1440. `.section.p-0` (case studies) now also gets the side padding, so it lines up with the rest.
- **Hiring-process card:** `.digital-process-banner` is also a `.section`, so it had been inheriting page-section padding (112px). It now has card padding of 64/64/72px, a 48px gap above the steps, and 36/28px inside the steps box.
- **Case studies keep their original height (client):** the cards are back to the original 400px min-height and 30px padding, and the outer card uses 50px top/bottom padding and a 50px gap. Only the side padding is tighter (32px). The heading follows the site's h2 scale.
- **Inner-page banner headings are original:** `.section-banner h2` uses the original sizes (64px desktop, 56px ≤991, 40px ≤767, 30px ≤480, 25px ≤320), overriding the smaller h2 scale used everywhere else.
- **Animations now match the live site exactly.** The live site's AnimateOnScroll props were read from the React fibers on all 19 live routes and applied element by element (`data-anim` = fadeInUp, fadeInDown, fadeInLeft or fadeInRight; `data-speed` = fast .75s or slow 2s, normal is 1.25s; `data-delay` in ms). Keyframes travel 100% of the element, like animate.css. The IntersectionObserver uses threshold 0.15 and fires once. `html, body { overflow-x: clip }` stops side-entering elements from widening the page (clip, not hidden, so the sticky sections on design-services keep working). The blog cards get a 120ms stagger from templates/listing.php.
- **Testing animations:** the in-app browser pane often stops rendering (no requestAnimationFrame, IO dead), and headless Chrome with `--virtual-time-budget` never fires IO. Drive real-time headless Chrome over CDP instead (Node 22+ has a built-in WebSocket).
- **Home video section (`.section-demo-video`)** is now a premium card: muted looping stock clip as a cropped background, gradient shade, one centred heading with a green gradient accent, a gradient hairline frame, viewfinder corners and a slow Ken Burns drift. The client asked for the buttons, sub-heading, paragraph and chips to be removed: keep the text minimal. Known issue: YouTube sometimes flashes its own play/pause overlay, which can't be hidden inside the iframe. A self-hosted MP4 would fix it.
- **Review photos removed (client):** the avatar stack beside "2.7k Positive Reviews" and the reviewer photos in every testimonial card are gone from all 10 pages that have the testimonial block. The summary is now five stars plus "2.7k Positive Reviews" on one line (CSS at the end of custom.css). The team-*.webp files are still used by the team section, so they stay.
- **Responsive fixes:** the header below 992px is now one 63–67px line (logo | theme | CTA | round menu button), and the CTA is hidden under 576px. Between 992 and 1199px the links are tighter and the CTA doesn't wrap, so the bar is 68px instead of 120px. 1200px and up is untouched. An audit of 16 pages at 375, 768 and 1024px found no page-level horizontal scroll and the menu opening on every page. Known leftovers: Bootstrap rows overhang the 16px phone gutter by about 9px (invisible), and design-services' decorative drift words are clipped on purpose.
- **Stale copy warning:** `C:\Users\Hafsa\test\rekrutify` is OLD (it still has the review photos and everything else that has since been removed). The client once reviewed that copy by mistake. Always point them to `C:\xampp\htdocs\rekrutify` or the latest zip.
- **Mobile menu:** a "Start Free Trial" button (`li.sidebar-cta`) was added at the bottom of the sidebar on all 18 static pages and in includes/header.php (so the blog and 404 have it too), because the phone navbar hides that CTA. The sidebar's z-index is raised above the fixed navbar (1100 vs 1050); before that, the navbar covered the menu's own logo and X button.
