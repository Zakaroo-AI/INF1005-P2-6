<?php
// ============================================================
// checkout.php — Checkout & Place Order
// ============================================================
require_once 'includes/header.php';
$pageTitle = 'Checkout';
requireLogin();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Fetch cart
$stmt = $pdo->prepare("
    SELECT c.quantity, c.listing_id,
           l.title, l.price, l.stock, l.status, l.seller_id,
           ca.card_name, ca.image_url
    FROM cart c
    JOIN listings l  ON c.listing_id = l.listing_id
    JOIN cards    ca ON l.card_id    = ca.card_id
    WHERE c.user_id = ?
");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll();

if (empty($cartItems)) {
    $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Your cart is empty.'];
    header('Location: /browse.php'); exit;
}

$cartTotal = array_sum(array_map(fn($i) => $i['price'] * $i['quantity'], $cartItems));

// ---- Handle Order Submission ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $errors = [];

    // Validate stock for each item
    foreach ($cartItems as $item) {
        if ($item['status'] !== 'active') {
            $errors[] = e($item['card_name']) . ' is no longer available.';
        } elseif ($item['stock'] < $item['quantity']) {
            $errors[] = 'Not enough stock for ' . e($item['card_name']) . '. Only ' . $item['stock'] . ' left.';
        }
    }

    if (empty($errors)) {
        try {
            $pdo->beginTransaction();

            // Create order
            $stmt2 = $pdo->prepare("INSERT INTO orders (buyer_id, total_price) VALUES (?, ?)");
            $stmt2->execute([$userId, $cartTotal]);
            $orderId = $pdo->lastInsertId();

            // Insert order items & decrement stock
            foreach ($cartItems as $item) {
                $stmt3 = $pdo->prepare("INSERT INTO order_items (order_id, listing_id, quantity, unit_price) VALUES (?,?,?,?)");
                $stmt3->execute([$orderId, $item['listing_id'], $item['quantity'], $item['price']]);

                // Decrement stock; mark as sold if 0
                $newStock = $item['stock'] - $item['quantity'];
                $newStatus = $newStock <= 0 ? 'sold' : 'active';
                $stmt4 = $pdo->prepare("UPDATE listings SET stock = ?, status = ? WHERE listing_id = ?");
                $stmt4->execute([$newStock, $newStatus, $item['listing_id']]);
            }

            // Clear cart
            $pdo->prepare("DELETE FROM cart WHERE user_id = ?")->execute([$userId]);

            $pdo->commit();

            $_SESSION['flash'] = ['type' => 'success', 'message' => '🎉 Order placed successfully! Your Pokémon are on their way!'];
            header('Location: /order-detail.php?id=' . $orderId); exit;

        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong. Please try again.';
        }
    }
}
?>

<div class="container py-5" style="max-width:900px;">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-bag-check me-2"></i>Checkout
    </h1>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger" role="alert">
        <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
    </div>
    <?php endif; ?>

    <div class="row g-4">
        <!-- Order Items -->
        <div class="col-md-7">
            <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <h2 class="h6 fw-bold mb-3">Order Items</h2>
                <?php foreach ($cartItems as $item): ?>
                <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
                    <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['card_name']) ?>"
                         style="width:56px;height:56px;object-fit:contain;background:#eef0ff;border-radius:10px;padding:4px;">
                    <div class="flex-grow-1">
                        <p class="mb-0 fw-bold small"><?= e($item['title']) ?></p>
                        <p class="mb-0 text-muted small">Qty: <?= $item['quantity'] ?></p>
                    </div>
                    <span class="fw-bold text-primary">$<?= number_format($item['price'] * $item['quantity'], 2) ?></span>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Trainer Info (display only) -->
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h2 class="h6 fw-bold mb-3">Trainer Details</h2>
                <p class="mb-1"><strong>Username:</strong> <?= e($_SESSION['username']) ?></p>
                <p class="mb-0 text-muted small">
                    <i class="bi bi-info-circle me-1"></i>
                    This is a demonstration marketplace. No real payment is processed.
                </p>
            </div>
        </div>

        <!-- Summary -->
        <div class="col-md-5">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px;">
                <h2 class="h5 fw-bold mb-4">Order Summary</h2>

                <div class="d-flex justify-content-between mb-2">
                    <span class="text-muted">Items (<?= count($cartItems) ?>)</span>
                    <span>$<?= number_format($cartTotal, 2) ?></span>
                </div>
                <div class="d-flex justify-content-between mb-3">
                    <span class="text-muted">Delivery</span>
                    <span class="text-success fw-bold">FREE</span>
                </div>
                <hr>
                <div class="d-flex justify-content-between mb-4">
                    <span class="fw-bold fs-5">Total</span>
                    <span class="fw-bold fs-5 text-primary">$<?= number_format($cartTotal, 2) ?></span>
                </div>

                <form method="POST">
                    <button type="submit" class="btn btn-pm-primary w-100 fw-bold py-2 mb-2">
                        <i class="bi bi-lock me-2"></i>Place Order — $<?= number_format($cartTotal, 2) ?>
                    </button>
                </form>
                <a href="/cart.php" class="btn btn-outline-secondary w-100 btn-sm mt-1">
                    <i class="bi bi-arrow-left me-2"></i>Back to Cart
                </a>
            </div>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
