<?php
// FILE: amgcs_app/get_all_my_notifications.php

header('Content-Type: application/json');
ini_set('display_errors', 0); // Hide potential HTML errors

// Use your standard database connection method
$pdo = require_once 'db_connect.php';

// Get the user ID from the app's request
$userId = isset($_GET['userId']) ? (int)$_GET['userId'] : 0;

// If there's no connection or no user ID, stop
if (!$pdo || $userId === 0) { 
    echo json_encode(['success' => false, 'message' => 'Invalid request.']);
    exit; 
}

try {
    // This query gets ALL notifications for the user, newest first.
    $stmt = $pdo->prepare("SELECT message, created_at FROM notifications WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([$userId]);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Send the results back to the app as JSON
    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (PDOException $e) {
    // If there's a database error, send a failure message
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query error.']);
    exit;
}
?>