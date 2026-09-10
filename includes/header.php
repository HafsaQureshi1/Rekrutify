<?php
/* Shared page shell. Extracted verbatim from the static pages so the
   PHP blog renders inside exactly the same navbar/sidebar markup. */
require_once __DIR__ . "/config.php";
$pageTitle = $pageTitle ?? "Rekrutify - Talent Without Borders";
$pageDesc  = $pageDesc  ?? "Rekrutify - Talent Without Borders.";
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= h($pageTitle) ?></title>
<meta name="description" content="<?= h($pageDesc) ?>">
<link rel="icon" href="<?= h(url("assets/images/favicon.ico")) ?>">
<link rel="stylesheet" href="<?= h(url("assets/css/style.css")) ?>">
<link rel="stylesheet" href="<?= h(url("assets/css/fonts.css")) ?>">
<link rel="stylesheet" href="<?= h(url("assets/css/custom.css")) ?>">
<script>
/* Hide the scroll-reveal elements only while JS is actually working, so the
   page can never get stuck with half its sections at opacity 0. The watchdog
   un-hides everything if main.js fails to load or throws before it signals
   ready — a failure that is much easier to hit on a file:// page. */
(function () {
  var el = document.documentElement;
  el.className += ' js';
  setTimeout(function () {
    if (!window.__rekrutifyReady) el.className = el.className.replace(/\bjs\b/, '');
  }, 2000);
  window.addEventListener('error', function (e) {
    if (e.target && e.target.tagName === 'SCRIPT') el.className = el.className.replace(/\bjs\b/, '');
  }, true);
})();
</script>
</head>
<body>

<!-- ============ NAVBAR ============ -->
<div class="navbar-wrapper">
  <nav class="navbar navbar-expand-lg">
    <div class="navbar-container">
      <div class="logo-container">
        <a class="navbar-brand active" href="<?= h(url("index.html")) ?>">
          <div class="site-logo">
            <img alt="Rekrutify" class="img-fluid rekrutify-logo" src="<?= h(url("assets/images/rekrutify_dark.png")) ?>" style="max-height: 40px;">
          </div>
        </a>
      </div>
      <button class="navbar-toggler nav-btn" type="button" aria-label="Toggle navigation">
        <i class="fa-solid fa-bars"></i>
      </button>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav mx-auto">
          <li class="nav-item"><a class="nav-link " href="<?= h(url("index.html")) ?>">Home</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= h(url("about.html")) ?>">About</a></li>
          <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" aria-expanded="false">Services <i class="fa-solid fa-angle-down accent-color"></i></a>
            <ul class="dropdown-menu">
              <li><a class="dropdown-item" href="<?= h(url("serviceoverview.html")) ?>">Services Overview</a></li>
              <li><a class="dropdown-item" href="<?= h(url("medical-billing.html")) ?>">Medical Billing</a></li>
              <li><a class="dropdown-item" href="#services">Customer Support</a></li>
              <li><a class="dropdown-item" href="#services">IT Services</a></li>
              <li><a class="dropdown-item" href="#services">Digital Marketing</a></li>
            </ul>
          </li>
          <li class="nav-item"><a class="nav-link " href="<?= h(url("casestudy.html")) ?>">Case Studies</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= h(url("pricing.html")) ?>">Pricing</a></li>
          <li class="nav-item"><a class="nav-link" href="<?= h(url("contact.html")) ?>">Contact Us</a></li>
        </ul>
      </div>
      <div class="navbar-action-container">
        <div class="navbar-action-button">
          <button id="themeSwitch"><i id="themeIcon" class="fas fa-moon"></i></button>
        </div>
        <a class="btn btn-primary rounded-pill d-flex align-items-center justify-content-center" href="#contact"
           style="padding: 12px 24px; background-color: var(--accent-color); color: var(--accent-color-2); font-weight: 600; text-decoration: none;">Start Free Trial</a>
      </div>
    </div>
  </nav>
</div>

<!-- ============ MOBILE SIDEBAR ============ -->
<div>
  <div class="sidebar-overlay"></div>
  <div class="sidebar">
    <div class="sidebar-header">
      <div class="logo mb-0">
        <img alt="Rekrutify" class="img-fluid rekrutify-logo" src="<?= h(url("assets/images/rekrutify_dark.png")) ?>" style="max-height: 40px;">
      </div>
      <button class="close-btn"><span>X</span></button>
    </div>
    <ul class="menu">
      <li><a href="<?= h(url("index.html")) ?>">Home</a></li>
      <li><a href="#about">About Us</a></li>
      <li class="sidebar-dropdown">
        <div class="dropdown-header">
          <a href="#services">Services</a>
          <button class="sidebar-dropdown-btn"><i class="fa-solid fa-angle-down"></i></button>
        </div>
        <ul class="sidebar-dropdown-menu">
          <li><a href="<?= h(url("serviceoverview.html")) ?>">Services Overview</a></li>
          <li><a href="<?= h(url("medical-billing.html")) ?>">Medical Billing</a></li>
          <li><a href="#services">Customer Support</a></li>
          <li><a href="#services">IT Services</a></li>
          <li><a href="#services">Digital Marketing</a></li>
        </ul>
      </li>
      <li class="below-dropdown"><a href="<?= h(url("casestudy.html")) ?>">Case Studies</a></li>
      <li class="below-dropdown"><a href="<?= h(url("pricing.html")) ?>">Pricing</a></li>
      <li class="below-dropdown"><a href="<?= h(url("contact.html")) ?>">Contact Us</a></li>
    </ul>
  </div>
</div>
