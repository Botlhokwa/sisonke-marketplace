<?php
// login.php  –  Updated for Sisonke Marketplace admin/RBAC system
session_start();
include("db.php");

// Already logged in → redirect
if (isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit();
}

$error = "";

// ── Status messages from redirects ───────────────────────────────
if (isset($_GET['suspended'])) {
    $error = "Your account has been suspended. Please contact support.";
} elseif (isset($_GET['banned'])) {
    $error = "Your account has been permanently banned.";
}

// ── Handle login form submission ──────────────────────────────────
if (isset($_POST['login_btn'])) {

    $login_input = trim($_POST['login']);
    $password    = trim($_POST['password']);

    if (empty($login_input) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1");
        $stmt->bind_param("ss", $login_input, $login_input);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
        $stmt->close();

        if ($user && password_verify($password, $user['password'])) {

            // ── Check account status BEFORE creating session ──────
            if ($user['status'] === 'suspended') {
                $error = "Your account has been suspended. Please contact support.";
            } elseif ($user['status'] === 'banned') {
                $error = "Your account has been permanently banned.";
            } else {
                // ── Set session ───────────────────────────────────
                session_regenerate_id(true);
                $_SESSION['user_id']  = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role']     = $user['role'];

                // Update last_login timestamp
                $now = date('Y-m-d H:i:s');
                $upd = $conn->prepare("UPDATE users SET last_login = ? WHERE id = ?");
                $upd->bind_param("si", $now, $user['id']);
                $upd->execute();
                $upd->close();

                // ── Force password change if required ─────────────
                if ($user['must_change_password']) {
                    header("Location: change_password.php");
                    exit();
                }

                // ── Redirect by role ──────────────────────────────
                if ($user['role'] === 'admin') {
                    header("Location: admin/dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit();
            }

        } else {
            $error = "Invalid email/username or password.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login | Sisonke Marketplace</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined">
</head>
<body>
<div class="auth-container">
    <!-- LEFT -->
    <div class="left-side">
        <div class="branding">
            <h1>Sisonke Marketplace</h1>
            <p>Discover trusted products and connect with buyers and sellers across South Africa.</p>
        </div>
    </div>
    <!-- RIGHT -->
    <div class="right-side">
        <div class="form-container">
            <h2>Welcome Back</h2>
            <p class="subtitle">Login to continue to your account.</p>

            <?php if ($error !== ""): ?>
                <div class="error-message"><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form method="POST">
                <div class="input-group">
                    <span class="material-symbols-outlined">person</span>
                    <input type="text" name="login" placeholder="Email or Username" required>
                </div>
                <div class="input-group">
                    <span class="material-symbols-outlined lock-icon">lock</span>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <span class="material-symbols-outlined password-toggle"
                          onclick="togglePassword('password', this)">visibility</span>
                </div>
                <div class="remember">
                    <input type="checkbox">
                    <label>Remember Me</label>
                </div>
                <button type="submit" name="login_btn" class="auth-btn">Login</button>
                <div class="auth-footer">
                    <a href="forgot_password.php">Forgot Password?</a>
                </div>
            </form>

            <div class="auth-footer">
                Don't have an account? <a href="register.php">Register</a>
            </div>
        </div>
    </div>
</div>
<script>
function togglePassword(id, icon) {
    const input = document.getElementById(id);
    if (input.type === "password") {
        input.type = "text";
        icon.innerHTML = "visibility_off";
    } else {
        input.type = "password";
        icon.innerHTML = "visibility";
    }
}
</script>
</body>
</html>