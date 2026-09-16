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
 
if ($product_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid product.']);
    exit;
}
 
$stmt = $conn->prepare("DELETE FROM cart WHERE user_id = ? AND product_id = ?");
$stmt->bind_param("ii", $user_id, $product_id);
 
if ($stmt->execute()) {
    // Recalculate totals
    $total_stmt = $conn->prepare("
        SELECT SUM(c.quantity) AS cart_count,
               SUM(c.quantity * p.price) AS cart_total
        FROM cart c
        JOIN products p ON c.product_id = p.id
        WHERE c.user_id = ?
    ");
    $total_stmt->bind_param("i", $user_id);
    $total_stmt->execute();
    $totals = $total_stmt->get_result()->fetch_assoc();
    $total_stmt->close();
 
    echo json_encode([
        'success'     => true,
        'cart_count'  => (int) ($totals['cart_count'] ?? 0),
        'cart_total'  => number_format((float)($totals['cart_total'] ?? 0), 2)
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Could not remove item.']);
}
 
$stmt->close();
$conn->close();
?>