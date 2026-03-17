<?php
// ============================================================
// index.php — Home / Landing Page
// ============================================================
$pageTitle = 'Home';
require_once 'includes/header.php';

$pdo = getPDO();

// Fetch 6 featured active listings (most recent)
$featured = $pdo->query("
    SELECT l.*, c.card_name, c.typing, c.rarity, c.image_url, l.condition_grade
    FROM listings l
    JOIN cards c ON l.card_id = c.card_id
    WHERE l.status = 'active'
    ORDER BY l.created_at DESC
    LIMIT 6
")->fetchAll();

// Stats for the hero section
$totalListings = $pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetchColumn();
$totalSold     = $pdo->query("SELECT COUNT(*) FROM order_items")->fetchColumn();

// Card types for the filter buttons
$types = ['Fire','Water','Grass','Lightning','Psychic','Fighting','Darkness','Metal','Dragon','Colorless','Fairy'];
?>

<!-- Hero -->
<section class="pm-hero" aria-label="Welcome banner">
    <div class="container text-center position-relative" style="z-index:1;">
        <h1 class="display-4 fw-bold mb-3">
            The World's #1<br>
            <span class="text-warning">Pokémon Card Marketplace</span>
        </h1>
        <p class="lead mb-4">Buy, sell and trade Pokémon cards with collectors across the globe.<br>Thousands of listings. Real collectors. Secure transactions.</p>
        <a href="/browse.php" class="btn btn-warning btn-lg fw-bold me-2 px-5">
            <i class="bi bi-search me-2"></i>Browse Now
        </a>
        <?php if (!isLoggedIn()): ?>
        <a href="/register.php" class="btn btn-outline-light btn-lg px-5">
            <i class="bi bi-person-plus me-2"></i>Join Free
        </a>
        <?php else: ?>
        <a href="/create-listing.php" class="btn btn-outline-light btn-lg px-5">
            <i class="bi bi-plus-circle me-2"></i>Sell a Card
        </a>
        <?php endif; ?>

        <!-- Quick stats -->
        <div class="row justify-content-center mt-5 g-3">
            <div class="col-auto">
                <div class="bg-white bg-opacity-10 rounded-3 px-4 py-2 text-white">
                    <strong class="text-warning fs-4"><?= number_format($totalListings) ?></strong><br>
                    <small>Active Listings</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-white bg-opacity-10 rounded-3 px-4 py-2 text-white">
                    <strong class="text-warning fs-4"><?= number_format($totalUsers) ?></strong><br>
                    <small>Registered Trainers</small>
                </div>
            </div>
            <div class="col-auto">
                <div class="bg-white bg-opacity-10 rounded-3 px-4 py-2 text-white">
                    <strong class="text-warning fs-4"><?= number_format($totalSold) ?></strong><br>
                    <small>Pokémon Sold</small>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Type Filter Buttons -->
<section class="container my-5" aria-label="Filter by type">
    <h2 class="section-title">Browse by Type</h2>
    <div class="d-flex flex-wrap gap-2">
        <?php foreach ($types as $type): ?>
            <a href="/browse.php?typing=<?= urlencode($type) ?>"
               class="type-badge text-decoration-none"
               style="background:<?= typeBadgeColor($type) ?>; padding: 6px 16px; font-size:0.9rem;">
                <?= e($type) ?>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<!-- Featured Listings -->
<section class="container my-5" aria-label="Featured listings">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2 class="section-title mb-0">Featured Listings</h2>
        <a href="/browse.php" class="btn btn-outline-primary btn-sm">View All <i class="bi bi-arrow-right ms-1"></i></a>
    </div>

    <?php if (empty($featured)): ?>
        <p class="text-muted">No listings yet. Be the first to <a href="/create-listing.php">sell a card</a>!</p>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($featured as $listing): ?>
        <div class="col">
            <article class="card listing-card h-100" aria-label="<?= e($listing['title']) ?>">
                <a href="/listing.php?id=<?= $listing['listing_id'] ?>">
                    <img src="<?= e($listing['image_url']) ?>"
                         class="card-img-top"
                         alt="<?= e($listing['card_name']) ?>"
                         loading="lazy">
                </a>
                <div class="card-body d-flex flex-column">
                    <!-- Type & Rarity -->
                    <div class="mb-2">
                        <span class="type-badge" style="background:<?= typeBadgeColor($listing['typing']) ?>">
                            <?= e($listing['typing']) ?>
                        </span>
                        <span class="badge ms-1 rarity-<?= strtolower(str_replace(' ','-', e($listing['rarity']))) ?>">
                            <?= e($listing['rarity']) ?>
                        </span>
                        <span class="badge ms-1 condition-<?= strtolower(str_replace(' ','-', e($listing['condition_grade']))) ?>">
                            <?= e($listing['condition_grade']) ?>
                        </span>
                    </div>
                    <h3 class="card-title fs-6 fw-bold">
                        <a href="/listing.php?id=<?= $listing['listing_id'] ?>" class="text-decoration-none text-dark">
                            <?= e($listing['title']) ?>
                        </a>
                    </h3>
                    <div class="mt-auto pt-2 d-flex justify-content-between align-items-center">
                        <span class="fs-5 fw-bold text-primary">$<?= number_format($listing['price'], 2) ?></span>
                        <a href="/listing.php?id=<?= $listing['listing_id'] ?>"
                           class="btn btn-pm-primary btn-sm">View</a>
                    </div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</section>

<!-- How It Works -->
<section class="bg-white py-5 my-5" aria-label="How it works">
    <div class="container text-center">
        <h2 class="section-title mx-auto">How It Works</h2>
        <div class="row g-4 mt-2">
            <div class="col-md-4">
                <div class="display-4 text-primary mb-3" aria-hidden="true"><i class="bi bi-search"></i></div>
                <h3 class="h5 fw-bold">1. Browse Listings</h3>
                <p class="text-muted small">Search and filter thousands of card listings by type, rarity, condition, and price.</p>
            </div>
            <div class="col-md-4">
                <div class="display-4 text-primary mb-3" aria-hidden="true"><i class="bi bi-cart3"></i></div>
                <h3 class="h5 fw-bold">2. Add to Cart</h3>
                <p class="text-muted small">Add your favourites to the cart and checkout securely with a single click.</p>
            </div>
            <div class="col-md-4">
                <div class="display-4 text-primary mb-3" aria-hidden="true"><i class="bi bi-stars"></i></div>
                <h3 class="h5 fw-bold">3. Become a Seller</h3>
                <p class="text-muted small">List your own cards for sale. Manage your listings and track your orders.</p>
            </div>
        </div>
    </div>
</section>

<?php require_once 'includes/footer.php'; ?>
