<?php
session_start();
require 'db_connection.php';
header('Content-Type: application/json');

if(!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin'){
    echo json_encode(['status'=>'error','message'=>'Unauthorized']); exit;
}

$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
$crop_name = isset($_POST['crop_name']) ? trim($_POST['crop_name']) : '';
$variety = isset($_POST['variety']) ? trim($_POST['variety']) : null;
$field = isset($_POST['field']) ? trim($_POST['field']) : null;
$planting_date = isset($_POST['planting_date']) && $_POST['planting_date'] !== '' ? $_POST['planting_date'] : null;
$growth_stage = isset($_POST['growth_stage']) ? trim($_POST['growth_stage']) : 'Seed';
$yield = isset($_POST['yield']) ? trim($_POST['yield']) : null;
$notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;

if($user_id <= 0 || $crop_name === ''){
    echo json_encode(['status'=>'error','message'=>'Required fields missing']); exit;
}

// ensure farmer exists
$chk = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'farmer'");
$chk->bind_param("i",$user_id);
$chk->execute();
$g = $chk->get_result();
if($g->num_rows === 0){
    echo json_encode(['status'=>'error','message'=>'Selected farmer not found']); $chk->close(); exit;
}
$chk->close();

// insert crop
$ins = $conn->prepare("INSERT INTO farmer_crops (user_id, crop_name, variety, field, planting_date, growth_stage, yield, notes, last_updated)
                       VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
$ins->bind_param("isssssss", $user_id, $crop_name, $variety, $field, $planting_date, $growth_stage, $yield, $notes);
if($ins->execute()){
    echo json_encode(['status'=>'success','id'=>$ins->insert_id]);
} else {
    echo json_encode(['status'=>'error','message'=>'Insert failed']);
}
$ins->close();
