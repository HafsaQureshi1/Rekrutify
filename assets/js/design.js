/* ===========================================================
   Design Services — motion engine.

   Drives the scroll sequence styled in design.css: inertial scrolling
   (Lenis), pinned sections scrubbed by scroll position, word-by-word reading
   reveals, the travelling showcase, the growing card, floating papers,
   drifting words, a short intro and a cursor follower — plus three small
   Three.js stages with chrome pixel objects.

   Everything is progressive:
   - no JavaScript / reduced motion → static page (html never gets .ds-motion)
   - no WebGL or three.js failed → all motion, no 3D objects (.ds-gl absent)
   - Lenis failed → native scrolling, everything else unchanged
   All scroll effects are tied to scroll POSITION, so they run backwards when
   the visitor scrolls up.
   =========================================================== */
(function () {
  'use strict';

  window.__dsnBoot = true;
  var root = document.documentElement;
  var main = document.querySelector('.dsn');
  if (!main) return;

  var motion = root.classList.contains('ds-motion');
  var clamp = function (v, a, b) { return v < a ? a : v > b ? b : v; };
  var q = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

  /* ---------- Reading reveal: wrap every word, keep any inline markup ---------- */
  q('.dsn-read').forEach(function (el) {
    var n = 0;
    var walk = function (node) {
      Array.prototype.slice.call(node.childNodes).forEach(function (child) {
        if (child.nodeType === 3) {
          var parts = child.textContent.split(/(\s+)/);
          var frag = document.createDocumentFragment();
          parts.forEach(function (part) {
            if (!part) return;
            if (/^\s+$/.test(part)) { frag.appendChild(document.createTextNode(part)); return; }
            var w = document.createElement('span');
            w.className = 'dsn-w';
            w.style.setProperty('--i', n++);
            w.textContent = part;
            frag.appendChild(w);
          });
          node.replaceChild(frag, child);
        } else if (child.nodeType === 1) {
          walk(child);
        }
      });
    };
    walk(el);
    el.style.setProperty('--n', n);
  });

  /* Without motion: finished states, nothing else to do. */
  if (!motion) {
    root.classList.add('dsn-ready');
    window.__dsnReady = true;
    return;
  }

  /* ---------- One-shot "in view" classes ---------- */
  q('.dsn-cols .dsn-list').forEach(function (list) {
    q('li', list).forEach(function (li, k) { li.style.setProperty('--k', k); });
  });
  var inTargets = q('.dsn-lines, .dsn-cols');
  if ('IntersectionObserver' in window) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (e) {
        if (e.isIntersecting) { e.target.classList.add('is-in'); io.unobserve(e.target); }
      });
    }, { threshold: 0.2 });
    inTargets.forEach(function (el) { io.observe(el); });
  } else {
    inTargets.forEach(function (el) { el.classList.add('is-in'); });
  }

  /* ---------- Intro: at most ~1.3s, once per visit, skippable ---------- */
  var intro = document.querySelector('.dsn-intro');
  var introShown = intro && root.classList.contains('ds-intro-on') && !root.classList.contains('dsn-seen');
  var ready = function () {
    if (window.__dsnReady) return;
    window.__dsnReady = true;
    root.classList.add('dsn-ready');
  };
  if (introShown) {
    var count = intro.querySelector('.dsn-intro-count b');
    var start = Date.now();
    var DUR = 1100;
    var done = false;
    var finish = function () {
      if (done) return;
      done = true;
      clearInterval(ticker);
      if (count) count.textContent = '100';
      intro.style.setProperty('--load', 1);
      intro.classList.add('is-out');
      try { sessionStorage.setItem('dsnIntro', '1'); } catch (e) { /* private mode */ }
      setTimeout(ready, 350);
      setTimeout(function () { root.classList.add('dsn-seen'); }, 900);
    };
    // A timer rather than rAF so it also finishes in a background tab.
    var ticker = setInterval(function () {
      var t = clamp((Date.now() - start) / DUR, 0, 1);
      var eased = 1 - Math.pow(1 - t, 3);
      if (count) count.textContent = Math.round(eased * 100);
      intro.style.setProperty('--load', eased.toFixed(3));
      if (t >= 1) finish();
    }, 30);
    intro.addEventListener('click', finish);
    window.addEventListener('keydown', finish, { once: true });
  } else {
    ready();
  }

  /* ---------- Smooth scrolling ---------- */
  var lenis = null;
  if (typeof window.Lenis === 'function') {
    try {
      lenis = new window.Lenis({ lerp: 0.09, smoothWheel: true, wheelMultiplier: 1 });
    } catch (e) { lenis = null; }
  }
  // Lenis owns the scroll position, so a plain native scroll (a hash jump, or
  // main.js's scrollIntoView for a discipline row) gets overridden. Route
  // every in-page target through Lenis instead.
  var hashTarget = function () {
    if (location.hash.length < 2) return null;
    try { return document.getElementById(decodeURIComponent(location.hash.slice(1))); } catch (err) { return null; }
  };
  var glideTo = function (target) {
    // Wait a beat so a row main.js has just opened is measured at full height.
    if (lenis && target) setTimeout(function () { lenis.scrollTo(target, { offset: -110 }); }, 60);
  };
  if (lenis) {
    document.addEventListener('click', function (e) {
      var a = e.target.closest && e.target.closest('a[href^="#"]');
      if (!a || a.getAttribute('href').length < 2) return;
      var id;
      try { id = decodeURIComponent(a.getAttribute('href').slice(1)); } catch (err) { return; }
      var target = document.getElementById(id);
      if (!target) return;
      e.preventDefault();
      if (location.hash !== '#' + id) {
        history.pushState(null, '', '#' + id);
        // pushState fires no hashchange; main.js listens for it to open rows.
        window.dispatchEvent(new HashChangeEvent('hashchange'));
      } else {
        glideTo(target);
      }
    });
    window.addEventListener('hashchange', function () { glideTo(hashTarget()); });
    // Arriving with a hash (e.g. design-services.html#brand-identity).
    if (hashTarget()) setTimeout(function () { glideTo(hashTarget()); }, 200);
  }

  /* ---------- Cursor follower (fine pointers only) ---------- */
  var cursor = document.querySelector('.dsn-cursor');
  var fine = window.matchMedia && window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var mouse = { x: -100, y: -100, cx: -100, cy: -100, nx: 0, ny: 0 };
  window.addEventListener('mousemove', function (e) {
    mouse.x = e.clientX;
    mouse.y = e.clientY;
    mouse.nx = e.clientX / window.innerWidth * 2 - 1;
    mouse.ny = e.clientY / window.innerHeight * 2 - 1;
    if (fine && cursor && !root.classList.contains('dsn-cursor-on')) {
      mouse.cx = mouse.x; mouse.cy = mouse.y;
      root.classList.add('dsn-cursor-on');
    }
  }, { passive: true });
  if (fine && cursor) {
    document.addEventListener('mouseover', function (e) {
      var hit = e.target.closest && e.target.closest('a, button, label, .ds-toggle, input, select, textarea');
      cursor.classList.toggle('is-hover', !!hit);
    });
    document.addEventListener('mouseleave', function () { root.classList.remove('dsn-cursor-on'); });
  } else {
    cursor = null;
  }

  /* ---------- Scroll-linked values ---------- */
  var hero = document.querySelector('.dsn-hero');
  var card = document.querySelector('.dsn-card');
  var show = document.querySelector('.dsn-show');
  var slides = show ? q('.dsn-slide', show) : [];
  var showCount = show && show.querySelector('.dsn-show-count b');
  var reads = q('.dsn-read');
  var float = document.querySelector('.dsn-float');
  var team = document.querySelector('.dsn-team');
  var drifts = q('.dsn-drift');
  var N = slides.length;
  var state = { hero: 0, show: 0, slide: 0, slideQ: 0, words: 0 };

  // Only touch the DOM when a value actually changes.
  var cache = new WeakMap();
  var setVar = function (el, name, v) {
    var s = v.toFixed(4);
    var c = cache.get(el);
    if (!c) { c = {}; cache.set(el, c); }
    if (c[name] === s) return;
    c[name] = s;
    el.style.setProperty(name, s);
  };
  var pinProgress = function (el, vh) {
    var r = el.getBoundingClientRect();
    return clamp(-r.top / Math.max(1, r.height - vh), 0, 1);
  };
  var viewProgress = function (r, vh) {
    return clamp((vh - r.top) / (vh + r.height), 0, 1);
  };

  var lastSlide = -1;
  var updateScroll = function () {
    var vh = window.innerHeight;

    if (hero) { state.hero = pinProgress(hero, vh); setVar(hero, '--p', state.hero); }
    if (card) setVar(card, '--p', pinProgress(card, vh));

    if (show && N) {
      var p = pinProgress(show, vh);
      state.show = p;
      setVar(show, '--sp', p);
      var s = p * N;
      var i = Math.min(N - 1, Math.floor(s));
      var local = clamp(s - i, 0, 1);
      state.slide = i;
      state.slideQ = local;
      slides.forEach(function (sl, j) {
        if (j !== i) { setVar(sl, '--v', 0); return; }
        var fadeIn = j === 0 ? 1 : clamp(local / 0.12, 0, 1);
        var fadeOut = j === N - 1 ? 1 : clamp((1 - local) / 0.12, 0, 1);
        setVar(sl, '--q', local);
        setVar(sl, '--v', Math.min(fadeIn, fadeOut));
      });
      if (i !== lastSlide) {
        slides.forEach(function (sl, j) {
          var on = j === i;
          sl.classList.toggle('is-on', on);
          // Hidden slides must not be reachable by keyboard or screen reader.
          sl.inert = !on;
          if (on) sl.removeAttribute('aria-hidden'); else sl.setAttribute('aria-hidden', 'true');
        });
        if (showCount) showCount.textContent = ('0' + (i + 1)).slice(-2);
        lastSlide = i;
      }
    }

    reads.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.bottom < -vh || r.top > vh * 2) return;
      setVar(el, '--t', clamp((vh * 0.88 - r.top) / (r.height + vh * 0.35), 0, 1));
    });

    if (float && team) {
      var tr = team.getBoundingClientRect();
      if (tr.bottom > -vh && tr.top < vh * 2) setVar(float, '--p', viewProgress(tr, vh));
    }

    drifts.forEach(function (el) {
      var r = el.getBoundingClientRect();
      if (r.bottom < -vh || r.top > vh * 2) return;
      var centre = r.top + r.height / 2;
      setVar(el, '--wp', viewProgress(r, vh));
      setVar(el, '--lit', clamp(1 - Math.abs(centre - vh * 0.5) / (vh * 0.32), 0, 1));
    });
    var words = document.querySelector('.dsn-words');
    if (words) state.words = viewProgress(words.getBoundingClientRect(), vh);
  };

  /* ---------- 3D stages ---------- */
  var stages = [];
  var THREE = window.THREE;
  var gl = false;
  if (THREE && THREE.WebGLRenderer) {
    try {
      var probe = document.createElement('canvas');
      gl = !!(probe.getContext('webgl') || probe.getContext('experimental-webgl'));
    } catch (e) { gl = false; }
  }
  if (gl) {
    try { buildStages(); root.classList.add('ds-gl'); } catch (e) { stages = []; root.classList.remove('ds-gl'); }
  }

  function buildStages() {
    var small = window.innerWidth < 768;
    var DPR = Math.min(window.devicePixelRatio || 1, small ? 1.5 : 1.75);

    /* Shared look: a studio "room" of bright panels baked into an environment
       map, so metal reflects something and reads as chrome. */
    var room = new THREE.Scene();
    room.add(new THREE.Mesh(new THREE.SphereGeometry(20, 32, 16), new THREE.MeshBasicMaterial({ color: 0x1a1a1a, side: THREE.BackSide })));
    var panel = function (w, h, color, x, y, z) {
      var m = new THREE.Mesh(new THREE.PlaneGeometry(w, h), new THREE.MeshBasicMaterial({ color: color, side: THREE.DoubleSide }));
      m.position.set(x, y, z);
      m.lookAt(0, 0, 0);
      room.add(m);
    };
    panel(30, 6, 0xffffff, 0, 14, 0);
    panel(8, 16, 0xf2f2f2, -14, 2, 4);
    panel(6, 12, 0x21a37a, 14, 0, -4);
    panel(10, 3, 0xffffff, 4, -2, 14);
    panel(12, 4, 0x9a9a9a, -6, -12, -6);

    // An environment map is tied to the WebGL context that baked it, so each
    // stage bakes its own and gets its own materials.
    var mats;
    var makeMats = function (renderer) {
      var pmrem = new THREE.PMREMGenerator(renderer);
      var envMap = pmrem.fromScene(room, 0.03).texture;
      pmrem.dispose();
      return {
        chrome: new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 1, roughness: 0.14, envMap: envMap }),
        green: new THREE.MeshStandardMaterial({ color: 0x21a37a, metalness: 0.35, roughness: 0.22, envMap: envMap }),
        white: new THREE.MeshStandardMaterial({ color: 0xf4f4f4, metalness: 0, roughness: 0.55, envMap: envMap, envMapIntensity: 0.7 }),
        dark: new THREE.MeshStandardMaterial({ color: 0x151515, metalness: 0.6, roughness: 0.3, envMap: envMap }),
        glass: new THREE.MeshStandardMaterial({ color: 0xffffff, metalness: 0.1, roughness: 0.05, envMap: envMap, transparent: true, opacity: 0.55 })
      };
    };

    /* Pixel shapes, drawn as text. Each "X" becomes one chrome cube. */
    var BITMAPS = {
      cursor: [
        'X.........', 'XX........', 'XXX.......', 'XXXX......', 'XXXXX.....', 'XXXXXX....',
        'XXXXXXX...', 'XXXXXXXX..', 'XXXXX.....', 'XX.XXX....', 'X...XX....', '....XXX...', '.....XX...'
      ],
      sparkle: ['...X...', '...X...', '..XXX..', 'XXXXXXX', '..XXX..', '...X...', '...X...'],
      heart: ['.XX.XX.', 'XXXXXXX', 'XXXXXXX', 'XXXXXXX', '.XXXXX.', '..XXX..', '...X...'],
      pen: ['......XX', '.....XXX', '....XXX.', '...XXX..', '..XXX...', '.XXX....', 'XXX.....', 'XX......'],
      browser: [
        'XXXXXXXXXXXXX', 'X.X.X.......X', 'XXXXXXXXXXXXX', 'X...........X', 'X.XXXXX.XXX.X',
        'X...........X', 'X.XXXXXXXXX.X', 'X.XXXXXXXXX.X', 'X...........X', 'XXXXXXXXXXXXX'
      ]
    };
    var cube = new THREE.BoxGeometry(0.9, 0.9, 0.9);
    var voxel = function (name, material, size) {
      var rows = BITMAPS[name];
      var cells = [];
      rows.forEach(function (row, y) {
        for (var x = 0; x < row.length; x++) if (row[x] === 'X') cells.push([x, y]);
      });
      var mesh = new THREE.InstancedMesh(cube, material, cells.length);
      var m = new THREE.Matrix4();
      var w = rows[0].length, h = rows.length;
      cells.forEach(function (c, k) {
        m.makeTranslation(c[0] - (w - 1) / 2, (h - 1) / 2 - c[1], 0);
        mesh.setMatrixAt(k, m);
      });
      mesh.instanceMatrix.needsUpdate = true;
      var g = new THREE.Group();
      g.add(mesh);
      g.scale.setScalar(size / Math.max(w, h));
      return g;
    };

    var makeStage = function (canvas, section) {
      var renderer = new THREE.WebGLRenderer({ canvas: canvas, antialias: true, alpha: true, powerPreference: 'high-performance' });
      renderer.setPixelRatio(DPR);
      renderer.setClearColor(0x000000, 0);
      renderer.outputEncoding = THREE.sRGBEncoding;
      renderer.toneMapping = THREE.ACESFilmicToneMapping;
      var scene = new THREE.Scene();
      scene.add(new THREE.HemisphereLight(0xffffff, 0x404040, 0.9));
      var sun = new THREE.DirectionalLight(0xffffff, 1.1);
      sun.position.set(4, 8, 6);
      scene.add(sun);
      var camera = new THREE.PerspectiveCamera(35, 1, 0.1, 100);
      camera.position.set(0, 0, 14);
      var stage = { mats: makeMats(renderer), canvas: canvas, section: section, renderer: renderer, scene: scene, camera: camera, w: 0, h: 0, visible: false, update: null };
      stages.push(stage);
      return stage;
    };
    // Half the visible width/height at z = 0, in world units.
    var halfView = function (stage) {
      var h = Math.tan(THREE.MathUtils.degToRad(stage.camera.fov / 2)) * stage.camera.position.z;
      return { x: h * stage.camera.aspect, y: h };
    };

    /* -- Hero: pixel objects hovering over the headline, splitting apart on scroll -- */
    var heroCanvas = document.querySelector('canvas[data-stage="hero"]');
    if (heroCanvas && hero) {
      var hs = makeStage(heroCanvas, hero);
      mats = hs.mats;
      var heroItems = [
        { obj: voxel('cursor', mats.chrome, 2.6), at: [-0.55, 0.28], dir: [-1, 0.6], spin: 0.35 },
        { obj: voxel('sparkle', mats.chrome, 1.7), at: [0.62, 0.42], dir: [1, 0.8], spin: -0.5 },
        { obj: voxel('heart', mats.green, 1.9), at: [0.2, -0.28], dir: [0.7, -1], spin: 0.45 },
        { obj: voxel('pen', mats.chrome, 2.1), at: [-0.12, -0.02], dir: [-0.4, -1], spin: -0.3 },
        { obj: voxel('sparkle', mats.green, 1.0), at: [-0.82, -0.45], dir: [-1, -0.4], spin: 0.7 }
      ];
      if (small) heroItems = heroItems.slice(0, 3);
      heroItems.forEach(function (it, k) {
        it.phase = k * 1.7;
        it.obj.rotation.set(0.3, -0.5 + k * 0.4, 0.1 * k);
        hs.scene.add(it.obj);
      });
      hs.update = function (t) {
        var hv = halfView(hs);
        var p = state.hero;
        var e = p * p * (3 - 2 * p);
        heroItems.forEach(function (it) {
          it.obj.position.set(
            it.at[0] * hv.x + it.dir[0] * e * hv.x * 0.9 + mouse.nx * 0.35,
            it.at[1] * hv.y + it.dir[1] * e * hv.y * 0.9 + Math.sin(t * 0.8 + it.phase) * 0.18 - mouse.ny * 0.25,
            e * 3
          );
          it.obj.rotation.y = -0.5 + t * it.spin * 0.6 + e * 3 * Math.sign(it.spin) + mouse.nx * 0.3;
          it.obj.rotation.x = 0.25 + Math.sin(t * 0.6 + it.phase) * 0.15 + e * 1.5;
        });
      };
    }

    /* -- Showcase: one object per discipline, turning on a white pixel platform -- */
    var showCanvas = document.querySelector('canvas[data-stage="show"]');
    if (showCanvas && show) {
      var ss = makeStage(showCanvas, show);
      mats = ss.mats;
      ss.camera.position.set(0, 2.2, 14);
      ss.camera.lookAt(0, -0.4, 0);

      // Platform: a round patch of cubes with gently varying heights.
      var R = 5;
      var cells = [];
      for (var x = -R; x <= R; x++) for (var z = -R; z <= R; z++) if (x * x + z * z <= R * R + 1) cells.push([x, z]);
      var plat = new THREE.InstancedMesh(new THREE.BoxGeometry(0.46, 0.46, 0.46), mats.white, cells.length);
      var pm = new THREE.Matrix4();
      var placePlatform = function (t) {
        cells.forEach(function (c, k) {
          var d = Math.sqrt(c[0] * c[0] + c[1] * c[1]);
          var y = Math.sin(d * 0.9 - t * 1.4) * 0.08 - d * 0.02;
          pm.makeTranslation(c[0] * 0.5, y, c[1] * 0.5);
          plat.setMatrixAt(k, pm);
        });
        plat.instanceMatrix.needsUpdate = true;
      };
      placePlatform(0);
      var platform = new THREE.Group();
      platform.add(plat);
      platform.scale.setScalar(0.78);
      platform.position.y = -2.4;
      ss.scene.add(platform);

      var objs = [];
      // 0 Interfaces: a fanned stack of screens.
      var ui = new THREE.Group();
      [[mats.dark, -0.5], [mats.white, 0], [mats.green, 0.5]].forEach(function (d, k) {
        var s = new THREE.Mesh(new THREE.BoxGeometry(2.6, 1.8, 0.1), d[0]);
        s.position.set(d[1] * 0.9, d[1] * 0.5, d[1]);
        s.rotation.z = d[1] * 0.25;
        ui.add(s);
        var bar = new THREE.Mesh(new THREE.BoxGeometry(1.6, 0.16, 0.12), k === 1 ? mats.green : mats.chrome);
        bar.position.set(d[1] * 0.9 - 0.3, d[1] * 0.5 + 0.45, d[1] + 0.06);
        bar.rotation.z = d[1] * 0.25;
        ui.add(bar);
      });
      objs.push(ui);
      // 1 Identity: a chrome knot — one continuous mark.
      objs.push(new THREE.Mesh(new THREE.TorusKnotGeometry(1.05, 0.34, 180, 28, 2, 3), mats.chrome));
      // 2 Motion: gyroscope rings, each turning on its own axis.
      var gyro = new THREE.Group();
      [1.6, 1.25, 0.9].forEach(function (r, k) {
        var ring = new THREE.Mesh(new THREE.TorusGeometry(r, 0.09, 20, 90), k === 1 ? mats.green : mats.chrome);
        ring.userData.axis = k;
        gyro.add(ring);
      });
      gyro.add(new THREE.Mesh(new THREE.SphereGeometry(0.42, 32, 16), mats.chrome));
      objs.push(gyro);
      // 3 Dimension: a faceted crystal with a glass shell.
      var crystal = new THREE.Group();
      var core = new THREE.Mesh(new THREE.IcosahedronGeometry(1.2, 0), new THREE.MeshStandardMaterial({ color: 0x21a37a, metalness: 0.5, roughness: 0.18, envMap: mats.chrome.envMap, flatShading: true }));
      crystal.add(core);
      var shell = new THREE.Mesh(new THREE.IcosahedronGeometry(1.7, 1), mats.glass);
      shell.material.wireframe = false;
      crystal.add(shell);
      var wire = new THREE.LineSegments(new THREE.EdgesGeometry(new THREE.IcosahedronGeometry(1.72, 1)), new THREE.LineBasicMaterial({ color: 0x21a37a, transparent: true, opacity: 0.6 }));
      crystal.add(wire);
      objs.push(crystal);
      // 4 Conversion: a pixel browser window.
      objs.push(voxel('browser', mats.chrome, 3.4));

      objs.forEach(function (o) { o.visible = false; ss.scene.add(o); });

      ss.update = function (t) {
        // Portrait screens: pull the camera back and aim lower, so the object
        // sits smaller and higher, clear of the text underneath.
        var a = ss.camera.aspect;
        var z = a < 1 ? 14 + (1 - a) * 16 : 14;
        if (ss.camera.position.z !== z) {
          ss.camera.position.set(0, 2.2, z);
          ss.camera.lookAt(0, a < 1 ? -1.3 : -0.4, 0);
        }
        var i = state.slide, lq = state.slideQ;
        var fadeIn = i === 0 ? 1 : clamp(lq / 0.14, 0, 1);
        var fadeOut = i === N - 1 ? 1 : clamp((1 - lq) / 0.14, 0, 1);
        var v = Math.min(fadeIn, fadeOut);
        v = v * v * (3 - 2 * v);
        objs.forEach(function (o, k) {
          o.visible = k === i && v > 0.01;
          if (!o.visible) return;
          o.scale.setScalar(0.35 + 0.65 * v);
          o.position.set(0, -0.2 + (1 - v) * -1.2 + Math.sin(t * 1.1) * 0.12, 0);
          o.rotation.y = lq * Math.PI * 1.6 + t * 0.25 + mouse.nx * 0.3;
          o.rotation.x = 0.2 + Math.sin(t * 0.7) * 0.1 + mouse.ny * 0.15;
          if (o === gyro) {
            gyro.children.forEach(function (ring) {
              if (ring.userData.axis === 0) ring.rotation.x = t * 0.9 + lq * 4;
              if (ring.userData.axis === 1) ring.rotation.y = t * 1.3 + lq * 5;
              if (ring.userData.axis === 2) ring.rotation.x = -t * 1.7 - lq * 6;
            });
          }
        });
        platform.rotation.y = state.show * Math.PI * 2 + t * 0.1;
        platform.position.y = -2.4 - (1 - v) * 0.3;
        placePlatform(t);
      };
    }

    /* -- Drifting words: small pixel objects crossing with the words -- */
    var wordsCanvas = document.querySelector('canvas[data-stage="words"]');
    var wordsSection = document.querySelector('.dsn-words');
    if (wordsCanvas && wordsSection) {
      var ws = makeStage(wordsCanvas, wordsSection);
      mats = ws.mats;
      var floaters = [
        { obj: voxel('sparkle', mats.chrome, 1.5), y: 0.55, from: -1.2, to: 0.9, spin: 0.8 },
        { obj: voxel('cursor', mats.chrome, 1.9), y: 0.0, from: 1.1, to: -0.7, spin: -0.5 },
        { obj: voxel('heart', mats.green, 1.4), y: -0.55, from: -0.9, to: 0.6, spin: 0.6 }
      ];
      floaters.forEach(function (f) { ws.scene.add(f.obj); });
      ws.update = function (t) {
        var hv = halfView(ws);
        var p = state.words;
        floaters.forEach(function (f, k) {
          f.obj.position.set((f.from + (f.to - f.from) * p) * hv.x, f.y * hv.y + Math.sin(t + k) * 0.15, 0);
          f.obj.rotation.y = t * f.spin + p * 4;
          f.obj.rotation.x = 0.3 + Math.sin(t * 0.5 + k) * 0.2;
        });
      };
    }
  }

  var sizeStages = function () {
    stages.forEach(function (s) {
      var w = s.canvas.clientWidth, h = s.canvas.clientHeight;
      if (!w || !h || (w === s.w && h === s.h)) return;
      s.w = w; s.h = h;
      s.renderer.setSize(w, h, false);
      s.camera.aspect = w / h;
      s.camera.updateProjectionMatrix();
    });
  };

  /* ---------- One loop for everything ---------- */
  var vhNow = window.innerHeight;
  var onResize = function () { vhNow = window.innerHeight; sizeStages(); updateScroll(); };
  window.addEventListener('resize', onResize);
  window.addEventListener('scroll', function () { if (!lenis) updateScroll(); }, { passive: true });
  if (lenis) lenis.on('scroll', updateScroll);

  var t0 = performance.now();
  var frame = function (now) {
    if (lenis) lenis.raf(now);
    updateScroll();

    if (cursor) {
      mouse.cx += (mouse.x - mouse.cx) * 0.2;
      mouse.cy += (mouse.y - mouse.cy) * 0.2;
      cursor.style.transform = 'translate3d(' + mouse.cx.toFixed(1) + 'px,' + mouse.cy.toFixed(1) + 'px,0)';
    }

    var t = (now - t0) / 1000;
    for (var k = 0; k < stages.length; k++) {
      var s = stages[k];
      var r = s.section.getBoundingClientRect();
      // Render only while the stage's section overlaps the screen.
      if (r.bottom < 0 || r.top > vhNow) continue;
      if (!s.w) sizeStages();
      if (s.update) s.update(t);
      s.renderer.render(s.scene, s.camera);
    }
    requestAnimationFrame(frame);
  };

  sizeStages();
  updateScroll();
  requestAnimationFrame(frame);
})();
