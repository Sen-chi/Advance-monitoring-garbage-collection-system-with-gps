<?php
session_start();
require 'db_connect.php'; 

// Validate and get schedule ID from URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "Invalid Schedule ID for deletion.";
    header("Location: dashboard_schedule.php");
    exit();
}
$schedule_id = intval($_GET['id']);

// Delete from database using Prepared Statement
$sql = "DELETE FROM schedules WHERE schedule_id = ?";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$schedule_id]);

    // Check if a row was actually deleted
    if ($stmt->rowCount() > 0) {
        $_SESSION['message'] = "Schedule (ID: $schedule_id) deleted successfully!";
    } else {
        // This means the ID didn't exist, maybe already deleted
        $_SESSION['error'] = "Schedule (ID: $schedule_id) not found or already deleted.";
    }

} catch (\PDOException $e) {
    error_log("Error deleting schedule (ID: $schedule_id): " . $e->getMessage());
    // Check for foreign key constraint errors if applicable (though ON DELETE CASCADE might handle it)
    $_SESSION['error'] = "An error occurred while deleting the schedule. It might be referenced elsewhere if constraints are restrictive.";
}

// Redirect back to the list view regardless of outcome
header("Location: dashboard_schedule.php");
exit();
?>