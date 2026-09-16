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
// Get Product Image
$stmt = $conn->prepare("SELECT image FROM products WHERE id = ? AND user_id = ?");
$stmt->bind_param("ii", $product_id, $user_id);
$stmt->execute();

$result = $stmt->get_result();

if($result->num_rows > 0){

    $product = $result->fetch_assoc();

    $imagePath = "uploads/" . $product['image'];
    // Delete Image File
    if(file_exists($imagePath)){
        unlink($imagePath);
    }
    // Delete Product
    $delete = $conn->prepare("DELETE FROM products WHERE id = ? AND user_id = ?");
    $delete->bind_param("ii", $product_id, $user_id);

    $delete->execute();
}
header("Location: my_listings.php");
exit();
?>