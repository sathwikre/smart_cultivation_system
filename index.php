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
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
<!-- Font Awesome -->
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
        radial-gradient(circle at 80% 80%, rgba(76, 175, 80, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 152, 0, 0.02) 0%, transparent 50%);
    z-index: 0;
    pointer-events: none;
}

/* Language Switch */
.lang-switch {
    position: fixed;
    top: 24px;
    right: 24px;
    z-index: 1000;
    display: flex;
    gap: 8px;
    background: var(--bg-white);
    padding: 6px;
    border-radius: 12px;
    box-shadow: var(--shadow-md);
}

.lang-switch a {
    padding: 8px 16px;
    border-radius: 8px;
    color: var(--text-dark);
    font-weight: 500;
    font-size: 14px;
    text-decoration: none;
    transition: var(--transition);
    background: transparent;
}

.lang-switch a:hover,
.lang-switch a.active {
    background: var(--primary-green);
    color: white;
}

/* Container */
.container {
    position: relative;
    z-index: 1;
    max-width: 1200px;
    margin: 0 auto;
    padding: 80px 24px 60px;
}

/* Hero Section */
.hero {
    text-align: center;
    margin-bottom: 80px;
    animation: fadeInUp 0.8s ease-out;
}

.hero h1 {
    font-size: clamp(2.5rem, 5vw, 4rem);
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 20px;
    line-height: 1.2;
    letter-spacing: -0.02em;
}

.hero .subtitle {
    font-size: clamp(1.1rem, 2vw, 1.4rem);
    color: var(--text-light);
    margin-bottom: 48px;
    font-weight: 400;
    max-width: 700px;
    margin-left: auto;
    margin-right: auto;
}

/* Action Buttons */
.hero-actions {
    display: flex;
    gap: 16px;
    justify-content: center;
    flex-wrap: wrap;
    margin-bottom: 20px;
}

.btn {
    display: inline-flex;
    align-items: center;
    gap: 10px;
    padding: 16px 32px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    text-decoration: none;
    transition: var(--transition);
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.btn-primary {
    background: var(--primary-green);
    color: white;
}

.btn-primary:hover {
    background: var(--primary-green-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-secondary {
    background: var(--bg-white);
    color: var(--primary-green);
    border: 2px solid var(--primary-green);
}

.btn-secondary:hover {
    background: var(--primary-green);
    color: white;
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn i {
    font-size: 18px;
}

/* Features Section */
.features {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 24px;
    margin-top: 60px;
}

.feature-card {
    background: var(--bg-white);
    padding: 32px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    transition: var(--transition);
    border: 1px solid var(--border-color);
    position: relative;
    overflow: hidden;
    animation: fadeInUp 0.8s ease-out;
    animation-fill-mode: both;
}

.feature-card:nth-child(1) { animation-delay: 0.1s; }
.feature-card:nth-child(2) { animation-delay: 0.2s; }
.feature-card:nth-child(3) { animation-delay: 0.3s; }
.feature-card:nth-child(4) { animation-delay: 0.4s; }

.feature-card::before {
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

.feature-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-green-light);
}

.feature-card:hover::before {
    transform: scaleX(1);
}

.feature-card .icon-wrapper {
    width: 64px;
    height: 64px;
    border-radius: 12px;
    background: linear-gradient(135deg, rgba(45, 134, 89, 0.1), rgba(76, 175, 80, 0.1));
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 24px;
    transition: var(--transition);
}

.feature-card:hover .icon-wrapper {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    transform: scale(1.1) rotate(5deg);
}

.feature-card i {
    font-size: 28px;
    color: var(--primary-green);
    transition: var(--transition);
}

.feature-card:hover i {
    color: white;
}

.feature-card h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
    line-height: 1.3;
}

.feature-card p {
    font-size: 15px;
    color: var(--text-light);
    line-height: 1.6;
}

/* Decorative Elements */
.decorative-elements {
    position: fixed;
    width: 100%;
    height: 100%;
    top: 0;
    left: 0;
    z-index: 0;
    pointer-events: none;
    overflow: hidden;
}

.decorative-elements .element {
    position: absolute;
    opacity: 0.05;
    color: var(--primary-green);
}

.decorative-elements .element:nth-child(1) {
    top: 10%;
    left: 5%;
    font-size: 120px;
    animation: float 20s ease-in-out infinite;
}

.decorative-elements .element:nth-child(2) {
    top: 60%;
    right: 8%;
    font-size: 100px;
    animation: float 25s ease-in-out infinite reverse;
}

.decorative-elements .element:nth-child(3) {
    bottom: 15%;
    left: 10%;
    font-size: 80px;
    animation: float 18s ease-in-out infinite;
}

/* Animations */
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

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-30px) rotate(10deg);
    }
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 60px 20px 40px;
    }

    .hero {
        margin-bottom: 60px;
    }

    .hero-actions {
        flex-direction: column;
    }

    .btn {
        width: 100%;
        justify-content: center;
    }

    .features {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .lang-switch {
        top: 16px;
        right: 16px;
        padding: 4px;
    }

    .lang-switch a {
        padding: 6px 12px;
        font-size: 13px;
    }
}

@media (max-width: 480px) {
    .hero h1 {
        font-size: 2rem;
    }

    .hero .subtitle {
        font-size: 1rem;
    }

    .feature-card {
        padding: 24px;
    }
}

/* Smooth Scroll */
html {
    scroll-behavior: smooth;
}
</style>
</head>
<body>

<!-- Decorative Background Elements -->
<div class="decorative-elements">
    <i class="fas fa-seedling element"></i>
    <i class="fas fa-leaf element"></i>
    <i class="fas fa-tractor element"></i>
</div>

<!-- Language Toggle -->
<div class="lang-switch">
    <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
    <a href="?lang=te" class="<?php echo $lang === 'te' ? 'active' : ''; ?>">తెలుగు</a>
</div>

<div class="container">
    <!-- Hero Section -->
    <div class="hero">
        <h1><?php echo $translations['welcome']; ?></h1>
        <p class="subtitle"><?php echo $translations['subtitle'] ?? 'Your complete platform for smart cultivation and crop management'; ?></p>
        <div class="hero-actions">
            <a href="login.php" class="btn btn-primary">
                <i class="fas fa-sign-in-alt"></i>
                <?php echo $translations['login']; ?>
            </a>
            <a href="register.php" class="btn btn-secondary">
                <i class="fas fa-user-plus"></i>
                <?php echo $translations['register']; ?>
            </a>
        </div>
    </div>

    <!-- Features Section -->
    <div class="features">
        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fas fa-seedling"></i>
            </div>
            <h3><?php echo $translations['crop_management'] ?? 'Crop Management'; ?></h3>
            <p><?php echo $translations['crop_management_desc'] ?? 'Select crops, track growth steps and get guidance for better yield.'; ?></p>
        </div>
        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fas fa-book-open"></i>
            </div>
            <h3><?php echo $translations['knowledge_base'] ?? 'Knowledge Base'; ?></h3>
            <p><?php echo $translations['knowledge_base_desc'] ?? 'Read tips, tutorials, and expert advice for smarter cultivation.'; ?></p>
        </div>
        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fas fa-bell"></i>
            </div>
            <h3><?php echo $translations['notifications'] ?? 'Notifications'; ?></h3>
            <p><?php echo $translations['notifications_desc'] ?? 'Receive reminders for watering, fertilizing and important crop tasks.'; ?></p>
        </div>
        <div class="feature-card">
            <div class="icon-wrapper">
                <i class="fas fa-chart-line"></i>
            </div>
            <h3><?php echo $translations['reports'] ?? 'Reports & Analytics'; ?></h3>
            <p><?php echo $translations['reports_desc'] ?? 'View crop growth stats, step completion and visual insights for better planning.'; ?></p>
        </div>
    </div>
</div>

</body>
</html>
