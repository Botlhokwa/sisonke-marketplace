<?php
session_start();
require 'db.php';

// Redirect if no order was just placed
if (!isset($_SESSION['last_order_id'])) {
    header("Location: index.php");
    exit;
}

$order_id    = (int) $_SESSION['last_order_id'];
$order_total = $_SESSION['last_order_total'];

// Fetch order details
$stmt = $conn->prepare("
    SELECT o.*, 
           DATE_FORMAT(o.created_at, '%d %M %Y') AS formatted_date
    FROM orders o
    WHERE o.id = ? AND o.user_id = ?
");
$stmt->bind_param("ii", $order_id, $_SESSION['user_id']);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch order items
$items_stmt = $conn->prepare("
    SELECT * FROM order_items WHERE order_id = ?
");
$items_stmt->bind_param("i", $order_id);
$items_stmt->execute();
$order_items = $items_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$items_stmt->close();

// Clear session order data after reading
unset($_SESSION['last_order_id']);
unset($_SESSION['last_order_total']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Placed - Sisonke Marketplace</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        /* ---- SUCCESS PAGE STYLES ---- */
        .success-wrapper {
            max-width: 780px;
            margin: 50px auto;
            padding: 0 5% 80px;
        }

        /* Animated checkmark banner */
        .success-banner {
            background: linear-gradient(135deg, #0057ff, #0037a3);
            border-radius: 25px;
            padding: 55px 40px;
            text-align: center;
            color: white;
            margin-bottom: 35px;
            position: relative;
            overflow: hidden;
        }
        .success-banner::before {
            content: '';
            position: absolute;
            top: -60px; right: -60px;
            width: 220px; height: 220px;
            background: rgba(255,255,255,0.06);
            border-radius: 50%;
        }
        .success-banner::after {
            content: '';
            position: absolute;
            bottom: -80px; left: -40px;
            width: 280px; height: 280px;
            background: rgba(255,255,255,0.04);
            border-radius: 50%;
        }
        .checkmark-circle {
            width: 90px;
            height: 90px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            position: relative;
            z-index: 1;
            animation: popIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
        }
        .checkmark-circle .material-symbols-outlined {
            font-size: 52px;
            color: #0057ff;
        }
        @keyframes popIn {
            0%   { transform: scale(0); opacity: 0; }
            100% { transform: scale(1); opacity: 1; }
        }
        .success-banner h1 {
            font-size: 32px;
            margin-bottom: 10px;
            position: relative;
            z-index: 1;
        }
        .success-banner p {
            font-size: 16px;
            opacity: 0.85;
            position: relative;
            z-index: 1;
        }
        .order-ref {
            display: inline-block;
            margin-top: 18px;
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.3);
            padding: 10px 22px;
            border-radius: 30px;
            font-size: 15px;
            font-weight: 700;
            letter-spacing: 1px;
            position: relative;
            z-index: 1;
        }

        /* Order details card */
        .order-card {
            background: white;
            border-radius: 22px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            overflow: hidden;
            margin-bottom: 25px;
        }
        .order-card-header {
            padding: 22px 28px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .order-card-header .material-symbols-outlined {
            color: #0057ff;
            font-size: 24px;
        }
        .order-card-header h3 {
            font-size: 17px;
            color: #222;
        }
        .order-card-body {
            padding: 24px 28px;
        }

        /* Info grid */
        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px 30px;
        }
        .info-item label {
            font-size: 12px;
            color: #999;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 5px;
        }
        .info-item span {
            font-size: 15px;
            color: #222;
            font-weight: 600;
        }

        /* Order items list */
        .order-item-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 14px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .order-item-row:last-child {
            border-bottom: none;
        }
        .order-item-left {
            display: flex;
            align-items: center;
            gap: 14px;
        }
        .item-qty-badge {
            background: #e8f0ff;
            color: #0057ff;
            font-size: 13px;
            font-weight: 700;
            padding: 4px 10px;
            border-radius: 20px;
            white-space: nowrap;
        }
        .order-item-name {
            font-size: 15px;
            color: #333;
            font-weight: 500;
        }
        .order-item-price {
            font-size: 15px;
            font-weight: 700;
            color: #0057ff;
        }

        /* Totals */
        .totals-section {
            padding-top: 6px;
        }
        .total-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 15px;
            color: #555;
            border-bottom: 1px solid #f5f5f5;
        }
        .total-row:last-child {
            border-bottom: none;
            font-size: 18px;
            font-weight: 700;
            color: #222;
            padding-top: 14px;
        }
        .total-row:last-child span:last-child {
            color: #0057ff;
        }

        /* Payment method badge */
        .payment-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e8f0ff;
            color: #0057ff;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        /* Action buttons */
        .action-buttons {
            display: flex;
            gap: 15px;
            margin-top: 10px;
        }
        .btn-primary {
            flex: 1;
            display: block;
            text-align: center;
            padding: 16px;
            background: #0057ff;
            color: white;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: 0.3s;
        }
        .btn-primary:hover {
            background: #0037a3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,87,255,0.25);
        }
        .btn-outline {
            flex: 1;
            display: block;
            text-align: center;
            padding: 16px;
            background: white;
            color: #0057ff;
            border: 2px solid #0057ff;
            border-radius: 14px;
            text-decoration: none;
            font-weight: 700;
            font-size: 15px;
            transition: 0.3s;
        }
        .btn-outline:hover {
            background: #e8f0ff;
            transform: translateY(-2px);
        }

        /* Status chip */
        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: #e6f9ee;
            color: #1e7e34;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }
        .status-dot {
            width: 8px;
            height: 8px;
            background: #28a745;
            border-radius: 50%;
            animation: pulse 1.5s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.4; }
        }

        @media (max-width: 600px) {
            .info-grid { grid-template-columns: 1fr; }
            .action-buttons { flex-direction: column; }
            .success-banner h1 { font-size: 24px; }
        }
    </style>
</head>
<body>
    <!-- TOP HEADER -->
    <div class="topheader">
        <p>Welcome to Sisonke Marketplace! Buy, sell and discover great deals.</p>
        <div class="toplinks">
            <a href="#">Help Center</a>
            <span class="divider">|</span>
            <a href="#">Sell on Sisonke</a>
        </div>
    </div>

    <!-- MAIN HEADER -->
    <header class="mainheader">
        <div class="logo">
            <h1><a href="index.php" style="text-decoration:none;color:inherit;">Sisonke Marketplace</a></h1>
            <p>Buy. Sell. Connect.</p>
        </div>
        <div class="searchbar">
            <input type="text" placeholder="Search for products, categories...">
            <button><span class="material-symbols-outlined">search</span></button>
        </div>
        <div class="navicons">
            <a href="#">
                <span class="material-symbols-outlined">favorite</span>
                <span>Favorites</span>
            </a>
            <a href="cart.php">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span>Cart</span>
            </a>
            <?php if (isset($_SESSION['user_id'])): ?>
            <a href="logout.php">
                <span class="material-symbols-outlined">person</span>
                <span><?php echo htmlspecialchars($_SESSION['username']); ?> | Logout</span>
            </a>
            <?php else: ?>
            <a href="login.php">
                <span class="material-symbols-outlined">person</span>
                <span>Login / Register</span>
            </a>
            <?php endif; ?>
        </div>
    </header>

    <!-- SUCCESS CONTENT -->
    <div class="success-wrapper">

        <!-- Banner -->
        <div class="success-banner">
            <div class="checkmark-circle">
                <span class="material-symbols-outlined">check_circle</span>
            </div>
            <h1>Order Placed Successfully!</h1>
            <p>Thank you, <strong><?php echo htmlspecialchars($order['full_name'] ?? $_SESSION['username']); ?></strong>! Your order is confirmed.</p>
            <div class="order-ref"># ORDER-<?php echo str_pad($order_id, 5, '0', STR_PAD_LEFT); ?></div>
        </div>

        <!-- Order Status -->
        <div class="order-card">
            <div class="order-card-header">
                <span class="material-symbols-outlined">local_shipping</span>
                <h3>Order Status</h3>
            </div>
            <div class="order-card-body">
                <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:15px;">
                    <div>
                        <div class="status-chip">
                            <div class="status-dot"></div>
                            Processing
                        </div>
                        <p style="font-size:14px; color:#999; margin-top:10px;">
                            <?php if ($order): ?>
                                Placed on <?php echo $order['formatted_date']; ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <label style="font-size:12px;color:#999;text-transform:uppercase;letter-spacing:0.5px;display:block;margin-bottom:5px;">Payment</label>
                        <div class="payment-badge">
                            <span class="material-symbols-outlined" style="font-size:18px;">payments</span>
                            <?php echo htmlspecialchars($order['payment_method'] ?? ''); ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Delivery Info -->
        <?php if ($order): ?>
        <div class="order-card">
            <div class="order-card-header">
                <span class="material-symbols-outlined">location_on</span>
                <h3>Delivery Information</h3>
            </div>
            <div class="order-card-body">
                <div class="info-grid">
                    <div class="info-item">
                        <label>Full Name</label>
                        <span><?php echo htmlspecialchars($order['full_name']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Email</label>
                        <span><?php echo htmlspecialchars($order['email']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>Phone</label>
                        <span><?php echo htmlspecialchars($order['phone']); ?></span>
                    </div>
                    <div class="info-item">
                        <label>City</label>
                        <span><?php echo htmlspecialchars($order['city']); ?></span>
                    </div>
                    <div class="info-item" style="grid-column: 1 / -1;">
                        <label>Delivery Address</label>
                        <span><?php echo htmlspecialchars($order['address']); ?>, <?php echo htmlspecialchars($order['province']); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Order Items -->
        <?php if (!empty($order_items)): ?>
        <div class="order-card">
            <div class="order-card-header">
                <span class="material-symbols-outlined">inventory_2</span>
                <h3>Items Ordered</h3>
            </div>
            <div class="order-card-body">
                <?php foreach ($order_items as $item): ?>
                <div class="order-item-row">
                    <div class="order-item-left">
                        <span class="item-qty-badge">x<?php echo $item['quantity']; ?></span>
                        <span class="order-item-name"><?php echo htmlspecialchars($item['product_title']); ?></span>
                    </div>
                    <span class="order-item-price">R<?php echo number_format($item['subtotal'], 2); ?></span>
                </div>
                <?php endforeach; ?>

                <!-- Totals -->
                <div class="totals-section" style="margin-top:10px;">
                    <?php
                        $subtotal = array_sum(array_column($order_items, 'subtotal'));
                        $delivery = $order['delivery_fee'] ?? 80.00;
                        $grand    = $order['total_amount'] ?? ($subtotal + $delivery);
                    ?>
                    <div class="total-row">
                        <span>Subtotal</span>
                        <span>R<?php echo number_format($subtotal, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Delivery Fee</span>
                        <span>R<?php echo number_format($delivery, 2); ?></span>
                    </div>
                    <div class="total-row">
                        <span>Grand Total</span>
                        <span>R<?php echo number_format($grand, 2); ?></span>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Action Buttons -->
        <div class="action-buttons">
            <a href="index.php" class="btn-primary">
                Continue Shopping
            </a>
            <a href="my_orders.php" class="btn-outline">
                View My Orders
            </a>
        </div>

    </div>

    <!-- FOOTER -->
    <footer>
        <div class="footercontainer">
            <div class="footerbox">
                <h3>About</h3>
                <p>Sisonke Marketplace is a trusted marketplace where people can buy and sell products easily.</p>
            </div>
            <div class="footerbox">
                <h3>Quick Links</h3>
                <a href="index.php">Home</a>
                <a href="#">Products</a>
            </div>
        </div>
        <div class="copyright">
            <p>© 2026 Sisonke Marketplace. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>