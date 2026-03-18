<?php
// ============================================================
// listing.php — Single Listing Detail Page
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
$pdo = getPDO();

$id      = (int)($_GET['id'] ?? 0);
$stmt    = $pdo->prepare("
    SELECT l.*, c.*, c.card_name, u.username AS seller_name, u.user_id AS seller_user_id
    FROM listings l
    JOIN cards c ON l.card_id = c.card_id
    JOIN users  u ON l.seller_id = u.user_id
    WHERE l.listing_id = ?
");
$stmt->execute([$id]);
$listing = $stmt->fetch();

if (!$listing) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Listing not found.'];
    header('Location: /browse.php'); exit;
}

$pageTitle = $listing['title'];

// Handle Add to Cart POST
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI'])); exit;
    }
    if ($listing['status'] !== 'active' || $listing['stock'] < 1) {
        $_SESSION['flash'] = ['type' => 'danger', 'message' => 'This listing is no longer available.'];
        header('Location: /listing.php?id=' . $id); exit;
    }
    // Upsert into cart
    $stmt2 = $pdo->prepare("
        INSERT INTO cart (user_id, listing_id, quantity)
        VALUES (?, ?, 1)
        ON DUPLICATE KEY UPDATE quantity = LEAST(quantity + 1, ?)
    ");
    $stmt2->execute([$_SESSION['user_id'], $id, $listing['stock']]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => $listing['card_name'] . ' added to your cart!'];
    header('Location: /cart.php'); exit;
}

// Related listings (same typing)
$related = $pdo->prepare("
    SELECT l.listing_id, l.title, l.price, c.card_name, c.image_url, c.typing
    FROM listings l JOIN cards c ON l.card_id = c.card_id
    WHERE c.typing = ? AND l.listing_id <> ? AND l.status = 'active'
    LIMIT 4
");
$related->execute([$listing['typing'], $id]);
$relatedListings = $related->fetchAll();

require_once 'includes/header.php';
?>

<div class="container py-5">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="/browse.php">Browse</a></li>
            <li class="breadcrumb-item active" aria-current="page"><?= e($listing['title']) ?></li>
        </ol>
    </nav>

    <div class="row g-5">
        <!-- Left: Image -->
        <div class="col-md-5 text-center">
            <div class="bg-light rounded-4 p-4 mb-3" style="background:linear-gradient(135deg,#e8ecff,#f0f4ff)!important;">
                <img src="<?= e($listing['image_url']) ?>"
                     alt="<?= e($listing['card_name']) ?>"
                     class="img-fluid" style="max-height:320px;">
            </div>
            <!-- Wishlist -->
            <?php if (isLoggedIn()): ?>
            <button class="btn btn-outline-danger wishlist-btn w-100"
                    data-listing="<?= $listing['listing_id'] ?>"
                    aria-pressed="false" aria-label="Add to wishlist">
                <i class="bi bi-heart me-2"></i>Add to Wishlist
            </button>
            <?php endif; ?>
        </div>

        <!-- Right: Details -->
        <div class="col-md-7">
            <!-- Type & Rarity -->
            <div class="mb-2">
                <span class="type-badge" style="background:<?= typeBadgeColor($listing['typing']) ?>">
                    <?= e($listing['typing']) ?>
                </span>
                <span class="badge ms-2 rarity-<?= strtolower(str_replace(' ','-', e($listing['rarity']))) ?>">
                    <?= e($listing['rarity']) ?>
                </span>
                <span class="badge ms-2 condition-<?= strtolower(str_replace(' ','-', e($listing['condition_grade']))) ?>">
                    <?= e($listing['condition_grade']) ?>
                </span>
            </div>

            <h1 class="h2 fw-bold"><?= e($listing['title']) ?></h1>
            <p class="text-muted">Listed by <strong><?= e($listing['seller_name']) ?></strong></p>

            <p class="fs-2 fw-bold text-primary mb-1">$<?= number_format($listing['price'], 2) ?></p>
            <p class="text-muted small mb-3">
                <?php if ($listing['stock'] > 0): ?>
                    <i class="bi bi-check-circle text-success me-1"></i><?= $listing['stock'] ?> available
                <?php else: ?>
                    <i class="bi bi-x-circle text-danger me-1"></i>Out of stock
                <?php endif; ?>
            </p>

            <!-- Add to Cart -->
            <?php if ($listing['status'] === 'active' && $listing['stock'] > 0): ?>
            <form method="POST">
                <button type="submit" name="add_to_cart" class="btn btn-pm-primary btn-lg px-5 mb-3">
                    <i class="bi bi-cart-plus me-2"></i>Add to Cart
                </button>
            </form>
            <?php else: ?>
            <button class="btn btn-secondary btn-lg px-5 mb-3" disabled>Unavailable</button>
            <?php endif; ?>

            <hr>
            <!-- Card Details -->
            <h2 class="h6 fw-bold mb-3">Card Details</h2>
            <table class="table table-sm table-borderless small">
                <tr><th class="text-muted" style="width:40%">Set</th><td><?= e($listing['set_name']) ?></td></tr>
                <tr><th class="text-muted">Card Number</th><td><?= e($listing['card_number']) ?></td></tr>
                <tr><th class="text-muted">Condition</th><td><?= e($listing['condition_grade']) ?></td></tr>
                <tr><th class="text-muted">Language</th><td><?= e($listing['language']) ?></td></tr>
            </table>

            <h2 class="h6 fw-bold mb-2">Description</h2>
            <p><?= nl2br(e($listing['description'] ?? 'No description provided.')) ?></p>
        </div>
    </div>

    <!-- Related Listings -->
    <?php if (!empty($relatedListings)): ?>
    <section class="mt-5" aria-label="Related listings">
        <h2 class="section-title">Related Listings</h2>
        <div class="row row-cols-2 row-cols-md-4 g-3">
            <?php foreach ($relatedListings as $rel): ?>
            <div class="col">
                <a href="/listing.php?id=<?= $rel['listing_id'] ?>" class="text-decoration-none">
                    <div class="card listing-card h-100 text-center p-2">
                        <img src="<?= e($rel['image_url']) ?>" alt="<?= e($rel['card_name']) ?>"
                             class="card-img-top" style="height:100px; object-fit:contain; padding:8px;">
                        <div class="card-body p-2">
                            <p class="small fw-bold mb-0 text-dark"><?= e($rel['card_name']) ?></p>
                            <p class="small text-primary fw-bold mb-0">$<?= number_format($rel['price'], 2) ?></p>
                        </div>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
