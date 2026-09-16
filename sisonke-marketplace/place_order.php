<?php
session_start();
ini_set('display_errors', 1);
error_reporting(E_ALL);
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: checkout.php");
    exit;
}

$user_id = (int) $_SESSION['user_id'];

// --- Sanitize inputs ---
$full_name      = trim(htmlspecialchars($_POST['full_name']      ?? ''));
$email          = trim(htmlspecialchars($_POST['email']          ?? ''));
$phone          = trim(htmlspecialchars($_POST['phone']          ?? ''));
$address        = trim(htmlspecialchars($_POST['address']        ?? ''));
$city           = trim(htmlspecialchars($_POST['city']           ?? ''));
$province       = trim(htmlspecialchars($_POST['province']       ?? ''));
$payment_method = trim(htmlspecialchars($_POST['payment_method'] ?? ''));
$use_payfast    = ($_POST['use_payfast'] ?? '0') === '1';

// --- Validate ---
$errors = [];
if (empty($full_name))      $errors[] = "Full name is required.";
if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
if (empty($phone))          $errors[] = "Phone number is required.";
if (empty($address))        $errors[] = "Delivery address is required.";
if (empty($city))           $errors[] = "City is required.";
if (empty($province))       $errors[] = "Province is required.";
if (empty($payment_method)) $errors[] = "Payment method is required.";

if (!empty($errors)) {
    $_SESSION['checkout_errors'] = $errors;
    header("Location: checkout.php");
    exit;
}

// --- Fetch cart ---
$cart_stmt = $conn->prepare("
    SELECT c.product_id, c.quantity, p.title AS product_title, p.price, p.stock, p.user_id AS seller_id
    FROM cart c
    JOIN products p ON c.product_id = p.id
    WHERE c.user_id = ?
");
$cart_stmt->bind_param("i", $user_id);
$cart_stmt->execute();
$cart_items = $cart_stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$cart_stmt->close();

if (empty($cart_items)) {
    $_SESSION['checkout_errors'] = ["Your cart is empty."];
    header("Location: cart.php");
    exit;
}

// --- Calculate totals ---
$delivery_fee = 80.00;
$subtotal     = 0;
foreach ($cart_items as $item) {
    $subtotal += $item['price'] * $item['quantity'];
}
$grand_total = $subtotal + $delivery_fee;

// --- Save order to DB (status = pending until PayFast confirms) ---
$conn->begin_transaction();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
    $status = $use_payfast ? 'pending' : 'confirmed';

    $order_stmt = $conn->prepare("
        INSERT INTO orders (user_id, full_name, email, phone, address, city, province,
                            payment_method, total_amount, order_status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $order_stmt->bind_param(
        "isssssssds",
        $user_id, $full_name, $email, $phone,
        $address, $city, $province,
        $payment_method, $grand_total, $status
    );
    $order_stmt->execute();
    $order_id = $conn->insert_id;
    $order_stmt->close();

    // Insert order items
    $item_stmt = $conn->prepare("
    INSERT INTO order_items (
        order_id,
        product_id,
        seller_id,
        product_title,
        price,
        quantity,
        subtotal,
        seller_status
    )
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($cart_items as $item) {
        $item_subtotal = $item['price'] * $item['quantity'];
        
        $seller_status = 'Pending'; // Default to pending until seller accepts
        
        $item_stmt->bind_param(
            "iiisdids",
            $order_id, $item['product_id'], $item['seller_id'], $item['product_title'],
            $item['price'], $item['quantity'], $item_subtotal, $seller_status
        );
        $item_stmt->execute();

    }
    $item_stmt->close();

    // If NOT PayFast, clear cart now
    if (!$use_payfast) {
        $clear = $conn->prepare("DELETE FROM cart WHERE user_id = ?");
        $clear->bind_param("i", $user_id);
        $clear->execute();
        $clear->close();
    }

    $conn->commit();

    // Store order info in session
    $_SESSION['last_order_id']    = $order_id;
    $_SESSION['last_order_total'] = number_format($grand_total, 2);

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['checkout_errors'] = ["Order failed: " . $e->getMessage()];
    header("Location: checkout.php");
    exit;
}

// PAYFAST SANDBOX REDIRECT//
if ($use_payfast) {

    // --- PayFast Sandbox Credentials ---
    // These are PayFast's official sandbox test credentials
    $merchant_id  = '10049700';
    $merchant_key = 'lcwvks1k89oqs';
    $passphrase   = 'jt7NOE43FZPn';  // Sandbox passphrase

    // URLs — update YOUR_DOMAIN to your actual domain or ngrok URL
    $return_url  = 'http://localhost/sisonke-market/payfast_return.php';
    $cancel_url  = 'http://localhost/sisonke-market/payfast_cancel.php';
    $notify_url  = 'http://localhost/sisonke-market/payfast_notify.php'; // must be publicly accessible

    // Build PayFast data array
    $data = [
        'merchant_id'   => $merchant_id,
        'merchant_key'  => $merchant_key,
        'return_url'    => $return_url,
        'cancel_url'    => $cancel_url,
        'notify_url'    => $notify_url,
        'name_first'    => explode(' ', $full_name)[0],
        'name_last'     => implode(' ', array_slice(explode(' ', $full_name), 1)) ?: '-',
        'email_address' => $email,
        'cell_number'   => $phone,
        'm_payment_id'  => $order_id,
        'amount'        => number_format($grand_total, 2, '.', ''),
        'item_name'     => 'Sisonke Marketplace Order #' . $order_id,
        'item_description' => 'Order with ' . count($cart_items) . ' item(s)',
        'custom_int1'   => $user_id,
        'custom_str1'   => 'sisonke_order',
    ];

    // Generate signature
    $pf_string = '';
    foreach ($data as $key => $val) {
        if ($val !== '') {
            $pf_string .= $key . '=' . urlencode(trim($val)) . '&';
        }
    }
    // Append passphrase
    $pf_string = rtrim($pf_string, '&');
    $pf_string .= '&passphrase=' . urlencode(trim($passphrase));
    $data['signature'] = md5($pf_string);

    // Build form and auto-submit to PayFast sandbox
    $payfast_url = 'https://sandbox.payfast.co.za/eng/process';
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Redirecting to PayFast...</title>
        <style>
            body { font-family: Arial, sans-serif; display: flex; justify-content: center;
                   align-items: center; min-height: 100vh; background: #f5f7fb; flex-direction: column; gap: 20px; }
            .redirect-box { text-align: center; background: white; padding: 50px 60px;
                            border-radius: 20px; box-shadow: 0 5px 30px rgba(0,0,0,0.08); }
            .spinner { width: 50px; height: 50px; border: 4px solid #e8f0ff;
                       border-top-color: #0057ff; border-radius: 50%;
                       animation: spin 0.8s linear infinite; margin: 0 auto 20px; }
            @keyframes spin { to { transform: rotate(360deg); } }
            h2 { color: #222; margin-bottom: 8px; }
            p  { color: #999; font-size: 15px; }
        </style>
    </head>
    <body>
        <div class="redirect-box">
            <div class="spinner"></div>
            <h2>Redirecting to PayFast...</h2>
            <p>Please wait, do not close this page.</p>
        </div>
        <form id="payfastForm" action="<?php echo $payfast_url; ?>" method="POST">
            <?php foreach ($data as $key => $val): ?>
            <input type="hidden" name="<?php echo $key; ?>" value="<?php echo htmlspecialchars($val); ?>">
            <?php endforeach; ?>
        </form>
        <script>
            setTimeout(() => document.getElementById('payfastForm').submit(), 1500);
        </script>
    </body>
    </html>
    <?php
    exit;
}

// Non-PayFast: go straight to success
header("Location: success.php");
exit;
?>