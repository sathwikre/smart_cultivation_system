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
<title>Add New Crop</title>

<!-- Google Fonts -->
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
body {
    margin: 0;
    padding: 0;
    font-family: 'Poppins', sans-serif;
    background: linear-gradient(135deg, #00d2ff, #3a47d5, #00ff87, #00d2ff);
    background-size: 400% 400%;
    animation: bgAnimation 12s ease infinite;
    color: #fff;
}

/* Animated Gradient Background */
@keyframes bgAnimation {
    0% {background-position: 0% 50%;}
    50% {background-position: 100% 50%;}
    100% {background-position: 0% 50%;}
}

/* Center Card */
.container {
    max-width: 550px;
    margin: 70px auto;
    padding: 30px;
    background: rgba(255,255,255,0.12);
    border-radius: 25px;
    box-shadow: 0 10px 40px rgba(0,0,0,0.4);
    backdrop-filter: blur(12px);
    animation: fadeIn 1.2s ease;
}

/* Fade Animation */
@keyframes fadeIn {
    from {opacity: 0; transform: translateY(25px);}
    to {opacity: 1; transform: translateY(0);}
}

h2 {
    text-align: center;
    font-size: 2rem;
    margin-bottom: 20px;
    letter-spacing: 1px;
}

.msg {
    background: rgba(0,0,0,0.2);
    padding: 12px;
    border-radius: 10px;
    text-align: center;
    margin-bottom: 15px;
    font-weight: 600;
}

/* Inputs */
input, select {
    width: 100%;
    padding: 12px;
    margin-top: 8px;
    margin-bottom: 18px;
    border: none;
    border-radius: 12px;
    background: rgba(255,255,255,0.15);
    color: #fff;
    font-size: 1rem;
    outline: none;
    backdrop-filter: blur(8px);
    transition: 0.3s;
}

/* Inputs Focus Effect */
input:focus, select:focus {
    background: rgba(255,255,255,0.25);
    transform: scale(1.03);
    box-shadow: 0 0 12px rgba(255,255,255,0.5);
}

/* Labels */
label {
    font-weight: 600;
    font-size: 1rem;
}

/* Button */
button {
    width: 100%;
    padding: 15px;
    background: linear-gradient(135deg, #14ff72, #00d26a);
    border: none;
    color: #000;
    font-size: 1.2rem;
    font-weight: 700;
    border-radius: 15px;
    cursor: pointer;
    margin-top: 10px;
    transition: 0.3s;
}

button:hover {
    transform: scale(1.05);
    box-shadow: 0 0 18px #00ff9d;
}

/* Back Link */
.back-link {
    margin-top: 15px;
    display: block;
    text-align: center;
    color: #fff;
    font-weight: 600;
    text-decoration: none;
    font-size: 1.1rem;
}

.back-link:hover {
    text-decoration: underline;
    color: #d4fffa;
}
</style>

</head>

<body>

<div class="container">

    <h2>🌾 Add New Crop</h2>

    <?php if ($message) echo "<div class='msg'>$message</div>"; ?>

    <form method="POST">

        <label>Crop Name</label>
        <input type="text" name="crop_name" placeholder="Enter crop name" required>

        <label>Variety</label>
        <input type="text" name="variety" placeholder="Seed variety" required>

        <label>Field / Plot Location</label>
        <input type="text" name="field" placeholder="Field area or plot info" required>

        <label>Planting Date</label>
        <input type="date" name="planting_date" required>

        <label>Growth Stage</label>
        <select name="growth_stage" required>
            <option value="" disabled selected>Select Stage</option>
            <option value="Seed">Seed</option>
            <option value="Germination">Germination</option>
            <option value="Vegetative">Vegetative</option>
            <option value="Flowering">Flowering</option>
            <option value="Harvest">Harvest</option>
        </select>

        <button type="submit">➕ Add Crop</button>

    </form>

    <a href="farmer_dashboard.php" class="back-link">⬅ Back to Dashboard</a>

</div>

</body>
</html>
