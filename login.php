<?php
// ============================================================
// login.php — User Login
// ============================================================
ob_start();
$pageTitle = 'Login';
require_once 'includes/header.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Please enter your email and password.';
    } else {
        $pdo  = getPDO();
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            if ($user['is_banned']) {
                $error = 'Your account has been suspended. Contact support.';
            } else {
                // Regenerate session ID to prevent session fixation
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                $redirect = $_GET['redirect'] ?? '/index.php';
                // Security: only allow relative redirects
                if (!str_starts_with($redirect, '/')) $redirect = '/index.php';

                $_SESSION['flash'] = ['type' => 'success', 'message' => 'Welcome back, ' . $user['username'] . '!'];
                header('Location: ' . $redirect);
                exit;
            }
        } else {
            $error = 'Incorrect email or password. Please try again.';
        }
    }
}
?>

<div class="container py-5" style="max-width:440px;">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <h1 class="h3 fw-bold text-center mb-1" style="color:var(--pm-blue);">Welcome Back</h1>
            <p class="text-muted text-center small mb-4">Log in to your trainer account</p>

            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert"><?= e($error) ?></div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate aria-label="Login form">
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                           required autocomplete="email">
                    <div class="invalid-feedback">Please enter your email.</div>
                </div>
                <div class="mb-4">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           required autocomplete="current-password">
                    <div class="invalid-feedback">Please enter your password.</div>
                </div>
                <button type="submit" class="btn btn-pm-primary w-100 fw-bold py-2">Log In</button>
            </form>

            <hr class="my-3">
            <p class="text-center small text-muted mb-1">
                <strong>Test Accounts:</strong><br>
                Admin: admin@pokemart.com / password<br>
                Trainer: ash@pokemart.com / password
            </p>
            <p class="text-center mt-3 small">Don't have an account? <a href="/register.php">Register free</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
