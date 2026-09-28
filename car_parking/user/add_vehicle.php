<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];
$error = '';

$uploadDir = __DIR__ . '/uploads/vehicles/';
if(!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $plate = strtoupper(mysqli_real_escape_string($conn, trim($_POST['plate_number'])));
    $type = mysqli_real_escape_string($conn, trim($_POST['vehicle_type']));
    $make = mysqli_real_escape_string($conn, trim($_POST['make']));
    $model = mysqli_real_escape_string($conn, trim($_POST['model']));
    $year = intval($_POST['year']);
    $color = mysqli_real_escape_string($conn, trim($_POST['color']));
    $category = mysqli_real_escape_string($conn, trim($_POST['category']));

    if(empty($plate) || empty($type) || empty($color) || empty($make)){
        $error = "Please fill in all required fields";
    } else {
        $check = mysqli_query($conn, "SELECT id FROM user_vehicles WHERE plate_number='$plate' AND user_id='$user_id'");
        if(mysqli_num_rows($check) > 0){
            $error = "This plate number is already registered to your account";
        } else {
            $photoPath = '';
            if(isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK){
                $allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/jpg'];
                $fileType = mime_content_type($_FILES['photo']['tmp_name']);

                if(!in_array($fileType, $allowed)){
                    $error = "Only JPG, PNG, or WEBP images are allowed";
                } elseif($_FILES['photo']['size'] > 5 * 1024 * 1024){
                    $error = "Photo must be less than 5MB";
                } else {
                    $ext = pathinfo($_FILES['photo']['name'], PATHINFO_EXTENSION);
                    $filename = 'vehicle_' . $user_id . '_' . time() . '_' . rand(1000, 9999) . '.' . strtolower($ext);
                    $targetPath = $uploadDir . $filename;

                    if(move_uploaded_file($_FILES['photo']['tmp_name'], $targetPath)){
                        $photoPath = 'uploads/vehicles/' . $filename;
                    } else {
                        $error = "Failed to upload photo";
                    }
                }
            }

            if(empty($error)){
                $sql = "INSERT INTO user_vehicles 
                        (user_id, plate_number, vehicle_type, make, model, year, color, category, photo) 
                        VALUES 
                        ('$user_id', '$plate', '$type', '$make', '$model', '$year', '$color', '$category', '$photoPath')";

                if(mysqli_query($conn, $sql)){
                    header("Location: add_vehicle.php?added=success");
                    exit();
                } else {
                    $error = "Failed to add vehicle: " . mysqli_error($conn);
                }
            }
        }
    }
}

$vehiclesQuery = mysqli_query($conn, "SELECT * FROM user_vehicles WHERE user_id='$user_id' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Vehicles - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="user-dashboard">

    <header class="user-header">
        <div class="user-brand">
            <h1><i class="fas fa-parking"></i> HYPERCAR <span>PARKING</span></h1>
        </div>
        <nav class="user-nav">
            <a href="index.php"><i class="fas fa-home"></i> Home</a>
            <a href="my_vehicles.php" class="active"><i class="fas fa-car"></i> My Vehicles</a>
            <a href="my_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-car"></i> My Vehicles</h1>

        <?php if($error): ?>
            <div class="error-message"><i class="fas fa-exclamation-circle"></i> <?php echo $error; ?></div>
        <?php endif; ?>

        <div class="two-column-layout">

            <div class="form-card">
                <h2><i class="fas fa-plus-circle"></i> Register New Vehicle</h2>

                <form action="add_vehicle.php" method="POST" enctype="multipart/form-data" class="styled-form">

                    <div class="vehicle-photo-upload">
                        <label for="photoInput" class="photo-dropzone" id="dropzone">
                            <i class="fas fa-camera"></i>
                            <span>Click to upload vehicle photo</span>
                            <small>JPG, PNG or WEBP — max 5MB</small>
                            <img id="photoPreview" style="display:none;" alt="Preview">
                        </label>
                        <input type="file" id="photoInput" name="photo" accept="image/*" style="display:none;">
                    </div>

                    <div class="form-group">
                        <label>Plate Number *</label>
                        <input type="text" name="plate_number" placeholder="e.g., ABC 1234" required maxlength="20" style="text-transform:uppercase;">
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Vehicle Type *</label>
                            <select name="vehicle_type" required>
                                <option value="">Select type</option>
                                <option value="Sedan">Sedan</option>
                                <option value="SUV">SUV</option>
                                <option value="Hatchback">Hatchback</option>
                                <option value="Pickup">Pickup</option>
                                <option value="Van">Van</option>
                                <option value="Motorcycle">Motorcycle</option>
                                <option value="Sports Car">Sports Car</option>
                                <option value="Electric">Electric</option>
                            </select>
                        </div>

                        <div class="form-group">
                            <label>Category *</label>
                            <select name="category" required>
                                <option value="Regular">Regular</option>
                                <option value="Premium">Premium</option>
                                <option value="Motorcycle">Motorcycle</option>
                                <option value="Truck">Truck</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Make / Brand *</label>
                            <input type="text" name="make" placeholder="e.g., Toyota, Honda" required>
                        </div>

                        <div class="form-group">
                            <label>Model</label>
                            <input type="text" name="model" placeholder="e.g., Camry, Civic">
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label>Year</label>
                            <input type="number" name="year" min="1950" max="<?php echo date('Y') + 1; ?>" placeholder="e.g., 2020">
                        </div>

                        <div class="form-group">
                            <label>Color *</label>
                            <input type="text" name="color" placeholder="e.g., Black, White" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-primary">
                        <i class="fas fa-plus"></i> Register Vehicle
                    </button>
                </form>
            </div>

            <div class="list-card">
                <h2><i class="fas fa-list"></i> Registered Vehicles (<?php echo mysqli_num_rows($vehiclesQuery); ?>)</h2>

                <?php if(mysqli_num_rows($vehiclesQuery) > 0): ?>
                <div class="vehicle-list-pro">
                    <?php while($v = mysqli_fetch_assoc($vehiclesQuery)): ?>
                    <div class="vehicle-card-pro">
                        <div class="vehicle-photo-wrap">
                            <?php if(!empty($v['photo']) && file_exists(__DIR__ . '/' . $v['photo'])): ?>
                                <img src="<?php echo htmlspecialchars($v['photo']); ?>" alt="Vehicle">
                            <?php else: ?>
                                <div class="no-photo"><i class="fas fa-car"></i></div>
                            <?php endif; ?>
                            <span class="category-tag <?php echo strtolower($v['category'] ?? 'regular'); ?>">
                                <?php echo htmlspecialchars($v['category'] ?? 'Regular'); ?>
                            </span>
                        </div>
                        <div class="vehicle-card-body">
                            <div class="vehicle-plate"><?php echo htmlspecialchars($v['plate_number']); ?></div>
                            <div class="vehicle-title">
                                <?php 
                                    $title = trim(($v['year'] ?? '') . ' ' . ($v['make'] ?? '') . ' ' . ($v['model'] ?? ''));
                                    echo htmlspecialchars($title ?: $v['vehicle_type']);
                                ?>
                            </div>
                            <div class="vehicle-meta">
                                <span><i class="fas fa-palette"></i> <?php echo htmlspecialchars($v['color']); ?></span>
                                <span><i class="fas fa-car-side"></i> <?php echo htmlspecialchars($v['vehicle_type']); ?></span>
                            </div>
                        </div>
                        <a href="delete_vehicle.php?id=<?php echo $v['id']; ?>" 
                           class="vehicle-delete-btn" 
                           onclick="return confirm('Delete this vehicle?')">
                            <i class="fas fa-trash"></i>
                        </a>
                    </div>
                    <?php endwhile; ?>
                </div>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-car"></i>
                    <p>No vehicles registered yet.</p>
                    <a href="add_vehicle.php">Register your first vehicle</a>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="notification-toast" id="successToast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Success!</span>
</div>

<script>
const photoInput = document.getElementById('photoInput');
const photoPreview = document.getElementById('photoPreview');
const dropzone = document.getElementById('dropzone');

photoInput.addEventListener('change', function(){
    const file = this.files[0];
    if(file){
        const reader = new FileReader();
        reader.onload = function(e){
            photoPreview.src = e.target.result;
            photoPreview.style.display = 'block';
            dropzone.classList.add('has-photo');
        };
        reader.readAsDataURL(file);
    }
});

dropzone.addEventListener('dragover', e => { e.preventDefault(); dropzone.classList.add('drag-over'); });
dropzone.addEventListener('dragleave', () => dropzone.classList.remove('drag-over'));
dropzone.addEventListener('drop', e => {
    e.preventDefault();
    dropzone.classList.remove('drag-over');
    if(e.dataTransfer.files.length){
        photoInput.files = e.dataTransfer.files;
        photoInput.dispatchEvent(new Event('change'));
    }
});
</script>

<script src="script.js"></script>
</body>
</html>