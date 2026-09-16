<?php
session_start();
include 'db.php';
/*GET CATEGORY FROM URL*/
$selectedCategory = isset($_GET['category']) 
    ? trim($_GET['category']) 
    : 'All Products';
/*CART COUNT*/
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $user_id = (int) $_SESSION['user_id'];

    $cc_stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    $cc_stmt->bind_param("i", $user_id);
    $cc_stmt->execute();

    $cart_count = (int)$cc_stmt->get_result()->fetch_assoc()['total'];

    $cc_stmt->close();
}
/*FETCH PRODUCTS BY CATEGORY */
if ($selectedCategory === 'All Products') {
    $stmt = $conn->prepare("
        SELECT * FROM products
        ORDER BY created_at DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT * FROM products
        WHERE category = ?
        ORDER BY created_at DESC
    ");
    $stmt->bind_param("s", $selectedCategory);
}
$stmt->execute();
$products = $stmt->get_result();

$selectedCategory = $_GET['category'] ?? '';

if (!empty($selectedCategory)) {
    $stmt1 = $conn->prepare("
        SELECT *
        FROM products
        WHERE category = ?
        ORDER BY created_at DESC
    ");
    $stmt1->bind_param("s", $selectedCategory);
    $stmt1->execute();
    $latestProducts = $stmt1->get_result();
    $stmt2 = $conn->prepare("
        SELECT *
        FROM products
        WHERE category = ?
        ORDER BY id DESC
    ");
    $stmt2->bind_param("s", $selectedCategory);
    $stmt2->execute();
    $listedProducts = $stmt2->get_result();
} else {
    $latestProducts = mysqli_query(
        $conn,
        "SELECT * FROM products ORDER BY created_at DESC LIMIT 8"
    );
    $listedProducts = mysqli_query(
        $conn,
        "SELECT * FROM products ORDER BY id DESC LIMIT 4"
    );
}

if(isset($_SESSION['error'])){
    echo "<div class='error-message'>".$_SESSION['error']."</div>";
    unset($_SESSION['error']);
}

?>


<?php
$pendingOrders = 0;

if (isset($_SESSION['user_id'])) {

    $seller_id = $_SESSION['user_id'];

    $notifStmt = $conn->prepare("
        SELECT COUNT(*) AS total
        FROM order_items
        WHERE seller_id = ?
        AND seller_status = 'Pending'
    ");

    $notifStmt->bind_param("i", $seller_id);
    $notifStmt->execute();

    $notifResult = $notifStmt->get_result();
    $notifRow = $notifResult->fetch_assoc();

    $pendingOrders = $notifRow['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sisonke Marketplace</title>
    <link rel="stylesheet" href="index.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body>
    <!--Top Header-->
    <div class="topheader">
        <p>Welcome to Sisonke Marketplace! Buy, sell and discover great deals.</p>
        <div class="toplinks">

            <?php if(isset($_SESSION['user_id'])) { ?>
                <a href="seller_orders.php" class="notification-link">
                    Seller Orders
                    <?php if ($pendingOrders > 0): ?>
                        <span class="notification-badge"><?php echo $pendingOrders; ?></span>
                    <?php endif; ?>
                </a>
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
    <!--Main Navigation-->
    <header class="mainheader">
        <!--Logo-->
        <div class="logo">
            <h1>Sisonke Marketplace</h1>
           <p>Buy. Sell. Connect.</p>
        </div>
        <!--Search-->
        <div class="searchbar">
            <input type="text" placeholder="Search for product, categories...">
            <button>
                <span class="material-symbols-outlined">
                search
                </span>
            </button>
        </div>
        <!--Icons-->
        <div class="navicons">
            <a href="#">
                <span class="material-symbols-outlined">
                favorite
                </span>
                <span>
                    Favorites
                </span>
            </a>

            <!-- Cart icon with dynamic badge -->
            <a href="cart.php" class="cart-link">
                <span class="material-symbols-outlined">shopping_cart</span>
                <span>Cart</span>
                <?php if ($cart_count > 0): ?>
                <span class="cart-badge" id="cartBadge"><?php echo $cart_count; ?></span>
                <?php else: ?>
                <span class="cart-badge" id="cartBadge" style="display:none;">0</span>
                <?php endif; ?>
            </a>

            <?php if(isset($_SESSION['user_id'])){ ?>
            <a href="logout.php">
            <span class="material-symbols-outlined">
                person
            </span>
            <span>
                <?php echo $_SESSION['username']; ?> | Logout
            </span>
            </a>

            <?php } else { ?>

            <a href="login.php">
            <span class="material-symbols-outlined">
                person
            </span>
            <span>
                Login / Register
            </span>
            </a>
            <?php } ?>
        </div>
    </header>
    <!--Menu Row-->
    <div class="menurow">
        <!--SideBar-->
        <aside class="sidebar">
           <div class="sidebar-header" onclick="toggleMenu()">
                <div class="sidebar-title">
                    <span class="material-symbols-outlined">
                        menu
                    </span>
                    <h3>shop by category</h3>
                </div>
                    <span class="material-symbols-outlined arrow">
                        expand_more
                    </span>
            </div> 
                 <!-- CATEGORY MENU -->
        <ul class="categorymenu" id="categoryMenu">
            <!-- ALL PRODUCTS -->
            <li>
                <a href="index.php"
                    class="<?php echo ($selectedCategory == 'All Products') ? 'active-category' : ''; ?>">
                    <span class="material-symbols-outlined">storefront</span>
                    All Products
                </a>
            </li>
            <!-- ELECTRONICS -->
            <li>
                <a href="index.php?category=Electronics"
                    class="<?php echo ($selectedCategory == 'Electronics') ? 'active-category' : ''; ?>">
                    <span class="material-symbols-outlined">devices</span>
                    Electronics
                </a>
            </li>
            <!-- CLOTHING -->
            <li>
                <a href="index.php?category=<?php echo urlencode('Clothing & Thrift'); ?>"
                    class="<?php echo ($selectedCategory == 'Clothing & Thrift') ? 'active-category' : ''; ?>">
                    <span class="material-symbols-outlined">checkroom</span>
                    Clothing & Thrift
                </a>
            </li>
            <!-- HOMEMADE -->
            <li>
                <a href="index.php?category=<?php echo urlencode('Homemade products'); ?>"
                    class="<?php echo ($selectedCategory == 'Homemade products') ? 'active-category' : ''; ?>">
                    <span class="material-symbols-outlined">home</span>
                    Homemade products
                </a>
            </li>
            <!-- HAIR -->
            <li>
                <a href="index.php?category=<?php echo urlencode('Hair products'); ?>"
                    class="<?php echo ($selectedCategory == 'Hair products') ? 'active-category' : ''; ?>">
                    <span class="material-symbols-outlined">content_cut</span>
                    Hair products
                </a>
            </li>
        </ul>
    </aside>
        <!--Quick Links-->
        <nav class="quicklinks">
            <a href="index.php">Home</a>
            <a href="my_orders.php">My Orders</a>
            <a href="my_listings.php">My Listings</a>
        </nav>
    </div>
    <!--Main Content-->
    <div class="maincontainer">
        <!--Content-->
        <main class="content">
            <!--Hero-->
            <section class="hero">
                <div class="herocontent">
                    <h1>Buy & Sell with People Around You</h1>
                    <p>A simple and trusted marketplace for everyone</p>
                    
                    <?php if(isset($_SESSION['user_id'])) { ?>
                        <a href="sell.php" class="herobutton">Start Selling</a>
                    <?php } else { ?>
                        <a href="login.php" class="herobutton">Login To Sell</a>
                    <?php } ?>
                
                </div> 
            </section>
            <!--Listed Products-->
            <?php if(empty($selectedCategory)): ?>
                <section class="listedproducts">
                    <div class="sectionheader">
                        <h2>Listed Products</h2>
                        <a href="#">View All</a>
                    </div>
                    <div class="horizontalproducts">

                        <?php if ($listedProducts && $listedProducts->num_rows > 0):
                            while ($product = $listedProducts->fetch_assoc()): ?>
                        <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration:none;color:inherit;">
                        <div class="horizontalcard">
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                onerror="this.style.background='#f0f0f0'"
                                alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <div class="productinfo">
                                <span class="tag"><?php echo htmlspecialchars($product['category']); ?></span>
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="price">R<?php echo number_format($product['price'], 2); ?></p>
                                <p class="stock-text">
                                Stock: <?php echo $product['stock']; ?>
                                </p>

                                <?php if (!isset($_SESSION['user_id']) ||
                                    $_SESSION['user_id'] != $product['user_id']
                                ) { ?>
                                    <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                        <span class="material-symbols-outlined">add_shopping_cart</span>
                                        Add to Cart
                                    </button>
                                <?php } else { ?>
                                    <button class="add-to-cart-btn" disabled>
                                        Your Product
                                    </button>
                                <?php } ?>

                            </div>
                        </div>
                        </a>
                        <?php endwhile;
                        else: ?>
                        <p style="color:#999; padding: 20px 0;">No products listed yet.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
            <!--Latest Product-->
            <?php if(empty($selectedCategory)): ?>
                <section class="latestproduct">
                    <div class="sectionheader">
                        <h2>Latest Product</h2>
                        <a href="#">view All</a>
                    </div>
                    <div class="productgrid">

                        <?php if ($latestProducts && $latestProducts->num_rows > 0):
                            while ($product = $latestProducts->fetch_assoc()): ?>
                        <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration:none;color:inherit;">
                        <div class="productcard">
                            <div class="wishlist">
                                <span class="material-symbols-outlined">favorite</span>
                            </div>
                            <img src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                onerror="this.style.background='#f0f0f0'"
                                alt="<?php echo htmlspecialchars($product['title']); ?>">
                            <div class="cardcontent">
                                <span class="tag"><?php echo htmlspecialchars($product['category']); ?></span>
                                <h3><?php echo htmlspecialchars($product['title']); ?></h3>
                                <p class="price">R<?php echo number_format($product['price'], 2); ?></p>
                                <p class="stock-text">
                                Stock: <?php echo $product['stock']; ?>
                                </p>
                                <?php if (!empty($product['location'])): ?>
                                <p class="location-text">
                                    <span class="material-symbols-outlined" style="font-size:15px;">location_on</span>
                                    <?php echo htmlspecialchars($product['location']); ?>
                                </p>
                                <?php endif; ?>

                                <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['user_id']) { ?>
                                    <button class="add-to-cart-btn"
                                        onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                        <span class="material-symbols-outlined">add_shopping_cart</span>
                                        Add to Cart
                                    </button>
                                <?php } else { ?>
                                    <button disabled class="own-product-btn">
                                        Your Product
                                    </button>
                                <?php } ?>
                            </div>
                        </div>
                        </a>
                        <?php endwhile;
                        else: ?>
                        <p style="color:#999; padding: 20px 0;">No products found.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
            <?php if(!empty($selectedCategory)): ?>
                <section class="latestproduct">
                    <div class="sectionheader">
                        <h2>
                            <?= htmlspecialchars($selectedCategory) ?>
                        </h2>
                    </div>
                    <div class="productgrid">
                        <?php if($latestProducts->num_rows > 0): ?>
                            <?php while($product = $latestProducts->fetch_assoc()): ?>
                                <a href="product.php?id=<?php echo $product['id']; ?>" style="text-decoration:none;color:inherit;">
                                    <div class="productcard">
                                        <div class="wishlist">
                                            <span class="material-symbols-outlined"> favorite </span>
                                        </div>
                                        <img
                                        src="uploads/<?php echo htmlspecialchars($product['image']); ?>"
                                    alt="<?php echo htmlspecialchars($product['title']); ?>">
                                    <div class="cardcontent">
                                        <span class="tag"><?php echo htmlspecialchars($product['category']); ?></span>
                                        <h3>
                                            <?php echo htmlspecialchars($product['title']); ?>
                                        </h3>
                                        <p class="price">
                                            R<?php echo number_format($product['price'], 2); ?>
                                        </p>
                                        <p class="stock-text">
                                            Stock: <?php echo $product['stock']; ?>
                                        </p>
                                        <?php if (!empty($product['location'])): ?>
                                        <p class="location-text">
                                            <span class="material-symbols-outlined"> location_on </span>
                                            <?php echo htmlspecialchars($product['location']); ?>
                                        </p>
                                        <?php endif; ?>
                                        <?php if(isset($_SESSION['user_id']) && $_SESSION['user_id'] != $product['user_id']) { ?>
                                            <button class="add-to-cart-btn" onclick="addToCart(<?php echo $product['id']; ?>, this)">
                                                <span class="material-symbols-outlined">add_shopping_cart</span>
                                                Add to Cart
                                            </button>
                                        <?php } else { ?>
                                            <button disabled class="own-product-btn">
                                                Your Product
                                            </button>
                                        <?php } ?>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p>No products found in this category.</p>
                        <?php endif; ?>
                    </div>
                </section>
            <?php endif; ?>
        </main>
    </div>
        <!-- FOOTER -->
    <footer>
        <div class="footercontainer">
            <div class="footerbox">
                <h3>About</h3>
                <p>
                    Sisonke Marketplace is a trusted marketplace where people can buy and sell products easily.
                </p>
            </div>
            <div class="footerbox">
                <h3>Quick Links</h3>
                <a href="#">Home</a>
                <a href="#">Products</a>
            </div>
        </div>
        <div class="copyright">
            <p>
                © 2026 TownTrade Marketplace. All Rights Reserved.
            </p>
        </div>
    </footer>

    <!-- Toast Notification -->
    <div class="toast" id="toast"></div>
    <!-- JAVASCRIPT -->
    <script>
        function toggleMenu(){
            const menu = document.getElementById("categoryMenu");
            menu.classList.toggle("show");
        }

        // Show toast message
        function showToast(msg, color = '#222') {
            const t = document.getElementById('toast');
            t.textContent = msg;
            t.style.background = color;
            t.classList.add('show');
            setTimeout(() => t.classList.remove('show'), 3000);
        }

        // Update cart badge in navbar
        function updateCartBadge(count) {
            const badge = document.getElementById('cartBadge');
            if (!badge) return;
            badge.textContent = count;
            badge.style.display = count > 0 ? 'flex' : 'none';
        }

         // Add to cart via AJAX
        function addToCart(productId, btn) {
            <?php if (!isset($_SESSION['user_id'])): ?>
                // Not logged in — redirect to login
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
                    btn.innerHTML = '<span class="material-symbols-outlined">check</span> Added!';
                    btn.style.background = '#28a745';
                    setTimeout(() => {
                        btn.innerHTML = '<span class="material-symbols-outlined">add_shopping_cart</span> Add to Cart';
                        btn.style.background = '';
                        btn.disabled = false;
                    }, 2000);
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