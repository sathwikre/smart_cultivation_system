<?php
session_start();
require 'db_connection.php'; // DB connection

// Load PHPMailer
require 'PHPMailer-master/src/PHPMailer.php';
require 'PHPMailer-master/src/SMTP.php';
require 'PHPMailer-master/src/Exception.php';
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Language toggle
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: login.php");
    exit;
}

$lang = $_SESSION['lang'] ?? 'en';
$translations = include "languages/$lang.php";

// Helper: generate OTP
function generateOTP($length = 6){
    return rand(pow(10, $length-1), pow(10, $length)-1);
}

// Send OTP email (pass $translations)
function sendOTPEmail($email, $otp, $translations){
    $mail = new PHPMailer(true);
    try {
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'joyswapnilrajparadeshi@gmail.com';
        $mail->Password   = 'kdbddmffsotggrjt';
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            ]
        ];
        $mail->setFrom('joyswapnilrajparadeshi@gmail.com','Smart Cultivation System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = $translations['otp_email_subject'] ?? "Your Login OTP";
        $mail->Body    = "<p>".$translations['otp_email_body']." <strong>$otp</strong></p><p>".$translations['otp_expiry']."</p>";
        $mail->AltBody = $translations['otp_email_body']." $otp. ".$translations['otp_expiry'];
        $mail->send();
        return true;
    } catch(Exception $e) {
        return "Mailer Error: ".$mail->ErrorInfo;
    }
}

$message = '';
$showForm = true;

// STEP 1: Request OTP
if(isset($_POST['step']) && $_POST['step'] == 'request_otp'){
    $email = trim($_POST['email']);
    if(empty($email)){
        $message = $translations['enter_email'] ?? "Please enter your email.";
    } else {
        $stmt = $conn->prepare("SELECT id, role FROM users WHERE email=? LIMIT 1");
        if($stmt){
            $stmt->bind_param("s",$email);
            $stmt->execute();
            $res = $stmt->get_result();
            if($res && $res->num_rows > 0){
                $user = $res->fetch_assoc();
                $stmt->close(); // Close the SELECT statement before preparing UPDATE
                
                $otp = generateOTP();
                $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
                
                $update = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
                if($update){
                    $update->bind_param("ssi", $otp, $expiry, $user['id']);
                    if($update->execute()){
                        $update->close();
                        
                        $sent = sendOTPEmail($email, $otp, $translations);
                        if($sent === true){
                            $_SESSION['login_email'] = $email;
                            $_SESSION['login_role'] = $user['role'];
                            $message = $translations['otp_sent'] ?? "OTP sent to your email.";
                            $showForm = false;
                        } else {
                            $message = $translations['otp_failed'] ?? "Failed to send OTP: ".$sent;
                        }
                    } else {
                        $message = $translations['otp_failed'] ?? "Database error: ".$update->error;
                        $update->close();
                    }
                } else {
                    $message = $translations['otp_failed'] ?? "Database error: ".$conn->error;
                }
            } else {
                $message = $translations['email_not_found'] ?? "Email not found. Please register first.";
                $stmt->close();
            }
        } else {
            $message = $translations['otp_failed'] ?? "Database error: ".$conn->error;
        }
    }
}

// STEP 2: Verify OTP
if(isset($_POST['step']) && $_POST['step'] == 'verify_otp'){
    $entered_otp = trim($_POST['otp'] ?? '');
    $email = $_SESSION['login_email'] ?? '';

    if(empty($entered_otp) || empty($email)){
        $message = $translations['otp_missing'] ?? "OTP or session expired. Try again.";
        $showForm = true;
    } else {
        $stmt = $conn->prepare("SELECT id, role, otp, otp_expiry FROM users WHERE email=? LIMIT 1");
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows > 0){
            $user = $res->fetch_assoc();
            $now = time();
            $otp_time = strtotime($user['otp_expiry']);
            if($entered_otp == $user['otp'] && $now <= $otp_time){
                unset($_SESSION['login_email']);
                unset($_SESSION['login_role']);
                session_regenerate_id();
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];

                if($user['role'] == 'farmer'){
                    header("Location: farmer_dashboard.php");
                } else if($user['role'] == 'admin'){
                    header("Location: admin_dashboard.php");
                } else {
                    header("Location: index.php");
                }
                exit;
            } else {
                $message = $translations['otp_invalid'] ?? "Invalid or expired OTP.";
                $showForm = false;
            }
        } else {
            $message = $translations['email_not_found'] ?? "User not found.";
            $showForm = true;
        }
        $stmt->close();
    }
}

// STEP 3: Resend OTP
if(isset($_POST['step']) && $_POST['step'] == 'resend_otp'){
    if(!isset($_SESSION['login_email'])){
        $message = $translations['session_expired'] ?? "Session expired. Please enter email again.";
        $showForm = true;
    } else {
        if(isset($_SESSION['otp_time']) && (time() - $_SESSION['otp_time'] < 30)){
            $message = $translations['wait_otp'] ?? "Please wait 30 seconds before requesting a new OTP.";
            $showForm = false;
        } else {
            $otp = generateOTP();
            $_SESSION['otp_time'] = time();
            $email = $_SESSION['login_email'];
            $stmt = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE email=?");
            $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));
            $stmt->bind_param("sss",$otp,$expiry,$email);
            $stmt->execute();
            $sent = sendOTPEmail($email, $otp, $translations);
            if($sent === true){
                $message = $translations['otp_resent'] ?? "New OTP has been sent to your email.";
            } else {
                $message = $translations['otp_failed'] ?? "Failed to resend OTP: ".$sent;
            }
            $showForm = false;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="<?php echo $lang; ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo $translations['login'] ?? 'Login'; ?> | Smart Cultivation System</title>
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
        radial-gradient(circle at 80% 80%, rgba(76, 175, 80, 0.03) 0%, transparent 50%),
        radial-gradient(circle at 40% 20%, rgba(255, 152, 0, 0.02) 0%, transparent 50%);
    z-index: 0;
    pointer-events: none;
}

/* Container */
.login-container {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 480px;
    background: var(--bg-white);
    border-radius: 20px;
    box-shadow: var(--shadow-lg);
    padding: 48px;
    animation: fadeInUp 0.6s ease-out;
}

/* Header */
.login-header {
    text-align: center;
    margin-bottom: 40px;
}

.login-header h1 {
    font-size: 32px;
    font-weight: 800;
    color: var(--primary-green-dark);
    margin-bottom: 8px;
    letter-spacing: -0.02em;
}

.login-header p {
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

.form-group input {
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

.form-group input:focus {
    border-color: var(--primary-green);
    box-shadow: 0 0 0 3px rgba(45, 134, 89, 0.1);
}

.form-group input::placeholder {
    color: #999;
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

.btn-outline {
    background: transparent;
    color: var(--primary-green);
    border: 2px solid var(--primary-green);
}

.btn-outline:hover {
    background: var(--primary-green);
    color: white;
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

/* Button Group */
.btn-group {
    display: flex;
    gap: 12px;
    margin-top: 8px;
}

.btn-group .btn {
    flex: 1;
    margin: 0;
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
    .login-container {
        padding: 32px 24px;
        margin: 10px;
    }

    .login-header h1 {
        font-size: 28px;
    }

    .btn-group {
        flex-direction: column;
    }
}

@media (max-width: 480px) {
    body {
        padding: 10px;
    }

    .login-container {
        padding: 24px 20px;
    }

    .login-header h1 {
        font-size: 24px;
    }
}

/* Success Message for Registration */
.registered-notice {
    background: #e8f5e9;
    color: var(--success-color);
    padding: 16px;
    border-radius: 12px;
    margin-bottom: 24px;
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    border: 1px solid #c8e6c9;
}
</style>
</head>
<body>

<!-- Language Toggle -->
<div class="lang-switch">
    <a href="?lang=en" class="<?php echo $lang === 'en' ? 'active' : ''; ?>">English</a>
    <a href="?lang=te" class="<?php echo $lang === 'te' ? 'active' : ''; ?>">తెలుగు</a>
</div>

<div class="login-container">
    <!-- Back Link -->
    <a href="index.php" class="back-link">
        <i class="fas fa-arrow-left"></i>
        <?php echo $translations['back'] ?? 'Back to Home'; ?>
    </a>

    <!-- Header -->
    <div class="login-header">
        <h1><i class="fas fa-sign-in-alt"></i> <?php echo $translations['login'] ?? 'Login'; ?></h1>
        <p><?php echo $translations['login_subtitle'] ?? 'Enter your email to receive OTP and login'; ?></p>
    </div>

    <!-- Registration Success Notice -->
    <?php if(isset($_GET['registered'])): ?>
    <div class="registered-notice">
        <i class="fas fa-check-circle"></i>
        <?php echo $translations['register_success'] ?? 'Registration successful! You can now login.'; ?>
    </div>
    <?php endif; ?>

    <!-- Message Alert -->
    <?php if($message): ?>
    <div class="message-alert <?php echo strpos($message, 'sent') !== false || strpos($message, 'success') !== false ? 'success' : 'error'; ?>">
        <i class="fas <?php echo strpos($message, 'sent') !== false || strpos($message, 'success') !== false ? 'fa-check-circle' : 'fa-exclamation-circle'; ?>"></i>
        <span><?php echo htmlspecialchars($message); ?></span>
    </div>
    <?php endif; ?>

    <?php if($showForm): ?>
    <!-- Email Input Form -->
    <form method="POST" id="loginForm">
        <input type="hidden" name="step" value="request_otp">
        
        <div class="form-group">
            <label for="email"><?php echo $translations['email'] ?? 'Email Address'; ?></label>
            <input 
                type="email" 
                id="email" 
                name="email" 
                placeholder="<?php echo $translations['enter_email'] ?? 'your.email@example.com'; ?>" 
                required 
                autofocus
            >
        </div>

        <button type="submit" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i>
            <?php echo $translations['send_otp'] ?? 'Send OTP'; ?>
        </button>
    </form>

    <div style="text-align: center; margin-top: 24px; color: var(--text-light); font-size: 14px;">
        <?php echo $translations['dont_have_account'] ?? "Don't have an account?"; ?>
        <a href="register.php" style="color: var(--primary-green); text-decoration: none; font-weight: 600;">
            <?php echo $translations['register'] ?? 'Register'; ?>
        </a>
    </div>

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
                <input 
                    type="text" 
                    id="otp" 
                    name="otp" 
                    class="otp-input" 
                    placeholder="000000" 
                    maxlength="6" 
                    pattern="[0-9]{6}" 
                    required 
                    autofocus
                >
            </div>

            <div class="btn-group">
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $translations['verify_otp'] ?? 'Verify OTP'; ?>
                </button>
            </div>
        </form>

        <form method="POST" style="margin-top: 12px;">
            <input type="hidden" name="step" value="resend_otp">
            <button type="submit" class="btn btn-outline">
                <i class="fas fa-redo"></i>
                <?php echo $translations['resend_otp'] ?? 'Resend OTP'; ?>
            </button>
        </form>

        <div style="text-align: center; margin-top: 24px;">
            <a href="login.php" class="back-link" style="display: inline-flex;">
                <i class="fas fa-arrow-left"></i>
                <?php echo $translations['back_to_login'] ?? 'Back to Login'; ?>
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
});
</script>

</body>
</html>
