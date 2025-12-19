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
<html>
<head>
<title>Edit Farmer</title>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;600&display=swap" rel="stylesheet">

<style>
body{font-family:'Poppins'; background:#f1f1f1; padding:40px;}
form{background:#fff; padding:25px; border-radius:12px; width:450px; margin:auto;
     box-shadow:0 4px 18px rgba(0,0,0,0.15);}
input{width:100%; padding:10px; margin-bottom:12px; border-radius:8px; border:1px solid #ccc;}
button{padding:12px; background:#28a745; color:#fff; border-radius:10px; width:100%; font-weight:bold;}
button:hover{background:#1e7e34;}
</style>
</head>

<body>

<h2 style="text-align:center;">Edit Farmer</h2>

<form method="POST">
    <label>Full Name</label>
    <input type="text" name="fullname" required value="<?= $farmer['fullname'] ?>">

    <label>Email</label>
    <input type="email" name="email" required value="<?= $farmer['email'] ?>">

    <label>Mobile</label>
    <input type="text" name="mobile" required value="<?= $farmer['mobile'] ?>">

    <label>District</label>
    <input type="text" name="district" value="<?= $farmer['district'] ?>">

    <label>State</label>
    <input type="text" name="state" value="<?= $farmer['state'] ?>">

    <label>New Password (optional)</label>
    <input type="password" name="password" placeholder="Leave empty to keep unchanged">

    <button type="submit" name="update">Update Farmer</button>
</form>

</body>
</html>
