<?php
/**
 * payfast_return.php
 * User is redirected here after successful PayFast payment
 */
session_start();
require 'db.php';

// The order ID was stored in session before redirecting to PayFast
$order_id = (int) ($_SESSION['last_order_id'] ?? 0);

if (!$order_id) {
    header("Location: index.php");
    exit;
}

// Show success page
header("Location: success.php");
exit;
?>