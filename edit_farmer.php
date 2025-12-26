<?php
session_start();
require "db_connection.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// Fetch farmer details
$stmt = $conn->prepare("SELECT * FROM users WHERE id=? AND role='farmer'");
$stmt->bind_param("i", $id);
$stmt->execute();
$farmer = $stmt->get_result()->fetch_assoc();
$stmt->close();

if(!$farmer){
    die("Farmer not found!");
}

// Update farmer details
if(isset($_POST['update'])){
    $fullname  = $_POST['fullname'];
    $email     = $_POST['email'];
    $mobile    = $_POST['mobile'];
    $district  = $_POST['district'];
    $state     = $_POST['state'];
    $password  = $_POST['password'];

    if(!empty($password)){
        // If admin changes password manually
        $password = password_hash($password, PASSWORD_DEFAULT);

        $stmt = $conn->prepare("UPDATE users 
                                SET fullname=?, email=?, mobile=?, district=?, state=?, password=? 
                                WHERE id=?");
        $stmt->bind_param("ssssssi", $fullname, $email, $mobile, $district, $state, $password, $id);
    } else {
        // No password change
        $stmt = $conn->prepare("UPDATE users 
                                SET fullname=?, email=?, mobile=?, district=?, state=? 
                                WHERE id=?");
        $stmt->bind_param("sssssi", $fullname, $email, $mobile, $district, $state, $id);
    }

    if($stmt->execute()){
        echo "<script>alert('Farmer updated successfully!'); window.location='farmers_admin.php';</script>";
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Edit Farmer | Admin</title>
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
.form-group:nth-child(6) { animation-delay: 0.6s; }

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
input[type="email"],
input[type="password"] {
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

input:focus {
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

.input-wrapper input {
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
    animation: fadeInUp 0.6s ease-out 0.7s both;
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
    <a href="farmers_admin.php" class="back-btn">
        <i class="fas fa-arrow-left"></i> Back to Farmers
    </a>

    <div class="header">
        <h2>
            <i class="fas fa-user-edit"></i>
            Edit Farmer
        </h2>
    </div>

    <form method="POST">
        <div class="form-group">
            <label>
                <i class="fas fa-user"></i>
                Full Name
            </label>
            <div class="input-wrapper">
                <i class="fas fa-user"></i>
                <input type="text" name="fullname" required value="<?php echo htmlspecialchars($farmer['fullname']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-envelope"></i>
                Email
            </label>
            <div class="input-wrapper">
                <i class="fas fa-envelope"></i>
                <input type="email" name="email" required value="<?php echo htmlspecialchars($farmer['email']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-phone"></i>
                Mobile
            </label>
            <div class="input-wrapper">
                <i class="fas fa-phone"></i>
                <input type="text" name="mobile" required value="<?php echo htmlspecialchars($farmer['mobile']); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-map-marker-alt"></i>
                District
            </label>
            <div class="input-wrapper">
                <i class="fas fa-map-marker-alt"></i>
                <input type="text" name="district" value="<?php echo htmlspecialchars($farmer['district'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-globe"></i>
                State
            </label>
            <div class="input-wrapper">
                <i class="fas fa-globe"></i>
                <input type="text" name="state" value="<?php echo htmlspecialchars($farmer['state'] ?? ''); ?>">
            </div>
        </div>

        <div class="form-group">
            <label>
                <i class="fas fa-lock"></i>
                New Password (optional)
            </label>
            <div class="input-wrapper">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" placeholder="Leave empty to keep unchanged">
            </div>
        </div>

        <button type="submit" name="update">
            <i class="fas fa-save"></i>
            <span>Update Farmer</span>
        </button>
    </form>
</div>

</body>
</html>
