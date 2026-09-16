<?php
session_start();
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sell Product</title>
    <link rel="stylesheet" href="sell.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body>
<div class="sell-container">
    <div class="sell-header">
        <h1>Choose Category</h1>
        <p>Select the category for your product</p>
    </div>
    <div class="category-grid">
        <div class="category-card" onclick="selectCategory('Electronics')">
            <span class="material-symbols-outlined">devices</span>
            <h3>Electronics</h3>
        </div>
        <div class="category-card" onclick="selectCategory('Clothing & Thrift')">
            <span class="material-symbols-outlined">checkroom</span>
            <h3>Clothing & Thrift</h3>
        </div>
        <div class="category-card" onclick="selectCategory('Homemade products')">
            <span class="material-symbols-outlined">chair</span>
            <h3>Homemade products</h3>
        </div>
        <div class="category-card" onclick="selectCategory('Hair Products')">
            <span class="material-symbols-outlined">health_and_beauty</span>
            <h3>Hair Products</h3>
        </div>
    </div>

    <div class="listing-form-box" id="listingForm">
        <h2>List Your Product</h2>
        <form action="process_listing.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="category" id="selectedCategory">
            <div class="form-group">
                <label>Product Title</label>
                <input type="text" name="title" required>
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" name="price" required>
            </div>
            <div class="form-group">
                <label>Stock Quantity</label>
                <input type="number" name="stock" min="1" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="5" required></textarea>
            </div>
            <div class="form-group">
                <label>Upload Product Image</label>
                <input type="file" name="image" accept="image/png, image/jpeg" required>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" required>
            </div>
            <div class="form-group">
                <label>Condition</label>
                <select name="product_condition" required>
                    <option value="">Select Condition</option>
                    <option value="New">New</option>
                    <option value="Used">Used</option>
                </select>
            </div>
            <div class="form-group">
                <label>Seller Phone Number</label>
                <input type="text" name="seller_phone" required>
            </div>
            <button type="submit" class="submit-btn">
                List Product
            </button>
        </form>
    </div>
</div>
<script>
function selectCategory(category) {
    document.getElementById('listingForm').style.display = 'block';
    document.getElementById('selectedCategory').value = category;

    window.scrollTo({
        top: document.getElementById('listingForm').offsetTop,
        behavior: 'smooth'
    });
}
</script>
</body>
</html>