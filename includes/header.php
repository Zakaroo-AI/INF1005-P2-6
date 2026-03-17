<?php
// ============================================================
// includes/header.php — Common Header & Navbar
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth.php';

$cartCount     = getCartCount();
$wishlistCount = getWishlistCount();
$currentPage   = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($pageTitle) ? e($pageTitle) . ' — PokéMart Global' : 'PokéMart Global' ?></title>
    <!-- Bootstrap 5 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>

<!-- Skip navigation for keyboard/screen reader users -->
<a class="visually-hidden-focusable" href="#main-content">Skip to main content</a>

<!-- ===== NAVBAR ===== -->
<nav class="navbar navbar-expand-lg navbar-dark pm-navbar fixed-top" aria-label="Main navigation">
    <div class="container">
        <!-- Brand -->
        <a class="navbar-brand fw-bold fs-4" href="/index.php">
            <img src="/assets/images/pokeball.svg" alt="" width="30" height="30" class="me-2">
            PokéMart <span class="text-warning">Global</span>
        </a>

        <!-- Mobile toggle -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav"
                aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="mainNav">
            <!-- Left links -->
            <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'index.php' ? 'active' : '' ?>"
                       href="/index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>>
                       Home
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'browse.php' ? 'active' : '' ?>"
                       href="/browse.php">Browse</a>
                </li>
                <?php if (isLoggedIn()): ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'create-listing.php' ? 'active' : '' ?>"
                       href="/create-listing.php">
                        Sell
                    </a>
                </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link <?= $currentPage === 'about.php' ? 'active' : '' ?>"
                       href="/about.php">About Us</a>
                </li>
            </ul>

            <!-- Search form -->
            <form class="d-flex me-3" action="/browse.php" method="GET" role="search">
                <div class="input-group">
                    <input class="form-control form-control-sm" type="search" name="q"
                           placeholder="Search cards..." aria-label="Search cards"
                           value="<?= isset($_GET['q']) ? e($_GET['q']) : '' ?>"
                           id="searchInput" autocomplete="off">
                    <button class="btn btn-warning btn-sm" type="submit" aria-label="Submit search">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
                <!-- Autocomplete dropdown -->
                <ul id="autocomplete-list" class="list-group position-absolute mt-5 shadow" style="z-index:9999;display:none;min-width:220px;"></ul>
            </form>

            <!-- Right links -->
            <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">
                <?php if (isLoggedIn()): ?>
                    <!-- Cart -->
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="/cart.php" aria-label="Shopping cart">
                            <i class="bi bi-cart3 fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" id="cart-badge"
                                  <?= $cartCount === 0 ? 'style="display:none"' : '' ?>>
                                <?= $cartCount ?>
                            </span>
                        </a>
                    </li>
                    <!-- Wishlist -->
                    <li class="nav-item me-2">
                        <a class="nav-link position-relative" href="/wishlist.php" aria-label="Wishlist">
                            <i class="bi bi-heart fs-5"></i>
                            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                                  <?= $wishlistCount === 0 ? 'style="display:none"' : '' ?>>
                                <?= $wishlistCount ?>
                            </span>
                        </a>
                    </li>
                    <!-- User menu -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false" id="userMenu">
                            <i class="bi bi-person-circle me-1"></i>
                            <?= e($_SESSION['username']) ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="/profile.php"><i class="bi bi-person me-2"></i>My Profile</a></li>
                            <li><a class="dropdown-item" href="/my-listings.php"><i class="bi bi-tags me-2"></i>My Listings</a></li>
                            <li><a class="dropdown-item" href="/seller-orders.php"><i class="bi bi-shop me-2"></i>My Sales</a></li>
                            <li><a class="dropdown-item" href="/orders.php"><i class="bi bi-bag me-2"></i>My Orders</a></li>
                            <?php if (isAdmin()): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-danger" href="/admin/index.php"><i class="bi bi-shield-lock me-2"></i>Admin Panel</a></li>
                            <?php endif; ?>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="/logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link" href="/login.php">Login</a>
                    </li>
                    <li class="nav-item">
                        <a class="btn btn-warning btn-sm ms-2" href="/register.php">Register</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>

<!-- Flash message -->
<?php if (isset($_SESSION['flash'])): ?>
<div class="container mt-3">
    <div class="alert alert-<?= e($_SESSION['flash']['type']) ?> alert-dismissible fade show" role="alert">
        <?= e($_SESSION['flash']['message']) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<main id="main-content">
