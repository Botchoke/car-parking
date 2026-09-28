<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];

if($_SERVER['REQUEST_METHOD'] != 'POST' || ($_POST['action'] ?? '') != 'park_now'){
    header("Location: index.php");
    exit();
}

$vehicle_id = intval($_POST['vehicle_id']);
$duration = intval($_POST['duration']);
$price = floatval($_POST['price']);
$plan_name = mysqli_real_escape_string($conn, $_POST['plan_name']);
$payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);

// Verify vehicle belongs to this user
$vQuery = mysqli_query($conn, "SELECT * FROM user_vehicles WHERE id='$vehicle_id' AND user_id='$user_id'");
if(mysqli_num_rows($vQuery) == 0){
    header("Location: index.php?error=" . urlencode("Vehicle not found"));
    exit();
}
$vehicle = mysqli_fetch_assoc($vQuery);

// Prevent same vehicle from being parked twice
$dupCheck = mysqli_query($conn, "
    SELECT id FROM parking_sessions 
    WHERE user_id='$user_id' 
    AND vehicle_id='$vehicle_id'
    AND status='active'
");
if(mysqli_num_rows($dupCheck) > 0){
    header("Location: index.php?error=" . urlencode("This vehicle is already parked"));
    exit();
}

// Find an available slot (1..50)
$slotQuery = mysqli_query($conn, "
    SELECT n AS slot_number FROM (
        SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
        UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
        UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
        UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
        UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
        UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
        UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
        UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
        UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
        UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50
    ) nums
    WHERE n NOT IN (SELECT slot_number FROM vehicles)
    ORDER BY n LIMIT 1
");
$slotRow = mysqli_fetch_assoc($slotQuery);

if(!$slotRow){
    header("Location: index.php?error=" . urlencode("No available parking slots right now"));
    exit();
}
$slot = $slotRow['slot_number'];

// Add to vehicles (active)
mysqli_query($conn, "
    INSERT INTO vehicles (plate_number, owner_name, slot_number, time_in, user_id, source)
    VALUES ('{$vehicle['plate_number']}', '{$_SESSION['user_name']}', '$slot', NOW(), '$user_id', 'user')
");

// Create parking session
mysqli_query($conn, "
    INSERT INTO parking_sessions
    (user_id, vehicle_id, plate_number, slot_number, plan_name, duration_hours, total_fee, payment_method, status)
    VALUES
    ('$user_id', '$vehicle_id', '{$vehicle['plate_number']}', '$slot',
     '$plan_name', '$duration', '$price', '$payment_method', 'active')
");

header("Location: index.php?parked=success");
exit();
?>