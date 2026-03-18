<?php
// ============================================================
// admin/listings.php — Manage All Listings
// ============================================================
// All PHP logic runs BEFORE any HTML output — no ob_start needed
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();

$pdo = getPDO();

// Handle status update or delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $listingId = (int)($_POST['listing_id'] ?? 0);
    $action    = $_POST['action'] ?? '';

    if ($listingId) {
        if ($action === 'delete') {
            $pdo->prepare("DELETE FROM listings WHERE listing_id = ?")->execute([$listingId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing deleted.'];
        } elseif (in_array($action, ['active','sold','removed'])) {
            $pdo->prepare("UPDATE listings SET status = ? WHERE listing_id = ?")->execute([$action, $listingId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing status updated.'];
        }
    }
    header('Location: /admin/listings.php'); exit;
}

$listings = $pdo->query("
    SELECT l.*,
           COALESCE(c.card_name, 'Unknown Card') AS card_name,
           COALESCE(c.typing, '') AS typing,
           COALESCE(c.image_url, '') AS image_url,
           COALESCE(u.username, 'Deleted User') AS seller_name
    FROM listings l
    LEFT JOIN cards c ON l.card_id   = c.card_id
    LEFT JOIN users u ON l.seller_id = u.user_id
    ORDER BY l.created_at DESC
")->fetchAll();

// HTML output starts here
$pageTitle = 'Manage Listings';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Manage Listings
                <span class="badge bg-secondary ms-2 fs-6"><?= count($listings) ?></span>
            </h1>
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr><th>Card</th><th>Title</th><th>Seller</th><th>Price</th><th>Stock</th><th>Status</th><th>Actions</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($listings as $l): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center gap-2">
                                        <img src="<?= e($l['image_url']) ?>" alt=""
                                             style="width:40px;height:40px;object-fit:contain;background:#eef0ff;border-radius:6px;">
                                        <span class="fw-bold small"><?= e($l['card_name']) ?></span>
                                    </div>
                                </td>
                                <td class="small"><?= e(strlen($l['title'] ?? '') > 40 ? substr($l['title'], 0, 40) . '...' : ($l['title'] ?? '')) ?></td>
                                <td class="small"><?= e($l['seller_name']) ?></td>
                                <td class="fw-bold text-primary">$<?= number_format($l['price'],2) ?></td>
                                <td><?= $l['stock'] ?></td>
                                <td>
                                    <span class="badge bg-<?= $l['status']==='active'?'success':($l['status']==='sold'?'secondary':'danger') ?>">
                                        <?= e($l['status']) ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-1 flex-wrap">
                                        <form method="POST" class="d-flex gap-1">
                                            <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                                            <select name="action" class="form-select form-select-sm" style="width:110px;">
                                                <option value="active"  <?= $l['status']==='active'  ?'selected':'' ?>>Active</option>
                                                <option value="sold"    <?= $l['status']==='sold'    ?'selected':'' ?>>Sold</option>
                                                <option value="removed" <?= $l['status']==='removed' ?'selected':'' ?>>Removed</option>
                                            </select>
                                            <button type="submit" class="btn btn-sm btn-pm-primary">Set</button>
                                        </form>
                                        <form method="POST" class="d-inline"
                                              onsubmit="return confirm('Delete this listing permanently?')">
                                            <input type="hidden" name="listing_id" value="<?= $l['listing_id'] ?>">
                                            <input type="hidden" name="action" value="delete">
                                            <button type="submit" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </div>
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
