<?php
session_start();
include 'db.php';
// Protect Page
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
// Fetch User Products
$stmt = $conn->prepare("SELECT * FROM products WHERE user_id = ? ORDER BY created_at DESC");
$stmt->bind_param("i", $user_id);
$stmt->execute();

$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Listings</title>
    <link rel="stylesheet" href="sell.css">
    <link rel="stylesheet"
    href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body>
    <div class="my-listings-container">
        <div class="listing-header">
            <h1>My Listings</h1>
            <a href="sell.php" class="sell-btn">
                + Sell New Product
            </a>
            <a href="index.php" class="back-btn">
                <p>< Back to Home</p>
            </a>
        </div>
        <div class="product-grid">
            <?php while($product = $result->fetch_assoc()) { ?>
            <div class="product-card">
                <div class="product-image">
                    <img src="uploads/<?php echo $product['image']; ?>" alt="">
                </div>
                <div class="product-content">
                    <span class="category-tag">
                        <?php echo htmlspecialchars($product['category']); ?>
                    </span>
                    <h3>
                        <?php echo htmlspecialchars($product['title']); ?>
                    </h3>
                    <p class="price">
                        R<?php echo number_format($product['price'], 2); ?>
                    </p>
                    <p class="date">
                        Listed:
                        <?php echo date('d M Y', strtotime($product['created_at'])); ?>
                    </p>
                    <div class="product-actions">
                        <a href="edit_product.php?id=<?php echo $product['id']; ?>"
                        class="edit-btn">
                            Edit
                        </a>
                        <a href="delete_product.php?id=<?php echo $product['id']; ?>"
                        class="delete-btn"
                        onclick="return confirm('Are you sure you want to delete this product?')">
                            Delete
                        </a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
    </div>
</body>
</html>