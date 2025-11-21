<?php
// FILE: amgcs_app/mark_notification_read.php
header('Content-Type: application/json');
$pdo = require_once 'db_connect.php';
$notificationId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($pdo && $notificationId > 0) {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = TRUE WHERE notification_id = ?");
    $stmt->execute([$notificationId]);
}
?>