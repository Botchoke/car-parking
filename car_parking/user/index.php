<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];

$userQuery = mysqli_query($conn, "SELECT * FROM users WHERE id='$user_id'");
$user = mysqli_fetch_assoc($userQuery);

// Get ALL active sessions
$activeSessions = [];
$activeQuery = mysqli_query($conn, "
    SELECT * FROM parking_sessions 
    WHERE user_id='$user_id' AND status='active' 
    ORDER BY time_in DESC
");
while($a = mysqli_fetch_assoc($activeQuery)){
    $activeSessions[] = $a;
}

$vehiclesQuery = mysqli_query($conn, "SELECT * FROM user_vehicles WHERE user_id='$user_id' ORDER BY id DESC");
$vehicles = [];
while($v = mysqli_fetch_assoc($vehiclesQuery)){
    $vehicles[] = $v;
}

$historyQuery = mysqli_query($conn, "
    SELECT * FROM parking_sessions 
    WHERE user_id='$user_id' AND status='completed' 
    ORDER BY id DESC LIMIT 5
");

$parkingOptions = [
    ['id' => 1, 'name' => '1 Hour',   'duration' => 1,  'price' => 30,  'description' => 'Perfect for quick errands'],
    ['id' => 2, 'name' => '2 Hours',  'duration' => 2,  'price' => 55,  'description' => 'Great for shopping'],
    ['id' => 3, 'name' => '3 Hours',  'duration' => 3,  'price' => 80,  'description' => 'Ideal for meetings'],
    ['id' => 4, 'name' => '4 Hours',  'duration' => 4,  'price' => 100, 'description' => 'Half-day parking'],
    ['id' => 5, 'name' => '6 Hours',  'duration' => 6,  'price' => 140, 'description' => 'Extended stay'],
    ['id' => 6, 'name' => '8 Hours',  'duration' => 8,  'price' => 180, 'description' => 'Full workday'],
    ['id' => 7, 'name' => '12 Hours', 'duration' => 12, 'price' => 250, 'description' => 'Overnight parking'],
    ['id' => 8, 'name' => '24 Hours', 'duration' => 24, 'price' => 400, 'description' => 'Full day access'],
];
?>
<!DOCTYPE html>
<html>
<head>
    <title>User Dashboard - Hypercar Parking</title>
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
            <a href="index.php" class="active"><i class="fas fa-home"></i> Home</a>
            <a href="my_vehicles.php"><i class="fas fa-car"></i> My Vehicles</a>
            <a href="my_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
        <div class="user-info">
            <i class="fas fa-user-circle"></i>
            <span><?php echo htmlspecialchars($user['fullname']); ?></span>
        </div>
    </header>

    <section class="user-welcome">
        <div class="welcome-content">
            <h2>Welcome, <strong><?php echo htmlspecialchars($user['fullname']); ?></strong></h2>
            <p>Thank you for choosing Hypercar Parking. You can park multiple vehicles with separate plans at the same time.</p>
            <div class="welcome-features">
                <span><i class="fas fa-shield-alt"></i> 24/7 Security</span>
                <span><i class="fas fa-clock"></i> Flexible Hours</span>
                <span><i class="fas fa-credit-card"></i> Easy Payment</span>
                <span><i class="fas fa-car"></i> 50 Slots Available</span>
            </div>
        </div>
    </section>

    <!-- ACTIVE SESSIONS -->
    <?php if(count($activeSessions) > 0): ?>
    <section class="active-sessions-section">
        <h2 class="section-title">
            <i class="fas fa-clock"></i> 
            Active Parking Sessions 
            <span class="active-count"><?php echo count($activeSessions); ?></span>
        </h2>
        <p class="section-subtitle">You currently have <?php echo count($activeSessions); ?> vehicle<?php echo count($activeSessions) > 1 ? 's' : ''; ?> parked.</p>

        <div class="active-sessions-grid">
            <?php foreach($activeSessions as $sess): 
                $start = strtotime($sess['time_in']);
                $planned = $sess['duration_hours'] * 3600;
                $elapsed = time() - $start;
                $grace = 15 * 60;
                $overtimeSecs = max(0, $elapsed - $planned - $grace);
                $overtimeHours = ceil($overtimeSecs / 3600);
                $overtimeFee = $overtimeHours * 20;
                $isOver = $overtimeHours > 0;

                // Get vehicle photo
                $photoPath = '';
                $vehicleId = $sess['vehicle_id'];
                $vQuery = mysqli_query($conn, "SELECT photo FROM user_vehicles WHERE id='$vehicleId'");
                if($vRow = mysqli_fetch_assoc($vQuery)){
                    if(!empty($vRow['photo']) && file_exists(__DIR__ . '/' . $vRow['photo'])){
                        $photoPath = $vRow['photo'];
                    }
                }
            ?>
            <div class="active-session-card <?php echo $isOver ? 'overdue' : ''; ?>">

                <!-- Car photo on top (clear, no dark overlay) -->
                <div class="active-session-image">
                    <?php if($photoPath): ?>
                        <img src="<?php echo htmlspecialchars($photoPath); ?>" alt="Vehicle">
                    <?php else: ?>
                        <div class="active-session-no-image"><i class="fas fa-car"></i></div>
                    <?php endif; ?>

                    <!-- Plate + slot overlay on the photo -->
                    <div class="active-session-header">
                        <div class="active-plate">
                            <i class="fas fa-car"></i>
                            <span><?php echo htmlspecialchars($sess['plate_number']); ?></span>
                        </div>
                        <div class="active-slot">Slot #<?php echo $sess['slot_number']; ?></div>
                    </div>
                </div>

                <!-- Info below the photo -->
                <div class="active-session-body">
                    <div class="active-info">
                        <span><i class="fas fa-list"></i> <?php echo htmlspecialchars($sess['plan_name']); ?></span>
                        <span><i class="fas fa-clock"></i> Started: <?php echo date('h:i A', $start); ?></span>
                    </div>
                    <div class="active-timer" data-time="<?php echo $sess['time_in']; ?>">0h 0m 0s</div>
                    <div class="active-status">
                        <?php if($isOver): ?>
                            <span class="status-overtime"><i class="fas fa-exclamation-triangle"></i> OVERDUE +₱<?php echo number_format($overtimeFee); ?></span>
                        <?php else: ?>
                            <span class="status-ok"><i class="fas fa-check-circle"></i> ON TIME</span>
                        <?php endif; ?>
                    </div>
                </div>
                <a href="active_session.php?id=<?php echo $sess['id']; ?>" class="btn-view-session">
                    <i class="fas fa-info-circle"></i> View Details
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>
    <?php endif; ?>

    <!-- PARKING PLANS -->
    <section class="parking-options-section">
        <h2 class="section-title"><i class="fas fa-list"></i> Choose Your Parking Plan</h2>
        <p class="section-subtitle">Click any plan to choose <strong>Park Now</strong> or <strong>Reserve</strong> for later.</p>

        <div class="policy-notice">
            <div class="policy-icon">
                <i class="fas fa-info-circle"></i>
            </div>
            <div class="policy-content">
                <h4><i class="fas fa-exclamation-triangle"></i> Important: Overtime Policy</h4>
                <p>
                    All plans include a <strong>15-minute grace period</strong>. After that, overtime is charged at
                    <strong>₱20 per hour (rounded up)</strong>.
                </p>
            </div>
        </div>

        <div class="parking-options-grid">
            <?php foreach($parkingOptions as $option): ?>
            <div class="parking-option-card" 
                 data-plan="<?php echo $option['id']; ?>"
                 onclick='selectOption(<?php echo json_encode($option, JSON_HEX_APOS | JSON_HEX_QUOT); ?>)'
                 role="button" 
                 tabindex="0">
                <div class="option-badge"><?php echo $option['duration']; ?>H</div>
                <h3><?php echo $option['name']; ?></h3>
                <div class="option-price">₱<?php echo $option['price']; ?></div>
                <p class="option-desc"><?php echo $option['description']; ?></p>
                <button type="button" class="btn-select">
                    Select <i class="fas fa-arrow-right"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- QUICK ACTIONS -->
    <section class="quick-actions">
        <h2 class="section-title"><i class="fas fa-bolt"></i> Quick Actions</h2>
        <div class="actions-grid">
            <a href="add_vehicle.php" class="action-card">
                <i class="fas fa-plus-circle"></i>
                <h3>Add Vehicle</h3>
                <p>Register a new vehicle</p>
            </a>
            <a href="my_reservations.php" class="action-card">
                <i class="fas fa-calendar-check"></i>
                <h3>My Reservations</h3>
                <p>View bookings</p>
            </a>
            <a href="my_vehicles.php" class="action-card">
                <i class="fas fa-car"></i>
                <h3>My Vehicles</h3>
                <p>Manage vehicles</p>
            </a>
            <a href="history.php" class="action-card">
                <i class="fas fa-history"></i>
                <h3>Parking History</h3>
                <p>View past sessions</p>
            </a>
        </div>
    </section>

    <!-- MY VEHICLES -->
    <section class="my-vehicles-preview">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-car"></i> My Vehicles</h2>
            <a href="my_vehicles.php" class="btn-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if(count($vehicles) > 0): ?>
        <div class="vehicles-preview-grid">
            <?php foreach(array_slice($vehicles, 0, 3) as $vehicle): 
                $hasPhoto = !empty($vehicle['photo']) && file_exists(__DIR__ . '/' . $vehicle['photo']);
            ?>
            <a href="my_vehicles.php#vehicle-<?php echo $vehicle['id']; ?>" class="vehicle-preview-card">
                <div class="vehicle-preview-image-wrap">
                    <?php if($hasPhoto): ?>
                        <img src="<?php echo htmlspecialchars($vehicle['photo']); ?>" alt="Vehicle" class="vehicle-preview-img">
                    <?php else: ?>
                        <div class="vehicle-preview-no-image"><i class="fas fa-car"></i></div>
                    <?php endif; ?>
                    <span class="vehicle-preview-category"><?php echo htmlspecialchars($vehicle['category'] ?? 'Regular'); ?></span>
                </div>
                <div class="vehicle-preview-info">
                    <h4><?php echo htmlspecialchars($vehicle['plate_number']); ?></h4>
                    <p><?php 
                        $title = trim(($vehicle['year'] ?? '') . ' ' . ($vehicle['make'] ?? '') . ' ' . ($vehicle['model'] ?? ''));
                        echo htmlspecialchars($title ?: $vehicle['vehicle_type']);
                    ?></p>
                    <span class="view-vehicle-hint">Click to view →</span>
                </div>
            </a>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-car"></i>
            <p>No vehicles registered yet.</p>
            <a href="add_vehicle.php">Add your first vehicle</a>
        </div>
        <?php endif; ?>
    </section>

    <!-- RECENT HISTORY -->
    <section class="recent-history">
        <div class="section-header">
            <h2 class="section-title"><i class="fas fa-history"></i> Recent Parking History</h2>
            <a href="history.php" class="btn-view-all">View All <i class="fas fa-arrow-right"></i></a>
        </div>
        <?php if(mysqli_num_rows($historyQuery) > 0): ?>
        <div class="history-table-wrapper">
            <table class="user-history-table">
                <thead>
                    <tr>
                        <th>Plate</th><th>Slot</th><th>Date</th>
                        <th>Plan</th><th>Amount</th><th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while($h = mysqli_fetch_assoc($historyQuery)): ?>
                    <tr>
                        <td><strong><?php echo htmlspecialchars($h['plate_number']); ?></strong></td>
                        <td><?php echo $h['slot_number']; ?></td>
                        <td><?php echo date('M d, Y', strtotime($h['time_in'])); ?></td>
                        <td><?php echo htmlspecialchars($h['plan_name']); ?></td>
                        <td class="amount">₱<?php echo number_format($h['total_fee'], 2); ?></td>
                        <td><span class="badge-paid">Paid</span></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-parking"></i>
            <p>No parking history yet.</p>
        </div>
        <?php endif; ?>
    </section>

</div>

<!-- MODAL -->
<div class="modal-overlay" id="optionModal">
    <div class="modal-content option-modal">
        <button class="modal-close" onclick="closeOptionModal()"><i class="fas fa-times"></i></button>

        <div class="modal-header">
            <h2 id="modalOptionName">1 Hour Parking</h2>
            <div class="modal-price" id="modalOptionPrice">₱30</div>
        </div>

        <div class="modal-body">
            <p id="modalOptionDesc">Perfect for quick errands</p>

            <div class="modal-choice">
                <h3>When would you like to park?</h3>
                <div class="choice-buttons">
                    <button type="button" class="choice-btn park-now" onclick="chooseParkNow()">
                        <i class="fas fa-clock"></i>
                        <span>Park Now</span>
                        <small>Start immediately</small>
                    </button>
                    <button type="button" class="choice-btn reserve" onclick="chooseReserve()">
                        <i class="fas fa-calendar-alt"></i>
                        <span>Reserve</span>
                        <small>Schedule for later</small>
                    </button>
                </div>
            </div>

            <!-- PARK NOW -->
            <div class="park-now-section" id="parkNowSection" style="display:none;">
                <h3><i class="fas fa-check-circle"></i> Select Your Vehicle</h3>

                <div class="modal-reminder">
                    <i class="fas fa-clock"></i>
                    <span><strong>Reminder:</strong> Overtime after 15-min grace = <strong>₱20/hour</strong></span>
                </div>

                <?php if(count($vehicles) > 0): ?>
                    <form action="process_parking.php" method="POST">
                        <input type="hidden" name="action" value="park_now">
                        <input type="hidden" name="duration" id="parkDuration">
                        <input type="hidden" name="price" id="parkPrice">
                        <input type="hidden" name="plan_name" id="parkPlanName">

                        <div class="vehicle-select-list">
                            <?php foreach($vehicles as $v): 
                                $alreadyParked = false;
                                foreach($activeSessions as $as){
                                    if($as['vehicle_id'] == $v['id']) $alreadyParked = true;
                                }
                            ?>
                            <label class="vehicle-select-item <?php echo $alreadyParked ? 'disabled' : ''; ?>">
                                <input type="radio" name="vehicle_id" value="<?php echo $v['id']; ?>" required <?php echo $alreadyParked ? 'disabled' : ''; ?>>
                                <div class="vehicle-info">
                                    <i class="fas fa-car"></i>
                                    <div>
                                        <strong><?php echo htmlspecialchars($v['plate_number']); ?></strong>
                                        <span><?php echo htmlspecialchars($v['vehicle_type']); ?> - <?php echo htmlspecialchars($v['color']); ?><?php echo $alreadyParked ? ' (Already parked)' : ''; ?></span>
                                    </div>
                                </div>
                            </label>
                            <?php endforeach; ?>
                        </div>

                        <div class="payment-select">
                            <label>Payment Method:</label>
                            <select name="payment_method" required>
                                <option value="Cash">Cash</option>
                                <option value="GCash">GCash</option>
                                <option value="Maya">Maya</option>
                                <option value="Credit Card">Credit Card</option>
                            </select>
                        </div>

                        <button type="submit" class="btn-confirm-park">
                            <i class="fas fa-parking"></i> Confirm Parking — ₱<span id="confirmPrice">30</span>
                        </button>
                    </form>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-car"></i>
                        <p>You need to add a vehicle first.</p>
                        <a href="add_vehicle.php">Add Vehicle</a>
                    </div>
                <?php endif; ?>
            </div>

            <!-- RESERVE -->
            <div class="reserve-section" id="reserveSection" style="display:none;">
                <h3><i class="fas fa-calendar-alt"></i> Reservation Details</h3>

                <div class="modal-reminder">
                    <i class="fas fa-info-circle"></i>
                    <span><strong>Please arrive on time.</strong> Overtime = <strong>₱20/hour</strong></span>
                </div>

                <form action="process_reservation.php" method="POST">
                    <input type="hidden" name="action" value="reserve">
                    <input type="hidden" name="duration" id="reserveDuration">
                    <input type="hidden" name="price" id="reservePrice">
                    <input type="hidden" name="plan_name" id="reservePlanName">

                    <?php if(count($vehicles) > 0): ?>
                    <div class="form-group">
                        <label>Select Vehicle</label>
                        <select name="vehicle_id" required>
                            <option value="">-- Select Vehicle --</option>
                            <?php foreach($vehicles as $v): ?>
                            <option value="<?php echo $v['id']; ?>">
                                <?php echo htmlspecialchars($v['plate_number']); ?> - <?php echo htmlspecialchars($v['vehicle_type']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="form-group">
                        <label>Reservation Date</label>
                        <input type="date" name="reservation_date" min="<?php echo date('Y-m-d'); ?>" required>
                    </div>

                    <div class="form-group">
                        <label>Reservation Time</label>
                        <input type="time" name="reservation_time" required>
                    </div>

                    <div class="form-group">
                        <label>Payment Method</label>
                        <select name="payment_method" required>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                            <option value="Maya">Maya</option>
                            <option value="Credit Card">Credit Card</option>
                        </select>
                    </div>

                    <div class="reservation-note">
                        <i class="fas fa-info-circle"></i>
                        <p>Your reservation will be reviewed by our admin. Please wait for approval.</p>
                    </div>

                    <button type="submit" class="btn-confirm-reserve">
                        <i class="fas fa-calendar-check"></i> Submit Reservation
                    </button>
                    <?php else: ?>
                    <div class="empty-state">
                        <i class="fas fa-car"></i>
                        <p>You need to add a vehicle first.</p>
                        <a href="add_vehicle.php">Add Vehicle</a>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="notification-toast" id="successToast">
    <i class="fas fa-check-circle"></i>
    <span id="toastMessage">Success!</span>
</div>

<script>
let selectedOption = null;

function selectOption(option) {
    selectedOption = option;
    document.getElementById('modalOptionName').textContent = option.name + ' Parking';
    document.getElementById('modalOptionPrice').textContent = '₱' + option.price;
    document.getElementById('modalOptionDesc').textContent = option.description;

    document.getElementById('parkDuration').value = option.duration;
    document.getElementById('parkPrice').value = option.price;
    document.getElementById('parkPlanName').value = option.name;
    document.getElementById('reserveDuration').value = option.duration;
    document.getElementById('reservePrice').value = option.price;
    document.getElementById('reservePlanName').value = option.name;
    document.getElementById('confirmPrice').textContent = option.price;

    document.getElementById('parkNowSection').style.display = 'none';
    document.getElementById('reserveSection').style.display = 'none';

    document.getElementById('optionModal').classList.add('active');
    document.body.style.overflow = 'hidden';
}

function closeOptionModal() {
    document.getElementById('optionModal').classList.remove('active');
    document.body.style.overflow = '';
}

function chooseParkNow() {
    document.getElementById('parkNowSection').style.display = 'block';
    document.getElementById('reserveSection').style.display = 'none';
}

function chooseReserve() {
    document.getElementById('parkNowSection').style.display = 'none';
    document.getElementById('reserveSection').style.display = 'block';
}

document.getElementById('optionModal').addEventListener('click', function(e){
    if(e.target === this) closeOptionModal();
});

document.addEventListener('keydown', function(e){
    if(e.key === 'Escape') closeOptionModal();
});

// Live timers
function updateAllTimers(){
    document.querySelectorAll('.active-timer').forEach(el => {
        const start = new Date(el.dataset.time.replace(" ", "T")).getTime();
        const diff = Math.floor((Date.now() - start) / 1000);
        const h = Math.floor(diff / 3600);
        const m = Math.floor((diff % 3600) / 60);
        const s = diff % 60;
        el.textContent = `${h}h ${m}m ${s}s`;
    });
}
setInterval(updateAllTimers, 1000);
updateAllTimers();
</script>

<script src="script.js"></script>
</body>
</html>