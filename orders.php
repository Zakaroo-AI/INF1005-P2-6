<?php
// ============================================================
// orders.php — Order History
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT o.*, COUNT(oi.item_id) AS item_count
    FROM orders o
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    WHERE o.buyer_id = ?
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();

function statusBadge(string $status): string {
    return match($status) {
        'pending'    => '<span class="badge bg-secondary">Pending</span>',
        'processing' => '<span class="badge bg-warning text-dark">Processing</span>',
        'shipped'    => '<span class="badge bg-primary">Shipped</span>',
        'delivered'  => '<span class="badge bg-success">Delivered</span>',
        default      => '<span class="badge bg-light text-dark">Unknown</span>'
    };
}

$pageTitle = 'My Orders';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-bag me-2"></i>My Orders
    </h1>

    <?php if (empty($orders)): ?>
    <div class="text-center py-5">
        <i class="bi bi-bag-x display-1 text-muted"></i>
        <h2 class="h4 mt-3 text-muted">No orders yet</h2>
        <a href="/browse.php" class="btn btn-pm-primary mt-3">Start Shopping</a>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-dark">
                    <tr>
                        <th scope="col">Order #</th>
                        <th scope="col">Date</th>
                        <th scope="col">Items</th>
                        <th scope="col">Total</th>
                        <th scope="col">Status</th>
                        <th scope="col">Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($orders as $order): ?>
                    <tr>
                        <td class="fw-bold">#<?= str_pad($order['order_id'], 4, '0', STR_PAD_LEFT) ?></td>
                        <td><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                        <td><?= $order['item_count'] ?> item<?= $order['item_count'] !== '1' ? 's' : '' ?></td>
                        <td class="fw-bold text-primary">$<?= number_format($order['total_price'], 2) ?></td>
                        <td><?= statusBadge($order['status']) ?></td>
                        <td>
                            <a href="/order-detail.php?id=<?= $order['order_id'] ?>"
                               class="btn btn-sm btn-outline-primary">View</a>
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
