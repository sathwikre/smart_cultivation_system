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

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>


<style>
/* General Styles */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{
    overflow-x:hidden;
    background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);
    background-size:400% 400%;
    animation: gradientBG 20s ease infinite;
    color:#fff;
}
@keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}

/* Floating Background Icons */
.icon-bg{position:fixed;font-size:2.5rem;color:rgba(255,255,255,0.15);animation: floatBg 20s linear infinite;z-index:-1;}
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

/* Layout */
.dashboard{display:flex; min-height:100vh;}
.sidebar{
    width:250px; background: rgba(0,0,0,0.5);
    backdrop-filter: blur(10px);
    padding:20px; display:flex; flex-direction:column;
    justify-content:space-between;
    border-right:1px solid rgba(255,255,255,0.2);
}
.sidebar h2{font-size:1.8rem; margin-bottom:30px; text-align:center;}
.sidebar a{
    color:#fff; text-decoration:none; padding:12px 15px;
    border-radius:12px; margin:6px 0; display:block; transition:0.3s;
}
.sidebar a:hover{background: rgba(255,255,255,0.2);}
.sidebar .lang-switch{text-align:center;}
.sidebar .lang-switch a{
    margin:0 5px; padding:6px 12px; border-radius:20px;
    background: rgba(255,255,255,0.2); transition:0.3s;
}
.sidebar .lang-switch a:hover{background: rgba(255,255,255,0.4);}
.logout-btn{
    display:inline-block; margin-top:20px; padding:10px 25px;
    border:none; border-radius:25px; background:#ff4c4c;
    color:#fff; font-weight:600;
    cursor:pointer; transition:0.3s;
}
.logout-btn:hover{background:#ff1c1c; transform:scale(1.05);}

/* Main Content */
.main{flex:1; padding:30px; overflow-y:auto;}
.header{display:flex; justify-content:space-between; align-items:center; margin-bottom:30px;}
.header h1{font-size:2.5rem; font-weight:700;}
.header .welcome{font-size:1.2rem; color:rgba(255,255,255,0.8);}

.cards{
    display:grid; grid-template-columns: repeat(auto-fit,minmax(220px,1fr));
    gap:20px; margin-bottom:30px;
}
.card{
    background: rgba(255,255,255,0.1); padding:25px; border-radius:20px;
    backdrop-filter: blur(10px); text-align:center;
    box-shadow:0 10px 30px rgba(0,0,0,0.4);
    transition:0.3s;
}
.card:hover{transform: translateY(-5px) scale(1.02);}

.section{margin-bottom:30px;}
.section h2{
    font-size:1.8rem; margin-bottom:15px;
    border-bottom:1px solid rgba(255,255,255,0.3); padding-bottom:5px;
}

.crop-list li, .notification-list li, .knowledge-list li{
    padding:12px 15px; margin-bottom:10px;
    background: rgba(255,255,255,0.1); border-radius:12px;
    transition:0.3s;
}
.crop-list li:hover, .knowledge-list li:hover{
    background: rgba(255,255,255,0.2);
}

/* Unread Notifications */
.notification-list li.unread{
    background: rgba(255,255,0,0.3);
}
.notification-list li.unread:hover{
    background: rgba(255,255,0,0.5);
}
.notification-list li button.mark-btn{
    margin-left:10px; padding:5px 10px; border:none; border-radius:8px;
    background:#28a745; color:#fff; cursor:pointer;
}
.notification-list li button.mark-btn:hover{
    background:#1e7e34;
}

/* Weather Card */
@keyframes fadeIn {0% {opacity:0; transform:translateY(20px);}100% {opacity:1; transform:translateY(0);}}
.btn-add-crop {display: inline-block; padding: 10px 20px; margin-bottom: 15px; font-weight: 600; font-size: 1rem; text-decoration: none; color: #fff; border-radius: 12px; background: linear-gradient(135deg, #28a745, #00ff7f); box-shadow: 0 4px 15px rgba(0,255,127,0.6); transition: 0.3s ease-in-out;}
.btn-add-crop:hover {background: linear-gradient(135deg, #00ff7f, #28a745); box-shadow: 0 0 20px rgba(0,255,127,0.9); transform: scale(1.05);}
.btn-update-stage {display: inline-block; padding: 10px 20px; margin-bottom: 15px; font-weight: 600; font-size: 1rem; text-decoration: none; color: #fff; border-radius: 12px; background: linear-gradient(135deg, #ffb100, #ff6b00); box-shadow: 0 4px 15px rgba(255,177,0,0.6); transition: 0.3s ease-in-out;}
.btn-update-stage:hover {background: linear-gradient(135deg, #ff6b00, #ffb100); box-shadow: 0 0 20px rgba(255,177,0,0.9); transform: scale(1.05);}
.kb-buttons {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
    margin-top: 30px;
}

.kb-btn {
    display: inline-block;
    padding: 18px 35px;
    font-size: 1.2rem;
    font-weight: 700;
    text-decoration: none;
    color: #fff;
    border-radius: 20px;
    box-shadow: 0 6px 20px rgba(0,0,0,0.3);
    position: relative;
    transition: transform 0.3s ease, box-shadow 0.3s ease;
    animation: floatBtn 3s ease-in-out infinite alternate;
}

.kb-btn .kb-icon {
    position: absolute;
    top: -10px;
    right: -10px;
    font-size: 1.5rem;
}

/* Tomato Button Gradient Animation */
.tomato-btn {
    background: linear-gradient(135deg, #ff4c4c, #ff7f50);
    animation: floatBtn 3s ease-in-out infinite alternate, gradientTomato 5s ease-in-out infinite alternate;
}

@keyframes gradientTomato {
    0% { background: linear-gradient(135deg, #ff4c4c, #ff7f50); }
    50% { background: linear-gradient(135deg, #ff6b6b, #ff906b); }
    100% { background: linear-gradient(135deg, #ff4c4c, #ff7f50); }
}

/* Groundnut Button Gradient Animation */
.groundnut-btn {
    background: linear-gradient(135deg, #ffca28, #ffb74d);
    animation: floatBtn 3s ease-in-out infinite alternate, gradientGroundnut 5s ease-in-out infinite alternate;
}

@keyframes gradientGroundnut {
    0% { background: linear-gradient(135deg, #ffca28, #ffb74d); }
    50% { background: linear-gradient(135deg, #ffd54f, #ffc107); }
    100% { background: linear-gradient(135deg, #ffca28, #ffb74d); }
}

/* Floating Animation */
@keyframes floatBtn {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
    100% { transform: translateY(0px); }
}

/* Hover Effect */
.kb-btn:hover {
    transform: scale(1.08) translateY(-5px);
    box-shadow: 0 10px 30px rgba(0,0,0,0.5);
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

<div class="dashboard">

<!-- Sidebar -->
<div class="sidebar">
    <h2><?php echo $translations['farmer_dashboard'] ?? 'Farmer Dashboard'; ?></h2>

    <a href="#profile"><i class="fas fa-user"></i> <?php echo $translations['profile']; ?></a>
    <a href="#crops"><i class="fas fa-seedling"></i> <?php echo $translations['crop_management']; ?></a>
    <a href="knowledge_base.php" class="sidebar-link"><i class="fas fa-book"></i> <?php echo $translations['knowledge_base']; ?></a>

    <a href="#notifications"><i class="fas fa-bell"></i> <?php echo $translations['notifications']; ?></a>
    <a href="farmer_reports.php"><i class="fas fa-chart-line"></i> <?php echo $translations['reports']; ?></a>
    <a href="#weather"><i class="fas fa-cloud-sun"></i> <?php echo $translations['weather']; ?></a>

    <div class="lang-switch">
        <a href="?lang=en">English</a> |
        <a href="?lang=te">తెలుగు</a>
    </div>

    <form action="logout.php" method="POST">
        <button type="submit" class="logout-btn"><?php echo $translations['logout']; ?></button>
    </form>
</div>

<!-- MAIN CONTENT -->
<div class="main">

<!-- Header -->
<div class="header">
    <h1><?php echo $translations['hello']; ?>, <?php echo $user['fullname']; ?></h1>
    <div class="welcome"><?php echo $translations['district']; ?>: <?php echo $user['district']; ?></div>
</div>

<!-- Dashboard Cards -->
<div class="cards">
    <div class="card"><i class="fas fa-seedling"></i><h3><?php echo $translations['crop_management']; ?></h3></div>
    <div class="card" onclick="window.location.href='knowledge_base.php'" style="cursor:pointer;">
    <i class="fas fa-book"></i>
    <h3><?php echo $translations['knowledge_base']; ?></h3>
</div>

    <div class="card"><i class="fas fa-bell"></i><h3><?php echo $translations['notifications']; ?></h3></div>
    <div class="card" onclick="window.location.href='farmer_reports.php'" style="cursor:pointer;"><i class="fas fa-chart-line"></i><h3><?php echo $translations['reports']; ?></h3></div>
</div>

<!-- Profile Section -->
<div class="section" id="profile">
    <h2><?php echo $translations['profile']; ?></h2>
    <ul class="crop-list">
        <li><b><?php echo $translations['fullname']; ?>:</b> <?php echo $user['fullname']; ?></li>
        <li><b><?php echo $translations['email']; ?>:</b> <?php echo $user['email']; ?></li>
        <li><b><?php echo $translations['mobile']; ?>:</b> <?php echo $user['mobile']; ?></li>
        <li><b><?php echo $translations['district']; ?>:</b> <?php echo $user['district']; ?></li>
        <li><b><?php echo $translations['state']; ?>:</b> <?php echo $user['state']; ?></li>
    </ul>
</div>

<!-- Crops Section -->
<div class="section" id="crops">
    <h2><?php echo $translations['crop_management']; ?></h2>
    <a href="add_crop.php" class="btn-add-crop">➕ Add New Crop</a>
    <a href="update_stage.php" class="btn-update-stage">🔄 Update Crop Stage</a>
    <ul class="crop-list">
        <?php 
        if(count($farmer_crops) > 0){ 
            foreach($farmer_crops as $c){ 
                echo "<li>{$c['crop_name']} (Stage: {$c['growth_stage']})</li>"; 
            } 
        } else echo "<li>No crops found.</li>";
        ?>
    </ul>
</div>

<!-- Knowledge Base Section -->
<div class="section" id="knowledge">
    <h2><?php echo $translations['knowledge_base']; ?></h2>
    <div class="kb-buttons">
        <!-- Tomato Button -->
        <a href="knowledge_base.php?crop=tomato" class="kb-btn tomato-btn">
            🍅 Tomato Guide
            <span class="kb-icon">🌿</span>
        </a>

        <!-- Groundnut Button -->
        <a href="knowledge_base.php?crop=groundnut" class="kb-btn groundnut-btn">
            🥜 Groundnut Guide
            <span class="kb-icon">🌱</span>
        </a>
    </div>
</div>

<!-- Notifications Section -->
<div class="section" id="notifications">
    <h2><?php echo $translations['notifications']; ?></h2>
    <ul class="notification-list">
        <?php 
        if(count($notifications)>0){
            foreach($notifications as $n){
                $d = date("d M Y", strtotime($n['notify_date']));
                $statusClass = ($n['status'] == 'unread') ? 'unread' : 'read';
                echo "<li class='$statusClass' data-id='{$n['id']}'>
                        [{$d}] {$n['message']} ({$n['crop_name']})";
                if($n['status'] == 'unread'){
                    echo " <button class='mark-btn' data-id='{$n['id']}'>Mark as Read</button>";
                }
                echo "</li>";
            }
        } else echo "<li>No notifications.</li>"; 
        ?>
    </ul>
</div>

<!-- Weather Section -->
<div class="section" id="weather">
    <h2><?php echo $translations['weather']; ?></h2>

    <div id="weatherBox"
         style="background: rgba(255,255,255,0.15);
                padding: 25px;
                border-radius: 20px;
                backdrop-filter: blur(12px);
                box-shadow: 0 10px 30px rgba(0,0,0,0.4);
                text-align: center;">
        <p>
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
        weatherBox.innerHTML = "<p>Geolocation not supported.</p>";
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
                    weatherBox.innerHTML = "<p>Weather data unavailable.</p>";
                    return;
                }

                weatherBox.innerHTML = `
                    <img src="https://openweathermap.org/img/wn/${data.weather[0].icon}@2x.png">
                    <h3>${data.weather[0].description.toUpperCase()}</h3>
                    <p>🌡 Temperature: <b>${data.main.temp}°C</b></p>
                    <p>💧 Humidity: <b>${data.main.humidity}%</b></p>
                    <p>🌬 Wind Speed: <b>${data.wind.speed} m/s</b></p>
                    <p style="opacity:0.8;">📍 ${data.name || 'Your Location'}</p>
                `;
            })
            .catch(() => {
                weatherBox.innerHTML = "<p>Unable to load weather.</p>";
            });
    }

    function errorLocation() {
        weatherBox.innerHTML = "<p>Location permission denied.</p>";
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
</div>
</div>

</body>
</html>
