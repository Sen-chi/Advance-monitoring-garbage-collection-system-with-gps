<?php
// handle_employee_actions.php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Set header for JSON response

// 1. Establish Database Connection
$pdo = null;
$dbError = '';
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Failed to get a valid database connection object from db_connect.php.");
    }
} catch (Exception $e) {
    $dbError = "Database Connection Error: " . $e->getMessage();
    error_log($dbError);
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit; // Stop execution if DB connection fails
}

// 2. Include the Model File
require_once("employeemodel.php"); // Adjust path if needed
// IMPORTANT: Ensure employeemodel.php has been updated to remove job_title references as well!

// Get the action from the POST request
$action = $_POST['action'] ?? '';
$response = ['success' => false, 'message' => 'Invalid action.'];
$errors = []; // Array to hold validation errors

// Process the action
switch ($action) {
    case 'add':
        // --- Server-Side Validation for Add ---
        // REMOVED 'job_title' from required fields
        $required_fields = ['user_id', 'first_name', 'last_name', 'employee_status']; // Added contact_number to optional check
        $optional_fields = ['middle_name', 'contact_number']; // List optional fields explicitly

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                 // Use input name for error key
                 $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

         // Check optional fields if present and need specific format validation
         // Basic contact number validation (optional field, only validate if not empty)
         if (!empty($_POST['contact_number'])) {
            // Example: check if it's mostly digits and optional hyphens/spaces/parentheses
            if (!preg_match('/^[\d\s\-\(\)]+$/', $_POST['contact_number'])) {
                 $errors['contact_number'] = 'Invalid contact number format.';
            }
         }

         // Basic status check (should match allowed values)
         $allowed_statuses = ['Active', 'Inactive', 'On Leave', 'Terminated'];
         if (!empty($_POST['employee_status']) && !in_array($_POST['employee_status'], $allowed_statuses)) {
              $errors['employee_status'] = 'Invalid status selected.';
         } elseif (empty($_POST['employee_status'])) {
             // This case is caught by the required fields check, but good to be explicit
              $errors['employee_status'] = 'Employee status is required.';
         }


        // Check if the selected user_id is valid and unassigned
        $selectedUserId = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT); // Use null coalesce for safety
        if ($selectedUserId === false || $selectedUserId <= 0) { // Check for false from filter or <= 0
             $errors['user_id'] = 'Invalid user selected.';
        } else {
             // Query database to check if this user_id is already in the employee table
             try {
                $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE user_id = :user_id");
                $stmt->bindParam(':user_id', $selectedUserId, PDO::PARAM_INT);
                $stmt->execute();
                if ($stmt->fetchColumn() > 0) {
                    $errors['user_id'] = 'This user is already linked to an employee record.';
                }
             } catch (PDOException $e) {
                 error_log("DB error checking unassigned user: " . $e->getMessage());
                 $errors['user_id'] = 'Could not validate user selection.';
             }
        }


        if (empty($errors)) {
            // REMOVED 'job_title' from the data array
            $employeeData = [
                'user_id' => $selectedUserId, // Use validated ID
                'first_name' => trim($_POST['first_name']),
                'middle_name' => trim($_POST['middle_name'] ?? ''), // Middle name is optional
                'last_name' => trim($_POST['last_name']),
                'contact_number' => trim($_POST['contact_number'] ?? ''), // Contact number is optional
                'employee_status' => trim($_POST['employee_status']),
            ];

            // Pass data to model function
            $newEmployeeId = addEmployee($pdo, $employeeData);

            if ($newEmployeeId !== false) {
                $response = ['success' => true, 'message' => 'Employee added successfully!', 'employee_id' => $newEmployeeId];
            } else {
                // Specific error message from model logging should help debug
                 $response = ['success' => false, 'message' => 'Failed to add employee. Database error.'];
                 error_log("Error adding employee via handle_employee_actions.php. Data: " . print_r($employeeData, true)); // Log data that failed
            }
        } else {
             $response = ['success' => false, 'message' => 'Validation failed. Please correct the errors.', 'errors' => $errors];
        }
        break;

    case 'edit':
        $employeeId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        // --- Server-Side Validation for Edit ---
        if ($employeeId === false || $employeeId <= 0) {
            $errors['id'] = 'Invalid employee ID.';
        } else {
             // Check if employee exists - Crucial for update
             if (!getEmployeeById($pdo, $employeeId)) {
                 $errors['id'] = 'Employee not found.';
             }
        }

        // REMOVED 'job_title' from required fields for edit
        $required_fields = ['first_name', 'last_name', 'employee_status'];
        $optional_fields = ['middle_name', 'contact_number'];

        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                 $errors[$field] = ucfirst(str_replace('_', ' ', $field)) . ' is required.';
            }
        }

         // Check optional fields if present and need specific format validation
         // Basic contact number validation (optional field, only validate if not empty)
         if (!empty($_POST['contact_number'])) {
            // Example: check if it's mostly digits and optional hyphens/spaces/parentheses
            if (!preg_match('/^[\d\s\-\(\)]+$/', $_POST['contact_number'])) {
                 $errors['contact_number'] = 'Invalid contact number format.';
            }
         }

         // Basic status check (should match allowed values)
          $allowed_statuses = ['Active', 'Inactive', 'On Leave', 'Terminated'];
         if (!empty($_POST['employee_status']) && !in_array($_POST['employee_status'], $allowed_statuses)) {
              $errors['employee_status'] = 'Invalid status selected.';
         } elseif (empty($_POST['employee_status'])) {
              $errors['employee_status'] = 'Employee status is required.';
         }


        if (empty($errors)) {
             // REMOVED 'job_title' from the data array
             $employeeData = [
                'first_name' => trim($_POST['first_name']),
                'middle_name' => trim($_POST['middle_name'] ?? ''),
                'last_name' => trim($_POST['last_name']),
                'contact_number' => trim($_POST['contact_number'] ?? ''),
                'employee_status' => trim($_POST['employee_status']),
            ];

            // Pass data to model function
            if (updateEmployee($pdo, $employeeId, $employeeData)) {
                $response = ['success' => true, 'message' => 'Employee updated successfully!'];
            } else {
                $response = ['success' => false, 'message' => 'Failed to update employee. Database error.'];
                 error_log("Error updating employee ID " . $employeeId . " via handle_employee_actions.php. Data: " . print_r($employeeData, true)); // Log data that failed
            }
        } else {
             $response = ['success' => false, 'message' => 'Validation failed. Please correct the errors.', 'errors' => $errors];
        }

        break;

    case 'delete':
        $employeeId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);

        if ($employeeId === false || $employeeId <= 0) {
            $response = ['success' => false, 'message' => 'Invalid employee ID for deletion.'];
        } else {
            $deleteResult = deleteEmployee($pdo, $employeeId);
            if ($deleteResult === true) {
                $response = ['success' => true, 'message' => 'Employee deleted successfully!'];
            } elseif ($deleteResult === false) {
                 // Check if it returned false because the ID wasn't found
                 if (!getEmployeeById($pdo, $employeeId)) {
                      $response = ['success' => false, 'message' => 'Employee not found or already deleted.'];
                 } else {
                      // Generic database error
                      $response = ['success' => false, 'message' => 'Failed to delete employee. Database error.'];
                 }
            }
            elseif ($deleteResult === 'foreign_key_error') {
                $response = ['success' => false, 'message' => 'Cannot delete employee: This employee is linked to other records (e.g., trips, schedules).'];
            }
            else {
                 // Catch any unexpected return values from the model
                $response = ['success' => false, 'message' => 'An unexpected error occurred during deletion.'];
                error_log("Unexpected result from deleteEmployee for ID " . $employeeId . ": " . print_r($deleteResult, true));
            }
        }
        break;

     case 'get_unassigned_users':
         // This action is likely not used directly by your current JS modals,
         // as employees.php fetches this on load. Keeping it just in case.
         $unassignedUsers = getUnassignedUsers($pdo);
         if ($unassignedUsers !== false) {
             $response = ['success' => true, 'users' => $unassignedUsers];
         } else {
             $response = ['success' => false, 'message' => 'Failed to retrieve unassigned users. Database error.'];
             error_log("Error retrieving unassigned users via handle_employee_actions.php.");
         }
         break;


    default:
        $response = ['success' => false, 'message' => 'Unknown action provided.'];
        break;
}

echo json_encode($response);

// Close the connection
$pdo = null;
?>