<?php
session_start();
require 'db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'farmer'){
    header("Location: login.php");
    exit;
}

// Fetch user data
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch crops for this farmer
$crops_stmt = $conn->prepare("SELECT crop_name, growth_stage FROM farmer_crops WHERE user_id=?");
$crops_stmt->bind_param("i", $_SESSION['user_id']);
$crops_stmt->execute();
$crops_result = $crops_stmt->get_result();
$farmer_crops = [];
while($row = $crops_result->fetch_assoc()){
    $farmer_crops[] = $row;
}
$crops_stmt->close();

// Fetch notifications
$notif_stmt = $conn->prepare("SELECT id, crop_name, message, notify_date, status FROM crop_notifications WHERE user_id=? ORDER BY notify_date DESC");
$notif_stmt->bind_param("i", $_SESSION['user_id']);
$notif_stmt->execute();
$notif_result = $notif_stmt->get_result();
$notifications = [];
while($row = $notif_result->fetch_assoc()){
    $notifications[] = $row;
}
$notif_stmt->close();

// Fetch knowledge base
$kb_stmt = $conn->prepare("
    SELECT title, description, crop_name, created_at
    FROM knowledge_base
    ORDER BY created_at DESC
");
$kb_stmt->execute();
$kb_result = $kb_stmt->get_result();
$knowledge_items = [];
while($row = $kb_result->fetch_assoc()){
    $knowledge_items[] = $row;
}
$kb_stmt->close();

// Language toggle
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: farmer_dashboard.php");
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
<title><?php echo $translations['farmer_dashboard'] ?? 'Farmer Dashboard'; ?> | Smart Cultivation System</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Leaflet CSS -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>

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
    --sidebar-bg: #1f5d3f;
    --sidebar-hover: #2d8659;
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
        radial-gradient(circle at 80% 80%, rgba(76, 175, 80, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 152, 0, 0.02) 0%, transparent 50%);
    z-index: 0;
    pointer-events: none;
}

/* Floating Background Icons */
.icon-bg {
    position: fixed;
    font-size: 3rem;
    color: rgba(45, 134, 89, 0.05);
    animation: floatBg 20s linear infinite;
    z-index: 0;
    pointer-events: none;
}
.icon-bg:nth-child(1) { top: 10%; left: 5%; animation-duration: 25s; }
.icon-bg:nth-child(2) { top: 30%; right: 8%; animation-duration: 20s; }
.icon-bg:nth-child(3) { bottom: 20%; left: 10%; animation-duration: 30s; }
.icon-bg:nth-child(4) { top: 50%; left: 50%; animation-duration: 22s; }
.icon-bg:nth-child(5) { bottom: 10%; right: 15%; animation-duration: 28s; }

@keyframes floatBg {
    0% { transform: translateY(0) rotate(0deg) scale(1); }
    33% { transform: translateY(-30px) rotate(120deg) scale(1.1); }
    66% { transform: translateY(-60px) rotate(240deg) scale(0.9); }
    100% { transform: translateY(0) rotate(360deg) scale(1); }
}

/* Dashboard Layout */
.dashboard {
    display: flex;
    min-height: 100vh;
    position: relative;
    z-index: 1;
}

/* Hamburger Menu Button */
.menu-toggle {
    display: none;
    position: fixed;
    top: 20px;
    left: 20px;
    z-index: 1001;
    background: var(--primary-green);
    color: white;
    border: none;
    width: 50px;
    height: 50px;
    border-radius: 12px;
    cursor: pointer;
    align-items: center;
    justify-content: center;
    box-shadow: var(--shadow-md);
    transition: var(--transition);
}

.menu-toggle:hover {
    background: var(--primary-green-dark);
    transform: scale(1.05);
}

.menu-toggle i {
    font-size: 24px;
}

/* Sidebar Overlay */
.sidebar-overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    z-index: 998;
    animation: fadeIn 0.3s ease-out;
}

.sidebar-overlay.active {
    display: block;
}

/* Sidebar */
.sidebar {
    width: 280px;
    background: var(--sidebar-bg);
    padding: 30px 20px;
    display: flex;
    flex-direction: column;
    justify-content: space-between;
    box-shadow: var(--shadow-lg);
    position: sticky;
    top: 0;
    height: 100vh;
    overflow-y: auto;
    animation: slideInLeft 0.5s ease-out;
    z-index: 1000;
}

@keyframes slideInLeft {
    from {
        transform: translateX(-100%);
        opacity: 0;
    }
    to {
        transform: translateX(0);
        opacity: 1;
    }
}

.sidebar h2 {
    font-size: 1.5rem;
    font-weight: 800;
    color: white;
    margin-bottom: 30px;
    text-align: center;
    padding-bottom: 20px;
    border-bottom: 2px solid rgba(255,255,255,0.2);
    animation: fadeInDown 0.6s ease-out;
}

@keyframes fadeInDown {
    from {
        opacity: 0;
        transform: translateY(-20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.sidebar a {
    color: rgba(255,255,255,0.9);
    text-decoration: none;
    padding: 14px 18px;
    border-radius: 12px;
    margin: 8px 0;
    display: flex;
    align-items: center;
    gap: 12px;
    transition: var(--transition);
    font-weight: 500;
    position: relative;
    overflow: hidden;
}

.sidebar a::before {
    content: "";
    position: absolute;
    left: 0;
    top: 0;
    width: 4px;
    height: 100%;
    background: var(--secondary-green);
    transform: scaleY(0);
    transition: var(--transition);
}

.sidebar a:hover,
.sidebar a.active {
    background: var(--sidebar-hover);
    color: white;
    transform: translateX(5px);
    box-shadow: var(--shadow-sm);
}

.sidebar a:hover::before,
.sidebar a.active::before {
    transform: scaleY(1);
}

.sidebar a i {
    font-size: 18px;
    width: 24px;
    text-align: center;
}

.sidebar .lang-switch {
    text-align: center;
    padding-top: 20px;
    border-top: 1px solid rgba(255,255,255,0.2);
    margin-top: 20px;
}

.sidebar .lang-switch a {
    display: inline-flex;
    margin: 0 5px;
    padding: 8px 16px;
    border-radius: 8px;
    background: rgba(255,255,255,0.1);
    font-size: 13px;
    justify-content: center;
}

.sidebar .lang-switch a:hover,
.sidebar .lang-switch a.active {
    background: var(--secondary-green);
    transform: scale(1.05);
}

.logout-btn {
    width: 100%;
    margin-top: 20px;
    padding: 14px;
    border: none;
    border-radius: 12px;
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    box-shadow: var(--shadow-sm);
}

.logout-btn:hover {
    background: linear-gradient(135deg, #c0392b, #a93226);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

/* Main Content */
.main {
    flex: 1;
    padding: 40px;
    overflow-y: auto;
    animation: fadeIn 0.6s ease-out;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

/* Header */
.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
    padding: 30px;
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    animation: slideInDown 0.6s ease-out;
}

@keyframes slideInDown {
    from {
        opacity: 0;
        transform: translateY(-30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.header h1 {
    font-size: 2rem;
    font-weight: 800;
    color: var(--primary-green-dark);
    letter-spacing: -0.02em;
}

.header .welcome {
    font-size: 1rem;
    color: var(--text-light);
    background: var(--bg-light);
    padding: 8px 16px;
    border-radius: 20px;
}

/* Dashboard Cards */
.cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.card {
    background: white;
    padding: 30px;
    border-radius: 16px;
    text-align: center;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    border: 2px solid transparent;
    cursor: pointer;
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(30px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
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
    font-size: 3rem;
    color: var(--primary-green);
    margin-bottom: 16px;
    transition: var(--transition);
}

.card:hover i {
    transform: scale(1.2) rotate(5deg);
    color: var(--secondary-green);
}

.card h3 {
    font-size: 1.1rem;
    font-weight: 700;
    color: var(--text-dark);
    margin-top: 12px;
}

/* Sections */
.section {
    margin-bottom: 40px;
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    animation: fadeInUp 0.6s ease-out;
}

.section h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 24px;
    padding-bottom: 16px;
    border-bottom: 3px solid var(--bg-light);
    position: relative;
}

.section h2::after {
    content: "";
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--primary-green);
    animation: expandWidth 0.6s ease-out;
}

@keyframes expandWidth {
    from { width: 0; }
    to { width: 60px; }
}

/* Lists */
.crop-list,
.notification-list,
.knowledge-list {
    list-style: none;
}

.crop-list li,
.notification-list li,
.knowledge-list li {
    padding: 16px 20px;
    margin-bottom: 12px;
    background: var(--bg-light);
    border-radius: 12px;
    transition: var(--transition);
    border-left: 4px solid transparent;
    animation: slideInRight 0.4s ease-out;
    animation-fill-mode: both;
}

.crop-list li:nth-child(1) { animation-delay: 0.1s; }
.crop-list li:nth-child(2) { animation-delay: 0.2s; }
.crop-list li:nth-child(3) { animation-delay: 0.3s; }
.crop-list li:nth-child(4) { animation-delay: 0.4s; }
.crop-list li:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.crop-list li:hover,
.knowledge-list li:hover {
    background: #e8f5e9;
    border-left-color: var(--primary-green);
    transform: translateX(5px);
    box-shadow: var(--shadow-sm);
}

/* Notifications */
.notification-list li.unread {
    background: #fff3cd;
    border-left-color: var(--accent-yellow);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4); }
    50% { box-shadow: 0 0 0 8px rgba(255, 193, 7, 0); }
}

.notification-list li.unread:hover {
    background: #ffe69c;
}

.mark-btn {
    margin-left: 12px;
    padding: 6px 14px;
    border: none;
    border-radius: 8px;
    background: var(--secondary-green);
    color: white;
    cursor: pointer;
    font-size: 12px;
    font-weight: 600;
    transition: var(--transition);
}

.mark-btn:hover {
    background: var(--primary-green);
    transform: scale(1.05);
}

/* Buttons */
.btn-add-crop,
.btn-update-stage {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 14px 24px;
    margin: 0 12px 16px 0;
    font-weight: 600;
    font-size: 15px;
    text-decoration: none;
    color: white;
    border-radius: 12px;
    border: none;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
    position: relative;
    overflow: hidden;
}

.btn-add-crop {
    background: linear-gradient(135deg, var(--secondary-green), var(--primary-green));
}

.btn-add-crop:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

.btn-add-crop::before {
    content: "";
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255,255,255,0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

.btn-add-crop:hover::before {
    width: 300px;
    height: 300px;
}

.btn-update-stage {
    background: linear-gradient(135deg, var(--accent-orange), #ff6b00);
}

.btn-update-stage:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}

/* Knowledge Base Buttons */
.kb-buttons {
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
    margin-top: 30px;
}

.kb-btn {
    display: inline-flex;
    align-items: center;
    gap: 12px;
    padding: 20px 40px;
    font-size: 1.1rem;
    font-weight: 700;
    text-decoration: none;
    color: white;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    animation: floatBtn 3s ease-in-out infinite;
}

@keyframes floatBtn {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-10px); }
}

.kb-btn::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(255,255,255,0.3) 0%, transparent 70%);
    transform: scale(0);
    transition: transform 0.6s;
}

.kb-btn:hover::before {
    transform: scale(1);
}

.kb-btn:hover {
    transform: translateY(-8px) scale(1.05);
    box-shadow: var(--shadow-lg);
}

.kb-icon {
    font-size: 1.5rem;
    animation: rotate 3s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.tomato-btn {
    background: linear-gradient(135deg, #ff4c4c, #ff7f50);
}

.groundnut-btn {
    background: linear-gradient(135deg, #ffca28, #ffb74d);
}

/* Weather Card */
#weatherBox {
    background: white;
    padding: 30px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    text-align: center;
    animation: fadeIn 1s ease-out;
}

#weatherBox img {
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-10px); }
}

/* Disease Recognition Button */
.disease-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    font-weight: 600;
    font-size: 16px;
    text-decoration: none;
    color: white;
    border-radius: 12px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    box-shadow: var(--shadow-md);
    transition: var(--transition);
    border: none;
    cursor: pointer;
}

.disease-btn:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

/* Profile Modal */
.profile-modal {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.5);
    backdrop-filter: blur(5px);
    z-index: 1000;
    align-items: center;
    justify-content: center;
    animation: fadeIn 0.3s ease-out;
}

.profile-modal.active {
    display: flex;
}

.profile-modal-content {
    background: white;
    border-radius: 20px;
    padding: 40px;
    max-width: 600px;
    width: 90%;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: var(--shadow-lg);
    position: relative;
    animation: slideUp 0.4s ease-out;
}

@keyframes slideUp {
    from {
        opacity: 0;
        transform: translateY(50px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.profile-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 30px;
    padding-bottom: 20px;
    border-bottom: 3px solid var(--bg-light);
    position: relative;
}

.profile-modal-header h2 {
    font-size: 1.8rem;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin: 0;
    display: flex;
    align-items: center;
    gap: 12px;
}

.profile-modal-header::after {
    content: "";
    position: absolute;
    bottom: -3px;
    left: 0;
    width: 60px;
    height: 3px;
    background: var(--primary-green);
}

.close-modal {
    background: var(--bg-light);
    border: none;
    width: 40px;
    height: 40px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: var(--transition);
    color: var(--text-dark);
    font-size: 20px;
}

.close-modal:hover {
    background: var(--error-color);
    color: white;
    transform: rotate(90deg);
}

.profile-modal-body {
    margin-top: 20px;
}

/* Responsive Design */
@media (max-width: 1024px) {
    .sidebar {
        width: 240px;
    }
}

@media (max-width: 768px) {
    /* Show hamburger menu on mobile */
    .menu-toggle {
        display: flex;
    }

    /* Show close button in sidebar on mobile */
    .sidebar-close {
        display: flex !important;
    }

    .sidebar-close:hover {
        background: rgba(255,255,255,0.3) !important;
        transform: rotate(90deg);
    }

    /* Hide sidebar by default on mobile */
    .sidebar {
        position: fixed;
        left: -280px;
        top: 0;
        width: 280px;
        height: 100vh;
        transition: left 0.3s ease-out;
        z-index: 1000;
        background: var(--sidebar-bg) !important;
    }

    /* Show sidebar when active */
    .sidebar.active {
        left: 0;
        z-index: 1000;
    }

    /* Prevent body scroll when sidebar is open */
    body.sidebar-open {
        overflow: hidden;
    }

    .main {
        padding: 20px;
        padding-top: 80px; /* Space for hamburger menu */
    }

    .header {
        flex-direction: column;
        align-items: flex-start;
        gap: 12px;
        margin-top: 20px;
    }

    .cards {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    .main {
        padding: 16px;
    }

    .section {
        padding: 20px;
    }

    .kb-buttons {
        flex-direction: column;
    }

    .kb-btn {
        width: 100%;
    }
}
</style>
</head>
<body>

<!-- Floating Background Icons -->
<i class="fas fa-leaf icon-bg"></i>
<i class="fas fa-seedling icon-bg"></i>
<i class="fas fa-tractor icon-bg"></i>
<i class="fas fa-water icon-bg"></i>
<i class="fas fa-sun icon-bg"></i>

<!-- Hamburger Menu Button (Mobile Only) -->
<button class="menu-toggle" id="menuToggle" onclick="toggleSidebar()">
    <i class="fas fa-bars" id="menuIcon"></i>
</button>

<!-- Sidebar Overlay (Mobile Only) -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="closeSidebar()"></div>

<div class="dashboard">

<!-- Sidebar -->
<div class="sidebar" id="sidebar">
    <!-- Close Button (Mobile Only) -->
    <button class="sidebar-close" onclick="closeSidebar()" style="display: none; position: absolute; top: 20px; right: 20px; background: rgba(255,255,255,0.2); border: none; color: white; width: 40px; height: 40px; border-radius: 50%; cursor: pointer; align-items: center; justify-content: center; transition: var(--transition);">
        <i class="fas fa-times"></i>
    </button>
    <h2><i class="fas fa-tractor"></i> <?php echo $translations['farmer_dashboard'] ?? 'Farmer Dashboard'; ?></h2>

    <nav>
        <a href="#" onclick="openProfileModal(); return false;"><i class="fas fa-user"></i> <?php echo $translations['profile']; ?></a>
        <a href="#crops"><i class="fas fa-seedling"></i> <?php echo $translations['crop_management']; ?></a>
        <a href="knowledge_base.php"><i class="fas fa-book"></i> <?php echo $translations['knowledge_base']; ?></a>
        <a href="#notifications"><i class="fas fa-bell"></i> <?php echo $translations['notifications']; ?></a>
        <a href="farmer_reports.php"><i class="fas fa-chart-line"></i> <?php echo $translations['reports']; ?></a>
        <a href="#weather"><i class="fas fa-cloud-sun"></i> <?php echo $translations['weather']; ?></a>
    </nav>

    <div>
        <div class="lang-switch">
            <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
            <a href="?lang=te" class="<?php echo $lang === 'te' ? 'active' : ''; ?>">తెలుగు</a>
        </div>

        <form action="logout.php" method="POST">
            <button type="submit" class="logout-btn" onclick="closeSidebar()">
                <i class="fas fa-sign-out-alt"></i>
                <?php echo $translations['logout']; ?>
            </button>
        </form>
    </div>
</div>

<!-- MAIN CONTENT -->
<div class="main">

<!-- Header -->
<div class="header">
    <div>
        <h1><?php echo $translations['hello']; ?>, <?php echo htmlspecialchars($user['fullname']); ?>! 👋</h1>
        <div class="welcome">
            <i class="fas fa-map-marker-alt"></i>
            <?php echo $translations['district']; ?>: <?php echo htmlspecialchars($user['district']); ?>
        </div>
    </div>
</div>

<!-- Dashboard Cards -->
<div class="cards">
    <div class="card" onclick="document.querySelector('#crops').scrollIntoView({behavior: 'smooth'})">
        <i class="fas fa-seedling"></i>
        <h3><?php echo $translations['crop_management']; ?></h3>
    </div>
    <div class="card" onclick="window.location.href='knowledge_base.php'">
        <i class="fas fa-book"></i>
        <h3><?php echo $translations['knowledge_base']; ?></h3>
    </div>
    <div class="card" onclick="document.querySelector('#notifications').scrollIntoView({behavior: 'smooth'})">
        <i class="fas fa-bell"></i>
        <h3><?php echo $translations['notifications']; ?></h3>
    </div>
    <div class="card" onclick="window.location.href='farmer_reports.php'">
        <i class="fas fa-chart-line"></i>
        <h3><?php echo $translations['reports']; ?></h3>
    </div>
</div>

<!-- Profile Modal -->
<div class="profile-modal" id="profileModal">
    <div class="profile-modal-content">
        <div class="profile-modal-header">
            <h2><i class="fas fa-user"></i> <?php echo $translations['profile']; ?></h2>
            <button class="close-modal" onclick="closeProfileModal()">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div class="profile-modal-body">
            <ul class="crop-list">
                <li>
                    <i class="fas fa-user-circle" style="color: var(--primary-green); margin-right: 10px;"></i>
                    <strong><?php echo $translations['fullname']; ?>:</strong> <?php echo htmlspecialchars($user['fullname']); ?>
                </li>
                <li>
                    <i class="fas fa-envelope" style="color: var(--primary-green); margin-right: 10px;"></i>
                    <strong><?php echo $translations['email']; ?>:</strong> <?php echo htmlspecialchars($user['email']); ?>
                </li>
                <li>
                    <i class="fas fa-phone" style="color: var(--primary-green); margin-right: 10px;"></i>
                    <strong><?php echo $translations['mobile']; ?>:</strong> <?php echo htmlspecialchars($user['mobile']); ?>
                </li>
                <li>
                    <i class="fas fa-map-marker-alt" style="color: var(--primary-green); margin-right: 10px;"></i>
                    <strong><?php echo $translations['district']; ?>:</strong> <?php echo htmlspecialchars($user['district']); ?>
                </li>
                <li>
                    <i class="fas fa-globe" style="color: var(--primary-green); margin-right: 10px;"></i>
                    <strong><?php echo $translations['state']; ?>:</strong> <?php echo htmlspecialchars($user['state'] ?? 'N/A'); ?>
                </li>
            </ul>
        </div>
    </div>
</div>

<!-- Crops Section -->
<div class="section" id="crops">
    <h2><i class="fas fa-seedling"></i> <?php echo $translations['crop_management']; ?></h2>
    <div style="margin-bottom: 24px;">
        <a href="add_crop.php" class="btn-add-crop">
            <i class="fas fa-plus-circle"></i>
            Add New Crop
        </a>
        <a href="update_stage.php" class="btn-update-stage">
            <i class="fas fa-sync-alt"></i>
            Update Crop Stage
        </a>
    </div>
    <ul class="crop-list">
        <?php 
        if(count($farmer_crops) > 0){ 
            foreach($farmer_crops as $c){ 
                echo "<li>
                        <i class='fas fa-seedling' style='color: var(--primary-green); margin-right: 10px;'></i>
                        <strong>" . htmlspecialchars($c['crop_name']) . "</strong> 
                        <span style='color: var(--text-light); margin-left: 10px;'>
                            (Stage: " . htmlspecialchars($c['growth_stage']) . ")
                        </span>
                      </li>"; 
            } 
        } else {
            echo "<li style='text-align: center; color: var(--text-light);'>
                    <i class='fas fa-info-circle' style='margin-right: 8px;'></i>
                    No crops found. Add your first crop to get started!
                  </li>";
        }
        ?>
    </ul>
</div>

<!-- Crop Disease Recognition Section -->
<div class="section" id="disease-recognition">
    <h2><i class="fas fa-microscope"></i> Crop Disease Recognition</h2>
    <p style="color: var(--text-light); margin-bottom: 20px;">
        Use this AI-powered tool to identify potential diseases in your crops by uploading leaf images. 
        Get instant diagnosis and treatment recommendations.
    </p>
    <button onclick="window.location.href='http://127.0.0.1:5000'" class="disease-btn">
        <i class="fas fa-camera"></i>
        Recognize Disease
    </button>
</div>

<!-- Knowledge Base Section -->
<div class="section" id="knowledge">
    <h2><i class="fas fa-book"></i> <?php echo $translations['knowledge_base']; ?></h2>
    <p style="color: var(--text-light); margin-bottom: 24px; text-align: center;">
        Access comprehensive guides and expert advice for your crops
    </p>
    <div class="kb-buttons">
        <!-- Tomato Button -->
        <a href="knowledge_base.php?crop=tomato" class="kb-btn tomato-btn">
            <span>🍅</span>
            <span>Tomato Guide</span>
            <span class="kb-icon">🌿</span>
        </a>

        <!-- Groundnut Button -->
        <a href="knowledge_base.php?crop=groundnut" class="kb-btn groundnut-btn">
            <span>🥜</span>
            <span>Groundnut Guide</span>
            <span class="kb-icon">🌱</span>
        </a>
    </div>
</div>

<!-- Notifications Section -->
<div class="section" id="notifications">
    <h2><i class="fas fa-bell"></i> <?php echo $translations['notifications']; ?></h2>
    <ul class="notification-list">
        <?php 
        if(count($notifications) > 0){
            foreach($notifications as $n){
                $d = date("d M Y", strtotime($n['notify_date']));
                $statusClass = ($n['status'] == 'unread') ? 'unread' : 'read';
                echo "<li class='$statusClass' data-id='{$n['id']}'>
                        <i class='fas fa-info-circle' style='color: var(--primary-green); margin-right: 10px;'></i>
                        <strong>[{$d}]</strong> " . htmlspecialchars($n['message']) . " 
                        <span style='color: var(--text-light);'>({$n['crop_name']})</span>";
                if($n['status'] == 'unread'){
                    echo " <button class='mark-btn' data-id='{$n['id']}'>
                            <i class='fas fa-check'></i> Mark as Read
                           </button>";
                }
                echo "</li>";
            }
        } else {
            echo "<li style='text-align: center; color: var(--text-light);'>
                    <i class='fas fa-bell-slash' style='margin-right: 8px;'></i>
                    No notifications at the moment.
                  </li>";
        }
        ?>
    </ul>
</div>

<!-- Weather Section -->
<div class="section" id="weather">
    <h2><i class="fas fa-cloud-sun"></i> <?php echo $translations['weather']; ?></h2>
    <div id="weatherBox">
        <p style="color: var(--text-light);">
            <i class="fas fa-spinner fa-spin"></i>
            <?php echo ($lang=='te')
                ? 'మీ ప్రస్తుత స్థలానికి వాతావరణం పొందుతోంది...'
                : 'Fetching weather for your current location...';
            ?>
        </p>
    </div>
</div>
<script>
/* ================= WEATHER (AUTO LOCATION) ================= */
document.addEventListener("DOMContentLoaded", function () {

    const apiKey = "8939754260ab572788b1c798b4e89406";
    const weatherBox = document.getElementById("weatherBox");

    if (!weatherBox) return;

    if (!navigator.geolocation) {
        weatherBox.innerHTML = `
            <p style="color: var(--text-light);">
                <i class="fas fa-exclamation-triangle" style="color: var(--accent-orange); margin-right: 8px;"></i>
                Geolocation not supported by your browser.
            </p>
        `;
        return;
    }

    navigator.geolocation.getCurrentPosition(
        successLocation,
        errorLocation,
        { enableHighAccuracy: true, timeout: 10000 }
    );

    function successLocation(position) {
        const lat = position.coords.latitude;
        const lon = position.coords.longitude;

        fetch(`https://api.openweathermap.org/data/2.5/weather?lat=${lat}&lon=${lon}&appid=${apiKey}&units=metric`)
            .then(res => res.json())
            .then(data => {
                if (data.cod !== 200) {
                    weatherBox.innerHTML = `
                        <p style="color: var(--text-light);">
                            <i class="fas fa-exclamation-circle" style="color: var(--accent-orange); margin-right: 8px;"></i>
                            Weather data unavailable at the moment.
                        </p>
                    `;
                    return;
                }

                weatherBox.innerHTML = `
                    <div style="margin-bottom: 20px;">
                        <img src="https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png" alt="Weather Icon" style="width: 80px; height: 80px;">
                    </div>
                    <h3 style="font-size: 1.5rem; font-weight: 700; color: var(--primary-green-dark); margin-bottom: 20px; text-transform: capitalize;">
                        ${data.weather[0].description}
                    </h3>
                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 16px; margin-top: 24px;">
                        <div style="padding: 16px; background: var(--bg-light); border-radius: 12px;">
                            <i class="fas fa-thermometer-half" style="color: var(--accent-orange); font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-light);">Temperature</p>
                            <p style="margin: 4px 0 0 0; font-size: 1.3rem; font-weight: 700; color: var(--text-dark);">${data.main.temp}°C</p>
                        </div>
                        <div style="padding: 16px; background: var(--bg-light); border-radius: 12px;">
                            <i class="fas fa-tint" style="color: #3498db; font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-light);">Humidity</p>
                            <p style="margin: 4px 0 0 0; font-size: 1.3rem; font-weight: 700; color: var(--text-dark);">${data.main.humidity}%</p>
                        </div>
                        <div style="padding: 16px; background: var(--bg-light); border-radius: 12px;">
                            <i class="fas fa-wind" style="color: var(--primary-green); font-size: 1.5rem; margin-bottom: 8px;"></i>
                            <p style="margin: 0; font-size: 0.9rem; color: var(--text-light);">Wind Speed</p>
                            <p style="margin: 4px 0 0 0; font-size: 1.3rem; font-weight: 700; color: var(--text-dark);">${data.wind.speed} m/s</p>
                        </div>
                    </div>
                    <p style="margin-top: 20px; color: var(--text-light); font-size: 0.9rem;">
                        <i class="fas fa-map-marker-alt" style="color: var(--primary-green);"></i>
                        ${data.name || 'Your Location'}
                    </p>
                `;
            })
            .catch(() => {
                weatherBox.innerHTML = `
                    <p style="color: var(--text-light);">
                        <i class="fas fa-exclamation-circle" style="color: var(--accent-orange); margin-right: 8px;"></i>
                        Unable to load weather data. Please try again later.
                    </p>
                `;
            });
    }

    function errorLocation() {
        weatherBox.innerHTML = `
            <p style="color: var(--text-light);">
                <i class="fas fa-map-marker-alt" style="color: var(--accent-orange); margin-right: 8px;"></i>
                Location permission denied. Please enable location access to view weather.
            </p>
        `;
    }
});
</script>

<script>
/* ================= NOTIFICATIONS ================= */
function attachMarkBtnEvents() {
    document.querySelectorAll('.mark-btn').forEach(btn => {
        btn.addEventListener('click', function () {

            const notifId = this.dataset.id;
            const li = this.closest('li');

            fetch('mark_notification.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'id=' + encodeURIComponent(notifId)
            })
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success') {
                    li.classList.remove('unread');
                    li.classList.add('read');
                    this.remove();
                }
            });
        });
    });
}

// Initial attach
attachMarkBtnEvents();

// Polling
setInterval(() => {
    fetch('fetch_notifications.php')
        .then(res => res.text())
        .then(html => {
            const ul = document.querySelector('.notification-list');
            if (ul) {
                ul.innerHTML = html;
                attachMarkBtnEvents();
            }
        });
}, 30000);
</script>

<script>
/* ================= PROFILE MODAL ================= */
function openProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.add('active');
        document.body.style.overflow = 'hidden'; // Prevent background scrolling
    }
}

function closeProfileModal() {
    const modal = document.getElementById('profileModal');
    if (modal) {
        modal.classList.remove('active');
        document.body.style.overflow = 'auto'; // Restore scrolling
    }
}

// Close modal when clicking outside the modal content
document.addEventListener('click', function(event) {
    const modal = document.getElementById('profileModal');
    if (modal && event.target === modal) {
        closeProfileModal();
    }
});

// Close modal with Escape key
document.addEventListener('keydown', function(event) {
    if (event.key === 'Escape') {
        closeProfileModal();
        closeSidebar(); // Also close sidebar if open
    }
});
</script>

<script>
/* ================= MOBILE SIDEBAR TOGGLE ================= */
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuIcon = document.getElementById('menuIcon');
    const body = document.body;

    if (sidebar && overlay) {
        sidebar.classList.toggle('active');
        overlay.classList.toggle('active');
        body.classList.toggle('sidebar-open');

        // Change icon between hamburger and X
        if (menuIcon) {
            if (sidebar.classList.contains('active')) {
                menuIcon.classList.remove('fa-bars');
                menuIcon.classList.add('fa-times');
            } else {
                menuIcon.classList.remove('fa-times');
                menuIcon.classList.add('fa-bars');
            }
        }
    }
}

function closeSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const overlay = document.getElementById('sidebarOverlay');
    const menuIcon = document.getElementById('menuIcon');
    const body = document.body;

    if (sidebar && overlay) {
        sidebar.classList.remove('active');
        overlay.classList.remove('active');
        body.classList.remove('sidebar-open');

        // Change icon back to hamburger
        if (menuIcon) {
            menuIcon.classList.remove('fa-times');
            menuIcon.classList.add('fa-bars');
        }
    }
}

// Close sidebar when clicking on a sidebar link (mobile)
document.addEventListener('DOMContentLoaded', function() {
    const sidebarLinks = document.querySelectorAll('.sidebar a');
    sidebarLinks.forEach(link => {
        link.addEventListener('click', function() {
            // Only close on mobile (screen width <= 768px)
            if (window.innerWidth <= 768) {
                closeSidebar();
            }
        });
    });
});

// Close sidebar on window resize if it becomes desktop view
window.addEventListener('resize', function() {
    if (window.innerWidth > 768) {
        closeSidebar();
    }
});
</script>
</div>
</div>

</body>
</html>
