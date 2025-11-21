<?php
// update_schedule_process.php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // --- FORM DATA VALIDATION ---

    // Updated required fields to check for 'driver_id'
    $required_fields = [
        'schedule_id', 'date', 'start_time', 'end_time', 'route_description',
        'driver_id', 'truck_id', 'waste_type', 'days'
    ];
    
    $errors = [];

    // 1. Check schedule_id first
    if (!isset($_POST['schedule_id']) || !is_numeric($_POST['schedule_id'])) {
        $errors[] = "Invalid Schedule ID.";
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: dashboard_schedule.php"); 
        exit();
    }
    $schedule_id = intval($_POST['schedule_id']);

    // 2. Validate all required fields
    foreach ($required_fields as $field) {
        if ($field === 'schedule_id') continue;

        if (in_array($field, ['days', 'route_description'])) {
            if (!isset($_POST[$field]) || !is_array($_POST[$field]) || empty($_POST[$field])) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        } 
        elseif (in_array($field, ['waste_type'])) {
            if (empty(trim($_POST[$field]))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        } 
        elseif (empty($_POST[$field])) {
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    // 3. Time validation
    if (!empty($_POST['start_time']) && !empty($_POST['end_time'])) {
        if ($_POST['end_time'] <= $_POST['start_time']) {
            $errors[] = "End Time must be later than Start Time.";
        }
    }
    
    // 4. Fetch Driver's Name from the submitted driver_id
    $driver_name = null;
    if (empty($errors) && !empty($_POST['driver_id'])) {
        try {
            $stmt_driver = $pdo->prepare("SELECT CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name FROM truck_driver WHERE driver_id = ?");
            $stmt_driver->execute([$_POST['driver_id']]);
            $driver_data = $stmt_driver->fetch(PDO::FETCH_ASSOC);

            if ($driver_data) {
                $driver_name = $driver_data['formatted_name'];
            } else {
                $errors[] = "The selected driver could not be found.";
            }
        } catch (\PDOException $e) {
            $errors[] = "Error fetching driver details: " . $e->getMessage();
        }
    }

    // 5. Redirect back if errors occurred
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST;
        header("Location: edit_schedule.php?id=" . $schedule_id);
        exit();
    }

    // --- DATABASE UPDATE ---

    // Prepare data for SQL execution
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $truck_id = $_POST['truck_id'];
    $driver_id = $_POST['driver_id']; // <-- ADDED: Get driver_id
    $assistant_id = !empty($_POST['assistant_id']) ? $_POST['assistant_id'] : null; // <-- ADDED: Get assistant_id
    $waste_type = trim($_POST['waste_type']);
    $days = implode(',', $_POST['days']);
    $route_description = implode(',', $_POST['route_description']);

    // --- FIX: SQL UPDATE statement now includes driver_id and assistant_id ---
    $sql = "UPDATE schedules SET
                date = ?,
                start_time = ?,
                end_time = ?,
                route_description = ?,
                driver_name = ?,
                driver_id = ?,          -- <-- ADDED
                assistant_id = ?,       -- <-- ADDED
                truck_id = ?,
                waste_type = ?,
                days = ?
            WHERE schedule_id = ?";

    try {
        $stmt = $pdo->prepare($sql);
        // Execute with the correct order of parameters
        $stmt->execute([
            $date, 
            $start_time, 
            $end_time, 
            $route_description, 
            $driver_name,
            $driver_id,             // <-- ADDED
            $assistant_id,          // <-- ADDED
            $truck_id, 
            $waste_type, 
            $days,
            $schedule_id
        ]);

        // --- ADDED: NOTIFICATION LOGIC ---
        $userIds = [];
        // Find user_id for the driver
        if (!empty($driver_id)) {
            $userStmt = $pdo->prepare("SELECT user_id FROM truck_driver WHERE driver_id = ? AND user_id IS NOT NULL");
            $userStmt->execute([$driver_id]);
            $driverUser = $userStmt->fetch();
            if ($driverUser) {
                $userIds[] = $driverUser['user_id'];
            }
        }
        // Find user_id for the assistant
        if (!empty($assistant_id)) {
            $userStmt = $pdo->prepare("SELECT user_id FROM truck_assistant WHERE assistant_id = ? AND user_id IS NOT NULL");
            $userStmt->execute([$assistant_id]);
            $assistantUser = $userStmt->fetch();
            if ($assistantUser) {
                $userIds[] = $assistantUser['user_id'];
            }
        }

        // Insert a notification row for each person
        foreach (array_unique($userIds) as $userId) {
            $message = "Your schedule has been updated: " . $route_description;
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'updated')");
            $notifStmt->execute([$userId, $message]);
        }
        // --- END NOTIFICATION LOGIC ---

        unset($_SESSION['form_data']);
        $_SESSION['message'] = "Schedule (ID: $schedule_id) updated successfully!";
        header("Location: dashboard_schedule.php");
        exit();

    } catch (\PDOException $e) {
        error_log("Error updating schedule (ID: $schedule_id): " . $e->getMessage());
        $_SESSION['error'] = "An error occurred during the update: " . $e->getMessage();
        $_SESSION['form_data'] = $_POST;
        header("Location: edit_schedule.php?id=" . $schedule_id);
        exit();
    }

} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: dashboard_schedule.php");
    exit();
}
?>