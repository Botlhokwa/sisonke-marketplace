<?php
session_start();
include 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$order_item_id = intval($_GET['id']);
$seller_id = $_SESSION['user_id'];

$stmt = $conn->prepare("
    UPDATE order_items
    SET seller_status = 'Rejected'
    WHERE id = ? AND seller_id = ?
");

$stmt->bind_param(
    "ii",
    $order_item_id,
    $seller_id
);

$stmt->execute();

header("Location: seller_orders.php");
exit();
?>