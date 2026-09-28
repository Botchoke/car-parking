<?php
session_start();
include "../db.php";

if(isset($_SESSION['user_id'])){
    header("Location: index.php");
    exit();
}

$error = '';
$success = '';

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $fullname = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $email = mysqli_real_escape_string($conn, trim($_POST['email']));
    $phone = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $username = mysqli_real_escape_string($conn, trim($_POST['username']));
    $password = $_POST['password'];
    $confirm = $_POST['confirm_password'];
    
    if(empty($fullname) || empty($email) || empty($phone) || empty($username) || empty($password)){
        $error = "All fields are required";
    } elseif(!filter_var($email, FILTER_VALIDATE_EMAIL)){
        $error = "Invalid email format";
    } elseif($password !== $confirm){
        $error = "Passwords do not match";
    } elseif(strlen($password) < 6){
        $error = "Password must be at least 6 characters";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM users WHERE username='$username' OR email='$email'");
        if(mysqli_num_rows($check) > 0){
            $error = "Username or email already exists";
        } else {
            $hashed = password_hash($password, PASSWORD_DEFAULT);
            $sql = "INSERT INTO users (fullname, email, phone, username, password, role, status) 
                    VALUES ('$fullname', '$email', '$phone', '$username', '$hashed', 'user', 'active')";
            if(mysqli_query($conn, $sql)){
                $success = "Account created! You can now <a href='login.php' style='color:#00f7ff;'>login</a>.";
            } else {
                $error = "Registration failed: " . mysqli_error($conn);
            }
        }
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Customer Registration - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="login-page">

    <div class="login-container" style="width:420px;">

        <div class="login-logo">
            <div class="icon-badge">
                <i class="fas fa-user-plus"></i>
            </div>
        </div>

        <h1>CREATE <span>ACCOUNT</span></h1>
        <p class="login-subtitle">Join Hypercar Parking</p>

        <?php if($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <?php if($success): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> <?php echo $success; ?></div>
        <?php endif; ?>

        <form action="register.php" method="POST">
            <input type="text" name="fullname" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="text" name="phone" placeholder="Phone Number" required>
            <input type="text" name="username" placeholder="Username" required>
            <input type="password" name="password" placeholder="Password (min 6 characters)" required>
            <input type="password" name="confirm_password" placeholder="Confirm Password" required>
            <button class="login-btn" type="submit">
                <i class="fas fa-user-plus"></i> Create Account
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