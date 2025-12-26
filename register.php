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
$showForm = true;

/* =========================
   STEP 1: REGISTER
   ========================= */
if (isset($_POST['step']) && $_POST['step'] === 'register') {

    $required = [
        'fullname','username','email','mobile','password',
        'confirm_password','role','age','gender',
        'aadhar','caste','total_land',
        'latitude','longitude','location_name'
    ];

    foreach ($required as $r) {
        if (empty($_POST[$r])) {
            $message = "Please fill all fields";
            goto render;
        }
    }

    if ($_POST['password'] !== $_POST['confirm_password']) {
        $message = "Passwords do not match";
        goto render;
    }

    if (userExists($conn, $_POST['username'], $_POST['email'])) {
        $message = "Username or Email already exists";
        goto render;
    }

    $_SESSION['reg_data'] = [
        'fullname'  => $_POST['fullname'],
        'username'  => $_POST['username'],
        'email'     => $_POST['email'],
        'mobile'    => $_POST['mobile'],
        'password'  => password_hash($_POST['password'], PASSWORD_DEFAULT),
        'role'      => $_POST['role'],
        'district'  => $_POST['district'] ?? '',
        'age'       => $_POST['age'],
        'gender'    => $_POST['gender'],
        'aadhar'    => $_POST['aadhar'],
        'caste'     => $_POST['caste'],
        'total_land'=> $_POST['total_land'],
        'latitude'  => $_POST['latitude'],
        'longitude' => $_POST['longitude'],
        'location'  => $_POST['location_name']
    ];

    $_SESSION['otp'] = generateOTP();
    $_SESSION['otp_time'] = time();

    $sent = sendOTPEmail($_POST['email'], $_SESSION['otp']);

    if ($sent === true) {
        $showForm = false;
        $message = "OTP sent to your email";
    } else {
        $message = "OTP sending failed: $sent";
    }
}

/* =========================
   STEP 2: VERIFY OTP
   ========================= */
if (isset($_POST['step']) && $_POST['step'] === 'verify_otp') {

    if (
        isset($_SESSION['otp'], $_SESSION['otp_time'], $_SESSION['reg_data']) &&
        $_POST['otp'] == $_SESSION['otp'] &&
        (time() - $_SESSION['otp_time']) <= 300
    ) {

        $d = $_SESSION['reg_data'];

        $stmt = $conn->prepare("
            INSERT INTO users
            (fullname, username, email, password, role, mobile,
             district, age, gender, aadhar, caste, total_land,
             latitude, longitude, location_address, created_at)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?, NOW())
        ");

       $stmt->bind_param(
    "sssssssisssddds",
    $d['fullname'],
    $d['username'],
    $d['email'],
    $d['password'],
    $d['role'],
    $d['mobile'],
    $d['district'],
    $d['age'],
    $d['gender'],
    $d['aadhar'],
    $d['caste'],
    $d['total_land'],
    $d['latitude'],
    $d['longitude'],
    $d['location']
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
    <!-- Registration Form -->
    <form method="POST" id="registerForm">
        <input type="hidden" name="step" value="register">

        <div class="form-row">
            <div class="form-group">
                <label for="fullname"><?php echo $translations['fullname'] ?? 'Full Name'; ?> <span class="required">*</span></label>
                <input type="text" id="fullname" name="fullname" placeholder="<?php echo $translations['fullname'] ?? 'Enter your full name'; ?>" required>
            </div>
            <div class="form-group">
                <label for="username"><?php echo $translations['username'] ?? 'Username'; ?> <span class="required">*</span></label>
                <input type="text" id="username" name="username" placeholder="<?php echo $translations['username'] ?? 'Choose a username'; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="email"><?php echo $translations['email'] ?? 'Email'; ?> <span class="required">*</span></label>
                <input type="email" id="email" name="email" placeholder="<?php echo $translations['email'] ?? 'your.email@example.com'; ?>" required>
            </div>
            <div class="form-group">
                <label for="mobile"><?php echo $translations['mobile'] ?? 'Mobile Number'; ?> <span class="required">*</span></label>
                <input type="tel" id="mobile" name="mobile" placeholder="<?php echo $translations['mobile'] ?? '10-digit mobile number'; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="age"><?php echo $translations['age'] ?? 'Age'; ?> <span class="required">*</span></label>
                <input type="number" id="age" name="age" placeholder="<?php echo $translations['age'] ?? 'Age'; ?>" min="18" max="100" required>
            </div>
            <div class="form-group">
                <label for="gender"><?php echo $translations['gender'] ?? 'Gender'; ?> <span class="required">*</span></label>
                <select id="gender" name="gender" required>
                    <option value=""><?php echo $translations['select_gender'] ?? 'Select Gender'; ?></option>
                    <option value="Male"><?php echo $translations['male'] ?? 'Male'; ?></option>
                    <option value="Female"><?php echo $translations['female'] ?? 'Female'; ?></option>
                    <option value="Other"><?php echo $translations['other'] ?? 'Other'; ?></option>
                </select>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="aadhar"><?php echo $translations['aadhar'] ?? 'Aadhar Number'; ?> <span class="required">*</span></label>
                <input type="text" id="aadhar" name="aadhar" placeholder="<?php echo $translations['aadhar'] ?? '12-digit Aadhar'; ?>" maxlength="12" required>
            </div>
            <div class="form-group">
                <label for="caste"><?php echo $translations['caste'] ?? 'Caste'; ?> <span class="required">*</span></label>
                <input type="text" id="caste" name="caste" placeholder="<?php echo $translations['caste'] ?? 'Enter caste'; ?>" required>
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="total_land"><?php echo $translations['total_land'] ?? 'Total Land (acres)'; ?> <span class="required">*</span></label>
                <input type="number" id="total_land" name="total_land" placeholder="<?php echo $translations['total_land'] ?? 'Land in acres'; ?>" step="0.01" min="0" required>
            </div>
            <div class="form-group">
                <label for="role"><?php echo $translations['role'] ?? 'Role'; ?> <span class="required">*</span></label>
                <select id="role" name="role" required>
                    <option value="farmer"><?php echo $translations['farmer'] ?? 'Farmer'; ?></option>
                    <option value="admin"><?php echo $translations['admin'] ?? 'Admin'; ?></option>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="location_name"><?php echo $translations['location'] ?? 'Location'; ?> <span class="required">*</span></label>
            <input type="text" id="location_name" name="location_name" placeholder="<?php echo $translations['location'] ?? 'Enter your location'; ?>" required>
            <input type="hidden" name="latitude" value="17.3850">
            <input type="hidden" name="longitude" value="78.4867">
        </div>

        <div class="form-divider">
            <span><?php echo $translations['account_security'] ?? 'Account Security'; ?></span>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="password"><?php echo $translations['password'] ?? 'Password'; ?> <span class="required">*</span></label>
                <input type="password" id="password" name="password" placeholder="<?php echo $translations['password'] ?? 'Create a strong password'; ?>" required>
                <div class="password-hint"><?php echo $translations['password_hint'] ?? 'Use at least 8 characters'; ?></div>
            </div>
            <div class="form-group">
                <label for="confirm_password"><?php echo $translations['confirm_password'] ?? 'Confirm Password'; ?> <span class="required">*</span></label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="<?php echo $translations['confirm_password'] ?? 'Re-enter password'; ?>" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-user-plus"></i>
            <?php echo $translations['register'] ?? 'Register'; ?>
        </button>

        <div style="text-align: center; margin-top: 24px; color: var(--text-light); font-size: 14px;">
            <?php echo $translations['already_have_account'] ?? 'Already have an account?'; ?>
            <a href="login.php" style="color: var(--primary-green); text-decoration: none; font-weight: 600;">
                <?php echo $translations['login'] ?? 'Login'; ?>
            </a>
        </div>
    </form>

    <?php else: ?>
    <!-- OTP Verification Form -->
    <div class="otp-section">
        <div class="icon-wrapper">
            <i class="fas fa-envelope"></i>
        </div>
        <h2><?php echo $translations['verify_otp'] ?? 'Verify OTP'; ?></h2>
        <p><?php echo $translations['otp_sent_message'] ?? 'We have sent an OTP to your email address. Please enter it below.'; ?></p>

        <form method="POST">
            <input type="hidden" name="step" value="verify_otp">
            
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
            <a href="register.php" class="btn btn-secondary" style="width: auto; padding: 12px 24px;">
                <i class="fas fa-arrow-left"></i>
                <?php echo $translations['back_to_register'] ?? 'Back to Registration'; ?>
            </a>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
// Auto-format OTP input
document.addEventListener('DOMContentLoaded', function() {
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
    }
});
</script>

</body>
</html>
