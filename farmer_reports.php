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

<!-- Fonts + icons + Chart.js -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<style>
/* Page layout and glass UI inspired by dashboard */
:root{
    --glass-bg: rgba(255,255,255,0.08);
    --glass-strong: rgba(255,255,255,0.14);
    --accent: linear-gradient(90deg,#ffd700,#ff8c00,#ff4c4c);
}
*{box-sizing:border-box;font-family:'Poppins',sans-serif}
body{
    margin:0;background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);
    background-size:400% 400%;animation: gradientBG 20s linear infinite;color:#fff;
}
@keyframes gradientBG{0%{background-position:0% 50%}50%{background-position:100% 50%}100%{background-position:0% 50%}}

.container{
    max-width:1200px;margin:28px auto;padding:20px;
}

/* Top bar */
.topbar{
    display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;
}
.topbar .title{
    display:flex;align-items:center;gap:16px;
}
.logo{
    width:64px;height:64px;border-radius:12px;background:var(--glass-strong);
    display:flex;align-items:center;justify-content:center;font-weight:800;color:#fff;font-size:20px;
    box-shadow:0 8px 30px rgba(0,0,0,0.4);
}
.title h1{font-size:1.6rem;margin:0}
.controls{display:flex;gap:10px;align-items:center;}
.controls a.btn, .controls button.btn{
    padding:10px 14px;border-radius:12px;background:rgba(255,255,255,0.08);color:#fff;text-decoration:none;border:none;
    cursor:pointer;font-weight:600;backdrop-filter: blur(6px);
}
.controls a.btn:hover, .controls button.btn:hover{transform:translateY(-2px)}
.lang-switch a{color:#fff;text-decoration:none;padding:8px 10px;border-radius:8px;background:rgba(255,255,255,0.06)}

/* Grid */
.grid{
    display:grid;grid-template-columns: 1fr 420px;gap:22px;
}
@media(max-width:980px){ .grid{grid-template-columns:1fr} }

/* Left column */
.left-col{
    display:flex;flex-direction:column;gap:18px;
}

/* Cards row */
.cards-row{display:grid;grid-template-columns: repeat(2,1fr);gap:16px}
@media(max-width:720px){ .cards-row{grid-template-columns:1fr} }

.card{
    background: var(--glass-bg); padding:18px;border-radius:14px; box-shadow:0 12px 30px rgba(0,0,0,0.45);
    border:1px solid rgba(255,255,255,0.04);
}
.card h3{margin:0 0 6px 0;font-size:1rem}
.card p{margin:0;color:rgba(255,255,255,0.85)}

/* Chart containers */
.chart-wrap{height:300px;padding:8px}

/* Timeline / list */
.timeline{
    display:flex;flex-direction:column;gap:10px;
}
.timeline .item{
    display:flex;gap:12px;align-items:center;background:var(--glass-strong);
    padding:10px 12px;border-radius:10px;border:1px solid rgba(255,255,255,0.03);
}
.timeline .item .dot{width:12px;height:12px;border-radius:50%;background:#fff;opacity:0.9}
.timeline .item .meta{font-size:0.95rem}
.timeline .item .meta small{display:block;color:rgba(255,255,255,0.7);font-size:0.8rem}

/* Right column (sidebar) */
.right-col{display:flex;flex-direction:column;gap:18px}
.weather-card{
    background: linear-gradient(180deg, rgba(255,255,255,0.08), rgba(255,255,255,0.04));
    padding:18px;border-radius:14px;text-align:center;
    border:1px solid rgba(255,255,255,0.04);
}
.weather-card img{width:80px;height:80px}
.small-stats{display:flex;gap:10px;flex-direction:row;flex-wrap:wrap}
.small-stat{flex:1;min-width:120px;background:rgba(255,255,255,0.04);padding:10px;border-radius:10px;text-align:center}

/* Footer actions */
.actions{display:flex;gap:10px;align-items:center;flex-wrap:wrap;margin-top:12px}
.actions button{padding:10px 12px;border-radius:10px;border:none;background:var(--accent);color:#111;font-weight:700;cursor:pointer}
.print-hidden{display:none}

/* Make charts crisp on dark background */
canvas{background:transparent !important;}

/* Utility */
.kv{display:flex;justify-content:space-between;opacity:0.9}

/* subtle entrance */
.fade-up{animation:fadeUp .6s ease both}
@keyframes fadeUp{ from{opacity:0;transform:translateY(8px)} to{opacity:1;transform:none} }
</style>
</head>
<body>
<div class="container">
    <div class="topbar">
        <div class="title">
            <div class="logo"><i class="fas fa-leaf"></i></div>
            <div>
                <h1><?php echo $translations['farmer_reports'] ?? 'Farmer Reports'; ?></h1>
                <div style="opacity:0.85;font-size:0.9rem"><?php echo $translations['welcome'] ?? 'Smart Cultivation System'; ?> — <?php echo htmlspecialchars($user['fullname']); ?></div>
            </div>
        </div>

        <div class="controls">
            <a class="btn" href="farmer_dashboard.php"><i class="fas fa-arrow-left"></i> <?php echo $translations['back'] ?? 'Back to Dashboard'; ?></a>
            <a class="btn" href="?lang=en">EN</a>
            <a class="btn" href="?lang=te">TE</a>
            <button class="btn" id="printBtn"><i class="fas fa-print"></i> Print</button>
        </div>
    </div>

    <div class="grid">
        <!-- LEFT: charts & timeline -->
        <div class="left-col">
            <div class="cards-row">
                <div class="card fade-up">
                    <h3><?php echo $translations['growth_progress'] ?? 'Growth Progress'; ?></h3>
                    <p><?php echo $translations['growth_progress_sub'] ?? 'Progress per crop based on current stage'; ?></p>
                    <div class="chart-wrap">
                        <canvas id="growthChart"></canvas>
                    </div>
                </div>

                <div class="card fade-up">
                    <h3><?php echo $translations['notifications_trend'] ?? 'Notifications Trend (7 days)'; ?></h3>
                    <p><?php echo $translations['notifications_trend_sub'] ?? 'Number of reminders / notifications per day'; ?></p>
                    <div class="chart-wrap">
                        <canvas id="notifChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card fade-up">
                <h3><?php echo $translations['weather_analytics'] ?? 'Weather Analytics (5 day)'; ?></h3>
                <p><?php echo $translations['weather_analytics_sub'] ?? 'Local forecast from OpenWeatherMap'; ?></p>
                <div class="chart-wrap">
                    <canvas id="weatherChart"></canvas>
                </div>
                <div style="margin-top:10px;font-size:0.9rem;opacity:0.85">
                    <span class="kv"><span>Location</span><strong><?php echo htmlspecialchars($user['district']); ?></strong></span>
                </div>
            </div>

            <div class="card fade-up">
                <h3><?php echo $translations['crop_stage_timeline'] ?? 'Crop Stage Timeline'; ?></h3>
                <p><?php echo $translations['crop_stage_timeline_sub'] ?? 'Recent stage updates for your crops'; ?></p>

                <div class="timeline" id="timelineList">
                    <?php
                    if(count($timeline_items) > 0){
                        foreach($timeline_items as $it){
                            $when = date("d M Y, H:i", strtotime($it['when']));
                            $stage = htmlspecialchars($it['stage']);
                            $crop = htmlspecialchars($it['crop']);
                            echo "<div class='item'><div class='dot'></div><div class='meta'><strong>{$crop}</strong><small>{$stage} &middot; {$when}</small></div></div>";
                        }
                    } else {
                        echo "<div class='item'><div class='dot'></div><div class='meta'><strong>No timeline data</strong></div></div>";
                    }
                    ?>
                </div>

            </div>
        </div>

        <!-- RIGHT: weather snapshot, key stats -->
        <aside class="right-col">
            <div class="weather-card fade-up" id="weatherSnapshot">
                <div style="display:flex;align-items:center;justify-content:center;gap:12px">
                    <div>
                        <img src="" alt="weather" id="wsIcon" style="display:none">
                    </div>
                    <div style="text-align:left">
                        <div id="wsDesc" style="font-size:1.1rem;font-weight:700">--</div>
                        <div id="wsTemp" style="font-size:1.6rem;font-weight:700">--°C</div>
                        <div style="opacity:0.85" id="wsLoc"><?php echo htmlspecialchars($user['district']); ?></div>
                    </div>
                </div>

                <div style="margin-top:12px" class="small-stats">
                    <div class="small-stat"><div style="opacity:0.85">Humidity</div><div id="wsHum">--%</div></div>
                    <div class="small-stat"><div style="opacity:0.85">Wind</div><div id="wsWind">-- m/s</div></div>
                    <div class="small-stat"><div style="opacity:0.85">Clouds</div><div id="wsCloud">--%</div></div>
                </div>

                <div class="actions">
                    <button id="refreshWeather"><?php echo $translations['refresh'] ?? 'Refresh'; ?></button>
                    <button id="downloadPNG"><?php echo $translations['download_chart'] ?? 'Download Chart'; ?></button>
                </div>
            </div>

            <div class="card fade-up">
                <h3><?php echo $translations['quick_stats'] ?? 'Quick Stats'; ?></h3>
                <div style="margin-top:10px">
                    <div class="kv"><span><?php echo $translations['total_crops'] ?? 'Total crops'; ?></span><strong><?php echo count($farmer_crops); ?></strong></div>
                    <div class="kv" style="margin-top:6px"><span><?php echo $translations['total_notifications'] ?? 'Total notifications'; ?></span>
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
                    <div style="margin-top:6px" class="kv"><span><?php echo $translations['last_update'] ?? 'Last update'; ?></span><strong><?php echo date("d M Y"); ?></strong></div>
                </div>
            </div>

            <div class="card fade-up">
                <h3><?php echo $translations['export'] ?? 'Export'; ?></h3>
                <p style="opacity:0.85"><?php echo $translations['export_sub'] ?? 'Download or print reports for records'; ?></p>
                <div style="margin-top:10px;display:flex;gap:8px">
                    <button onclick="window.print()" class="btn"><?php echo $translations['print'] ?? 'Print'; ?></button>
                    <a class="btn" href="farmer_dashboard.php"><?php echo $translations['back'] ?? 'Back'; ?></a>
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
                // gradient effect
                const g = ctx.chart.ctx.createLinearGradient(0,0,0,300);
                g.addColorStop(0, '#ffd700');
                g.addColorStop(1, '#ff4c4c');
                return g;
            }
        }]
    },
    options: {
        responsive:true,
        maintainAspectRatio:false,
        scales:{
            y:{beginAtZero:true, max:100, ticks:{stepSize:20}}
        },
        plugins:{legend:{display:false}}
    }
});

/* CHART: Notifications (line) */
const nCtx = document.getElementById('notifChart').getContext('2d');
const notifChart = new Chart(nCtx, {
    type:'line',
    data:{
        labels: notifLabels,
        datasets:[{
            label: '<?php echo addslashes($translations['notifications'] ?? 'Notifications'); ?>',
            data: notifValues,
            tension:0.3,
            pointRadius:6,
            fill:true,
            backgroundColor:'rgba(255,255,255,0.06)',
            borderColor:'#ff8c00',
            pointBackgroundColor:'#fff'
        }]
    },
    options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}}
});

/* CHART: Weather placeholder - will update after fetch */
const wCtx = document.getElementById('weatherChart').getContext('2d');
let weatherChart = new Chart(wCtx, {
    type:'line',
    data:{labels:[], datasets:[{
        label: '<?php echo addslashes($translations['temperature'] ?? 'Temperature (°C)'); ?>',
        data:[], tension:0.3, fill:true, backgroundColor:'rgba(255,255,255,0.06)', borderColor:'#00c6ff'
    }]},
    options:{responsive:true, maintainAspectRatio:false, plugins:{legend:{display:false}}}
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
