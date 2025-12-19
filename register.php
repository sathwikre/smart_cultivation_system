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

/* Language toggle */
if(isset($_GET['lang'])){
    $_SESSION['lang'] = $_GET['lang'];
    header("Location: register.php");
    exit;
}
$lang = $_SESSION['lang'] ?? 'en';
$translations = include "languages/$lang.php";

/* OTP generator */
function generateOTP($length = 6){
    return rand(pow(10,$length-1), pow(10,$length)-1);
}

/* Send OTP email */
function sendOTPEmail($email, $otp){
    $mail = new PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'joyswapnilrajparadeshi@gmail.com';
        $mail->Password = 'kdbddmffsotggrjt';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;

        $mail->setFrom('joyswapnilrajparadeshi@gmail.com','Smart Cultivation System');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = "OTP for Registration";
        $mail->Body = "<b>Your OTP:</b> $otp <br>Valid for 5 minutes";

        $mail->send();
        return true;
    }catch(Exception $e){
        return $mail->ErrorInfo;
    }
}

/* Duplicate check */
function userExists($conn,$username,$email){
    $stmt = $conn->prepare("SELECT id FROM users WHERE username=? OR email=?");
    $stmt->bind_param("ss",$username,$email);
    $stmt->execute();
    return $stmt->get_result()->num_rows > 0;
}

$message = '';
$showForm = true;

/* STEP 1: REGISTER */
if(isset($_POST['step']) && $_POST['step']=='register'){

    $required = ['fullname','username','email','mobile','password','confirm_password','role','age','gender','aadhar','caste','total_land'];
    foreach($required as $r){
        if(empty($_POST[$r])){
            $message = "Please fill all fields";
            goto render;
        }
    }

    if($_POST['password'] !== $_POST['confirm_password']){
        $message = "Passwords do not match";
        goto render;
    }

    if(userExists($conn,$_POST['username'],$_POST['email'])){
        $message = "Username or Email already exists";
        goto render;
    }
if (empty($_POST['latitude']) || empty($_POST['longitude'])) {
    $message = ($lang=='te')
        ? 'దయచేసి మ్యాప్‌లో మీ స్థలాన్ని గుర్తించండి'
        : 'Please mark your location on the map';
    goto render;
}

if (empty($_POST['location_name'])) {
    $message = ($lang=='te')
        ? 'స్థల పేరు నమోదు చేయండి'
        : 'Please enter location name';
    goto render;
}


    $_SESSION['reg_data'] = [
        'fullname'=>$_POST['fullname'],
        'username'=>$_POST['username'],
        'email'=>$_POST['email'],
        'mobile'=>$_POST['mobile'],
        'password'=>password_hash($_POST['password'],PASSWORD_DEFAULT),
        'role'=>$_POST['role'],
        'district'=>$_POST['district'],
        'state'=>$_POST['state'],
        'age'=>$_POST['age'],
        'gender'=>$_POST['gender'],
        'aadhar'=>$_POST['aadhar'],
        'caste'=>$_POST['caste'],
'latitude' => $_POST['latitude'],
'longitude' => $_POST['longitude'],
'location_address' => $_POST['location_address'],

        'total_land'=>$_POST['total_land']
    ];

    $_SESSION['otp'] = generateOTP();
    $_SESSION['otp_time'] = time();

    $sent = sendOTPEmail($_POST['email'],$_SESSION['otp']);
    if($sent===true){
        $showForm = false;
        $message = "OTP sent to your email";
    }else{
        $message = "OTP failed: ".$sent;
    }
}

/* STEP 2: VERIFY OTP */
if(isset($_POST['step']) && $_POST['step']=='verify_otp'){
    if($_POST['otp']==$_SESSION['otp'] && time()-$_SESSION['otp_time']<=300){
        $d = $_SESSION['reg_data'];

        $stmt = $conn->prepare("
        INSERT INTO users
        (fullname,username,email,password,role,mobile,district,state,
         age,gender,aadhar,caste,total_land,created_at,latitude, longitude, location_address)
        VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,NOW())
        ");

        $stmt->bind_param(
            "ssssssssisssd",
            $d['fullname'],$d['username'],$d['email'],$d['password'],$d['role'],
            $d['mobile'],$d['district'],$d['state'],
            $d['age'],$d['gender'],$d['aadhar'],$d['caste'],$d['total_land'],$d['latitude'],
$d['longitude'],
$d['location_address'],
        );

        $stmt->execute();
        unset($_SESSION['reg_data'],$_SESSION['otp'],$_SESSION['otp_time']);
        header("Location: login.php?registered=1");
        exit;
    }else{
        $message="Invalid or expired OTP";
        $showForm=false;
    }
}

render:
?>

<!DOCTYPE html>
<html lang="<?= $lang ?>">
<head>
<meta charset="UTF-8">
<title>Register</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<style>
/* (your existing CSS here) */
/* Your existing CSS goes here (same as current register.php) */ /* CSS same as your current code */ *{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;} body{overflow-x:hidden;} body::before{ content:"";position:fixed;top:0;left:0;width:100%;height:100%; background: linear-gradient(135deg,#00c6ff,#0072ff,#00ffb0,#00c6ff); background-size:400% 400%;animation: gradientBG 20s ease infinite;z-index:-2; } @keyframes gradientBG{0%{background-position:0% 50%;}50%{background-position:100% 50%;}100%{background-position:0% 50%;}} .icon-bg{position:fixed;font-size:2.5rem;color:rgba(255,255,255,0.15);animation: floatBg 15s linear infinite;z-index:-1;} .icon-bg:nth-child(1){top:10%; left:5%; animation-duration:20s;} .icon-bg:nth-child(2){top:30%; left:85%; animation-duration:18s;} .icon-bg:nth-child(3){top:70%; left:10%; animation-duration:22s;} .icon-bg:nth-child(4){top:50%; left:50%; animation-duration:25s;} .icon-bg:nth-child(5){top:80%; left:80%; animation-duration:30s;} @keyframes floatBg{0%{transform: translateY(0) rotate(0deg);}50%{transform: translateY(-30px) rotate(180deg);}100%{transform: translateY(0) rotate(360deg);}} /* Language switch */ .lang-switch{position:absolute; top:20px; right:30px; z-index:10;} .lang-switch a{margin:0 10px; padding:8px 15px; border-radius:25px; background:rgba(255,255,255,0.3); color:#fff; font-weight:600; transition:0.3s;} .lang-switch a:hover{background:rgba(255,255,255,0.6);} /* Container */ .container{display:flex; flex-direction:column; justify-content:center; align-items:center; min-height:100vh; padding:20px; text-align:center;} .hero h1{font-size:4rem; font-weight:900; text-transform:uppercase; background: linear-gradient(90deg,#ffd700,#ff8c00,#ff0000,#ffd700); background-size:400%; -webkit-background-clip:text; -webkit-text-fill-color:transparent; animation: gradientText 6s linear infinite, floatHeading 3s ease-in-out infinite; margin-bottom:20px;} .hero p{font-size:1.3rem;color:rgba(255,255,255,0.9);margin-bottom:40px;animation: fadeIn 2s ease-in-out;} @keyframes gradientText{0%{background-position:0%;}50%{background-position:100%;}100%{background-position:0%;}} @keyframes floatHeading{0%,100%{transform:translateY(0);}50%{transform:translateY(-15px);}} /* Form box */ .form-box{background: rgba(255,255,255,0.1);padding:35px;border-radius:25px;backdrop-filter: blur(10px);width:100%; max-width:500px;box-shadow:0 15px 40px rgba(0,0,0,0.5);animation: fadeIn 2s ease-in-out;} .form-box input, .form-box select{width:100%; padding:14px 18px; margin:12px 0; border:none; border-radius:12px; outline:none; font-size:1rem; background: rgba(255,255,255,0.2); color:#fff; transition:0.3s;} .form-box input::placeholder{color:rgba(255,255,255,0.7);} .form-box input:focus, .form-box select:focus{background: rgba(255,255,255,0.3); transform:scale(1.02);} .btn{display:inline-block; margin:15px 0; padding:15px 45px; font-size:1.2rem; font-weight:700; border:none; border-radius:50px; cursor:pointer; transition:0.4s; background: linear-gradient(45deg,#ffd700,#ff8c00,#ff0000); color:#fff; box-shadow:0 8px 25px rgba(0,0,0,0.4); position:relative; overflow:hidden;} .btn:before{content:''; position:absolute; top:0; left:-100%; width:100%; height:100%; background:rgba(255,255,255,0.2); transition:0.4s;} .btn:hover:before{left:100%;} .btn:hover{transform:translateY(-5px) scale(1.02); box-shadow:0 15px 35px rgba(0,0,0,0.5);} @keyframes fadeIn{0%{opacity:0; transform:translateY(20px);}100%{opacity:1; transform:translateY(0);}} /* Messages */ .message{color:#fff; margin:15px 0; font-weight:600;}
*{margin:0;padding:0;box-sizing:border-box;font-family:'Poppins',sans-serif;}
body{overflow-x:hidden;}
/* ... keep the rest of your CSS from before ... */
.form-box{background: rgba(255,255,255,0.1);padding:35px;border-radius:25px;backdrop-filter: blur(10px);width:100%; max-width:500px;box-shadow:0 15px 40px rgba(0,0,0,0.5);animation: fadeIn 2s ease-in-out;}
.message{color:#fff; margin:15px 0; font-weight:600;}
</style>
<!-- Leaflet Map -->
<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css"/>
<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

</head>
<body>
<div class="container">
<h1><?= $translations['register'] ?? 'Register' ?></h1>
<?php if($message) echo "<p>$message</p>"; ?>

<div class="form-box">
<?php if($showForm): ?>
<form method="POST">
<input type="hidden" name="step" value="register">

<input name="fullname" placeholder="<?= $translations['fullname'] ?? 'Full Name' ?>">
<input name="username" placeholder="<?= $translations['username'] ?? 'Username' ?>">
<input name="email" placeholder="<?= $translations['email'] ?? 'Email' ?>">
<input name="mobile" placeholder="<?= $translations['mobile'] ?? 'Mobile' ?>">

<input type="number" name="age" placeholder="<?= $lang=='te'?'వయస్సు':'Age' ?>">
<select name="gender" required>
    <option value=""><?= $translations['gender'] ?? 'Gender' ?></option>
    <option value="Male"><?= $translations['male'] ?? 'Male' ?></option>
    <option value="Female"><?= $translations['female'] ?? 'Female' ?></option>
    <option value="Other"><?= $translations['other'] ?? 'Other' ?></option>
</select>

<input name="aadhar" placeholder="<?= $lang=='te'?'ఆధార్ నంబర్':'Aadhar Number' ?>" maxlength="12">
<input type="text" name="caste"
       placeholder="<?= $translations['caste'] ?? 'Caste' ?>" required>

<input name="total_land" placeholder="<?= $lang=='te'?'మొత్తం భూమి విస్తీర్ణం':'Total Land Area' ?>">

<input type="password" name="password"
       placeholder="<?= $translations['password'] ?? 'Password' ?>" required>

<input type="password" name="confirm_password"
       placeholder="<?= $translations['confirm_password'] ?? 'Confirm Password' ?>" required>
<select name="role" required>
    <option value="farmer"><?= $translations['farmer'] ?? 'Farmer' ?></option>
    <option value="admin"><?= $translations['admin'] ?? 'Admin' ?></option>
</select>

<!-- LOCATION PICKER -->
<p style="color:white;font-weight:600;">
<?= $lang=='te'
    ? 'మ్యాప్‌లో మీ భూమి స్థానాన్ని గుర్తించండి మరియు స్థలాన్ని నమోదు చేయండి'
    : 'Mark your land on the map and enter location name'
?>
</p>

<!-- Manual Location Name -->
<input type="text"
       name="location_name"
       id="location_name"
       placeholder="<?= $lang=='te'
           ? 'గ్రామం / మండలం / జిల్లా'
           : 'Village / Mandal / District'
       ?>"
       required>

<!-- Hidden coordinates -->
<input type="hidden" name="latitude" id="latitude" required>
<input type="hidden" name="longitude" id="longitude" required>

<!-- Optional district (auto / manual) -->
<input type="text"
       name="district"
       id="district"
       placeholder="<?= $lang=='te' ? 'జిల్లా' : 'District' ?>">

<div id="map"
     style="height:350px; width:100%; margin-top:15px; border-radius:15px;">
</div>
<button type="submit">Register</button>
</form>

<?php else: ?>
<form method="POST">
<input type="hidden" name="step" value="verify_otp">
<input name="otp" placeholder="Enter OTP">
<button type="submit">Verify OTP</button>
</form>
<?php endif; ?>
</div>
</div>
<script>
document.addEventListener("DOMContentLoaded", function () {

    var map = L.map('map').setView([20.5937, 78.9629], 5);
    var marker;

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap'
    }).addTo(map);

    map.on('click', function (e) {
        var lat = e.latlng.lat;
        var lon = e.latlng.lng;

        document.getElementById('latitude').value = lat;
        document.getElementById('longitude').value = lon;

        if (marker) {
            marker.setLatLng([lat, lon]);
        } else {
            marker = L.marker([lat, lon]).addTo(map);
        }

        // Try to fetch district only (optional)
        fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lon}`)
            .then(res => res.json())
            .then(data => {
                if (data.address && data.address.county) {
                    document.getElementById('district').value = data.address.county;
                } else if (data.address && data.address.state_district) {
                    document.getElementById('district').value = data.address.state_district;
                }
            })
            .catch(() => {
                // silently fail – manual input allowed
            });
    });

});
</script>


</body>
</html>
