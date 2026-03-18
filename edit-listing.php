<?php
// ============================================================
// edit-listing.php — Edit Existing Listing
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo       = getPDO();
$listingId = (int)($_GET['id'] ?? 0);

// Fetch listing — verify ownership
$stmt = $pdo->prepare("
    SELECT l.*, c.card_name
    FROM listings l JOIN cards c ON l.card_id = c.card_id
    WHERE l.listing_id = ? AND l.seller_id = ?
");
$stmt->execute([$listingId, $_SESSION['user_id']]);
$listing = $stmt->fetch();

if (!$listing) {
    $_SESSION['flash'] = ['type' => 'danger', 'message' => 'Listing not found or access denied.'];
    header('Location: /my-listings.php'); exit;
}

$conditions = ['PSA 1','PSA 2','PSA 3','PSA 4','PSA 5','PSA 6','PSA 7','PSA 8','PSA 9','PSA 10'];

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title          = trim($_POST['title']          ?? '');
    $description    = trim($_POST['description']    ?? '');
    $price          = (float)($_POST['price']       ?? 0);
    $stock          = (int)($_POST['stock']         ?? 1);
    $status         = $_POST['status']              ?? 'active';
    $conditionGrade = $_POST['condition_grade']     ?? '';

    if (strlen($title) < 5)         $errors[] = 'Title must be at least 5 characters.';
    if ($price <= 0)                $errors[] = 'Price must be greater than 0.';
    if ($stock < 0 || $stock > 99)  $errors[] = 'Stock must be between 0 and 99.';
    if (!in_array($status, ['active','removed'])) $errors[] = 'Invalid status.';
    if (!in_array($conditionGrade, $conditions))  $errors[] = 'Please select a valid condition.';

    if (empty($errors)) {
        $pdo->prepare("
            UPDATE listings SET title=?, description=?, price=?, stock=?, status=?, condition_grade=?
            WHERE listing_id=? AND seller_id=?
        ")->execute([$title, $description, $price, $stock, $status, $conditionGrade, $listingId, $_SESSION['user_id']]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Listing updated!'];
        header('Location: /my-listings.php'); exit;
    }
}

$pageTitle = 'Edit Listing';
require_once 'includes/header.php';
?>

<div class="container py-5" style="max-width:640px;">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-pencil me-2"></i>Edit Listing
    </h1>
    <p class="text-muted mb-4">Editing: <strong><?= e($listing['card_name']) ?></strong></p>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-4 p-4">
        <form method="POST" class="needs-validation" novalidate>
            <div class="mb-3">
                <label for="title" class="form-label fw-semibold">Title</label>
                <input type="text" class="form-control" id="title" name="title"
                       value="<?= e($_POST['title'] ?? $listing['title']) ?>"
                       required minlength="5" maxlength="150">
                <div class="invalid-feedback">Title must be at least 5 characters.</div>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label fw-semibold">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4"><?= e($_POST['description'] ?? $listing['description']) ?></textarea>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label for="price" class="form-label fw-semibold">Price ($)</label>
                    <div class="input-group">
                        <span class="input-group-text">$</span>
                        <input type="number" class="form-control" id="price" name="price"
                               value="<?= e($_POST['price'] ?? $listing['price']) ?>"
                               required min="0.01" step="0.01">
                    </div>
                </div>
                <div class="col-6">
                    <label for="stock" class="form-label fw-semibold">Stock</label>
                    <input type="number" class="form-control" id="stock" name="stock"
                           value="<?= e($_POST['stock'] ?? $listing['stock']) ?>"
                           required min="0" max="99">
                </div>
            </div>
            <div class="mb-3">
                <label for="condition_grade" class="form-label fw-semibold">Condition (PSA Grade)</label>
                <select class="form-select" id="condition_grade" name="condition_grade" required>
                    <?php foreach ($conditions as $cond): ?>
                    <option value="<?= $cond ?>" <?= (($_POST['condition_grade'] ?? $listing['condition_grade']) === $cond) ? 'selected' : '' ?>><?= $cond ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-4">
                <label for="status" class="form-label fw-semibold">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active"  <?= ($listing['status']==='active')  ? 'selected':'' ?>>Active</option>
                    <option value="removed" <?= ($listing['status']==='removed') ? 'selected':'' ?>>Remove Listing</option>
                </select>
            </div>
            <div class="d-flex gap-2">
                <button type="submit" class="btn btn-pm-primary px-5">Save Changes</button>
                <a href="/my-listings.php" class="btn btn-outline-secondary">Cancel</a>
            </div>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
