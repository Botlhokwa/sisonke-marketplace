<?php
$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
require_once '../includes/admin_middleware.php';

// ── STATS ────────────────────────────────────────────────────────
$usersRow = $conn->query("
    SELECT
        COUNT(*)                               AS total,
        SUM(status = 'active')                 AS active,
        SUM(status = 'suspended')              AS suspended,
        SUM(status = 'banned')                 AS banned,
        SUM(DATE(created_at) = CURDATE())      AS new_today
    FROM users
")->fetch_assoc();

$productsRow = $conn->query("
    SELECT
        COUNT(*)                               AS total,
        SUM(status = 'pending')                AS pending,
        SUM(status = 'approved')               AS approved,
        SUM(status = 'rejected')               AS rejected,
        SUM(status = 'fraudulent')             AS fraudulent,
        SUM(DATE(created_at) = CURDATE())      AS today
    FROM products
")->fetch_assoc();

$ordersRow = $conn->query("
    SELECT
        COUNT(*)                               AS total,
        SUM(DATE(created_at) = CURDATE())      AS today
    FROM orders
")->fetch_assoc();

$catRow = $conn->query("SELECT COUNT(DISTINCT category) AS total FROM products")->fetch_assoc();

// ── RECENT DATA ───────────────────────────────────────────────────
$recentUsers = $conn->query("
    SELECT id, username, email, created_at, role, status
    FROM users ORDER BY created_at DESC LIMIT 6
");

$recentListings = $conn->query("
    SELECT p.id, p.title, p.category, p.created_at, p.status, u.username AS seller
    FROM products p
    JOIN users u ON u.id = p.user_id
    ORDER BY p.created_at DESC LIMIT 6
");

$recentOrders = $conn->query("
    SELECT o.id, o.total_amount, o.created_at, o.status AS order_status,
           u.username AS customer
    FROM orders o
    JOIN users u ON u.id = o.user_id
    ORDER BY o.created_at DESC LIMIT 6
");
?>

<!-- USERS STATS -->
<p class="stat-section-title">Users</p>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($usersRow['total']) ?></div><div class="stat-label">Total Users</div></div>
            <div class="stat-icon blue"><span class="material-symbols-outlined">group</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($usersRow['active']) ?></div><div class="stat-label">Active Users</div></div>
            <div class="stat-icon green"><span class="material-symbols-outlined">check_circle</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($usersRow['suspended']) ?></div><div class="stat-label">Suspended</div></div>
            <div class="stat-icon yellow"><span class="material-symbols-outlined">pause_circle</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($usersRow['banned']) ?></div><div class="stat-label">Banned</div></div>
            <div class="stat-icon red"><span class="material-symbols-outlined">block</span></div>
        </div>
    </div>
</div>

<!-- PRODUCTS STATS -->
<p class="stat-section-title">Products</p>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($productsRow['total']) ?></div><div class="stat-label">Total Products</div></div>
            <div class="stat-icon blue"><span class="material-symbols-outlined">inventory_2</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($productsRow['pending']) ?></div><div class="stat-label">Pending</div></div>
            <div class="stat-icon yellow"><span class="material-symbols-outlined">pending</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($productsRow['approved']) ?></div><div class="stat-label">Approved</div></div>
            <div class="stat-icon green"><span class="material-symbols-outlined">verified</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($productsRow['rejected']) ?></div><div class="stat-label">Rejected</div></div>
            <div class="stat-icon red"><span class="material-symbols-outlined">cancel</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($productsRow['fraudulent']) ?></div><div class="stat-label">Fraudulent</div></div>
            <div class="stat-icon orange"><span class="material-symbols-outlined">report</span></div>
        </div>
    </div>
</div>

<!-- ORDERS & MARKETPLACE STATS -->
<p class="stat-section-title">Orders & Marketplace</p>
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($ordersRow['total'] ?? 0) ?></div><div class="stat-label">Total Orders</div></div>
            <div class="stat-icon purple"><span class="material-symbols-outlined">receipt_long</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($ordersRow['today'] ?? 0) ?></div><div class="stat-label">Orders Today</div></div>
            <div class="stat-icon blue"><span class="material-symbols-outlined">today</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($catRow['total']) ?></div><div class="stat-label">Categories</div></div>
            <div class="stat-icon purple"><span class="material-symbols-outlined">category</span></div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-card-header">
            <div><div class="stat-value"><?= number_format($usersRow['new_today']) ?></div><div class="stat-label">New Users Today</div></div>
            <div class="stat-icon green"><span class="material-symbols-outlined">person_add</span></div>
        </div>
    </div>
</div>

<!-- RECENT ACTIVITY - stacked vertically below stats -->
<div class="activity-grid">

    <!-- Latest Registrations -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Latest Registrations</h2>
            <a href="users.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>User</th><th>Role</th><th>Status</th><th>Joined</th></tr></thead>
                <tbody>
                <?php if ($recentUsers && $recentUsers->num_rows > 0):
                    while ($u = $recentUsers->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($u['username']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= htmlspecialchars($u['email']) ?></small>
                    </td>
                    <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                    <td><span class="badge badge-<?= $u['status'] ?>"><?= $u['status'] ?></span></td>
                    <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($u['created_at'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4"><div class="empty-state"><span class="material-symbols-outlined">group_off</span><p>No users yet.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Latest Listings -->
    <div class="admin-card">
        <div class="admin-card-header">
            <h2>Latest Listings</h2>
            <a href="products.php" class="btn btn-secondary btn-sm">View All</a>
        </div>
        <div class="admin-table-wrap">
            <table class="admin-table">
                <thead><tr><th>Product</th><th>Seller</th><th>Status</th><th>Date</th></tr></thead>
                <tbody>
                <?php if ($recentListings && $recentListings->num_rows > 0):
                    while ($p = $recentListings->fetch_assoc()): ?>
                <tr>
                    <td>
                        <strong><?= htmlspecialchars($p['title']) ?></strong><br>
                        <small style="color:var(--text-muted)"><?= htmlspecialchars($p['category']) ?></small>
                    </td>
                    <td><?= htmlspecialchars($p['seller']) ?></td>
                    <td><span class="badge badge-<?= $p['status'] ?>"><?= $p['status'] ?></span></td>
                    <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($p['created_at'])) ?></td>
                </tr>
                <?php endwhile; else: ?>
                <tr><td colspan="4"><div class="empty-state"><span class="material-symbols-outlined">inventory_2</span><p>No products yet.</p></div></td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- Latest Orders - full width -->
<div class="admin-card">
    <div class="admin-card-header">
        <h2>Latest Orders</h2>
        <a href="orders.php" class="btn btn-secondary btn-sm">View All</a>
    </div>
    <div class="admin-table-wrap">
        <table class="admin-table">
            <thead><tr><th>Order ID</th><th>Customer</th><th>Amount</th><th>Status</th><th>Date</th></tr></thead>
            <tbody>
            <?php if ($recentOrders && $recentOrders->num_rows > 0):
                while ($o = $recentOrders->fetch_assoc()): ?>
            <tr>
                <td><span style="font-family:'DM Mono',monospace;font-size:13px">#<?= $o['id'] ?></span></td>
                <td><?= htmlspecialchars($o['customer']) ?></td>
                <td style="font-weight:700;color:var(--blue-light)">R<?= number_format($o['total_amount'], 2) ?></td>
                <td><span class="badge badge-<?= strtolower($o['order_status'] ?? 'pending') ?>"><?= $o['order_status'] ?? 'Pending' ?></span></td>
                <td style="color:var(--text-muted);font-size:12px"><?= date('d M Y', strtotime($o['created_at'])) ?></td>
            </tr>
            <?php endwhile; else: ?>
            <tr><td colspan="5"><div class="empty-state"><span class="material-symbols-outlined">receipt_long</span><p>No orders yet.</p></div></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</div><!-- /.admin-content -->
</div><!-- /.admin-main -->

<script>
function toggleSidebar() {
    document.getElementById('adminSidebar').classList.toggle('open');
}
</script>
</body>
</html>