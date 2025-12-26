<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'farmer') {
    header("Location: login.php");
    exit;
}

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    $user_id = $_SESSION['user_id'];
    $crop_name = $_POST['crop_name'];
    $variety = $_POST['variety'];
    $field = $_POST['field'];
    $planting_date = $_POST['planting_date'];
    $growth_stage = $_POST['growth_stage'];

    $stmt = $conn->prepare("
        INSERT INTO farmer_crops 
        (user_id, crop_name, variety, field, planting_date, growth_stage)
        VALUES (?, ?, ?, ?, ?, ?)
    ");

    $stmt->bind_param("isssss", $user_id, $crop_name, $variety, $field, $planting_date, $growth_stage);

    if ($stmt->execute()) {
        $message = "🌱 Crop added successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }

    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add New Crop | Smart Cultivation System</title>
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
    --text-dark: #2c3e50;
    --text-light: #5a6c7d;
    --bg-light: #f8f9fa;
    --bg-white: #ffffff;
    --border-color: #e0e0e0;
    --error-color: #e74c3c;
    --success-color: #27ae60;
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
    display: flex;
    align-items: center;
    justify-content: center;
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
    width: 100%;
    max-width: 600px;
    background: var(--bg-white);
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    padding: 48px;
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
}

.header p {
    color: var(--text-light);
    font-size: 15px;
}

/* Message Alert */
.msg {
    padding: 14px 18px;
    border-radius: 12px;
    margin-bottom: 24px;
    font-size: 14px;
    font-weight: 500;
    display: flex;
    align-items: center;
    gap: 10px;
    animation: slideDown 0.3s ease-out;
}

@keyframes slideDown {
    from {
        opacity: 0;
        transform: translateY(-10px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.msg.success {
    background: #e8f5e9;
    color: var(--success-color);
    border: 1px solid #c8e6c9;
}

.msg.error {
    background: #fee;
    color: var(--error-color);
    border: 1px solid #fcc;
}

.msg i {
    font-size: 18px;
}

/* Form Groups */
.form-group {
    margin-bottom: 24px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 8px;
}

.form-group label .required {
    color: var(--error-color);
    margin-left: 3px;
}

.form-group input,
.form-group select {
    width: 100%;
    padding: 14px 16px;
    font-size: 15px;
    font-family: inherit;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    background: var(--bg-white);
    color: var(--text-dark);
    transition: var(--transition);
    outline: none;
}

.form-group input:focus,
.form-group select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

.form-group input::placeholder {
    color: #999;
}

/* Form Row (for side-by-side fields if needed) */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Button */
.btn {
    width: 100%;
    padding: 16px;
    font-size: 16px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    margin-top: 8px;
}

.btn-primary {
    background: var(--primary-green);
    color: white;
    box-shadow: var(--shadow-sm);
}

.btn-primary:hover {
    background: var(--primary-green-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-primary:active {
    transform: translateY(0);
}

.btn-primary i {
    font-size: 18px;
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-light);
    text-decoration: none;
    font-size: 14px;
    margin-top: 24px;
    transition: var(--transition);
    width: 100%;
    justify-content: center;
    padding: 12px;
    border-radius: 8px;
}

.back-link:hover {
    color: var(--primary-green);
    background: var(--bg-light);
}

.back-link i {
    font-size: 14px;
}

/* Responsive Design */
@media (max-width: 768px) {
    .container {
        padding: 32px 24px;
        margin: 10px;
    }

    .header h1 {
        font-size: 28px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .container {
        padding: 24px 20px;
    }

    .header h1 {
        font-size: 24px;
    }
}

/* Icon decoration */
.icon-decoration {
    position: fixed;
    font-size: 8rem;
    color: rgba(45, 134, 89, 0.03);
    z-index: 0;
    pointer-events: none;
}

.icon-decoration:nth-child(1) {
    top: 10%;
    left: 5%;
    animation: float 20s ease-in-out infinite;
}

.icon-decoration:nth-child(2) {
    bottom: 10%;
    right: 5%;
    animation: float 25s ease-in-out infinite reverse;
}

@keyframes float {
    0%, 100% {
        transform: translateY(0) rotate(0deg);
    }
    50% {
        transform: translateY(-30px) rotate(10deg);
    }
}
</style>
</head>
<body>

<!-- Decorative Icons -->
<i class="fas fa-seedling icon-decoration"></i>
<i class="fas fa-leaf icon-decoration"></i>

<div class="container">
    <!-- Back Link -->
    <a href="farmer_dashboard.php" class="back-link" style="margin-top: 0; margin-bottom: 24px; width: auto; justify-content: flex-start;">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <!-- Header -->
    <div class="header">
        <h1>
            <i class="fas fa-seedling"></i>
            Add New Crop
        </h1>
        <p>Enter the details of your new crop to start tracking its growth</p>
    </div>

    <!-- Message Alert -->
    <?php if ($message): ?>
    <div class="msg <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
        <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <!-- Form -->
    <form method="POST" id="addCropForm">
        <div class="form-group">
            <label for="crop_name">Crop Name <span class="required">*</span></label>
            <input 
                type="text" 
                id="crop_name" 
                name="crop_name" 
                placeholder="e.g., Tomato, Rice, Wheat" 
                required
                autofocus
            >
        </div>

        <div class="form-group">
            <label for="variety">Variety <span class="required">*</span></label>
            <input 
                type="text" 
                id="variety" 
                name="variety" 
                placeholder="e.g., Hybrid, Local, Improved" 
                required
            >
        </div>

        <div class="form-group">
            <label for="field">Field / Plot Location <span class="required">*</span></label>
            <input 
                type="text" 
                id="field" 
                name="field" 
                placeholder="e.g., Field A, Plot 1, North Section" 
                required
            >
        </div>

        <div class="form-group">
            <label for="planting_date">Planting Date <span class="required">*</span></label>
            <input 
                type="date" 
                id="planting_date" 
                name="planting_date" 
                required
            >
        </div>

        <div class="form-group">
            <label for="growth_stage">Growth Stage <span class="required">*</span></label>
            <select id="growth_stage" name="growth_stage" required>
                <option value="" disabled selected>Select current growth stage</option>
                <option value="Seed">🌱 Seed</option>
                <option value="Germination">🌿 Germination</option>
                <option value="Vegetative">🌳 Vegetative</option>
                <option value="Flowering">🌸 Flowering</option>
                <option value="Harvest">🌾 Harvest</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i>
            Add Crop
        </button>
    </form>
</div>

<script>
// Set today's date as max for planting date
document.addEventListener('DOMContentLoaded', function() {
    const plantingDateInput = document.getElementById('planting_date');
    if (plantingDateInput) {
        const today = new Date().toISOString().split('T')[0];
        plantingDateInput.setAttribute('max', today);
    }
});
</script>

</body>
</html>
