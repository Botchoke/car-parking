<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

if($search) {
    $history = mysqli_query($conn, "
        SELECT * FROM parking_sessions 
        WHERE user_id='$user_id' AND status='completed'
        AND (plate_number LIKE '%$search%' OR slot_number LIKE '%$search%')
        ORDER BY id DESC
    ");
} else {
    $history = mysqli_query($conn, "
        SELECT * FROM parking_sessions 
        WHERE user_id='$user_id' AND status='completed'
        ORDER BY id DESC
    ");
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Parking History - Hypercar Parking</title>
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
            <a href="my_vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
            <a href="my_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
            <a href="history.php" class="active"><i class="fas fa-history"></i> History</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-history"></i> My Parking History</h1>

        <div class="history-search" style="margin-bottom:20px;">
            <form method="GET" action="history.php" style="display:flex;background:rgba(10,15,35,0.8);border-radius:12px;border:1px solid rgba(79,124,172,0.2);padding:4px 16px;">
                <i class="fas fa-search" style="color:#4F7CAC;align-self:center;"></i>
                <input type="text" name="search" placeholder="Search by plate or slot..." value="<?php echo htmlspecialchars($search); ?>" style="flex:1;background:transparent;border:none;padding:12px 14px;color:#9cfaff;outline:none;">
                <button type="submit" style="background:linear-gradient(135deg,#4F7CAC,#2F5A86);border:none;color:white;padding:8px 20px;border-radius:8px;font-weight:600;cursor:pointer;">Search</button>
            </form>
        </div>

        <?php if(mysqli_num_rows($history) > 0): ?>
        <div class="history-table-wrapper">
            <table class="user-history-table">
                <thead>
                    <tr>
                        <th>Plate</th>
                        <th>Slot</th>
                        <th>Plan</th>
                        <th>Time In</th>
                        <th>Time Out</th>
                        <th>Amount</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($h = mysqli_fetch_assoc($history)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($h['plate_number']); ?></strong></td>
                        <td><?php echo $h['slot_number']; ?></td>
                        <td><?php echo htmlspecialchars($h['plan_name']); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($h['time_in'])); ?></td>
                        <td><?php echo date('M d, H:i', strtotime($h['time_out'])); ?></td>
                        <td class="amount">
                            ₱<?php echo number_format($h['total_fee'], 2); ?>
                            <?php if(!empty($h['overtime_hours']) && $h['overtime_hours'] > 0): ?>
                                <small class="overtime-tag">+<?php echo $h['overtime_hours']; ?>h overtime</small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($h['payment_method']); ?></td>
                        <td><span class="badge-paid">Paid</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-parking"></i>
            <p>No parking history found.</p>
            <a href="index.php">Start Parking</a>
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