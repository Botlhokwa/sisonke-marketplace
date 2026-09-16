<?php
session_start();
include("db.php");
$message = "";
if(isset($_POST['submit'])){

    $email = trim($_POST['email']);

    $stmt = $conn->prepare("SELECT id FROM users WHERE email=?");
    $stmt->bind_param("s",$email);
    $stmt->execute();

    $result = $stmt->get_result();

    if($result->num_rows > 0){

        $_SESSION['reset_email'] = $email;

        header("Location: reset_password.php");
        exit();

    }else{
        $message = "No account found with that email.";
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Forgot Password</title>
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
                <h2>Forgot Password</h2>
                <?php if($message != ""){ ?>
                <div class="error-message">
                <?php echo $message; ?>
                </div>
                <?php } ?>
                <form method="POST">

                <div class="input-group">
                <input
                type="email"
                name="email"
                placeholder="Enter your email"
                required>
                </div>

                <button
                type="submit"
                name="submit"
                class="auth-btn">
                Continue
                </button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>