<?php
// ============================================================
// about.php — About Us
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once 'config/db.php';
require_once 'includes/auth.php';

// Handle contact form
$success = false;
$errors  = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name    = trim($_POST['name']    ?? '');
    $email   = trim($_POST['email']   ?? '');
    $message = trim($_POST['message'] ?? '');
    if (strlen($name) < 2)                         $errors[] = 'Please enter your name.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email.';
    if (strlen($message) < 10)                     $errors[] = 'Message must be at least 10 characters.';
    if (empty($errors)) {
        // In a real app you would send an email here using mail() or PHPMailer
        $success = true;
    }
}

$pageTitle = 'About Us';
require_once 'includes/header.php';
?>

<!-- Hero Banner -->
<section class="pm-hero py-5" aria-label="About us banner">
    <div class="container text-center position-relative" style="z-index:1;">
        <h1 class="display-5 fw-bold">About <span class="text-warning">PokéMart Global</span></h1>
        <p class="lead">The story behind the world's #1 Pokémon marketplace</p>
    </div>
</section>

<div class="container py-5">
    <!-- Mission -->
    <div class="row align-items-center g-5 mb-5">
        <div class="col-md-6">
            <h2 class="section-title">Our Mission</h2>
            <p>PokéMart Global was founded with a simple belief: every collector deserves access to a trustworthy marketplace where they can find, buy, and sell Pokémon cards safely and confidently.</p>
            <p>We connect collectors from all corners of the world, providing a secure platform backed by transparent listings, fair pricing, and top-tier customer protection for every card transaction.</p>
        </div>
        <div class="col-md-6 text-center">
            <div class="bg-light rounded-4 p-5" style="background:linear-gradient(135deg,#e8ecff,#f0f4ff)!important;">
                <i class="bi bi-globe2 display-1 text-primary"></i>
                <p class="mt-3 fw-bold text-primary">Connecting Trainers Worldwide</p>
            </div>
        </div>
    </div>

    <!-- Values -->
    <section class="bg-white rounded-4 p-5 mb-5 shadow-sm" aria-label="Our values">
        <h2 class="section-title">Our Values</h2>
        <div class="row g-4 mt-2">
            <div class="col-md-4 text-center">
                <i class="bi bi-shield-check display-4 text-success mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold">Trust & Safety</h3>
                <p class="text-muted small">Every transaction is protected. We verify sellers and ensure all Pokémon listings are legitimate before going live.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-people display-4 text-primary mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold">Community First</h3>
                <p class="text-muted small">PokéMart thrives because of our community of trainers. We listen, adapt, and build features that our users actually need.</p>
            </div>
            <div class="col-md-4 text-center">
                <i class="bi bi-lightning display-4 text-warning mb-3" aria-hidden="true"></i>
                <h3 class="h5 fw-bold">Speed & Reliability</h3>
                <p class="text-muted small">Fast listings, quick checkout, and reliable order tracking. We believe your time as a trainer is valuable.</p>
            </div>
        </div>
    </section>

    <!-- Team -->
    <section class="mb-5" aria-label="Our team">
        <h2 class="section-title">Meet The Team</h2>
        <div class="row row-cols-1 row-cols-md-3 g-4 mt-2 justify-content-center">
            <?php
            $team = [
                ['name' => 'Lee Hong Yih', 'role' => 'Head Developer',       'icon' => 'person-badge'],
                ['name' => 'Felix', 'role' => 'Developer',   'icon' => 'cpu'],
                ['name' => 'Yong Heng',      'role' => 'Developer',  'icon' => 'shop']
            ];
            foreach ($team as $member): ?>
            <div class="col text-center">
                <div class="card border-0 shadow-sm rounded-4 p-4 h-100">
                    <div class="bg-light rounded-circle d-inline-flex align-items-center justify-content-center mb-3 mx-auto"
                         style="width:64px;height:64px;background:linear-gradient(135deg,#e8ecff,#f0f4ff)!important;">
                        <i class="bi bi-<?= e($member['icon']) ?> fs-3 text-primary" aria-hidden="true"></i>
                    </div>
                    <h3 class="h6 fw-bold mb-1"><?= e($member['name']) ?></h3>
                    <p class="text-muted small mb-0"><?= e($member['role']) ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Contact Form -->
    <section aria-label="Contact form">
        <h2 class="section-title">Get In Touch</h2>
        <?php if ($success): ?>
        <div class="alert alert-success" role="alert">
            <i class="bi bi-check-circle me-2"></i>
            Thanks for your message! Our team will get back to you shortly.
        </div>
        <?php else: ?>
        <?php if (!empty($errors)): ?>
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0"><?php foreach ($errors as $err): ?><li><?= e($err) ?></li><?php endforeach; ?></ul>
        </div>
        <?php endif; ?>
        <div class="card border-0 shadow-sm rounded-4 p-4" style="max-width:600px;">
            <form method="POST" class="needs-validation" novalidate aria-label="Contact us">
                <div class="mb-3">
                    <label for="name" class="form-label fw-semibold">Your Name</label>
                    <input type="text" class="form-control" id="name" name="name"
                           value="<?= isset($_POST['name']) ? e($_POST['name']) : '' ?>" required>
                    <div class="invalid-feedback">Please enter your name.</div>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email Address</label>
                    <input type="email" class="form-control" id="email" name="email"
                           value="<?= isset($_POST['email']) ? e($_POST['email']) : '' ?>" required>
                    <div class="invalid-feedback">Please enter a valid email.</div>
                </div>
                <div class="mb-4">
                    <label for="message" class="form-label fw-semibold">Message</label>
                    <textarea class="form-control" id="message" name="message" rows="5"
                              required minlength="10" aria-describedby="msgHelp"><?= isset($_POST['message']) ? e($_POST['message']) : '' ?></textarea>
                    <div id="msgHelp" class="form-text">Minimum 10 characters.</div>
                    <div class="invalid-feedback">Message must be at least 10 characters.</div>
                </div>
                <button type="submit" class="btn btn-pm-primary px-5">Send Message</button>
            </form>
        </div>
        <?php endif; ?>
    </section>
</div>

<?php require_once 'includes/footer.php'; ?>
