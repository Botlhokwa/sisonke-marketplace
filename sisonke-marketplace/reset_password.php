<?php
session_start();
include("db.php");

if(!isset($_SESSION['reset_email'])){
    header("Location: forgot_password.php");
    exit();
}
$message = "";
if(isset($_POST['reset'])){
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];

    if($password != $confirm){
        $message = "Passwords do not match.";

    }else{
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare(
        "UPDATE users SET password=? WHERE email=?"
        );

        $stmt->bind_param(
        "ss",
        $hashed,
        $_SESSION['reset_email']
        );

        if($stmt->execute()){

            unset($_SESSION['reset_email']);

            header("Location: login.php");
            exit();

        }else{
            $message = "Something went wrong.";
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Password</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="auth-container">
        <div class="left-side">
            <div class="branding">
                <h1>Sisonke Marketplace</h1>
                <p>Discover trusted products and connect with buyers and sellers across South Africa.</p>
            </div>
        </div>
        <div class="right-side">
            <div class="form-container">
                <h2>Create New Password</h2>
                <?php if($message != ""){ ?>
                <div class="error-message">
                <?php echo $message; ?>
                </div>
                <?php } ?>
                <form method="POST">

                <div class="input-group">
                <input
                type="password"
                name="password"
                placeholder="New Password"
                required>
                </div>

                <div class="input-group">
                <input
                type="password"
                name="confirm_password"
                placeholder="Confirm Password"
                required>
                </div>

                <button type="submit" name="reset" class="auth-btn">
                Reset Password
                </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>