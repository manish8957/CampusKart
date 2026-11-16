<?php
// includes/footer.php - Global Page Footer
?>
</main>

<footer class="campus-footer">
    <div class="container">
        <div class="row g-4">
            <!-- Brand & Mission -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="brand-badge bg-warning text-dark"><i class="bi bi-mortarboard-fill"></i></span>
                    <span class="fs-4 fw-bold text-white">CampusKart</span>
                </div>
                <p class="text-secondary small pe-lg-4">
                    The student-first marketplace for second-hand textbooks, scientific calculators, lab gear, and tech accessories. Pass it forward, save semester budgets, and build a sustainable campus economy.
                </p>
                <div class="d-flex gap-3 mt-3">
                    <span class="badge bg-primary bg-opacity-25 text-primary border border-primary px-3 py-2">
                        <i class="bi bi-shield-check me-1"></i> Campus Verified
                    </span>
                    <span class="badge bg-warning bg-opacity-25 text-warning border border-warning px-3 py-2">
                        <i class="bi bi-geo-alt me-1"></i> In-Person Hostel Pickup
                    </span>
                </div>
            </div>

            <!-- Quick Navigation (Marketplace) -->
            <div class="col-lg-2 col-md-6 col-6">
                <h5>Marketplace</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="index.php"><i class="bi bi-chevron-right me-1 small"></i> Home</a></li>
                    <li><a href="products.php"><i class="bi bi-chevron-right me-1 small"></i> Books & Gadgets</a></li>
                    <li><a href="sneakers.php"><i class="bi bi-chevron-right me-1 small"></i> Sneaker Store</a></li>
                    <li><a href="canteen.php"><i class="bi bi-chevron-right me-1 small"></i> Campus Canteen</a></li>
                    <li><a href="wishlist.php"><i class="bi bi-chevron-right me-1 small text-danger"></i> My Wishlist</a></li>
                    <li><a href="cart.php"><i class="bi bi-chevron-right me-1 small"></i> Cart & Trays</a></li>
                    <li><a href="sell.php" class="text-warning fw-semibold"><i class="bi bi-plus-circle me-1 small"></i> Sell An Item</a></li>
                </ul>
            </div>

            <!-- About & Contact Section (Moved from Navbar) -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5>About & Contact</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2">
                    <li><a href="contact.php"><i class="bi bi-info-circle me-1 small text-primary"></i> About CampusKart</a></li>
                    <li><a href="contact.php#about-story"><i class="bi bi-mortarboard me-1 small text-primary"></i> Campus Mission & Story</a></li>
                    <li><a href="contact.php#contact-form"><i class="bi bi-envelope-fill me-1 small text-warning"></i> Contact Help Desk</a></li>
                    <li><a href="contact.php#faq"><i class="bi bi-question-circle me-1 small text-info"></i> Campus Safety FAQ</a></li>
                    <li><a href="mailto:support@campuskart.edu"><i class="bi bi-chat-dots me-1 small text-success"></i> support@campuskart.edu</a></li>
                    <li><a href="tel:9876543210"><i class="bi bi-telephone me-1 small text-secondary"></i> +91 98765 43210 (Helpline)</a></li>
                </ul>
            </div>

            <!-- Student Portal & Safety -->
            <div class="col-lg-3 col-md-6 col-6">
                <h5>Student Portal</h5>
                <ul class="list-unstyled small d-flex flex-column gap-2 mb-3">
                    <?php if (is_logged_in()): ?>
                        <li><a href="my-listings.php"><i class="bi bi-chevron-right me-1 small"></i> My Listings</a></li>
                        <li><a href="orders.php"><i class="bi bi-chevron-right me-1 small"></i> My Orders</a></li>
                        <li><a href="sneaker-orders.php"><i class="bi bi-chevron-right me-1 small"></i> Sneaker Orders</a></li>
                        <li><a href="canteen-orders.php"><i class="bi bi-chevron-right me-1 small"></i> Canteen Tokens</a></li>
                        <li><a href="logout.php" class="text-danger"><i class="bi bi-box-arrow-right me-1 small"></i> Logout</a></li>
                    <?php else: ?>
                        <li><a href="login.php"><i class="bi bi-chevron-right me-1 small"></i> Student Login</a></li>
                        <li><a href="register.php"><i class="bi bi-chevron-right me-1 small"></i> Create Account</a></li>
                    <?php endif; ?>
                </ul>
                <div class="p-2 rounded-3 bg-dark border border-secondary border-opacity-25 small">
                    <small class="text-warning fw-bold d-block"><i class="bi bi-building me-1"></i> Pickup Hubs:</small>
                    <span class="text-secondary" style="font-size: 0.76rem;">Library Lobby, Canteen & North Gate</span>
                </div>
            </div>
        </div>

        <!-- Footer Bottom Bar -->
        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center">
            <p class="mb-0 text-secondary">
                &copy; <?= date('Y') ?> <strong>CampusKart</strong>. Designed for college students.
            </p>
            <p class="mb-0 text-secondary small">
                Built with <i class="bi bi-heart-fill text-danger"></i> PHP, MySQLi & Bootstrap 5
            </p>
        </div>
    </div>
</footer>

</body>
</html>

