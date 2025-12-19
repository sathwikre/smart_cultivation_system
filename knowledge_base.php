<?php
session_start();
require 'db_connection.php'; // Connect to your smart_cultivation DB

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'farmer'){
    header("Location: login.php");
    exit;
}

$crop = $_GET['crop'] ?? "";

// Fetch knowledge base entries for selected crop
$entries = [];
if($crop != ""){
    $stmt = $conn->prepare("SELECT * FROM knowledge_base WHERE crop_name=? ORDER BY 
        FIELD(section,'Growth Stages','Problems & Solutions','Fertilizer Schedule','Watering Schedule'), id");
    $stmt->bind_param("s", $crop);
    $stmt->execute();
    $result = $stmt->get_result();
    while($row = $result->fetch_assoc()){
        $entries[$row['section']][] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Knowledge Base</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<style>
body {
    margin:0;
    padding:0;
    font-family:'Poppins',sans-serif;
    background: linear-gradient(135deg,#0cebeb,#20e3b2,#29ffc6);
    background-size:300% 300%;
    animation:bgAnim 10s infinite alternate;
    color:#fff;
}
@keyframes bgAnim {
    0%{background-position:0% 50%;}
    100%{background-position:100% 50%;}
}

.container {
    max-width:1200px;
    margin:40px auto;
    padding:20px;
}

.card {
    background: rgba(255,255,255,0.15);
    border-radius:20px;
    padding:25px;
    margin-bottom:20px;
    backdrop-filter: blur(12px);
    box-shadow:0 8px 25px rgba(0,0,0,0.3);
}

.crop-btn {
    background:#fff;
    color:#333;
    padding:12px 20px;
    border-radius:12px;
    margin-right:10px;
    text-decoration:none;
    font-weight:700;
    box-shadow:0 5px 12px rgba(0,0,0,0.25);
    position: sticky;
    top: 10px;
}
.crop-btn:hover {
    background:#ffca28;
    color:#000;
}

.accordion-button {
    background:rgba(255,255,255,0.2);
    color:#fff;
    font-weight:600;
    border:none;
}
.accordion-button:not(.collapsed){
    background:rgba(255,255,255,0.35);
}
.accordion-body {
    background:rgba(255,255,255,0.1);
    border-radius:10px;
    padding:20px;
    line-height:1.8;
}

h2, h3 {
    font-weight:700;
}
.search-bar {
    width:100%;
    padding:12px 15px;
    border-radius:12px;
    border:none;
    margin-bottom:20px;
    font-size:16px;
}
.highlight {
    background: rgba(255,255,0,0.3);
    color:#000;
    padding:2px 6px;
    border-radius:4px;
}
</style>
</head>

<body>
<div class="container">

<h2 style="text-align:center;margin-bottom:20px;">📘 Knowledge Base</h2>

<!-- CROP SELECTION -->
<div style="text-align:center;margin-bottom:20px;">
    <a href="knowledge_base.php?crop=tomato" class="crop-btn">🍅 Tomato Guide</a>
    <a href="knowledge_base.php?crop=groundnut" class="crop-btn">🥜 Groundnut Guide</a>
    <a href="farmer_dashboard.php" class="crop-btn" style="background:#ff5252;color:#fff;">⬅ Back</a>
</div>

<!-- SEARCH BAR -->
<?php if($crop != ""): ?>
<input type="text" id="search" class="search-bar" placeholder="Search stages, fertilizer, pests...">
<?php endif; ?>

<?php if($crop == ""): ?>
<div class="card">
    <h3>Select a crop to view complete step-by-step guide 👆</h3>
</div>
<?php endif; ?>

<!-- SEARCH FILTER SCRIPT -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('search');
    if(searchInput){
        searchInput.addEventListener('keyup', function(){
            const query = this.value.toLowerCase();
            document.querySelectorAll('.accordion-item').forEach(function(item){
                if(item.textContent.toLowerCase().includes(query)){
                    item.style.display='block';
                } else {
                    item.style.display='none';
                }
            });
        });
    }
});
</script>

<!-- DYNAMIC KNOWLEDGE BASE -->
<?php if($crop != "" && !empty($entries)): ?>
    <div class="card"><h2>
        <?php echo ($crop=="tomato"?"🍅 Tomato":"🥜 Groundnut"); ?> — Complete Crop Guide
    </h2></div>

    <?php foreach($entries as $section => $rows): ?>
        <div class="card">
            <h3>
                <?php
                if($section=="Growth Stages") echo "🌱 Growth Stages — FULL DETAILS";
                elseif($section=="Problems & Solutions") echo "⚠ Problems & Solutions";
                elseif($section=="Fertilizer Schedule") echo "💧 Fertilizer Schedule";
                elseif($section=="Watering Schedule") echo "🚰 Watering Schedule";
                else echo $section;
                ?>
            </h3>

            <?php if($section=="Growth Stages"): ?>
                <div class="accordion" id="<?php echo strtolower(str_replace(' ','_',$section)); ?>">
                    <?php $id=1; foreach($rows as $row): ?>
                        <div class="accordion-item">
                            <h2 class="accordion-header">
                                <button class="accordion-button <?php echo $id>1?'collapsed':''; ?>" data-bs-toggle="collapse" data-bs-target="#<?php echo 's'.$id; ?>">
                                    🌿 <?php echo $row['title']; ?>
                                </button>
                            </h2>
                            <div id="<?php echo 's'.$id; ?>" class="accordion-collapse collapse <?php echo $id==1?'show':''; ?>">
                                <div class="accordion-body">
                                    <ul>
                                        <?php
                                        $points = array_filter(array_map('trim', explode('.', $row['description'])));
                                        foreach($points as $point){
                                            echo '<li>'.$point.'</li>';
                                        }
                                        ?>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    <?php $id++; endforeach; ?>
                </div>
            <?php else: ?>
                <ul>
                    <?php foreach($rows as $row):
                        $points = array_filter(array_map('trim', explode('.', $row['description'])));
                        echo '<li><b>'.$row['title'].':</b><ul>';
                        foreach($points as $point){
                            echo '<li>'.$point.'</li>';
                        }
                        echo '</ul></li>';
                    endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endforeach; ?>

<?php elseif($crop != "" && empty($entries)): ?>
    <div class="card">
        <h3>No guide available for this crop yet.</h3>
    </div>
<?php endif; ?>

</div>
</body>
</html>
