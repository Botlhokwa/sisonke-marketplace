<?php
session_start();
include 'db.php';
// Protect Page
if(!isset($_SESSION['user_id'])){
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if(!isset($_GET['id'])){
    header("Location: my_listings.php");
    exit();
}

$product_id = intval($_GET['id']);
// Fetch Product
$stmt = $conn->prepare("SELECT * FROM products WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows === 0){
    die("Product not found.");
}

$product = $result->fetch_assoc();
// Update Product
if(isset($_POST['update_product'])){

    $title = htmlspecialchars(trim($_POST['title']));
    $price = htmlspecialchars(trim($_POST['price']));
    $description = htmlspecialchars(trim($_POST['description']));
    $location = htmlspecialchars(trim($_POST['location']));
    $condition = htmlspecialchars(trim($_POST['product_condition']));
    $phone = htmlspecialchars(trim($_POST['seller_phone']));

    $update = $conn->prepare("
        UPDATE products
        SET title = ?, price = ?, description = ?, location = ?, product_condition = ?, seller_phone = ?
        WHERE id = ? AND user_id = ?
    ");

    $update->bind_param(
        "sdssssii",
        $title,
        $price,
        $description,
        $location,
        $condition,
        $phone,
        $product_id,
        $user_id
    );

    if($update->execute()){
        header("Location: my_listings.php");
        exit();
    } else {
        echo "Update failed.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Product</title>
    <link rel="stylesheet" href="sell.css">
</head>
<body>
    <div class="edit-container">
        <form method="POST" class="edit-form">
            <h1>Edit Product</h1>
            <div class="form-group">
                <label>Product Title</label>
                <input type="text" name="title" value="<?php echo htmlspecialchars($product['title']); ?>" required>
            </div>
            <div class="form-group">
                <label>Price</label>
                <input type="number" step="0.01" name="price" value="<?php echo $product['price']; ?>" required>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="5" required><?php echo htmlspecialchars($product['description']); ?></textarea>
            </div>
            <div class="form-group">
                <label>Location</label>
                <input type="text" name="location" value="<?php echo htmlspecialchars($product['location']); ?>" required>
            </div>
            <div class="form-group">
                <label>Condition</label>
                <select name="product_condition" required>
                    <option value="New"
                    <?php if($product['product_condition'] == 'New') echo 'selected'; ?>>
                        New
                    </option>
                    <option value="Used"
                    <?php if($product['product_condition'] == 'Used') echo 'selected'; ?>>
                        Used
                    </option>
                </select>
            </div>
            <div class="form-group">
                <label>Seller Phone</label>
                <input type="text" name="seller_phone" value="<?php echo htmlspecialchars($product['seller_phone']); ?>" required>
            </div>
            <button type="submit" name="update_product" class="update-btn">
                Update Product
            </button>
        </form>
    </div>
</body>
</html>