<?php
session_start();
require 'db_connection.php';
if(!isset($_SESSION['user_id'])) exit;

$notif_stmt = $conn->prepare("SELECT id, crop_name, message, notify_date, status FROM crop_notifications WHERE user_id=? ORDER BY notify_date DESC");
$notif_stmt->bind_param("i", $_SESSION['user_id']);
$notif_stmt->execute();
$result = $notif_stmt->get_result();
while($n = $result->fetch_assoc()){
    $d = date("d M Y", strtotime($n['notify_date']));
    $statusClass = ($n['status']=='unread')?'unread':'read';
    echo "<li class='$statusClass' data-id='{$n['id']}'>[{$d}] {$n['message']} ({$n['crop_name']})";
    if($n['status']=='unread') echo " <button class='mark-btn' data-id='{$n['id']}'>Mark as Read</button>";
    echo "</li>";
}
$notif_stmt->close();
