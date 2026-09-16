<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit;
}

$user_id    = (int) $_SESSION['user_id'];
$product_id = isset($_POST['product_id']) ? (int) $_POST['product_id'] : 0;
$quantity   = isset($_POST['quantity'])   ? (int) $_POST['quantity']   : 1;

if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}
// If quantity drops to 0 or below, remove item
if ($quantity <= 0) {
    $stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("ii", $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
} else {
    // Check stock
    $stock_stmt = $conn->prepare("SELECT stock FROM products WHERE id = ?");
    $stock_stmt->bind_param("i", $product_id);
    $stock_stmt->execute();
    $stock = $stock_stmt->get_result()->fetch_assoc()['stock'];
    $stock_stmt->close();

    if ($quantity > $stock) {
        echo json_encode(['success' => false, 'message' => 'Not enough stock available.']);
        exit;
    }

    $stmt = $conn->prepare("UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?");
    $stmt->bind_param("iii", $quantity, $user_id, $product_id);
    $stmt->execute();
    $stmt->close();
}
// Return updated totals for this item and overall cart
$total_stmt = $conn->prepare("
    SELECT 
        SUM(c.quantity) AS cart_count,
        SUM(c.quantity * p.price) AS cart_total
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$total_stmt->bind_param("i", $user_id);
$total_stmt->execute();
$totals = $total_stmt->get_result()->fetch_assoc();
$total_stmt->close();
// Item subtotal
$item_subtotal = 0;
if ($quantity > 0) {
    $price_stmt = $conn->prepare("SELECT price FROM products WHERE id = ?");
    $price_stmt->bind_param("i", $product_id);
    $price_stmt->execute();
    $price = $price_stmt->get_result()->fetch_assoc()['price'];
    $price_stmt->close();
    $item_subtotal = $price * $quantity;
}

echo json_encode([
    'success'        => true,
    'cart_count'     => (int) ($totals['cart_count'] ?? 0),
    'cart_total'     => number_format((float)($totals['cart_total'] ?? 0), 2),
    'item_subtotal'  => number_format($item_subtotal, 2),
    'removed'        => ($quantity <= 0)
]);

$conn->close();
?>