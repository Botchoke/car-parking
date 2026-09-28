<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];
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
        <div class="section-header">
            <h1 class="page-title"><i class="fas fa-car"></i> My Vehicles</h1>
            <a href="add_vehicle.php" class="btn-primary">
                <i class="fas fa-plus"></i> Add Vehicle
            </a>
        </div>

        <?php if(mysqli_num_rows($vehiclesQuery) > 0): ?>
        <div class="vehicles-grid-pro">
            <?php while($v = mysqli_fetch_assoc($vehiclesQuery)): ?>
            <div class="vehicle-card-pro wide" id="vehicle-<?php echo $v['id']; ?>">
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

<div class="notification-toast" id="successToast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Success!</span>
</div>

<script src="script.js"></script>
</body>
</html>