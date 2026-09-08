# Rekrutify — static HTML/CSS clone

Pure HTML + CSS + vanilla JS rebuild of the rekrutify.com homepage (the live
site is a React/Vite SPA). No build step, no framework, no npm.

## Run

Open `index.html` directly, or serve the folder:

```bash
python -m http.server 5599
```

## Structure

```
index.html                 full homepage markup
assets/css/style.css       the site's own stylesheet (asset URLs rewritten to this folder)
assets/css/custom.css      replacements for the runtime libraries (see below)
assets/js/main.js          vanilla replacements for Bootstrap JS / Swiper / AOS
assets/images/             all images + SVG icons
assets/webfonts/           Font Awesome
```

Plus Jakarta Sans is loaded from Google Fonts (declared inside `style.css`).

## What replaced the JS libraries

The live site ships Bootstrap JS, Swiper, tsParticles and animate.css. Those are
gone; `custom.css` + `main.js` reproduce the same visible behaviour:

| Live site | Here |
| --- | --- |
| Swiper autoplay loop (partner logos) | CSS keyframe marquee, slides duplicated in JS |
| Swiper (testimonials) | CSS flex track + JS autoplay, same 3-up / 50px gap geometry |
| animate.css + scroll trigger | `.reveal` + IntersectionObserver |
| tsParticles canvases | dropped (decorative only); the hero keeps a CSS scrim |
| Bootstrap collapse/dropdown | plain JS for the navbar dropdown and mobile sidebar |
| React theme switch | `body.lightmode` toggle + logo swap, persisted in `localStorage` |

## Fidelity

Measured against the live site at a 1280px viewport — every section's top
offset, width and height matches, and total document height is identical
(11591px on both).

Internal links point at in-page anchors, since only the homepage was built.
