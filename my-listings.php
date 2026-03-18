<?php
// ============================================================
// my-listings.php — Seller's Own Listings
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];

// Handle delete
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $delId = (int)$_POST['delete_id'];
    $stmt = $pdo->prepare("DELETE FROM listings WHERE listing_id = ? AND seller_id = ?");
    $stmt->execute([$delId, $userId]);
    $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing removed.'];
    header('Location: /my-listings.php'); exit;
}

$stmt = $pdo->prepare("
    SELECT l.*, c.card_name, c.image_url, c.typing, c.rarity
    FROM listings l
    JOIN cards c ON l.card_id = c.card_id
    WHERE l.seller_id = ?
    ORDER BY l.created_at DESC
");
$stmt->execute([$userId]);
$listings = $stmt->fetchAll();

$pageTitle = 'My Listings';
require_once 'includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <h1 class="h2 fw-bold mb-0" style="color:var(--pm-blue);">
            <i class="bi bi-tags me-2"></i>My Listings
        </h1>
        <a href="/create-listing.php" class="btn btn-pm-primary">
            <i class="bi bi-plus-circle me-2"></i>New Listing
        </a>
    </div>

    <?php if (empty($listings)): ?>
    <div class="text-center py-5">
        <i class="bi bi-tags display-1 text-muted"></i>
        <h2 class="h4 mt-3 text-muted">No listings yet</h2>
        <a href="/create-listing.php" class="btn btn-pm-primary mt-3">Create Your First Listing</a>
    </div>
    <?php else: ?>
    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-dark">
                    <tr>
                        <th>Card</th>
                        <th>Title</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($listings as $l): ?>
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <img src="<?= e($l['image_url']) ?>" alt="<?= e($l['card_name']) ?>"
                                     style="width:44px;height:44px;object-fit:contain;background:#eef0ff;border-radius:8px;">
                                <div>
                                    <div class="fw-bold small"><?= e($l['card_name']) ?></div>
                                    <span class="type-badge" style="background:<?= typeBadgeColor($l['typing']) ?>; font-size:0.65rem;">
                                        <?= e($l['typing']) ?>
                                    </span>
                                </div>
                            </div>
                        </td>
                        <td><?= e($l['title']) ?></td>
                        <td class="fw-bold text-primary">$<?= number_format($l['price'], 2) ?></td>
                        <td><?= $l['stock'] ?></td>
                        <td>
                            <?php if ($l['status'] === 'active'): ?>
                                <span class="badge bg-success">Active</span>
                            <?php elseif ($l['status'] === 'sold'): ?>
                                <span class="badge bg-secondary">Sold</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Removed</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <a href="/edit-listing.php?id=<?= $l['listing_id'] ?>" class="btn btn-sm btn-outline-primary me-1">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="/listing.php?id=<?= $l['listing_id'] ?>" class="btn btn-sm btn-outline-secondary me-1">
                                <i class="bi bi-eye"></i>
                            </a>
                            <form method="POST" class="d-inline" onsubmit="return confirm('Delete this listing?')">
                                <input type="hidden" name="delete_id" value="<?= $l['listing_id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
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
