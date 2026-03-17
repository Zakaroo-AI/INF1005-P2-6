<?php
// ============================================================
// admin/includes/sidebar.php — Admin Sidebar
// ============================================================
$adminPage = basename($_SERVER['PHP_SELF']);
?>
<nav class="admin-sidebar col-md-3 col-lg-2 d-md-block" aria-label="Admin navigation">
    <div class="px-3 py-3">
        <div class="text-warning fw-bold mb-4 fs-5">
            <i class="bi bi-shield-lock me-2"></i>Admin Panel
        </div>
        <ul class="nav flex-column">
            <li class="nav-item">
                <a class="nav-link <?= $adminPage==='index.php' ? 'active':'' ?>" href="/admin/index.php">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $adminPage==='users.php' ? 'active':'' ?>" href="/admin/users.php">
                    <i class="bi bi-people me-2"></i>Users
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $adminPage==='listings.php' ? 'active':'' ?>" href="/admin/listings.php">
                    <i class="bi bi-tags me-2"></i>Listings
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $adminPage==='orders.php' ? 'active':'' ?>" href="/admin/orders.php">
                    <i class="bi bi-bag me-2"></i>Orders
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $adminPage==='pokemon.php' ? 'active':'' ?>" href="/admin/pokemon.php">
                    <i class="bi bi-collection me-2"></i>Cards
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $adminPage==='disputes.php' ? 'active':'' ?>" href="/admin/disputes.php">
                    <i class="bi bi-flag me-2"></i>Disputes
                </a>
            </li>
            <li class="nav-item mt-3">
                <a class="nav-link text-muted" href="/index.php">
                    <i class="bi bi-arrow-left me-2"></i>Back to Site
                </a>
            </li>
        </ul>
    </div>
</nav>
