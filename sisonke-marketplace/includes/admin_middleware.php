<?php
// includes/admin_middleware.php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../db.php';

requireLogin();
requireAdmin();

// Update last_login once per session
if (!isset($_SESSION['_admin_ll_updated'])) {
    $uid = (int)$_SESSION['user_id'];
    $now = date('Y-m-d H:i:s');
    $upd = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
    $upd->bind_param("si", $now, $uid);
    $upd->execute();
    $upd->close();
    $_SESSION['_admin_ll_updated'] = true;
}

// Pending products badge
$pendingProductsCount = 0;
$ppRes = $conn->query("SELECT COUNT(*) AS c FROM products WHERE status = 'pending'");
if ($ppRes) $pendingProductsCount = (int)$ppRes->fetch_assoc()['c'];

$pageTitle  = $pageTitle  ?? 'Admin';
$activePage = $activePage ?? '';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> – Sisonke Admin</title>
    <link rel="stylesheet" href="../admin/admin.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
</head>
<body class="admin-body">

<!-- SIDEBAR -->
<aside class="admin-sidebar" id="adminSidebar">
    <div class="sidebar-brand">
        <div class="brand-icon">S</div>
        <div class="brand-text">
            <span class="brand-name">Sisonke</span>
            <span class="brand-sub">Admin Panel</span>
        </div>
    </div>
    <nav class="sidebar-nav">
        <a href="../admin/dashboard.php" class="nav-item <?= $activePage==='dashboard'?'active':'' ?>">
            <span class="material-symbols-outlined">dashboard</span><span>Dashboard</span>
        </a>
        <a href="../admin/users.php" class="nav-item <?= $activePage==='users'?'active':'' ?>">
            <span class="material-symbols-outlined">group</span><span>Users</span>
        </a>
        <a href="../admin/products.php" class="nav-item <?= $activePage==='products'?'active':'' ?>">
            <span class="material-symbols-outlined">inventory_2</span><span>Products</span>
            <?php if ($pendingProductsCount > 0): ?>
                <span class="nav-badge"><?= $pendingProductsCount ?></span>
            <?php endif; ?>
        </a>
        <a href="../admin/orders.php" class="nav-item <?= $activePage==='orders'?'active':'' ?>">
            <span class="material-symbols-outlined">receipt_long</span><span>Orders</span>
        </a>
        <a href="../admin/reports.php" class="nav-item <?= $activePage==='reports'?'active':'' ?>">
            <span class="material-symbols-outlined">bar_chart</span><span>Reports</span>
        </a>
        <a href="../admin/settings.php" class="nav-item <?= $activePage==='settings'?'active':'' ?>">
            <span class="material-symbols-outlined">settings</span><span>Settings</span>
        </a>
    </nav>
    <div class="sidebar-footer">
        <div class="admin-profile">
            <div class="admin-avatar"><?= strtoupper(substr($_SESSION['username'], 0, 1)) ?></div>
            <div class="admin-info">
                <span class="admin-name"><?= htmlspecialchars($_SESSION['username']) ?></span>
                <span class="admin-role">Administrator</span>
            </div>
        </div>
        <a href="../logout.php" class="logout-btn" title="Logout">
            <span class="material-symbols-outlined">logout</span>
        </a>
    </div>
</aside>

<!-- MAIN WRAPPER -->
<div class="admin-main" id="adminMain">
    <!-- TOP BAR -->
    <header class="admin-topbar">
        <button class="sidebar-toggle" onclick="toggleSidebar()">
            <span class="material-symbols-outlined">menu</span>
        </button>
        <div class="topbar-title">
            <h1><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="breadcrumb">Admin / <?= htmlspecialchars($pageTitle) ?></p>
        </div>
        <div class="topbar-actions">
            <a href="../index.php" class="view-site-btn">
                <span class="material-symbols-outlined">open_in_new</span>
                View Site
            </a>
        </div>
    </header>
    <!-- PAGE CONTENT -->
    <div class="admin-content">