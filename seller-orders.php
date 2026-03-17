<?php
// ============================================================
// seller-orders.php — Seller Order Management
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo      = getPDO();
$sellerId = $_SESSION['user_id'];

// Handle confirm / ship actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId = (int)($_POST['order_id'] ?? 0);
    $action  = $_POST['action'] ?? '';

    if ($orderId && in_array($action, ['confirm', 'ship'])) {
        // Security: verify this order actually contains this seller's listings
        $check = $pdo->prepare("
            SELECT COUNT(*) FROM order_items oi
            JOIN listings l ON oi.listing_id = l.listing_id
            WHERE oi.order_id = ? AND l.seller_id = ?
        ");
        $check->execute([$orderId, $sellerId]);

        if ($check->fetchColumn() > 0) {
            if ($action === 'confirm') {
                $pdo->prepare("UPDATE orders SET status = 'processing' WHERE order_id = ? AND status = 'pending'")
                    ->execute([$orderId]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order confirmed! Please prepare and ship the item(s).'];
            } elseif ($action === 'ship') {
                $pdo->prepare("UPDATE orders SET status = 'shipped' WHERE order_id = ? AND status = 'processing'")
                    ->execute([$orderId]);
                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order marked as shipped! Awaiting buyer confirmation.'];
            }
        }
    }
    header('Location: /seller-orders.php'); exit;
}

// Fetch all orders that contain this seller's listings
$stmt = $pdo->prepare("
    SELECT DISTINCT o.order_id, o.status, o.total_price, o.created_at,
           u.username AS buyer_name,
           COUNT(oi.item_id) AS item_count
    FROM orders o
    JOIN order_items oi ON o.order_id  = oi.order_id
    JOIN listings   l  ON oi.listing_id = l.listing_id
    JOIN users      u  ON o.buyer_id    = u.user_id
    WHERE l.seller_id = ?
    GROUP BY o.order_id, o.status, o.total_price, o.created_at, u.username
    ORDER BY FIELD(o.status,'pending','processing','shipped','delivered'), o.created_at DESC
");
$stmt->execute([$sellerId]);
$orders = $stmt->fetchAll();
$pageTitle = 'My Sales';
require_once 'includes/header.php';
?>

<div class="container py-5" style="max-width:960px;">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-shop me-2"></i>My Sales
    </h1>

    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="bi bi-inbox display-1 text-muted"></i>
        <p class="mt-3 text-muted">No orders for your listings yet.</p>
        <a href="/my-listings.php" class="btn btn-pm-primary mt-2">View My Listings</a>
    </div>
    <?php else: ?>

    <!-- Summary badges -->
    <?php
    $pending    = count(array_filter($orders, fn($o) => $o['status'] === 'pending'));
    $processing = count(array_filter($orders, fn($o) => $o['status'] === 'processing'));
    ?>
    <?php if ($pending || $processing): ?>
    <div class="alert alert-warning d-flex gap-3 align-items-center mb-4">
        <i class="bi bi-bell-fill fs-5"></i>
        <span>
            <?php if ($pending): ?><strong><?= $pending ?></strong> order<?= $pending > 1 ? 's' : '' ?> awaiting your confirmation. <?php endif; ?>
            <?php if ($processing): ?><strong><?= $processing ?></strong> order<?= $processing > 1 ? 's' : '' ?> ready to ship. <?php endif; ?>
        </span>
    </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Order</th>
                        <th>Buyer</th>
                        <th>Items</th>
                        <th>Value</th>
                        <th>Date</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $o):
                        $badgeClass = match($o['status']) {
                            'pending'    => 'bg-secondary',
                            'processing' => 'bg-warning text-dark',
                            'shipped'    => 'bg-primary',
                            'delivered'  => 'bg-success',
                            default      => 'bg-light text-dark'
                        };
                    ?>
                    <tr>
                        <td class="fw-bold">#<?= str_pad($o['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= e($o['buyer_name']) ?></td>
                        <td class="text-muted small"><?= $o['item_count'] ?> item<?= $o['item_count'] != 1 ? 's' : '' ?></td>
                        <td class="fw-bold text-primary">$<?= number_format($o['total_price'], 2) ?></td>
                        <td class="small text-muted"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
                        <td><span class="badge <?= $badgeClass ?>"><?= ucfirst(e($o['status'])) ?></span></td>
                        <td>
                            <?php if ($o['status'] === 'pending'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                <input type="hidden" name="action"   value="confirm">
                                <button type="submit" class="btn btn-sm btn-success"
                                        onclick="return confirm('Confirm this order and prepare for shipping?')">
                                    <i class="bi bi-check-circle me-1"></i>Confirm
                                </button>
                            </form>
                            <?php elseif ($o['status'] === 'processing'): ?>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="order_id" value="<?= $o['order_id'] ?>">
                                <input type="hidden" name="action"   value="ship">
                                <button type="submit" class="btn btn-sm btn-primary"
                                        onclick="return confirm('Mark this order as shipped?')">
                                    <i class="bi bi-truck me-1"></i>Mark Shipped
                                </button>
                            </form>
                            <?php elseif ($o['status'] === 'shipped'): ?>
                            <span class="text-muted small"><i class="bi bi-hourglass-split me-1"></i>Awaiting buyer</span>
                            <?php else: ?>
                            <span class="text-success small"><i class="bi bi-check-circle-fill me-1"></i>Complete</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>
