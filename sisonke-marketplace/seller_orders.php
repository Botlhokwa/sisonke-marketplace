<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$seller_id = $_SESSION['user_id'];

$pendingOrders = 0;

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


$stmt = $conn->prepare("
SELECT
    oi.*,
    o.full_name,
    o.phone,
    o.address,
    o.city,
    o.total_amount,
    o.order_status,
    oi.seller_status,
    o.created_at,
    p.title,
    p.image,
    p.stock
FROM order_items oi
JOIN orders o ON oi.order_id = o.id
JOIN products p ON oi.product_id = p.id
WHERE oi.seller_id = ?
ORDER BY o.created_at DESC
");

$stmt->bind_param("i", $seller_id);
$stmt->execute();
$result = $stmt->get_result();

?>

<!DOCTYPE html>
<html>
<head>
    <title>Seller Orders</title>
    <link rel="stylesheet" href="index.css" />
    <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
<style>
body{
    margin:0;
    padding:0;
    font-family:Arial, Helvetica, sans-serif;
    background:#f5f7fb;
}

.page-container{
    max-width:1200px;
    margin:40px auto;
    padding:0 20px;
}

.page-title{
    font-size:32px;
    color:#222;
    margin-bottom:30px;
}

.orders-grid{
    display:grid;
    grid-template-columns:repeat(auto-fit,minmax(350px,1fr));
    gap:25px;
}

.order-card{
    background:white;
    border-radius:20px;
    padding:25px;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
    transition:0.3s ease;
}

.order-card:hover{
    transform:translateY(-5px);
    box-shadow:0 10px 25px rgba(0,0,0,0.12);
}

.order-card h3{
    margin-bottom:15px;
    color:#0057ff;
    font-size:22px;
}

.order-info{
    margin-bottom:8px;
    color:#444;
    font-size:15px;
    line-height:1.6;
}

.status{
    display:inline-block;
    padding:8px 15px;
    border-radius:30px;
    font-size:13px;
    font-weight:bold;
    margin-top:10px;
}

.status.pending{
    background:#fff3cd;
    color:#856404;
}

.status.accepted{
    background:#d4edda;
    color:#155724;
}

.status.rejected{
    background:#f8d7da;
    color:#721c24;
}

.action-buttons{
    display:flex;
    gap:10px;
    margin-top:auto;
}

.btn{
    text-decoration:none;
    padding:12px 18px;
    border-radius:10px;
    font-weight:600;
    transition:0.3s;
    text-align:center;
}

.btn-accept{
    background:#28a745;
    color:white;
}

.btn-accept:hover{
    background:#218838;
}

.btn-reject{
    background:#dc3545;
    color:white;
}

.btn-reject:hover{
    background:#c82333;
}

.empty-orders{
    background:white;
    padding:40px;
    border-radius:20px;
    text-align:center;
    box-shadow:0 5px 20px rgba(0,0,0,0.08);
}

.empty-orders h3{
    color:#666;
    margin-bottom:10px;
}

.empty-orders p{
    color:#999;
}

.top-actions{
    margin-bottom:25px;
}

.back-home{
    display:inline-flex;
    align-items:center;
    gap:8px;
    background:#0057ff;
    color:white;
    text-decoration:none;
    padding:12px 18px;
    border-radius:10px;
    font-weight:600;
    transition:0.3s;
}

.back-home:hover{
    background:#0037a3;
    transform:translateY(-2px);
}

.product-image{
    width:100%;
    height:220px;
    object-fit:cover;
    border-radius:15px;
    margin-bottom:15px;
    background:#f1f1f1;
}

.new-order-alert{
    background:#fff3cd;
    color:#856404;
    border-left:5px solid #ffc107;
    padding:15px;
    margin-bottom:25px;
    border-radius:10px;
    font-weight:600;
}

.new-badge{
    display:inline-block;
    background:#dc3545;
    color:white;
    padding:5px 10px;
    border-radius:20px;
    font-size:12px;
    font-weight:bold;
    margin-bottom:10px;
}

@media(max-width:768px){

    .page-title{
        font-size:26px;
    }

    .orders-grid{
        grid-template-columns:1fr;
    }

    .action-buttons{
        flex-direction:column;
    }

    .btn{
        width:100%;
    }
}
</style>
</head>
<body>

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


<div class="page-container">

    <div class="top-actions">
        <a href="index.php" class="back-home">
            < Back to Home
        </a>
    </div>

    <h1 class="page-title">Seller Orders</h1>

    <?php if($pendingOrders > 0){ ?>
        <div class="new-order-alert">
            You have <?php echo $pendingOrders; ?> pending order(s) awaiting action.
        </div>
    <?php } ?>

    <?php if($result->num_rows > 0){ ?>

    <div class="orders-grid">

        <?php while($row = $result->fetch_assoc()) { ?>

        <div class="order-card">

            <img src="uploads/<?php echo htmlspecialchars($row['image']); ?>" alt="<?php echo htmlspecialchars($row['product_title']); ?>" class="product-image">

            <h3><?php echo htmlspecialchars($row['product_title']); ?></h3>

            <div class="order-info">
                <strong>Buyer:</strong>
                <?php echo htmlspecialchars($row['full_name']); ?>
            </div>

            <div class="order-info">
                <strong>Phone:</strong>
                <?php echo htmlspecialchars($row['phone']); ?>
            </div>

            <div class="order-info">
                <strong>Address:</strong>
                <?php echo htmlspecialchars($row['address']); ?>
            </div>

            <div class="order-info">
                <strong>City:</strong>
                <?php echo htmlspecialchars($row['city']); ?>
            </div>

            <div class="order-info">
                <strong>Quantity:</strong>
                <?php echo $row['quantity']; ?>

                <div class="order-info">
                <strong>Amount:</strong>
                R<?php echo number_format($row['subtotal'], 2); ?>
                </div>

            </div>

            <div class="order-info">
                <strong>Date:</strong>
                <?php echo date('d M Y', strtotime($row['created_at'])); ?>
            </div>

            <span class="status <?php echo strtolower($row['seller_status']); ?>">
                <?php echo $row['seller_status']; ?>
            </span>

            <?php if($row['seller_status'] == 'Pending') { ?>

                <div class="new-badge">NEW</div>

                <a href="accept_order.php?id=<?php echo $row['id']; ?>"
                    class="btn btn-accept">
                    Accept
                </a>

                <a href="reject_order.php?id=<?php echo $row['id']; ?>"
                    class="btn btn-reject"
                    onclick = "return confirm('Reject this order?')"
                >
                    Reject
                </a>

                <div class="order-info">
                    <strong>Stock Left:</strong>
                    <?php echo $row['stock']; ?>
                </div>

            <?php } ?>

        </div>

        <?php } ?>

    </div>

    <?php } else { ?>

    <div class="empty-orders">
        <h3>No Orders Yet</h3>
        <p>Orders for your products will appear here.</p>
    </div>

    <?php } ?>

</div>

</body>
</html>
