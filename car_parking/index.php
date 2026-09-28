<?php
session_start();

if (isset($_SESSION['admin'])) {
    header("Location: admin/index.php");
    exit();
}

if (isset($_SESSION['user_id'])) {
    header("Location: user/index.php");
    exit();
}

header("Location: user/login.php");
exit();
?>