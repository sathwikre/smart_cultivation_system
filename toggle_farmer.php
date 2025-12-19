<?php
session_start();
require "db_connection.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;
$action = $_GET['action'] ?? "";

// Fetch farmer
$stmt = $conn->prepare("SELECT status FROM users WHERE id=? AND role='farmer'");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$farmer = $result->fetch_assoc();
$stmt->close();

if(!$farmer){
    die("Invalid farmer!");
}

// Activate
if($action === "activate"){
    $stmt = $conn->prepare("UPDATE users SET status='active' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Farmer Activated'); window.location='farmers_admin.php';</script>";
    exit;
}

// Deactivate
if($action === "deactivate"){
    $stmt = $conn->prepare("UPDATE users SET status='inactive' WHERE id=?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();

    echo "<script>alert('Farmer Deactivated'); window.location='farmers_admin.php';</script>";
    exit;
}

echo "Invalid action";
?>
