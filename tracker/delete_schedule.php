<?php
session_start();
require 'db_connect.php'; 

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid Schedule ID for deletion.";
    header("Location: dashboard_schedule.php");
    exit();
}
$schedule_id = intval($_GET['id']);

try {
    // --- NOTIFICATION LOGIC ---
    // 1. Get schedule details BEFORE deleting
    $stmt_get = $pdo->prepare("SELECT driver_id, assistant_id, route_description FROM schedules WHERE schedule_id = ?");
    $stmt_get->execute([$schedule_id]);
    $schedule = $stmt_get->fetch(PDO::FETCH_ASSOC);

    if ($schedule) {
        // 2. Find the users to notify
        $userIds = [];
        if (!empty($schedule['driver_id'])) {
            $userStmt = $pdo->prepare("SELECT user_id FROM truck_driver WHERE driver_id = ? AND user_id IS NOT NULL");
            $userStmt->execute([$schedule['driver_id']]);
            $driverUser = $userStmt->fetch();
            if ($driverUser) $userIds[] = $driverUser['user_id'];
        }
        if (!empty($schedule['assistant_id'])) {
            $userStmt = $pdo->prepare("SELECT user_id FROM truck_assistant WHERE assistant_id = ? AND user_id IS NOT NULL");
            $userStmt->execute([$schedule['assistant_id']]);
            $assistantUser = $userStmt->fetch();
            if ($assistantUser) $userIds[] = $assistantUser['user_id'];
        }

        // 3. Create the notification
        foreach (array_unique($userIds) as $userId) {
            $message = "Your schedule has been cancelled: " . $schedule['route_description'];
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'deleted')");
            $notifStmt->execute([$userId, $message]);
        }
    }
    // --- END NOTIFICATION LOGIC ---

    // 4. Now, delete the schedule
    $sql_delete = "DELETE FROM schedules WHERE schedule_id = ?";
    $stmt_delete = $pdo->prepare($sql_delete);
    $stmt_delete->execute([$schedule_id]);

    if ($stmt_delete->rowCount() > 0) {
        $_SESSION['message'] = "Schedule (ID: $schedule_id) deleted successfully!";
    } else {
        $_SESSION['error'] = "Schedule (ID: $schedule_id) not found.";
    }

} catch (\PDOException $e) {
    error_log("Error deleting schedule (ID: $schedule_id): " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while deleting the schedule.";
}

header("Location: dashboard_schedule.php");
exit();
?>