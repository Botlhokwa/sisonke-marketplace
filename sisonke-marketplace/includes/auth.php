<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Require the user to be logged in.
 * Redirects to login.php if not authenticated.
 */
function requireLogin(): void {
    if (empty($_SESSION['user_id'])) {
        header('Location: /login.php');
        exit();
    }
}

function requireAdmin(): void {
    if (empty($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
        http_response_code(403);
        ?>
        <!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <title>Access Denied - Sisonke Marketplace</title>
            <link rel="stylesheet" href="/admin/admin.css">
            <style>
                body { display:flex; justify-content:center; align-items:center; min-height:100vh; background:#0f172a; }
                .denied-box { background:#1e293b; border:1px solid #ef4444; border-radius:20px; padding:60px 50px; text-align:center; max-width:440px; }
                .denied-box .icon { font-size:60px; margin-bottom:20px; }
                .denied-box h1 { color:#ef4444; font-size:28px; margin-bottom:12px; }
                .denied-box p  { color:#94a3b8; margin-bottom:30px; }
                .denied-box a  { background:#0057ff; color:white; padding:12px 28px; border-radius:10px; text-decoration:none; font-weight:700; }
            </style>
        </head>
        <body>
            <div class="denied-box">
                <div class="icon">🚫</div>
                <h1>Access Denied</h1>
                <p>You do not have permission to view this page. Admin access only.</p>
                <a href="/index.php">← Back to Marketplace</a>
            </div>
        </body>
        </html>
        <?php
        exit();
    }
}

/**
 * Logs them out and shows a message if suspended/banned.
 */
function requireActiveAccount(mysqli $conn): void {
    if (empty($_SESSION['user_id'])) return;

    $id   = (int) $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT status FROM users WHERE id = ? LIMIT 1");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();

    if (!$row) return;

    if ($row['status'] === 'suspended') {
        session_destroy();
        header('Location: /login.php?suspended=1');
        exit();
    }
    if ($row['status'] === 'banned') {
        session_destroy();
        header('Location: /login.php?banned=1');
        exit();
    }
}

function csrf_token(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void {
    $submitted = $_POST['csrf_token'] ?? '';
    if (empty($submitted) || !hash_equals($_SESSION['csrf_token'] ?? '', $submitted)) {
        http_response_code(403);
        die('Invalid CSRF token. Please go back and try again.');
    }
    // Rotate token after successful verify
    unset($_SESSION['csrf_token']);
}