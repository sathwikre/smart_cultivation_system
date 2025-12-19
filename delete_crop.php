<?php
session_start();
require 'db_connection.php';

if(!isset($_SESSION['user_id']) || $_SESSION['role'] != 'admin'){
    header("Location: login.php");
    exit;
}

if(isset($_GET['id'])){
    $crop_id = $_GET['id'];
    $stmt = $conn->prepare("DELETE FROM farmer_crops WHERE id=?");
    $stmt->bind_param("i", $crop_id);
    $stmt->execute();
    $stmt->close();
}

header("Location: crop_management.php");
exit;
?>
