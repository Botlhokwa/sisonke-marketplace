<?php
/**
 * payfast_notify.php
 * PayFast ITN (Instant Transaction Notification) handler
 * PayFast calls this URL server-to-server after payment
 * NOTE: For sandbox testing, this URL must be publicly accessible
 *       Use ngrok (https://ngrok.com) to expose localhost if testing locally
 */

require 'db.php';

// --- PayFast Sandbox Credentials ---
$merchant_id = '10000100';
$passphrase  = 'jt7NOE43FZPn';

// Only accept POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit;
}

// Get posted data
$post_data = $_POST;

// 1. Verify the payment status
$payment_status = $post_data['payment_status'] ?? '';
$order_id       = (int) ($post_data['m_payment_id'] ?? 0);
$amount_gross   = $post_data['amount_gross'] ?? '0.00';

if (empty($order_id)) {
    http_response_code(400);
    exit;
}

// 2. Build signature from posted data (excluding signature itself)
$pf_data = [];
foreach ($post_data as $key => $val) {
    if ($key !== 'signature') {
        $pf_data[$key] = stripslashes($val);
    }
}
$pf_string = '';
foreach ($pf_data as $key => $val) {
    if ($val !== '') {
        $pf_string .= $key . '=' . urlencode(trim($val)) . '&';
    }
}
$pf_string = rtrim($pf_string, '&');
$pf_string .= '&passphrase=' . urlencode(trim($passphrase));
$generated_signature = md5($pf_string);

// 3. Validate signature
if ($generated_signature !== ($post_data['signature'] ?? '')) {
    // Signature mismatch — log and exit
    error_log("PayFast ITN signature mismatch for order $order_id");
    http_response_code(400);
    exit;
}

// 4. Verify host is PayFast sandbox
$valid_hosts = ['sandbox.payfast.co.za', 'w1w.payfast.co.za', 'w2w.payfast.co.za'];
$source_ip   = $_SERVER['REMOTE_ADDR'];
$valid_ip    = false;
foreach ($valid_hosts as $host) {
    $ips = gethostbynamel($host);
    if ($ips && in_array($source_ip, $ips)) {
        $valid_ip = true;
        break;
    }
}
// Note: skip IP check on localhost for sandbox testing
// if (!$valid_ip) { http_response_code(400); exit; }

// 5. Handle payment status
if ($payment_status === 'COMPLETE') {
    // Mark order as confirmed
    $stmt = $conn->prepare("
        UPDATE orders SET status = 'confirmed', payment_status = 'paid'
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();

    // Clear cart for this user
    $user_stmt = $conn->prepare("SELECT user_id FROM orders WHERE id = ?");
    $user_stmt->bind_param("i", $order_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();
    $user_stmt->close();

    if ($user) {
        $clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear->bind_param("i", $user['user_id']);
        $clear->execute();
        $clear->close();
    }

} elseif (in_array($payment_status, ['FAILED', 'CANCELLED'])) {
    // Mark order as failed
    $stmt = $conn->prepare("
        UPDATE orders SET status = 'failed', payment_status = 'failed'
        WHERE id = ?
    ");
    $stmt->bind_param("i", $order_id);
    $stmt->execute();
    $stmt->close();
}

// PayFast expects a 200 OK response
http_response_code(200);
exit;
?>