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
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Knowledge Base | Smart Cultivation System</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<!-- Bootstrap for Accordion -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">

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
    max-width: 1200px;
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

/* Header */
.header {
    text-align: center;
    margin-bottom: 40px;
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    animation: slideDown 0.6s ease-out;
}

@keyframes slideDown {
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
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 8px;
    letter-spacing: -0.02em;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
}

.header h1 i {
    color: var(--primary-green);
    font-size: 36px;
    animation: rotate 3s linear infinite;
}

@keyframes rotate {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}

.header p {
    color: var(--text-light);
    font-size: 15px;
}

/* Crop Selection Buttons */
.crop-selection {
    display: flex;
    justify-content: center;
    gap: 16px;
    margin-bottom: 32px;
    flex-wrap: wrap;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.2s;
    animation-fill-mode: both;
}

.crop-btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    font-size: 16px;
    font-weight: 700;
    text-decoration: none;
    color: white;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    position: relative;
    overflow: hidden;
    transition: var(--transition);
    animation: floatBtn 3s ease-in-out infinite;
    animation-delay: calc(var(--delay, 0) * 0.1s);
}

@keyframes floatBtn {
    0%, 100% { transform: translateY(0px); }
    50% { transform: translateY(-8px); }
}

.crop-btn::before {
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

.crop-btn:hover::before {
    transform: scale(1);
}

.crop-btn:hover {
    transform: translateY(-6px) scale(1.05);
    box-shadow: var(--shadow-lg);
}

.crop-btn.tomato {
    background: linear-gradient(135deg, #ff4c4c, #ff7f50);
}

.crop-btn.groundnut {
    background: linear-gradient(135deg, #ffca28, #ffb74d);
}

.crop-btn.back {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
}

/* Search Bar */
.search-container {
    margin-bottom: 32px;
    animation: fadeInUp 0.8s ease-out;
    animation-delay: 0.4s;
    animation-fill-mode: both;
}

.search-bar {
    width: 100%;
    padding: 16px 20px;
    font-size: 16px;
    font-family: inherit;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: white;
    color: var(--text-dark);
    transition: var(--transition);
    outline: none;
    box-shadow: var(--shadow-sm);
}

.search-bar:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

.search-bar::placeholder {
    color: #999;
}

/* Cards */
.card {
    background: white;
    border-radius: 16px;
    padding: 32px;
    margin-bottom: 24px;
    box-shadow: var(--shadow-sm);
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

.card:hover {
    transform: translateY(-4px);
    box-shadow: var(--shadow-md);
    border-color: var(--primary-green-light);
}

.card:hover::before {
    transform: scaleX(1);
}

.card:nth-child(1) { animation-delay: 0.1s; }
.card:nth-child(2) { animation-delay: 0.2s; }
.card:nth-child(3) { animation-delay: 0.3s; }
.card:nth-child(4) { animation-delay: 0.4s; }
.card:nth-child(5) { animation-delay: 0.5s; }

.card h2, .card h3 {
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
}

.card h3 {
    font-size: 20px;
    margin-bottom: 20px;
    padding-bottom: 12px;
    border-bottom: 3px solid var(--bg-light);
    position: relative;
}

.card h3::after {
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

/* Accordion Styling */
.accordion {
    --bs-accordion-border-color: var(--border-color);
    --bs-accordion-border-radius: 12px;
    --bs-accordion-inner-border-radius: 12px;
}

.accordion-item {
    border: 2px solid var(--border-color);
    border-radius: 12px !important;
    margin-bottom: 12px;
    overflow: hidden;
    transition: var(--transition);
    animation: slideInRight 0.4s ease-out;
    animation-fill-mode: both;
}

.accordion-item:nth-child(1) { animation-delay: 0.1s; }
.accordion-item:nth-child(2) { animation-delay: 0.2s; }
.accordion-item:nth-child(3) { animation-delay: 0.3s; }
.accordion-item:nth-child(4) { animation-delay: 0.4s; }
.accordion-item:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInRight {
    from {
        opacity: 0;
        transform: translateX(-30px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.accordion-item:hover {
    border-color: var(--primary-green);
    box-shadow: var(--shadow-sm);
    transform: translateX(4px);
}

.accordion-button {
    background: var(--bg-light) !important;
    color: var(--text-dark) !important;
    font-weight: 600;
    font-size: 16px;
    padding: 18px 20px;
    border: none;
    transition: var(--transition);
}

.accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, rgba(45, 134, 89, 0.1), rgba(76, 175, 80, 0.1)) !important;
    color: var(--primary-green-dark) !important;
    box-shadow: none;
}

.accordion-button:focus {
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
    border-color: var(--primary-green);
}

.accordion-button::after {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%232d8659'%3e%3cpath fill-rule='evenodd' d='M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z'/%3e%3c/svg%3e");
    transition: transform 0.3s ease;
}

.accordion-button:not(.collapsed)::after {
    transform: rotate(180deg);
}

.accordion-body {
    background: white;
    padding: 24px;
    line-height: 1.8;
    color: var(--text-dark);
    animation: fadeIn 0.4s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
    }
    to {
        opacity: 1;
    }
}

.accordion-body ul {
    list-style: none;
    padding-left: 0;
}

.accordion-body ul li {
    padding: 10px 0 10px 28px;
    position: relative;
    color: var(--text-dark);
    animation: slideInLeft 0.3s ease-out;
    animation-fill-mode: both;
}

.accordion-body ul li:nth-child(1) { animation-delay: 0.1s; }
.accordion-body ul li:nth-child(2) { animation-delay: 0.2s; }
.accordion-body ul li:nth-child(3) { animation-delay: 0.3s; }
.accordion-body ul li:nth-child(4) { animation-delay: 0.4s; }
.accordion-body ul li:nth-child(5) { animation-delay: 0.5s; }

@keyframes slideInLeft {
    from {
        opacity: 0;
        transform: translateX(-20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

.accordion-body ul li::before {
    content: "✓";
    position: absolute;
    left: 0;
    color: var(--primary-green);
    font-weight: 700;
    font-size: 18px;
    animation: checkmark 0.5s ease-out;
    animation-delay: calc(var(--index, 0) * 0.1s);
}

@keyframes checkmark {
    0% {
        transform: scale(0) rotate(-180deg);
        opacity: 0;
    }
    50% {
        transform: scale(1.2) rotate(10deg);
    }
    100% {
        transform: scale(1) rotate(0deg);
        opacity: 1;
    }
}

/* Lists */
.card ul {
    list-style: none;
    padding-left: 0;
}

.card ul li {
    padding: 12px 0 12px 32px;
    position: relative;
    color: var(--text-dark);
    border-bottom: 1px solid var(--bg-light);
    transition: var(--transition);
    animation: slideInLeft 0.4s ease-out;
    animation-fill-mode: both;
}

.card ul li:last-child {
    border-bottom: none;
}

.card ul li:hover {
    background: var(--bg-light);
    padding-left: 40px;
    border-left: 4px solid var(--primary-green);
}

.card ul li::before {
    content: "🌿";
    position: absolute;
    left: 0;
    font-size: 18px;
}

.card ul li b {
    color: var(--primary-green-dark);
    font-weight: 700;
    display: block;
    margin-bottom: 6px;
}

.card ul li ul {
    margin-top: 8px;
    margin-left: 20px;
}

.card ul li ul li {
    padding-left: 24px;
    font-size: 14px;
    color: var(--text-light);
}

.card ul li ul li::before {
    content: "•";
    color: var(--primary-green);
    font-size: 20px;
    left: 0;
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    animation: fadeInUp 0.6s ease-out;
}

.empty-state i {
    font-size: 64px;
    color: var(--text-light);
    margin-bottom: 20px;
    opacity: 0.5;
    animation: bounce 2s ease-in-out infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

.empty-state h3 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
}

.empty-state p {
    color: var(--text-light);
    font-size: 16px;
}

/* Section Icons */
.section-icon {
    font-size: 24px;
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% { transform: scale(1); }
    50% { transform: scale(1.1); }
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 0;
    }

    .header {
        padding: 30px 20px;
    }

    .header h1 {
        font-size: 28px;
    }

    .crop-selection {
        flex-direction: column;
    }

    .crop-btn {
        width: 100%;
        justify-content: center;
    }

    .card {
        padding: 24px 20px;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .header {
        padding: 24px 16px;
    }

    .header h1 {
        font-size: 24px;
    }

    .card {
        padding: 20px 16px;
    }
}

/* Highlight for search */
.highlight {
    background: linear-gradient(120deg, #ffc107 0%, #ffc107 100%);
    background-repeat: no-repeat;
    background-size: 100% 0.2em;
    background-position: 0 88%;
    padding: 2px 4px;
    border-radius: 4px;
    font-weight: 600;
}

/* Decorative Icons */
.icon-decoration {
    position: fixed;
    font-size: 8rem;
    color: rgba(45, 134, 89, 0.03);
    z-index: 0;
    pointer-events: none;
    animation: float 20s ease-in-out infinite;
}

.icon-decoration:nth-child(1) {
    top: 10%;
    left: 5%;
    animation-duration: 25s;
}

.icon-decoration:nth-child(2) {
    bottom: 10%;
    right: 5%;
    animation-duration: 30s;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg) scale(1);
    }
    33% {
        transform: translateY(-40px) rotate(120deg) scale(1.1);
    }
    66% {
        transform: translateY(-80px) rotate(240deg) scale(0.9);
    }
}
</style>
</head>
<body>

<!-- Decorative Icons -->
<i class="fas fa-book icon-decoration"></i>
<i class="fas fa-seedling icon-decoration"></i>

<div class="container">
    <!-- Header -->
    <div class="header">
        <h1>
            <i class="fas fa-book-open"></i>
            Knowledge Base
        </h1>
        <p>Comprehensive guides and expert advice for your crops</p>
    </div>

    <!-- Crop Selection -->
    <div class="crop-selection">
        <a href="knowledge_base.php?crop=tomato" class="crop-btn tomato" style="--delay: 0;">
            <span>🍅</span>
            <span>Tomato Guide</span>
        </a>
        <a href="knowledge_base.php?crop=groundnut" class="crop-btn groundnut" style="--delay: 1;">
            <span>🥜</span>
            <span>Groundnut Guide</span>
        </a>
        <a href="farmer_dashboard.php" class="crop-btn back" style="--delay: 2;">
            <i class="fas fa-arrow-left"></i>
            <span>Back to Dashboard</span>
        </a>
    </div>

    <!-- Search Bar -->
    <?php if($crop != ""): ?>
    <div class="search-container">
        <div style="position: relative;">
            <i class="fas fa-search" style="position: absolute; left: 20px; top: 50%; transform: translateY(-50%); color: var(--text-light); z-index: 1;"></i>
            <input type="text" id="search" class="search-bar" placeholder="Search stages, fertilizer, pests, problems..." style="padding-left: 50px;">
        </div>
    </div>
    <?php endif; ?>

    <?php if($crop == ""): ?>
    <div class="card empty-state">
        <i class="fas fa-hand-pointer"></i>
        <h3>Select a Crop Guide</h3>
        <p>Choose a crop from above to view the complete step-by-step cultivation guide</p>
    </div>
    <?php endif; ?>

    <!-- DYNAMIC KNOWLEDGE BASE -->
    <?php if($crop != "" && !empty($entries)): ?>
        <div class="card" style="background: linear-gradient(135deg, rgba(45, 134, 89, 0.1), rgba(76, 175, 80, 0.1)); border: 2px solid var(--primary-green-light);">
            <h2 style="margin-bottom: 0;">
                <span class="section-icon"><?php echo ($crop=="tomato"?"🍅":"🥜"); ?></span>
                <?php echo ucfirst($crop); ?> — Complete Crop Guide
            </h2>
            <p style="color: var(--text-light); margin-top: 8px; font-size: 15px;">
                Comprehensive cultivation guide with detailed stages, solutions, and schedules
            </p>
        </div>

        <?php 
        $sectionIcons = [
            "Growth Stages" => "🌱",
            "Problems & Solutions" => "⚠️",
            "Fertilizer Schedule" => "💧",
            "Watering Schedule" => "🚰"
        ];
        
        foreach($entries as $section => $rows): 
            $icon = $sectionIcons[$section] ?? "📋";
        ?>
            <div class="card">
                <h3>
                    <span class="section-icon"><?php echo $icon; ?></span>
                    <?php
                    if($section=="Growth Stages") echo "Growth Stages — Full Details";
                    elseif($section=="Problems & Solutions") echo "Problems & Solutions";
                    elseif($section=="Fertilizer Schedule") echo "Fertilizer Schedule";
                    elseif($section=="Watering Schedule") echo "Watering Schedule";
                    else echo $section;
                    ?>
                </h3>

                <?php if($section=="Growth Stages"): ?>
                    <div class="accordion" id="<?php echo strtolower(str_replace(' ','_',$section)); ?>">
                        <?php $id=1; foreach($rows as $row): ?>
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?php echo $id>1?'collapsed':''; ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?php echo 's'.$id; ?>" aria-expanded="<?php echo $id==1?'true':'false'; ?>">
                                        <i class="fas fa-seedling" style="margin-right: 10px; color: var(--primary-green);"></i>
                                        <?php echo htmlspecialchars($row['title']); ?>
                                    </button>
                                </h2>
                                <div id="<?php echo 's'.$id; ?>" class="accordion-collapse collapse <?php echo $id==1?'show':''; ?>" data-bs-parent="#<?php echo strtolower(str_replace(' ','_',$section)); ?>">
                                    <div class="accordion-body">
                                        <ul>
                                            <?php
                                            $points = array_filter(array_map('trim', explode('.', $row['description'])));
                                            $pointIndex = 0;
                                            foreach($points as $point):
                                                if(!empty($point)):
                                                    $pointIndex++;
                                            ?>
                                                <li style="--index: <?php echo $pointIndex; ?>"><?php echo htmlspecialchars($point); ?></li>
                                            <?php
                                                endif;
                                            endforeach;
                                            ?>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        <?php $id++; endforeach; ?>
                    </div>
                <?php else: ?>
                    <ul>
                        <?php 
                        $itemIndex = 0;
                        foreach($rows as $row):
                            $itemIndex++;
                            $points = array_filter(array_map('trim', explode('.', $row['description'])));
                        ?>
                            <li style="--index: <?php echo $itemIndex; ?>">
                                <b><?php echo htmlspecialchars($row['title']); ?></b>
                                <?php if(!empty($points)): ?>
                                    <ul>
                                        <?php 
                                        $subIndex = 0;
                                        foreach($points as $point):
                                            if(!empty($point)):
                                                $subIndex++;
                                        ?>
                                            <li style="--index: <?php echo $subIndex; ?>"><?php echo htmlspecialchars($point); ?></li>
                                        <?php 
                                            endif;
                                        endforeach; 
                                        ?>
                                    </ul>
                                <?php endif; ?>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>

    <?php elseif($crop != "" && empty($entries)): ?>
        <div class="card empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No Guide Available</h3>
            <p>We're working on adding comprehensive guides for this crop. Please check back soon!</p>
        </div>
    <?php endif; ?>

</div>

<!-- Search Filter Script -->
<script>
document.addEventListener('DOMContentLoaded', function(){
    const searchInput = document.getElementById('search');
    if(searchInput){
        let searchTimeout;
        
        searchInput.addEventListener('input', function(){
            clearTimeout(searchTimeout);
            const query = this.value.toLowerCase().trim();
            
            searchTimeout = setTimeout(() => {
                const accordionItems = document.querySelectorAll('.accordion-item');
                const listItems = document.querySelectorAll('.card ul > li');
                let hasResults = false;
                
                // Search in accordion items
                accordionItems.forEach(function(item){
                    const text = item.textContent.toLowerCase();
                    if(text.includes(query) || query === ''){
                        item.style.display = 'block';
                        hasResults = true;
                        
                        // Highlight matching text
                        if(query !== ''){
                            highlightText(item, query);
                        } else {
                            removeHighlight(item);
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Search in list items
                listItems.forEach(function(item){
                    const text = item.textContent.toLowerCase();
                    if(text.includes(query) || query === ''){
                        item.style.display = 'block';
                        hasResults = true;
                        
                        if(query !== ''){
                            highlightText(item, query);
                        } else {
                            removeHighlight(item);
                        }
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Show/hide parent cards based on results
                document.querySelectorAll('.card').forEach(function(card){
                    if(card.querySelector('.accordion') || card.querySelector('ul')){
                        const visibleItems = card.querySelectorAll('.accordion-item[style*="block"], li[style*="block"]');
                        if(visibleItems.length === 0 && query !== ''){
                            card.style.display = 'none';
                        } else {
                            card.style.display = 'block';
                        }
                    }
                });
            }, 300);
        });
        
        function highlightText(element, query){
            const walker = document.createTreeWalker(
                element,
                NodeFilter.SHOW_TEXT,
                null,
                false
            );
            
            const textNodes = [];
            let node;
            while(node = walker.nextNode()){
                if(node.textContent.trim()){
                    textNodes.push(node);
                }
            }
            
            textNodes.forEach(textNode => {
                const text = textNode.textContent;
                const regex = new RegExp(`(${query})`, 'gi');
                if(regex.test(text)){
                    const highlighted = text.replace(regex, '<span class="highlight">$1</span>');
                    const wrapper = document.createElement('span');
                    wrapper.innerHTML = highlighted;
                    textNode.parentNode.replaceChild(wrapper, textNode);
                }
            });
        }
        
        function removeHighlight(element){
            const highlights = element.querySelectorAll('.highlight');
            highlights.forEach(highlight => {
                const parent = highlight.parentNode;
                parent.replaceChild(document.createTextNode(highlight.textContent), highlight);
                parent.normalize();
            });
        }
    }
    
    // Add smooth scroll behavior
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if(target){
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    // Add intersection observer for scroll animations
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries){
        entries.forEach(entry => {
            if(entry.isIntersecting){
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);
    
    document.querySelectorAll('.card, .accordion-item').forEach(el => {
        observer.observe(el);
    });
});
</script>

</body>
</html>
