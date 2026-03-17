<?php
// ============================================================
// admin/users.php — Manage Users
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();
$pdo = getPDO();

// Handle ban/unban or promote
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $targetId = (int)($_POST['user_id'] ?? 0);
    $action   = $_POST['action'] ?? '';

    // Prevent self-modification
    if ($targetId !== (int)$_SESSION['user_id'] && $targetId > 0) {
        if ($action === 'ban') {
            $pdo->prepare("UPDATE users SET is_banned = 1 WHERE user_id = ?")->execute([$targetId]);
            $_SESSION['flash'] = ['type' => 'warning', 'message' => 'User banned.'];
        } elseif ($action === 'unban') {
            $pdo->prepare("UPDATE users SET is_banned = 0 WHERE user_id = ?")->execute([$targetId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User unbanned.'];
        } elseif ($action === 'promote') {
            $pdo->prepare("UPDATE users SET role = 'admin' WHERE user_id = ?")->execute([$targetId]);
            $_SESSION['flash'] = ['type' => 'success', 'message' => 'User promoted to admin.'];
        } elseif ($action === 'demote') {
            $pdo->prepare("UPDATE users SET role = 'trainer' WHERE user_id = ?")->execute([$targetId]);
            $_SESSION['flash'] = ['type' => 'info', 'message' => 'User demoted to trainer.'];
        }
    }
    header('Location: /admin/users.php'); exit;
}

$users = $pdo->query("
    SELECT u.*, COUNT(l.listing_id) AS listing_count
    FROM users u LEFT JOIN listings l ON u.user_id = l.seller_id
    GROUP BY u.user_id ORDER BY u.created_at DESC
")->fetchAll();
$pageTitle = 'Manage Users';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Manage Users
                <span class="badge bg-secondary ms-2 fs-6"><?= count($users) ?></span>
            </h1>

            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-dark">
                            <tr>
                                <th>ID</th><th>Username</th><th>Email</th><th>Role</th>
                                <th>Listings</th><th>Status</th><th>Joined</th><th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user): ?>
                            <tr <?= $user['is_banned'] ? 'class="table-danger"' : '' ?>>
                                <td class="text-muted small">#<?= $user['user_id'] ?></td>
                                <td class="fw-bold"><?= e($user['username']) ?></td>
                                <td class="small"><?= e($user['email']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $user['role']==='admin' ? 'danger':'primary' ?>">
                                        <?= e($user['role']) ?>
                                    </span>
                                </td>
                                <td><?= $user['listing_count'] ?></td>
                                <td>
                                    <?php if ($user['is_banned']): ?>
                                        <span class="badge bg-danger">Banned</span>
                                    <?php else: ?>
                                        <span class="badge bg-success">Active</span>
                                    <?php endif; ?>
                                </td>
                                <td class="small"><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                <td>
                                    <?php if ($user['user_id'] != $_SESSION['user_id']): ?>
                                    <form method="POST" class="d-flex gap-1 flex-wrap">
                                        <input type="hidden" name="user_id" value="<?= $user['user_id'] ?>">
                                        <?php if ($user['is_banned']): ?>
                                            <button name="action" value="unban" class="btn btn-xs btn-success btn-sm">Unban</button>
                                        <?php else: ?>
                                            <button name="action" value="ban" class="btn btn-xs btn-warning btn-sm"
                                                    onclick="return confirm('Ban this user?')">Ban</button>
                                        <?php endif; ?>
                                        <?php if ($user['role'] !== 'admin'): ?>
                                            <button name="action" value="promote" class="btn btn-xs btn-outline-danger btn-sm"
                                                    onclick="return confirm('Promote to admin?')">Promote</button>
                                        <?php else: ?>
                                            <button name="action" value="demote" class="btn btn-xs btn-outline-secondary btn-sm"
                                                    onclick="return confirm('Demote to trainer?')">Demote</button>
                                        <?php endif; ?>
                                    </form>
                                    <?php else: ?>
                                        <span class="text-muted small">You</span>
                                    <?php endif; ?>
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
