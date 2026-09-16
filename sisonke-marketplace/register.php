<?php
session_start();
include("db.php");

$error = "";
$success = "";

if(isset($_POST['register'])){

    $full_name = htmlspecialchars(trim($_POST['full_name']));
    $username = htmlspecialchars(trim($_POST['username']));
    $email = htmlspecialchars(trim($_POST['email']));
    $phone = htmlspecialchars(trim($_POST['phone']));
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    if($password !== $confirm_password){
        $error = "Passwords do not match.";
    } else {

        // Check Email
        $check_email = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check_email->bind_param("s", $email);
        $check_email->execute();
        $check_email->store_result();

        // Check Username
        $check_username = $conn->prepare("SELECT id FROM users WHERE username = ?");
        $check_username->bind_param("s", $username);
        $check_username->execute();
        $check_username->store_result();

        if($check_email->num_rows > 0){
            $error = "Email already exists.";
        }
        elseif($check_username->num_rows > 0){
            $error = "Username already exists.";
        }
        else{

            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            $stmt = $conn->prepare("INSERT INTO users(full_name, username, email, phone, password) VALUES(?,?,?,?,?)");

            $stmt->bind_param("sssss", $full_name, $username, $email, $phone, $hashed_password);

            if($stmt->execute()){
                $success = "Registration successful. You can now login.";
            }
            else{
                $error = "Something went wrong.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register | Sisonke Marketplace</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" />
</head>
<body>
<div class="auth-container">
    <!-- LEFT -->
    <div class="left-side">
        <div class="branding">
            <h1>Sisonke Marketplace</h1>
            <p>
                Join South Africa's growing digital marketplace.
                Buy, sell, and connect with trusted people around you.
            </p>
        </div>
    </div>
    <!-- RIGHT -->
    <div class="right-side">
        <div class="form-container">
            <h2>Create Account</h2>
            <p class="subtitle">
                Register and start buying & selling today.
            </p>

            <?php if($error != ""){ ?>
                <div class="error-message">
                    <?php echo $error; ?>
                </div>
            <?php } ?>

            <?php if($success != ""){ ?>
                <div class="success-message">
                    <?php echo $success; ?>
                </div>
            <?php } ?>

            <form method="POST">
                <div class="input-group">
                    <span class="material-symbols-outlined">
                        person
                    </span>
                    <input type="text" name="full_name" placeholder="Full Name" required>
                </div>
                <div class="input-group">
                    <span class="material-symbols-outlined">
                        badge
                    </span>
                    <input type="text" name="username" placeholder="Username" required>
                </div>
                <div class="input-group">
                    <span class="material-symbols-outlined">
                        mail
                    </span>
                    <input type="email" name="email" placeholder="Email Address" required>
                </div>
                <div class="input-group">
                    <span class="material-symbols-outlined">
                        call
                    </span>
                    <input type="text" name="phone" placeholder="Phone Number" required>
                </div>
                <div class="input-group">
                    <span class="material-symbols-outlined">
                        lock
                    </span>
                    <input type="password" id="password" name="password" placeholder="Password" required>
                    <span class="material-symbols-outlined password-toggle" onclick="togglePassword('password', this)">
                        visibility
                    </span>
                </div>
                <div class="input-group">
                    <span class="material-symbols-outlined">
                        lock
                    </span>
                    <input type="password" id="confirmPassword" name="confirm_password" placeholder="Confirm Password" required>
                    <span class="material-symbols-outlined password-toggle" onclick="togglePassword('confirmPassword', this)">
                        visibility
                    </span>
                </div>
                <button type="submit" name="register" class="auth-btn">
                    Register
                </button>
            </form>
            <div class="auth-footer">
                Already have an account?
                <a href="login.php">Login</a>
            </div>
        </div>
    </div>
</div>

<script>
function togglePassword(id, icon){
    const input = document.getElementById(id);
    if(input.type === "password"){
        input.type = "text";
        icon.innerHTML = "visibility_off";
    }
    else{
        input.type = "password";
        icon.innerHTML = "visibility";
    }
}
</script>
</body>
</html>