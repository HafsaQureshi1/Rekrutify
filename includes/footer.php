<?php /* Shared footer, extracted verbatim from the static pages. */ ?>
<!-- ============ FOOTER ============ -->
<div class="section-footer">
  <div class="bg-footer-wrapper">
    <div class="bg-footer">
      <div class="hero-container position-relative z-2">
        <div class="d-flex flex-column gspace-2">
          <div class="row row-cols-lg-5 row-cols-md-2 row-cols-1 grid-spacer-5">
            <div class="col col-lg-4">
              <div class="footer-logo-container">
                <div class="logo-container-footer mb-3">
                  <img alt="Rekrutify" class="img-fluid rekrutify-logo" src="<?= h(url("assets/images/rekrutify_dark.png")) ?>" style="max-height: 40px;">
                </div>
                <p>One simple hourly rate. Zero recruitment hassle. A team that feels like your own. Rekrutify &mdash; Talent Without Borders.</p>
                <div class="social-container mt-3">
                  <div class="social-item-wrapper"><a href="#" class="social-item"><i class="fa-brands fa-facebook"></i></a></div>
                  <div class="social-item-wrapper"><a href="#" class="social-item"><i class="fa-brands fa-linkedin"></i></a></div>
                  <div class="social-item-wrapper"><a href="#" class="social-item"><i class="fa-brands fa-instagram"></i></a></div>
                  <div class="social-item-wrapper"><a href="#" class="social-item"><i class="fa-brands fa-youtube"></i></a></div>
                </div>
              </div>
            </div>
            <div class="col col-lg-2">
              <div class="footer-quick-links">
                <h5>Company</h5>
                <ul class="footer-list">
                  <li><a href="<?= h(url("about.html")) ?>">About Us</a></li>
                  <li><a href="<?= h(url("team.html")) ?>">Our Team &amp; Vetting</a></li>
                  <li><a href="<?= h(url("partner.html")) ?>">Partnership</a></li>
                  <li><a href="<?= h(url("pricing.html")) ?>">Pricing</a></li>
                  <li><a href="<?= h(url("contact.html")) ?>">Contact Us</a></li>
                </ul>
              </div>
            </div>
            <div class="col col-lg-2">
              <div class="footer-services-container">
                <h5>Services</h5>
                <ul class="footer-list">
                  <li><a href="<?= h(url("medical-billing.html")) ?>">Medical Billing</a></li>
                  <li><a href="#services">Customer Support</a></li>
                  <li><a href="#services">IT Services</a></li>
                  <li><a href="#services">Digital Marketing</a></li>
                </ul>
              </div>
            </div>
            <div class="col col-lg-2">
              <div class="footer-quick-links">
                <h5>Resources</h5>
                <ul class="footer-list">
                  <li><a href="<?= h(blog_url()) ?>">Blog</a></li>
                  <li><a href="#case">Case Studies</a></li>
                  <li><a href="#">Testimonials</a></li>
                  <li><a href="#">FAQs</a></li>
                </ul>
              </div>
            </div>
            <div class="col col-lg-2">
              <div class="footer-contact-container">
                <h5>Contact Info</h5>
                <ul class="contact-list">
                  <li>hello@rekrutify.com</li>
                  <li>+1 (800) 555-0199</li>
                  <li>Montclair, VA 22025, USA</li>
                </ul>
              </div>
            </div>
          </div>
          <div class="footer-content-spacer"></div>
        </div>
        <div class="copyright-container">
          <span class="copyright">&copy; 2026 Rekrutify LLC. All Rights Reserved. | Registered in Virginia, USA.</span>
          <div class="d-flex flex-row gspace-2">
            <a class="legal-link" href="<?= h(url("terms-of-service.html")) ?>">Terms of Service</a>
            <a class="legal-link" href="#">Privacy Policy</a>
            <a class="legal-link" href="#">Security</a>
          </div>
        </div>
        <div class="footer-spacer"></div>
      </div>
    </div>
  </div>
</div>

<script src="<?= h(url("assets/js/main.js")) ?>"></script>
</body>
</html>
