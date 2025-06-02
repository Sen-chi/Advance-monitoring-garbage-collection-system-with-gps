<?php
// handle_driver_actions.php

// ** Start Output Buffering as the very first action **
// Ensure NO whitespace, blank lines, or BOM before this <?php tag.
ob_start();

// Configuration: Error Reporting & Display
// Turn off display_errors in production environments.
ini_set('display_errors', 0); // Recommended for production
ini_set('display_startup_errors', 0); // Recommended for production
error_reporting(E_ALL);
// Configure logging if not already done in php.ini
// ini_set('log_errors', 1);
// ini_set('error_log', '/path/to/your/php-error.log'); // Specify log file

// Include dependencies (ensure these files have no leading/trailing whitespace/BOM)
$pdo = null;
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Failed to get a valid database connection object.");
    }
} catch (Exception $e) {
     error_log("Database Connection Error in driver actions handler: " . $e->getMessage());
     // If DB fails early, clean buffer and send error
     ob_clean(); // Clear buffer
     header('Content-Type: application/json'); // Still attempt to set header
     echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
     ob_end_flush();
     exit;
}

// Include the model files
require_once("drivermodel.php"); // Contains driver-specific functions
require_once("truckmodel.php");  // Contains truck-specific functions (like status update, getting driver's truck)


// Set headers for JSON response (safe after ob_start)
header('Content-Type: application/json');

// Basic Input Validation & Security (Check request method)
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean(); // Clear buffer
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST requests are allowed.']);
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

// --- Process Actions ---
try {
    // Start a transaction for actions that involve both driver and truck tables
    if (in_array($action, ['add', 'edit', 'delete'])) {
        $pdo->beginTransaction();
    }

    switch ($action) {
        case 'add':
            // --- Get and Sanitize Input ---
            // Use FILTER_UNSAFE_RAW and then htmlspecialchars for potentially multi-byte chars
            $firstName_raw = $_POST['first_name'] ?? '';
            $middleName_raw = $_POST['middle_name'] ?? '';
            $lastName_raw = $_POST['last_name'] ?? '';
            $contactNo_raw = $_POST['contact_no'] ?? '';
            $truckIdInput = $_POST['truck_id'] ?? ''; // Get value from select, might be empty string or numeric
            $status_raw = $_POST['status'] ?? '';

            $firstName = htmlspecialchars(trim($firstName_raw), ENT_QUOTES, 'UTF-8');
            $middleName = htmlspecialchars(trim($middleName_raw), ENT_QUOTES, 'UTF-8'); // Middle name can be empty, but trim/sanitize
            $lastName = htmlspecialchars(trim($lastName_raw), ENT_QUOTES, 'UTF-8');
            $contactNo = htmlspecialchars(trim($contactNo_raw), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars(trim($status_raw), ENT_QUOTES, 'UTF-8');

            // Validate Truck ID if provided (not empty string)
            $newTruckId = null; // Default to unassigned (NULL)
            if (!empty($truckIdInput)) {
                 // Use filter_var for stricter numeric validation
                 if (filter_var($truckIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                     $errors['truck_id'] = 'Invalid Truck ID format.';
                 } else {
                    $newTruckId = (int)$truckIdInput;
                    // Check if the truck_id exists in truck_info (and is not already assigned to another driver?)
                    // For now, just check existence. We'll update status regardless if assigned.
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM truck_info WHERE truck_id = ?");
                    $stmt->execute([$newTruckId]);
                    if ($stmt->fetchColumn() == 0) {
                        $errors['truck_id'] = 'Selected Truck ID does not exist.';
                    }
                    // else: $newTruckId is valid and exists
                 }
            }
            // Note: if truckIdInput is empty (''), $newTruckId remains null, which is correct for unassigning.


            // --- Server-side Validation ---
            if (empty($firstName)) $errors['first_name'] = 'First name is required.';
            if (empty($lastName)) $errors['last_name'] = 'Last name is required.';
            if (empty($contactNo)) $errors['contact_no'] = 'Contact number is required.';
            if (empty($status)) $errors['status'] = 'Status is required.';
            // Validate status against allowed enum values (assuming 'Active', 'Inactive')
            if (!empty($status) && !in_array(strtolower($status), ['active', 'inactive'])) {
                 $errors['status'] = 'Invalid status selected.';
            }


            if (empty($errors)) {
                // --- Call Model Function to Add Driver ---
                // Pass the new truck ID (can be null)
                $insertSuccess = addDriver($pdo, $firstName, $middleName, $lastName, $contactNo, $newTruckId, $status);

                if ($insertSuccess) {
                    // If driver added successfully, update the truck status if a truck was assigned
                    if ($newTruckId !== null) {
                        // *** CORRECTED FUNCTION NAME HERE ***
                         $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $newTruckId, 'Assigned');
                         if (!$truckUpdateSuccess) {
                              // If truck status update failed, log and potentially rollback
                              error_log("Failed to update truck status to Assigned for truck ID: " . $newTruckId);
                              // Rollback the driver insert if truck update fails for consistency
                              $pdo->rollBack();
                              $response = ['success' => false, 'message' => 'Failed to update truck status after adding driver. Operation cancelled.', 'errors' => ['general' => 'Database operation failed.']];
                              break; // Exit switch after handling rollback
                         }
                    }
                    // Both driver insert and truck status update (if applicable) were successful
                    $pdo->commit(); // Commit the transaction
                    $response = ['success' => true, 'message' => 'Driver added successfully!'];

                } else {
                     // Driver insert failed
                     $pdo->rollBack(); // Rollback the transaction
                     $response = ['success' => false, 'message' => 'Failed to add driver. It might be a database error or duplicate contact number.', 'errors' => ['general' => 'Database operation failed.']];
                }
            } else {
                 $response = ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
                 $pdo->rollBack(); // Rollback if validation failed (no changes were made, but good practice)
            }
            break;

        case 'edit':
            // --- Get and Sanitize Input ---
            $idInput = $_POST['id'] ?? ''; // Driver ID
            $firstName_raw = $_POST['first_name'] ?? '';
            $middleName_raw = $_POST['middle_name'] ?? '';
            $lastName_raw = $_POST['last_name'] ?? '';
            $contactNo_raw = $_POST['contact_no'] ?? '';
            $truckIdInput = $_POST['truck_id'] ?? ''; // Get value from select
            $status_raw = $_POST['status'] ?? '';

             // Sanitize input
            $firstName = htmlspecialchars(trim($firstName_raw), ENT_QUOTES, 'UTF-8');
            $middleName = htmlspecialchars(trim($middleName_raw), ENT_QUOTES, 'UTF-8');
            $lastName = htmlspecialchars(trim($lastName_raw), ENT_QUOTES, 'UTF-8');
            $contactNo = htmlspecialchars(trim($contactNo_raw), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars(trim($status_raw), ENT_QUOTES, 'UTF-8');


             // --- Server-side Validation ---
            // Validate driver ID
             $driverId = null;
             if (empty($idInput) || !filter_var($idInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                  $errors['general'] = 'Invalid or missing driver ID for update.';
             } else {
                 $driverId = (int)$idInput;
             }

             if (empty($firstName)) $errors['first_name'] = 'First name is required.';
             if (empty($lastName)) $errors['last_name'] = 'Last name is required.';
             if (empty($contactNo)) $errors['contact_no'] = 'Contact number is required.';
             if (empty($status)) $errors['status'] = 'Status is required.';
            // Validate status against allowed enum values
            if (!empty($status) && !in_array(strtolower($status), ['active', 'inactive'])) {
                 $errors['status'] = 'Invalid status selected.';
            }

             // Validate Truck ID if provided (not empty string)
            $newTruckId = null; // Default to unassigned (NULL)
            if (!empty($truckIdInput)) {
                 // Use filter_var for stricter numeric validation
                 if (filter_var($truckIdInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) === false) {
                     $errors['truck_id'] = 'Invalid Truck ID format.';
                 } else {
                    $newTruckId = (int)$truckIdInput;
                    // Check if the truck_id exists in truck_info
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM truck_info WHERE truck_id = ?");
                    $stmt->execute([$newTruckId]);
                    if ($stmt->fetchColumn() == 0) {
                        $errors['truck_id'] = 'Selected Truck ID does not exist.';
                    }
                    // else: $newTruckId is valid and exists
                 }
            }
            // Note: if truckIdInput is empty (''), $newTruckId remains null.


             if (empty($errors) && $driverId !== null) {
                 // --- Get the OLD truck_id BEFORE updating the driver record ---
                 // *** CORRECTED FUNCTION NAME HERE ***
                 $oldTruckId = getAssignedTruckIdForDriver($pdo, $driverId);

                 // --- Call Model Function to Update Driver ---
                 // This function in drivermodel.php correctly handles setting the truck_id column
                 $updateResult = updateDriver($pdo, $driverId, $firstName, $middleName, $lastName, $contactNo, $newTruckId, $status);

                 if ($updateResult !== false) { // updateDriver returns rowCount (0 or >0) or false on DB error
                     $driverRecordChanged = ($updateResult > 0); // Did the driver row itself change?

                     // --- Now handle truck status updates based on old and new truck IDs ---
                     $truckUpdateSuccess = true; // Assume success initially for truck status updates

                     // Case 1: Truck WAS assigned ($oldTruckId is not null) AND it changed ($oldTruckId != $newTruckId)
                     // Note: Use strict comparison ( !== ) for null check, but loose comparison ( != ) might be okay for IDs if they come from different sources (int vs string) but strict is generally safer if types are consistent (int). Let's use strict != null and loose != for ID comparison to be safe with potential type variations from $_POST.
                     if ($oldTruckId !== null && $oldTruckId != $newTruckId) {
                         // Set the OLD truck's status back to 'Available'
                         // *** CORRECTED FUNCTION NAME HERE ***
                         $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $oldTruckId, 'Available');
                         if (!$truckUpdateSuccess) {
                             error_log("Failed to update OLD truck status to Available for truck ID: " . $oldTruckId);
                             // If truck update fails, we'll rollback the transaction below
                         }
                     }

                     // Case 2: Truck IS now assigned ($newTruckId is not null) AND it changed ($newTruckId != $oldTruckId)
                     // Only attempt to update NEW truck status if the previous truck update (if any) was successful.
                     if ($newTruckId !== null && $newTruckId != $oldTruckId && $truckUpdateSuccess) {
                         // Set the NEW truck's status to 'Assigned'
                          // *** CORRECTED FUNCTION NAME HERE ***
                          $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $newTruckId, 'Assigned');
                           if (!$truckUpdateSuccess) {
                             error_log("Failed to update NEW truck status to Assigned for truck ID: " . $newTruckId);
                              // If truck update fails, we'll rollback the transaction below
                         }
                     }

                     // --- Check overall success (driver update AND all required truck status updates) and commit/rollback ---
                     if ($truckUpdateSuccess) {
                         $pdo->commit(); // Commit the transaction
                         // Success message logic: check if *anything* relevant was updated
                         if ($driverRecordChanged || ($oldTruckId != $newTruckId)) {
                             $response = ['success' => true, 'message' => 'Driver and truck assignment updated successfully!'];
                         } else {
                              // No changes detected in driver fields OR truck assignment
                             $response = ['success' => true, 'message' => 'No changes detected for driver ID ' . $driverId];
                         }
                     } else {
                         // One of the truck updates failed (old or new)
                         $pdo->rollBack(); // Rollback the entire transaction
                         $response = ['success' => false, 'message' => 'Failed to update truck assignment status. Operation cancelled.', 'errors' => ['general' => 'Database operation failed on truck status update.']];
                     }


                 } else {
                      // updateDriver returned false (database error)
                     $pdo->rollBack(); // Rollback the transaction
                     $response = ['success' => false, 'message' => 'Failed to update driver. Database error.', 'errors' => ['general' => 'Database operation failed on driver update.']];
                 }
             } else {
                 // If driverId was null due to initial validation failure, add that error
                 if ($driverId === null && !isset($errors['general'])) {
                     $errors['general'] = 'Driver ID is required for update.';
                 }
                 $pdo->rollBack(); // Rollback if validation failed
                 $response = ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
             }
             break;

        case 'delete':
            // --- Get and Sanitize Input ---
            $idInput = $_POST['id'] ?? '';

            // --- Server-side Validation ---
            $driverId = null;
             if (empty($idInput) || !filter_var($idInput, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]])) {
                 $errors['general'] = 'Invalid or missing driver ID for deletion.';
             } else {
                 $driverId = (int)$idInput;
             }


            if (empty($errors) && $driverId !== null) {
                 // --- Get the truck_id BEFORE deleting the driver record ---
                 // *** CORRECTED FUNCTION NAME HERE ***
                 $oldTruckId = getAssignedTruckIdForDriver($pdo, $driverId);

                // --- Call Model Function to Delete Driver ---
                $deleteResult = deleteDriver($pdo, $driverId); // Model returns rowCount (0 or 1) or false

                // Check if deletion was successful (1 row affected).
                // We check $deleteResult !== false to distinguish DB error from no rows affected (deleteResult === 0).
                if ($deleteResult !== false && $deleteResult > 0) {
                    // If deletion was successful AND the driver WAS assigned a truck
                    if ($oldTruckId !== null) {
                        // Set the OLD truck's status back to 'Available'
                        // *** CORRECTED FUNCTION NAME HERE ***
                        $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $oldTruckId, 'Available');
                         if (!$truckUpdateSuccess) {
                             error_log("Failed to update truck status to Available for truck ID: " . $oldTruckId . " after driver deletion.");
                             // If truck update fails, we'll rollback the transaction below
                         }
                    } else {
                         // No truck assigned, so truck status update step is 'successful' (nothing needed)
                         $truckUpdateSuccess = true;
                    }

                    // --- Check overall success (driver delete AND truck status update if applicable) and commit/rollback ---
                    if ($truckUpdateSuccess) {
                         $pdo->commit(); // Commit the transaction
                         $response = ['success' => true, 'message' => 'Driver deleted successfully!'];
                    } else {
                        // Truck status update failed after successful driver delete
                        $pdo->rollBack(); // Rollback the transaction (both driver delete and truck status)
                        $response = ['success' => false, 'message' => 'Driver deleted, but failed to update truck status. Please manually check truck status.', 'errors' => ['general' => 'Database operation failed on truck status update after driver deletion.']];
                    }

                } else {
                     // deleteDriver returned false (database error) or 0 rows affected (driver ID not found)
                     $pdo->rollBack(); // Rollback the transaction
                     if ($deleteResult === false) {
                          $response = ['success' => false, 'message' => 'Failed to delete driver due to a database error.', 'errors' => ['general' => 'Database operation failed on driver deletion.']];
                     } else { // $deleteResult was 0
                           $response = ['success' => false, 'message' => 'Driver not found or already deleted.'];
                     }
                }
            } else {
                // If driverId was null due to initial validation failure, add that error
                if ($driverId === null && !isset($errors['general'])) {
                     $errors['general'] = 'Driver ID is required for deletion.';
                }
                 $pdo->rollBack(); // Rollback if validation failed
                $response = ['success' => false, 'message' => 'Deletion failed.', 'errors' => $errors];
            }
             break;

        // Add a 'get' case for fetching single driver data if your edit modal uses AJAX to pre-fill

        default:
             $response['message'] = 'Unknown action specified.';
             // No transaction needed for unknown action, or handle rollback if begun
             if ($pdo->inTransaction()) {
                $pdo->rollBack();
             }
            break;
    }

} catch (\PDOException $e) {
     // Catch any unexpected PDO errors during transaction or other operations
     if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback the transaction on any PDO exception
     }
     $error_message = "PDO Exception in handle_driver_actions.php [Action: $action]: " . $e->getMessage();
     error_log($error_message); // Log the detailed error server-side
     $response['success'] = false;
     $response['message'] = 'A database error occurred while processing your request.'; // Generic message for the client
     // In development, you *might* include $e->getMessage() here for easier debugging, but remove in production
     // $response['message'] .= ' Details: ' . $e->getMessage();
} catch (Exception $e) {
    // Catch any other unexpected errors
     if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack(); // Rollback the transaction on any other exception
     }
    $error_message = "General Exception in handle_driver_actions.php [Action: $action]: " . $e->getMessage();
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

?>