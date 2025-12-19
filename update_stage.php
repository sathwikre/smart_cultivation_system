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
<title>Update Crop Stage</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg,#00d2ff,#3a47d5,#00ff87,#00d2ff);
    background-size: 400% 400%;
    animation: bgAnimation 12s ease infinite;
    color: #fff;
}
@keyframes bgAnimation {
    0%{background-position:0% 50%;}
    50%{background-position:100% 50%;}
    100%{background-position:0% 50%;}
}

/* Container */
.container {
    max-width: 900px;
    margin: 50px auto;
    padding: 30px;
    background: rgba(255,255,255,0.12);
    border-radius: 25px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    backdrop-filter: blur(12px);
    animation: fadeIn 1s ease;
}
@keyframes fadeIn {
    from {opacity:0; transform:translateY(25px);}
    to {opacity:1; transform:translateY(0);}
}

h2 {text-align:center; margin-bottom:20px;}

/* Message Box */
.msg {
    text-align:center; 
    margin-bottom:20px; 
    font-weight:600; 
    background: rgba(0,0,0,0.2); 
    padding:12px; 
    border-radius:10px;
}

/* Table */
table {width:100%; border-collapse:collapse;}
th, td {padding:12px; text-align:center; border-bottom:1px solid rgba(255,255,255,0.2);}
tr:hover {background: rgba(255,255,255,0.15);}

/* Select */
select {
    padding:6px 8px; 
    border-radius:10px; 
    border:none; 
    outline:none; 
    background: rgba(255,255,255,0.15); 
    color:#fff;
    transition:0.3s;
}
select:focus {background: rgba(255,255,255,0.25); transform: scale(1.05);}

/* Gradient Update Button */
button {
    padding:8px 15px; 
    border:none; 
    border-radius:12px; 
    background: linear-gradient(135deg,#ffb100,#ff6b00);
    font-weight:700; 
    cursor:pointer; 
    transition:0.3s;
}
button:hover {box-shadow:0 0 20px #ffb100; transform:scale(1.05);}

/* Back Link */
.back-link {
    display:block; 
    text-align:center; 
    margin-top:20px; 
    color:#fff; 
    text-decoration:none; 
    font-weight:600;
}
.back-link:hover {color:#ffd700;}
</style>
</head>

<body>
<div class="container">
    <h2>🌿 Update Crop Stage</h2>

    <?php if ($message) echo "<div class='msg'>$message</div>"; ?>

    <table>
        <tr>
            <th>Crop Name</th>
            <th>Variety</th>
            <th>Field</th>
            <th>Planting Date</th>
            <th>Current Stage</th>
            <th>Update Stage</th>
        </tr>

        <?php foreach ($crops as $c): ?>
        <tr>
            <td><?php echo $c['crop_name']; ?></td>
            <td><?php echo $c['variety']; ?></td>
            <td><?php echo $c['field']; ?></td>
            <td><?php echo $c['planting_date']; ?></td>
            <td><?php echo $c['growth_stage']; ?></td>
            <td>
                <form method="POST" style="display:flex; gap:5px; justify-content:center;">
                    <input type="hidden" name="crop_id" value="<?php echo $c['id']; ?>">
                    <select name="growth_stage" required>
                        <option value="" disabled selected>Change Stage</option>
                        <option value="Seed">Seed</option>
                        <option value="Germination">Germination</option>
                        <option value="Vegetative">Vegetative</option>
                        <option value="Flowering">Flowering</option>
                        <option value="Harvest">Harvest</option>
                    </select>
                    <button type="submit" name="update_stage">Update</button>
                </form>
            </td>
        </tr>
        <?php endforeach; ?>
    </table>

    <a href="farmer_dashboard.php" class="back-link">⬅ Back to Dashboard</a>
</div>
</body>
</html>
