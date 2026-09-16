<?php
$pageTitle  = 'Reports & Analytics';
$activePage = 'reports';
require_once '../includes/admin_middleware.php';

$userStatuses = [];
$res = $conn->query("SELECT status, COUNT(*) AS c FROM users GROUP BY status");
while ($r = $res->fetch_assoc()) $userStatuses[$r['status']] = (int)$r['c'];

$catLabels = [];
$catCounts = [];
$res = $conn->query("SELECT category, COUNT(*) AS c FROM products WHERE status='approved' GROUP BY category ORDER BY c DESC");
while ($r = $res->fetch_assoc()) {
    $catLabels[] = $r['category'];
    $catCounts[] = (int)$r['c'];
}

$orderDays    = [];
$orderAmounts = [];
$res = $conn->query("
    SELECT DATE(created_at) AS day, COUNT(*) AS cnt, SUM(total_amount) AS revenue
    FROM orders
    WHERE MONTH(created_at) = MONTH(CURDATE()) AND YEAR(created_at) = YEAR(CURDATE())
    GROUP BY DATE(created_at)
    ORDER BY day ASC
");
while ($r = $res->fetch_assoc()) {
    $orderDays[]    = date('d M', strtotime($r['day']));
    $orderAmounts[] = (float)$r['revenue'];
}

$topProducts = $conn->query("
    SELECT p.title, SUM(oi.quantity) AS units_sold, SUM(oi.quantity * oi.price) AS revenue
    FROM order_items oi
    JOIN products p ON p.id = oi.product_id
    GROUP BY oi.product_id
    ORDER BY units_sold DESC
    LIMIT 5
");

// ── DATA: New users per month (last 6 months) ─────────────────────
$userMonths  = [];
$userCounts  = [];
$res = $conn->query("
    SELECT DATE_FORMAT(created_at, '%b %Y') AS mo, COUNT(*) AS c
    FROM users
    WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 6 MONTH)
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY MIN(created_at) ASC
");
while ($r = $res->fetch_assoc()) {
    $userMonths[] = $r['mo'];
    $userCounts[] = (int)$r['c'];
}

// ── Summary stats ─────────────────────────────────────────────────

$monthOrders = $conn->query("
    SELECT COUNT(*) AS c FROM orders
    WHERE MONTH(created_at)=MONTH(CURDATE()) AND YEAR(created_at)=YEAR(CURDATE())
")->fetch_assoc()['c'];
?>

    <!-- SUMMARY CARDS -->
    <div class="stats-grid" style="margin-bottom:28px;">
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= $monthOrders ?></div>
                    <div class="stat-label">Orders This Month</div>
                </div>
                <div class="stat-icon blue"><span class="material-symbols-outlined">receipt_long</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= array_sum($userStatuses) ?></div>
                    <div class="stat-label">Total Users</div>
                </div>
                <div class="stat-icon purple"><span class="material-symbols-outlined">group</span></div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-card-header">
                <div>
                    <div class="stat-value"><?= array_sum($catCounts) ?></div>
                    <div class="stat-label">Approved Products</div>
                </div>
                <div class="stat-icon orange"><span class="material-symbols-outlined">inventory_2</span></div>
            </div>
        </div>
    </div>

    <!-- CHARTS ROW 1 -->
    <div class="charts-grid">

        <!-- Products per Category Doughnut -->
        <div class="chart-card">
            <h3>Products per Category</h3>
            <canvas id="catChart" height="220"></canvas>
        </div>

        <!-- User Statuses Doughnut -->
        <div class="chart-card">
            <h3>User Status Breakdown</h3>
            <canvas id="userChart" height="220"></canvas>
        </div>

        <!-- New Users Bar -->
        <div class="chart-card">
            <h3>New Users (Last 6 Months)</h3>
            <canvas id="userGrowthChart" height="220"></canvas>
        </div>

    </div>

    <!-- TOP SELLING PRODUCTS TABLE -->
    <div class="admin-card" style="margin-bottom:28px;">
        <div class="admin-card-header">
            <h2>Top Selling Products</h2>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th>Rank</th>
                        <th>Product</th>
                        <th>Units Sold</th>
                        <th>Revenue</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $rank = 1;
                if ($topProducts && $topProducts->num_rows > 0):
                    while ($tp = $topProducts->fetch_assoc()):
                ?>
                <tr>
                    <td>
                        <?php if ($rank === 1): ?>
                            <span style="font-size:20px">🥇</span>
                        <?php elseif ($rank === 2): ?>
                            <span style="font-size:20px">🥈</span>
                        <?php elseif ($rank === 3): ?>
                            <span style="font-size:20px">🥉</span>
                        <?php else: ?>
                            <span style="color:var(--text-muted);font-weight:700">#<?= $rank ?></span>
                        <?php endif; ?>
                    </td>
                    <td><strong><?= htmlspecialchars($tp['title']) ?></strong></td>
                    <td><?= number_format($tp['units_sold']) ?> units</td>
                    <td style="font-weight:700;color:var(--blue-light)">R<?= number_format($tp['revenue'], 2) ?></td>
                </tr>
                <?php $rank++; endwhile; else: ?>
                <tr><td colspan="4">
                    <div class="empty-state">
                        <span class="material-symbols-outlined">bar_chart</span>
                        <p>No sales data yet.</p>
                    </div>
                </td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div></div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Shared chart defaults for dark theme
Chart.defaults.color          = '#94a3b8';
Chart.defaults.borderColor    = 'rgba(255,255,255,0.07)';
Chart.defaults.font.family    = "'DM Sans', sans-serif";

const blue   = '#0057ff';
const green  = '#22c55e';
const yellow = '#f59e0b';
const red    = '#ef4444';
const purple = '#a855f7';
const orange = '#f97316';

//Category Doughnut
new Chart(document.getElementById('catChart'), {
    type: 'doughnut',
    data: {
        labels:   <?= json_encode($catLabels) ?>,
        datasets: [{
            data:            <?= json_encode($catCounts) ?>,
            backgroundColor: [blue, green, yellow, red, purple, orange, '#06b6d4', '#ec4899'],
            borderWidth:     2,
            borderColor:     '#111827',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
    }
});

//User Status Doughnut
new Chart(document.getElementById('userChart'), {
    type: 'doughnut',
    data: {
        labels:   ['Active', 'Suspended', 'Banned'],
        datasets: [{
            data:            [
                <?= (int)($userStatuses['active']    ?? 0) ?>,
                <?= (int)($userStatuses['suspended'] ?? 0) ?>,
                <?= (int)($userStatuses['banned']    ?? 0) ?>
            ],
            backgroundColor: [green, yellow, red],
            borderWidth:     2,
            borderColor:     '#111827',
        }]
    },
    options: {
        responsive: true,
        cutout: '65%',
        plugins: { legend: { position: 'bottom' } }
    }
});

//User Growth Bar
new Chart(document.getElementById('userGrowthChart'), {
    type: 'bar',
    data: {
        labels:   <?= json_encode($userMonths) ?>,
        datasets: [{
            label:           'New Users',
            data:            <?= json_encode($userCounts) ?>,
            backgroundColor: 'rgba(0,87,255,0.7)',
            borderRadius:    6,
        }]
    },
    options: {
        responsive: true,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { precision: 0 } } }
    }
});
</script>
<script>
function toggleSidebar() { document.getElementById('adminSidebar').classList.toggle('open'); }
</script>
</body>
</html>