<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
if($id <= 0){ echo json_encode(['status'=>'error','message'=>'Invalid ID']); exit; }

// Optionally: fetch crop info for audit/notification (not sending notification on delete currently)
$del = $conn->prepare("DELETE FROM farmer_crops WHERE id = ?");
$del->bind_param("i",$id);
if($del->execute()){
    echo json_encode(['status'=>'success']);
} else {
    echo json_encode(['status'=>'error','message'=>'Delete failed']);
}
$del->close();
