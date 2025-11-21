<?php
// FILE: amgcs_app/get_app_notifications.php
header('Content-Type: application/json');
$pdo = require_once 'db_connect.php';
$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;

if (!$pdo || $userId === 0) { exit; }

try {
    $stmt = $pdo->prepare("SELECT notification_id, message FROM notifications WHERE user_id = ? AND is_read = FALSE");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'notifications' => $notifications]);
} catch (PDOException $e) {
    exit;
}
?>