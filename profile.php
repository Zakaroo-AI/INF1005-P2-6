<?php
// ============================================================
// profile.php — User Profile
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';
requireLogin();

$pdo    = getPDO();
$userId = $_SESSION['user_id'];
$user   = getCurrentUser();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_profile') {
        $username = trim($_POST['username'] ?? '');
        if (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be 3–50 characters.';
        } else {
            // Check uniqueness (excluding self)
            $chk = $pdo->prepare("SELECT user_id FROM users WHERE username = ? AND user_id <> ?");
            $chk->execute([$username, $userId]);
            if ($chk->fetch()) {
                $errors[] = 'That username is already taken.';
            } else {
                $pdo->prepare("UPDATE users SET username = ? WHERE user_id = ?")->execute([$username, $userId]);
                $_SESSION['username'] = $username;
                $success = 'Profile updated!';
                $user = getCurrentUser();
            }
        }
    } elseif ($action === 'change_password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password']     ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password_hash'])) {
            $errors[] = 'Current password is incorrect.';
        } elseif (strlen($new) < 8) {
            $errors[] = 'New password must be at least 8 characters.';
        } elseif ($new !== $confirm) {
            $errors[] = 'New passwords do not match.';
        } else {
            $hash = password_hash($new, PASSWORD_BCRYPT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?")->execute([$hash, $userId]);
            $success = 'Password changed successfully!';
        }
    }
}

// Stats
$listingCount = $pdo->prepare("SELECT COUNT(*) FROM listings WHERE seller_id = ?");
$listingCount->execute([$userId]);
$listingCount = $listingCount->fetchColumn();

$orderCount = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE buyer_id = ?");
$orderCount->execute([$userId]);
$orderCount = $orderCount->fetchColumn();

$pageTitle = 'My Profile';
require_once 'includes/header.php';
?>

<div class="container py-5" style="max-width:700px;">
    <h1 class="h2 fw-bold mb-4" style="color:var(--pm-blue);">
        <i class="bi bi-person-circle me-2"></i>My Profile
    </h1>

    <?php if ($success): ?>
    <div class="alert alert-success"><?= e($success) ?></div>
    <?php endif; ?>
    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger"><ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul></div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row g-3 mb-4">
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <div class="fs-2 fw-bold text-primary"><?= $listingCount ?></div>
                <div class="small text-muted">Listings</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <div class="fs-2 fw-bold text-primary"><?= $orderCount ?></div>
                <div class="small text-muted">Orders</div>
            </div>
        </div>
        <div class="col-4">
            <div class="card border-0 shadow-sm rounded-4 text-center p-3">
                <div class="fs-2 fw-bold text-warning"><?= ucfirst(e($user['role'])) ?></div>
                <div class="small text-muted">Role</div>
            </div>
        </div>
    </div>

    <!-- Update Profile -->
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4">
        <h2 class="h6 fw-bold mb-3">Update Username</h2>
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="update_profile">
            <div class="mb-3">
                <label for="username" class="form-label">Username</label>
                <input type="text" class="form-control" id="username" name="username"
                       value="<?= e($user['username']) ?>" required minlength="3" maxlength="50">
                <div class="invalid-feedback">Username must be 3–50 characters.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Email</label>
                <input type="email" class="form-control" value="<?= e($user['email']) ?>" disabled>
                <div class="form-text">Email cannot be changed.</div>
            </div>
            <button type="submit" class="btn btn-pm-primary">Save Changes</button>
        </form>
    </div>

    <!-- Change Password -->
    <div class="card border-0 shadow-sm rounded-4 p-4">
        <h2 class="h6 fw-bold mb-3">Change Password</h2>
        <form method="POST" class="needs-validation" novalidate>
            <input type="hidden" name="action" value="change_password">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
                <div class="invalid-feedback">Please enter your current password.</div>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required minlength="8">
                <div class="invalid-feedback">Must be at least 8 characters.</div>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                <div class="invalid-feedback">Passwords must match.</div>
            </div>
            <button type="submit" class="btn btn-pm-primary">Change Password</button>
        </form>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
