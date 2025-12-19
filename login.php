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
        $stmt->bind_param("s",$email);
        $stmt->execute();
        $res = $stmt->get_result();
        if($res && $res->num_rows > 0){
            $user = $res->fetch_assoc();
            $otp = generateOTP();
            $expiry = date("Y-m-d H:i:s", strtotime("+5 minutes"));

            $update = $conn->prepare("UPDATE users SET otp=?, otp_expiry=? WHERE id=?");
            $update->bind_param("ssi",$otp,$expiry,$user['id']);
            $update->execute();

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
            $message = $translations['email_not_found'] ?? "Email not found. Please register first.";
        }
        $stmt->close();
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
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* Copy same CSS from register.php for consistency */
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;} body{overflow-x:hidden;} body::before{ content:"";position:fixed;top:0;left:0;width:100%;height:100%; background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff); background-size:400% 400%;animation: gradientBG 20s ease infinite;z-index:-2; } @keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}} .icon-bg{position:fixed;font-size:2.5rem;color:rgba(255,255,255,0.15);animation: floatBg 15s linear infinite;z-index:-1;} .icon-bg:nth-child(1){top:10%; left:5%; animation-duration:20s;} .icon-bg:nth-child(2){top:30%; left:85%; animation-duration:18s;} .icon-bg:nth-child(3){top:70%; left:10%; animation-duration:22s;} .icon-bg:nth-child(4){top:50%; left:50%; animation-duration:25s;} .icon-bg:nth-child(5){top:80%; left:80%; animation-duration:30s;} @keyframes floatBg{0%{transform: translateY(0) rotate(0deg);}50%{transform: translateY(-30px) rotate(180deg);}100%{transform: translateY(0) rotate(360deg);}} .container{display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; padding:20px; text-align:center;} .hero h1{font-size:4rem; font-weight:900; text-transform:uppercase; background: linear-gradient(90deg,#ffd700,#ff8c00,#ff0000,#ffd700); background-size:400%; -webkit-background-clip:text; -webkit-text-fill-color:transparent; animation: gradientText 6s linear infinite, floatHeading 3s ease-in-out infinite; margin-bottom:20px;} .hero p{font-size:1.3rem;color:rgba(255,255,255,0.9);margin-bottom:40px;animation: fadeIn 2s ease-in-out;} @keyframes gradientText{0%{background-position:0%;}50%{background-position:100%;}100%{background-position:0%;}} @keyframes floatHeading{0%,100%{transform:translateY(0);}50%{transform:translateY(-15px);}} .form-box{background: rgba(255,255,255,0.1);padding:35px;border-radius:25px;backdrop-filter: blur(10px);width:100%; max-width:500px;box-shadow:0 15px 40px rgba(0,0,0,0.5);animation: fadeIn 2s ease-in-out;} .form-box input{width:100%; padding:14px 18px; margin:12px 0; border:none; border-radius:12px; outline:none; font-size:1rem; background: rgba(255,255,255,0.2); color:#fff; transition:0.3s;} .form-box input::placeholder{color:rgba(255,255,255,0.7);} .form-box input:focus{background: rgba(255,255,255,0.3); transform:scale(1.02);} .btn{display:inline-block; margin:15px 0; padding:15px 45px; font-size:1.2rem; font-weight:700; border:none; border-radius:50px; cursor:pointer; transition:0.4s; background: linear-gradient(45deg,#ffd700,#ff8c00,#ff0000); color:#fff; box-shadow:0 8px 25px rgba(0,0,0,0.4); position:relative; overflow:hidden;} .btn:before{content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:rgba(255,255,255,0.2); transition:0.4s;} .btn:hover:before{left:100%;} .btn:hover{transform:translateY(-5px) scale(1.02); box-shadow:0 15px 35px rgba(0,0,0,0.5);} .message{color:#fff; margin:15px 0; font-weight:600;} .lang-switch{position:absolute; top:20px; right:30px; z-index:10;} .lang-switch a{margin:0 10px; padding:8px 15px; border-radius:25px; background:rgba(255,255,255,0.3); color:#fff; font-weight:600; transition:0.3s;} .lang-switch a:hover{background:rgba(255,255,255,0.6);}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{overflow-x:hidden;}
body::before{ content:"";position:fixed;top:0;left:0;width:100%;height:100%; background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff); background-size:400% 400%;animation: gradientBG 20s ease infinite;z-index:-2; } 
@keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}}
.icon-bg{position:fixed;font-size:2.5rem;color:rgba(255,255,255,0.15);animation: floatBg 15s linear infinite;z-index:-1;}
.icon-bg:nth-child(1){top:10%; left:5%; animation-duration:20s;} 
.icon-bg:nth-child(2){top:30%; left:85%; animation-duration:18s;} 
.icon-bg:nth-child(3){top:70%; left:10%; animation-duration:22s;} 
.icon-bg:nth-child(4){top:50%; left:50%; animation-duration:25s;} 
.icon-bg:nth-child(5){top:80%; left:80%; animation-duration:30s;} 
@keyframes floatBg{0%{transform: translateY(0) rotate(0deg);}50%{transform: translateY(-30px) rotate(180deg);}100%{transform: translateY(0) rotate(360deg);}}
.container{display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; padding:20px; text-align:center;}
.hero h1{font-size:4rem; font-weight:900; text-transform:uppercase; background: linear-gradient(90deg,#ffd700,#ff8c00,#ff0000,#ffd700); background-size:400%; -webkit-background-clip:text; -webkit-text-fill-color:transparent; animation: gradientText 6s linear infinite, floatHeading 3s ease-in-out infinite; margin-bottom:20px;}
.hero p{font-size:1.3rem;color:rgba(255,255,255,0.9);margin-bottom:40px;animation: fadeIn 2s ease-in-out;}
@keyframes gradientText{0%{background-position:0%;}50%{background-position:100%;}100%{background-position:0%;}} 
@keyframes floatHeading{0%,100%{transform:translateY(0);}50%{transform:translateY(-15px);}} 
.form-box{background: rgba(255,255,255,0.1);padding:35px;border-radius:25px;backdrop-filter: blur(10px);width:100%; max-width:500px;box-shadow:0 15px 40px rgba(0,0,0,0.5);animation: fadeIn 2s ease-in-out;}
.form-box input{width:100%; padding:14px 18px; margin:12px 0; border:none; border-radius:12px; outline:none; font-size:1rem; background: rgba(255,255,255,0.2); color:#fff; transition:0.3s;}
.form-box input::placeholder{color:rgba(255,255,255,0.7);}
.form-box input:focus{background: rgba(255,255,255,0.3); transform:scale(1.02);}
.btn{display:inline-block; margin:15px 0; padding:15px 45px; font-size:1.2rem; font-weight:700; border:none; border-radius:50px; cursor:pointer; transition:0.4s; background: linear-gradient(45deg,#ffd700,#ff8c00,#ff0000); color:#fff; box-shadow:0 8px 25px rgba(0,0,0,0.4); position:relative; overflow:hidden;}
.btn:before{content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:rgba(255,255,255,0.2); transition:0.4s;}
.btn:hover:before{left:100%;} 
.btn:hover{transform:translateY(-5px) scale(1.02); box-shadow:0 15px 35px rgba(0,0,0,0.5);}
.message{color:#fff; margin:15px 0; font-weight:600;}
.lang-switch{position:absolute; top:20px; right:30px; z-index:10;}
.lang-switch a{margin:0 10px; padding:8px 15px; border-radius:25px; background:rgba(255,255,255,0.3); color:#fff; font-weight:600; transition:0.3s;}
.lang-switch a:hover{background:rgba(255,255,255,0.6);}
</style>
</head>
<body>
<div class="lang-switch">
    <a href="?lang=en">English</a> | <a href="?lang=te">తెలుగు</a>
</div>

<!-- Floating icons -->
<i class="fas fa-leaf icon-bg"></i>
<i class="fas fa-seedling icon-bg"></i>
<i class="fas fa-tractor icon-bg"></i>
<i class="fas fa-water icon-bg"></i>
<i class="fas fa-sun icon-bg"></i>

<div class="container">
    <div class="hero">
        <h1><?php echo $translations['login'] ?? 'Login'; ?></h1>
        <p><?php echo $translations['login_subtitle'] ?? 'Enter your email to receive OTP and login'; ?></p>
    </div>
    <?php if($message) echo "<div class='message'>$message</div>"; ?>

    <div class="form-box">
        <?php if($showForm): ?>
        <form method="POST">
            <input type="hidden" name="step" value="request_otp">
            <input type="email" name="email" placeholder="<?php echo $translations['email'] ?? 'Enter your email'; ?>" required>
            <button type="submit" class="btn"><?php echo $translations['send_otp'] ?? 'Send OTP'; ?></button>
        </form>
        <?php else: ?>
        <form method="POST" style="display:inline-block; width:60%;">
            <input type="hidden" name="step" value="verify_otp">
            <input type="text" name="otp" placeholder="<?php echo $translations['enter_otp'] ?? 'Enter OTP'; ?>" required>
            <button type="submit" class="btn"><?php echo $translations['verify_otp'] ?? 'Verify OTP'; ?></button>
        </form>
        <form method="POST" style="display:inline-block; margin-left:10px;">
            <input type="hidden" name="step" value="resend_otp">
            <button type="submit" class="btn" style="background:#0099ff;"><?php echo $translations['resend_otp'] ?? 'Resend OTP'; ?></button>
        </form>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
