<?php
session_start();
if(!isset($_SESSION['admin'])){
    header("Location: login.php");
    exit();
}
include "../db.php";

if(isset($_GET['toggle'])){
    $id = intval($_GET['toggle']);
    $q = mysqli_query($conn, "SELECT status FROM users WHERE id='$id' AND role='user'");
    if($row = mysqli_fetch_assoc($q)){
        $new = ($row['status'] == 'active') ? 'banned' : 'active';
        mysqli_query($conn, "UPDATE users SET status='$new' WHERE id='$id'");
    }
    header("Location: manage_users.php");
    exit();
}

if(isset($_GET['delete'])){
    $id = intval($_GET['delete']);
    mysqli_query($conn, "DELETE FROM users WHERE id='$id' AND role='user'");
    header("Location: manage_users.php");
    exit();
}

$users = mysqli_query($conn, "SELECT * FROM users WHERE role='user' ORDER BY id DESC");
?>
<!DOCTYPE html>
<html>
<head>
    <title>Manage Users</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body>
<div class="container">
    <h1 class="title">👥 MANAGE USERS</h1>
    <div class="nav">
        <a href="index.php">Dashboard</a>
        <a href="manage_reservations.php">Reservations</a>
        <a href="logout.php">Logout</a>
    </div>

    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>ID</th><th>Name</th><th>Email</th><th>Phone</th>
                    <th>Username</th><th>Status</th><th>Registered</th><th>Action</th>
                </tr>
            </thead>
            <tbody>
                <?php if(mysqli_num_rows($users) > 0): ?>
                    <?php while($u = mysqli_fetch_assoc($users)): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['fullname'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($u['email'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($u['phone'] ?? '-'); ?></td>
                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                        <td>
                            <span style="color:<?php echo $u['status']=='active'?'#2ed573':'#ff4757'; ?>">
                                <?php echo ucfirst($u['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M d, Y', strtotime($u['created_at'])); ?></td>
                        <td>
                            <a class="editbtn" href="?toggle=<?php echo $u['id']; ?>">
                                <?php echo $u['status']=='active' ? 'Ban' : 'Unban'; ?>
                            </a>
                            <a class="exitbtn" href="?delete=<?php echo $u['id']; ?>" onclick="return confirm('Delete this user?')">Delete</a>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="8" style="text-align:center;padding:40px;color:#4a5568;">No registered users yet</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>