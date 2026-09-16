<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch cart items
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity,
           p.title AS product_title, p.price, p.image, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
    ORDER BY c.product_id DESC
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

if (empty($cart_items)) {
    header("Location: cart.php");
    exit;
}

// Calculate totals
$subtotal   = 0;
$cart_count = 0;
foreach ($cart_items as $item) {
    $subtotal   += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}
$delivery_fee = 80.00;
$grand_total  = $subtotal + $delivery_fee;

// Checkout errors from session
$errors = $_SESSION['checkout_errors'] ?? [];
unset($_SESSION['checkout_errors']);

// Cart badge count
$cc = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
$cc->bind_param("i", $user_id);
$cc->execute();
$cart_badge = (int) $cc->get_result()->fetch_assoc()['total'];
$cc->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout - Sisonke Marketplace</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        .page-title {
            padding: 30px 5% 10px;
            font-size: 28px;
            color: #222;
        }
        .page-title span { color: #0057ff; }

        .checkout-wrapper {
            display: flex;
            gap: 30px;
            padding: 20px 5% 60px;
            align-items: flex-start;
        }

        /* Form */
        .checkout-form-wrap {
            flex: 1;
            display: flex;
            flex-direction: column;
            gap: 25px;
        }
        .form-card {
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .form-card-header {
            padding: 20px 28px;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .form-card-header .material-symbols-outlined { color: #0057ff; }
        .form-card-header h3 { font-size: 17px; color: #222; }
        .form-card-body { padding: 28px; }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 18px;
        }
        .form-group {
            display: flex;
            flex-direction: column;
            gap: 7px;
        }
        .form-group.full { grid-column: 1 / -1; }
        .form-group label {
            font-size: 13px;
            font-weight: 700;
            color: #444;
            text-transform: uppercase;
            letter-spacing: 0.4px;
        }
        .form-group input,
        .form-group select,
        .form-group textarea {
            padding: 13px 16px;
            border: 2px solid #eee;
            border-radius: 12px;
            font-size: 15px;
            outline: none;
            transition: 0.2s;
            background: #fafafa;
            color: #222;
        }
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            border-color: #0057ff;
            background: white;
        }

        /* Payment methods */
        .payment-options {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .payment-option {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 16px 20px;
            border: 2px solid #eee;
            border-radius: 14px;
            cursor: pointer;
            transition: 0.2s;
        }
        .payment-option:hover { border-color: #0057ff; background: #f8f9ff; }
        .payment-option.selected { border-color: #0057ff; background: #f0f4ff; }
        .payment-option input[type="radio"] { accent-color: #0057ff; width: 18px; height: 18px; }
        .payment-option-info { flex: 1; }
        .payment-option-info strong { display: block; font-size: 15px; color: #222; }
        .payment-option-info span { font-size: 13px; color: #999; }
        .payment-option-icon {
            width: 44px;
            height: 44px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 22px;
        }
        .payfast-badge {
            background: #00b8a9;
            color: white;
            font-size: 11px;
            font-weight: 800;
            padding: 4px 10px;
            border-radius: 6px;
            letter-spacing: 0.5px;
        }

        /* Error box */
        .error-box {
            background: #fff0f0;
            border: 1px solid #ffcccc;
            border-radius: 12px;
            padding: 16px 20px;
        }
        .error-box p { color: #e53935; font-size: 14px; margin-bottom: 4px; }

        /* Order summary sidebar */
        .order-summary {
            width: 360px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            padding: 28px;
            position: sticky;
            top: 20px;
        }
        .order-summary h3 { font-size: 20px; margin-bottom: 24px; color: #222; }

        .summary-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .summary-item img {
            width: 56px;
            height: 56px;
            object-fit: cover;
            border-radius: 10px;
            background: #f0f0f0;
            flex-shrink: 0;
        }
        .summary-item-info { flex: 1; }
        .summary-item-info h4 { font-size: 14px; color: #222; margin-bottom: 3px; }
        .summary-item-info p { font-size: 13px; color: #999; }
        .summary-item-price { font-size: 15px; font-weight: 700; color: #0057ff; }

        .summary-totals { margin-top: 20px; }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 12px;
            font-size: 15px;
            color: #555;
        }
        .summary-row.total {
            border-top: 2px solid #f0f0f0;
            padding-top: 14px;
            margin-top: 8px;
            font-size: 18px;
            font-weight: 700;
            color: #222;
        }
        .summary-row.total span:last-child { color: #0057ff; }

        /* Place order button */
        .place-order-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            width: 100%;
            padding: 17px;
            background: #0057ff;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 24px;
            transition: 0.3s;
        }
        .place-order-btn:hover {
            background: #0037a3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,87,255,0.25);
        }

        /* PayFast info note */
        .payfast-note {
            display: flex;
            align-items: flex-start;
            gap: 10px;
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 14px 16px;
            margin-top: 16px;
            font-size: 13px;
            color: #0369a1;
        }
        .payfast-note .material-symbols-outlined { font-size: 18px; flex-shrink: 0; margin-top: 1px; }

        .secure-badge {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            font-size: 13px;
            color: #999;
            margin-top: 14px;
        }
        .secure-badge .material-symbols-outlined { font-size: 16px; color: #28a745; }

        @media (max-width: 900px) {
            .checkout-wrapper { flex-direction: column; }
            .order-summary { width: 100%; position: static; }
            .form-grid { grid-template-columns: 1fr; }
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
            <a href="cart.php" class="cart-link">
                <span class="cart-icon-wrap">
                    <span class="material-symbols-outlined">shopping_cart</span>
                    <?php if ($cart_badge > 0): ?>
                    <span class="cart-badge"><?php echo $cart_badge; ?></span>
                    <?php endif; ?>
                </span>
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

    <h2 class="page-title">Secure <span>Checkout</span></h2>

    <div class="checkout-wrapper">
        <!-- LEFT: Form -->
        <div class="checkout-form-wrap">

            <?php if (!empty($errors)): ?>
            <div class="error-box">
                <?php foreach ($errors as $e): ?>
                <p><?php echo htmlspecialchars($e); ?></p>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="place_order.php" id="checkoutForm">

                <!-- Delivery Details -->
                <div class="form-card">
                    <div class="form-card-header">
                        <span class="material-symbols-outlined">local_shipping</span>
                        <h3>Delivery Details</h3>
                    </div>
                    <div class="form-card-body">
                        <div class="form-grid">
                            <div class="form-group full">
                                <label>Full Name</label>
                                <input type="text" name="full_name" placeholder="e.g. Sipho Dlamini"
                                    value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Email Address</label>
                                <input type="email" name="email" placeholder="you@email.com"
                                    value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Phone Number</label>
                                <input type="tel" name="phone" placeholder="e.g. 0821234567"
                                    value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group full">
                                <label>Delivery Address</label>
                                <input type="text" name="address" placeholder="Street address"
                                    value="<?php echo htmlspecialchars($_POST['address'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>City</label>
                                <input type="text" name="city" placeholder="e.g. Johannesburg"
                                    value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>" required>
                            </div>
                            <div class="form-group">
                                <label>Province</label>
                                <select name="province" required>
                                    <option value="">Select Province</option>
                                    <?php
                                    $provinces = ['Gauteng','Western Cape','KwaZulu-Natal','Eastern Cape',
                                                  'Limpopo','Mpumalanga','North West','Free State','Northern Cape'];
                                    foreach ($provinces as $p):
                                        $sel = (($_POST['province'] ?? '') === $p) ? 'selected' : '';
                                    ?>
                                    <option value="<?php echo $p; ?>" <?php echo $sel; ?>><?php echo $p; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Payment Method -->
                <div class="form-card">
                    <div class="form-card-header">
                        <span class="material-symbols-outlined">payments</span>
                        <h3>Payment Method</h3>
                    </div>
                    <div class="form-card-body">
                        <div class="payment-options">

                            <!-- PayFast (sandbox) -->
                            <label class="payment-option selected" id="opt-payfast">
                                <input type="radio" name="payment_method" value="PayFast"
                                    checked onchange="selectPayment(this)">
                                <div class="payment-option-icon" style="background:#e8f8f7;">
                                    <span style="font-size:13px;font-weight:800;color:#00b8a9;">PF</span>
                                </div>
                                <div class="payment-option-info">
                                    <strong>PayFast <span class="payfast-badge">SANDBOX</span></strong>
                                    <span>Pay securely via PayFast — Card, EFT, SnapScan & more</span>
                                </div>
                            </label>

                            <!-- EFT / Direct -->
                            <label class="payment-option" id="opt-eft">
                                <input type="radio" name="payment_method" value="EFT"
                                    onchange="selectPayment(this)">
                                <div class="payment-option-icon" style="background:#e8f0ff;">
                                    <span class="material-symbols-outlined" style="color:#0057ff;font-size:22px;">account_balance</span>
                                </div>
                                <div class="payment-option-info">
                                    <strong>EFT / Direct Bank Transfer</strong>
                                    <span>Transfer directly to our bank account</span>
                                </div>
                            </label>

                            <!-- Cash on Delivery -->
                            <label class="payment-option" id="opt-cod">
                                <input type="radio" name="payment_method" value="Cash on Delivery"
                                    onchange="selectPayment(this)">
                                <div class="payment-option-icon" style="background:#e8ffe8;">
                                    <span class="material-symbols-outlined" style="color:#28a745;font-size:22px;">payments</span>
                                </div>
                                <div class="payment-option-info">
                                    <strong>Cash on Delivery</strong>
                                    <span>Pay when your order arrives</span>
                                </div>
                            </label>

                        </div>

                        <!-- PayFast sandbox note -->
                        <div class="payfast-note" id="payfastNote">
                            <span class="material-symbols-outlined">info</span>
                            <span>You are using the <strong>PayFast Sandbox</strong>. No real money will be charged. Use test card: <strong>4000000000000002</strong>, any future expiry and CVV.</span>
                        </div>
                    </div>
                </div>

                <!-- Hidden submit for non-PayFast methods -->
                <input type="hidden" name="use_payfast" id="usePayfast" value="1">

            </form>
        </div>

        <!-- RIGHT: Order Summary -->
        <div class="order-summary">
            <h3>Order Summary</h3>

            <?php foreach ($cart_items as $item): ?>
            <div class="summary-item">
                <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'56\' height=\'56\'%3E%3Crect width=\'56\' height=\'56\' fill=\'%23f0f0f0\'/%3E%3C/svg%3E'"
                     alt="<?php echo htmlspecialchars($item['product_title']); ?>">
                <div class="summary-item-info">
                    <h4><?php echo htmlspecialchars($item['product_title']); ?></h4>
                    <p>Qty: <?php echo $item['quantity']; ?></p>
                </div>
                <span class="summary-item-price">
                    R<?php echo number_format($item['price'] * $item['quantity'], 2); ?>
                </span>
            </div>
            <?php endforeach; ?>

            <div class="summary-totals">
                <div class="summary-row">
                    <span>Subtotal</span>
                    <span>R<?php echo number_format($subtotal, 2); ?></span>
                </div>
                <div class="summary-row">
                    <span>Delivery Fee</span>
                    <span>R<?php echo number_format($delivery_fee, 2); ?></span>
                </div>
                <div class="summary-row total">
                    <span>Grand Total</span>
                    <span>R<?php echo number_format($grand_total, 2); ?></span>
                </div>
            </div>

            <button class="place-order-btn" onclick="submitOrder()">
                <span class="material-symbols-outlined">lock</span>
                Place Order
            </button>

            <div class="secure-badge">
                <span class="material-symbols-outlined">verified_user</span>
                Secured by PayFast SSL Encryption
            </div>
        </div>
    </div>

    <script>
        function selectPayment(radio) {
            // Remove selected from all
            document.querySelectorAll('.payment-option').forEach(o => o.classList.remove('selected'));
            // Add to clicked
            radio.closest('.payment-option').classList.add('selected');

            // Show/hide PayFast note
            const note = document.getElementById('payfastNote');
            note.style.display = radio.value === 'PayFast' ? 'flex' : 'none';

            // Set payfast flag
            document.getElementById('usePayfast').value = radio.value === 'PayFast' ? '1' : '0';
        }

        function submitOrder() {
            // Basic validation
            const form = document.getElementById('checkoutForm');
            const inputs = form.querySelectorAll('[required]');
            let valid = true;
            inputs.forEach(input => {
                if (!input.value.trim()) {
                    input.style.borderColor = '#e53935';
                    valid = false;
                } else {
                    input.style.borderColor = '#eee';
                }
            });
            if (!valid) {
                window.scrollTo({ top: 0, behavior: 'smooth' });
                return;
            }
            form.submit();
        }
    </script>
</body>
</html>