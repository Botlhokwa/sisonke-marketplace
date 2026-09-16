<?php
// change_password.php
// Shown when must_change_password = 1 (after admin resets password)
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPass     = $_POST['new_password']     ?? '';
    $confirmPass = $_POST['confirm_password'] ?? '';

    if (strlen($newPass) < 8) {
        $error = "Password must be at least 8 characters.";
    } elseif ($newPass !== $confirmPass) {
        $error = "Passwords do not match.";
    } else {
        $hashed = password_hash($newPass, PASSWORD_DEFAULT);
        $uid    = (int)$_SESSION['user_id'];
        $stmt   = $conn->prepare("UPDATE users SET password=?, must_change_password=0 WHERE id=?");
        $stmt->bind_param("si", $hashed, $uid);
        $stmt->execute();
        $stmt->close();

        $success = "Password updated successfully!";
        header("refresh:2;url=index.php");
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Change Password | Sisonke Marketplace</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
<div class="auth-container">
    <div class="left-side">
        <div class="branding">
            <h1>Sisonke Marketplace</h1>
            <p>Please set a new password to continue.</p>
        </div>
    </div>
    <div class="right-side">
        <div class="form-container">
            <h2>Set New Password</h2>
            <p class="subtitle">Your password has been reset by an administrator. Please choose a new one.</p>

            <?php if ($error):   ?><div class="error-message"><?= htmlspecialchars($error) ?></div><?php endif; ?>
            <?php if ($success): ?><div class="error-message" style="background:#dcfce7;color:#166534;"><?= $success ?></div><?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <input type="password" name="new_password"     placeholder="New Password (min 8 chars)" required>
                </div>
                <div class="input-group">
                    <input type="password" name="confirm_password" placeholder="Confirm New Password" required>
                </div>
                <button type="submit" class="auth-btn">Update Password</button>
            </form>
        </div>
    </div>
</div>
</body>
</html>