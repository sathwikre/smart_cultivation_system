<?php
session_start();
require 'db_connection.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] != 'farmer') {
    header("Location: login.php");
    exit;
}

$message = "";

// Update crop stage
if (isset($_POST['update_stage'])) {
    $crop_id = $_POST['crop_id'];
    $new_stage = $_POST['growth_stage'];

    $stmt = $conn->prepare("UPDATE farmer_crops SET growth_stage=?, last_updated=NOW() WHERE id=? AND user_id=?");
    $stmt->bind_param("sii", $new_stage, $crop_id, $_SESSION['user_id']);
    if ($stmt->execute()) {
        $message = "🌱 Crop stage updated successfully!";
    } else {
        $message = "❌ Error: " . $stmt->error;
    }
    $stmt->close();
}

// Fetch farmer crops
$stmt = $conn->prepare("SELECT id, crop_name, variety, field, planting_date, growth_stage FROM farmer_crops WHERE user_id=?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$result = $stmt->get_result();
$crops = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Update Crop Stage | Smart Cultivation System</title>
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
    max-width: 1000px;
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

/* Crop Cards */
.crops-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 24px;
    margin-bottom: 40px;
}

.crop-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    box-shadow: var(--shadow-sm);
    border: 2px solid transparent;
    transition: var(--transition);
    animation: fadeInUp 0.6s ease-out;
    animation-fill-mode: both;
    position: relative;
    overflow: hidden;
}

.crop-card::before {
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

.crop-card:nth-child(1) { animation-delay: 0.1s; }
.crop-card:nth-child(2) { animation-delay: 0.2s; }
.crop-card:nth-child(3) { animation-delay: 0.3s; }
.crop-card:nth-child(4) { animation-delay: 0.4s; }
.crop-card:nth-child(5) { animation-delay: 0.5s; }

.crop-card:hover {
    transform: translateY(-8px);
    box-shadow: var(--shadow-lg);
    border-color: var(--primary-green-light);
}

.crop-card:hover::before {
    transform: scaleX(1);
}

.crop-card-header {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 20px;
    padding-bottom: 16px;
    border-bottom: 2px solid var(--bg-light);
}

.crop-card-header i {
    font-size: 28px;
    color: var(--primary-green);
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(45, 134, 89, 0.1);
    border-radius: 12px;
    transition: var(--transition);
    animation: pulse 2s ease-in-out infinite;
}

@keyframes pulse {
    0%, 100% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
}

.crop-card:hover .crop-card-header i {
    background: rgba(45, 134, 89, 0.2);
    transform: scale(1.1) rotate(5deg);
}

.crop-card-header h3 {
    font-size: 18px;
    font-weight: 700;
    color: var(--text-dark);
    flex: 1;
}

.crop-info {
    margin-bottom: 20px;
}

.info-item {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 12px;
    font-size: 14px;
    animation: slideInLeft 0.4s ease-out;
    animation-fill-mode: both;
}

.info-item:nth-child(1) { animation-delay: 0.1s; }
.info-item:nth-child(2) { animation-delay: 0.2s; }
.info-item:nth-child(3) { animation-delay: 0.3s; }
.info-item:nth-child(4) { animation-delay: 0.4s; }

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

.info-item i {
    color: var(--primary-green);
    width: 20px;
    text-align: center;
    transition: var(--transition);
}

.info-item:hover i {
    transform: scale(1.2);
    color: var(--primary-green-light);
}

.info-item strong {
    color: var(--text-dark);
    min-width: 100px;
}

.info-item span {
    color: var(--text-light);
}

.current-stage {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 8px 14px;
    background: linear-gradient(135deg, rgba(45, 134, 89, 0.1), rgba(76, 175, 80, 0.1));
    border-radius: 20px;
    font-size: 13px;
    font-weight: 700;
    color: var(--primary-green);
    border: 2px solid rgba(45, 134, 89, 0.2);
    transition: var(--transition);
    animation: fadeIn 0.5s ease-out;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: scale(0.9);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

.current-stage:hover {
    background: linear-gradient(135deg, rgba(45, 134, 89, 0.2), rgba(76, 175, 80, 0.2));
    transform: scale(1.05);
}

/* Update Form */
.update-form {
    margin-top: 20px;
    padding-top: 20px;
    border-top: 2px solid var(--bg-light);
}

.form-row {
    display: flex;
    gap: 12px;
    align-items: flex-end;
}

.form-group {
    flex: 1;
}

.form-group label {
    display: block;
    font-size: 12px;
    font-weight: 600;
    color: var(--text-dark);
    margin-bottom: 6px;
}

.form-group select {
    width: 100%;
    padding: 12px 16px;
    font-size: 14px;
    font-family: inherit;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    background: var(--bg-white);
    color: var(--text-dark);
    transition: var(--transition);
    outline: none;
    cursor: pointer;
    font-weight: 500;
}

.form-group select:hover {
    border-color: var(--primary-green-light);
}

.form-group select:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
    transform: translateY(-2px);
}

.btn {
    padding: 12px 24px;
    font-size: 14px;
    font-weight: 700;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 8px;
    white-space: nowrap;
    position: relative;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
}

.btn::before {
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

.btn:hover::before {
    width: 300px;
    height: 300px;
}

.btn span, .btn i {
    position: relative;
    z-index: 1;
}

.btn-primary {
    background: linear-gradient(135deg, var(--primary-green), var(--secondary-green));
    color: white;
    box-shadow: var(--shadow-md);
}

.btn-primary:hover {
    background: linear-gradient(135deg, var(--primary-green-light), var(--primary-green));
    transform: translateY(-3px);
    box-shadow: var(--shadow-lg);
}

.btn-primary:active {
    transform: translateY(-1px);
}

/* Empty State */
.empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: var(--shadow-sm);
}

.empty-state i {
    font-size: 64px;
    color: var(--text-light);
    margin-bottom: 20px;
    opacity: 0.5;
}

.empty-state h3 {
    font-size: 20px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
}

.empty-state p {
    color: var(--text-light);
    margin-bottom: 24px;
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--primary-green-dark);
    text-decoration: none;
    font-size: 15px;
    font-weight: 600;
    margin-bottom: 24px;
    transition: var(--transition);
    padding: 12px 24px;
    background: white;
    border-radius: 12px;
    box-shadow: var(--shadow-sm);
}

.back-link:hover {
    color: white;
    background: var(--primary-green);
    transform: translateX(-4px);
    box-shadow: var(--shadow-md);
}

.back-link i {
    font-size: 16px;
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

    .crops-grid {
        grid-template-columns: 1fr;
        gap: 20px;
    }

    .form-row {
        flex-direction: column;
        align-items: stretch;
    }

    .btn {
        width: 100%;
        justify-content: center;
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

    .crop-card {
        padding: 20px;
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
    <a href="farmer_dashboard.php" class="back-link" style="margin-bottom: 24px;">
        <i class="fas fa-arrow-left"></i>
        Back to Dashboard
    </a>

    <!-- Header -->
    <div class="header">
        <h1>
            <i class="fas fa-sync-alt"></i>
            Update Crop Stage
        </h1>
        <p>Update the growth stage of your crops to track their progress</p>
    </div>

    <!-- Message Alert -->
    <?php if ($message): ?>
    <div class="msg <?php echo strpos($message, 'successfully') !== false ? 'success' : 'error'; ?>">
        <i class="fas <?php echo strpos($message, 'successfully') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <!-- Crops Grid -->
    <?php if (count($crops) > 0): ?>
    <div class="crops-grid">
        <?php foreach ($crops as $c): ?>
        <div class="crop-card">
            <div class="crop-card-header">
                <i class="fas fa-seedling"></i>
                <h3><?php echo htmlspecialchars($c['crop_name']); ?></h3>
            </div>

            <div class="crop-info">
                <div class="info-item">
                    <i class="fas fa-tag"></i>
                    <strong>Variety:</strong>
                    <span><?php echo htmlspecialchars($c['variety']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-map-marker-alt"></i>
                    <strong>Field:</strong>
                    <span><?php echo htmlspecialchars($c['field']); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-calendar-alt"></i>
                    <strong>Planted:</strong>
                    <span><?php echo date('d M Y', strtotime($c['planting_date'])); ?></span>
                </div>
                <div class="info-item">
                    <i class="fas fa-chart-line"></i>
                    <strong>Current Stage:</strong>
                    <span class="current-stage">
                        <?php 
                        $stageIcons = [
                            'Seed' => '🌱',
                            'Germination' => '🌿',
                            'Vegetative' => '🌳',
                            'Flowering' => '🌸',
                            'Harvest' => '🌾'
                        ];
                        echo ($stageIcons[$c['growth_stage']] ?? '') . ' ' . htmlspecialchars($c['growth_stage']);
                        ?>
                    </span>
                </div>
            </div>

            <div class="update-form">
                <form method="POST">
                    <input type="hidden" name="crop_id" value="<?php echo $c['id']; ?>">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="growth_stage_<?php echo $c['id']; ?>">Update to:</label>
                            <select id="growth_stage_<?php echo $c['id']; ?>" name="growth_stage" required>
                                <option value="" disabled selected>Select new stage</option>
                                <option value="Seed" <?php echo $c['growth_stage'] == 'Seed' ? 'disabled' : ''; ?>>🌱 Seed</option>
                                <option value="Germination" <?php echo $c['growth_stage'] == 'Germination' ? 'disabled' : ''; ?>>🌿 Germination</option>
                                <option value="Vegetative" <?php echo $c['growth_stage'] == 'Vegetative' ? 'disabled' : ''; ?>>🌳 Vegetative</option>
                                <option value="Flowering" <?php echo $c['growth_stage'] == 'Flowering' ? 'disabled' : ''; ?>>🌸 Flowering</option>
                                <option value="Harvest" <?php echo $c['growth_stage'] == 'Harvest' ? 'disabled' : ''; ?>>🌾 Harvest</option>
                            </select>
                        </div>
                        <button type="submit" name="update_stage" class="btn btn-primary">
                            <i class="fas fa-sync-alt"></i>
                            <span>Update</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php else: ?>
    <div class="empty-state">
        <i class="fas fa-seedling"></i>
        <h3>No Crops Found</h3>
        <p>You haven't added any crops yet. Add your first crop to get started!</p>
        <a href="add_crop.php" class="btn btn-primary" style="display: inline-flex; text-decoration: none;">
            <i class="fas fa-plus-circle"></i>
            <span>Add New Crop</span>
        </a>
    </div>
    <?php endif; ?>
</div>

</body>
</html>
