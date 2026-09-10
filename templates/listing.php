<!-- ============ PAGE BANNER ============ -->
<div class="section-banner">
  <div class="banner-layout-wrapper banner-inner">
    <div class="banner-layout">
      <div class="d-flex flex-column text-center align-items-center gspace-2">
        <h2 class="title-heading reveal" data-anim="fadeInUp">Our Blog</h2>
        <nav class="breadcrumb">
          <a href="<?= h(url("index.html")) ?>" class="gspace-2">Home</a><span class="separator-link">/</span><p class="current-page">Blog</p>
        </nav>
      </div>
      <div class="spacer"></div>
    </div>
  </div>
</div>

<!-- ============ BLOG GRID ============ -->
<div class="section">
  <div class="hero-container">
    <div class="d-flex flex-column gspace-5">

      <div class="row row-cols-lg-2 row-cols-1 grid-spacer-5 m-0">
        <div class="col col-lg-8 ps-0 pe-0">
          <div class="d-flex flex-column gspace-2 reveal" data-anim="fadeInUp">
            <div class="sub-heading"><i class="fa-regular fa-circle-dot"></i><span>Insights &amp; Trends</span></div>
            <h2 class="title-heading">Latest Insights &amp; Industry Trends</h2>
          </div>
        </div>
        <div class="col col-lg-4 ps-0 pe-0">
          <div class="d-flex flex-column gspace-2 justify-content-end h-100 reveal" data-anim="fadeInUp">
            <p>Explore our latest blog articles covering industry trends, expert insights, and actionable strategies to elevate your digital marketing game.</p>
            <div class="link-wrapper">
              <a href="<?= h(blog_url()) ?>">View All Articles</a><i class="fa-solid fa-circle-arrow-right"></i>
            </div>
          </div>
        </div>
      </div>

      <div class="row row-cols-md-2 row-cols-lg-3 row-cols-1 grid-spacer-3">
        <?php foreach ($posts as $post): ?>
        <div class="col">
          <div>
            <div class="card card-blog card-hover-lift h-100 reveal" data-anim="fadeInUp" style="cursor: pointer;">
              <div class="blog-image"><img alt="Blog" src="<?= h(post_image_url($post['image'])) ?>"></div>
              <div class="card-body">
                <div class="d-flex flex-row gspace-2">
                  <div class="d-flex flex-row gspace-1 align-items-center"><i class="fa-solid fa-calendar accent-color"></i><span class="meta-data"><?= h($post['date']) ?></span></div>
                  <div class="d-flex flex-row gspace-1 align-items-center"><i class="fa-solid fa-folder accent-color"></i><span class="meta-data"><?= h($post['category']) ?></span></div>
                </div>
                <a class="blog-link" href="<?= h(post_url($post['slug'])) ?>"><?= h($post['title']) ?></a>
                <p><?= h($post['excerpt']) ?></p>
                <a class="read-more" href="<?= h(post_url($post['slug'])) ?>">Read More</a>
              </div>
            </div>
          </div>
        </div>
        <?php endforeach; ?>
      </div>

    </div>
  </div>
</div>

