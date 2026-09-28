<?php
session_start();

if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}

include "../db.php";

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    mysqli_query($conn,"DELETE FROM history WHERE id='$id'");
    header("Location: history.php");
    exit();
}

if(isset($_POST['update'])){
    $id = intval($_POST['id']);
    $fee = floatval($_POST['fee']);
    mysqli_query($conn,"UPDATE history SET total_fee='$fee' WHERE id='$id'");
    header("Location: history.php");
    exit();
}

$search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

if($search) {
    $result = mysqli_query($conn, "
        SELECT * FROM history 
        WHERE plate_number LIKE '%$search%' 
        OR slot_number LIKE '%$search%'
        ORDER BY id DESC
    ");
} else {
    $result = mysqli_query($conn,"SELECT * FROM history ORDER BY id DESC");
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Parking History</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
</head>
<body>

<div class="history-container">
    <div class="history-top">
        <div class="history-brand">
            <h1>PARKING <span>HISTORY</span></h1>
            <span class="sub">RECORDS</span>
        </div>
        <div class="history-nav">
            <a href="index.php"><i class="fas fa-th-large"></i> Dashboard</a>
            <a href="revenue.php"><i class="fas fa-chart-line"></i> Revenue</a>
            <a href="logout.php" class="logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
        </div>
    </div>

    <div class="history-search">
        <form method="GET" action="history.php">
            <i class="fas fa-search"></i>
            <input type="text" name="search" placeholder="Search by plate or slot..." value="<?php echo htmlspecialchars($search); ?>" />
            <button type="submit"><i class="fas fa-search"></i> Search</button>
            <?php if($search): ?>
                <a href="history.php" class="clear-btn" style="color:#fca5a5;margin-left:10px;text-decoration:none;"><i class="fas fa-times"></i> Clear</a>
            <?php endif; ?>
        </form>
    </div>

    <div class="history-table-wrapper">
        <table class="history-table">
            <thead>
                <tr>
                    <th>PLATE NO.</th>
                    <th>SLOT</th>
                    <th>DATE</th>
                    <th>TIME IN</th>
                    <th>TIME OUT</th>
                    <th>AMOUNT</th>
                    <th>PAYMENT</th>
                    <th>VERIFIED</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($result) > 0): ?>
                    <?php while($row = mysqli_fetch_assoc($result)): 
                        $time_in = strtotime($row['time_in']);
                        $time_out = strtotime($row['time_out']);
                        
                        $payment = isset($row['payment_method']) ? $row['payment_method'] : 'Cash';
                        $paymentClasses = [
                            'Cash' => 'payment-cash',
                            'GCash' => 'payment-gcash',
                            'Maya' => 'payment-maya',
                            'Credit Card' => 'payment-card',
                            'Credit' => 'payment-card'
                        ];
                        $paymentClass = $paymentClasses[$payment] ?? 'payment-cash';
                    ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($row['plate_number']); ?></strong></td>
                            <td><span class="slot-badge"><?php echo $row['slot_number']; ?></span></td>
                            <td><?php echo date('Y-m-d', $time_in); ?></td>
                            <td><?php echo date('H:i', $time_in); ?></td>
                            <td><?php echo date('H:i', $time_out); ?></td>
                            <td class="amount">
                                ₱<?php echo number_format($row['total_fee'], 2); ?>
                                <?php if(!empty($row['overtime_hours']) && $row['overtime_hours'] > 0): ?>
                                    <small class="overtime-tag-admin">+<?php echo $row['overtime_hours']; ?>h overtime</small>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="payment-badge <?php echo $paymentClass; ?>">
                                    <?php echo htmlspecialchars($payment); ?>
                                </span>
                            </td>
                            <td>
                                <?php if(!empty($row['payment_verified']) && $row['payment_verified'] == 1): ?>
                                    <span class="payment-verified-badge" title="Verified by <?php echo htmlspecialchars($row['verified_by'] ?? 'system'); ?>">
                                        <i class="fas fa-check-circle"></i> Verified
                                    </span>
                                <?php else: ?>
                                    <span class="payment-unverified-badge">
                                        <i class="fas fa-exclamation-circle"></i> Unverified
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <button class="receipt-btn" onclick="showReceipt(
                                    <?php echo $row['id']; ?>, 
                                    '<?php echo htmlspecialchars($row['plate_number']); ?>', 
                                    '<?php echo $row['slot_number']; ?>', 
                                    '<?php echo $row['time_in']; ?>', 
                                    '<?php echo $row['time_out']; ?>', 
                                    '<?php echo $row['total_fee']; ?>', 
                                    '<?php echo htmlspecialchars($payment); ?>',
                                    '<?php echo htmlspecialchars($row['verified_by'] ?? 'Not verified'); ?>',
                                    '<?php echo !empty($row['payment_verified']) ? 'Yes' : 'No'; ?>'
                                )">
                                    <i class="fas fa-receipt"></i> Receipt
                                </button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" class="no-data">No history records found</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- RECEIPT MODAL -->
<div class="receipt-modal" id="receiptModal">
    <div class="receipt-overlay" onclick="closeReceipt()"></div>
    <div class="receipt-content">
        <button class="receipt-close" onclick="closeReceipt()"><i class="fas fa-times"></i></button>
        <div class="receipt" id="receiptContent">
        </div>
        <div class="receipt-actions">
            <button class="print-btn" onclick="printReceipt()">
                <i class="fas fa-print"></i> Print Receipt
            </button>
        </div>
    </div>
</div>

<!-- DELETE CONFIRM MODAL -->
<div id="deleteModal" class="modal">
    <div class="modal-box">
        <p>Delete this record?</p>
        <label style="font-size:14px;">
            <input type="checkbox" id="dontAsk">
            Do not ask again
        </label>
        <div class="modal-buttons">
            <button id="confirmDelete">Delete</button>
            <button id="cancelDelete">Cancel</button>
        </div>
    </div>
</div>

<script>
let deleteId = null;

document.querySelectorAll(".deleteBtn").forEach(btn => {
    btn.addEventListener("click", function(){
        deleteId = this.dataset.id;
        if(localStorage.getItem("skipDeleteConfirm") === "true"){
            window.location = "history.php?delete=" + deleteId;
            return;
        }
        document.getElementById("deleteModal").style.display="flex";
    });
});

document.getElementById("confirmDelete").onclick = function(){
    if(document.getElementById("dontAsk").checked){
        localStorage.setItem("skipDeleteConfirm","true");
    }
    window.location = "history.php?delete=" + deleteId;
};

document.getElementById("cancelDelete").onclick = function(){
    document.getElementById("deleteModal").style.display="none";
};

function showReceipt(id, plate, slot, time_in, time_out, total_fee, payment, verified_by, verified) {
    const dateIn = new Date(time_in);
    const dateOut = new Date(time_out);
    const paymentMethod = payment || 'Cash';

    const diffMs = dateOut - dateIn;
    const hours = Math.floor(diffMs / 3600000);
    const minutes = Math.floor((diffMs % 3600000) / 60000);
    const duration = hours > 0 ? `${hours}h ${minutes}m` : `${minutes}m`;

    const receiptHTML = `
        <div class="receipt-header">
            <h1>PARKREV</h1>
            <div class="sub">Parking Management System</div>
            <div class="phone">Tel: (02) 8888-0000</div>
        </div>

        <div class="receipt-body">
            <div class="receipt-row">
                <span class="label">Receipt No:</span>
                <span class="value">RCP-${String(id).padStart(3, '0')}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Date:</span>
                <span class="value">${dateIn.toISOString().split('T')[0]}</span>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-section-title">SESSION DETAILS</div>

            <div class="receipt-row">
                <span class="label">Plate No.</span>
                <span class="value">${plate}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Slot</span>
                <span class="value">${slot}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Time In</span>
                <span class="value">${dateIn.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Time Out</span>
                <span class="value">${dateOut.toLocaleTimeString('en-US', {hour: '2-digit', minute: '2-digit'})}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Duration</span>
                <span class="value">${duration}</span>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-section-title">PAYMENT</div>

            <div class="receipt-row">
                <span class="label">Method</span>
                <span class="value">${paymentMethod}</span>
            </div>
            <div class="receipt-row">
                <span class="label">Status</span>
                <span class="value" style="color: #86efac;">Paid</span>
            </div>
            <div class="receipt-row">
                <span class="label">Verified By</span>
                <span class="value">${verified_by}</span>
            </div>

            <div class="receipt-divider"></div>

            <div class="receipt-row total">
                <span class="label">TOTAL</span>
                <span class="value">₱${parseFloat(total_fee).toFixed(2)}</span>
            </div>
        </div>

        <div class="receipt-footer">
            <div class="thankyou">Thank you for parking with us!</div>
            <div>Please drive safely.</div>
            <div class="ref">SESSION-${String(id).padStart(3, '0')}</div>
        </div>
    `;

    document.getElementById('receiptContent').innerHTML = receiptHTML;
    document.getElementById('receiptModal').classList.add('active');
}

function closeReceipt() {
    document.getElementById('receiptModal').classList.remove('active');
}

function printReceipt() {
    const content = document.getElementById('receiptContent').innerHTML;
    const printWindow = window.open('', '_blank', 'width=400,height=600');

    printWindow.document.write(`
        <!DOCTYPE html>
        <html>
        <head>
            <title>Receipt - ParkRev</title>
            <style>
                * { margin: 0; padding: 0; box-sizing: border-box; }

                body {
                    font-family: 'Courier New', monospace;
                    color: #000;
                    background: #fff;
                    padding: 20px;
                    font-size: 12px;
                    line-height: 1.5;
                }

                .receipt-header {
                    text-align: center;
                    padding-bottom: 12px;
                    border-bottom: 2px dashed #000;
                    margin-bottom: 12px;
                }

                .receipt-header h1 {
                    font-size: 20px;
                    font-weight: 700;
                    letter-spacing: 3px;
                    color: #000;
                    margin-bottom: 4px;
                }

                .receipt-header .sub { font-size: 10px; color: #333; margin-bottom: 3px; }
                .receipt-header .phone { font-size: 10px; color: #666; }

                .receipt-body { padding: 6px 0; }

                .receipt-row {
                    display: flex;
                    justify-content: space-between;
                    padding: 4px 0;
                    font-size: 11px;
                }

                .receipt-row .label { color: #333; }
                .receipt-row .value { color: #000; font-weight: 600; }

                .receipt-divider {
                    border-bottom: 1px dashed #999;
                    margin: 10px 0;
                }

                .receipt-section-title {
                    font-size: 10px;
                    font-weight: 700;
                    letter-spacing: 1px;
                    color: #000;
                    margin-bottom: 6px;
                }

                .receipt-row.total {
                    font-size: 16px;
                    font-weight: 700;
                    border-top: 2px solid #000;
                    padding-top: 10px;
                    margin-top: 8px;
                }

                .receipt-footer {
                    text-align: center;
                    padding-top: 12px;
                    border-top: 2px dashed #000;
                    margin-top: 12px;
                    font-size: 10px;
                    color: #333;
                }

                .receipt-footer .thankyou {
                    font-weight: 700;
                    color: #000;
                    margin-bottom: 4px;
                }

                .receipt-footer .ref {
                    margin-top: 8px;
                    font-size: 9px;
                    color: #666;
                    letter-spacing: 1px;
                }

                @media print {
                    body { padding: 10px; }
                    @page { margin: 8mm; }
                }
            </style>
        </head>
        <body>
            ${content}
        </body>
        </html>
    `);

    printWindow.document.close();

    setTimeout(function(){
        printWindow.focus();
        printWindow.print();
    }, 300);
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeReceipt();
    }
});

document.addEventListener('DOMContentLoaded', function() {
    const overlay = document.querySelector('.receipt-overlay');
    if (overlay) {
        overlay.addEventListener('click', closeReceipt);
    }
});
</script>

</body>
</html>