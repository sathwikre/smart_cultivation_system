<?php
session_start();
require 'db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'farmer'){
    header("Location: login.php");
    exit;
}

// Fetch user
$stmt = $conn->prepare("SELECT * FROM users WHERE id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$user = $stmt->get_result()->fetch_assoc();
$stmt->close();

// Fetch farmer crops
$crops_stmt = $conn->prepare("SELECT id, crop_name, growth_stage, last_updated FROM farmer_crops WHERE user_id=?");
$crops_stmt->bind_param("i", $_SESSION['user_id']);
$crops_stmt->execute();
$crops_result = $crops_stmt->get_result();
$farmer_crops = [];
while($row = $crops_result->fetch_assoc()){
    $farmer_crops[] = $row;
}
$crops_stmt->close();

// Map growth stages to numeric progress (adjust mapping as you want)
$stage_map = [
    'Seed' => 10,
    'Seedling' => 20,
    'Vegetative' => 40,
    'Flowering' => 70,
    'Fruiting' => 85,
    'Fruit' => 85,
    'Harvest' => 100,
    'Mature' => 95
];

// Prepare growth chart data
$growth_labels = [];
$growth_values = [];
foreach($farmer_crops as $c){
    $growth_labels[] = $c['crop_name'];
    $stage = $c['growth_stage'];
    $val = $stage_map[$stage] ?? 30; // default if missing
    $growth_values[] = (int)$val;
}

// Notifications trend - last 7 days
$notif_dates = [];
$notif_counts = [];
$notif_stmt = $conn->prepare("
    SELECT notify_date, COUNT(*) as cnt
    FROM crop_notifications
    WHERE user_id=? AND notify_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    GROUP BY notify_date
    ORDER BY notify_date ASC
");
$notif_stmt->bind_param("i", $_SESSION['user_id']);
$notif_stmt->execute();
$notif_res = $notif_stmt->get_result();
$notif_map = [];
while($r = $notif_res->fetch_assoc()){
    $notif_map[$r['notify_date']] = (int)$r['cnt'];
}
$notif_stmt->close();

// Fill last 7 days labels and counts (ensures days with 0 are shown)
for($i = 6; $i >= 0; $i--){
    $d = date('Y-m-d', strtotime("-{$i} days"));
    $notif_dates[] = date('d M', strtotime($d));
    $notif_counts[] = $notif_map[$d] ?? 0;
}

// Crop stage timeline (use last_updated timestamp from farmer_crops)
$timeline_items = [];
foreach($farmer_crops as $c){
    $timeline_items[] = [
        'crop' => $c['crop_name'],
        'stage' => $c['growth_stage'],
        'when' => $c['last_updated']
    ];
}

// Encode PHP arrays for JS
$growth_labels_json = json_encode($growth_labels);
$growth_values_json = json_encode($growth_values);
$notif_dates_json = json_encode($notif_dates);
$notif_counts_json = json_encode($notif_counts);
$timeline_json = json_encode($timeline_items);

// Language/Translations
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: farmer_reports.php");
    exit;
}
$lang = $_SESSION['lang'] ?? 'en';
$translations = include "languages/$lang.php";

// Page title and navbar link use translations where available
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($lang); ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title><?php echo $translations['farmer_reports'] ?? 'Farmer Reports'; ?> | Smart Cultivation System</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

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
    padding: 20px;
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

/* Container */
.container {
    position: relative;
    z-index: 1;
    max-width: 1400px;
    margin: 0 auto;
    animation: fadeInUp 0.6s ease-out;
}

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

/* Top Bar */
.topbar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 32px;
    background: white;
    padding: 24px 32px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    flex-wrap: wrap;
    gap: 16px;
}

.topbar .title {
    display: flex;
    align-items: center;
    gap: 16px;
}

.logo {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 24px;
    box-shadow: var(--shadow-sm);
}

.title h1 {
    font-size: 28px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin: 0;
    letter-spacing: -0.02em;
}

.title .subtitle {
    font-size: 14px;
    color: var(--text-light);
    margin-top: 4px;
}

.controls {
    display: flex;
    gap: 12px;
    align-items: center;
    flex-wrap: wrap;
}

.controls .btn {
    padding: 10px 18px;
    border-radius: 10px;
    background: var(--bg-light);
    color: var(--text-dark);
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    font-size: 14px;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
}

.controls .btn:hover {
    background: var(--primary-green);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

.controls .btn-primary {
    background: var(--primary-green);
    color: white;
}

.controls .btn-primary:hover {
    background: var(--primary-green-dark);
}

.lang-switch {
    display: flex;
    gap: 4px;
    background: var(--bg-light);
    padding: 4px;
    border-radius: 8px;
}

.lang-switch a {
    padding: 6px 12px;
    border-radius: 6px;
    color: var(--text-dark);
    text-decoration: none;
    font-size: 13px;
    transition: var(--transition);
}

.lang-switch a:hover,
.lang-switch a.active {
    background: var(--primary-green);
    color: white;
}

/* Grid Layout */
.grid {
    display: grid;
    grid-template-columns: 1fr 380px;
    gap: 24px;
}

@media (max-width: 1024px) {
    .grid {
        grid-template-columns: 1fr;
    }
}

/* Left Column */
.left-col {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Cards Row */
.cards-row {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 24px;
}

@media (max-width: 768px) {
    .cards-row {
        grid-template-columns: 1fr;
    }
}

/* Cards */
.card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    border: 2px solid transparent;
    transition: var(--transition);
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }

.card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-green-light);
}

.card h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0 0 8px 0;
    display: flex;
    align-items: center;
    gap: 10px;
}

.card h3 i {
    color: var(--primary-green);
    font-size: 20px;
}

.card p {
    margin: 0 0 16px 0;
    color: var(--text-light);
    font-size: 14px;
}

/* Chart Containers */
.chart-wrap {
    height: 280px;
    padding: 8px;
    position: relative;
}

/* Timeline */
.timeline {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.timeline .item {
    display: flex;
    gap: 14px;
    align-items: flex-start;
    background: var(--bg-light);
    padding: 14px 16px;
    border-radius: 12px;
    border-left: 4px solid var(--primary-green);
    transition: var(--transition);
    animation: slideInRight 0.4s ease-out;
    animation-fill-mode: both;
}

.timeline .item:nth-child(1) { animation-delay: 0.1s; }
.timeline .item:nth-child(2) { animation-delay: 0.2s; }
.timeline .item:nth-child(3) { animation-delay: 0.3s; }
.timeline .item:nth-child(4) { animation-delay: 0.4s; }
.timeline .item:nth-child(5) { animation-delay: 0.5s; }

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

.timeline .item:hover {
    background: #e8f5e9;
    transform: translateX(4px);
}

.timeline .item .dot {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    background: var(--primary-green);
    margin-top: 6px;
    flex-shrink: 0;
    box-shadow: 0 0 0 4px rgba(45, 134, 89, 0.1);
}

.timeline .item .meta {
    flex: 1;
}

.timeline .item .meta strong {
    display: block;
    color: var(--text-dark);
    font-size: 15px;
    margin-bottom: 4px;
}

.timeline .item .meta small {
    display: block;
    color: var(--text-light);
    font-size: 13px;
}

/* Right Column */
.right-col {
    display: flex;
    flex-direction: column;
    gap: 24px;
}

/* Weather Card */
.weather-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    text-align: center;
    animation: fadeInUp 0.6s ease-out;
    animation-delay: 0.3s;
    animation-fill-mode: both;
}

.weather-header {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 16px;
    margin-bottom: 20px;
    padding-bottom: 20px;
    border-bottom: 2px solid var(--bg-light);
}

.weather-icon {
    width: 80px;
    height: 80px;
}

.weather-info {
    text-align: left;
}

.weather-info .description {
    font-size: 16px;
    font-weight: 600;
    color: var(--text-dark);
    text-transform: capitalize;
    margin-bottom: 4px;
}

.weather-info .temperature {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 4px;
}

.weather-info .location {
    font-size: 13px;
    color: var(--text-light);
    display: flex;
    align-items: center;
    gap: 6px;
}

.small-stats {
    display: grid;
    grid-template-columns: repeat(3, 1fr);
    gap: 12px;
    margin-bottom: 20px;
}

.small-stat {
    background: var(--bg-light);
    padding: 12px;
    border-radius: 10px;
    text-align: center;
}

.small-stat .label {
    font-size: 12px;
    color: var(--text-light);
    margin-bottom: 6px;
}

.small-stat .value {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
}

.actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.actions button {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    border: none;
    background: var(--primary-green);
    color: white;
    font-weight: 600;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
}

.actions button:hover {
    background: var(--primary-green-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

/* Stats Card */
.stats-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

.stats-card h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 20px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.stats-card h3 i {
    color: var(--primary-green);
}

.kv {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid var(--bg-light);
}

.kv:last-child {
    border-bottom: none;
}

.kv span {
    color: var(--text-light);
    font-size: 14px;
}

.kv strong {
    color: var(--text-dark);
    font-size: 16px;
    font-weight: 700;
}

/* Export Card */
.export-card {
    background: white;
    padding: 24px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

.export-card h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 10px;
}

.export-card h3 i {
    color: var(--primary-green);
}

.export-card p {
    color: var(--text-light);
    font-size: 14px;
    margin-bottom: 16px;
}

.export-actions {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.export-actions .btn {
    flex: 1;
    padding: 12px;
    border-radius: 10px;
    background: var(--bg-light);
    color: var(--text-dark);
    text-decoration: none;
    border: none;
    cursor: pointer;
    font-weight: 600;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    font-size: 14px;
}

.export-actions .btn:hover {
    background: var(--primary-green);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-sm);
}

/* Print Styles */
@media print {
    .controls,
    .actions,
    .export-actions {
        display: none;
    }
    
    .card {
        page-break-inside: avoid;
    }
}

/* Responsive */
@media (max-width: 768px) {
    .topbar {
        flex-direction: column;
        align-items: flex-start;
    }

    .controls {
        width: 100%;
        justify-content: flex-start;
    }

    .small-stats {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .topbar {
        padding: 20px;
    }

    .card {
        padding: 20px;
    }
}
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="title">
            <div class="logo">
                <i class="fas fa-chart-line"></i>
            </div>
            <div>
                <h1><?php echo $translations['farmer_reports'] ?? 'Farmer Reports'; ?></h1>
                <div class="subtitle">
                    <i class="fas fa-user"></i>
                    <?php echo htmlspecialchars($user['fullname']); ?>
                </div>
            </div>
        </div>

        <div class="controls">
            <a class="btn btn-primary" href="farmer_dashboard.php">
                <i class="fas fa-arrow-left"></i>
                <?php echo $translations['back'] ?? 'Back to Dashboard'; ?>
            </a>
            <div class="lang-switch">
                <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">EN</a>
                <a href="?lang=te" class="<?php echo $lang === 'te' ? 'active' : ''; ?>">TE</a>
            </div>
            <button class="btn btn-primary" id="printBtn">
                <i class="fas fa-print"></i>
                Print
            </button>
        </div>
    </div>

    <div class="grid">
        <!-- LEFT: charts & timeline -->
        <div class="left-col">
            <div class="cards-row">
                <div class="card">
                    <h3><i class="fas fa-chart-bar"></i> <?php echo $translations['growth_progress'] ?? 'Growth Progress'; ?></h3>
                    <p><?php echo $translations['growth_progress_sub'] ?? 'Progress per crop based on current stage'; ?></p>
                    <div class="chart-wrap">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>

                <div class="card">
                    <h3><i class="fas fa-bell"></i> <?php echo $translations['notifications_trend'] ?? 'Notifications Trend (7 days)'; ?></h3>
                    <p><?php echo $translations['notifications_trend_sub'] ?? 'Number of reminders / notifications per day'; ?></p>
                    <div class="chart-wrap">
                        <canvas id="notifChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-cloud-sun"></i> <?php echo $translations['weather_analytics'] ?? 'Weather Analytics (5 day)'; ?></h3>
                <p><?php echo $translations['weather_analytics_sub'] ?? 'Local forecast from OpenWeatherMap'; ?></p>
                <div class="chart-wrap">
                    <canvas id="weatherChart"></canvas>
                </div>
                <div style="margin-top:16px;padding-top:16px;border-top:2px solid var(--bg-light);">
                    <div class="kv">
                        <span><i class="fas fa-map-marker-alt" style="color: var(--primary-green); margin-right: 6px;"></i>Location</span>
                        <strong><?php echo htmlspecialchars($user['district']); ?></strong>
                    </div>
                </div>
            </div>

            <div class="card">
                <h3><i class="fas fa-history"></i> <?php echo $translations['crop_stage_timeline'] ?? 'Crop Stage Timeline'; ?></h3>
                <p><?php echo $translations['crop_stage_timeline_sub'] ?? 'Recent stage updates for your crops'; ?></p>

                <div class="timeline" id="timelineList">
                    <?php
                    if(count($timeline_items) > 0){
                        foreach($timeline_items as $it){
                            $when = date("d M Y, H:i", strtotime($it['when']));
                            $stage = htmlspecialchars($it['stage']);
                            $crop = htmlspecialchars($it['crop']);
                            $stageIcons = [
                                'Seed' => '🌱',
                                'Germination' => '🌿',
                                'Vegetative' => '🌳',
                                'Flowering' => '🌸',
                                'Harvest' => '🌾'
                            ];
                            $stageIcon = $stageIcons[$stage] ?? '🌱';
                            echo "<div class='item'>
                                    <div class='dot'></div>
                                    <div class='meta'>
                                        <strong>{$stageIcon} {$crop}</strong>
                                        <small>{$stage} &middot; {$when}</small>
                                    </div>
                                  </div>";
                        }
                    } else {
                        echo "<div class='item'>
                                <div class='dot'></div>
                                <div class='meta'>
                                    <strong>No timeline data available</strong>
                                    <small>Update your crop stages to see timeline</small>
                                </div>
                              </div>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <!-- RIGHT: weather snapshot, key stats -->
        <aside class="right-col">
            <div class="weather-card" id="weatherSnapshot">
                <div class="weather-header">
                    <img src="" alt="weather" id="wsIcon" class="weather-icon" style="display:none">
                    <div class="weather-info">
                        <div id="wsDesc" class="description">Loading...</div>
                        <div id="wsTemp" class="temperature">--°C</div>
                        <div id="wsLoc" class="location">
                            <i class="fas fa-map-marker-alt"></i>
                            <?php echo htmlspecialchars($user['district']); ?>
                        </div>
                    </div>
                </div>

                <div class="small-stats">
                    <div class="small-stat">
                        <div class="label"><i class="fas fa-tint"></i> Humidity</div>
                        <div id="wsHum" class="value">--%</div>
                    </div>
                    <div class="small-stat">
                        <div class="label"><i class="fas fa-wind"></i> Wind</div>
                        <div id="wsWind" class="value">-- m/s</div>
                    </div>
                    <div class="small-stat">
                        <div class="label"><i class="fas fa-cloud"></i> Clouds</div>
                        <div id="wsCloud" class="value">--%</div>
                    </div>
                </div>

                <div class="actions">
                    <button id="refreshWeather">
                        <i class="fas fa-sync-alt"></i>
                        <?php echo $translations['refresh'] ?? 'Refresh'; ?>
                    </button>
                    <button id="downloadPNG">
                        <i class="fas fa-download"></i>
                        <?php echo $translations['download_chart'] ?? 'Download Chart'; ?>
                    </button>
                </div>
            </div>

            <div class="stats-card">
                <h3><i class="fas fa-chart-pie"></i> <?php echo $translations['quick_stats'] ?? 'Quick Stats'; ?></h3>
                <div>
                    <div class="kv">
                        <span><i class="fas fa-seedling" style="color: var(--primary-green); margin-right: 6px;"></i><?php echo $translations['total_crops'] ?? 'Total crops'; ?></span>
                        <strong><?php echo count($farmer_crops); ?></strong>
                    </div>
                    <div class="kv">
                        <span><i class="fas fa-bell" style="color: var(--primary-green); margin-right: 6px;"></i><?php echo $translations['total_notifications'] ?? 'Total notifications'; ?></span>
                        <strong>
                        <?php
                        // Count total notifications for this user
                        $totN = 0;
                        $tstmt = $conn->prepare("SELECT COUNT(*) as cnt FROM crop_notifications WHERE user_id=?");
                        $tstmt->bind_param("i", $_SESSION['user_id']);
                        $tstmt->execute();
                        $tres = $tstmt->get_result()->fetch_assoc();
                        $totN = $tres['cnt'] ?? 0;
                        $tstmt->close();
                        echo (int)$totN;
                        ?>
                        </strong>
                    </div>
                    <div class="kv">
                        <span><i class="fas fa-calendar-check" style="color: var(--primary-green); margin-right: 6px;"></i><?php echo $translations['last_update'] ?? 'Last update'; ?></span>
                        <strong><?php echo date("d M Y"); ?></strong>
                    </div>
                </div>
            </div>

            <div class="export-card">
                <h3><i class="fas fa-file-export"></i> <?php echo $translations['export'] ?? 'Export'; ?></h3>
                <p><?php echo $translations['export_sub'] ?? 'Download or print reports for records'; ?></p>
                <div class="export-actions">
                    <button onclick="window.print()" class="btn">
                        <i class="fas fa-print"></i>
                        <?php echo $translations['print'] ?? 'Print'; ?>
                    </button>
                    <a class="btn" href="farmer_dashboard.php">
                        <i class="fas fa-arrow-left"></i>
                        <?php echo $translations['back'] ?? 'Back'; ?>
                    </a>
                </div>
            </div>
        </aside>
    </div>
</div>

<!-- JS: Charts + weather fetch -->
<script>
/* Data passed from PHP */
const growthLabels = <?php echo $growth_labels_json; ?>;
const growthValues = <?php echo $growth_values_json; ?>;
const notifLabels = <?php echo $notif_dates_json; ?>;
const notifValues = <?php echo $notif_counts_json; ?>;
const timelineItems = <?php echo $timeline_json; ?>;

/* CHART: Growth Progress (bar) */
const gCtx = document.getElementById('growthChart').getContext('2d');
const growthChart = new Chart(gCtx, {
    type: 'bar',
    data: {
        labels: growthLabels,
        datasets: [{
            label: '<?php echo addslashes($translations['progress'] ?? 'Progress (%)'); ?>',
            data: growthValues,
            borderRadius: 8,
            backgroundColor: (ctx) => {
                const g = ctx.chart.ctx.createLinearGradient(0,0,0,300);
                g.addColorStop(0, '#3da372');
                g.addColorStop(1, '#2d8659');
                return g;
            },
            borderColor: '#1f5d3f',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                max: 100,
                ticks: {
                    stepSize: 20,
                    color: '#5a6c7d',
                    font: { size: 12 }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                ticks: {
                    color: '#5a6c7d',
                    font: { size: 12 }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(45, 134, 89, 0.9)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#1f5d3f',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8
            }
        }
    }
});

/* CHART: Notifications (line) */
const nCtx = document.getElementById('notifChart').getContext('2d');
const notifChart = new Chart(nCtx, {
    type: 'line',
    data: {
        labels: notifLabels,
        datasets: [{
            label: '<?php echo addslashes($translations['notifications'] ?? 'Notifications'); ?>',
            data: notifValues,
            tension: 0.4,
            pointRadius: 6,
            pointHoverRadius: 8,
            fill: true,
            backgroundColor: 'rgba(45, 134, 89, 0.1)',
            borderColor: '#2d8659',
            borderWidth: 3,
            pointBackgroundColor: '#2d8659',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    color: '#5a6c7d',
                    font: { size: 12 },
                    stepSize: 1
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                ticks: {
                    color: '#5a6c7d',
                    font: { size: 12 }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(45, 134, 89, 0.9)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#1f5d3f',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8
            }
        }
    }
});

/* CHART: Weather placeholder - will update after fetch */
const wCtx = document.getElementById('weatherChart').getContext('2d');
let weatherChart = new Chart(wCtx, {
    type: 'line',
    data: {
        labels: [],
        datasets: [{
            label: '<?php echo addslashes($translations['temperature'] ?? 'Temperature (°C)'); ?>',
            data: [],
            tension: 0.4,
            fill: true,
            backgroundColor: 'rgba(76, 175, 80, 0.1)',
            borderColor: '#4caf50',
            borderWidth: 3,
            pointRadius: 6,
            pointHoverRadius: 8,
            pointBackgroundColor: '#4caf50',
            pointBorderColor: '#fff',
            pointBorderWidth: 2
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
            y: {
                ticks: {
                    color: '#5a6c7d',
                    font: { size: 12 }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            },
            x: {
                ticks: {
                    color: '#5a6c7d',
                    font: { size: 12 }
                },
                grid: {
                    color: 'rgba(0,0,0,0.05)'
                }
            }
        },
        plugins: {
            legend: { display: false },
            tooltip: {
                backgroundColor: 'rgba(45, 134, 89, 0.9)',
                titleColor: '#fff',
                bodyColor: '#fff',
                borderColor: '#1f5d3f',
                borderWidth: 1,
                padding: 12,
                cornerRadius: 8
            }
        }
    }
});

/* WEATHER: fetch 5-day forecast (group by day) */
const apiKey = "8939754260ab572788b1c798b4e89406";
const district = "<?php echo addslashes($user['district']); ?>";
const weatherSnapshot = document.getElementById('weatherSnapshot');
const wsIcon = document.getElementById('wsIcon');
const wsDesc = document.getElementById('wsDesc');
const wsTemp = document.getElementById('wsTemp');
const wsHum = document.getElementById('wsHum');
const wsWind = document.getElementById('wsWind');
const wsCloud = document.getElementById('wsCloud');

function fetchWeatherAndRender(){
    // Use 5 day / 3 hour forecast and average per day
    const url = `https://api.openweathermap.org/data/2.5/forecast?q=${encodeURIComponent(district)}&appid=${apiKey}&units=metric`;
    fetch(url).then(r => r.json()).then(d => {
        if(!d || d.cod != "200"){
            wsDesc.innerText = '<?php echo addslashes($translations['weather_unavailable'] ?? 'Weather unavailable'); ?>';
            wsTemp.innerText = '--°C';
            return;
        }

        // snapshot: use first item as current-ish
        const first = d.list[0];
        wsIcon.src = `https://openweathermap.org/img/wn/${first.weather[0].icon}@2x.png`;
        wsIcon.style.display = 'block';
        wsDesc.innerText = first.weather[0].description.toUpperCase();
        wsTemp.innerText = Math.round(first.main.temp) + '°C';
        wsHum.innerText = first.main.humidity + '%';
        wsWind.innerText = first.wind.speed + ' m/s';
        wsCloud.innerText = (first.clouds && first.clouds.all ? first.clouds.all : '--') + '%';

        // Prepare 5-day aggregated temps
        const dayMap = {}; // date => {sum, count}
        d.list.forEach(item => {
            const date = item.dt_txt.split(' ')[0]; // yyyy-mm-dd
            if(!dayMap[date]) dayMap[date] = {sum:0, count:0};
            dayMap[date].sum += item.main.temp;
            dayMap[date].count += 1;
        });

        const days = Object.keys(dayMap).slice(0,5);
        const labels = days.map(dt => {
            const dd = new Date(dt);
            return dd.toLocaleDateString(undefined, {day:'2-digit', month:'short'});
        });
        const temps = days.map(dt => Math.round(dayMap[dt].sum / dayMap[dt].count));

        // Update weather chart
        weatherChart.data.labels = labels;
        weatherChart.data.datasets[0].data = temps;
        weatherChart.update();

    }).catch(err => {
        console.error(err);
        wsDesc.innerText = '<?php echo addslashes($translations['weather_unavailable'] ?? 'Weather unavailable'); ?>';
        wsTemp.innerText = '--°C';
    });
}

/* Refresh handlers */
document.getElementById('refreshWeather').addEventListener('click', ()=>{
    fetchWeatherAndRender();
});

/* Initial fetch */
fetchWeatherAndRender();

/* Print button */
document.getElementById('printBtn').addEventListener('click', ()=>window.print());

/* Download PNG for growth chart (simple example) */
document.getElementById('downloadPNG').addEventListener('click', ()=>{
    const a = document.createElement('a');
    a.href = growthChart.toBase64Image();
    a.download = 'growth_progress_<?php echo date('Ymd'); ?>.png';
    a.click();
});
</script>
</body>
</html>
