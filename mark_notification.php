<?php
session_start();
require 'db_connection.php';
if(!isset($_SESSION['user_id'])) exit;

$id = $_POST['id'] ?? 0;
$stmt = $conn->prepare("UPDATE crop_notifications SET status='read' WHERE id=? AND user_id=?");
$stmt->bind_param("ii",$id,$_SESSION['user_id']);
if($stmt->execute()){
    echo json_encode(['status'=>'success']);
} else echo json_encode(['status'=>'error']);
$stmt->close();
