<?php
// ============================================================
// cart.php — Shopping Cart
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Fetch cart items
$stmt = $pdo->prepare("
    SELECT c.cart_id, c.quantity, c.listing_id,
           l.title, l.price, l.stock, l.status,
           ca.card_name, ca.image_url, ca.typing
    FROM cart c
    JOIN listings l  ON c.listing_id = l.listing_id
    JOIN cards    ca ON l.card_id    = ca.card_id
    WHERE c.user_id = ?
    ORDER BY c.added_at DESC
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

$cartTotal = 0;
foreach ($cartItems as $item) {
    $cartTotal += $item['price'] * $item['quantity'];
}

$pageTitle = 'Shopping Cart';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-cart3 me-2"></i>Your Cart
        <span class="badge bg-secondary fs-6 ms-2"><?= count($cartItems) ?> item<?= count($cartItems) !== 1 ? 's' : '' ?></span>
    </h1>

    <?php if (empty($cartItems)): ?>
    <div class="text-center py-5">
        <i class="bi bi-cart-x display-1 text-muted"></i>
        <h2 class="h4 mt-3 text-muted">Your cart is empty</h2>
        <a href="/browse.php" class="btn btn-pm-primary mt-3">Browse Listings</a>
    </div>
    <?php else: ?>
    <div class="row g-4">

        <!-- Cart Items -->
        <div class="col-lg-8" id="cart-items">
            <?php foreach ($cartItems as $item): ?>
            <div class="card border-0 shadow-sm rounded-4 mb-3 p-3" id="cart-row-<?= $item['listing_id'] ?>">
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <!-- Image -->
                    <img src="<?= e($item['image_url']) ?>"
                         alt="<?= e($item['card_name']) ?>"
                         class="cart-item-img">

                    <!-- Info -->
                    <div class="flex-grow-1">
                        <h2 class="h6 fw-bold mb-1">
                            <a href="/listing.php?id=<?= $item['listing_id'] ?>" class="text-decoration-none text-dark">
                                <?= e($item['title']) ?>
                            </a>
                        </h2>
                        <span class="type-badge" style="background:<?= typeBadgeColor($item['typing']) ?>; font-size:0.7rem;">
                            <?= e($item['typing']) ?>
                        </span>
                        <p class="mb-0 mt-1 text-primary fw-bold">$<?= number_format($item['price'], 2) ?> each</p>
                    </div>

                    <!-- Quantity controls -->
                    <div class="d-flex align-items-center gap-2">
                        <button class="btn btn-outline-secondary qty-btn"
                                data-listing="<?= $item['listing_id'] ?>"
                                data-action="decrease"
                                aria-label="Decrease quantity">
                            <i class="bi bi-dash"></i>
                        </button>
                        <span id="qty-<?= $item['listing_id'] ?>" class="fw-bold px-2 fs-5">
                            <?= $item['quantity'] ?>
                        </span>
                        <button class="btn btn-outline-secondary qty-btn"
                                data-listing="<?= $item['listing_id'] ?>"
                                data-action="increase"
                                aria-label="Increase quantity">
                            <i class="bi bi-plus"></i>
                        </button>
                    </div>

                    <!-- Subtotal -->
                    <div class="text-end" style="min-width:90px;">
                        <p class="fw-bold text-primary mb-1 fs-5" id="subtotal-<?= $item['listing_id'] ?>">
                            $<?= number_format($item['price'] * $item['quantity'], 2) ?>
                        </p>
                        <button class="btn btn-sm btn-outline-danger remove-cart-btn"
                                data-listing="<?= $item['listing_id'] ?>"
                                aria-label="Remove <?= e($item['card_name']) ?> from cart">
                            <i class="bi bi-trash"></i> Remove
                        </button>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="col-lg-4" id="cart-summary">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px;">
                <h2 class="h5 fw-bold mb-4">Order Summary</h2>
                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Subtotal</span>
                    <span id="cart-total" class="fw-bold">$<?= number_format($cartTotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Shipping</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-5 text-primary" id="cart-total-display">$<?= number_format($cartTotal, 2) ?></span>
                </div>
                <a href="/checkout.php" class="btn btn-pm-primary w-100 fw-bold py-2 mb-2">
                    <i class="bi bi-lock me-2"></i>Proceed to Checkout
                </a>
                <a href="/browse.php" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-2"></i>Continue Browsing
                </a>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Sync the two total elements via JS -->
<script>
document.addEventListener('DOMContentLoaded', () => {
    const t1 = document.getElementById('cart-total');
    const t2 = document.getElementById('cart-total-display');
    if (t1 && t2) {
        const observer = new MutationObserver(() => { t2.textContent = t1.textContent; });
        observer.observe(t1, { childList: true, characterData: true, subtree: true });
    }
});
</script>

<?php require_once 'includes/footer.php'; ?>
