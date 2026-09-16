<?php
/**
 * payfast_cancel.php
 * User is redirected here if they cancel on PayFast
 */
session_start();
require 'db.php';

// Mark order as cancelled if it exists
$order_id = (int) ($_SESSION['last_order_id'] ?? 0);
if ($order_id && isset($_SESSION['user_id'])) {
    $stmt = $conn->prepare("
        UPDATE orders SET status = 'cancelled'
        WHERE id = ? AND user_id = ? AND status = 'pending'
    ");
    $stmt->bind_param("ii", $order_id, (int)$_SESSION['user_id']);
    $stmt->execute();
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Cancelled - Sisonke Marketplace</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .cancel-wrapper {
            max-width: 560px;
            margin: 80px auto;
            padding: 0 5%;
            text-align: center;
        }
        .cancel-icon {
            width: 90px;
            height: 90px;
            background: #fff0f0;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 28px;
        }
        .cancel-icon .material-symbols-outlined {
            font-size: 48px;
            color: #e53935;
        }
        .cancel-wrapper h1 {
            font-size: 28px;
            color: #222;
            margin-bottom: 12px;
        }
        .cancel-wrapper p {
            font-size: 16px;
            color: #777;
            margin-bottom: 32px;
            line-height: 1.6;
        }
        .btn-row {
            display: flex;
            gap: 14px;
            justify-content: center;
            flex-wrap: wrap;
        }
        .btn-primary {
            padding: 14px 28px;
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
        }
        .btn-outline {
            padding: 14px 28px;
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
    </style>
</head>
<body>
    <!-- TOP HEADER -->
    <div class="topheader">
        <p>Welcome to Sisonke Marketplace! Buy, sell and discover great deals.</p>
        <div class="toplinks">
            <a href="#">Help Center</a>
            <span class="divider">|</span>
            <a href="sell.php">Sell on Sisonke</a>
        </div>
    </div>
    <header class="mainheader">
        <div class="logo">
            <h1><a href="index.php" style="text-decoration:none;color:inherit;">Sisonke Marketplace</a></h1>
            <p>Buy. Sell. Connect.</p>
        </div>
        <div class="searchbar">
            <input type="text" placeholder="Search for products...">
            <button><span class="material-symbols-outlined">search</span></button>
        </div>
        <div class="navicons">
            <a href="cart.php">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span>Cart</span>
            </a>
        </div>
    </header>

    <div class="cancel-wrapper">
        <div class="cancel-icon">
            <span class="material-symbols-outlined">cancel</span>
        </div>
        <h1>Payment Cancelled</h1>
        <p>Your payment was cancelled and your order has not been charged. Your cart items are still saved — you can try again whenever you're ready.</p>
        <div class="btn-row">
            <a href="checkout.php" class="btn-primary">Try Again</a>
            <a href="cart.php" class="btn-outline">Back to Cart</a>
        </div>
    </div>

    <footer>
        <div class="copyright" style="padding: 30px 5%; margin: 0;">
            <p>© 2026 Sisonke Marketplace. All Rights Reserved.</p>
        </div>
    </footer>
</body>
</html>