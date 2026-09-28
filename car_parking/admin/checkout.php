<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include "../db.php";

if(isset($_GET['id'])) {
    $id = intval($_GET['id']);
    
    $query = mysqli_query($conn, "SELECT * FROM vehicles WHERE id = '$id'");
    $vehicle = mysqli_fetch_assoc($query);
    
    if($vehicle) {
        $time_in = strtotime($vehicle['time_in']);
        $time_out = time();
        $hours_parked = max(1, ceil(($time_out - $time_in) / 3600));
        $fee = $hours_parked * 10;
        
        $payment_method = isset($_POST['payment_method']) ? mysqli_real_escape_string($conn, $_POST['payment_method']) : 'Cash';
        
        $insert = mysqli_query($conn, "
            INSERT INTO history (
                plate_number, 
                owner_name, 
                slot_number, 
                time_in, 
                time_out, 
                total_fee,
                payment_method
            ) VALUES (
                '{$vehicle['plate_number']}',
                '{$vehicle['owner_name']}',
                '{$vehicle['slot_number']}',
                '{$vehicle['time_in']}',
                NOW(),
                '$fee',
                '$payment_method'
            )
        ");
        
        mysqli_query($conn, "DELETE FROM vehicles WHERE id = '$id'");
        
        header("Location: index.php?checkout=success&fee=" . $fee);
        exit();
    }
}

header("Location: index.php");
exit();
?>