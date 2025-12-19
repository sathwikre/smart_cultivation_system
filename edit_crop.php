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
?>

<h2>Edit Crop</h2>
<form method="POST">
    <label>Crop Name:</label>
    <input type="text" name="crop_name" value="<?php echo htmlspecialchars($crop['crop_name']); ?>" required><br><br>

    <label>Variety:</label>
    <input type="text" name="variety" value="<?php echo htmlspecialchars($crop['variety']); ?>" required><br><br>

    <label>Field / Plot:</label>
    <input type="text" name="field" value="<?php echo htmlspecialchars($crop['field']); ?>" required><br><br>

    <label>Planting Date:</label>
    <input type="date" name="planting_date" value="<?php echo $crop['planting_date']; ?>" required><br><br>

    <label>Growth Stage:</label>
    <input type="text" name="growth_stage" value="<?php echo htmlspecialchars($crop['growth_stage']); ?>" required><br><br>

    <button type="submit" name="update">Update Crop</button>
</form>
