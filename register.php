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
<html>
<head>
<meta charset="UTF-8">
<title>Register</title>
</head>
<body>

<h2>Register</h2>
<p style="color:red;"><?php echo $message; ?></p>

<?php if ($showForm): ?>
<form method="POST">
<input type="hidden" name="step" value="register">

<input name="fullname" placeholder="Full Name"><br>
<input name="username" placeholder="Username"><br>
<input name="email" placeholder="Email"><br>
<input name="mobile" placeholder="Mobile"><br>
<input name="age" placeholder="Age"><br>
<input name="aadhar" placeholder="Aadhar"><br>
<input name="caste" placeholder="Caste"><br>
<input name="total_land" placeholder="Total Land"><br>

<select name="gender">
    <option value="">Gender</option>
    <option>Male</option>
    <option>Female</option>
</select><br>

<select name="role">
    <option value="farmer">Farmer</option>
    <option value="admin">Admin</option>
</select><br>

<input type="password" name="password" placeholder="Password"><br>
<input type="password" name="confirm_password" placeholder="Confirm Password"><br>

<input name="location_name" placeholder="Location"><br>
<input type="hidden" name="latitude" value="17.3850">
<input type="hidden" name="longitude" value="78.4867">

<button type="submit">Register</button>
</form>

<?php else: ?>
<form method="POST">
<input type="hidden" name="step" value="verify_otp">
<input name="otp" placeholder="Enter OTP">
<button type="submit">Verify OTP</button>
</form>
<?php endif; ?>

</body>
</html>
