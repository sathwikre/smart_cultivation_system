<?php
session_start();

// Language toggle
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: index.php");
    exit;
}

// Default language
$lang = $_SESSION['lang'] ?? 'en';
$translations = include "languages/$lang.php";
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Smart Cultivation System</title>
<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Reset */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{overflow-x:hidden;}

/* Full Screen Gradient Background */
body::before{
    content:"";
    position:fixed;
    top:0; left:0;
    width:100%; height:100%;
    background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff);
    background-size:400% 400%;
    animation: gradientBG 20s ease infinite;
    z-index:-2;
}

@keyframes gradientBG{
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

/* Language Switch */
.lang-switch{
    position:absolute; top:20px; right:30px; z-index:10;
}
.lang-switch a{
    margin:0 10px; padding:8px 15px; border-radius:25px; 
    background:rgba(255,255,255,0.3); color:#fff; font-weight:600;
    transition:0.3s;
}
.lang-switch a:hover{background:rgba(255,255,255,0.6);}

/* Container */
.container{display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; padding:20px; text-align:center;}

/* Hero Heading */
.hero h1{
    font-size:5rem;
    font-weight:900;
    text-transform:uppercase;
    background: linear-gradient(90deg,#ffd700,#ff8c00,#ff0000,#ffd700);
    background-size:400%;
    -webkit-background-clip:text;
    -webkit-text-fill-color:transparent;
    animation: gradientText 6s linear infinite, floatHeading 3s ease-in-out infinite;
    margin-bottom:20px;
}

@keyframes gradientText{
    0%{background-position:0%;}
    50%{background-position:100%;}
    100%{background-position:0%;}
}

@keyframes floatHeading{
    0%,100%{transform:translateY(0);}
    50%{transform:translateY(-15px);}
}

/* Subtitle */
.hero p{
    font-size:1.5rem;
    margin-bottom:40px;
    color:rgba(255,255,255,0.9);
    animation: fadeIn 2s ease-in-out;
}

/* Buttons */
.btn{
    display:inline-block;
    margin:10px;
    padding:18px 45px;
    font-size:1.2rem;
    font-weight:700;
    border:none;
    border-radius:50px;
    cursor:pointer;
    transition:0.4s;
    background: linear-gradient(45deg,#ffd700,#ff8c00,#ff0000);
    color:#fff;
    box-shadow:0 8px 25px rgba(0,0,0,0.4);
    position:relative;
    overflow:hidden;
}
.btn:before{
    content:'';
    position:absolute;
    top:0; left:-100%;
    width:100%; height:100%;
    background:rgba(255,255,255,0.2);
    transition:0.4s;
}
.btn:hover:before{left:100%;}
.btn:hover{transform:translateY(-5px); box-shadow:0 12px 35px rgba(0,0,0,0.5);}

/* Features Grid */
.features{
    display:grid;
    grid-template-columns: repeat(auto-fit,minmax(250px,1fr));
    gap:35px;
    margin-top:60px;
    animation: fadeIn 2s ease-in-out;
    width:100%;
}

.feature-card{
    background: rgba(255,255,255,0.1);
    padding:30px;
    border-radius:25px;
    backdrop-filter: blur(10px);
    transition:0.4s;
    transform: translateY(0);
}
.feature-card:hover{
    transform: translateY(-12px) scale(1.05);
    box-shadow:0 20px 40px rgba(0,0,0,0.5);
}
.feature-card i{
    font-size:3.5rem;
    margin-bottom:15px;
    color:#ffd700;
    animation: floatIcon 3s ease-in-out infinite;
}
@keyframes floatIcon{
    0%,100%{transform: translateY(0);}
    50%{transform: translateY(-12px);}
}
.feature-card h3{margin-bottom:15px; font-size:1.5rem; font-weight:700;}
.feature-card p{font-size:1rem; line-height:1.5; color: #f0f0f0;}

/* Floating background icons */
.icon-bg{
    position:fixed;
    font-size:2.5rem;
    color:rgba(255,255,255,0.15);
    animation: floatBg 15s linear infinite;
    z-index:-1;
}
.icon-bg:nth-child(1){top:10%; left:5%; animation-duration:20s;}
.icon-bg:nth-child(2){top:30%; left:85%; animation-duration:18s;}
.icon-bg:nth-child(3){top:70%; left:10%; animation-duration:22s;}
.icon-bg:nth-child(4){top:50%; left:50%; animation-duration:25s;}
.icon-bg:nth-child(5){top:80%; left:80%; animation-duration:30s;}
@keyframes floatBg{
    0%{transform: translateY(0) rotate(0deg);}
    50%{transform: translateY(-30px) rotate(180deg);}
    100%{transform: translateY(0) rotate(360deg);}
}

/* Animations */
@keyframes fadeIn{
    0%{opacity:0; transform: translateY(20px);}
    100%{opacity:1; transform: translateY(0);}
}

@media(max-width:600px){
    .hero h1{font-size:3.2rem;}
    .hero p{font-size:1.2rem;}
}
</style>
</head>
<body>

<!-- Floating Background Icons -->
<i class="fa-solid fa-seedling icon-bg"></i>
<i class="fa-solid fa-droplet icon-bg"></i>
<i class="fa-solid fa-sun icon-bg"></i>
<i class="fa-solid fa-tractor icon-bg"></i>
<i class="fa-solid fa-leaf icon-bg"></i>

<!-- Language Toggle -->
<div class="lang-switch">
    <a href="?lang=en">English</a> | <a href="?lang=te">తెలుగు</a>
</div>

<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <h1><?php echo $translations['welcome']; ?></h1>
        <p><?php echo $translations['subtitle'] ?? 'Your complete platform for smart cultivation and crop management'; ?></p>
        <a href="login.php" class="btn"><?php echo $translations['login']; ?></a>
        <a href="register.php" class="btn"><?php echo $translations['register']; ?></a>
    </div>

    <!-- Features Section -->
    <div class="features">
        <div class="feature-card">
            <i class="fa-solid fa-seedling"></i>
            <h3><?php echo $translations['crop_management'] ?? 'Crop Management'; ?></h3>
            <p><?php echo $translations['crop_management_desc'] ?? 'Select crops, track growth steps and get guidance for better yield.'; ?></p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-lightbulb"></i>
            <h3><?php echo $translations['knowledge_base'] ?? 'Knowledge Base'; ?></h3>
            <p><?php echo $translations['knowledge_base_desc'] ?? 'Read tips, tutorials, and expert advice for smarter cultivation.'; ?></p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-bell"></i>
            <h3><?php echo $translations['notifications'] ?? 'Notifications'; ?></h3>
            <p><?php echo $translations['notifications_desc'] ?? 'Receive reminders for watering, fertilizing and important crop tasks.'; ?></p>
        </div>
        <div class="feature-card">
            <i class="fa-solid fa-chart-line"></i>
            <h3><?php echo $translations['reports'] ?? 'Reports & Analytics'; ?></h3>
            <p><?php echo $translations['reports_desc'] ?? 'View crop growth stats, step completion and visual insights for better planning.'; ?></p>
        </div>
    </div>
</div>

</body>
</html>
