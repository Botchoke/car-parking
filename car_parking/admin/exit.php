<?php
session_start();

if (!isset($_SESSION['admin'])) {
    header("Location: login.php");
    exit();
}

include "../db.php";

// ============================================
// STEP 1: Show confirmation form (GET request)
// ============================================
if (isset($_GET['exit_id']) && !isset($_POST['confirm_exit'])) {

    $id = intval($_GET['exit_id']);

    $query = mysqli_query($conn, "SELECT * FROM vehicles WHERE id='$id'");

    if (!$query || mysqli_num_rows($query) == 0) {
        die("Vehicle not found.");
    }

    $row = mysqli_fetch_assoc($query);

    $plate = $row['plate_number'];
    $owner = $row['owner_name'];
    $slot = $row['slot_number'];
    $time_in = $row['time_in'];
    $user_id = !empty($row['user_id']) ? intval($row['user_id']) : 0;
    $source = !empty($row['source']) ? $row['source'] : 'walk-in';

    $time_out = date("Y-m-d H:i:s");
    $seconds = strtotime($time_out) - strtotime($time_in);

    // Find parking session
    $sess = null;
    if($user_id > 0){
        $sessQ = mysqli_query($conn, "SELECT * FROM parking_sessions WHERE user_id='$user_id' AND status='active' ORDER BY id DESC LIMIT 1");
        $sess = mysqli_fetch_assoc($sessQ);
    }
    if(!$sess){
        $plateEsc = mysqli_real_escape_string($conn, $plate);
        $sessQ = mysqli_query($conn, "SELECT * FROM parking_sessions WHERE LOWER(plate_number)=LOWER('$plateEsc') AND status='active' ORDER BY id DESC LIMIT 1");
        $sess = mysqli_fetch_assoc($sessQ);
    }

    // Compute fees
    $overtimeHours = 0;
    $overtimeFee = 0;

    if($sess){
        $plannedSeconds = $sess['duration_hours'] * 3600;
        $grace = 15 * 60;
        $overtimeSecs = max(0, $seconds - $plannedSeconds - $grace);
        $overtimeHours = ceil($overtimeSecs / 3600);
        $overtimeFee = $overtimeHours * 20;
        $total_fee = $sess['total_fee'] + $overtimeFee;
        $payment_method = $sess['payment_method'];
    } else {
        $minutes = max(1, ceil($seconds / 120));
        $total_fee = $minutes * 50;
        $payment_method = 'Cash';
    }

    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    $duration_text = $hours > 0 ? "{$hours}h {$minutes}m" : "{$minutes}m";
?>
<!DOCTYPE html>
<html>
<head>
    <title>Confirm Payment — Hypercar Parking</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>

<div class="confirm-wrapper">

    <div class="confirm-card">

        <div class="confirm-header">
            <i class="fas fa-cash-register"></i>
            <h1>Payment Verification</h1>
            <p>Confirm payment before completing this session</p>
        </div>

        <!-- Session Details -->
        <div class="confirm-section">
            <h2><i class="fas fa-info-circle"></i> Session Details</h2>

            <div class="detail-row">
                <span>Plate Number</span>
                <strong><?php echo htmlspecialchars($plate); ?></strong>
            </div>

            <div class="detail-row">
                <span>Owner</span>
                <strong><?php echo htmlspecialchars($owner); ?></strong>
            </div>

            <div class="detail-row">
                <span>Slot</span>
                <strong>#<?php echo $slot; ?></strong>
            </div>

            <div class="detail-row">
                <span>Time In</span>
                <strong><?php echo date('h:i A', strtotime($time_in)); ?></strong>
            </div>

            <div class="detail-row">
                <span>Time Out</span>
                <strong><?php echo date('h:i A', strtotime($time_out)); ?></strong>
            </div>

            <div class="detail-row">
                <span>Duration</span>
                <strong><?php echo $duration_text; ?></strong>
            </div>
        </div>

        <!-- Fee Breakdown -->
        <div class="confirm-section confirm-fee-section">
            <h2><i class="fas fa-receipt"></i> Fee Breakdown</h2>

            <?php if($sess): ?>
            <div class="detail-row">
                <span>Base Fee (<?php echo htmlspecialchars($sess['plan_name']); ?>)</span>
                <strong>₱<?php echo number_format($sess['total_fee'], 2); ?></strong>
            </div>

            <?php if($overtimeHours > 0): ?>
            <div class="detail-row overtime">
                <span>Overtime (<?php echo $overtimeHours; ?>h × ₱20)</span>
                <strong>+₱<?php echo number_format($overtimeFee, 2); ?></strong>
            </div>
            <?php endif; ?>
            <?php endif; ?>

            <div class="detail-row total-row">
                <span>TOTAL DUE</span>
                <strong>₱<?php echo number_format($total_fee, 2); ?></strong>
            </div>
        </div>

        <!-- Payment Confirmation Form -->
        <form method="POST" action="exit.php" class="confirm-form">

            <input type="hidden" name="exit_id" value="<?php echo $id; ?>">
            <input type="hidden" name="time_out" value="<?php echo $time_out; ?>">
            <input type="hidden" name="total_fee" value="<?php echo $total_fee; ?>">
            <input type="hidden" name="overtime_hours" value="<?php echo $overtimeHours; ?>">
            <input type="hidden" name="overtime_fee" value="<?php echo $overtimeFee; ?>">
            <input type="hidden" name="sess_id" value="<?php echo $sess ? $sess['id'] : ''; ?>">

            <div class="confirm-section">
                <h2><i class="fas fa-wallet"></i> Payment Method</h2>

                <div class="method-display">
                    <i class="fas fa-check-circle"></i>
                    Customer chose: <strong><?php echo htmlspecialchars($payment_method); ?></strong>
                </div>

                <div class="form-group">
                    <label>Confirm Actual Payment Method *</label>
                    <select name="payment_method" required>
                        <option value="Cash" <?php echo $payment_method == 'Cash' ? 'selected' : ''; ?>>Cash</option>
                        <option value="GCash" <?php echo $payment_method == 'GCash' ? 'selected' : ''; ?>>GCash</option>
                        <option value="Maya" <?php echo $payment_method == 'Maya' ? 'selected' : ''; ?>>Maya</option>
                        <option value="Credit Card" <?php echo $payment_method == 'Credit Card' ? 'selected' : ''; ?>>Credit Card</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Payment Reference (Optional)</label>
                    <input type="text" name="payment_reference" placeholder="e.g., GCash reference no. / card last 4 digits">
                </div>
            </div>

            <!-- Verification Checkbox -->
            <div class="verify-box">
                <label class="verify-label">
                    <input type="checkbox" name="payment_confirmed" value="1" required>
                    <span class="verify-text">
                        I confirm I have <strong>received ₱<?php echo number_format($total_fee, 2); ?></strong> from this customer
                    </span>
                </label>
            </div>

            <div class="confirm-actions">
                <a href="index.php" class="cancel-btn">
                    <i class="fas fa-times"></i> Cancel
                </a>
                <button type="submit" name="confirm_exit" class="confirm-btn">
                    <i class="fas fa-check"></i> Confirm Payment & Exit
                </button>
            </div>

        </form>

    </div>

</div>

</body>
</html>
<?php
exit();
}

// ============================================
// STEP 2: Handle confirmation (POST request)
// ============================================
if (isset($_POST['confirm_exit'])) {

    $id = intval($_POST['exit_id']);
    $time_out = mysqli_real_escape_string($conn, $_POST['time_out']);
    $total_fee = floatval($_POST['total_fee']);
    $overtimeHours = intval($_POST['overtime_hours']);
    $overtimeFee = floatval($_POST['overtime_fee']);
    $sess_id = !empty($_POST['sess_id']) ? intval($_POST['sess_id']) : 0;
    $payment_method = mysqli_real_escape_string($conn, $_POST['payment_method']);
    $payment_reference = !empty($_POST['payment_reference']) ? mysqli_real_escape_string($conn, $_POST['payment_reference']) : null;
    $payment_confirmed = isset($_POST['payment_confirmed']) ? 1 : 0;
    $verified_by = $_SESSION['admin'];
    $verified_at = date('Y-m-d H:i:s');

    if (!$payment_confirmed) {
        die("Payment confirmation required.");
    }

    $query = mysqli_query($conn, "SELECT * FROM vehicles WHERE id='$id'");
    if (!$query || mysqli_num_rows($query) == 0) {
        die("Vehicle not found.");
    }

    $row = mysqli_fetch_assoc($query);
    $plate = $row['plate_number'];
    $owner = $row['owner_name'];
    $slot = $row['slot_number'];
    $time_in = $row['time_in'];
    $user_id = !empty($row['user_id']) ? intval($row['user_id']) : 0;
    $source = !empty($row['source']) ? $row['source'] : 'walk-in';

    // Mark parking session completed
    if ($sess_id > 0) {
        mysqli_query($conn, "
            UPDATE parking_sessions 
            SET time_out = '$time_out', 
                overtime_hours = '$overtimeHours',
                overtime_fee = '$overtimeFee',
                total_fee = '$total_fee',
                status = 'completed',
                exit_requested = 0
            WHERE id = '$sess_id'
        ");
    }

    // Write to history
    $user_id_sql = $user_id > 0 ? "'$user_id'" : "NULL";
    $ref_sql = $payment_reference ? "'$payment_reference'" : "NULL";

    $insert = mysqli_query($conn, "
        INSERT INTO history
        (user_id, plate_number, owner_name, slot_number, time_in, time_out, 
         total_fee, overtime_hours, overtime_fee, source, 
         payment_method, payment_verified, verified_by, verified_at, payment_reference)
        VALUES
        ($user_id_sql, '$plate', '$owner', '$slot', '$time_in', '$time_out', 
         '$total_fee', '$overtimeHours', '$overtimeFee', '$source',
         '$payment_method', '$payment_confirmed', '$verified_by', '$verified_at', $ref_sql)
    ");

    if (!$insert) {
        die("History Insert Error: " . mysqli_error($conn));
    }

    mysqli_query($conn, "DELETE FROM vehicles WHERE id='$id'");

    header("Location: index.php?exited=success");
    exit();
}

// No valid request
header("Location: index.php");
exit();
?>