<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "../db.php";

$message = '';
$error = '';
$found = null;

// Handle manual code entry
if (isset($_POST['manual_code'])) {
    $code = strtoupper(mysqli_real_escape_string($conn, trim($_POST['manual_code'])));

    $q = mysqli_query($conn, "
        SELECT r.*, u.fullname, u.phone 
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.checkin_code = '$code' 
          AND r.status = 'approved' 
        LIMIT 1
    ");

    if (mysqli_num_rows($q) == 0) {
        $error = "Code not found or reservation is not approved.";
    } else {
        $found = mysqli_fetch_assoc($q);
    }
}

// Handle QR token entry
if (isset($_POST['qr_token'])) {
    $token = mysqli_real_escape_string($conn, trim($_POST['qr_token']));

    $q = mysqli_query($conn, "
        SELECT r.*, u.fullname, u.phone 
        FROM reservations r
        LEFT JOIN users u ON r.user_id = u.id
        WHERE r.qr_token = '$token' 
          AND r.status = 'approved' 
        LIMIT 1
    ");

    if (mysqli_num_rows($q) == 0) {
        $error = "QR code not recognized or reservation is not approved.";
    } else {
        $found = mysqli_fetch_assoc($q);
    }
}

// Handle check-in confirmation
if (isset($_POST['confirm_checkin'])) {
    $res_id = intval($_POST['res_id']);
    $r = mysqli_fetch_assoc(mysqli_query($conn, "
        SELECT r.*, u.fullname 
        FROM reservations r 
        LEFT JOIN users u ON r.user_id = u.id 
        WHERE r.id = '$res_id'
    "));

    if ($r && $r['status'] == 'approved') {
        $slot = $r['slot_number'];

        // Check if slot is taken
        $slotCheck = mysqli_query($conn, "SELECT id FROM vehicles WHERE slot_number='$slot'");
        if (mysqli_num_rows($slotCheck) > 0) {
            // Find a new slot
            $newSlotQ = mysqli_query($conn, "
                SELECT n FROM (
                    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                    UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
                    UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
                    UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
                    UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
                ) nums
                WHERE n NOT IN (SELECT slot_number FROM vehicles)
                ORDER BY n LIMIT 1
            ");
            $slot = mysqli_fetch_assoc($newSlotQ)['n'] ?? 1;
        }

        // Add to vehicles
        mysqli_query($conn, "
            INSERT INTO vehicles (plate_number, owner_name, slot_number, time_in, user_id, source)
            VALUES ('{$r['plate_number']}', '{$r['fullname']}', '$slot', NOW(), '{$r['user_id']}', 'reservation')
        ");

        // Create parking session
        mysqli_query($conn, "
            INSERT INTO parking_sessions
            (user_id, vehicle_id, plate_number, slot_number, plan_name, duration_hours, total_fee, payment_method, status)
            VALUES ('{$r['user_id']}', '{$r['vehicle_id']}', '{$r['plate_number']}', '$slot',
                    '{$r['plan_name']}', '{$r['duration_hours']}', '{$r['price']}', '{$r['payment_method']}', 'active')
        ");

        // Mark reservation completed
        mysqli_query($conn, "
            UPDATE reservations 
            SET status='completed', checked_in_at=NOW() 
            WHERE id = '$res_id'
        ");

        $message = "✅ Checked in! Plate {$r['plate_number']} → Slot #$slot";
        $found = null;
    } else {
        $error = "Cannot check in — reservation is not approved.";
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>QR Check-In - Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
</head>
<body>

<div class="scan-page">

    <div class="scan-header">
        <h1><i class="fas fa-qrcode"></i> QR CHECK-IN</h1>
        <div class="scan-nav">
            <a href="index.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="manage_reservations.php"><i class="fas fa-calendar-check"></i> Reservations</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <?php if ($message): ?>
        <div class="scan-success">
            <i class="fas fa-check-circle"></i>
            <?php echo $message; ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="scan-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <div class="scan-grid">

        <!-- Camera Scanner -->
        <div class="scan-card">
            <h2><i class="fas fa-camera"></i> Scan QR Code</h2>
            <p class="scan-hint">Point the customer's QR code at your camera</p>

            <div id="qr-reader" class="qr-reader-box"></div>

            <div class="scan-note">
                <i class="fas fa-info-circle"></i>
                If the camera doesn't work, use manual entry on the right.
            </div>
        </div>

        <!-- Manual Entry -->
        <div class="scan-card">
            <h2><i class="fas fa-keyboard"></i> Manual Code Entry</h2>
            <p class="scan-hint">Enter the 6-character code shown below the QR</p>

            <form method="POST" class="manual-form">
                <input 
                    type="text" 
                    name="manual_code" 
                    placeholder="A7K9X2" 
                    maxlength="6" 
                    required 
                    autocomplete="off"
                    oninput="this.value = this.value.toUpperCase()">
                <button type="submit">
                    <i class="fas fa-search"></i> Look Up
                </button>
            </form>

            <div class="scan-note">
                <i class="fas fa-lightbulb"></i>
                Ask the customer to read the code from their reservation page.
            </div>
        </div>

    </div>

    <!-- Result Card -->
    <?php if ($found): ?>
    <div class="result-card">
        <div class="result-header">
            <i class="fas fa-check-circle"></i>
            <h3>Reservation Found</h3>
        </div>

        <div class="result-body">
            <div class="result-row">
                <span><i class="fas fa-car"></i> Plate</span>
                <strong><?php echo htmlspecialchars($found['plate_number']); ?></strong>
            </div>
            <div class="result-row">
                <span><i class="fas fa-user"></i> Customer</span>
                <strong><?php echo htmlspecialchars($found['fullname'] ?? 'Unknown'); ?></strong>
            </div>
            <div class="result-row">
                <span><i class="fas fa-phone"></i> Phone</span>
                <strong><?php echo htmlspecialchars($found['phone'] ?? '—'); ?></strong>
            </div>
            <div class="result-row">
                <span><i class="fas fa-parking"></i> Assigned Slot</span>
                <strong>#<?php echo $found['slot_number']; ?></strong>
            </div>
            <div class="result-row">
                <span><i class="fas fa-list"></i> Plan</span>
                <strong><?php echo htmlspecialchars($found['plan_name']); ?></strong>
            </div>
            <div class="result-row">
                <span><i class="fas fa-calendar"></i> Scheduled</span>
                <strong><?php echo date('M d, Y h:i A', strtotime($found['reservation_date'] . ' ' . $found['reservation_time'])); ?></strong>
            </div>
            <div class="result-row">
                <span><i class="fas fa-money-bill"></i> Amount</span>
                <strong class="result-amount">₱<?php echo number_format($found['price'], 2); ?></strong>
            </div>
        </div>

        <form method="POST" class="result-actions">
            <input type="hidden" name="res_id" value="<?php echo $found['id']; ?>">
            <button type="submit" name="confirm_checkin" class="btn-confirm-checkin">
                <i class="fas fa-check"></i> Confirm Check-In
            </button>
        </form>
    </div>
    <?php endif; ?>

    <div class="scan-footer">
        <a href="index.php" class="btn-back">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>
    </div>

</div>

<script>
// HTML5 QR Scanner
function onScanSuccess(decodedText) {
    // Auto-submit as QR token
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = 'scan.php';
    const input = document.createElement('input');
    input.type = 'hidden';
    input.name = 'qr_token';
    input.value = decodedText;
    form.appendChild(input);
    document.body.appendChild(form);
    form.submit();
}

let html5QrCode;
if (document.getElementById('qr-reader')) {
    try {
        html5QrCode = new Html5QrcodeScanner(
            "qr-reader",
            { 
                fps: 10, 
                qrbox: { width: 250, height: 250 },
                aspectRatio: 1.0
            },
            false
        );
        html5QrCode.render(onScanSuccess);
    } catch (e) {
        document.getElementById('qr-reader').innerHTML = 
            '<div style="color:#93a7c4;text-align:center;padding:40px 20px;">' +
            '<i class="fas fa-video-slash" style="font-size:32px;margin-bottom:12px;display:block;opacity:0.5;"></i>' +
            'Camera not available.<br>Please use manual code entry.' +
            '</div>';
    }
}
</script>

</body>
</html>