<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$id = isset($_POST['id']) ? intval($_POST['id']) : 0;
$growth_stage = isset($_POST['growth_stage']) ? trim($_POST['growth_stage']) : '';

if($id <= 0 || $growth_stage === ''){
    echo json_encode(['status'=>'error','message'=>'Invalid input']); exit;
}

// fetch crop + farmer info
$stmt = $conn->prepare("SELECT user_id, crop_name FROM farmer_crops WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();
if($res->num_rows === 0){
    echo json_encode(['status'=>'error','message'=>'Crop not found']); $stmt->close(); exit;
}
$row = $res->fetch_assoc();
$user_id = (int)$row['user_id'];
$crop_name = $row['crop_name'];
$stmt->close();

// update stage
$u = $conn->prepare("UPDATE farmer_crops SET growth_stage = ?, last_updated = NOW() WHERE id = ?");
$u->bind_param("si", $growth_stage, $id);
if(!$u->execute()){
    echo json_encode(['status'=>'error','message'=>'Update failed']); $u->close(); exit;
}
$u->close();

// insert into crop_notifications table
$notify_stmt = $conn->prepare("INSERT INTO crop_notifications (user_id, crop_name, message, notify_date, status) VALUES (?, ?, ?, CURDATE(), 'unread')");
$message = "Admin updated your crop '{$crop_name}' to stage: {$growth_stage}";
$notify_stmt->bind_param("iss", $user_id, $crop_name, $message);
$notify_stmt->execute();
$notify_stmt->close();

// return success + last updated timestamp
$fetch = $conn->prepare("SELECT DATE_FORMAT(last_updated, '%d %b %Y %H:%i') AS lu FROM farmer_crops WHERE id = ?");
$fetch->bind_param("i",$id);
$fetch->execute();
$r = $fetch->get_result()->fetch_assoc();
$last = $r['lu'] ?? date('d M Y H:i');
$fetch->close();

echo json_encode(['status'=>'success','last_updated'=>$last]);
