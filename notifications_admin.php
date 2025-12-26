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

<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* Reset & Base */
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

:root {
    --primary-green: #2d8659;
    --primary-green-dark: #1f5d3f;
    --primary-green-light: #3da372;
    --secondary-green: #4caf50;
    --accent-orange: #ff9800;
    --accent-yellow: #ffc107;
    --text-dark: #2c3e50;
    --text-light: #5a6c7d;
    --bg-light: #f8f9fa;
    --bg-white: #ffffff;
    --border-color: #e0e0e0;
    --shadow-sm: 0 2px 8px rgba(0,0,0,0.08);
    --shadow-md: 0 4px 16px rgba(0,0,0,0.12);
    --shadow-lg: 0 8px 32px rgba(0,0,0,0.16);
    --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
}

body {
    font-family: 'Inter', 'Poppins', sans-serif;
    background: linear-gradient(135deg, #f5f7fa 0%, #e8f5e9 100%);
    min-height: 100vh;
    color: var(--text-dark);
    line-height: 1.6;
    overflow-x: hidden;
}

/* Background Pattern */
body::before {
    content: "";
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-image: 
        radial-gradient(circle at 20% 50%, rgba(45, 134, 89, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 80% 80%, rgba(76, 175, 80, 0.03) 0%, transparent 50%);
    z-index: 0;
    pointer-events: none;
}

/* Dashboard Layout */
.dashboard {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* Sidebar */
.sidebar {
    width: 280px;
    background: white;
    box-shadow: var(--shadow-md);
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    border-right: 2px solid var(--bg-light);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    animation: slideInLeft 0.4s ease-out;
}

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.sidebar h2 {
    text-align: center;
    font-size: 24px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 40px;
    padding-bottom: 20px;
    border-bottom: 3px solid var(--bg-light);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.sidebar h2 i {
    color: var(--primary-green);
    font-size: 28px;
}

.sidebar a {
    color: var(--text-dark);
    text-decoration: none;
    padding: 14px 18px;
    border-radius: 12px;
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: var(--transition);
    font-weight: 500;
    font-size: 15px;
    position: relative;
}

.sidebar a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: var(--primary-green);
    border-radius: 0 4px 4px 0;
    transform: scaleY(0);
    transition: var(--transition);
}

.sidebar a:hover {
    background: var(--bg-light);
    color: var(--primary-green);
    padding-left: 22px;
}

.sidebar a:hover::before {
    transform: scaleY(1);
}

.sidebar a i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

/* Language Switch */
.lang-switch {
    text-align: center;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 2px solid var(--bg-light);
}

.lang-switch a {
    padding: 8px 16px;
    border-radius: 8px;
    background: var(--bg-light);
    margin: 0 4px;
    color: var(--text-dark);
    font-weight: 600;
    font-size: 13px;
    display: inline-block;
    transition: var(--transition);
}

.lang-switch a:hover {
    background: var(--primary-green);
    color: white;
    transform: translateY(-2px);
}

/* Logout Button */
.logout-btn {
    width: 100%;
    margin-top: 20px;
    padding: 14px 25px;
    background: linear-gradient(135deg, #f56565, #e53e3e);
    border: none;
    border-radius: 12px;
    font-weight: 700;
    color: white;
    cursor: pointer;
    transition: var(--transition);
    font-size: 15px;
    box-shadow: var(--shadow-sm);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Main Content */
.main {
    flex: 1;
    padding: 40px;
    background: transparent;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Back Button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 20px;
    padding: 12px 24px;
    background: white;
    color: var(--primary-green-dark);
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.back-btn:hover {
    background: var(--primary-green);
    color: white;
    transform: translateX(-4px);
    box-shadow: var(--shadow-md);
}

/* Header */
.header {
    text-align: center;
    margin-bottom: 30px;
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

.header h2 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin: 0;
}

.header h2 i {
    color: var(--primary-green);
    font-size: 36px;
}

/* Card */
.card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
    border: 2px solid transparent;
    transition: var(--transition);
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
    position: relative;
    overflow: hidden;
}

.card::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
    transform: scaleX(0);
    transition: var(--transition);
}

.card:hover::before {
    transform: scaleX(1);
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }

.card h4 {
    font-size: 24px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 24px;
    display: flex;
    align-items: center;
    gap: 12px;
}

.card h4 i {
    color: var(--primary-green);
}

/* Form Controls */
label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
    margin-top: 16px;
}

label:first-child {
    margin-top: 0;
}

.form-control, select, textarea, input[type="text"] {
    width: 100%;
    padding: 12px 16px;
    border-radius: 10px;
    border: 2px solid var(--border-color);
    font-size: 15px;
    font-family: inherit;
    transition: var(--transition);
    background: white;
    color: var(--text-dark);
    resize: vertical;
}

.form-control::placeholder, textarea::placeholder {
    color: var(--text-light);
}

.form-control:focus, select:focus, textarea:focus, input[type="text"]:focus {
    outline: none;
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

/* Buttons */
button, .btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 12px 24px;
    border-radius: 10px;
    border: none;
    cursor: pointer;
    font-weight: 700;
    font-size: 14px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
    text-decoration: none;
}

.btn::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn span {
    position: relative;
    z-index: 1;
}

.btn-success {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
}

.btn-success:hover {
    background: linear-gradient(135deg, var(--primary-green-light), var(--primary-green));
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-danger {
    background: linear-gradient(135deg, #f56565, #e53e3e);
    color: white;
}

.btn-danger:hover {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-warning {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: white;
}

.btn-warning:hover {
    background: linear-gradient(135deg, #f57c00, #e65100);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-sm {
    padding: 8px 16px;
    font-size: 13px;
}

/* Table */
table {
    width: 100%;
    border-collapse: collapse;
    color: var(--text-dark);
}

thead th {
    text-align: left;
    padding: 16px 14px;
    background: var(--bg-light);
    border-bottom: 3px solid var(--primary-green);
    font-weight: 700;
    color: var(--primary-green-dark);
    font-size: 13px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    position: sticky;
    top: 0;
}

tbody td {
    padding: 16px 14px;
    border-bottom: 1px solid var(--bg-light);
    color: var(--text-dark);
    font-size: 14px;
    transition: var(--transition);
}

tbody tr {
    transition: var(--transition);
    animation: slideInRight 0.4s ease-out;
    animation-fill-mode: both;
}

tbody tr:nth-child(1) { animation-delay: 0.1s; }
tbody tr:nth-child(2) { animation-delay: 0.2s; }
tbody tr:nth-child(3) { animation-delay: 0.3s; }
tbody tr:nth-child(4) { animation-delay: 0.4s; }
tbody tr:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

tbody tr:hover {
    background: var(--bg-light);
    transform: translateX(4px);
}

.status-unread {
    color: #ff9800;
    font-weight: 700;
}

.status-read {
    color: var(--secondary-green);
    font-weight: 700;
}

.text-center {
    text-align: center;
}

/* Responsive */
@media (max-width: 1024px) {
    .dashboard {
        flex-direction: column;
    }

    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
        border-right: none;
        border-bottom: 2px solid var(--bg-light);
    }

    .main {
        padding: 20px;
    }
}

@media (max-width: 768px) {
    .header {
        padding: 24px;
    }

    .header h2 {
        font-size: 24px;
    }

    .card {
        padding: 24px;
    }

    table {
        font-size: 13px;
    }

    th, td {
        padding: 12px 8px;
    }
}

@media (max-width: 480px) {
    .main {
        padding: 16px;
    }
}
</style>
</head>

<body>

<div class="dashboard">
    <!-- Sidebar -->
    <div class="sidebar">
        <h2>
            <i class="fas fa-shield-alt"></i>
            Admin Dashboard
        </h2>
        <nav>
            <a href="admin_dashboard.php">
                <i class="fas fa-home"></i>
                <span>Home</span>
            </a>
            <a href="crop_management.php">
                <i class="fas fa-seedling"></i>
                <span>Crop Management</span>
            </a>
            <a href="knowledge_base_admin.php">
                <i class="fas fa-book"></i>
                <span>Knowledge Base</span>
            </a>
            <a href="notifications_admin.php">
                <i class="fas fa-bell"></i>
                <span>Notifications</span>
            </a>
            <a href="farmers_admin.php">
                <i class="fas fa-users"></i>
                <span>Farmer Management</span>
            </a>
        </nav>

        <div>
            <div class="lang-switch">
                <a href="?lang=en">EN</a>
                <a href="?lang=te">TE</a>
            </div>

            <form action="logout.php" method="POST">
                <button type="submit" class="logout-btn">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main">
        <a href="admin_dashboard.php" class="back-btn">
            <i class="fas fa-arrow-left"></i> Back to Dashboard
        </a>

        <div class="header">
            <h2>
                <i class="fas fa-bell"></i>
                Notifications Management
            </h2>
        </div>

        <!-- SEND NOTIFICATION -->
        <div class="card">
            <h4>
                <i class="fas fa-paper-plane"></i>
                Send Notification
            </h4>
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

            <button type="submit" name="send_notification" class="btn btn-success" style="margin-top:20px;">
                <i class="fas fa-paper-plane"></i> <span>Send Notification</span>
            </button>
    </form>
</div>

        <!-- NOTIFICATIONS TABLE -->
        <div class="card">
            <h4>
                <i class="fas fa-list"></i>
                All Notifications
            </h4>

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
                            <i class="fas fa-<?php echo $row['status']=='unread'?'check':'undo'; ?>"></i>
                            <span>Mark <?php echo $row['status']=='unread'?'Read':'Unread'; ?></span>
                        </a>
                        <a href="?delete=<?php echo $row['id']; ?>" class="btn btn-danger btn-sm"
                           onclick="return confirm('Delete this notification?');">
                            <i class="fas fa-trash"></i> <span>Delete</span>
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
</div>

</body>
</html>
