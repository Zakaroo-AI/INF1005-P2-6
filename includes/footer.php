<?php
// ============================================================
// includes/footer.php — Common Footer
// ============================================================
?>
</main>

<footer class="pm-footer py-4" role="contentinfo">
    <div class="container">
        <div class="row g-4">
            <div class="col-md-4">
                <h5 class="text-warning fw-bold">PokéMart Global</h5>
                <p class="text-light small">The world's premier Pokémon marketplace. Buy, sell and trade with trainers across the globe.</p>
            </div>
            <div class="col-md-4">
                <h6 class="text-white">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="/index.php"  class="footer-link">Home</a></li>
                    <li><a href="/browse.php" class="footer-link">Browse Listings</a></li>
                    <li><a href="/about.php"  class="footer-link">About Us</a></li>
                    <?php if (isLoggedIn()): ?>
                    <li><a href="/create-listing.php" class="footer-link">Sell a Card</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="text-white">Account</h6>
                <ul class="list-unstyled small">
                    <?php if (isLoggedIn()): ?>
                    <li><a href="/profile.php"  class="footer-link">My Profile</a></li>
                    <li><a href="/orders.php"   class="footer-link">My Orders</a></li>
                    <li><a href="/wishlist.php" class="footer-link">Wishlist</a></li>
                    <?php else: ?>
                    <li><a href="/login.php"    class="footer-link">Login</a></li>
                    <li><a href="/register.php" class="footer-link">Register</a></li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
        <hr class="border-secondary mt-3">
        <p class="text-center text-secondary small mb-0">
            &copy; <?= date('Y') ?> PokéMart Global - A fictitious company for INF1005.
        </p>
    </div>
</footer>

<!-- Bootstrap 5 JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="/assets/js/main.js"></script>
</body>
</html>
