<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include "../db.php";

$admin_user = $_SESSION['admin'];

// ============================================
// AUTO-REJECT NO-SHOWS (30 min after scheduled time)
// ============================================
mysqli_query($conn, "
    UPDATE reservations 
    SET status='rejected', 
        rejected_at=NOW(), 
        rejected_reason='Auto-rejected: No-show (30 min late)',
        slot_number=NULL
    WHERE status='approved' 
      AND CONCAT(reservation_date, ' ', reservation_time) < DATE_SUB(NOW(), INTERVAL 30 MINUTE)
");

// ============================================
// APPROVE — auto-assign slot
// ============================================
if(isset($_GET['approve'])){
    $id = intval($_GET['approve']);

    $slotQuery = mysqli_query($conn, "
        SELECT n AS slot_number FROM (
            SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
            UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
            UNION SELECT 11 UNION SELECT 12 UNION SELECT 13 UNION SELECT 14 UNION SELECT 15
            UNION SELECT 16 UNION SELECT 17 UNION SELECT 18 UNION SELECT 19 UNION SELECT 20
            UNION SELECT 21 UNION SELECT 22 UNION SELECT 23 UNION SELECT 24 UNION SELECT 25
            UNION SELECT 26 UNION SELECT 27 UNION SELECT 28 UNION SELECT 29 UNION SELECT 30
            UNION SELECT 31 UNION SELECT 32 UNION SELECT 33 UNION SELECT 34 UNION SELECT 35
            UNION SELECT 36 UNION SELECT 37 UNION SELECT 38 UNION SELECT 39 UNION SELECT 40
            UNION SELECT 41 UNION SELECT 42 UNION SELECT 43 UNION SELECT 44 UNION SELECT 45
            UNION SELECT 46 UNION SELECT 47 UNION SELECT 48 UNION SELECT 49 UNION SELECT 50
        ) nums
        WHERE n NOT IN (SELECT slot_number FROM vehicles)
          AND n NOT IN (SELECT slot_number FROM parking_sessions WHERE status='active')
          AND n NOT IN (SELECT slot_number FROM reservations WHERE status='approved' AND slot_number IS NOT NULL)
        ORDER BY n LIMIT 1
    ");
    $slotRow = mysqli_fetch_assoc($slotQuery);

    if($slotRow){
        $slot = intval($slotRow['slot_number']);

        $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id='$id'"));
        $resDateTime = $r['reservation_date'] . ' ' . $r['reservation_time'];
        $expiryAt = date('Y-m-d H:i:s', strtotime($resDateTime . ' +30 minutes'));

        mysqli_query($conn, "
            UPDATE reservations 
            SET status='approved', 
                slot_number='$slot',
                approved_at=NOW(),
                approved_by='$admin_user',
                expiry_at='$expiryAt'
            WHERE id='$id'
        ");
        header("Location: manage_reservations.php?msg=approved&slot=$slot");
    } else {
        header("Location: manage_reservations.php?error=no_slot");
    }
    exit();
}

// ============================================
// REJECT
// ============================================
if(isset($_POST['reject'])){
    $id = intval($_POST['id']);
    $note = mysqli_real_escape_string($conn, $_POST['note']);
    mysqli_query($conn, "
        UPDATE reservations 
        SET status='rejected', 
            admin_note='$note',
            rejected_at=NOW(),
            rejected_reason='$note',
            slot_number=NULL
        WHERE id='$id'
    ");
    header("Location: manage_reservations.php?msg=rejected");
    exit();
}

// ============================================
// CHECK-IN
// ============================================
if(isset($_GET['complete'])){
    $id = intval($_GET['complete']);
    $r = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM reservations WHERE id='$id'"));
    
    if($r && $r['status'] == 'approved'){
        $slot = $r['slot_number'];

        // Check if slot is now taken
        $slotCheck = mysqli_query($conn, "SELECT id FROM vehicles WHERE slot_number='$slot'");
        if(mysqli_num_rows($slotCheck) > 0){
            $newSlotQ = mysqli_query($conn, "
                SELECT n AS slot_number FROM (
                    SELECT 1 AS n UNION SELECT 2 UNION SELECT 3 UNION SELECT 4 UNION SELECT 5
                    UNION SELECT 6 UNION SELECT 7 UNION SELECT 8 UNION SELECT 9 UNION SELECT 10
                ) nums
                WHERE n NOT IN (SELECT slot_number FROM vehicles)
                ORDER BY n LIMIT 1
            ");
            $slot = mysqli_fetch_assoc($newSlotQ)['slot_number'] ?? 1;
        }

        mysqli_query($conn, "
            INSERT INTO vehicles (plate_number, owner_name, slot_number, time_in, user_id, source)
            VALUES ('{$r['plate_number']}', '{$r['fullname']}', '$slot', NOW(), '{$r['user_id']}', 'reservation')
        ");

        mysqli_query($conn, "
            INSERT INTO parking_sessions
            (user_id, vehicle_id, plate_number, slot_number, plan_name, duration_hours, total_fee, payment_method, status)
            VALUES ('{$r['user_id']}', '{$r['vehicle_id']}', '{$r['plate_number']}', '$slot',
                    '{$r['plan_name']}', '{$r['duration_hours']}', '{$r['price']}', '{$r['payment_method']}', 'active')
        ");

        mysqli_query($conn, "
            UPDATE reservations 
            SET status='completed', checked_in_at=NOW() 
            WHERE id='$id'
        ");

        header("Location: manage_reservations.php?msg=checked_in");
    } else {
        header("Location: manage_reservations.php?error=not_approved");
    }
    exit();
}

// ============================================
// FILTERS + FETCH
// ============================================
$filter = isset($_GET['filter']) ? mysqli_real_escape_string($conn, $_GET['filter']) : 'pending';
$allowedFilters = ['pending', 'approved', 'rejected', 'completed', 'cancelled', 'all'];
if(!in_array($filter, $allowedFilters)) $filter = 'pending';

$whereClause = $filter === 'all' ? "" : "WHERE r.status='$filter'";

$reservations = mysqli_query($conn, "
    SELECT r.*, u.fullname, u.email, u.phone 
    FROM reservations r
    LEFT JOIN users u ON r.user_id = u.id
    $whereClause
    ORDER BY 
        CASE r.status 
            WHEN 'pending' THEN 1 
            WHEN 'approved' THEN 2 
            WHEN 'completed' THEN 3 
            ELSE 4 
        END,
        r.created_at DESC
");

$counts = [];
foreach(['pending', 'approved', 'rejected', 'completed'] as $st){
    $cq = mysqli_query($conn, "SELECT COUNT(*) AS cnt FROM reservations WHERE status='$st'");
    $counts[$st] = mysqli_fetch_assoc($cq)['cnt'];
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Reservations</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <h1 class="title">📅 MANAGE RESERVATIONS</h1>
    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="manage_users.php">Users</a>
        <a href="logout.php">Logout</a>
    </div>

    <?php if(isset($_GET['msg'])): ?>
        <div class="success-message">
            <?php
            $msgs = [
                'approved' => '✓ Reservation approved! Slot #' . ($_GET['slot'] ?? '') . ' assigned',
                'rejected' => '✓ Reservation rejected',
                'checked_in' => '✓ Customer checked in — parking session started'
            ];
            echo $msgs[$_GET['msg']] ?? '✓ Action completed';
            ?>
        </div>
    <?php endif; ?>

    <?php if(isset($_GET['error'])): ?>
        <div class="error-message">
            <?php
            $errs = [
                'no_slot' => '✗ No slots available for this reservation',
                'not_approved' => '✗ Can only check-in approved reservations'
            ];
            echo $errs[$_GET['error']] ?? '✗ Something went wrong';
            ?>
        </div>
    <?php endif; ?>

    <div class="filter-buttons">
        <a href="?filter=pending" class="addbtn <?php echo $filter==='pending'?'active':''; ?>">
            Pending <?php if($counts['pending'] > 0): ?><span class="badge-count"><?php echo $counts['pending']; ?></span><?php endif; ?>
        </a>
        <a href="?filter=approved" class="addbtn <?php echo $filter==='approved'?'active':''; ?>">
            Approved <?php if($counts['approved'] > 0): ?><span class="badge-count"><?php echo $counts['approved']; ?></span><?php endif; ?>
        </a>
        <a href="?filter=completed" class="addbtn <?php echo $filter==='completed'?'active':''; ?>">
            Completed
        </a>
        <a href="?filter=rejected" class="addbtn <?php echo $filter==='rejected'?'active':''; ?>">
            Rejected
        </a>
        <a href="?filter=all" class="addbtn <?php echo $filter==='all'?'active':''; ?>">
            All
        </a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Plate</th>
                    <th>Slot</th>
                    <th>Plan</th>
                    <th>Scheduled</th>
                    <th>Price</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($reservations) > 0): ?>
                    <?php while($r = mysqli_fetch_assoc($reservations)): 
                        $resDateTime = $r['reservation_date'] . ' ' . $r['reservation_time'];
                        $isPast = strtotime($resDateTime) < time();
                    ?>
                    <tr>
                        <td><?php echo $r['id']; ?></td>
                        <td>
                            <strong><?php echo htmlspecialchars($r['fullname'] ?? 'Unknown'); ?></strong><br>
                            <small style="color:#7a8fa6;"><?php echo htmlspecialchars($r['phone'] ?? ''); ?></small>
                        </td>
                        <td><strong style="color:#00f7ff;"><?php echo htmlspecialchars($r['plate_number']); ?></strong></td>
                        <td>
                            <?php if($r['slot_number']): ?>
                                <span class="slot-badge">#<?php echo $r['slot_number']; ?></span>
                            <?php else: ?>
                                <span style="color:#7a8fa6;">—</span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($r['plan_name']); ?></td>
                        <td>
                            <?php echo date('M d, Y', strtotime($r['reservation_date'])); ?><br>
                            <small><?php echo date('h:i A', strtotime($r['reservation_time'])); ?></small>
                            <?php if($r['status'] === 'approved'): ?>
                                <br><small style="color:<?php echo $isPast?'#ff4757':'#2ed573'; ?>;">
                                    <?php echo $isPast ? '⚠ Overdue' : '✓ Upcoming'; ?>
                                </small>
                            <?php endif; ?>
                        </td>
                        <td class="amount">₱<?php echo number_format($r['price'], 2); ?></td>
                        <td>
                            <?php
                            $badges = [
                                'pending' => ['badge-pending', 'Pending'],
                                'approved' => ['badge-approved', 'Approved'],
                                'rejected' => ['badge-rejected', 'Rejected'],
                                'completed' => ['badge-completed', 'Completed'],
                                'cancelled' => ['badge-rejected', 'Cancelled']
                            ];
                            $badge = $badges[$r['status']] ?? ['badge-pending', 'Unknown'];
                            ?>
                            <span class="<?php echo $badge[0]; ?>"><?php echo $badge[1]; ?></span>
                            <?php if(!empty($r['rejected_reason']) && $r['status']=='rejected'): ?>
                                <br><small style="color:#ff6b7a;font-size:11px;"><?php echo htmlspecialchars($r['rejected_reason']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if($r['status'] == 'pending'): ?>
                                <a class="editbtn" href="?approve=<?php echo $r['id']; ?>" onclick="return confirm('Approve this reservation? A slot will be auto-assigned.');">Approve</a>
                                <button class="exitbtn" onclick="rejectRes(<?php echo $r['id']; ?>)">Reject</button>
                            <?php elseif($r['status'] == 'approved'): ?>
                                <a class="editbtn" href="?complete=<?php echo $r['id']; ?>" onclick="return confirm('Check in this customer?');">Check-in</a>
                            <?php elseif($r['status'] == 'completed'): ?>
                                <span style="color:#2ed573;font-size:12px;"><i class="fas fa-check"></i> Done</span>
                            <?php else: ?>
                                <span style="color:#7a8fa6;">—</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center;padding:40px;color:#4a5568;">No reservations found</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="rejectModal" class="modal">
    <div class="modal-box">
        <h3>Reject Reservation</h3>
        <form method="POST" action="manage_reservations.php">
            <input type="hidden" name="id" id="rejectId">
            <textarea name="note" placeholder="Reason (optional)" style="width:100%;padding:8px;margin:10px 0;background:#0b0f2a;color:white;border:1px solid #4F7CAC;border-radius:6px;box-sizing:border-box;"></textarea>
            <div class="modal-buttons">
                <button type="submit" name="reject" style="background:red;color:white;">Reject</button>
                <button type="button" onclick="document.getElementById('rejectModal').style.display='none'">Cancel</button>
            </div>
        </form>
    </div>
</div>

<script>
function rejectRes(id){
    document.getElementById('rejectId').value = id;
    document.getElementById('rejectModal').style.display = 'flex';
}
</script>
</body>
</html>