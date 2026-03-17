<?php
// ============================================================
// admin/disputes.php — Manage Disputes
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();
$pdo = getPDO();

// Handle resolve / close
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $disputeId = (int)($_POST['dispute_id'] ?? 0);
    $action    = $_POST['action']     ?? '';
    $adminNote = trim($_POST['admin_note'] ?? '');

    if ($disputeId && in_array($action, ['resolved', 'closed'])) {
        $pdo->prepare("UPDATE disputes SET status = ?, admin_note = ? WHERE dispute_id = ?")
            ->execute([$action, $adminNote ?: null, $disputeId]);
        $_SESSION['flash'] = ['type' => 'success', 'message' => 'Dispute ' . $action . '.'];
    }
    header('Location: /admin/disputes.php'); exit;
}

$disputes = $pdo->query("
    SELECT d.*,
           u.username  AS buyer_name,
           o.total_price,
           o.status    AS order_status
    FROM disputes d
    JOIN users  u ON d.buyer_id  = u.user_id
    JOIN orders o ON d.order_id  = o.order_id
    ORDER BY (d.status = 'open') DESC, d.created_at DESC
")->fetchAll();

$openCount = count(array_filter($disputes, fn($d) => $d['status'] === 'open'));
$pageTitle = 'Manage Disputes';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">
                Disputes
                <span class="badge bg-secondary ms-2 fs-6"><?= count($disputes) ?></span>
                <?php if ($openCount): ?>
                <span class="badge bg-danger ms-1 fs-6"><?= $openCount ?> open</span>
                <?php endif; ?>
            </h1>

            <?php if (empty($disputes)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-shield-check display-1"></i>
                <p class="mt-3">No disputes filed yet.</p>
            </div>
            <?php else: ?>
            <div class="d-flex flex-column gap-3">
                <?php foreach ($disputes as $d): ?>
                <div class="card border-0 shadow-sm rounded-4 p-4
                     <?= $d['status'] === 'open' ? 'border-start border-danger border-3' : '' ?>">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                        <div>
                            <span class="fw-bold">
                                Order #<?= str_pad($d['order_id'], 4, '0', STR_PAD_LEFT) ?>
                            </span>
                            <span class="text-muted small ms-2">by <?= e($d['buyer_name']) ?></span>
                            <span class="text-muted small ms-2">· $<?= number_format($d['total_price'], 2) ?></span>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <span class="badge bg-<?= $d['order_status'] === 'delivered' ? 'success' : 'primary' ?>">
                                Order: <?= ucfirst(e($d['order_status'])) ?>
                            </span>
                            <span class="badge bg-<?= $d['status'] === 'open' ? 'danger' : ($d['status'] === 'resolved' ? 'success' : 'secondary') ?>">
                                <?= ucfirst(e($d['status'])) ?>
                            </span>
                        </div>
                    </div>

                    <p class="text-muted small mb-2">
                        <i class="bi bi-calendar3 me-1"></i>
                        <?= date('d M Y, g:ia', strtotime($d['created_at'])) ?>
                    </p>

                    <div class="bg-light rounded-3 p-3 mb-3">
                        <p class="mb-0 small"><strong>Buyer's report:</strong> <?= e($d['reason']) ?></p>
                    </div>

                    <?php if ($d['admin_note']): ?>
                    <div class="alert alert-info py-2 mb-3">
                        <small><strong>Admin note:</strong> <?= e($d['admin_note']) ?></small>
                    </div>
                    <?php endif; ?>

                    <?php if ($d['status'] === 'open'): ?>
                    <div class="d-flex gap-2 flex-wrap align-items-end">
                        <div class="flex-grow-1">
                            <label class="form-label small fw-semibold mb-1" for="note_<?= $d['dispute_id'] ?>">
                                Admin Note <span class="text-muted fw-normal">(optional)</span>
                            </label>
                            <input type="text" id="note_<?= $d['dispute_id'] ?>"
                                   class="form-control form-control-sm dispute-note"
                                   data-id="<?= $d['dispute_id'] ?>"
                                   placeholder="e.g. Refund issued, seller contacted...">
                        </div>
                        <form method="POST" class="d-flex gap-2">
                            <input type="hidden" name="dispute_id" value="<?= $d['dispute_id'] ?>">
                            <input type="hidden" name="admin_note" class="admin-note-hidden" value="">
                            <button type="submit" name="action" value="resolved"
                                    class="btn btn-sm btn-success"
                                    onclick="syncNote(this)">
                                <i class="bi bi-check-circle me-1"></i>Resolve
                            </button>
                            <button type="submit" name="action" value="closed"
                                    class="btn btn-sm btn-outline-secondary"
                                    onclick="syncNote(this)">
                                Close
                            </button>
                        </form>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
function syncNote(btn) {
    const form      = btn.closest('form');
    const card      = form.closest('.card');
    const noteInput = card.querySelector('.dispute-note');
    const hidden    = form.querySelector('.admin-note-hidden');
    if (noteInput && hidden) hidden.value = noteInput.value;
}
</script>

<?php require_once '../includes/footer.php'; ?>
