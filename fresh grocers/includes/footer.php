<?php
// Ensure $BASE_URL is available (defined in config.php). Fallback kept for safety.
if (!isset($BASE_URL)) { $BASE_URL = "/fresh grocers/"; }
$CUSTOMER_URL = $BASE_URL . "customer/";
?>

<footer class="mt-5 pt-5" style="background-color: #146c43; color: white;">
    <div class="container pb-5">
        <div class="row g-5">

            <!-- Brand Section -->
            <div class="col-lg-4 col-md-6 pe-lg-5">
                <div class="d-flex align-items-center mb-4">
                    <div class="brand-box text-success px-3 py-2 rounded-3 me-2 fw-bolder" style="letter-spacing: 1px;">FRESH</div>
                    <div class="fw-bolder text-white" style="font-size: 1.4rem; letter-spacing: 1px;">GROCERS</div>
                </div>
                <p class="small mb-4" style="color: rgba(255,255,255,0.85); line-height: 1.8;">
                    Your neighborhood grocery store with a global spirit. We deliver farm-fresh vegetables, premium meats, and daily household essentials directly to your doorstep.
                </p>
                <!-- Social Icons -->
                <div class="d-flex gap-2">
                    <a href="#" class="btn social-btn rounded-circle d-flex align-items-center justify-content-center" title="Facebook">
                        <i class="bi bi-facebook fs-5"></i>
                    </a>
                    <a href="#" class="btn social-btn rounded-circle d-flex align-items-center justify-content-center" title="Instagram">
                        <i class="bi bi-instagram fs-5"></i>
                    </a>
                    <a href="#" class="btn social-btn rounded-circle d-flex align-items-center justify-content-center" title="Twitter">
                        <i class="bi bi-twitter-x fs-5"></i>
                    </a>
                </div>
            </div>

            <!-- Categories -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 1px;">CATEGORIES</h6>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>shop.php?category=Beverages" class="footer-link">Beverages</a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>shop.php?category=Rice+%26+Grains" class="footer-link">Rice &amp; Grains</a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>shop.php?category=Dairy" class="footer-link">Dairy Products</a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>shop.php" class="footer-link">Household Items</a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>shop.php?special=1" class="footer-link">Offers &amp; Promos</a>
                    </li>
                </ul>
            </div>

            <!-- Useful links -->
            <div class="col-lg-2 col-md-6">
                <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 1px;">QUICK LINKS</h6>
                <ul class="list-unstyled">
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>index.php" class="footer-link">Home / Dashboard</a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>contact.php" class="footer-link">Contact Us</a>
                    </li>
                    <li class="mb-3">
                        <a href="<?php echo $CUSTOMER_URL; ?>track-order.php" class="footer-link">Track Delivery</a>
                    </li>
                    <li class="mb-3">
                        <a href="#" class="footer-link">Terms &amp; Conditions</a>
                    </li>
                    <li class="mb-3">
                        <a href="#" class="footer-link">Privacy Policy</a>
                    </li>
                </ul>
            </div>

            <!-- Reach us -->
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-4 text-white" style="letter-spacing: 1px;">GET IN TOUCH</h6>
                
                <div class="d-flex mb-3">
                    <i class="bi bi-geo-alt-fill fs-5 me-3 text-white opacity-75"></i>
                    <div>
                        <strong class="d-block mb-1">Head Office</strong>
                        <span class="small" style="color: rgba(255,255,255,0.75);">No 123, Galle Road,<br>Colombo 03, Sri Lanka.</span>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <i class="bi bi-telephone-fill fs-5 me-3 text-white opacity-75"></i>
                    <div>
                        <strong class="d-block mb-1">Hotline</strong>
                        <a href="tel:0112345678" class="footer-link small m-0 p-0">011-234-5678</a>
                    </div>
                </div>

                <div class="d-flex mb-3">
                    <i class="bi bi-envelope-fill fs-5 me-3 text-white opacity-75"></i>
                    <div>
                        <strong class="d-block mb-1">Email Support</strong>
                        <a href="mailto:info@freshgrocers.lk" class="footer-link small m-0 p-0">info@freshgrocers.lk</a>
                    </div>
                </div>
            </div>

        </div>
    </div>

    <!-- Bottom Copyright Bar -->
    <div style="background-color: #0f5132; border-top: 1px solid rgba(255,255,255,0.1);">
        <div class="container py-4">
            <div class="row align-items-center">
                <div class="col-md-6 text-center text-md-start mb-2 mb-md-0">
                    <small style="color: rgba(255,255,255,0.75);">
                        &copy; <?php echo date('Y'); ?> Fresh Grocers Sri Lanka. All Rights Reserved.
                    </small>
                </div>
                <div class="col-md-6 text-center text-md-end">
                    <i class="bi bi-credit-card mx-1 fs-4 opacity-50" title="Card Payment"></i>
                    <i class="bi bi-cash mx-1 fs-4 opacity-50" title="Cash on Delivery"></i>
                    <i class="bi bi-shield-check mx-1 fs-4 opacity-50" title="Secure Payment"></i>
                </div>
            </div>
        </div>
    </div>
</footer>

<!-- Toast Container for Notifications -->
<div id="toast-container" class="position-fixed bottom-0 end-0 p-3" style="z-index:9999;"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo $BASE_URL; ?>assets/js/script.js"></script>

</body>
</html>
