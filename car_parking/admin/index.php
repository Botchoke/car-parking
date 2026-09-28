<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include "../db.php";

$result = mysqli_query($conn,"SELECT * FROM vehicles");

$totalCars = mysqli_num_rows($result);
$occupied = $totalCars;
$available = 50 - $occupied;

$pendingQuery = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM reservations WHERE status='pending'");
$pendingCount = mysqli_fetch_assoc($pendingQuery)['cnt'] ?? 0;

$approvedQuery = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM reservations WHERE status='approved'");
$approvedCount = mysqli_fetch_assoc($approvedQuery)['cnt'] ?? 0;

$usersQuery = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM users WHERE role='user'");
$usersCount = mysqli_fetch_assoc($usersQuery)['cnt'] ?? 0;

$urgentQ = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM parking_sessions WHERE status='active' AND exit_requested=1");
$urgentCount = mysqli_fetch_assoc($urgentQ)['cnt'] ?? 0;

$overtimeQ = mysqli_query($conn, "SELECT time_in, duration_hours FROM parking_sessions WHERE status='active'");
$overtimeCount = 0;
while($o = mysqli_fetch_assoc($overtimeQ)){
    $otStart = strtotime($o['time_in']);
    $otPlanned = $o['duration_hours'] * 3600;
    if((time() - $otStart) > ($otPlanned + 900)) $overtimeCount++;
}

$todayRevQ = mysqli_query($conn, "SELECT SUM(total_fee) AS rev FROM history WHERE DATE(time_out) = CURDATE()");
$todayRev = mysqli_fetch_assoc($todayRevQ)['rev'] ?? 0;

$monthRevQ = mysqli_query($conn, "SELECT SUM(total_fee) AS rev FROM history WHERE MONTH(time_out) = MONTH(CURDATE()) AND YEAR(time_out) = YEAR(CURDATE())");
$monthRev = mysqli_fetch_assoc($monthRevQ)['rev'] ?? 0;
?>
<!DOCTYPE html>
<html>
<head>
<title>Hypercar Parking HUD</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<link rel="stylesheet" href="style.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="container">

<h1 class="title">⚡ HYPERCAR PARKING HUD</h1>

<div class="nav">
<a href="history.php">History</a>
<a href="revenue.php">Revenue</a>
<a href="manage_users.php">Users</a>
<a href="manage_reservations.php">Reservations
<?php if($pendingCount > 0): ?>
<span style="background:red;padding:2px 8px;border-radius:20px;font-size:12px;"><?php echo $pendingCount; ?></span>
<?php endif; ?>
</a>
<a href="logout.php">Logout</a>
</div>

<?php if($urgentCount > 0 || $overtimeCount > 0): ?>
<div class="admin-alerts">
    <?php if($urgentCount > 0): ?>
        <div class="alert-item urgent">
            <i class="fas fa-door-open"></i>
            <strong><?php echo $urgentCount; ?></strong> customer<?php echo $urgentCount > 1 ? 's' : ''; ?> waiting to leave
        </div>
    <?php endif; ?>
    <?php if($overtimeCount > 0): ?>
        <div class="alert-item overtime">
            <i class="fas fa-exclamation-triangle"></i>
            <strong><?php echo $overtimeCount; ?></strong> vehicle<?php echo $overtimeCount > 1 ? 's' : ''; ?> overtime
        </div>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- STAT CARDS -->
<div class="cards">

<div class="card">
<h3>Total Cars</h3>
<p class="counter" data-target="<?php echo $totalCars ?>">0</p>
</div>

<div class="card">
<h3>Occupied Slots</h3>
<p class="counter" data-target="<?php echo $occupied ?>">0</p>
</div>

<div class="card">
<h3>Available Slots</h3>
<p class="counter" data-target="<?php echo $available ?>">0</p>
</div>

<div class="card">
<h3>Registered Users</h3>
<p class="counter" data-target="<?php echo $usersCount ?>">0</p>
</div>

</div>

<!-- QUICK ACTIONS -->
<h2 class="section">QUICK ACTIONS</h2>

<div class="quick-panel">

    <a href="scan.php" class="quick-tile scan-tile">
        <div class="quick-icon">
            <i class="fas fa-qrcode"></i>
        </div>
        <div class="quick-info">
            <h4>Scan QR</h4>
            <p>Check-in with QR code</p>
        </div>
    </a>

    <a href="manage_reservations.php" class="quick-tile reserve-tile">
        <div class="quick-icon">
            <i class="fas fa-calendar-check"></i>
        </div>
        <div class="quick-info">
            <h4>Reservations</h4>
            <p><?php echo $pendingCount; ?> pending · <?php echo $approvedCount; ?> approved</p>
        </div>
        <?php if($pendingCount > 0): ?>
        <span class="quick-badge"><?php echo $pendingCount; ?></span>
        <?php endif; ?>
    </a>

    <a href="revenue.php" class="quick-tile revenue-tile">
        <div class="quick-icon">
            <i class="fas fa-chart-line"></i>
        </div>
        <div class="quick-info">
            <h4>Today's Revenue</h4>
            <p>₱<?php echo number_format($todayRev, 2); ?></p>
        </div>
    </a>

    <a href="revenue.php" class="quick-tile money-tile">
        <div class="quick-icon">
            <i class="fas fa-money-bill-wave"></i>
        </div>
        <div class="quick-info">
            <h4>This Month</h4>
            <p>₱<?php echo number_format($monthRev, 2); ?></p>
        </div>
    </a>

</div>

<h2 class="section">ACTIVE VEHICLES</h2>

<input type="text" id="searchBar" placeholder="Search Plate or Owner">

<div class="table-container">

<table id="vehicleTable">

<thead>
<tr>
<th>ID</th>
<th>Plate Number</th>
<th>Owner</th>
<th>Type</th>
<th>Slot</th>
<th>Time Parked</th>
<th>Fee</th>
<th>Action</th>
</tr>
</thead>

<tbody>

<?php 
mysqli_data_seek($result, 0);
while($row=mysqli_fetch_assoc($result)){ 
    $plate = mysqli_real_escape_string($conn, $row['plate_number']);
    $sessQ = mysqli_query($conn, "
        SELECT * FROM parking_sessions 
        WHERE plate_number='$plate' AND status='active' 
        ORDER BY id DESC LIMIT 1
    ");
    $sess = mysqli_fetch_assoc($sessQ);

    $hasOvertime = false;
    $overtimeHours = 0;
    $overtimeFee = 0;
    $exitRequested = false;
    $baseFee = 0;

    if($sess){
        $baseFee = $sess['total_fee'];  // Fixed fee from customer's plan
        $start = strtotime($sess['time_in']);
        $planned = $sess['duration_hours'] * 3600;
        $elapsed = time() - $start;
        $grace = 15 * 60;
        $overtimeSecs = max(0, $elapsed - $planned - $grace);
        $overtimeHours = ceil($overtimeSecs / 3600);
        $overtimeFee = $overtimeHours * 20;
        $hasOvertime = $overtimeHours > 0;
        $exitRequested = !empty($sess['exit_requested']);
    }

    $source = $row['source'] ?? 'walk-in';
    $sourceLabel = 'Walk-in';
    $sourceClass = 'src-walkin';
    if($source === 'user'){ $sourceLabel = 'Customer'; $sourceClass = 'src-user'; }
    if($source === 'reservation'){ $sourceLabel = 'Reserved'; $sourceClass = 'src-reservation'; }

    $totalDue = $baseFee + $overtimeFee;
?>

<tr class="<?php echo $exitRequested ? 'row-exit-request' : ($hasOvertime ? 'row-overtime' : ''); ?>">

<td><?php echo $row['id']; ?></td>
<td>
    <?php echo htmlspecialchars($row['plate_number']); ?>
    <?php if($exitRequested): ?>
        <span class="exit-badge"><i class="fas fa-door-open"></i> LEAVING</span>
    <?php endif; ?>
</td>
<td><?php echo htmlspecialchars($row['owner_name']); ?></td>
<td>
    <span class="source-badge <?php echo $sourceClass; ?>">
        <i class="fas fa-<?php echo $source === 'walk-in' ? 'walking' : ($source === 'user' ? 'user' : 'calendar-check'); ?>"></i>
        <?php echo $sourceLabel; ?>
    </span>
</td>
<td><?php echo $row['slot_number']; ?></td>

<td class="timer" data-time="<?php echo $row['time_in']; ?>">0:00</td>
<td class="fee">
    <?php if($sess): ?>
        <?php if($hasOvertime): ?>
            <span class="fee-base">₱<?php echo number_format($baseFee); ?></span>
            <span class="fee-overtime">+₱<?php echo number_format($overtimeFee); ?> OT</span>
            <div class="fee-total">₱<?php echo number_format($totalDue); ?></div>
        <?php else: ?>
            ₱<?php echo number_format($baseFee); ?>
        <?php endif; ?>
    <?php else: ?>
        —
    <?php endif; ?>
</td>

<td>
    <a class="editbtn" href="edit.php?id=<?php echo $row['id']; ?>">EDIT</a>
    <a class="exitbtn <?php echo $exitRequested ? 'urgent' : ''; ?>" 
       href="exit.php?exit_id=<?php echo $row['id']; ?>">
        EXIT<?php echo $exitRequested ? ' ⚡' : ''; ?>
    </a>
</td>

</tr>

<?php } ?>

</tbody>
</table>

</div>

</div>

<script>

const searchBar = document.getElementById("searchBar");

searchBar.addEventListener("keyup", function () {

    const value = this.value.toLowerCase();
    const rows = document.querySelectorAll("#vehicleTable tbody tr");

    rows.forEach(row => {

        const plate = row.children[1].textContent.toLowerCase();
        const owner = row.children[2].textContent.toLowerCase();

        if (plate.includes(value) || owner.includes(value)) {
            row.style.display = "";
        } else {
            row.style.display = "none";
        }

    });

});

</script>

<script src="script.js"></script>

</body>
</html>