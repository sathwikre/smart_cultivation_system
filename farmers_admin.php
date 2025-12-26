<?php
session_start();
require 'db_connection.php';

// ADMIN CHECK
if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    header("Location: login.php");
    exit;
}

// Fetch all farmers
$sql = "SELECT * FROM users WHERE role='farmer' ORDER BY created_at DESC";
$farmers = $conn->query($sql);

// Function: Count crops owned by farmer
function cropCount($conn, $id){
    $s = $conn->prepare("SELECT COUNT(*) AS total FROM farmer_crops WHERE user_id=?");
    $s->bind_param("i",$id);
    $s->execute();
    $res = $s->get_result()->fetch_assoc();
    return $res['total'];
}

// Function: Count notifications
function notificationCount($conn, $id){
    $s = $conn->prepare("SELECT COUNT(*) AS total FROM crop_notifications WHERE user_id=?");
    $s->bind_param("i",$id);
    $s->execute();
    $res = $s->get_result()->fetch_assoc();
    return $res['total'];
}
?>
<!DOCTYPE html>
<html>
<head>
<title>Farmer Management | Admin</title>
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
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 30px;
}

.header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    gap: 12px;
    margin: 0;
}

.header h1 i {
    color: var(--primary-green);
    font-size: 36px;
}

/* Search Bar */
.search-container {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
    position: relative;
}

#search {
    width: 100%;
    padding: 14px 16px 14px 50px;
    border-radius: 10px;
    border: 2px solid var(--border-color);
    font-size: 15px;
    font-family: inherit;
    transition: var(--transition);
    background: white;
    color: var(--text-dark);
    outline: none;
}

#search:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

.search-container::before {
    content: "\f002";
    font-family: "Font Awesome 6 Free";
    font-weight: 900;
    position: absolute;
    left: 40px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    font-size: 18px;
    pointer-events: none;
}

/* Table Box */
.table-box {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    overflow-x: auto;
    animation: fadeInUp 0.6s ease-out 0.2s both;
}

table {
    width: 100%;
    border-collapse: collapse;
    color: var(--text-dark);
    min-width: 1200px;
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

/* Action Buttons */
.action-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 16px;
    border: none;
    border-radius: 8px;
    color: white;
    cursor: pointer;
    font-weight: 600;
    font-size: 13px;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    text-decoration: none;
    position: relative;
    overflow: hidden;
}

.action-btn::before {
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

.action-btn:hover::before {
    width: 300px;
    height: 300px;
}

.action-btn span {
    position: relative;
    z-index: 1;
}

.btn-edit {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
}

.btn-edit:hover {
    background: linear-gradient(135deg, var(--primary-green-light), var(--primary-green));
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-delete {
    background: linear-gradient(135deg, #f56565, #e53e3e);
}

.btn-delete:hover {
    background: linear-gradient(135deg, #e53e3e, #c53030);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-deactivate {
    background: linear-gradient(135deg, #ff9800, #f57c00);
}

.btn-deactivate:hover {
    background: linear-gradient(135deg, #f57c00, #e65100);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-activate {
    background: linear-gradient(135deg, #2196f3, #1976d2);
}

.btn-activate:hover {
    background: linear-gradient(135deg, #1976d2, #1565c0);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Status Badge */
.status-badge {
    display: inline-block;
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 12px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.status-active {
    background: rgba(76, 175, 80, 0.1);
    color: var(--secondary-green);
}

.status-inactive {
    background: rgba(255, 152, 0, 0.1);
    color: #ff9800;
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

    .header h1 {
        font-size: 24px;
    }

    .table-box {
        padding: 16px;
    }

    table {
        font-size: 13px;
    }

    th, td {
        padding: 12px 8px;
    }

    .action-btn {
        padding: 6px 12px;
        font-size: 12px;
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
            <h1>
                <i class="fas fa-users"></i>
                Farmer Management
            </h1>
        </div>

        <div class="search-container">
            <input type="text" id="search" placeholder="Search farmers by Name, Email, Mobile, District...">
        </div>

        <div class="table-box">
        <table id="farmersTable">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>District</th>
                    <th>State</th>
                    <th>Registered</th>
                    <th>Crops</th>
                    <th>Notifications</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>

            <tbody>
                <?php if($farmers->num_rows > 0): ?>
                    <?php while($f = $farmers->fetch_assoc()): ?>

                        <tr>
                            <td><?php echo $f['id']; ?></td>
                            <td><?php echo htmlspecialchars($f['fullname']); ?></td>
                            <td><?php echo htmlspecialchars($f['email']); ?></td>
                            <td><?php echo htmlspecialchars($f['mobile']); ?></td>
                            <td><?php echo htmlspecialchars($f['district'] ?? '-'); ?></td>
                            <td><?php echo htmlspecialchars($f['state'] ?? '-'); ?></td>
                            <td><?php echo $f['created_at']; ?></td>

                            <td><?php echo cropCount($conn, $f['id']); ?></td>
                            <td><?php echo notificationCount($conn, $f['id']); ?></td>

                            <td>
                                <?php 
                                    $status = ($f['password'] == 'blocked') ? 'Inactive' : 'Active';
                                    $statusClass = ($status == 'Active') ? 'status-active' : 'status-inactive';
                                ?>
                                <span class="status-badge <?php echo $statusClass; ?>">
                                    <?php echo $status; ?>
                                </span>
                            </td>

                            <td>
                                <a href="edit_farmer.php?id=<?php echo $f['id']; ?>">
                                    <button class="action-btn btn-edit">
                                        <i class="fas fa-edit"></i> <span>Edit</span>
                                    </button>
                                </a>

                                <a href="toggle_farmer.php?id=<?php echo $f['id']; ?>">
                                    <button class="action-btn <?php echo ($status == 'Active') ? 'btn-deactivate' : 'btn-activate'; ?>">
                                        <i class="fas fa-<?php echo ($status == 'Active') ? 'ban' : 'check-circle'; ?>"></i>
                                        <span><?php echo ($status == 'Active') ? 'Deactivate' : 'Activate'; ?></span>
                                    </button>
                                </a>

                                <a href="delete_farmer.php?id=<?php echo $f['id']; ?>" onclick="return confirm('Delete this farmer?')">
                                    <button class="action-btn btn-delete">
                                        <i class="fas fa-trash"></i> <span>Delete</span>
                                    </button>
                                </a>
                            </td>
                        </tr>

                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="11" style="text-align:center;">No farmers found.</td></tr>
                <?php endif; ?>
            </tbody>

            </table>
        </div>
    </div>
</div>

<script>
document.getElementById("search").addEventListener("keyup", function(){
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll("#farmersTable tbody tr");

    rows.forEach(r=>{
        let text = r.innerText.toLowerCase();
        r.style.display = text.includes(filter) ? "" : "none";
    });
});
</script>

</body>
</html>
