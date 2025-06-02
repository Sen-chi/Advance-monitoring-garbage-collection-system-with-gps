<?php
session_start(); // Might be needed for checking user permissions later

// Set header to return JSON
header('Content-Type: application/json');

// --- Response helper function ---
function send_json_response($success, $message = '', $errors = []) {
    $response = ['success' => (bool)$success];
    if (!empty($message)) {
        $response['message'] = $message;
    }
    if (!empty($errors)) {
        $response['errors'] = $errors;
    }
    // Use JSON_UNESCAPED_SLASHES and JSON_UNESCAPED_UNICODE if needed for specific characters, but usually not required here.
    echo json_encode($response);
    exit; // Stop script execution after sending response
}

// --- Basic Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Invalid request method.');
}

if (!isset($_POST['action'])) {
     send_json_response(false, 'Action not specified.');
}

$action = $_POST['action'];

// --- Database Connection & Model ---
$pdo = null;
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Failed to get PDO object.");
    }
    // Set PDO error mode for better debugging during development
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Include the model AFTER connection is established
    require_once("usermodel.php");

} catch (Exception $e) {
    // Log the exact error for the admin
    error_log("Handle User Actions - DB/Model Error: " . $e->getMessage());
    // Send a generic error message to the user
    send_json_response(false, 'Internal server error. Could not connect to the database or load resources.');
}

// --- Action Handling ---
try {
    switch ($action) {
        // --- ADD USER ---
        case 'add':
            // Keep your existing 'add' logic here
            // 1. Sanitize and retrieve data
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? ''; // Don't trim password initially
            $confirmPassword = $_POST['confirmPassword'] ?? ''; // Added confirm password
            $role = trim($_POST['role'] ?? '');
            $status = trim($_POST['status'] ?? '');

            // 2. Validation
            $errors = [];
            if (empty($username)) { $errors['username'] = "Username is required."; }
            if (empty($email)) {
                $errors['email'] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Invalid email format.";
            } else {
                // Check if email already exists using the model function
                 if (isEmailTaken($pdo, $email)) { // Pass PDO connection
                     $errors['email'] = "Email address is already registered.";
                 }
            }
            if (empty($password)) {
                $errors['password'] = "Password is required.";
            } elseif (strlen($password) < 6) {
                $errors['password'] = "Password must be at least 6 characters long.";
            }
             // Confirm password validation
             if (empty($confirmPassword)) {
                  $errors['confirmPassword'] = "Confirm password is required.";
             } else if ($password !== $confirmPassword) {
                $errors['confirmPassword'] = "Passwords do not match.";
             }
            $allowed_roles = ['admin', 'collector', 'resident', 'driver', 'encoder']; // Keep consistent
            if (empty($role) || !in_array($role, $allowed_roles)) {
                $errors['role'] = "Please select a valid role.";
            }
            $allowed_statuses = ['active', 'inactive'];
            if (empty($status) || !in_array($status, $allowed_statuses)) {
                 $errors['status'] = "Please select a valid status.";
            }

            // 3. Process if no validation errors
            if (!empty($errors)) {
                send_json_response(false, 'Validation failed.', $errors);
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);
                if ($password_hash === false) {
                     error_log("Password hashing failed for new user: " . $email);
                     send_json_response(false, 'Could not process password securely.');
                }

                // Prepare data for model function
                $userData = [
                    'username' => $username,
                    'email' => $email,
                    'password' => $password_hash, // Send the hash
                    'role' => $role,
                    'status' => $status
                ];

                // Call model function to add user
                if (addUser($pdo, $userData)) { // Pass PDO connection
                    send_json_response(true, 'User added successfully!');
                } else {
                    // Check if it was likely a duplicate entry not caught by initial check (less common with email check)
                    // Or a generic DB insert error
                    send_json_response(false, 'Failed to add user to the database.');
                }
            }
            break; // End case 'add'

        // --- EDIT USER ---
        case 'edit':
             // Keep your existing 'edit' logic here
            // 1. Sanitize and retrieve data
            $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? ''; // Password is optional for edit
             $confirmPassword = $_POST['confirmPassword'] ?? ''; // Added confirm password
            $role = trim($_POST['role'] ?? '');
            $status = trim($_POST['status'] ?? '');

            // 2. Validation
            $errors = [];
            if (empty($userId)) {
                // This is a fundamental request error, not a form field error
                 send_json_response(false, 'Invalid User ID provided for edit.');
            }
            if (empty($username)) { $errors['username'] = "Username is required."; }
            if (empty($email)) {
                 $errors['email'] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 $errors['email'] = "Invalid email format.";
            } else {
                 // Check if email exists for ANOTHER user
                 if (isEmailTaken($pdo, $email, $userId)) { // Pass ID to exclude
                    $errors['email'] = "Email address is already used by another account.";
                 }
            }
            // Password validation (only if provided)
            if (!empty($password)) {
                 if (strlen($password) < 6) {
                    $errors['password'] = "Password must be at least 6 characters long.";
                 }
                 if ($password !== $confirmPassword) {
                    $errors['confirmPassword'] = "Passwords do not match.";
                 }
            }

            $allowed_roles = ['admin', 'collector', 'resident', 'driver', 'encoder'];
             if (empty($role) || !in_array($role, $allowed_roles)) {
                 $errors['role'] = "Please select a valid role.";
             }
            $allowed_statuses = ['active', 'inactive'];
             if (empty($status) || !in_array($status, $allowed_statuses)) {
                  $errors['status'] = "Please select a valid status.";
             }

            // 3. Process if no validation errors
            if (!empty($errors)) {
                 send_json_response(false, 'Validation failed.', $errors);
            } else {
                 // Prepare data for model function
                 $userData = [
                     'username' => $username,
                     'email' => $email,
                     'role' => $role,
                     'status' => $status
                     // DO NOT include password yet
                 ];

                 // Hash password only if a new one was entered
                 if (!empty($password)) {
                     $password_hash = password_hash($password, PASSWORD_DEFAULT);
                     if ($password_hash === false) {
                         error_log("Password hashing failed during edit for user ID: " . $userId);
                         send_json_response(false, 'Could not process new password securely.');
                     }
                     $userData['password'] = $password_hash; // Add hashed password to data array
                 }

                 // Call model function to update user
                 if (updateUser($pdo, $userId, $userData)) { // Pass PDO, ID, data
                     send_json_response(true, 'User updated successfully!');
                 } else {
                     // Could be DB error or user not found (update affected 0 rows)
                     // Check affected rows in updateUser function for more specific message
                     send_json_response(false, 'Failed to update user. User may not exist or data is unchanged.');
                 }
            }
            break; // End case 'edit'

        // --- DELETE USER ---
        case 'delete':
            // Keep your existing 'delete' logic here
            $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);

            if (empty($userId)) {
                send_json_response(false, 'Invalid User ID provided for deletion.');
            }

             // Call model function to delete user
            if (deleteUser($pdo, $userId)) { // Pass PDO, ID
                 send_json_response(true, 'User deleted successfully!');
            } else {
                 // Could be DB error or user not found (delete affected 0 rows)
                 send_json_response(false, 'Failed to delete user. User may not exist.');
            }
            break; // End case 'delete'

        // --- TOGGLE STATUS ---
        case 'toggle_status':
            // 1. Sanitize and retrieve data
            $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            $status = trim($_POST['status'] ?? ''); // The *new* status requested

            // 2. Validation
            $errors = [];
            if (empty($userId)) {
                 send_json_response(false, 'Invalid User ID provided for status update.');
            }
            $allowed_statuses = ['active', 'inactive'];
            if (empty($status) || !in_array($status, $allowed_statuses)) {
                 // This shouldn't happen with the JS, but good to validate server-side
                 send_json_response(false, 'Invalid status value provided.');
            }

            // 3. Call model function to update status
            // Use the new dedicated function updateUserStatus
            if (updateUserStatus($pdo, $userId, $status)) { // Pass PDO, ID, new status
                 send_json_response(true, 'User status updated successfully.');
            } else {
                 // Could be DB error or user not found (update affected 0 rows)
                 send_json_response(false, 'Failed to update user status. User may not exist or status is unchanged.');
            }
            break; // End case 'toggle_status'


        default:
            send_json_response(false, 'Invalid action specified.');
            break;
    }
} catch (PDOException $e) {
    // Catch database errors specifically during operations
    error_log("Handle User Actions - PDOException: " . $e->getMessage());
    send_json_response(false, 'A database error occurred during the operation. Please try again.');
} catch (Exception $e) {
     // Catch other general errors from model functions or logic
     error_log("Handle User Actions - Exception: " . $e->getMessage());
     send_json_response(false, 'An unexpected error occurred during the operation. Please try again.');
} finally {
    // Close connection (implicitly closed when script ends, but good practice)
    $pdo = null;
}

?>