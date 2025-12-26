<?php
session_start();
require 'db_connection.php';

// Admin session check
if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

// Fetch admin user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$admin = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Language toggle
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: admin_dashboard.php");
    exit;
}
$lang = $_SESSION['lang'] ?? 'en';
$translations = include "languages/$lang.php";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $translations['admin_dashboard'] ?? 'Admin Dashboard'; ?> | Smart Cultivation System</title>

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
}

.lang-switch a:hover {
    background: var(--primary-green);
    color: white;
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
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

.header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    gap: 12px;
}

.header h1 i {
    color: var(--primary-green);
    font-size: 36px;
}

.header .welcome {
    font-size: 16px;
    color: var(--text-light);
    background: var(--bg-light);
    padding: 10px 20px;
    border-radius: 20px;
    font-weight: 600;
}

/* Cards Grid */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 24px;
    margin-top: 30px;
}

.card {
    background: white;
    padding: 32px;
    border-radius: 16px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    cursor: pointer;
    text-decoration: none;
    color: var(--text-dark);
    border: 2px solid transparent;
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

.card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-green-light);
}

.card:hover::before {
    transform: scaleX(1);
}

.card i {
    font-size: 48px;
    margin-bottom: 16px;
    color: var(--primary-green);
    display: block;
    transition: var(--transition);
}

.card:hover i {
    transform: scale(1.1);
    color: var(--primary-green-light);
}

.card h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--primary-green-dark);
    margin-top: 12px;
}

/* Responsive Design */
@media (max-width: 768px) {
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

    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 16px;
        padding: 24px;
    }

    .header h1 {
        font-size: 24px;
    }

    .cards {
        grid-template-columns: 1fr;
        gap: 16px;
    }

    .card {
        padding: 24px;
    }
}

@media (max-width: 480px) {
    .main {
        padding: 16px;
    }

    .header {
        padding: 20px;
    }

    .card {
        padding: 20px;
    }

    .card i {
        font-size: 40px;
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
                <i class="fas fa-sign-out-alt"></i> <?php echo $translations['logout'] ?? 'Logout'; ?>
            </button>
        </form>
    </div>
</div>

<!-- Main Content -->
<div class="main">
    <div class="header">
        <h1>
            <i class="fas fa-user-shield"></i>
            Hello, <?php echo htmlspecialchars($admin['fullname']); ?>
        </h1>
        <div class="welcome">
            <i class="fas fa-badge-check"></i> Role: Admin
        </div>
    </div>

    <!-- Dashboard Shortcut Cards -->
    <div class="cards">
        <a href="crop_management.php" class="card">
            <i class="fas fa-seedling"></i>
            <h3>Crop Management</h3>
        </a>

        <a href="knowledge_base_admin.php" class="card">
            <i class="fas fa-book"></i>
            <h3>Knowledge Base</h3>
        </a>

        <a href="notifications_admin.php" class="card">
            <i class="fas fa-bell"></i>
            <h3>Notifications</h3>
        </a>

        <a href="farmers_admin.php" class="card">
            <i class="fas fa-users"></i>
            <h3>Farmer Management</h3>
        </a>
    </div>
</div>

</div>

</body>
</html>
