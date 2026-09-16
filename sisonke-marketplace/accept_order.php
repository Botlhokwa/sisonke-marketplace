<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_item_id = intval($_GET['id']);
$seller_id = $_SESSION['user_id'];

/* Verify the order belongs to this seller */
$stmt = $conn->prepare("
    SELECT product_id, quantity, seller_status
    FROM order_items
    WHERE id = ? AND seller_id = ?
");

$stmt->bind_param("ii", $order_item_id, $seller_id);
$stmt->execute();

$result = $stmt->get_result();
$order = $result->fetch_assoc();

if ($order && $order['seller_status'] == 'Pending') {

    /* Mark as accepted */
    $update = $conn->prepare("
        UPDATE order_items
        SET seller_status = 'Accepted'
        WHERE id = ?
    ");

    $update->bind_param("i", $order_item_id);
    $update->execute();

    /* Reduce stock */
    $stock = $conn->prepare("
        UPDATE products
        SET stock = stock - ?
        WHERE id = ?
    ");

    $stock->bind_param(
        "ii",
        $order['quantity'],
        $order['product_id']
    );

    $stock->execute();
}


header("Location: seller_orders.php");
exit();
?>