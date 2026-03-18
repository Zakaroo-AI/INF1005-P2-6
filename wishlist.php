<?php
// ============================================================
// wishlist.php — User Wishlist
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT w.wishlist_id, w.added_at, l.listing_id, l.title, l.price, l.status, l.stock,
           c.card_name, c.image_url, c.typing, c.rarity
    FROM wishlist w
    JOIN listings l ON w.listing_id = l.listing_id
    JOIN cards    c ON l.card_id    = c.card_id
    WHERE w.user_id = ?
    ORDER BY w.added_at DESC
");
$stmt->execute([$userId]);
$wishItems = $stmt->fetchAll();

$pageTitle = 'My Wishlist';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-heart me-2"></i>My Wishlist
        <span class="badge bg-secondary fs-6 ms-2"><?= count($wishItems) ?></span>
    </h1>

    <?php if (empty($wishItems)): ?>
    <div class="text-center py-5">
        <i class="bi bi-heart display-1 text-muted"></i>
        <h2 class="h4 mt-3 text-muted">Your wishlist is empty</h2>
        <p class="text-muted">Click the heart icon on any listing to save it here.</p>
        <a href="/browse.php" class="btn btn-pm-primary mt-2">Browse Listings</a>
    </div>
    <?php else: ?>
    <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
        <?php foreach ($wishItems as $item): ?>
        <div class="col" id="wish-row-<?= $item['listing_id'] ?>">
            <article class="card listing-card h-100">
                <a href="/listing.php?id=<?= $item['listing_id'] ?>">
                    <img src="<?= e($item['image_url']) ?>"
                         alt="<?= e($item['card_name']) ?>"
                         class="card-img-top" loading="lazy">
                </a>
                <div class="card-body d-flex flex-column">
                    <div class="mb-2">
                        <span class="type-badge" style="background:<?= typeBadgeColor($item['typing']) ?>">
                            <?= e($item['typing']) ?>
                        </span>
                        <span class="badge ms-1 rarity-<?= strtolower(str_replace(' ','-',e($item['rarity']))) ?>">
                            <?= e($item['rarity']) ?>
                        </span>
                    </div>
                    <h3 class="card-title fs-6 fw-bold">
                        <a href="/listing.php?id=<?= $item['listing_id'] ?>" class="text-decoration-none text-dark">
                            <?= e($item['title']) ?>
                        </a>
                    </h3>
                    <p class="text-primary fw-bold fs-5 mb-2">$<?= number_format($item['price'], 2) ?></p>

                    <div class="mt-auto d-flex gap-2">
                        <?php if ($item['status'] === 'active' && $item['stock'] > 0): ?>
                        <!-- Add to Cart from wishlist -->
                        <form method="POST" action="/listing.php?id=<?= $item['listing_id'] ?>" class="flex-grow-1">
                            <button type="submit" name="add_to_cart" class="btn btn-pm-primary btn-sm w-100">
                                <i class="bi bi-cart-plus me-1"></i>Add to Cart
                            </button>
                        </form>
                        <?php else: ?>
                        <button class="btn btn-secondary btn-sm flex-grow-1" disabled>Unavailable</button>
                        <?php endif; ?>

                        <!-- Remove from wishlist -->
                        <button class="btn btn-outline-danger btn-sm wishlist-btn wishlisted"
                                data-listing="<?= $item['listing_id'] ?>"
                                aria-pressed="true"
                                aria-label="Remove from wishlist"
                                title="Remove from wishlist">
                            <i class="bi bi-heart-fill"></i>
                        </button>
                    </div>
                </div>
            </article>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Remove card from DOM after wishlist toggle -->
<script>
document.querySelectorAll('.wishlist-btn').forEach(btn => {
    btn.addEventListener('click', async () => {
        const listingId = btn.dataset.listing;
        const res  = await fetch('/api/wishlist.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ listing_id: listingId })
        });
        const data = await res.json();
        if (data.success && !data.wishlisted) {
            const row = document.getElementById('wish-row-' + listingId);
            if (row) { row.style.opacity='0'; row.style.transition='opacity 0.3s'; setTimeout(()=>row.remove(), 300); }
        }
    });
});
</script>

<?php require_once 'includes/footer.php'; ?>
