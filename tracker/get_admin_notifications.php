<?php
// FILE: tracker/get_admin_notifications.php
header('Content-Type: application/json');
require 'db_connect.php';

/*
 * This script fetches schedules that have been marked as 'Completed'
 * but for which the admin has not yet been notified (is_notified = FALSE).
 */

$sql = "SELECT
            s.schedule_id,
            s.date,
            s.route_description,
            s.driver_name
        FROM schedules s
        WHERE s.status = 'Completed' AND s.is_notified = FALSE
        ORDER BY s.date DESC";

try {
    $stmt = $pdo->query($sql);
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // If we found new notifications, mark them as "notified" in the database
    // so they don't show up again in the next check.
    if (!empty($notifications)) {
        $ids = array_column($notifications, 'schedule_id');
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        
        $updateSql = "UPDATE schedules SET is_notified = TRUE WHERE schedule_id IN ($placeholders)";
        $updateStmt = $pdo->prepare($updateSql);
        $updateStmt->execute($ids);
    }

    echo json_encode(['success' => true, 'notifications' => $notifications]);

} catch (PDOException $e) {
    // Return a JSON error if something goes wrong
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>