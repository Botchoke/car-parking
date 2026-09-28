<?php
session_start();
if(!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'user'){
    header("Location: login.php");
    exit();
}
include "../db.php";

$user_id = $_SESSION['user_id'];

// Cancel reservation
if(isset($_GET['cancel'])){
    $id = intval($_GET['cancel']);
    mysqli_query($conn, "
        UPDATE reservations 
        SET status='cancelled', 
            rejected_at=NOW(), 
            rejected_reason='Cancelled by customer',
            slot_number=NULL
        WHERE id='$id' AND user_id='$user_id' AND status IN ('pending','approved')
    ");
    header("Location: my_reservations.php?cancelled=1");
    exit();
}

$reservations = mysqli_query($conn, "
    SELECT * FROM reservations 
    WHERE user_id='$user_id' 
    ORDER BY 
        CASE status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'completed' THEN 3 
            ELSE 4 
        END,
        created_at DESC
");
?>
<!DOCTYPE html>
<html>
<head>
    <title>My Reservations - Hypercar Parking</title>
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
            <a href="my_reservations.php" class="active"><i class="fas fa-calendar-check"></i> Reservations</a>
            <a href="history.php"><i class="fas fa-history"></i> History</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </nav>
    </header>

    <div class="page-content">
        <h1 class="page-title"><i class="fas fa-calendar-check"></i> My Reservations</h1>

        <?php if(isset($_GET['cancelled'])): ?>
            <div class="success-message"><i class="fas fa-check-circle"></i> Reservation cancelled.</div>
        <?php endif; ?>

        <?php if(mysqli_num_rows($reservations) > 0): ?>
        <div class="reservations-grid">
            <?php while($r = mysqli_fetch_assoc($reservations)): 
                $resDateTime = $r['reservation_date'] . ' ' . $r['reservation_time'];
                $isPast = strtotime($resDateTime) < time();
            ?>
            <div class="reservation-card status-<?php echo $r['status']; ?>">
                <div class="res-header">
                    <div class="res-status">
                        <?php
                        $icons = [
                            'pending' => ['fa-hourglass-half', 'Pending Approval', '#f59e0b'],
                            'approved' => ['fa-check-circle', 'Approved', '#2ed573'],
                            'rejected' => ['fa-times-circle', 'Rejected', '#ff4757'],
                            'completed' => ['fa-check-double', 'Completed', '#4F7CAC'],
                            'cancelled' => ['fa-ban', 'Cancelled', '#7a8fa6']
                        ];
                        $ic = $icons[$r['status']] ?? ['fa-question', 'Unknown', '#7a8fa6'];
                        ?>
                        <i class="fas <?php echo $ic[0]; ?>" style="color:<?php echo $ic[2]; ?>;"></i>
                        <span style="color:<?php echo $ic[2]; ?>;"><?php echo $ic[1]; ?></span>
                    </div>
                    <div class="res-id">#<?php echo str_pad($r['id'], 4, '0', STR_PAD_LEFT); ?></div>
                </div>

                <?php if($r['status'] === 'approved' && $r['slot_number']): ?>
                <div class="slot-highlight">
                    <i class="fas fa-parking"></i>
                    <div>
                        <div class="slot-label">Your Assigned Slot</div>
                        <div class="slot-number">#<?php echo $r['slot_number']; ?></div>
                    </div>
                </div>

                <!-- QR CODE FOR CHECK-IN -->
                <div class="qr-checkin-box">
                    <div class="qr-header">
                        <i class="fas fa-qrcode"></i>
                        <span>Show this at the gate</span>
                    </div>

                    <div class="qr-code" id="qr-<?php echo $r['id']; ?>">
                        <!-- QR will be drawn here by JavaScript -->
                    </div>

                    <div class="qr-code-manual">
                        <div class="qr-label">Or use this code:</div>
                        <div class="qr-code-text"><?php echo htmlspecialchars($r['checkin_code'] ?? '------'); ?></div>
                    </div>
                </div>

                <!-- Load QR library and generate QR -->
                <script src="https://cdn.jsdelivr.net/npm/qrcodejs@1.0.0/qrcode.min.js"></script>
                <script>
                (function(){
                    var el = document.getElementById('qr-<?php echo $r['id']; ?>');
                    if(el && typeof QRCode !== 'undefined'){
                        new QRCode(el, {
                            text: "<?php echo htmlspecialchars($r['qr_token'] ?? ''); ?>",
                            width: 180,
                            height: 180,
                            colorDark: "#001018",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.H
                        });
                    }
                })();
                </script>
                <?php endif; ?>

                <div class="res-body">
                    <div class="res-row">
                        <span><i class="fas fa-car"></i> Plate</span>
                        <strong><?php echo htmlspecialchars($r['plate_number']); ?></strong>
                    </div>
                    <div class="res-row">
                        <span><i class="fas fa-list"></i> Plan</span>
                        <strong><?php echo htmlspecialchars($r['plan_name']); ?></strong>
                    </div>
                    <div class="res-row">
                        <span><i class="fas fa-calendar"></i> Date</span>
                        <strong><?php echo date('M d, Y', strtotime($r['reservation_date'])); ?></strong>
                    </div>
                    <div class="res-row">
                        <span><i class="fas fa-clock"></i> Time</span>
                        <strong><?php echo date('h:i A', strtotime($r['reservation_time'])); ?></strong>
                    </div>
                    <div class="res-row">
                        <span><i class="fas fa-money-bill"></i> Price</span>
                        <strong style="color:#00f7ff;">₱<?php echo number_format($r['price'], 2); ?></strong>
                    </div>
                    <div class="res-row">
                        <span><i class="fas fa-credit-card"></i> Payment</span>
                        <strong><?php echo htmlspecialchars($r['payment_method']); ?></strong>
                    </div>
                </div>

                <?php if($r['status'] === 'pending'): ?>
                <div class="res-info info-pending">
                    <i class="fas fa-info-circle"></i>
                    <span>Waiting for admin approval. Please check back soon.</span>
                </div>
                <?php elseif($r['status'] === 'approved'): ?>
                <div class="res-info info-approved">
                    <i class="fas fa-check-circle"></i>
                    <span>
                        <strong>Approved!</strong> Please arrive on time.
                        <?php if(!$isPast): ?>
                            Your slot is reserved.
                        <?php else: ?>
                            <span style="color:#ff6b7a;">You're running late! Please go to the lot now.</span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php elseif($r['status'] === 'rejected'): ?>
                <div class="res-info info-rejected">
                    <i class="fas fa-times-circle"></i>
                    <span>Rejected: <?php echo htmlspecialchars($r['rejected_reason'] ?? 'No reason provided'); ?></span>
                </div>
                <?php elseif($r['status'] === 'completed'): ?>
                <div class="res-info info-completed">
                    <i class="fas fa-check-double"></i>
                    <span>Parking session completed. Thank you!</span>
                </div>
                <?php endif; ?>

                <?php if($r['status'] === 'pending' || $r['status'] === 'approved'): ?>
                <a href="?cancel=<?php echo $r['id']; ?>" 
                   class="btn-cancel-res" 
                   onclick="return confirm('Cancel this reservation?');">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <?php endif; ?>
            </div>
            <?php endwhile; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <p>You haven't made any reservations yet.</p>
            <a href="index.php">Browse Parking Plans</a>
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