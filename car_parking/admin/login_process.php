<?php
session_start();
include "../db.php";

if (isset($_POST['username']) && isset($_POST['password'])) {

    $username = $_POST['username'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM users WHERE username='$username' AND role='admin'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) == 1) {

        $row = mysqli_fetch_assoc($result);

        if (password_verify($password, $row['password'])) {

            $_SESSION['admin'] = $username;
            $_SESSION['admin_id'] = $row['id'];

            header("Location: index.php");
            exit();

        } else {

            header("Location: login.php?error=1");
            exit();

        }

    } else {

        header("Location: login.php?error=1");
        exit();

    }

} else {

    header("Location: login.php");
    exit();

}
?>