<?php
session_start();
require 'db.php';
// Get product ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit;
}
$product_id = (int) $_GET['id'];
// Fetch product
$stmt = $conn->prepare("
    SELECT p.*, u.username AS seller_name
    FROM products p
    LEFT JOIN users u ON p.user_id = u.id
    WHERE p.id = ?
");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
if (!$product) {
    header("Location: index.php");
    exit;
}
// Cart count for badge
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];
    $cc = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    $cc->bind_param("i", $user_id);
    $cc->execute();
    $cart_count = (int) $cc->get_result()->fetch_assoc()['total'];
    $cc->close();
}
// Fetch related products (same category, exclude current)
$rel = $conn->prepare("
    SELECT * FROM products
    WHERE category = ? AND id != ?
    ORDER BY created_at DESC
    LIMIT 4
");
$rel->bind_param("si", $product['category'], $product_id);
$rel->execute();
$related = $rel->get_result()->fetch_all(MYSQLI_ASSOC);
$rel->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['title'] ?? $product['name']); ?> - Sisonke Marketplace</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
    <style>
        /* ---- PRODUCT PAGE ---- */
        .product-wrapper {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 5% 80px;
        }
        /* Breadcrumb */
        .breadcrumb {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            color: #999;
            margin-bottom: 30px;
        }
        .breadcrumb a {
            text-decoration: none;
            color: #0057ff;
        }
        .breadcrumb a:hover { text-decoration: underline; }
        .breadcrumb .material-symbols-outlined { font-size: 16px; }
        /* Main product layout */
        .product-main {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 50px;
            align-items: start;
            background: white;
            border-radius: 25px;
            box-shadow: 0 5px 30px rgba(0,0,0,0.07);
            padding: 40px;
            margin-bottom: 50px;
        }
        /* Image side */
        .product-image-wrap {
            position: relative;
        }
        .product-image-main {
            width: 100%;
            height: 420px;
            object-fit: cover;
            border-radius: 18px;
            background: #f0f0f0;
            display: block;
        }
        .product-badge-condition {
            position: absolute;
            top: 16px;
            left: 16px;
            background: #0057ff;
            color: white;
            padding: 6px 14px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 700;
        }
        .product-badge-condition.used {
            background: #ff9800;
        }
        .product-badge-condition.refurbished {
            background: #9c27b0;
        }
        /* Info side */
        .product-info-wrap {}

        .product-category-tag {
            display: inline-block;
            background: #e8f0ff;
            color: #0057ff;
            padding: 6px 16px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 16px;
        }

        .product-title {
            font-size: 30px;
            color: #111;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 16px;
        }

        .product-price {
            font-size: 38px;
            font-weight: 800;
            color: #0057ff;
            margin-bottom: 24px;
        }

        /* Meta info chips */
        .product-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            margin-bottom: 28px;
        }
        .meta-chip {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f5f7fb;
            padding: 8px 16px;
            border-radius: 30px;
            font-size: 14px;
            color: #444;
        }
        .meta-chip .material-symbols-outlined {
            font-size: 18px;
            color: #0057ff;
        }

        /* Description */
        .product-desc-label {
            font-size: 16px;
            font-weight: 700;
            color: #222;
            margin-bottom: 10px;
        }
        .product-desc {
            font-size: 15px;
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
            white-space: pre-line;
        }

        /* Stock indicator */
        .stock-indicator {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 14px;
            font-weight: 600;
            margin-bottom: 28px;
        }
        .stock-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
        }
        .in-stock { color: #28a745; }
        .in-stock .stock-dot { background: #28a745; }
        .out-stock { color: #e53935; }
        .out-stock .stock-dot { background: #e53935; }

        /* Action buttons */
        .product-actions {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .btn-add-cart {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 16px;
            background: #0057ff;
            color: white;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.3s;
            width: 100%;
        }
        .btn-add-cart:hover {
            background: #0037a3;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,87,255,0.25);
        }
        .btn-add-cart:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }
        .btn-contact {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 15px;
            background: white;
            color: #0057ff;
            border: 2px solid #0057ff;
            border-radius: 14px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            text-decoration: none;
            transition: 0.3s;
            width: 100%;
            text-align: center;
        }
        .btn-contact:hover {
            background: #e8f0ff;
            transform: translateY(-2px);
        }

        /* Seller card */
        .seller-card {
            background: #f5f7fb;
            border-radius: 18px;
            padding: 22px;
            margin-top: 30px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .seller-avatar {
            width: 52px;
            height: 52px;
            border-radius: 50%;
            background: #0057ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 22px;
            font-weight: 700;
            flex-shrink: 0;
        }
        .seller-info h4 {
            font-size: 15px;
            color: #222;
            margin-bottom: 3px;
        }
        .seller-info p {
            font-size: 13px;
            color: #999;
        }

        /* Related products */
        .related-section { margin-top: 20px; }
        .related-section .sectionheader { margin-bottom: 25px; }

        /* Toast */
        .toast {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: #222;
            color: white;
            padding: 14px 22px;
            border-radius: 12px;
            font-size: 14px;
            font-weight: 600;
            box-shadow: 0 8px 30px rgba(0,0,0,0.2);
            opacity: 0;
            transform: translateY(20px);
            transition: 0.4s;
            z-index: 9999;
            pointer-events: none;
        }
        .toast.show { opacity: 1; transform: translateY(0); }

        @media (max-width: 768px) {
            .product-main {
                grid-template-columns: 1fr;
                padding: 24px;
                gap: 30px;
            }
            .product-image-main { height: 280px; }
            .product-title { font-size: 24px; }
            .product-price { font-size: 30px; }
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
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="sell.php">Sell on Sisonke</a>
            <?php else: ?>
                <a href="login.php">Sell on Sisonke</a>
            <?php endif; ?>
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
                    <?php if ($cart_count > 0): ?>
                    <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                    <?php else: ?>
                    <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
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

    <!-- PRODUCT CONTENT -->
    <div class="product-wrapper">

        <!-- Breadcrumb -->
        <div class="breadcrumb">
            <a href="index.php">Home</a>
            <span class="material-symbols-outlined">chevron_right</span>
            <span><?php echo htmlspecialchars($product['title'] ?? $product['name']); ?></span>
        </div>

        <!-- Main Product Card -->
        <div class="product-main">

            <!-- LEFT: Image -->
            <div class="product-image-wrap">
                <img class="product-image-main"
                     src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                     onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'420\'%3E%3Crect width=\'400\' height=\'420\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%23aaa\' font-size=\'16\'%3ENo Image%3C/text%3E%3C/svg%3E'"
                     alt="<?php echo htmlspecialchars($product['title'] ?? $product['name']); ?>">

                <?php if (!empty($product['product_condition'])): 
                    $cond = strtolower($product['product_condition']);
                    $cond_class = in_array($cond, ['used', 'refurbished']) ? $cond : '';
                ?>
                <div class="product-badge-condition <?php echo $cond_class; ?>">
                    <?php echo htmlspecialchars(ucfirst($product['product_condition'])); ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- RIGHT: Info -->
            <div class="product-info-wrap">

                <span class="product-category-tag"><?php echo htmlspecialchars($product['category']); ?></span>

                <h1 class="product-title">
                    <?php echo htmlspecialchars($product['title'] ?? $product['name']); ?>
                </h1>

                <div class="product-price">
                    R<?php echo number_format($product['price'], 2); ?>
                </div>

                <!-- Meta chips -->
                <div class="product-meta">
                    <?php if (!empty($product['location'])): ?>
                    <div class="meta-chip">
                        <span class="material-symbols-outlined">location_on</span>
                        <?php echo htmlspecialchars($product['location']); ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($product['product_condition'])): ?>
                    <div class="meta-chip">
                        <span class="material-symbols-outlined">star</span>
                        <?php echo htmlspecialchars(ucfirst($product['product_condition'])); ?>
                    </div>
                    <?php endif; ?>
                    <div class="meta-chip">
                        <span class="material-symbols-outlined">calendar_today</span>
                        <?php echo date('d M Y', strtotime($product['created_at'])); ?>
                    </div>
                </div>

                <!-- Description -->
                <?php if (!empty($product['description'])): ?>
                <p class="product-desc-label">Description</p>
                <p class="product-desc"><?php echo htmlspecialchars($product['description']); ?></p>
                <?php endif; ?>

                <!-- Stock -->
                <?php if ($product['stock'] > 0): ?>
                <div class="stock-indicator in-stock">
                    <span class="stock-dot"></span>
                    In Stock (<?php echo $product['stock']; ?> available)
                </div>
                <?php else: ?>
                <div class="stock-indicator out-stock">
                    <span class="stock-dot"></span>
                    Out of Stock
                </div>
                <?php endif; ?>

                <!-- Action Buttons -->
                <div class="product-actions">
                    <?php if (
                        isset($_SESSION['user_id']) &&
                        $_SESSION['user_id'] == $product['user_id']
                    ): ?>
                    <button class="btn-add-cart" disabled>
                        <span class="material-symbols-outlined">person</span>
                        Your Product
                    </button>
                    <?php elseif ($product['stock'] > 0): ?>
                        <button class="btn-add-cart" id="addCartBtn" onclick="addToCart(<?php echo $product['id']; ?>, this)">
                            <span class="material-symbols-outlined">add_shopping_cart</span>
                            Add to Cart
                        </button>
                    <?php else: ?>
                        <button class="btn-add-cart" disabled>
                            <span class="material-symbols-outlined">remove_shopping_cart</span>
                            Out of Stock
                        </button>
                    <?php endif; ?>
                    <?php if (!empty($product['seller_phone'])): ?>
                        <a href="tel:<?php echo htmlspecialchars($product['seller_phone']); ?>"
                        class="btn-contact">
                            <span class="material-symbols-outlined">call</span>
                            Call Seller
                        </a>
                        <a href="https://wa.me/27<?php echo ltrim(htmlspecialchars($product['seller_phone']), '0'); ?>?text=Hi, I'm interested in your listing: <?php echo urlencode($product['title']); ?>"
                        target="_blank"
                        class="btn-contact"
                        style="background:#25d366;color:white;border-color:#25d366;">
                            <span class="material-symbols-outlined">chat</span>
                            WhatsApp Seller
                        </a>
                    <?php endif; ?>
                </div>
                <!-- Seller Card -->
                <?php if (!empty($product['seller_name'])): ?>
                <div class="seller-card">
                    <div class="seller-avatar">
                        <?php echo strtoupper(substr($product['seller_name'], 0, 1)); ?>
                    </div>
                    <div class="seller-info">
                        <h4><?php echo htmlspecialchars(ucfirst($product['seller_name'])); ?></h4>
                        <p>Seller on Sisonke Marketplace</p>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
        <!-- RELATED PRODUCTS -->
        <?php if (!empty($related)): ?>
        <div class="related-section">
            <div class="sectionheader">
                <h2>Related Products</h2>
                <a href="#">View All</a>
            </div>
            <div class="productgrid">
                <?php foreach ($related as $r): ?>
                <a href="product.php?id=<?php echo $r['id']; ?>" style="text-decoration:none;color:inherit;">
                    <div class="productcard" style="cursor:pointer;">
                        <img src="uploads/<?php echo htmlspecialchars($r['image']); ?>"
                             onerror="this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'400\' height=\'240\'%3E%3Crect width=\'400\' height=\'240\' fill=\'%23f0f0f0\'/%3E%3Ctext x=\'50%25\' y=\'50%25\' dominant-baseline=\'middle\' text-anchor=\'middle\' fill=\'%23aaa\' font-size=\'16\'%3ENo Image%3C/text%3E%3C/svg%3E'"
                             alt="<?php echo htmlspecialchars($r['title'] ?? $r['name']); ?>">
                        <div class="cardcontent">
                            <span class="tag"><?php echo htmlspecialchars($r['category']); ?></span>
                            <h3><?php echo htmlspecialchars($r['title'] ?? $r['name']); ?></h3>
                            <p class="price">R<?php echo number_format($r['price'], 2); ?></p>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
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
    <!-- Toast -->
    <div class="toast" id="toast"></div>
    <script>
        function showToast(msg, color = '#222') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = color;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        function updateCartBadge(count) {
            const badge = document.getElementById('cartBadge');
            if (!badge) return;
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }

        function addToCart(productId, btn) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                window.location.href = 'login.php';
                return;
            <?php endif; ?>

            btn.disabled = true;
            btn.innerHTML = '<span class="material-symbols-outlined">hourglass_top</span> Adding...';

            fetch('add_to_cart.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: `product_id=${productId}`
            })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    showToast('✓ Added to cart!', '#0057ff');
                    updateCartBadge(data.cart_count);
                    btn.innerHTML = '<span class="material-symbols-outlined">check</span> Added to Cart!';
                    btn.style.background = '#28a745';
                    setTimeout(() => {
                        btn.innerHTML = '<span class="material-symbols-outlined">add_shopping_cart</span> Add to Cart';
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 2500);
                } else {
                    showToast(data.message || 'Could not add to cart.', '#e53935');
                    btn.innerHTML = '<span class="material-symbols-outlined">add_shopping_cart</span> Add to Cart';
                    btn.disabled = false;
                }
            })
            .catch(() => {
                showToast('Something went wrong.', '#e53935');
                btn.innerHTML = '<span class="material-symbols-outlined">add_shopping_cart</span> Add to Cart';
                btn.disabled = false;
            });
        }
    </script>
</body>
</html>