<?php
// handle_truck_actions.php

// ** Start Output Buffering as the very first action **
// Ensure NO whitespace, blank lines, or BOM before this <?php tag.
ob_start();

// Configuration: Error Reporting & Display
// Turn off display_errors in production environments.
// Keep error_reporting on E_ALL and log errors for debugging.
ini_set('display_errors', 0); // Recommended for production
ini_set('display_startup_errors', 0); // Recommended for production
error_reporting(E_ALL);
// Configure logging if not already done in php.ini
// ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Specify log file

// Include dependencies (ensure these files have no leading/trailing whitespace/BOM)
$pdo = require_once("db_connect.php");
require_once("truckmodel.php");

// Set headers for JSON response (safe after ob_start)
header('Content-Type: application/json');

// Basic Input Validation & Security (Check request method)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); // Clear buffer
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST requests are allowed.']);
    ob_end_flush();
    exit;
}

// Check DB connection early
if (!$pdo instanceof PDO) {
    error_log("Database connection failed in handle_truck_actions.php."); // Log server-side
    ob_clean(); // Clear buffer
    echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']); // User-friendly message
    ob_end_flush();
    exit;
}

// Sanitize and validate 'action'
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
if (empty($action)) {
    ob_clean(); // Clear buffer
    echo json_encode(['success' => false, 'message' => 'Action not specified.']);
    ob_end_flush();
    exit;
}

// --- Initialize Response ---
$response = ['success' => false, 'message' => 'An internal server error occurred.']; // Default fallback response
$errors = []; // Array to hold specific field errors

// *** MODIFIED: Define allowed statuses for validation (NEW values) ***
$allowed_statuses = ['Available', 'Assigned', 'Maintenance', 'Inactive'];


// --- Process Actions ---
try {
    switch ($action) {
        case 'get':
            // Fetch a single truck's data for editing
            $truckIdInput = $_POST['truck_id'] ?? '';

            // Validate truck_id
            if (empty($truckIdInput) || !filter_var($truckIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                 $response['message'] = 'Invalid or missing truck ID for fetching.';
                 break; // Stop processing this action
            }
            $truck_id = (int)$truckIdInput;

            // getTruckById now fetches availability_status (will be one of the new values)
            $truckData = getTruckById($pdo, $truck_id); // Logs errors internally

            if ($truckData) {
                $response = ['success' => true, 'message' => 'Truck data fetched successfully.', 'data' => $truckData];
            } else {
                 // getTruckById returns null if not found or error
                 $response = ['success' => false, 'message' => 'Truck not found or error fetching data.'];
            }
            break;

        case 'add':
            // Add a new truck
            $plateNumber_raw = $_POST['plate_number'] ?? '';
            $capacityKg_input_raw = $_POST['capacity_kg'] ?? '';
            $model_raw = $_POST['model'] ?? '';
            // Get availability_status from POST
            $availabilityStatus_raw = $_POST['availability_status'] ?? '';

            // Sanitize input
            $plateNumber = htmlspecialchars(trim($plateNumber_raw), ENT_QUOTES, 'UTF-8');
            $model = htmlspecialchars(trim($model_raw), ENT_QUOTES, 'UTF-8');
            $capacityKg_input = trim($capacityKg_input_raw);
            // Sanitize availability_status
            $availabilityStatus = htmlspecialchars(trim($availabilityStatus_raw), ENT_QUOTES, 'UTF-8');


            // Server-side validation
            if (empty($plateNumber)) {
                $errors['plate_number'] = 'Plate Number is required.';
            }

            // Validate capacity - allow null or non-negative integer
            $capacityKg = null; // Default to null
            if ($capacityKg_input !== '') { // If input is not empty
                if (filter_var($capacityKg_input, FILTER_VALIDATE_INT) !== false && (int)$capacityKg_input >= 0) {
                    $capacityKg = (int)$capacityKg_input;
                } else {
                    $errors['capacity_kg'] = 'Capacity must be a non-negative integer or left empty.';
                }
            }

            // Validate availability_status against the NEW allowed list
            if (empty($availabilityStatus)) {
                 $errors['availability_status'] = 'Availability Status is required.';
            } elseif (!in_array($availabilityStatus, $allowed_statuses)) {
                 $errors['availability_status'] = 'Invalid Availability Status.';
            }

            // $model is nullable in DB, no strict validation needed here beyond sanitation


            if (empty($errors)) {
                // Call model function to add truck, passing the validated status
                $addSuccess = addTruck($pdo, $plateNumber, $capacityKg, $model, $availabilityStatus);

                if ($addSuccess) {
                    $response = ['success' => true, 'message' => 'Truck added successfully.'];
                } else {
                    // Check for duplicate key error specifically
                    $pdoError = $pdo->errorInfo();
                     // MySQL error code for duplicate entry
                    if ($pdoError[1] == 1062) {
                         $errors['plate_number'] = 'Failed to add truck. Plate number already exists.'; // Add specific error
                         $response['message'] = $errors['plate_number']; // Use the specific message
                         $response['errors'] = $errors; // Send back specific errors
                    } else {
                         $response['message'] = 'Failed to add truck. Database error.'; // Generic DB error
                         error_log("Database error during truck add (not duplicate): " . print_r($pdoError, true));
                    }
                }
            } else {
                 $response['message'] = 'Validation failed.';
                 $response['errors'] = $errors; // Send back specific errors
            }
            break;

        case 'edit':
            // Edit an existing truck
            $truckIdInput = $_POST['truck_id'] ?? '';
            $plateNumber_raw = $_POST['plate_number'] ?? '';
            $capacityKg_input_raw = $_POST['capacity_kg'] ?? '';
            $model_raw = $_POST['model'] ?? '';
            // Get availability_status from POST
            $availabilityStatus_raw = $_POST['availability_status'] ?? '';

             // Validate truck_id
            if (empty($truckIdInput) || !filter_var($truckIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                 $errors['truck_id'] = 'Invalid or missing truck ID for editing.'; // Use 'truck_id' as error key
                 // No break here, let's collect other errors if possible
            } else {
                 $truck_id = (int)$truckIdInput;
            }

            // Sanitize input
            $plateNumber = htmlspecialchars(trim($plateNumber_raw), ENT_QUOTES, 'UTF-8');
            $model = htmlspecialchars(trim($model_raw), ENT_QUOTES, 'UTF-8');
            $capacityKg_input = trim($capacityKg_input_raw);
            // Sanitize availability_status
            $availabilityStatus = htmlspecialchars(trim($availabilityStatus_raw), ENT_QUOTES, 'UTF-8');


            // Server-side validation
            if (empty($plateNumber)) {
                $errors['plate_number'] = 'Plate Number is required.';
            }

             // Validate capacity - allow null or non-negative integer
            $capacityKg = null; // Default to null
            if ($capacityKg_input !== '') { // If input is not empty
                if (filter_var($capacityKg_input, FILTER_VALIDATE_INT) !== false && (int)$capacityKg_input >= 0) {
                    $capacityKg = (int)$capacityKg_input;
                } else {
                    $errors['capacity_kg'] = 'Capacity must be a non-negative integer or left empty.';
                }
            }

            // Validate availability_status against the NEW allowed list
            if (empty($availabilityStatus)) {
                 $errors['availability_status'] = 'Availability Status is required.';
            } elseif (!in_array($availabilityStatus, $allowed_statuses)) {
                 $errors['availability_status'] = 'Invalid Availability Status.';
            }

            // $model is nullable, no strict validation needed here beyond sanitation

            // Only proceed if truck_id is valid, otherwise errors are already set
            if (!isset($errors['truck_id']) && empty($errors)) {
                // Call model function to update truck, passing the validated status
                $updateResult = updateTruck($pdo, $truck_id, $plateNumber, $capacityKg, $model, $availabilityStatus);

                if ($updateResult !== false) { // updateTruck returns rowCount (0 or >0) or false on DB error
                    if ($updateResult > 0) {
                         $response = ['success' => true, 'message' => 'Truck updated successfully.'];
                    } else {
                         // 0 rows affected means the data was the same, still successful from a user perspective
                         $response = ['success' => true, 'message' => 'No changes detected for the truck.'];
                    }
                } else {
                    // updateTruck returned false (database error)
                    // Check for duplicate key error specifically
                     $pdoError = $pdo->errorInfo();
                    if ($pdoError[1] == 1062) {
                         $errors['plate_number'] = 'Plate number already exists.'; // Add specific error
                         $response['message'] = $errors['plate_number']; // Use the specific message
                         $response['errors'] = $errors; // Send back specific errors
                    } else {
                        $response['message'] = 'Failed to update truck. Database error.';
                         error_log("Database error during truck update (not duplicate): " . print_r($pdoError, true));
                    }
                }
            } else {
                 // If there were truck_id errors or other validation errors
                 $response['message'] = 'Validation failed.';
                 $response['errors'] = $errors; // Send back specific errors
            }
            break;

        case 'delete':
            // Delete a truck
            $truckIdInput = $_POST['truck_id'] ?? '';

            // Validate truck_id
             if (empty($truckIdInput) || !filter_var($truckIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                 $response['message'] = 'Invalid or missing truck ID for deletion.';
                 break; // Stop processing this action
            }
            $truck_id = (int)$truckIdInput;

            // Call model function to delete truck
            $deleteResult = deleteTruck($pdo, $truck_id); // Model returns rowCount (0 or 1) or false

            if ($deleteResult !== false) { // deleteTruck returns rowCount or false on DB error
                if ($deleteResult > 0) {
                    $response = ['success' => true, 'message' => 'Truck deleted successfully. Assigned drivers are now unassigned.'];
                } else {
                    // 0 rows affected means truck ID wasn't found
                    $response = ['success' => false, 'message' => 'Truck not found or already deleted.'];
                }
            } else {
                // deleteTruck returned false (database error)
                $response['message'] = 'Failed to delete truck due to a database error.';
            }
            break;

        default:
            $response['message'] = 'Invalid action specified.';
            break;
    }

} catch (\PDOException $e) {
     // Catch any unexpected PDO errors
     $error_message = "PDO Exception in handle_truck_actions.php [Action: $action]: " . $e->getMessage();
     error_log($error_message); // Log the detailed error server-side
     $response['success'] = false;
     $response['message'] = 'A database error occurred.'; // Generic message for the client
     // In development, you *might* include $e->getMessage() here for easier debugging, but remove in production
     // $response['message'] .= ' Details: ' . $e->getMessage();
} catch (Exception $e) {
    // Catch any other unexpected errors
    $error_message = "General Exception in handle_truck_actions.php [Action: $action]: " . $e->getMessage();
    error_log($error_message); // Log the detailed error server-side
    $response['success'] = false;
    $response['message'] = 'A server error occurred. Please try again later.'; // Generic message for the client
    // In development, you *might* include $e->getMessage() here
    // $response['message'] .= ' Details: ' . $e->getMessage();
}

// ** Final Output Block **
// Ensure buffer is cleaned before sending headers and JSON
ob_clean();

// Output the final JSON response.
echo json_encode($response);

// End buffering and send the output to the client.
ob_end_flush();

exit; // Ensure script terminates immediately after sending response