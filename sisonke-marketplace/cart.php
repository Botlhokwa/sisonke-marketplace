<?php
session_start();
require 'db.php';

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// Fetch cart items with product details
$stmt = $conn->prepare("
    SELECT c.product_id, c.quantity,
           p.title, p.price, p.image, p.stock
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$cart_items = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

// Calculate totals
$subtotal     = 0;
$cart_count   = 0;
foreach ($cart_items as $item) {
    $subtotal   += $item['price'] * $item['quantity'];
    $cart_count += $item['quantity'];
}
$delivery_fee = empty($cart_items) ? 0 : 80.00;
$grand_total  = $subtotal + $delivery_fee;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cart - Sisonke Marketplace</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        /* ---- CART PAGE STYLES ---- */
        .page-title {
            padding: 30px 5% 10px;
            font-size: 28px;
            color: #222;
        }
        .page-title span {
            color: #0057ff;
        }
        .cart-wrapper {
            display: flex;
            gap: 30px;
            padding: 20px 5% 60px;
            align-items: flex-start;
        }
        /* --- Cart Items Table --- */
        .cart-items {
            flex: 1;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            overflow: hidden;
        }
        .cart-items-header {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 50px;
            padding: 18px 24px;
            background: #f5f7fb;
            font-weight: 700;
            font-size: 14px;
            color: #555;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .cart-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr 1fr 50px;
            padding: 20px 24px;
            align-items: center;
            border-top: 1px solid #f0f0f0;
            transition: background 0.2s;
        }
        .cart-row:hover {
            background: #fafbff;
        }
        .cart-product {
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .cart-product img {
            width: 75px;
            height: 75px;
            object-fit: cover;
            border-radius: 12px;
            background: #f0f0f0;
        }
        .cart-product h4 {
            font-size: 15px;
            color: #222;
            margin-bottom: 4px;
        }
        .cart-product .cart-tag {
            font-size: 12px;
            color: #0057ff;
        }
        .cart-price {
            font-size: 15px;
            font-weight: 600;
            color: #333;
        }
        /* Quantity controls */
        .qty-control {
            display: flex;
            align-items: center;
            gap: 0;
            background: #f5f7fb;
            border-radius: 10px;
            width: fit-content;
            overflow: hidden;
        }
        .qty-btn {
            width: 34px;
            height: 34px;
            border: none;
            background: none;
            cursor: pointer;
            font-size: 18px;
            font-weight: bold;
            color: #0057ff;
            transition: background 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .qty-btn:hover {
            background: #e8f0ff;
        }
        .qty-display {
            width: 36px;
            text-align: center;
            font-size: 15px;
            font-weight: 700;
        }
        .cart-subtotal {
            font-size: 15px;
            font-weight: 700;
            color: #0057ff;
        }
        .remove-btn {
            background: none;
            border: none;
            cursor: pointer;
            color: #ccc;
            transition: color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remove-btn:hover {
            color: #e53935;
        }
        /* --- Order Summary --- */
        .order-summary {
            width: 340px;
            background: white;
            border-radius: 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.06);
            padding: 28px;
            position: sticky;
            top: 20px;
        }
        .order-summary h3 {
            font-size: 20px;
            margin-bottom: 24px;
            color: #222;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 14px;
            font-size: 15px;
            color: #555;
        }
        .summary-row.total {
            border-top: 2px solid #f0f0f0;
            padding-top: 16px;
            margin-top: 8px;
            font-size: 18px;
            font-weight: 700;
            color: #222;
        }
        .summary-row.total span:last-child {
            color: #0057ff;
        }
        .checkout-btn {
            display: block;
            width: 100%;
            padding: 16px;
            background: #0057ff;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
            margin-top: 24px;
            transition: 0.3s;
        }
        .checkout-btn:hover {
            background: #0037a3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,87,255,0.25);
        }
        .continue-btn {
            display: block;
            text-align: center;
            margin-top: 12px;
            color: #0057ff;
            text-decoration: none;
            font-size: 14px;
        }
        .continue-btn:hover { text-decoration: underline; }
        /* Empty cart */
        .empty-cart {
            text-align: center;
            padding: 80px 20px;
        }
        .empty-cart .material-symbols-outlined {
            font-size: 80px;
            color: #ddd;
            display: block;
            margin-bottom: 20px;
        }
        .empty-cart h3 {
            font-size: 22px;
            color: #999;
            margin-bottom: 10px;
        }
        .empty-cart a {
            display: inline-block;
            margin-top: 20px;
            background: #0057ff;
            color: white;
            padding: 12px 28px;
            border-radius: 12px;
            text-decoration: none;
            font-weight: 700;
        }
        /* Toast notification */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #222;
            color: white;
            padding: 14px 22px;
            border-radius: 12px;
            font-size: 14px;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: 0.4s;
            z-index: 9999;
        }
        .toast.show {
            opacity: 1;
            transform: translateY(0);
        }
        @media (max-width: 900px) {
            .cart-wrapper { flex-direction: column; }
            .order-summary { width: 100%; }
            .cart-items-header { display: none; }
            .cart-row {
                grid-template-columns: 1fr;
                gap: 12px;
            }
        }
    </style>
</head>
<body>
    <!-- TOP HEADER -->
    <div class="topheader">
        <p>Welcome to Sisonke Marketplace! Buy, sell and discover great deals.</p>
        <div class="toplinks">
            <?php if(isset($_SESSION['user_id'])) { ?>
                <a href="seller_orders.php">Seller Orders</a>
            <?php } else { ?>
                <a href="login.php">Seller Orders</a>
            <?php } ?>

            <span class="divider">|</span>

            <?php if(isset($_SESSION['user_id'])) { ?>
                <a href="sell.php">Sell on Sisonke</a>
            <?php } else { ?>
                <a href="login.php">Sell on Sisonke</a>
            <?php } ?>
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
            <a href="cart.php" style="position:relative;">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span>Cart</span>
                <?php if ($cart_count > 0): ?>
                <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                <?php endif; ?>
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

    <!-- PAGE TITLE -->
    <h2 class="page-title">My <span>Cart</span>
        <?php if (!empty($cart_items)): ?>
        <small style="font-size:16px;color:#999;font-weight:400;margin-left:10px;">(<?php echo $cart_count; ?> items)</small>
        <?php endif; ?>
    </h2>

    <!-- CART CONTENT -->
    <div class="cart-wrapper">
        <?php if (empty($cart_items)): ?>
        <!-- Empty State -->
        <div class="cart-items" style="flex:1">
            <div class="empty-cart">
                <span class="material-symbols-outlined">shopping_cart</span>
                <h3>Your cart is empty</h3>
                <p style="color:#aaa;">Looks like you haven't added anything yet.</p>
                <a href="index.php">Start Shopping</a>
            </div>
        </div>
        <?php else: ?>
        <!-- Cart Items -->
        <div class="cart-items" id="cartItemsContainer">
            <div class="cart-items-header">
                <div>Product</div>
                <div>Price</div>
                <div>Quantity</div>
                <div>Subtotal</div>
                <div></div>
            </div>

            <?php foreach ($cart_items as $item): ?>
            <?php $item_subtotal = $item['price'] * $item['quantity']; ?>
            <div class="cart-row" id="row-<?php echo $item['product_id']; ?>">
                <!-- Product -->
                <div class="cart-product">
                    <img src="uploads/<?php echo htmlspecialchars($item['image']); ?>"
                        onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'100\' height=\'75\'%3E%3Crect width=\'100\' height=\'75\' fill=\'%23f0f0f0\'/%3E%3C/svg%3E'"
                         alt="<?php echo htmlspecialchars($item['title']); ?>">
                    <div>
                        <h4><?php echo htmlspecialchars($item['title']); ?></h4>
                    </div>
                </div>
                <!-- Price -->
                <div class="cart-price">R<?php echo number_format($item['price'], 2); ?></div>
                <!-- Quantity -->
                <div>
                    <div class="qty-control">
                        <button class="qty-btn"
                            onclick="updateQty(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] - 1; ?>)">−</button>
                        <span class="qty-display" id="qty-<?php echo $item['product_id']; ?>"><?php echo $item['quantity']; ?></span>
                        <button class="qty-btn"
                            onclick="updateQty(<?php echo $item['product_id']; ?>, <?php echo $item['quantity'] + 1; ?>)">+</button>
                    </div>
                </div>
                <!-- Subtotal -->
                <div class="cart-subtotal" id="sub-<?php echo $item['product_id']; ?>">
                    R<?php echo number_format($item_subtotal, 2); ?>
                </div>
                <!-- Remove -->
                <div>
                    <button class="remove-btn" onclick="removeItem(<?php echo $item['product_id']; ?>)" title="Remove">
                        <span class="material-symbols-outlined">delete</span>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Order Summary -->
        <div class="order-summary">
            <h3>Order Summary</h3>
            <div class="summary-row">
                <span>Subtotal (<?php echo $cart_count; ?> items)</span>
                <span id="summarySubtotal">R<?php echo number_format($subtotal, 2); ?></span>
            </div>
            <div class="summary-row">
                <span>Delivery Fee</span>
                <span>R<?php echo number_format($delivery_fee, 2); ?></span>
            </div>
            <div class="summary-row total">
                <span>Grand Total</span>
                <span id="summaryTotal">R<?php echo number_format($grand_total, 2); ?></span>
            </div>
            <a href="checkout.php" class="checkout-btn">Proceed to Checkout →</a>
            <a href="index.php" class="continue-btn">← Continue Shopping</a>
        </div>
        <?php endif; ?>
    </div>
    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>
    <script>
        function showToast(msg, color = '#222') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = color;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function updateQty(productId, newQty) {
            fetch('update_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}&quantity=${newQty}`
            })
            .then(r => r.json())
            .then(data => {
                if (!data.success) {
                    showToast(data.message, '#e53935');
                    return;
                }
                if (data.removed || newQty <= 0) {
                    // Remove row from DOM
                    const row = document.getElementById(`row-${productId}`);
                    if (row) row.remove();
                    checkEmpty();
                } else {
                    document.getElementById(`qty-${productId}`).textContent = newQty;
                    document.getElementById(`sub-${productId}`).textContent = 'R' + data.item_subtotal;
                    // Update buttons
                    const row = document.getElementById(`row-${productId}`);
                    const btns = row.querySelectorAll('.qty-btn');
                    btns[0].setAttribute('onclick', `updateQty(${productId}, ${newQty - 1})`);
                    btns[1].setAttribute('onclick', `updateQty(${productId}, ${newQty + 1})`);
                }
                updateSummary(data.cart_count, data.cart_total);
            });
        }

        function removeItem(productId) {
            fetch('remove_from_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    const row = document.getElementById(`row-${productId}`);
                    if (row) {
                        row.style.opacity = '0';
                        row.style.transform = 'translateX(30px)';
                        row.style.transition = '0.3s';
                        setTimeout(() => { row.remove(); checkEmpty(); }, 300);
                    }
                    updateSummary(data.cart_count, data.cart_total);
                    showToast('Item removed from cart.');
                }
            });
        }

        function updateSummary(count, total) {
            const delivery = count > 0 ? 80.00 : 0;
            const sub = parseFloat(total.replace(',', ''));
            const grand = sub + delivery;

            const subEl = document.getElementById('summarySubtotal');
            const totalEl = document.getElementById('summaryTotal');
            if (subEl) subEl.textContent = 'R' + parseFloat(total).toFixed(2);
            if (totalEl) totalEl.textContent = 'R' + grand.toFixed(2);

            // Update badge
            const badge = document.getElementById('cartBadge');
            if (badge) badge.textContent = count;
        }

        function checkEmpty() {
            const rows = document.querySelectorAll('.cart-row');
            if (rows.length === 0) {
                document.getElementById('cartItemsContainer').innerHTML = `
                    <div class="empty-cart">
                        <span class="material-symbols-outlined">shopping_cart</span>
                        <h3>Your cart is empty</h3>
                        <p style="color:#aaa;">All items have been removed.</p>
                        <a href="index.php">Start Shopping</a>
                    </div>`;
            }
        }
    </script>
</body>
</html>