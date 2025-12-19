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

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<style>
/* General Styling */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
    overflow-x:hidden;
    background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);
    background-size:400% 400%;
    animation: gradientBG 20s ease infinite;
    color:#fff;
}
@keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}

/* Floating Icons */
.icon-bg{
    position:fixed;font-size:2.5rem;color:rgba(255,255,255,0.15);
    animation: floatBg 20s linear infinite;z-index:-1;
}
.icon-bg:nth-child(1){top:10%; left:5%;}
.icon-bg:nth-child(2){top:30%; left:85%;}
.icon-bg:nth-child(3){top:70%; left:10%;}
.icon-bg:nth-child(4){top:50%; left:50%;}
.icon-bg:nth-child(5){top:80%; left:80%;}
@keyframes floatBg{0%{transform: translateY(0) rotate(0deg);}50%{transform: translateY(-30px) rotate(180deg);}100%{transform: translateY(0) rotate(360deg);}}

/* Dashboard Layout */
.dashboard{display:flex; min-height:100vh;}
.sidebar{
    width:250px; background: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px); padding:20px;
    display:flex; flex-direction:column; justify-content:space-between;
    border-right:1px solid rgba(255,255,255,0.2);
}
.sidebar h2{text-align:center; font-size:1.8rem; margin-bottom:30px;}
.sidebar a{
    color:#fff; text-decoration:none; padding:12px 15px;
    border-radius:12px; margin:6px 0; display:block; transition:.3s;
}
.sidebar a:hover{background: rgba(255,255,255,0.2);}

.lang-switch{text-align:center; margin-top:20px;}
.lang-switch a{
    padding:6px 12px; border-radius:15px;
    background:rgba(255,255,255,0.2); margin:0 5px;
}
.lang-switch a:hover{background:rgba(255,255,255,0.35);}

.logout-btn{
    margin-top:20px; padding:10px 25px;
    background:#ff4c4c; border:none; border-radius:25px;
    font-weight:600; color:#fff; cursor:pointer; transition:.3s;
}
.logout-btn:hover{background:#ff1c1c; transform:scale(1.05);}

/* Main Content */
.main{flex:1;padding:30px;}
.header{display:flex; justify-content:space-between; align-items:center;}
.header h1{font-size:2.4rem;font-weight:700;}
.header .welcome{font-size:1.1rem; opacity:.8;}

.cards{
    display:grid;grid-template-columns:repeat(auto-fit,minmax(220px,1fr));
    gap:20px;margin-top:30px;
}
.card{
    background:rgba(255,255,255,0.1);padding:25px;border-radius:20px;
    text-align:center;backdrop-filter:blur(10px);
    box-shadow:0 10px 30px rgba(0,0,0,0.4);
    transition:.3s; cursor:pointer;
}
.card:hover{transform:translateY(-6px);background:rgba(255,255,255,0.2);}
.card i{font-size:2.2rem;margin-bottom:12px;}
.card h3{font-size:1.3rem;}
</style>
</head>

<body>

<!-- Background Icons -->
<i class="fas fa-leaf icon-bg"></i>
<i class="fas fa-seedling icon-bg"></i>
<i class="fas fa-tractor icon-bg"></i>
<i class="fas fa-water icon-bg"></i>
<i class="fas fa-sun icon-bg"></i>

<div class="dashboard">

<!-- Sidebar -->
<div class="sidebar">
    <h2>Admin Dashboard</h2>

    <a href="crop_management.php"><i class="fas fa-seedling"></i> Crop Management</a>
    <a href="knowledge_base_admin.php"><i class="fas fa-book"></i> Knowledge Base</a>
    <a href="notifications_admin.php"><i class="fas fa-bell"></i> Notifications</a>
    <a href="farmers_admin.php"><i class="fas fa-user"></i> Farmer Management</a>

    <div class="lang-switch">
        <a href="?lang=en">EN</a> 
        <a href="?lang=te">TE</a>
    </div>

    <form action="logout.php" method="POST">
        <button class="logout-btn"><?php echo $translations['logout']; ?></button>
    </form>
</div>

<!-- Main Content -->
<div class="main">

    <div class="header">
        <h1>Hello, <?php echo $admin['fullname']; ?></h1>
        <div class="welcome">Role: Admin</div>
    </div>

    <!-- Dashboard Shortcut Cards -->
    <div class="cards">
        <a href="crop_management.php" class="card">
            <i class="fas fa-seedling"></i><h3>Crop Management</h3>
        </a>

        <a href="knowledge_base_admin.php" class="card">
            <i class="fas fa-book"></i><h3>Knowledge Base</h3>
        </a>

        <a href="notifications_admin.php" class="card">
            <i class="fas fa-bell"></i><h3>Notifications</h3>
        </a>

        <a href="farmers_admin.php" class="card">
            <i class="fas fa-user"></i><h3>Farmer Management</h3>
        </a>
    </div>

</div>
</div>

</body>
</html>
