<?php
session_start();
require 'db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

// Fetch all notifications
$notif_result = $conn->query("
    SELECT n.id, n.user_id, u.fullname AS farmer_name, n.crop_name, n.message, n.notify_date, n.status
    FROM crop_notifications n
    LEFT JOIN users u ON n.user_id = u.id
    ORDER BY n.notify_date DESC
");

// Fetch all farmers
$farmer_result = $conn->query("SELECT id, fullname FROM users ORDER BY fullname");

// Handle Send
if(isset($_POST['send_notification'])){
    $user_id = $_POST['user_id'];
    $crop_name = $_POST['crop_name'];
    $message = $_POST['message'];
    $date = date('Y-m-d');

    if($user_id == 0){
        $all_farmers = $conn->query("SELECT id FROM users");
        while($farmer = $all_farmers->fetch_assoc()){
            $stmt = $conn->prepare("INSERT INTO crop_notifications (user_id, crop_name, message, notify_date) VALUES (?,?,?,?)");
            $stmt->bind_param("isss", $farmer['id'], $crop_name, $message, $date);
            $stmt->execute();
            $stmt->close();
        }
    } else {
        $stmt = $conn->prepare("INSERT INTO crop_notifications (user_id, crop_name, message, notify_date) VALUES (?,?,?,?)");
        $stmt->bind_param("isss", $user_id, $crop_name, $message, $date);
        $stmt->execute();
        $stmt->close();
    }

    header("Location: notifications_admin.php");
    exit;
}

// Toggle Status
if(isset($_GET['toggle'])){
    $id = $_GET['toggle'];
    $notif = $conn->query("SELECT status FROM crop_notifications WHERE id=$id")->fetch_assoc();
    $new_status = ($notif['status']=='unread') ? 'read' : 'unread';
    $stmt = $conn->prepare("UPDATE crop_notifications SET status=? WHERE id=?");
    $stmt->bind_param("si", $new_status, $id);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications_admin.php");
    exit;
}

// Delete
if(isset($_GET['delete'])){
    $id = $_GET['delete'];
    $stmt = $conn->prepare("DELETE FROM crop_notifications WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
    header("Location: notifications_admin.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Notifications Admin</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
body{
    margin:0;
    padding:0;
    font-family:'Poppins',sans-serif;
    background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);
    background-size:400% 400%;
    animation: gradientBG 20s ease infinite;
    color:#fff;
}
@keyframes gradientBG {
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

/* Floating Background Icons */
.icon-bg{
    position:fixed; font-size:2.5rem;
    color:rgba(255,255,255,0.15);
    animation: floatBg 20s linear infinite;
    z-index:-1;
}
.icon-bg:nth-child(1){top:10%; left:5%;}
.icon-bg:nth-child(2){top:30%; left:85%;}
.icon-bg:nth-child(3){top:70%; left:10%;}
.icon-bg:nth-child(4){top:50%; left:50%;}
.icon-bg:nth-child(5){top:80%; left:80%;}
@keyframes floatBg{
    0%{transform: translateY(0) rotate(0deg);}
    50%{transform: translateY(-30px) rotate(180deg);}
    100%{transform: translateY(0) rotate(360deg);}
}

.container{
    width:90%;
    max-width:1200px;
    margin: 40px auto;
}

.card{
    background: rgba(255,255,255,0.15);
    padding:25px;
    border-radius:20px;
    backdrop-filter: blur(10px);
    box-shadow:0 10px 30px rgba(0,0,0,0.4);
    margin-bottom:30px;
}

h2, h4 {
    text-shadow:0 2px 5px rgba(0,0,0,0.4);
}

/* Table Styles */
table{
    width:100%;
    color:#fff;
    border-collapse: collapse;
}
table th{
    background: rgba(255,255,255,0.2);
    padding:12px;
    text-align:left;
}
table td{
    padding:12px;
    background: rgba(255,255,255,0.1);
    border-bottom:1px solid rgba(255,255,255,0.2);
}

.status-unread{color:#ffeb3b; font-weight:bold;}
.status-read{color:#00ff90; font-weight:bold;}

button, .btn{
    border:none;
    padding:10px 18px;
    border-radius:12px;
    cursor:pointer;
    font-weight:600;
}

.btn-warning{background:#ffb100; color:#000;}
.btn-danger{background:#ff4c4c; color:#fff;}
.btn-success{background:#28a745; color:#fff;}

/* Back Button */
.back-btn{
    display:inline-block;
    padding:12px 25px;
    font-size:1rem;
    background: linear-gradient(135deg, #ff4c4c, #ff7f50);
    border-radius:15px;
    color:#fff;
    text-decoration:none;
    box-shadow:0 4px 15px rgba(0,0,0,0.3);
    transition:0.3s ease;
}
.back-btn:hover{
    transform:scale(1.05);
    box-shadow:0 8px 25px rgba(0,0,0,0.5);
}
</style>
</head>

<body>

<!-- Floating Icons -->
<i class="fas fa-leaf icon-bg"></i>
<i class="fas fa-seedling icon-bg"></i>
<i class="fas fa-tractor icon-bg"></i>
<i class="fas fa-water icon-bg"></i>
<i class="fas fa-sun icon-bg"></i>

<div class="container">

<a href="admin_dashboard.php" class="back-btn">⬅ Back to Dashboard</a>

<h2 class="text-center" style="margin-top:20px;">🔔 Notifications Management</h2>

<!-- SEND NOTIFICATION -->
<div class="card">
    <h4>Send Notification</h4>
    <form method="POST">
        <label>Target Farmer:</label>
        <select class="form-control" name="user_id" required>
            <option value="0">All Farmers</option>
            <?php while($farmer = $farmer_result->fetch_assoc()): ?>
                <option value="<?php echo $farmer['id']; ?>">
                    <?php echo htmlspecialchars($farmer['fullname']); ?>
                </option>
            <?php endwhile; ?>
        </select>

        <label style="margin-top:15px;">Crop Name (optional):</label>
        <input type="text" class="form-control" name="crop_name" placeholder="Leave empty if general">

        <label style="margin-top:15px;">Message:</label>
        <textarea class="form-control" name="message" rows="3" required></textarea>

        <button type="submit" name="send_notification" class="btn btn-success" style="margin-top:15px;">
            Send Notification
        </button>
    </form>
</div>

<!-- NOTIFICATIONS TABLE -->
<div class="card">
    <h4>All Notifications</h4>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>Farmer</th>
                <th>Crop</th>
                <th>Message</th>
                <th>Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>

        <tbody>
        <?php if($notif_result->num_rows > 0): ?>
            <?php while($row = $notif_result->fetch_assoc()): ?>
                <tr>
                    <td><?php echo $row['id']; ?></td>
                    <td><?php echo $row['farmer_name'] ?? 'All Farmers'; ?></td>
                    <td><?php echo htmlspecialchars($row['crop_name']); ?></td>
                    <td><?php echo htmlspecialchars($row['message']); ?></td>
                    <td><?php echo $row['notify_date']; ?></td>
                    <td class="status-<?php echo $row['status']; ?>">
                        <?php echo ucfirst($row['status']); ?>
                    </td>
                    <td>
                        <a href="?toggle=<?php echo $row['id']; ?>" class="btn btn-warning btn-sm">
                            Mark <?php echo $row['status']=='unread'?'Read':'Unread'; ?>
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this notification?');">
                            Delete
                        </a>
                    </td>
                </tr>
            <?php endwhile; ?>
        <?php else: ?>
            <tr><td colspan="7" style="text-align:center;">No notifications found.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

</div>
</body>
</html>
