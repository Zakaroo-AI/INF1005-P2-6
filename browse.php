<?php
// ============================================================
// browse.php — Browse All Listings
// ============================================================
$pageTitle = 'Browse Listings';
require_once 'includes/header.php';
$pdo = getPDO();

// --- Filters from GET ---
$search    = trim($_GET['q']         ?? '');
$typing    = trim($_GET['typing']    ?? '');
$rarity    = trim($_GET['rarity']    ?? '');
$condition = trim($_GET['condition'] ?? '');
$language  = trim($_GET['language']  ?? '');
$sort      = trim($_GET['sort']      ?? 'newest');
$minPrice  = isset($_GET['min']) && $_GET['min'] !== '' ? (float)$_GET['min'] : 0;
$maxPrice  = isset($_GET['max']) && $_GET['max'] !== '' ? (float)$_GET['max'] : 99999;

// --- Build query ---
$where  = ["l.status = 'active'"];
$params = [];

if ($search) {
    $where[]  = "(c.card_name LIKE ? OR l.title LIKE ? OR c.set_name LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
if ($typing) {
    $where[]  = "c.typing = ?";
    $params[] = $typing;
}
if ($rarity) {
    $where[]  = "c.rarity = ?";
    $params[] = $rarity;
}
if ($condition) {
    $where[]  = "l.condition_grade = ?";
    $params[] = $condition;
}
if ($language) {
    $where[]  = "l.language = ?";
    $params[] = $language;
}
$where[]  = "l.price >= ?";
$params[] = $minPrice;
$where[]  = "l.price <= ?";
$params[] = $maxPrice;

$orderBy = match($sort) {
    'price_asc'  => 'l.price ASC',
    'price_desc' => 'l.price DESC',
    'name_asc'   => 'c.card_name ASC',
    default      => 'l.created_at DESC'
};

$sql = "
    SELECT l.*, c.card_name, c.set_name, c.card_number, c.typing,
           c.rarity, c.image_url, u.username AS seller_name
    FROM listings l
    JOIN cards c ON l.card_id = c.card_id
    JOIN users  u ON l.seller_id = u.user_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY $orderBy
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$listings = $stmt->fetchAll();

$typings    = ['Fire','Water','Grass','Lightning','Psychic','Fighting','Darkness','Metal','Dragon','Colorless','Fairy'];
$rarities   = ['Common','Uncommon','Rare','Holo Rare','Double Rare','Ultra Rare','Illustration Rare','Special Illustration Rare','Hyper Rare','Secret Rare','Ace Spec Rare','Shiny Rare','Shiny Ultra Rare','Promo'];
$conditions = ['PSA 1','PSA 2','PSA 3','PSA 4','PSA 5','PSA 6','PSA 7','PSA 8','PSA 9','PSA 10'];
$languages  = ['English','Japanese','Korean','Chinese','German','French','Spanish','Italian'];
?>

<div class="container py-5">
    <div class="row g-4">

        <!-- Sidebar Filters -->
        <aside class="col-lg-3" aria-label="Listing filters">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top:80px;">
                <h2 class="h6 fw-bold mb-3" style="color:var(--pm-blue);">
                    <i class="bi bi-funnel me-2"></i>Filter Listings
                </h2>
                <form method="GET" id="filterForm">
                    <?php if ($search): ?>
                    <input type="hidden" name="q" value="<?= e($search) ?>">
                    <?php endif; ?>

                    <!-- Typing -->
                    <label for="filterTyping" class="form-label small fw-semibold">Card Type</label>
                    <select class="form-select form-select-sm mb-3" name="typing" id="filterTyping" onchange="this.form.submit()">
                        <option value="">All Types</option>
                        <?php foreach ($typings as $t): ?>
                        <option value="<?= e($t) ?>" <?= $typing === $t ? 'selected' : '' ?>><?= e($t) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Rarity -->
                    <label for="filterRarity" class="form-label small fw-semibold">Rarity</label>
                    <select class="form-select form-select-sm mb-3" name="rarity" id="filterRarity" onchange="this.form.submit()">
                        <option value="">All Rarities</option>
                        <?php foreach ($rarities as $r): ?>
                        <option value="<?= e($r) ?>" <?= $rarity === $r ? 'selected' : '' ?>><?= e($r) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Condition -->
                    <label for="filterCondition" class="form-label small fw-semibold">Condition</label>
                    <select class="form-select form-select-sm mb-3" name="condition" id="filterCondition" onchange="this.form.submit()">
                        <option value="">All Conditions</option>
                        <?php foreach ($conditions as $c): ?>
                        <option value="<?= e($c) ?>" <?= $condition === $c ? 'selected' : '' ?>><?= e($c) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Language -->
                    <label for="filterLanguage" class="form-label small fw-semibold">Language</label>
                    <select class="form-select form-select-sm mb-3" name="language" id="filterLanguage" onchange="this.form.submit()">
                        <option value="">All Languages</option>
                        <?php foreach ($languages as $lang): ?>
                        <option value="<?= e($lang) ?>" <?= $language === $lang ? 'selected' : '' ?>><?= e($lang) ?></option>
                        <?php endforeach; ?>
                    </select>

                    <!-- Sort -->
                    <label for="filterSort" class="form-label small fw-semibold">Sort By</label>
                    <select class="form-select form-select-sm mb-3" name="sort" id="filterSort" onchange="this.form.submit()">
                        <option value="newest"     <?= $sort==='newest'     ? 'selected':'' ?>>Newest First</option>
                        <option value="price_asc"  <?= $sort==='price_asc'  ? 'selected':'' ?>>Price: Low to High</option>
                        <option value="price_desc" <?= $sort==='price_desc' ? 'selected':'' ?>>Price: High to Low</option>
                        <option value="name_asc"   <?= $sort==='name_asc'   ? 'selected':'' ?>>Name A–Z</option>
                    </select>

                    <!-- Price Range -->
                    <label class="form-label small fw-semibold">Price Range</label>
                    <div class="d-flex gap-2 mb-3">
                        <input type="number" class="form-control form-control-sm" name="min"
                               placeholder="Min" value="<?= $minPrice > 0 ? $minPrice : '' ?>" min="0">
                        <input type="number" class="form-control form-control-sm" name="max"
                               placeholder="Max" value="<?= $maxPrice < 99999 ? $maxPrice : '' ?>" min="0">
                    </div>
                    <button type="submit" class="btn btn-pm-primary btn-sm w-100">Apply</button>

                    <?php if ($typing || $rarity || $condition || $language || $search || $minPrice || $maxPrice < 99999): ?>
                    <a href="/browse.php" class="btn btn-outline-secondary btn-sm w-100 mt-2">Clear Filters</a>
                    <?php endif; ?>
                </form>
            </div>
        </aside>

        <!-- Listings Grid -->
        <div class="col-lg-9">
            <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
                <h1 class="h4 fw-bold mb-0">
                    <?php if ($search): ?>Results for "<?= e($search) ?>"
                    <?php elseif ($typing): ?><?= e($typing) ?> Type Cards
                    <?php else: ?>All Listings<?php endif; ?>
                </h1>
                <span class="badge bg-secondary"><?= count($listings) ?> listing<?= count($listings) !== 1 ? 's' : '' ?> found</span>
            </div>

            <?php if (empty($listings)): ?>
                <div class="text-center py-5">
                    <i class="bi bi-search display-1 text-muted"></i>
                    <p class="mt-3 text-muted">No listings found. Try adjusting your filters.</p>
                    <a href="/browse.php" class="btn btn-pm-primary mt-2">Clear Filters</a>
                </div>
            <?php else: ?>
            <div class="row row-cols-1 row-cols-md-2 row-cols-xl-3 g-4">
                <?php foreach ($listings as $listing): ?>
                <div class="col">
                    <article class="card listing-card h-100" aria-label="<?= e($listing['title']) ?>">
                        <!-- Wishlist button -->
                        <?php if (isLoggedIn()): ?>
                        <button class="btn btn-light btn-sm wishlist-btn position-absolute top-0 end-0 m-2 rounded-circle"
                                data-listing="<?= $listing['listing_id'] ?>"
                                aria-label="Add to wishlist" aria-pressed="false"
                                title="Add to wishlist" style="z-index:2;">
                            <i class="bi bi-heart"></i>
                        </button>
                        <?php endif; ?>

                        <a href="/listing.php?id=<?= $listing['listing_id'] ?>">
                            <img src="<?= e($listing['image_url']) ?>"
                                 class="card-img-top"
                                 alt="<?= e($listing['card_name']) ?>"
                                 loading="lazy">
                        </a>
                        <div class="card-body d-flex flex-column">
                            <div class="mb-2">
                                <span class="type-badge" style="background:<?= typeBadgeColor($listing['typing']) ?>">
                                    <?= e($listing['typing']) ?>
                                </span>
                                <span class="badge ms-1 rarity-<?= strtolower(str_replace(' ','-', e($listing['rarity']))) ?>">
                                    <?= e($listing['rarity']) ?>
                                </span>
                                <span class="badge ms-1 condition-<?= strtolower(str_replace(' ','-', e($listing['condition_grade']))) ?>">
                                    <?= e($listing['condition_grade']) ?>
                                </span>
                            </div>
                            <h3 class="card-title fs-6 fw-bold">
                                <a href="/listing.php?id=<?= $listing['listing_id'] ?>" class="text-decoration-none text-dark">
                                    <?= e($listing['title']) ?>
                                </a>
                            </h3>
                            <p class="text-muted small">by <?= e($listing['seller_name']) ?></p>
                            <p class="text-muted small mb-1"><?= e($listing['set_name']) ?> · <?= e($listing['condition_grade']) ?> · <?= e($listing['language']) ?></p>
                            <div class="mt-auto d-flex justify-content-between align-items-center">
                                <span class="fs-5 fw-bold text-primary">$<?= number_format($listing['price'], 2) ?></span>
                                <a href="/listing.php?id=<?= $listing['listing_id'] ?>"
                                   class="btn btn-pm-primary btn-sm">View</a>
                            </div>
                        </div>
                    </article>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>
