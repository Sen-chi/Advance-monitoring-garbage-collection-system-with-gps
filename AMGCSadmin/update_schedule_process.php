<?php
session_start();
require 'db_connect.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // *** MODIFIED: Update required fields list ***
     $required_fields = [
        'schedule_id', 'date', 'start_time', 'end_time', 'route_description',
        'driver_name', 'truck_id', 'waste_type', 'status'
    ];
    // *** END MODIFICATION ***

    $errors = [];
    // Check schedule_id first and ensure it's numeric
    if (!isset($_POST['schedule_id']) || !is_numeric($_POST['schedule_id'])) {
        $errors[] = "Invalid Schedule ID.";
        $_SESSION['error'] = implode('<br>', $errors);
        header("Location: dashboard_schedule.php"); // Can't redirect back without valid ID
        exit();
    }
    $schedule_id = intval($_POST['schedule_id']); // Validated


    foreach ($required_fields as $field) {
         if ($field === 'schedule_id') continue; // Skip ID check here

         // Trim text fields before checking if empty
         if (in_array($field, ['route_description', 'driver_name', 'waste_type'])) {
              if (empty(trim($_POST[$field]))) {
                 $errors[] = ucfirst(str_replace('_', ' ', $field)) . " is required.";
             }
         } elseif (empty($_POST[$field])) { // Check other fields normally
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

     // Redirect back to edit form if errors
    if (!empty($errors)) {
        $_SESSION['error'] = implode('<br>', $errors);
        $_SESSION['form_data'] = $_POST; // Store data for repopulation
        header("Location: edit_schedule.php?id=" . $schedule_id);
        exit();
    }

    // Prepare data (schedule_id is already set and validated)
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


    // *** MODIFIED: Update SQL UPDATE statement ***
    $sql = "UPDATE schedules SET
                date = ?,
                start_time = ?,
                end_time = ?,
                route_description = ?,
                driver_name = ?,
                truck_id = ?,
                waste_type = ?,
                status = ?
            WHERE schedule_id = ?";
    // *** END MODIFICATION ***

    try {
        $stmt = $pdo->prepare($sql);
        // *** MODIFIED: Update execute array (schedule_id is last for WHERE) ***
        $stmt->execute([
            $date, $start_time, $end_time, $route_description, $driver_name,
            $truck_id, $waste_type, $status, $schedule_id
        ]);
        // *** END MODIFICATION ***

        unset($_SESSION['form_data']); // Clear form data on success
        if ($stmt->rowCount() > 0) {
             $_SESSION['message'] = "Schedule (ID: $schedule_id) updated successfully!";
        } else {
             $_SESSION['message'] = "Schedule (ID: $schedule_id) processed. No data changes were made.";
        }
        header("Location: dashboard_schedule.php");
        exit();

    } catch (\PDOException $e) {
        error_log("Error updating schedule (ID: $schedule_id): " . $e->getMessage());
         if ($e->getCode() == 23000) {
             $_SESSION['error'] = "Could not update schedule. Conflict detected (e.g., duplicate data or constraint violation).";
        } else {
             $_SESSION['error'] = "An error occurred: " . $e->getMessage(); // More detailed error
            // $_SESSION['error'] = "An error occurred while updating the schedule.";
        }
        $_SESSION['form_data'] = $_POST; // Keep data on error
        header("Location: edit_schedule.php?id=" . $schedule_id);
        exit();
    }

} else {
    $_SESSION['error'] = "Invalid request method.";
    header("Location: dashboard_schedule.php");
    exit();
}
?>