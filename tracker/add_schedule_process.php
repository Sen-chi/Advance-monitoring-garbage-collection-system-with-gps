<?php
// add_schedule_process.php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // *** MODIFIED: Update required fields list ***
    $required_fields = [
        'date', 'start_time', 'end_time', 'route_description',
        'driver_name', 'truck_id', 'waste_type', 'status'
    ];
    // *** END MODIFICATION ***

    $errors = [];
    foreach ($required_fields as $field) {
        // Trim text fields before checking if empty
        if (in_array($field, ['route_description', 'driver_name', 'waste_type'])) {
             if (empty(trim($_POST[$field]))) {
                $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
            }
        } elseif (empty($_POST[$field])) { // Check other fields normally (date, time, selects)
            $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
        }
    }

    // *** ADDED: Time validation ***
    if (!empty($_POST['start_time']) && !empty($_POST['end_time'])) {
        if ($_POST['end_time'] <= $_POST['start_time']) {
            $errors[] = "End Time must be later than Start Time.";
        }
    }
    // *** END ADDITION ***

    // Additional validation (e.g., check if truck_id exists) could go here

    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST; // Keep data on error
        header("Location: add_schedule.php");
        exit();
    }

    // Prepare data
    $date = $_POST['date'];
    // *** MODIFIED: Get new fields ***
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    $route_description = trim($_POST['route_description']);
    $driver_name = trim($_POST['driver_name']);
    $truck_id = $_POST['truck_id'];
    $waste_type = trim($_POST['waste_type']); // Includes days now
    $status = $_POST['status'];
    // *** END MODIFICATION ***

    // *** MODIFIED: Update SQL INSERT statement ***
    $sql = "INSERT INTO schedules (date, start_time, end_time, route_description, driver_name, truck_id, waste_type, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    // *** END MODIFICATION ***

    try {
        $stmt = $pdo->prepare($sql);
        // *** MODIFIED: Update execute array ***
        $stmt->execute([$date, $start_time, $end_time, $route_description, $driver_name, $truck_id, $waste_type, $status]);
        // *** END MODIFICATION ***

        unset($_SESSION['form_data']); // Clear form data on success
        $_SESSION['message'] = "Schedule added successfully!";
        header("Location: dashboard_schedule.php");
        exit();

    } catch (\PDOException $e) {
        error_log("Error adding schedule: " . $e->getMessage());
        if ($e->getCode() == 23000) {
             $_SESSION['error'] = "Could not add schedule. Conflict detected (e.g., duplicate data or constraint violation).";
        } else {
             $_SESSION['error'] = "An error occurred: " . $e->getMessage(); // More detailed error for debugging if needed
            // $_SESSION['error'] = "An error occurred while adding the schedule.";
        }
        $_SESSION['form_data'] = $_POST; // Keep data on error
        header("Location: add_schedule.php");
        exit();
    }

} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: dashboard_schedule.php");
    exit();
}
?>