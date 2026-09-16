<?php
session_start();
include 'db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

$category = (trim($_POST['category']));
$title = (trim($_POST['title']));
$price = (trim($_POST['price']));
$stock = intval($_POST['stock']);
$description = (trim($_POST['description']));
$location = (trim($_POST['location']));
$product_condition = (trim($_POST['product_condition']));
$seller_phone = (trim($_POST['seller_phone']));

// Image Upload
$image = $_FILES['image'];
$imageName = time() . '_' . basename($image['name']);
$target = "uploads/" . $imageName;
$allowedTypes = ['jpg', 'jpeg', 'png'];
$imageExtension = strtolower(pathinfo($imageName, PATHINFO_EXTENSION));

if(!in_array($imageExtension, $allowedTypes)) {
    die("Invalid image type. Only JPG, JPEG and PNG allowed.");
}

if(move_uploaded_file($image['tmp_name'], $target)) {

    $stmt = $conn->prepare("INSERT INTO products(user_id, category, title, price, stock, description, image, location, product_condition, seller_phone)
    VALUES(?,?,?,?,?,?,?,?,?,?)");

    $stmt->bind_param(
        "issdisssss",
        $user_id,
        $category,
        $title,
        $price,
        $stock,
        $description,
        $imageName,
        $location,
        $product_condition,
        $seller_phone
    );
    if($stmt->execute()) {
        header("Location: my_listings.php");
        exit();
    } else {
        echo "Database Error.";
    }

} else {
    echo "Image upload failed.";
}
?>