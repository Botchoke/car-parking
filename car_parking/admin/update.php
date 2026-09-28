<?php
include "../db.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $id = intval($_POST['id']);
    $plate = mysqli_real_escape_string($conn, $_POST['plate']);
    $owner = mysqli_real_escape_string($conn, $_POST['owner']);
    $slot = intval($_POST['slot']);

    $sql = "UPDATE vehicles
            SET plate_number='$plate',
                owner_name='$owner',
                slot_number='$slot'
            WHERE id='$id'";

    if (mysqli_query($conn, $sql)) {
        header("Location: index.php");
        exit();
    } else {
        echo "Database Error: " . mysqli_error($conn);
    }
}
?>