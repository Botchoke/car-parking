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
    <title>Admin Registration - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="login-page admin-login-page">

    <div class="login-container admin-login-container">

        <div class="login-logo">
            <div class="icon-badge admin-badge">
                <i class="fas fa-user-shield"></i>
            </div>
        </div>

        <h1><span>ADMIN</span> SIGN UP</h1>
        <p class="login-subtitle">
            <i class="fas fa-lock"></i> Create Admin Account
        </p>

        <?php
        if(isset($_GET['success'])){
            echo '<div class="success-message">Account created! You can now <a href="login.php" style="color: inherit; text-decoration: underline;">login</a>.</div>';
        }

        if(isset($_GET['error'])){
            echo '<div class="error-message">' . htmlspecialchars($_GET['error']) . '</div>';
        }
        ?>

        <form action="register_process.php" method="POST">
            <input type="text" name="username" placeholder="Admin Username" required autocomplete="off">
            <input type="password" name="password" placeholder="Password" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button type="submit" class="login-btn admin-login-btn">
                <i class="fas fa-user-plus"></i> Create Admin Account
            </button>
        </form>

        <p class="register-text">
            Already have an account?
            <a href="login.php">Login</a>
        </p>

    </div>

</div>

</body>
</html>