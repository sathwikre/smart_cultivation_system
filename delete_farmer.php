<?php
session_start();
require "db_connection.php";

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

$id = $_GET['id'] ?? 0;

// Delete related crops
$stmt = $conn->prepare("DELETE FROM farmer_crops WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Delete notifications
$stmt = $conn->prepare("DELETE FROM crop_notifications WHERE user_id=?");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

// Delete farmer account
$stmt = $conn->prepare("DELETE FROM users WHERE id=? AND role='farmer'");
$stmt->bind_param("i", $id);
$stmt->execute();
$stmt->close();

echo "<script>alert('Farmer Deleted Successfully'); window.location='farmers_admin.php';</script>";
?>
