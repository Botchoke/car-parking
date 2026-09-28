<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] != 'POST' || ($_POST['action'] ?? '') != 'reserve'){
    header("Location: index.php");
    exit();
}

$vehicle_id = intval($_POST['vehicle_id']);
$duration = intval($_POST['duration']);
$price = floatval($_POST['price']);
$plan_name = mysqli_real_escape_string($conn, $_POST['plan_name']);
$reservation_date = mysqli_real_escape_string($conn, $_POST['reservation_date']);
$reservation_time = mysqli_real_escape_string($conn, $_POST['reservation_time']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

// Verify vehicle belongs to this user
$vQuery = mysqli_query($conn, "SELECT * FROM user_vehicles WHERE id='$vehicle_id' AND user_id='$user_id'");
if(mysqli_num_rows($vQuery) == 0){
    header("Location: index.php?error=" . urlencode("Vehicle not found"));
    exit();
}
$vehicle = mysqli_fetch_assoc($vQuery);

// Prevent duplicate reservations for same date/time
$dupCheck = mysqli_query($conn, "
    SELECT id FROM reservations 
    WHERE user_id='$user_id' 
    AND reservation_date='$reservation_date' 
    AND reservation_time='$reservation_time'
    AND status IN ('pending','approved')
");
if(mysqli_num_rows($dupCheck) > 0){
    header("Location: index.php?error=" . urlencode("You already have a reservation at that time"));
    exit();
}

// Insert reservation
mysqli_query($conn, "
    INSERT INTO reservations
    (user_id, vehicle_id, plate_number, plan_name, duration_hours,
     reservation_date, reservation_time, price, payment_method, status)
    VALUES
    ('$user_id', '$vehicle_id', '{$vehicle['plate_number']}', '$plan_name', '$duration',
     '$reservation_date', '$reservation_time', '$price', '$payment_method', 'pending')
");

// Get the new reservation ID
$new_id = mysqli_insert_id($conn);

// ============================================
// GENERATE CHECK-IN CODE + QR TOKEN
// ============================================
$checkin_code = strtoupper(substr(md5($new_id . $vehicle['plate_number'] . time()), 0, 6));
$qr_token = md5($new_id . $user_id . $vehicle['plate_number'] . time());

mysqli_query($conn, "
    UPDATE reservations 
    SET checkin_code = '$checkin_code', qr_token = '$qr_token' 
    WHERE id = '$new_id'
");

header("Location: index.php?reserved=success");
exit();
?>