<div class="section-banner">
  <div class="banner-layout-wrapper banner-inner">
    <div class="banner-layout">
      <div class="d-flex flex-column text-center align-items-center gspace-2">
        <h2 class="title-heading reveal" data-anim="fadeInUp">Post Not Found</h2>
        <nav class="breadcrumb">
          <a href="<?= h(url("index.html")) ?>" class="gspace-2">Home</a><span class="separator-link">/</span><p class="current-page">Blog</p>
        </nav>
      </div>
      <div class="spacer"></div>
    </div>
  </div>
</div>

<div class="section">
  <div class="hero-container">
    <div class="row justify-content-center">
      <div class="col-lg-10">
        <div class="d-flex flex-column gspace-2">
          <p>We couldn't find that article. It may have been moved or unpublished.</p>
          <div class="link-wrapper">
            <a href="<?= h(blog_url()) ?>">Back to all articles</a><i class="fa-solid fa-circle-arrow-right"></i>
          </div>
        </div>
      </div>
    </div>
  </div>
