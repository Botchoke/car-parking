<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];
$id = intval($_GET['id'] ?? 0);

$check = mysqli_query($conn, "SELECT photo FROM user_vehicles WHERE id='$id' AND user_id='$user_id'");
if($row = mysqli_fetch_assoc($check)){
    if(!empty($row['photo'])){
        $photoFile = __DIR__ . '/' . $row['photo'];
        if(file_exists($photoFile)) unlink($photoFile);
    }
    mysqli_query($conn, "DELETE FROM user_vehicles WHERE id='$id' AND user_id='$user_id'");
}

header("Location: my_vehicles.php?deleted=success");
exit();
?>