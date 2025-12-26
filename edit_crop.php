<?php
session_start();
require 'db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

if(!isset($_GET['id'])){
    header("Location: crop_management.php");
    exit;
}

$crop_id = $_GET['id'];

// Fetch crop info
$stmt = $conn->prepare("SELECT * FROM farmer_crops WHERE id=?");
$stmt->bind_param("i", $crop_id);
$stmt->execute();
$crop = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$crop){
    echo "Crop not found.";
    exit;
}

// Handle form submission
if(isset($_POST['update'])){
    $crop_name = $_POST['crop_name'];
    $variety = $_POST['variety'];
    $field = $_POST['field'];
    $planting_date = $_POST['planting_date'];
    $growth_stage = $_POST['growth_stage'];

    $update_stmt = $conn->prepare("UPDATE farmer_crops SET crop_name=?, variety=?, field=?, planting_date=?, growth_stage=?, last_updated=NOW() WHERE id=?");
    $update_stmt->bind_param("sssssi", $crop_name, $variety, $field, $planting_date, $growth_stage, $crop_id);
    $update_stmt->execute();
    $update_stmt->close();

    header("Location: crop_management.php");
    exit;
}

// Growth stages
$stages = ['Seed', 'Germination', 'Vegetative', 'Flowering', 'Harvest'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Crop | Admin</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">
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
    padding: 40px 20px;
    position: relative;
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
    max-width: 600px;
    margin: 0 auto;
    position: relative;
    z-index: 1;
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Back Button */
.back-btn {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 24px;
    padding: 12px 24px;
    background: white;
    color: var(--primary-green-dark);
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}

.back-btn:hover {
    background: var(--primary-green);
    color: white;
    transform: translateX(-4px);
    box-shadow: var(--shadow-md);
}

/* Header */
.header {
    text-align: center;
    background: white;
    padding: 30px 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
    margin-bottom: 24px;
}

.header h2 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 12px;
    margin: 0;
}

.header h2 i {
    color: var(--primary-green);
    font-size: 36px;
}

/* Form */
form {
    background: white;
    padding: 40px;
    border-radius: 16px;
    box-shadow: var(--shadow-md);
    border: 2px solid transparent;
    position: relative;
    overflow: hidden;
    animation: scaleIn 0.4s ease-out 0.2s both;
}

@keyframes scaleIn {
    from {
        opacity: 0;
        transform: scale(0.95);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

form::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    width: 100%;
    height: 4px;
    background: linear-gradient(90deg, var(--primary-green), var(--secondary-green));
}

/* Form Groups */
.form-group {
    margin-bottom: 24px;
    animation: slideInLeft 0.4s ease-out;
    animation-fill-mode: both;
}

.form-group:nth-child(1) { animation-delay: 0.1s; }
.form-group:nth-child(2) { animation-delay: 0.2s; }
.form-group:nth-child(3) { animation-delay: 0.3s; }
.form-group:nth-child(4) { animation-delay: 0.4s; }
.form-group:nth-child(5) { animation-delay: 0.5s; }

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

label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    color: var(--text-dark);
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
}

label i {
    color: var(--primary-green);
    font-size: 16px;
}

input[type="text"],
input[type="date"],
select {
    width: 100%;
    padding: 14px 16px;
    border-radius: 10px;
    border: 2px solid var(--border-color);
    font-size: 15px;
    font-family: inherit;
    transition: var(--transition);
    background: white;
    color: var(--text-dark);
    outline: none;
}

input::placeholder {
    color: var(--text-light);
}

input:focus,
select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

/* Input Wrapper with Icon */
.input-wrapper {
    position: relative;
}

.input-wrapper i {
    position: absolute;
    left: 16px;
    top: 50%;
    transform: translateY(-50%);
    color: var(--text-light);
    font-size: 18px;
    pointer-events: none;
}

.input-wrapper input,
.input-wrapper select {
    padding-left: 48px;
}

/* Button */
button[type="submit"] {
    width: 100%;
    padding: 16px 32px;
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    border: none;
    border-radius: 12px;
    font-weight: 700;
    font-size: 16px;
    cursor: pointer;
    transition: var(--transition);
    box-shadow: var(--shadow-md);
    margin-top: 8px;
    position: relative;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    animation: fadeInUp 0.6s ease-out 0.6s both;
}

button[type="submit"]::before {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 0;
    height: 0;
    border-radius: 50%;
    background: rgba(255, 255, 255, 0.3);
    transform: translate(-50%, -50%);
    transition: width 0.6s, height 0.6s;
}

button[type="submit"]:hover::before {
    width: 400px;
    height: 400px;
}

button[type="submit"] span {
    position: relative;
    z-index: 1;
}

button[type="submit"]:hover {
    background: linear-gradient(135deg, var(--primary-green-light), var(--primary-green));
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

button[type="submit"]:active {
    transform: translateY(-1px);
}

/* Responsive */
@media (max-width: 768px) {
    body {
        padding: 20px 16px;
    }

    .header {
        padding: 24px;
    }

    .header h2 {
        font-size: 24px;
    }

    form {
        padding: 32px 24px;
    }
}

@media (max-width: 480px) {
    .header h2 {
        flex-direction: column;
        gap: 8px;
    }

    form {
        padding: 24px 20px;
    }
}
</style>
</head>

<body>

<div class="container">
    <a href="crop_management.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Crop Management
    </a>

    <div class="header">
        <h2>
            <i class="fas fa-seedling"></i>
            Edit Crop
        </h2>
    </div>

    <form method="POST">
        <div class="form-group">
            <label>
                <i class="fas fa-seedling"></i>
                Crop Name
            </label>
            <div class="input-wrapper">
                <i class="fas fa-seedling"></i>
                <input type="text" name="crop_name" value="<?php echo htmlspecialchars($crop['crop_name']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-leaf"></i>
                Variety
            </label>
            <div class="input-wrapper">
                <i class="fas fa-leaf"></i>
                <input type="text" name="variety" value="<?php echo htmlspecialchars($crop['variety']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-map-marked-alt"></i>
                Field / Plot
            </label>
            <div class="input-wrapper">
                <i class="fas fa-map-marked-alt"></i>
                <input type="text" name="field" value="<?php echo htmlspecialchars($crop['field']); ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-calendar-alt"></i>
                Planting Date
            </label>
            <div class="input-wrapper">
                <i class="fas fa-calendar-alt"></i>
                <input type="date" name="planting_date" value="<?php echo $crop['planting_date']; ?>" required>
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-chart-line"></i>
                Growth Stage
            </label>
            <div class="input-wrapper">
                <i class="fas fa-chart-line"></i>
                <select name="growth_stage" required>
                    <option value="">-- Select Growth Stage --</option>
                    <?php foreach($stages as $stage): ?>
                        <option value="<?php echo $stage; ?>" <?php echo ($crop['growth_stage'] == $stage) ? 'selected' : ''; ?>>
                            <?php echo $stage; ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <button type="submit" name="update">
            <i class="fas fa-save"></i>
            <span>Update Crop</span>
        </button>
    </form>
</div>

</body>
</html>
