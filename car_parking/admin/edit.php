<?php

include "../db.php";

$id = intval($_GET['id']);

$result = mysqli_query($conn,"SELECT * FROM vehicles WHERE id='$id'");
$row = mysqli_fetch_assoc($result);

?>


<!DOCTYPE html>
<html>


<head>
    <title>Edit Vehicle</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
</head>


<body>


<div class="container">


<div class="edit-wrapper">


<div class="edit-card">


<h2>EDIT VEHICLE</h2>


<form action="update.php" method="POST" class="edit-form">


<input type="hidden" name="id" value="<?php echo $row['id']; ?>">


<label>Plate Number</label>
<input type="text" name="plate" value="<?php echo htmlspecialchars($row['plate_number']); ?>" required>


<label>Owner Name</label>
<input type="text" name="owner" value="<?php echo htmlspecialchars($row['owner_name']); ?>" required>


<label>Slot Number</label>
<input type="number" name="slot" value="<?php echo $row['slot_number']; ?>" required>


<button class="update-btn">UPDATE VEHICLE</button>


</form>


<a class="back-btn" href="index.php">⬅ Back to Dashboard</a>


</div>


</div>


</div>


</body>


</html>