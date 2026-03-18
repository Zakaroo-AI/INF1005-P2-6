<?php
// ============================================================
// register.php — User Registration
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email    = trim($_POST['email']    ?? '');
    $password =      $_POST['password'] ?? '';
    $confirm  =      $_POST['confirm']  ?? '';

    // --- Server-side validation ---
    if (strlen($username) < 3 || strlen($username) > 50) {
        $errors[] = 'Username must be between 3 and 50 characters.';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters.';
    }
    if ($password !== $confirm) {
        $errors[] = 'Passwords do not match.';
    }

    if (empty($errors)) {
        $pdo = getPDO();

        // Check for duplicate username / email
        $stmt = $pdo->prepare("SELECT user_id FROM users WHERE username = ? OR email = ?");
        $stmt->execute([$username, $email]);
        if ($stmt->fetch()) {
            $errors[] = 'Username or email is already registered.';
        } else {
            // Insert new user
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            $stmt->execute([$username, $email, $hash]);

            $_SESSION['flash'] = ['type' => 'success', 'message' => 'Account created! You can now log in.'];
            header('Location: /login.php');
            exit;
        }
    }
}

$pageTitle = 'Register';
require_once 'includes/header.php';
?>

<div class="container py-5" style="max-width:480px;">
    <div class="card shadow border-0 rounded-4">
        <div class="card-body p-4">
            <h1 class="h3 fw-bold text-center mb-1" style="color:var(--pm-blue);">Join PokéMart</h1>
            <p class="text-muted text-center small mb-4">Create your free trainer account</p>

            <?php if (!empty($errors)): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php foreach ($errors as $e): ?>
                    <li><?= e($e) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <form method="POST" class="needs-validation" novalidate aria-label="Registration form">
                <div class="mb-3">
                    <label for="username" class="form-label fw-semibold">Username</label>
                    <input type="text" class="form-control" id="username" name="username"
                           value="<?= isset($_POST['username']) ? e($_POST['username']) : '' ?>"
                           required minlength="3" maxlength="50"
                           aria-describedby="usernameHelp">
                    <div id="usernameHelp" class="form-text">3–50 characters.</div>
                    <div class="invalid-feedback">Username must be 3–50 characters.</div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>"
                           required>
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Password</label>
                    <input type="password" class="form-control" id="password" name="password"
                           required minlength="8" aria-describedby="passHelp">
                    <div id="passHelp" class="form-text">Minimum 8 characters.</div>
                    <div class="invalid-feedback">Password must be at least 8 characters.</div>
                </div>
                <div class="mb-4">
                    <label for="confirm" class="form-label fw-semibold">Confirm Password</label>
                    <input type="password" class="form-control" id="confirm" name="confirm" required>
                    <div class="invalid-feedback">Please confirm your password.</div>
                </div>
                <button type="submit" class="btn btn-pm-primary w-100 fw-bold py-2">Create Account</button>
            </form>
            <p class="text-center mt-3 small">Already have an account? <a href="/login.php">Log in</a></p>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
