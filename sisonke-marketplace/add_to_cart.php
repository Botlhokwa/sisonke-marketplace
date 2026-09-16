<?php
session_start();
require 'db.php';
// Must be logged in
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Please login to add items to cart.', 'redirect' => 'login.php']);
    exit;
} 
// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}
 
$user_id    = (int) $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
 
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}
// Check product exists and is in stock
$stmt = $conn->prepare("SELECT id, title, stock, user_id FROM products WHERE id = ?");
$stmt->bind_param("i", $product_id);
$stmt->execute();
$product = $stmt->get_result()->fetch_assoc();
$stmt->close();
 
if (!$product) {
    echo json_encode(['success' => false, 'message' => 'Product not found.']);
    exit;
}
if ($product['stock'] <= 0) {
    echo json_encode(['success' => false, 'message' => 'Product is out of stock.']);
    exit;
}
if($product['user_id'] == $user_id){

    $_SESSION['error'] = "You cannot purchase your own product.";

    header("Location: index.php");
    exit();
}
// Insert or increment quantity (ON DUPLICATE KEY handles duplicates)
$stmt = $conn->prepare("
    INSERT INTO cart (user_id, product_id, quantity)
    VALUES (?, ?, 1)
    ON DUPLICATE KEY UPDATE quantity = quantity + 1
");
$stmt->bind_param("ii", $user_id, $product_id);
 
if ($stmt->execute()) {
    // Get updated cart count
    $count_stmt = $conn->prepare("SELECT SUM(quantity) AS total FROM cart WHERE user_id = ?");
    $count_stmt->bind_param("i", $user_id);
    $count_stmt->execute();
    $count = $count_stmt->get_result()->fetch_assoc()['total'];
    $count_stmt->close();
 
    echo json_encode([
        'success'    => true,
        'message'    => '"' . htmlspecialchars($product['title']) . '" added to cart!',
        'cart_count' => (int) $count
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not add to cart. Please try again.']);
}
 
$stmt->close();
$conn->close();
?>