<?php
include "../db.php";

if($_SERVER["REQUEST_METHOD"] == "POST"){

    $plate = mysqli_real_escape_string($conn, $_POST['plate']);
    $owner = mysqli_real_escape_string($conn, $_POST['owner']);
    $slot = intval($_POST['slot']);
    
    $sql = "INSERT INTO vehicles (plate_number, owner_name, slot_number, time_in) 
            VALUES ('$plate', '$owner', '$slot', NOW())";

    if(mysqli_query($conn, $sql)){
        header("Location: index.php?added=success");
        exit();
    }else{
        echo "Error: " . mysqli_error($conn);
    }
}
?>