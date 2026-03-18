<?php
// ============================================================
// includes/auth.php — Authentication Helpers
// ============================================================

function isLoggedIn(): bool {
    return isset($_SESSION['user_id']);
}

function isAdmin(): bool {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
        exit;
    }
}

function requireAdmin(): void {
    requireLogin();
    if (!isAdmin()) {
        header('Location: /index.php');
        exit;
    }
}

function getCurrentUser(): ?array {
    if (!isLoggedIn()) return null;
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch() ?: null;
}

function getCartCount(): int {
    if (!isLoggedIn()) return 0;
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT COALESCE(SUM(quantity), 0) FROM cart WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

function getWishlistCount(): int {
    if (!isLoggedIn()) return 0;
    $pdo  = getPDO();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM wishlist WHERE user_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return (int)$stmt->fetchColumn();
}

// Sanitize output — always use this before echoing user data
function e(?string $str): string {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

// Type badge colour mapping
function typeBadgeColor(string $type): string {
    $colors = [
        'Fire'      => '#FF6B35', 'Water'     => '#4A90D9', 'Grass'     => '#48A744',
        'Lightning' => '#F5C518', 'Psychic'   => '#F95587', 'Ghost'     => '#735797',
        'Dragon'    => '#6F35FC', 'Darkness'  => '#705746', 'Fighting'  => '#C22E28',
        'Metal'     => '#B7B7CE', 'Colorless' => '#A8A878', 'Fairy'     => '#D685AD',
    ];
    return $colors[$type] ?? '#888888';
}

function rarityBadgeColor(string $rarity): string {
    return match($rarity) {
        'Common'    => '#6c757d',
        'Rare'      => '#0d6efd',
        'Epic'      => '#6f42c1',
        'Legendary' => '#fd7e14',
        default     => '#6c757d'
    };
}
