<?php
// ============================================================
// order-detail.php — Order Detail, Confirmation, Dispute & Review
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo     = getPDO();
$orderId = (int)($_GET['id'] ?? 0);
$userId  = $_SESSION['user_id'];

// ---- POST handler ----
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // Re-verify ownership on every POST
    $chk = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?");
    $chk->execute([$orderId, $userId]);
    $chkOrder = $chk->fetch();

    if ($chkOrder) {
        if ($action === 'mark_received' && $chkOrder['status'] === 'shipped') {
            $pdo->prepare("UPDATE orders SET status = 'delivered' WHERE order_id = ?")
                ->execute([$orderId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order marked as received. Thank you!'];
        }

        if ($action === 'submit_dispute') {
            $reason = trim($_POST['reason'] ?? '');
            if (strlen($reason) >= 10) {
                $pdo->prepare("INSERT IGNORE INTO disputes (order_id, buyer_id, reason) VALUES (?, ?, ?)")
                    ->execute([$orderId, $userId, $reason]);
                $_SESSION['flash'] = ['type' => 'warning', 'message' => 'Dispute submitted. Our team will review it shortly.'];
            } else {
                $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Please describe the issue (at least 10 characters).'];
            }
        }

        if ($action === 'submit_review' && $chkOrder['status'] === 'delivered') {
            $rating  = (int)($_POST['rating'] ?? 0);
            $comment = trim($_POST['comment'] ?? '');
            // Get seller_id from first item in this order
            $sellerStmt = $pdo->prepare("
                SELECT l.seller_id FROM order_items oi
                JOIN listings l ON oi.listing_id = l.listing_id
                WHERE oi.order_id = ? LIMIT 1
            ");
            $sellerStmt->execute([$orderId]);
            $sellerId = $sellerStmt->fetchColumn();

            if ($rating >= 1 && $rating <= 5 && $sellerId && $sellerId != $userId) {
                $pdo->prepare("INSERT IGNORE INTO reviews (order_id, buyer_id, seller_id, rating, comment) VALUES (?, ?, ?, ?, ?)")
                    ->execute([$orderId, $userId, $sellerId, $rating, $comment ?: null]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Review submitted! Thank you for your feedback.'];
            }
        }
    }
    header('Location: /order-detail.php?id=' . $orderId); exit;
}

// ---- Fetch order ----
$stmt = $pdo->prepare("SELECT * FROM orders WHERE order_id = ? AND buyer_id = ?");
$stmt->execute([$orderId, $userId]);
$order = $stmt->fetch();

if (!$order) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Order not found.'];
    header('Location: /orders.php'); exit;
}

// Fetch order items
$stmt2 = $pdo->prepare("
    SELECT oi.*, l.title, c.card_name, c.image_url, c.typing
    FROM order_items oi
    JOIN listings l ON oi.listing_id = l.listing_id
    JOIN cards    c ON l.card_id     = c.card_id
    WHERE oi.order_id = ?
");
$stmt2->execute([$orderId]);
$items = $stmt2->fetchAll();

// Fetch dispute if any
$dispStmt = $pdo->prepare("SELECT * FROM disputes WHERE order_id = ? AND buyer_id = ?");
$dispStmt->execute([$orderId, $userId]);
$dispute = $dispStmt->fetch();

// Fetch review if any
$revStmt = $pdo->prepare("SELECT * FROM reviews WHERE order_id = ? AND buyer_id = ?");
$revStmt->execute([$orderId, $userId]);
$review = $revStmt->fetch();

// Status steps for timeline
$steps = [
    ['key' => 'pending',    'label' => 'Order Placed',      'icon' => 'check-circle'],
    ['key' => 'processing', 'label' => 'Seller Confirmed',  'icon' => 'gear'],
    ['key' => 'shipped',    'label' => 'Shipped',           'icon' => 'truck'],
    ['key' => 'delivered',  'label' => 'Delivered',         'icon' => 'house-check'],
];
$statusOrder = ['pending' => 0, 'processing' => 1, 'shipped' => 2, 'delivered' => 3];
$currentIdx  = $statusOrder[$order['status']] ?? 0;
$pageTitle   = 'Order #' . str_pad($orderId, 4, '0', STR_PAD_LEFT);
require_once 'includes/header.php';
?>

<div class="container py-5" style="max-width:800px;">

    <a href="/orders.php" class="btn btn-outline-secondary btn-sm mb-4">
        <i class="bi bi-arrow-left me-1"></i>Back to Orders
    </a>

    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h2 fw-bold mb-0" style="color:var(--pm-blue);">
            Order #<?= str_pad($orderId, 4, '0', STR_PAD_LEFT) ?>
        </h1>
        <span class="text-muted small">Placed on <?= date('d M Y, g:ia', strtotime($order['created_at'])) ?></span>
    </div>

    <!-- Status Timeline -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4" aria-label="Order status">
        <h2 class="h6 fw-bold mb-4">Delivery Status</h2>
        <div class="status-timeline" role="list">
            <?php foreach ($steps as $idx => $step):
                $isDone   = $idx < $currentIdx;
                $isActive = $idx === $currentIdx;
                $cls = $isDone ? 'done' : ($isActive ? 'active' : '');
            ?>
            <div class="status-step <?= $cls ?>" role="listitem"
                 aria-current="<?= $isActive ? 'step' : 'false' ?>">
                <div class="step-icon"><i class="bi bi-<?= e($step['icon']) ?>"></i></div>
                <div class="step-label"><?= e($step['label']) ?></div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Mark as Received button -->
        <?php if ($order['status'] === 'shipped'): ?>
        <div class="mt-4 pt-3 border-top">
            <p class="text-muted small mb-2">Have you received your cards?</p>
            <form method="POST">
                <input type="hidden" name="action" value="mark_received">
                <button type="submit" class="btn btn-success"
                        onclick="return confirm('Confirm you have received your order? This cannot be undone.')">
                    <i class="bi bi-house-check me-2"></i>Mark as Received
                </button>
            </form>
        </div>
        <?php endif; ?>
    </div>

    <!-- Order Items -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h2 class="h6 fw-bold mb-3">Items Ordered</h2>
        <?php foreach ($items as $item): ?>
        <div class="d-flex align-items-center gap-3 mb-3 pb-3 border-bottom">
            <img src="<?= e($item['image_url']) ?>" alt="<?= e($item['card_name']) ?>"
                 style="width:64px;height:64px;object-fit:contain;background:#eef0ff;border-radius:12px;padding:6px;">
            <div class="flex-grow-1">
                <p class="mb-0 fw-bold"><?= e($item['title']) ?></p>
                <span class="type-badge" style="background:<?= typeBadgeColor($item['typing']) ?>; font-size:0.7rem;">
                    <?= e($item['typing']) ?>
                </span>
                <p class="mb-0 text-muted small mt-1">Qty: <?= $item['quantity'] ?></p>
            </div>
            <div class="text-end">
                <p class="mb-0 fw-bold text-primary">$<?= number_format($item['unit_price'] * $item['quantity'], 2) ?></p>
                <p class="mb-0 text-muted small">$<?= number_format($item['unit_price'], 2) ?> each</p>
            </div>
        </div>
        <?php endforeach; ?>
        <div class="d-flex justify-content-between align-items-center pt-2">
            <span class="fw-bold fs-5">Order Total</span>
            <span class="fw-bold fs-5 text-primary">$<?= number_format($order['total_price'], 2) ?></span>
        </div>
    </div>

    <!-- Review Section (only after delivery) -->
    <?php if ($order['status'] === 'delivered'): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h2 class="h6 fw-bold mb-3"><i class="bi bi-star me-2 text-warning"></i>Rate Your Experience</h2>
        <?php if ($review): ?>
        <div>
            <div class="d-flex align-items-center gap-1 mb-2">
                <?php for ($i = 1; $i <= 5; $i++): ?>
                <i class="bi bi-star<?= $i <= $review['rating'] ? '-fill text-warning' : ' text-muted' ?> fs-5"></i>
                <?php endfor; ?>
                <span class="text-muted small ms-2"><?= $review['rating'] ?>/5 — submitted <?= date('d M Y', strtotime($review['created_at'])) ?></span>
            </div>
            <?php if ($review['comment']): ?>
            <p class="text-muted small fst-italic mb-0">"<?= e($review['comment']) ?>"</p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3">How was your experience with this seller?</p>
        <form method="POST">
            <input type="hidden" name="action" value="submit_review">
            <div class="mb-3">
                <label class="form-label fw-semibold">Rating <span class="text-danger">*</span></label>
                <div class="d-flex gap-2 flex-wrap">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <input type="radio" class="btn-check" name="rating" id="star<?= $i ?>" value="<?= $i ?>" required>
                    <label class="btn btn-outline-warning btn-sm" for="star<?= $i ?>">
                        <i class="bi bi-star-fill me-1"></i><?= $i ?>★
                    </label>
                    <?php endfor; ?>
                </div>
            </div>
            <div class="mb-3">
                <label for="reviewComment" class="form-label fw-semibold">
                    Comment <span class="text-muted fw-normal">(optional)</span>
                </label>
                <textarea class="form-control" id="reviewComment" name="comment" rows="3"
                          placeholder="Share your experience with the seller..."></textarea>
            </div>
            <button type="submit" class="btn btn-pm-primary">
                <i class="bi bi-star me-2"></i>Submit Review
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- Report Issue / Dispute -->
    <?php if (in_array($order['status'], ['processing','shipped','delivered'])): ?>
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h2 class="h6 fw-bold mb-3">
            <i class="bi bi-exclamation-triangle me-2 text-warning"></i>Report an Issue
        </h2>
        <?php if ($dispute): ?>
        <div class="alert alert-<?= $dispute['status'] === 'resolved' ? 'success' : ($dispute['status'] === 'closed' ? 'secondary' : 'warning') ?> mb-0">
            <strong>Dispute <?= ucfirst(e($dispute['status'])) ?></strong>
            — Submitted <?= date('d M Y', strtotime($dispute['created_at'])) ?>
            <p class="mb-0 small mt-1">Your report: "<?= e(mb_strimwidth($dispute['reason'], 0, 120, '...')) ?>"</p>
            <?php if ($dispute['admin_note']): ?>
            <p class="mb-0 small mt-2"><strong>Admin response:</strong> <?= e($dispute['admin_note']) ?></p>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <p class="text-muted small mb-3">
            Have a problem with this order? Let us know and our team will investigate within 2–3 business days.
        </p>
        <form method="POST">
            <input type="hidden" name="action" value="submit_dispute">
            <div class="mb-3">
                <label for="disputeReason" class="form-label fw-semibold">
                    Describe the Issue <span class="text-danger">*</span>
                </label>
                <textarea class="form-control" id="disputeReason" name="reason" rows="3"
                          required minlength="10"
                          placeholder="e.g. Card condition did not match listing, item not received, wrong card sent..."></textarea>
            </div>
            <button type="submit" class="btn btn-outline-danger"
                    onclick="return confirm('Submit a dispute for this order?')">
                <i class="bi bi-flag me-2"></i>Submit Dispute
            </button>
        </form>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <a href="/browse.php" class="btn btn-pm-primary">
        <i class="bi bi-search me-2"></i>Continue Shopping
    </a>
</div>

<?php require_once 'includes/footer.php'; ?>
