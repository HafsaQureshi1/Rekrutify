/* Rekrutify static clone — vanilla replacements for the React/Bootstrap runtime. */
(function () {
  'use strict';

  // Tells the watchdog in index.html that the script parsed and is running.
  window.__rekrutifyReady = true;

  // If anything below throws, reveal every section rather than leaving the
  // page half-blank.
  window.addEventListener('error', function () {
    var el = document.documentElement;
    el.className = el.className.replace(/\bjs\b/, '');
    var hidden = document.querySelectorAll('.reveal');
    for (var i = 0; i < hidden.length; i++) hidden[i].classList.add('is-visible');
  });

  /* ---------- Hero video ----------
     A YouTube embed refuses to load (and cannot autoplay) from a file://
     page, which leaves the player's error UI sitting on top of the hero.
     Drop it there; the CSS scrim keeps the hero looking right. */
  if (location.protocol === 'file:') {
    var heroVideo = document.getElementById('banner-video-background');
    if (heroVideo) heroVideo.remove();
  }

  /* ---------- Navbar shrink on scroll ---------- */
  var navWrapper = document.querySelector('.navbar-wrapper');
  function onScroll() {
    if (!navWrapper) return;
    navWrapper.classList.toggle('scrolled', window.scrollY > 40);
  }
  window.addEventListener('scroll', onScroll, { passive: true });
  onScroll();

  /* ---------- Mobile sidebar ---------- */
  var sidebar = document.querySelector('.sidebar');
  var overlay = document.querySelector('.sidebar-overlay');
  var openBtn = document.querySelector('.nav-btn');
  var closeBtn = document.querySelector('.close-btn');

  function setSidebar(open) {
    if (!sidebar) return;
    sidebar.classList.toggle('active', open);
    if (overlay) overlay.classList.toggle('active', open);
    document.body.style.overflow = open ? 'hidden' : '';
  }
  if (openBtn) openBtn.addEventListener('click', function () { setSidebar(true); });
  if (closeBtn) closeBtn.addEventListener('click', function () { setSidebar(false); });
  if (overlay) overlay.addEventListener('click', function () { setSidebar(false); });
  document.querySelectorAll('.sidebar .menu a').forEach(function (a) {
    a.addEventListener('click', function () { setSidebar(false); });
  });

  /* ---------- Sidebar sub-menu ---------- */
  var sbToggle = document.querySelector('.sidebar-dropdown-btn');
  if (sbToggle) {
    sbToggle.addEventListener('click', function () {
      var menu = document.querySelector('.sidebar-dropdown-menu');
      if (!menu) return;
      var open = menu.classList.toggle('active');
      sbToggle.style.transform = open ? 'rotate(180deg)' : '';
    });
  }

  /* ---------- Desktop dropdown ---------- */
  document.querySelectorAll('.nav-item.dropdown').forEach(function (item) {
    var toggle = item.querySelector('.dropdown-toggle');
    var menu = item.querySelector('.dropdown-menu');
    if (!toggle || !menu) return;
    function open(state) {
      menu.classList.toggle('show', state);
      toggle.classList.toggle('show', state);
      toggle.setAttribute('aria-expanded', String(state));
    }
    toggle.addEventListener('click', function (e) {
      e.preventDefault();
      open(!menu.classList.contains('show'));
    });
    item.addEventListener('mouseenter', function () { open(true); });
    item.addEventListener('mouseleave', function () { open(false); });
    document.addEventListener('click', function (e) {
      if (!item.contains(e.target)) open(false);
    });
  });

  /* ---------- Scroll reveal ---------- */
  var reveals = document.querySelectorAll('.reveal');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        entry.target.classList.add('is-visible');
        io.unobserve(entry.target);
      });
    }, { threshold: 0.15, rootMargin: '0px 0px -60px 0px' });
    reveals.forEach(function (el) { io.observe(el); });
  } else {
    reveals.forEach(function (el) { el.classList.add('is-visible'); });
  }

  /* ---------- Partner marquee: duplicate slides for a seamless loop ---------- */
  var marquee = document.querySelector('.swiperPartner .swiper-wrapper.marquee');
  if (marquee) {
    marquee.innerHTML += marquee.innerHTML;
  }

  /* ---------- Counters ---------- */
  function runCounter(el) {
    var target = parseInt(el.getAttribute('data-target'), 10) || 0;
    var duration = 1600;
    var start = null;
    function step(ts) {
      if (start === null) start = ts;
      var progress = Math.min((ts - start) / duration, 1);
      el.textContent = String(Math.floor(progress * target));
      if (progress < 1) requestAnimationFrame(step);
    }
    requestAnimationFrame(step);
  }
  var counters = document.querySelectorAll('.counter');
  if ('IntersectionObserver' in window) {
    var cio = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (!entry.isIntersecting) return;
        runCounter(entry.target);
        cio.unobserve(entry.target);
      });
    }, { threshold: 0.5 });
    counters.forEach(function (el) { cio.observe(el); });
  } else {
    counters.forEach(runCounter);
  }

  /* ---------- Testimonial slider ---------- */
  var tSwiper = document.querySelector('.swiperTestimonial');
  if (tSwiper) {
    var track = tSwiper.querySelector('.swiper-wrapper');
    var slides = track.querySelectorAll('.swiper-slide');
    var index = 0;

    function perView() {
      if (window.innerWidth <= 767) return 1;
      if (window.innerWidth <= 991) return 2;
      return 3;
    }
    function maxIndex() { return Math.max(0, slides.length - perView()); }
    function render() {
      index = Math.min(index, maxIndex());
      var offset = slides[index].offsetLeft - slides[0].offsetLeft;
      track.style.transform = 'translate3d(' + (-offset) + 'px, 0, 0)';
    }
    function go(delta) {
      var max = maxIndex();
      index += delta;
      if (index > max) index = 0;
      if (index < 0) index = max;
      render();
    }

    // The live site autoplays without visible arrows; keyboard/pointer users
    // can still drag-free navigate via the autoplay loop.
    var timer = setInterval(function () { go(1); }, 5000);
    tSwiper.addEventListener('mouseenter', function () { clearInterval(timer); });

    window.addEventListener('resize', render);
    render();
  }

  /* ---------- Theme switch (body.lightmode, matching the live site) ---------- */
  var themeSwitch = document.getElementById('themeSwitch');
  var themeIcon = document.getElementById('themeIcon');

  /* Work the images folder out from the stylesheet that is already loaded,
     rather than hard-coding "./assets/...". The static pages sit at the site
     root, but the PHP blog also serves /blog/<slug>, where a relative path
     would resolve one level too deep and blank out the logo. */
  var ASSET_IMAGES = (function () {
    var link = document.querySelector('link[rel="stylesheet"][href*="assets/css/style.css"]');
    var href = link ? link.getAttribute('href') : './assets/css/style.css';
    return href.replace(/assets\/css\/style\.css.*$/, 'assets/images/');
  })();

  function applyTheme(light) {
    document.body.classList.toggle('lightmode', light);
    if (themeIcon) themeIcon.className = light ? 'fas fa-sun' : 'fas fa-moon';
    document.querySelectorAll('img.rekrutify-logo').forEach(function (img) {
      img.setAttribute('src', ASSET_IMAGES + (light ? 'rekrutify_light.png' : 'rekrutify_dark.png'));
    });
    try {
      if (light) localStorage.setItem('lightmode', 'active');
      else localStorage.removeItem('lightmode');
    } catch (e) { /* storage unavailable */ }
  }

  var stored = null;
  try { stored = localStorage.getItem('lightmode'); } catch (e) { /* ignore */ }
  applyTheme(stored === 'active');

  if (themeSwitch) {
    themeSwitch.addEventListener('click', function () {
      applyTheme(!document.body.classList.contains('lightmode'));
    });
  }

  /* ---------- Newsletter form ---------- */
  var form = document.getElementById('newsletterForm');
  if (form) {
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      var input = document.getElementById('newsletter-email');
      if (!input || !input.value || !input.checkValidity()) {
        input && input.focus();
        return;
      }
      input.value = '';
      input.placeholder = 'Thanks — you are subscribed!';
    });
  }

  /* ---------- Smooth anchor scrolling ---------- */
  document.querySelectorAll('a[href^="#"]').forEach(function (a) {
    a.addEventListener('click', function (e) {
      var id = a.getAttribute('href');
      if (!id || id === '#') return;
      var target = document.querySelector(id);
      if (!target) return;
      e.preventDefault();
      window.scrollTo({ top: target.getBoundingClientRect().top + window.scrollY - 90, behavior: 'smooth' });
    });
  });

  /* ---------- Contact form (contact.html) ----------
     The live site posts nowhere: it validates the email client-side, shows a
     banner for 3 seconds, then resets. No-ops on pages without the form. */
  var contactForm = document.getElementById('contactForm');
  if (contactForm) {
    var okAlert = document.getElementById('form-success-message');
    var badAlert = document.getElementById('form-error-message');
    var emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
    var resetTimer;

    contactForm.addEventListener('submit', function (e) {
      e.preventDefault();
      var emailField = contactForm.querySelector('#email');
      clearTimeout(resetTimer);

      if (!emailField || !emailPattern.test(emailField.value)) {
        if (badAlert) badAlert.classList.remove('hidden');
        if (okAlert) okAlert.classList.add('hidden');
        resetTimer = setTimeout(function () {
          if (badAlert) badAlert.classList.add('hidden');
        }, 3000);
        return;
      }

      if (okAlert) okAlert.classList.remove('hidden');
      if (badAlert) badAlert.classList.add('hidden');
      contactForm.reset();
      resetTimer = setTimeout(function () {
        if (okAlert) okAlert.classList.add('hidden');
      }, 3000);
    });
  }

  /* ---------- FAQ accordion (replaces Bootstrap's Collapse plugin) ----------
     style.css already ships Bootstrap's accordion rules, including the
     `.collapsing { height: 0; transition: height .35s ease }` step and the
     +/- icon swap driven by `.accordion-button.collapsed`. This reproduces
     the plugin's show/hide sequence so those rules fire unchanged, and
     honours `data-bs-parent` so opening one panel closes its siblings. */
  var collapseToggles = document.querySelectorAll('[data-bs-toggle="collapse"][data-bs-target]');

  if (collapseToggles.length) {
    var setButton = function (panel, expanded) {
      var btn = document.querySelector('[data-bs-target="#' + panel.id + '"]');
      if (!btn) return;
      btn.classList.toggle('collapsed', !expanded);
      btn.setAttribute('aria-expanded', String(expanded));
    };

    var show = function (panel) {
      if (panel.classList.contains('collapsing') || panel.classList.contains('show')) return;
      panel.classList.remove('collapse');
      panel.classList.add('collapsing');
      panel.style.height = '0px';
      setButton(panel, true);
      // Force a reflow so the height transition has a start value.
      void panel.offsetHeight;
      panel.style.height = panel.scrollHeight + 'px';

      var done = function () {
        panel.removeEventListener('transitionend', done);
        panel.classList.remove('collapsing');
        panel.classList.add('collapse', 'show');
        panel.style.height = '';
      };
      panel.addEventListener('transitionend', done);
    };

    var hide = function (panel) {
      if (panel.classList.contains('collapsing') || !panel.classList.contains('show')) return;
      panel.style.height = panel.getBoundingClientRect().height + 'px';
      void panel.offsetHeight;
      panel.classList.remove('collapse', 'show');
      panel.classList.add('collapsing');
      panel.style.height = '0px';
      setButton(panel, false);

      var done = function () {
        panel.removeEventListener('transitionend', done);
        panel.classList.remove('collapsing');
        panel.classList.add('collapse');
        panel.style.height = '';
      };
      panel.addEventListener('transitionend', done);
    };

    collapseToggles.forEach(function (toggle) {
      toggle.addEventListener('click', function (e) {
        e.preventDefault();
        var panel = document.querySelector(toggle.getAttribute('data-bs-target'));
        if (!panel) return;

        if (panel.classList.contains('show')) {
          hide(panel);
          return;
        }

        // data-bs-parent makes the group exclusive, as Bootstrap does.
        var parentSel = panel.getAttribute('data-bs-parent');
        if (parentSel) {
          var parent = document.querySelector(parentSel);
          if (parent) {
            parent.querySelectorAll('.collapse.show').forEach(function (open) {
              if (open !== panel && open.getAttribute('data-bs-parent') === parentSel) hide(open);
            });
          }
        }
        show(panel);
      });
    });
  }

  /* ---------- Design Services: discipline index ----------
     The rows start OPEN in the HTML so the page reads fine without JavaScript.
     Here they collapse to just the first one — or to whichever row the URL
     hash names (e.g. design-services.html#brand-identity) so a discipline can
     be linked to directly. Several can be open at once, for comparing. */
  var dsItems = document.querySelectorAll('.ds-item');
  if (dsItems.length) {
    var dsSetOpen = function (item, open) {
      var btn = item.querySelector('.ds-toggle');
      var panel = item.querySelector('.ds-panel');
      item.classList.toggle('is-open', open);
      if (btn) btn.setAttribute('aria-expanded', String(open));
      // inert takes a closed panel's links out of the tab order and the
      // accessibility tree immediately. The CSS visibility transition does the
      // same visually, but it cannot be relied on for that: when the transition
      // does not run, a collapsed panel stayed focusable at zero height.
      if (panel) panel.inert = !open;
    };

    // The row a URL hash names, if any. getElementById rather than
    // querySelector: ids like "3d-illustration" start with a digit, which is
    // valid HTML but an invalid CSS selector. decodeURIComponent throws on a
    // malformed "%" sequence, so a bad hash just means "no target".
    var dsFromHash = function () {
      if (location.hash.length < 2) return null;
      var id;
      try { id = decodeURIComponent(location.hash.slice(1)); } catch (e) { return null; }
      var el = document.getElementById(id);
      return el && el.classList.contains('ds-item') ? el : null;
    };

    var dsTarget = dsFromHash();

    dsItems.forEach(function (item, i) {
      dsSetOpen(item, dsTarget ? item === dsTarget : i === 0);
      var btn = item.querySelector('.ds-toggle');
      if (btn) {
        btn.addEventListener('click', function () {
          dsSetOpen(item, !item.classList.contains('is-open'));
        });
      }
    });

    if (dsTarget) {
      setTimeout(function () {
        dsTarget.scrollIntoView({ block: 'start', behavior: 'smooth' });
      }, 150);
    }

    // A link to #some-row from elsewhere on the same page changes only the
    // hash — no reload, so the code above never re-runs. Open that row too.
    // Other open rows are left alone, so comparing still works.
    window.addEventListener('hashchange', function () {
      var target = dsFromHash();
      if (!target) return;
      dsSetOpen(target, true);
      target.scrollIntoView({ block: 'start', behavior: 'smooth' });
    });
  }

  /* ---------- Design Services: brief builder summary ----------
     Echoes the current selection under the Continue button, so it is clear
     what will be carried into the contact form. */
  var dsBrief = document.querySelector('.ds-brief-form');
  var dsSummary = document.getElementById('dsBriefSummary');
  if (dsBrief && dsSummary) {
    var DS_HOURS = { '20': 'Part-time', '40': 'Full-time', flexible: 'Hours flexible' };
    var DS_START = { asap: 'ASAP', month: 'Within a month', exploring: 'Just exploring' };

    var dsRefresh = function () {
      var picked = [].map.call(dsBrief.querySelectorAll('input[name="discipline"]:checked'), function (el) {
        return el.value;
      });
      var hours = dsBrief.querySelector('input[name="hours"]:checked');
      var start = dsBrief.querySelector('input[name="start"]:checked');
      var parts = [];
      if (picked.length) parts.push(picked.join(' + '));
      if (hours) parts.push(DS_HOURS[hours.value]);
      if (start) parts.push(DS_START[start.value]);
      dsSummary.textContent = parts.join(' · ');
    };

    dsBrief.addEventListener('change', dsRefresh);
    dsRefresh();
  }

  /* ---------- Contact form: pre-fill from a Design Services link ----------
     The brief builder and the "Hire a … designer" links send their choices
     as URL parameters. Only known values are accepted, and only empty fields
     are filled, so nothing the visitor has typed is ever overwritten. */
  var ctForm = document.getElementById('contactForm');
  if (ctForm && location.search && 'URLSearchParams' in window) {
    var q = new URLSearchParams(location.search);
    var DISCIPLINES = ['UI/UX Design', 'Web Design', 'Brand Identity', 'Graphic Design',
      'Motion Graphics', '3D & Illustration', 'Product Design', 'Presentation Design'];
    var HOURS_TEXT = { '20': '20 hours/week', '40': '40 hours/week', flexible: 'Flexible' };
    var START_TEXT = { asap: 'As soon as possible', month: 'Within a month', exploring: 'Just exploring' };

    var wanted = q.getAll('discipline').filter(function (d) { return DISCIPLINES.indexOf(d) !== -1; });
    var hoursKey = q.get('hours');
    var startKey = q.get('start');
    var isDesign = q.get('service') === 'design' || wanted.length > 0;

    if (isDesign) {
      var role = document.getElementById('role');
      if (role && !role.value && role.querySelector('option[value="Design"]')) role.value = 'Design';

      var hoursField = document.getElementById('expected-hours');
      if (hoursField && !hoursField.value && HOURS_TEXT[hoursKey]) hoursField.value = HOURS_TEXT[hoursKey];

      var msg = document.getElementById('message');
      if (msg && !msg.value) {
        var lines = ['I\'d like to hire a designer.'];
        if (wanted.length) lines.push('Disciplines: ' + wanted.join(', '));
        if (HOURS_TEXT[hoursKey]) lines.push('Hours: ' + HOURS_TEXT[hoursKey]);
        if (START_TEXT[startKey]) lines.push('Start: ' + START_TEXT[startKey]);
        msg.value = lines.join('\n');
      }
    }
  }
})();
