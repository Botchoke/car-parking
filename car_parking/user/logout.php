<?php
session_start();
unset($_SESSION['user_id']);
unset($_SESSION['user_name']);
unset($_SESSION['user_username']);
unset($_SESSION['user_role']);
session_destroy();
header("Location: login.php");
exit();
?>