<!-- ============ PAGE BANNER ============ -->
<div class="section-banner">
  <div class="banner-layout-wrapper banner-inner">
    <div class="banner-layout">
      <div class="d-flex flex-column text-center align-items-center gspace-2">
        <h2 class="title-heading reveal" data-anim="fadeInUp"><?= h($post['title']) ?></h2>
        <nav class="breadcrumb">
          <a href="<?= h(url("index.html")) ?>" class="gspace-2">Home</a><span class="separator-link">/</span><p class="current-page">Blog</p>
        </nav>
      </div>
      <div class="spacer"></div>
    </div>
  </div>
</div>

<!-- ============ ARTICLE ============ -->
<div class="section">
  <div class="hero-container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="d-flex flex-column gspace-2">

          <div class="d-flex flex-row align-items-center gspace-2">
            <div class="d-flex flex-row gspace-1 align-items-center"><i class="fa-solid fa-calendar accent-color"></i><span class="meta-data-post"><?= h($post['date']) ?></span></div>
            <div class="d-flex flex-row gspace-1 align-items-center"><i class="fa-solid fa-folder accent-color"></i><span class="meta-data-post"><?= h($post['category']) ?></span></div>
            <div class="d-flex flex-row gspace-1 align-items-center"><i class="fa-solid fa-user accent-color"></i><span class="meta-data"><?= h($post['author']) ?></span></div>
          </div>

          <div class="underline-muted-full"></div>

          <?php if ($post['image'] !== ''): ?>
          <div>
            <div class="post-image mb-3">
              <img alt="<?= h($post['image_alt']) ?>" class="img-fluid" src="<?= h(post_image_url($post['image'])) ?>">
            </div>
          </div>
          <?php endif; ?>

          <?php if ($post['takeaways']): ?>
          <div class="key-takeaways">
            <h4><i class="fa-solid fa-lightbulb"></i> Key Takeaways</h4>
            <ul>
              <?php foreach ($post['takeaways'] as $point): ?>
              <li><?= $point /* admin-authored HTML, intentionally raw */ ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <?php endif; ?>

          <?= rebase_body_links($post["body"]) /* admin-authored HTML, intentionally raw */ ?>

          <?php if ($post['further_reading']): ?>
          <div class="underline-muted-full mt-3"></div>
          <h5>Further Reading</h5>
          <ul class="check-list">
            <?php foreach ($post['further_reading'] as $link): ?>
            <li><a href="<?= h(rebase_link($link["href"])) ?>"><?= h($link["label"]) ?></a></li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- ============ NEWSLETTER ============ -->
<div class="section">
  <div class="hero-container">
    <div class="newsletter-wrapper">
      <div class="newsletter-layout">
        <div class="spacer"></div>
        <div class="d-flex flex-column gspace-5 position-relative z-2">
          <div class="d-flex flex-column gspace-2 reveal" data-anim="fadeInUp">
            <h3 class="title-heading">You scrolled so far. You want this. Trust us.</h3>
            <p>Get exclusive insights, trends, and strategies delivered straight to your inbox. Subscribe now!</p>
          </div>
          <form id="newsletterForm" class="needs-validation reveal" data-anim="fadeInUp" novalidate>
            <div class="input-container">
              <input id="newsletter-email" placeholder="Give your best email" required type="email" name="newsletter-email">
            </div>
            <button class="btn btn-accent" type="submit">
              <span class="btn-title"><span>Subscribe</span></span>
              <span class="icon-circle"><i class="fa-solid fa-arrow-right"></i></span>
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

