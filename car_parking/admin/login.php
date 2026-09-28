<?php
session_start();
if(isset($_SESSION['admin'])){
    header("Location: index.php");
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="login-page admin-login-page">

    <div class="login-container admin-login-container">

        <div class="login-logo">
            <div class="icon-badge admin-badge">
                <i class="fas fa-shield-alt"></i>
            </div>
        </div>

        <h1><span>ADMIN</span> PORTAL</h1>
        <p class="login-subtitle">
            <i class="fas fa-lock"></i> Restricted Access
        </p>

        <form action="login_process.php" method="POST">

            <input type="text" name="username" placeholder="Admin Username" required autocomplete="off">
            <input type="password" name="password" placeholder="Admin Password" required>

            <?php
            if(isset($_GET['error'])){
                echo "<div class='error-message'>Invalid admin credentials</div>";
            }
            ?>

            <button class="login-btn admin-login-btn" type="submit">
                <i class="fas fa-sign-in-alt"></i> Login as Admin
            </button>

        </form>

        <p class="register-text">
            Need an admin account?
            <a href="register.php">Register</a>
        </p>

    </div>

</div>

</body>
</html>