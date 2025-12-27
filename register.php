<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require 'db_connection.php';

/* PHPMailer */
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/* Language */
$lang = $_SESSION['lang'] ?? 'en';
$translations = file_exists("languages/$lang.php")
    ? include "languages/$lang.php"
    : [];

/* OTP generator */
function generateOTP($length = 6) {
    return rand(pow(10, $length - 1), pow(10, $length) - 1);
}

/* Send OTP */
function sendOTPEmail($email, $otp) {
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joyswapnilrajparadeshi@gmail.com';
        $mail->Password = 'kdbddmffsotggrjt';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('joyswapnilrajparadeshi@gmail.com', 'Smart Cultivation System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "OTP for Registration";
        $mail->Body = "<b>Your OTP:</b> $otp <br>Valid for 5 minutes";

        $mail->send();
        return true;
    } catch (Exception $e) {
        return $mail->ErrorInfo;
    }
}

/* Check duplicate user */
function userExists($conn, $username, $email) {
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss", $username, $email);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

$message = '';
// Get current step from GET parameter or default to 1
if (isset($_GET['step'])) {
    $currentStep = (int)$_GET['step'];
    // Ensure step is valid (1-3)
    if ($currentStep < 1 || $currentStep > 3) {
        $currentStep = 1;
    }
} else {
    $currentStep = 1;
}

// Validate step access - user must complete previous steps
if (!isset($_SESSION['reg_data'])) {
    $_SESSION['reg_data'] = [];
}

// Check if user can access the requested step
if ($currentStep == 2 && (!isset($_SESSION['reg_data']['fullname']) || !isset($_SESSION['reg_data']['email']))) {
    $currentStep = 1;
    $message = "Please complete Step 1 first";
} elseif ($currentStep == 3 && (!isset($_SESSION['reg_data']['aadhar']) || !isset($_SESSION['reg_data']['location']))) {
    $currentStep = 2;
    $message = "Please complete Step 2 first";
}

$showForm = true;

// Initialize registration data in session if not exists
if (!isset($_SESSION['reg_data'])) {
    $_SESSION['reg_data'] = [];
}

/* =========================
   STEP 1: BASIC INFORMATION
   ========================= */
if (isset($_POST['form_step']) && $_POST['form_step'] === '1') {
    $required = ['fullname', 'username', 'email', 'mobile', 'age', 'gender'];
    
    foreach ($required as $r) {
        if (empty($_POST[$r])) {
            $message = "Please fill all fields in Step 1";
            $currentStep = 1;
            goto render;
        }
    }

    if (userExists($conn, $_POST['username'], $_POST['email'])) {
        $message = "Username or Email already exists";
        $currentStep = 1;
        goto render;
    }

    $_SESSION['reg_data']['fullname'] = $_POST['fullname'];
    $_SESSION['reg_data']['username'] = $_POST['username'];
    $_SESSION['reg_data']['email'] = $_POST['email'];
    $_SESSION['reg_data']['mobile'] = $_POST['mobile'];
    $_SESSION['reg_data']['age'] = $_POST['age'];
    $_SESSION['reg_data']['gender'] = $_POST['gender'];

    header("Location: register.php?step=2");
    exit;
}

/* =========================
   STEP 2: PERSONAL & LAND DETAILS
   ========================= */
if (isset($_POST['form_step']) && $_POST['form_step'] === '2') {
    $required = ['aadhar', 'caste', 'total_land', 'role', 'location_name'];
    
    foreach ($required as $r) {
        if (empty($_POST[$r])) {
            $message = "Please fill all fields in Step 2";
            $currentStep = 2;
            goto render;
        }
    }

    $_SESSION['reg_data']['aadhar'] = $_POST['aadhar'];
    $_SESSION['reg_data']['caste'] = $_POST['caste'];
    $_SESSION['reg_data']['total_land'] = $_POST['total_land'];
    $_SESSION['reg_data']['role'] = $_POST['role'];
    $_SESSION['reg_data']['location'] = $_POST['location_name'];
    $_SESSION['reg_data']['latitude'] = $_POST['latitude'] ?? '17.3850';
    $_SESSION['reg_data']['longitude'] = $_POST['longitude'] ?? '78.4867';
    $_SESSION['reg_data']['district'] = $_POST['district'] ?? '';

    header("Location: register.php?step=3");
    exit;
}

/* =========================
   STEP 3: ACCOUNT SECURITY & SUBMIT
   ========================= */
if (isset($_POST['form_step']) && $_POST['form_step'] === '3') {
    if (empty($_POST['password']) || empty($_POST['confirm_password'])) {
        $message = "Please fill password fields";
        $currentStep = 3;
        goto render;
    }

    if ($_POST['password'] !== $_POST['confirm_password']) {
        $message = "Passwords do not match";
        $currentStep = 3;
        goto render;
    }

    if (strlen($_POST['password']) < 8) {
        $message = "Password must be at least 8 characters";
        $currentStep = 3;
        goto render;
    }

    $_SESSION['reg_data']['password'] = password_hash($_POST['password'], PASSWORD_DEFAULT);

    // All steps complete, send OTP
    $_SESSION['otp'] = generateOTP();
    $_SESSION['otp_time'] = time();

    $sent = sendOTPEmail($_SESSION['reg_data']['email'], $_SESSION['otp']);

    if ($sent === true) {
        $showForm = false;
        $message = "OTP sent to your email";
    } else {
        $message = "OTP sending failed: $sent";
        $currentStep = 3;
    }
}

/* =========================
   STEP 4: VERIFY OTP
   ========================= */
if (isset($_POST['form_step']) && $_POST['form_step'] === 'verify_otp') {

    if (
        isset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['reg_data']) &&
        $_POST['otp'] == $_SESSION['otp'] &&
        (time() - $_SESSION['otp_time']) <= 300
    ) {

        $d = $_SESSION['reg_data'];

        // Ensure all required values exist with defaults
        $district = $d['district'] ?? '';
        $fullname = $d['fullname'];
        $username = $d['username'];
        $email = $d['email'];
        $password = $d['password'];
        $role = $d['role'];
        $mobile = $d['mobile'];
        $age = $d['age'];
        $gender = $d['gender'];
        $aadhar = $d['aadhar'];
        $caste = $d['caste'];
        $total_land = $d['total_land'];
        $latitude = $d['latitude'];
        $longitude = $d['longitude'];
        $location = $d['location'];

        $stmt = $conn->prepare("
            INSERT INTO users
            (fullname, username, email, password, role, mobile,
             district, age, gender, aadhar, caste, total_land,
             latitude, longitude, location_address, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
        ");

       $stmt->bind_param(
    "sssssssisssddds",
    $fullname,
    $username,
    $email,
    $password,
    $role,
    $mobile,
    $district,
    $age,
    $gender,
    $aadhar,
    $caste,
    $total_land,
    $latitude,
    $longitude,
    $location
);


        if ($stmt->execute()) {
            unset($_SESSION['reg_data'], $_SESSION['otp'], $_SESSION['otp_time']);
            header("Location: login.php?registered=1");
            exit;
        } else {
            $message = "Registration failed: " . $stmt->error;
        }

    } else {
        $message = "Invalid or expired OTP";
        $showForm = false;
    }
}

render:
?>
<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $translations['register'] ?? 'Register'; ?> | Smart Cultivation System</title>
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
.register-container {
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

/* Header */
.register-header {
    text-align: center;
    margin-bottom: 40px;
}

.register-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 8px;
    letter-spacing: -0.02em;
}

.register-header p {
    color: var(--text-light);
    font-size: 15px;
}

/* Message Alert */
.message-alert {
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

.message-alert.error {
    background: #fee;
    color: var(--error-color);
    border: 1px solid #fcc;
}

.message-alert.success {
    background: #efe;
    color: var(--success-color);
    border: 1px solid #cfc;
}

.message-alert i {
    font-size: 18px;
}

/* Form Groups */
.form-group {
    margin-bottom: 20px;
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

/* Form Row (for side-by-side fields) */
.form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 16px;
}

/* Buttons */
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

.btn-secondary {
    background: var(--bg-light);
    color: var(--text-dark);
    border: 2px solid var(--border-color);
}

.btn-secondary:hover {
    background: var(--bg-white);
    border-color: var(--primary-green);
    color: var(--primary-green);
}

/* OTP Section */
.otp-section {
    text-align: center;
}

.otp-section .icon-wrapper {
    width: 80px;
    height: 80px;
    margin: 0 auto 24px;
    border-radius: 50%;
    background: linear-gradient(135deg, rgba(45, 134, 89, 0.1), rgba(76, 175, 80, 0.1));
    display: flex;
    align-items: center;
    justify-content: center;
}

.otp-section i {
    font-size: 36px;
    color: var(--primary-green);
}

.otp-section h2 {
    font-size: 24px;
    font-weight: 700;
    color: var(--text-dark);
    margin-bottom: 12px;
}

.otp-section p {
    color: var(--text-light);
    margin-bottom: 32px;
    font-size: 15px;
}

.otp-input {
    font-size: 24px;
    font-weight: 600;
    text-align: center;
    letter-spacing: 8px;
    padding: 16px;
}

.otp-input::placeholder {
    letter-spacing: 4px;
    font-size: 16px;
}

/* Back Link */
.back-link {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    color: var(--text-light);
    text-decoration: none;
    font-size: 14px;
    margin-bottom: 24px;
    transition: var(--transition);
}

.back-link:hover {
    color: var(--primary-green);
}

.back-link i {
    font-size: 14px;
}

/* Divider */
.form-divider {
    display: flex;
    align-items: center;
    margin: 24px 0;
    color: var(--text-light);
    font-size: 14px;
}

.form-divider::before,
.form-divider::after {
    content: "";
    flex: 1;
    height: 1px;
    background: var(--border-color);
}

.form-divider span {
    padding: 0 16px;
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

/* Responsive Design */
@media (max-width: 768px) {
    .register-container {
        padding: 32px 24px;
        margin: 10px;
    }

    .register-header h1 {
        font-size: 28px;
    }

    .form-row {
        grid-template-columns: 1fr;
    }

    .progress-steps {
        margin-bottom: 24px;
    }

    .step-label {
        font-size: 10px;
    }

    .step-circle {
        width: 32px;
        height: 32px;
        font-size: 14px;
    }

    .form-navigation {
        flex-direction: column-reverse;
    }

    .btn-nav {
        width: 100%;
        justify-content: center;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .register-container {
        padding: 24px 20px;
    }

    .register-header h1 {
        font-size: 24px;
    }
}

/* Password Strength Indicator */
.password-hint {
    font-size: 12px;
    color: var(--text-light);
    margin-top: 6px;
}

/* Progress Indicator */
.progress-container {
    margin-bottom: 40px;
}

.progress-steps {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    margin-bottom: 30px;
}

.progress-steps::before {
    content: '';
    position: absolute;
    top: 20px;
    left: 0;
    right: 0;
    height: 3px;
    background: var(--border-color);
    z-index: 0;
}

.progress-line {
    position: absolute;
    top: 20px;
    left: 0;
    height: 3px;
    background: var(--primary-green);
    z-index: 1;
    transition: width 0.4s ease;
}

.step-item {
    position: relative;
    z-index: 2;
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
}

.step-circle {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: white;
    border: 3px solid var(--border-color);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 16px;
    color: var(--text-light);
    transition: var(--transition);
    margin-bottom: 8px;
}

.step-item.active .step-circle {
    border-color: var(--primary-green);
    background: var(--primary-green);
    color: white;
}

.step-item.completed .step-circle {
    border-color: var(--primary-green);
    background: var(--primary-green);
    color: white;
}

.step-item.completed .step-circle::after {
    content: '\f00c';
    font-family: 'Font Awesome 6 Free';
    font-weight: 900;
}

.step-label {
    font-size: 12px;
    font-weight: 600;
    color: var(--text-light);
    text-align: center;
    margin-top: 4px;
}

.step-item.active .step-label {
    color: var(--primary-green);
}

.step-item.completed .step-label {
    color: var(--primary-green);
}

/* Form Steps */
.form-step {
    display: none;
    animation: fadeIn 0.4s ease;
}

.form-step.active {
    display: block;
}

@keyframes fadeIn {
    from {
        opacity: 0;
        transform: translateX(20px);
    }
    to {
        opacity: 1;
        transform: translateX(0);
    }
}

/* Navigation Buttons */
.form-navigation {
    display: flex;
    gap: 12px;
    margin-top: 32px;
    justify-content: space-between;
}

.btn-nav {
    padding: 14px 32px;
    font-size: 15px;
    font-weight: 600;
    border: none;
    border-radius: 12px;
    cursor: pointer;
    transition: var(--transition);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}

.btn-next {
    background: var(--primary-green);
    color: white;
    box-shadow: var(--shadow-sm);
    margin-left: auto;
}

.btn-next:hover {
    background: var(--primary-green-dark);
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}

.btn-prev {
    background: var(--bg-light);
    color: var(--text-dark);
    border: 2px solid var(--border-color);
}

.btn-prev:hover {
    background: var(--bg-white);
    border-color: var(--primary-green);
    color: var(--primary-green);
}

.btn-nav:disabled {
    opacity: 0.5;
    cursor: not-allowed;
}

.btn-nav:disabled:hover {
    transform: none;
}
</style>
</head>
<body>

<div class="register-container">
    <!-- Back Link -->
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        <?php echo $translations['back'] ?? 'Back to Home'; ?>
    </a>

    <!-- Header -->
    <div class="register-header">
        <h1><i class="fas fa-user-plus"></i> <?php echo $translations['register'] ?? 'Register'; ?></h1>
        <p><?php echo $translations['register_subtitle'] ?? 'Create your account to start smart cultivation'; ?></p>
    </div>

    <!-- Message Alert -->
    <?php if ($message): ?>
    <div class="message-alert <?php echo strpos($message, 'sent') !== false || strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
        <i class="fas <?php echo strpos($message, 'sent') !== false || strpos($message, 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <?php if ($showForm): ?>
    <!-- Progress Indicator -->
    <div class="progress-container">
        <div class="progress-steps">
            <div class="progress-line" style="width: <?php echo (($currentStep - 1) / 3) * 100; ?>%"></div>
            <div class="step-item <?php echo $currentStep >= 1 ? ($currentStep > 1 ? 'completed' : 'active') : ''; ?>">
                <div class="step-circle"><?php echo $currentStep > 1 ? '' : '1'; ?></div>
                <div class="step-label">Basic Info</div>
            </div>
            <div class="step-item <?php echo $currentStep >= 2 ? ($currentStep > 2 ? 'completed' : 'active') : ''; ?>">
                <div class="step-circle"><?php echo $currentStep > 2 ? '' : '2'; ?></div>
                <div class="step-label">Personal Details</div>
            </div>
            <div class="step-item <?php echo $currentStep >= 3 ? 'active' : ''; ?>">
                <div class="step-circle"><?php echo $currentStep > 3 ? '' : '3'; ?></div>
                <div class="step-label">Security</div>
            </div>
        </div>
    </div>

    <!-- Registration Form -->
    <form method="POST" id="registerForm">
        <input type="hidden" name="form_step" id="form_step" value="<?php echo $currentStep; ?>">
        <!-- STEP 1: Basic Information -->
        <div class="form-step <?php echo $currentStep == 1 ? 'active' : ''; ?>" id="step1">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--text-dark); margin-bottom: 24px;">
                <i class="fas fa-user" style="color: var(--primary-green); margin-right: 8px;"></i>
                Basic Information
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="fullname"><?php echo $translations['fullname'] ?? 'Full Name'; ?> <span class="required">*</span></label>
                    <input type="text" id="fullname" name="fullname" value="<?php echo $_SESSION['reg_data']['fullname'] ?? ''; ?>" placeholder="<?php echo $translations['fullname'] ?? 'Enter your full name'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="username"><?php echo $translations['username'] ?? 'Username'; ?> <span class="required">*</span></label>
                    <input type="text" id="username" name="username" value="<?php echo $_SESSION['reg_data']['username'] ?? ''; ?>" placeholder="<?php echo $translations['username'] ?? 'Choose a username'; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="email"><?php echo $translations['email'] ?? 'Email'; ?> <span class="required">*</span></label>
                    <input type="email" id="email" name="email" value="<?php echo $_SESSION['reg_data']['email'] ?? ''; ?>" placeholder="<?php echo $translations['email'] ?? 'your.email@example.com'; ?>" required>
                </div>
                <div class="form-group">
                    <label for="mobile"><?php echo $translations['mobile'] ?? 'Mobile Number'; ?> <span class="required">*</span></label>
                    <input type="tel" id="mobile" name="mobile" value="<?php echo $_SESSION['reg_data']['mobile'] ?? ''; ?>" placeholder="<?php echo $translations['mobile'] ?? '10-digit mobile number'; ?>" required>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="age"><?php echo $translations['age'] ?? 'Age'; ?> <span class="required">*</span></label>
                    <input type="number" id="age" name="age" value="<?php echo $_SESSION['reg_data']['age'] ?? ''; ?>" placeholder="<?php echo $translations['age'] ?? 'Age'; ?>" min="18" max="100" required>
                </div>
                <div class="form-group">
                    <label for="gender"><?php echo $translations['gender'] ?? 'Gender'; ?> <span class="required">*</span></label>
                    <select id="gender" name="gender" required>
                        <option value=""><?php echo $translations['select_gender'] ?? 'Select Gender'; ?></option>
                        <option value="Male" <?php echo (isset($_SESSION['reg_data']['gender']) && $_SESSION['reg_data']['gender'] == 'Male') ? 'selected' : ''; ?>><?php echo $translations['male'] ?? 'Male'; ?></option>
                        <option value="Female" <?php echo (isset($_SESSION['reg_data']['gender']) && $_SESSION['reg_data']['gender'] == 'Female') ? 'selected' : ''; ?>><?php echo $translations['female'] ?? 'Female'; ?></option>
                        <option value="Other" <?php echo (isset($_SESSION['reg_data']['gender']) && $_SESSION['reg_data']['gender'] == 'Other') ? 'selected' : ''; ?>><?php echo $translations['other'] ?? 'Other'; ?></option>
                    </select>
                </div>
            </div>

            <div class="form-navigation">
                <div></div>
                <button type="submit" class="btn-nav btn-next" onclick="document.getElementById('form_step').value='1'; return true;">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 2: Personal & Land Details -->
        <div class="form-step <?php echo $currentStep == 2 ? 'active' : ''; ?>" id="step2">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--text-dark); margin-bottom: 24px;">
                <i class="fas fa-landmark" style="color: var(--primary-green); margin-right: 8px;"></i>
                Personal & Land Details
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="aadhar"><?php echo $translations['aadhar'] ?? 'Aadhar Number'; ?> <span class="required">*</span></label>
                    <input type="text" id="aadhar" name="aadhar" value="<?php echo $_SESSION['reg_data']['aadhar'] ?? ''; ?>" placeholder="<?php echo $translations['aadhar'] ?? '12-digit Aadhar'; ?>" maxlength="12" <?php echo $currentStep == 2 ? 'required' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="caste"><?php echo $translations['caste'] ?? 'Caste'; ?> <span class="required">*</span></label>
                    <input type="text" id="caste" name="caste" value="<?php echo $_SESSION['reg_data']['caste'] ?? ''; ?>" placeholder="<?php echo $translations['caste'] ?? 'Enter caste'; ?>" <?php echo $currentStep == 2 ? 'required' : ''; ?>>
                </div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="total_land"><?php echo $translations['total_land'] ?? 'Total Land (acres)'; ?> <span class="required">*</span></label>
                    <input type="number" id="total_land" name="total_land" value="<?php echo $_SESSION['reg_data']['total_land'] ?? ''; ?>" placeholder="<?php echo $translations['total_land'] ?? 'Land in acres'; ?>" step="0.01" min="0" <?php echo $currentStep == 2 ? 'required' : ''; ?>>
                </div>
                <div class="form-group">
                    <label for="role"><?php echo $translations['role'] ?? 'Role'; ?> <span class="required">*</span></label>
                    <select id="role" name="role" <?php echo $currentStep == 2 ? 'required' : ''; ?>>
                        <option value="farmer" <?php echo (isset($_SESSION['reg_data']['role']) && $_SESSION['reg_data']['role'] == 'farmer') ? 'selected' : ''; ?>><?php echo $translations['farmer'] ?? 'Farmer'; ?></option>
                        <option value="admin" <?php echo (isset($_SESSION['reg_data']['role']) && $_SESSION['reg_data']['role'] == 'admin') ? 'selected' : ''; ?>><?php echo $translations['admin'] ?? 'Admin'; ?></option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label for="location_name"><?php echo $translations['location'] ?? 'Location'; ?> <span class="required">*</span></label>
                <input type="text" id="location_name" name="location_name" value="<?php echo $_SESSION['reg_data']['location'] ?? ''; ?>" placeholder="<?php echo $translations['location'] ?? 'Enter your location'; ?>" <?php echo $currentStep == 2 ? 'required' : ''; ?>>
                <input type="hidden" name="latitude" value="17.3850">
                <input type="hidden" name="longitude" value="78.4867">
            </div>

            <div class="form-navigation">
                <a href="?step=1" class="btn-nav btn-prev">
                    <i class="fas fa-arrow-left"></i> Previous
                </a>
                <button type="submit" class="btn-nav btn-next" onclick="document.getElementById('form_step').value='2'; return true;">
                    Next <i class="fas fa-arrow-right"></i>
                </button>
            </div>
        </div>

        <!-- STEP 3: Account Security -->
        <div class="form-step <?php echo $currentStep == 3 ? 'active' : ''; ?>" id="step3">
            <h2 style="font-size: 20px; font-weight: 700; color: var(--text-dark); margin-bottom: 24px;">
                <i class="fas fa-lock" style="color: var(--primary-green); margin-right: 8px;"></i>
                Account Security
            </h2>

            <div class="form-row">
                <div class="form-group">
                    <label for="password"><?php echo $translations['password'] ?? 'Password'; ?> <span class="required">*</span></label>
                    <input type="password" id="password" name="password" placeholder="<?php echo $translations['password'] ?? 'Create a strong password'; ?>" <?php echo $currentStep == 3 ? 'required' : ''; ?>>
                    <div class="password-hint"><?php echo $translations['password_hint'] ?? 'Use at least 8 characters'; ?></div>
                </div>
                <div class="form-group">
                    <label for="confirm_password"><?php echo $translations['confirm_password'] ?? 'Confirm Password'; ?> <span class="required">*</span></label>
                    <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo $translations['confirm_password'] ?? 'Re-enter password'; ?>" <?php echo $currentStep == 3 ? 'required' : ''; ?>>
                </div>
            </div>

            <div class="form-navigation">
                <a href="?step=2" class="btn-nav btn-prev">
                    <i class="fas fa-arrow-left"></i> Previous
                </a>
                <button type="submit" class="btn-nav btn-next" onclick="document.getElementById('form_step').value='3'; return true;">
                    <i class="fas fa-check"></i> Complete Registration
                </button>
            </div>
        </div>

        <div style="text-align: center; margin-top: 32px; color: var(--text-light); font-size: 14px;">
            <?php echo $translations['already_have_account'] ?? 'Already have an account?'; ?>
            <a href="login.php" style="color: var(--primary-green); text-decoration: none; font-weight: 600;">
                <?php echo $translations['login'] ?? 'Login'; ?>
            </a>
        </div>
    </form>

    <?php else: ?>
    <!-- OTP Verification Form (Step 4) -->
    <div class="progress-container">
        <div class="progress-steps">
            <div class="progress-line" style="width: 100%"></div>
            <div class="step-item completed">
                <div class="step-circle"></div>
                <div class="step-label">Basic Info</div>
            </div>
            <div class="step-item completed">
                <div class="step-circle"></div>
                <div class="step-label">Personal Details</div>
            </div>
            <div class="step-item completed">
                <div class="step-circle"></div>
                <div class="step-label">Security</div>
            </div>
        </div>
    </div>

    <div class="otp-section">
        <div class="icon-wrapper">
            <i class="fas fa-envelope"></i>
        </div>
        <h2><?php echo $translations['verify_otp'] ?? 'Verify OTP'; ?></h2>
        <p><?php echo $translations['otp_sent_message'] ?? 'We have sent an OTP to your email address. Please enter it below.'; ?></p>

        <form method="POST">
            <input type="hidden" name="form_step" value="verify_otp">
            
            <div class="form-group">
                <label for="otp"><?php echo $translations['enter_otp'] ?? 'Enter OTP'; ?></label>
                <input type="text" id="otp" name="otp" class="otp-input" placeholder="000000" maxlength="6" pattern="[0-9]{6}" required autofocus>
            </div>

            <button type="submit" class="btn btn-primary">
                <i class="fas fa-check-circle"></i>
                <?php echo $translations['verify_otp'] ?? 'Verify OTP'; ?>
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px;">
            <a href="register.php?step=3" class="btn btn-secondary" style="width: auto; padding: 12px 24px;">
                <i class="fas fa-arrow-left"></i>
                <?php echo $translations['back_to_register'] ?? 'Back to Registration'; ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Auto-format OTP input
    const otpInput = document.getElementById('otp');
    if (otpInput) {
        otpInput.addEventListener('input', function(e) {
            e.target.value = e.target.value.replace(/\D/g, '');
        });
    }

    // Password match validation
    const password = document.getElementById('password');
    const confirmPassword = document.getElementById('confirm_password');
    
    if (password && confirmPassword) {
        confirmPassword.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('<?php echo $translations['passwords_do_not_match'] ?? 'Passwords do not match'; ?>');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });

        password.addEventListener('input', function() {
            if (password.value !== confirmPassword.value) {
                confirmPassword.setCustomValidity('<?php echo $translations['passwords_do_not_match'] ?? 'Passwords do not match'; ?>');
            } else {
                confirmPassword.setCustomValidity('');
            }
        });
    }

    // Form step validation before submission
    const registerForm = document.getElementById('registerForm');
    const formStepInput = document.getElementById('form_step');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const activeStep = document.querySelector('.form-step.active');
            if (activeStep) {
                const inputs = activeStep.querySelectorAll('input[required], select[required]');
                let isValid = true;
                
                inputs.forEach(input => {
                    if (!input.value.trim()) {
                        isValid = false;
                        input.style.borderColor = 'var(--error-color)';
                    } else {
                        input.style.borderColor = '';
                    }
                });

                if (!isValid) {
                    e.preventDefault();
                    alert('Please fill all required fields');
                    return false;
                }

                // Get current step from hidden input
                const formStep = formStepInput ? formStepInput.value : '1';
                
                // Additional validation for step 1
                if (formStep === '1') {
                    const emailInput = document.getElementById('email');
                    const mobileInput = document.getElementById('mobile');
                    
                    if (emailInput && !emailInput.value.match(/^[^\s@]+@[^\s@]+\.[^\s@]+$/)) {
                        e.preventDefault();
                        alert('Please enter a valid email address');
                        return false;
                    }
                    
                    if (mobileInput && mobileInput.value.length < 10) {
                        e.preventDefault();
                        alert('Please enter a valid mobile number');
                        return false;
                    }
                }

                // Additional validation for step 3
                if (formStep === '3' && password && confirmPassword) {
                    if (password.value.length < 8) {
                        e.preventDefault();
                        alert('Password must be at least 8 characters long');
                        return false;
                    }
                    
                    if (password.value !== confirmPassword.value) {
                        e.preventDefault();
                        alert('Passwords do not match');
                        return false;
                    }
                }
            }
        });
    }

    // Remove error styling on input
    const inputs = document.querySelectorAll('.form-step input, .form-step select');
    inputs.forEach(input => {
        input.addEventListener('input', function() {
            this.style.borderColor = '';
        });
    });
});
</script>

</body>
</html>
