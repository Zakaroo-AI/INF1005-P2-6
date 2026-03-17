<?php
// ============================================================
// admin/orders.php — Manage Orders & Update Status
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();
$pdo = getPDO();

// Update order status
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $orderId   = (int)($_POST['order_id'] ?? 0);
    $newStatus = $_POST['status'] ?? '';
    $allowed   = ['pending','processing','shipped','delivered'];
    if ($orderId && in_array($newStatus, $allowed)) {
        $pdo->prepare("UPDATE orders SET status = ? WHERE order_id = ?")->execute([$newStatus, $orderId]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Order #' . str_pad($orderId,4,'0',STR_PAD_LEFT) . ' status updated.'];
    }
    header('Location: /admin/orders.php'); exit;
}

$orders = $pdo->query("
    SELECT o.*, u.username, COUNT(oi.item_id) AS item_count
    FROM orders o
    JOIN users u ON o.buyer_id = u.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
")->fetchAll();
$pageTitle = 'Manage Orders';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Manage Orders
                <span class="badge bg-secondary ms-2 fs-6"><?= count($orders) ?></span>
            </h1>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr><th>Order</th><th>Trainer</th><th>Items</th><th>Total</th><th>Date</th><th>Status</th><th>Update</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td class="fw-bold">#<?= str_pad($order['order_id'],4,'0',STR_PAD_LEFT) ?></td>
                                <td><?= e($order['username']) ?></td>
                                <td><?= $order['item_count'] ?></td>
                                <td class="fw-bold text-primary">$<?= number_format($order['total_price'],2) ?></td>
                                <td class="small"><?= date('d M Y', strtotime($order['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $badge = match($order['status']) {
                                        'pending'    => 'secondary',
                                        'processing' => 'warning',
                                        'shipped'    => 'primary',
                                        'delivered'  => 'success',
                                        default      => 'light'
                                    };
                                    ?>
                                    <span class="badge bg-<?= $badge ?>"><?= e($order['status']) ?></span>
                                </td>
                                <td>
                                    <form method="POST" class="d-flex gap-1 align-items-center">
                                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                                        <select name="status" class="form-select form-select-sm" style="width:140px;">
                                            <?php foreach (['pending','processing','shipped','delivered'] as $s): ?>
                                            <option value="<?= $s ?>" <?= $order['status']===$s?'selected':'' ?>><?= ucfirst($s) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-pm-primary">Update</button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
