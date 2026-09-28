<?php
session_start();
include "../db.php";

if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$error = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    
    $sql = "SELECT * FROM users WHERE username='$username' AND role='user'";
    $result = mysqli_query($conn, $sql);
    
    if(mysqli_num_rows($result) == 1){
        $user = mysqli_fetch_assoc($result);
        if(password_verify($password, $user['password'])){
            if($user['status'] == 'banned'){
                $error = "Your account has been banned. Contact admin.";
            } else {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['user_name'] = $user['fullname'];
                $_SESSION['user_username'] = $user['username'];
                $_SESSION['user_role'] = 'user';
                header("Location: index.php");
                exit();
            }
        } else {
            $error = "Invalid username or password";
        }
    } else {
        $error = "Invalid username or password";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Login - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="login-page">

    <div class="login-container">

        <div class="login-logo">
            <div class="icon-badge">
                <i class="fas fa-user"></i>
            </div>
        </div>

        <h1>CUSTOMER <span>LOGIN</span></h1>
        <p class="login-subtitle">Sign in to your account</p>

        <?php if($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <form action="login.php" method="POST">
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password" required>
            <button class="login-btn" type="submit">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>

        <p class="register-text">
            Don't have an account?
            <a href="register.php">Create Account</a>
        </p>

    </div>

</div>

</body>
</html>