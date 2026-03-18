<?php
// ============================================================
// admin/index.php — Admin Dashboard
// ============================================================
if (session_status() === PHP_SESSION_NONE) session_start();
require_once '../config/db.php';
require_once '../includes/auth.php';
requireAdmin();
$pdo = getPDO();

$stats = [
    'users'    => $pdo->query("SELECT COUNT(*) FROM users WHERE role='trainer'")->fetchColumn(),
    'listings' => $pdo->query("SELECT COUNT(*) FROM listings WHERE status='active'")->fetchColumn(),
    'orders'   => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'revenue'  => $pdo->query("SELECT COALESCE(SUM(total_price),0) FROM orders")->fetchColumn(),
];

// Recent orders
$recentOrders = $pdo->query("
    SELECT o.order_id, o.total_price, o.status, o.created_at, u.username
    FROM orders o JOIN users u ON o.buyer_id = u.user_id
    ORDER BY o.created_at DESC LIMIT 5
")->fetchAll();

// Recent listings
$recentListings = $pdo->query("
    SELECT l.listing_id, l.title, l.price, l.status, u.username AS seller, c.card_name
    FROM listings l
    JOIN users u ON l.seller_id = u.user_id
    JOIN cards c ON l.card_id   = c.card_id
    ORDER BY l.created_at DESC LIMIT 5
")->fetchAll();

// Chart: Orders over last 7 days
$ordersChart = $pdo->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS count
    FROM orders
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY DATE(created_at)
    ORDER BY day
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Fill missing days with 0
$orderLabels = [];
$orderData   = [];
for ($i = 6; $i >= 0; $i--) {
    $day           = date('Y-m-d', strtotime("-$i days"));
    $orderLabels[] = date('D d/m', strtotime($day));
    $orderData[]   = (int)($ordersChart[$day] ?? 0);
}

// Chart: Listings by rarity
$rarityRows = $pdo->query("
    SELECT c.rarity, COUNT(*) AS count
    FROM listings l JOIN cards c ON l.card_id = c.card_id
    WHERE l.status = 'active'
    GROUP BY c.rarity
")->fetchAll();
$rarityLabels = array_column($rarityRows, 'rarity');
$rarityData   = array_map('intval', array_column($rarityRows, 'count'));

// Chart: Listings by typing
$typingRows = $pdo->query("
    SELECT c.typing, COUNT(*) AS count
    FROM listings l JOIN cards c ON l.card_id = c.card_id
    WHERE l.status = 'active'
    GROUP BY c.typing
    ORDER BY count DESC
")->fetchAll();
$typingLabels = array_column($typingRows, 'typing');
$typingData   = array_map('intval', array_column($typingRows, 'count'));

$pageTitle = 'Admin Dashboard';
require_once '../includes/header.php';
?>

<div class="container-fluid">
    <div class="row">
        <?php include 'sidebar.php'; ?>
        <div class="col-md-9 col-lg-10 px-4 py-4">
            <h1 class="h3 fw-bold mb-4">Dashboard</h1>

            <!-- Stat Cards -->
            <div class="row g-4 mb-5">
                <?php
                $cards = [
                    ['label'=>'Trainers',       'value'=>$stats['users'],    'icon'=>'people',        'color'=>'primary'],
                    ['label'=>'Active Listings', 'value'=>$stats['listings'], 'icon'=>'tags',          'color'=>'success'],
                    ['label'=>'Total Orders',    'value'=>$stats['orders'],   'icon'=>'bag',           'color'=>'warning'],
                    ['label'=>'Total Revenue',   'value'=>'$'.number_format($stats['revenue'],2), 'icon'=>'cash-stack','color'=>'danger'],
                ];
                foreach ($cards as $card): ?>
                <div class="col-sm-6 col-xl-3">
                    <div class="card stat-card border-0 p-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-muted small"><?= $card['label'] ?></div>
                                <div class="fs-3 fw-bold mt-1 text-<?= $card['color'] ?>"><?= $card['value'] ?></div>
                            </div>
                            <div class="fs-1 text-<?= $card['color'] ?> opacity-25">
                                <i class="bi bi-<?= $card['icon'] ?>"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="row g-4">
                <!-- Recent Orders -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-dark text-white fw-bold py-3">Recent Orders</div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr><th>Order</th><th>Trainer</th><th>Total</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentOrders as $o): ?>
                                    <tr>
                                        <td><a href="/admin/orders.php">#<?= str_pad($o['order_id'],4,'0',STR_PAD_LEFT) ?></a></td>
                                        <td><?= e($o['username']) ?></td>
                                        <td class="fw-bold text-primary">$<?= number_format($o['total_price'],2) ?></td>
                                        <td><span class="badge bg-secondary"><?= e($o['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end"><a href="/admin/orders.php" class="btn btn-sm btn-outline-primary">View All</a></div>
                    </div>
                </div>

                <!-- Recent Listings -->
                <div class="col-lg-6">
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-dark text-white fw-bold py-3">Recent Listings</div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0 small">
                                <thead class="table-light">
                                    <tr><th>Card</th><th>Seller</th><th>Price</th><th>Status</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recentListings as $l): ?>
                                    <tr>
                                        <td><?= e($l['card_name']) ?></td>
                                        <td><?= e($l['seller']) ?></td>
                                        <td class="fw-bold text-primary">$<?= number_format($l['price'],2) ?></td>
                                        <td><span class="badge bg-<?= $l['status']==='active'?'success':'secondary' ?>"><?= e($l['status']) ?></span></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="card-footer text-end"><a href="/admin/listings.php" class="btn btn-sm btn-outline-primary">View All</a></div>
                    </div>
                </div>
            </div>

            <!-- Charts -->
            <div class="row g-4 mt-2">
                <!-- Orders last 7 days -->
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h2 class="h6 fw-bold mb-3">Orders — Last 7 Days</h2>
                        <canvas id="ordersChart" height="100"></canvas>
                    </div>
                </div>
                <!-- Listings by rarity -->
                <div class="col-lg-4">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h2 class="h6 fw-bold mb-3">Listings by Rarity</h2>
                        <canvas id="rarityChart"></canvas>
                    </div>
                </div>
                <!-- Listings by typing -->
                <div class="col-lg-12">
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h2 class="h6 fw-bold mb-3">Listings by Typing</h2>
                        <canvas id="typingChart" height="60"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const orderLabels = <?= json_encode($orderLabels) ?>;
const orderData   = <?= json_encode($orderData) ?>;
const rarityLabels = <?= json_encode($rarityLabels) ?>;
const rarityData   = <?= json_encode($rarityData) ?>;
const typingLabels = <?= json_encode($typingLabels) ?>;
const typingData   = <?= json_encode($typingData) ?>;

// Orders line chart
new Chart(document.getElementById('ordersChart'), {
    type: 'line',
    data: {
        labels: orderLabels,
        datasets: [{
            label: 'Orders',
            data: orderData,
            borderColor: '#0d6efd',
            backgroundColor: 'rgba(13,110,253,0.1)',
            tension: 0.4,
            fill: true,
            pointBackgroundColor: '#0d6efd'
        }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});

// Rarity doughnut chart
const rarityColors = ['#6c757d','#198754','#0d6efd','#6f42c1','#fd7e14','#ffc107'];
new Chart(document.getElementById('rarityChart'), {
    type: 'doughnut',
    data: {
        labels: rarityLabels,
        datasets: [{ data: rarityData, backgroundColor: rarityColors, borderWidth: 2 }]
    },
    options: { plugins: { legend: { position: 'bottom' } } }
});

// Typing bar chart
const typingColors = {
    'Fire':'#fd7e14','Water':'#0d6efd','Grass':'#198754','Lightning':'#ffc107',
    'Psychic':'#9c6dd8','Fighting':'#dc3545','Darkness':'#343a40','Metal':'#6c757d',
    'Colorless':'#adb5bd','Dragon':'#6f42c1','Fairy':'#e91e8c'
};
const typingBg = typingLabels.map(t => typingColors[t] ?? '#0d6efd');
new Chart(document.getElementById('typingChart'), {
    type: 'bar',
    data: {
        labels: typingLabels,
        datasets: [{ label: 'Listings', data: typingData, backgroundColor: typingBg, borderRadius: 6 }]
    },
    options: { plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } } }
});
</script>

<?php require_once '../includes/footer.php'; ?>
