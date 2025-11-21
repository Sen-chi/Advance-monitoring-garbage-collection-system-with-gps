<?php
session_start();
header('Content-Type: application/json');

// --- Response helper function ---
function send_json_response(bool $success, string $message = '', array $errors = []): void {
    $response = ['success' => $success];
    if ($message) $response['message'] = $message;
    if ($errors) $response['errors'] = $errors;
    echo json_encode($response);
    exit;
}

// --- Basic Input Validation ---
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    send_json_response(false, 'Invalid request method.');
}

if (empty($_POST['action'])) {
     send_json_response(false, 'Action not specified.');
}

$action = $_POST['action'];

// --- Database Connection & Model ---
try {
    $pdo = require("db_connect.php");
    if (!$pdo instanceof PDO) throw new Exception("Failed to get PDO object.");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    require_once("usermodel.php");
} catch (Exception $e) {
    error_log("Handle User Actions - DB/Model Error: " . $e->getMessage());
    send_json_response(false, 'Internal server error. Please try again later.');
}

// --- Define Allowed Roles for Input ---
$input_allowed_roles = ['admin', 'collector'];

// --- Action Handling ---
try {
    switch ($action) {
        // --- ADD USER ---
        case 'add':
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? ''; // CORRECTED
            $role = trim($_POST['role'] ?? '');
            $status = 'active'; // New users are active by default

            $errors = [];
            if (empty($username)) $errors['username'] = "Username is required.";
            if (empty($email)) {
                $errors['email'] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = "Invalid email format.";
            } elseif (isEmailTaken($pdo, $email)) {
                $errors['email'] = "Email address is already registered.";
            }

            if (strlen($password) < 6) {
                $errors['password'] = "Password must be at least 6 characters long.";
            }
            if ($password !== $confirmPassword) {
                $errors['confirm_password'] = "Passwords do not match."; // CORRECTED
            }

            if (empty($role) || !in_array($role, $input_allowed_roles)) {
                $errors['role'] = "Please select a valid role.";
            }

            if (!empty($errors)) {
                send_json_response(false, 'Validation failed.', $errors);
            }

            $password_hash = password_hash($password, PASSWORD_DEFAULT);
            if (!$password_hash) {
                 error_log("Password hashing failed for: " . $email);
                 send_json_response(false, 'Could not process password securely.');
            }

            $userData = compact('username', 'email', 'password_hash', 'role', 'status');
            $userData['password'] = $userData['password_hash']; // Align key for model
            
            if (addUser($pdo, $userData)) {
                send_json_response(true, 'User added successfully!');
            } else {
                send_json_response(false, 'Failed to add user to the database.');
            }
            break;

        // --- EDIT USER ---
        case 'edit':
            $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$userId) send_json_response(false, 'Invalid User ID for edit.');

            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? ''; // CORRECTED
            $role = trim($_POST['role'] ?? '');

            $errors = [];
            if (empty($username)) $errors['username'] = "Username is required.";

            if (empty($email)) {
                 $errors['email'] = "Email is required.";
            } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                 $errors['email'] = "Invalid email format.";
            } elseif (isEmailTaken($pdo, $email, $userId)) {
                $errors['email'] = "Email is already used by another account.";
            }

            if (!empty($password)) {
                 if (strlen($password) < 6) $errors['password'] = "Password must be at least 6 characters long.";
                 if ($password !== $confirmPassword) $errors['confirm_password'] = "Passwords do not match."; // CORRECTED
            }

            if (empty($role) || !in_array($role, $input_allowed_roles)) {
                 $errors['role'] = "Please select a valid role.";
            }

            if (!empty($errors)) {
                 send_json_response(false, 'Validation failed.', $errors);
            }

            $userData = compact('username', 'email', 'role');
            if (!empty($password)) {
                $userData['password'] = password_hash($password, PASSWORD_DEFAULT);
                if (!$userData['password']) {
                     error_log("Password hashing failed for user ID: " . $userId);
                     send_json_response(false, 'Could not process new password securely.');
                }
            }

            if (updateUser($pdo, $userId, $userData)) {
                 send_json_response(true, 'User updated successfully!');
            } else {
                 send_json_response(false, 'No changes were made or the user could not be found.');
            }
            break;

        // --- DELETE USER ---
        case 'delete':
            $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$userId) send_json_response(false, 'Invalid User ID for deletion.');

            if (deleteUser($pdo, $userId)) {
                 send_json_response(true, 'User deleted successfully!');
            } else {
                 send_json_response(false, 'Failed to delete user. User may not exist.');
            }
            break;

        // --- TOGGLE STATUS ---
        case 'toggle_status':
            $userId = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
            if (!$userId) send_json_response(false, 'Invalid User ID for status update.');

            $status = trim($_POST['status'] ?? '');
            if (!in_array($status, ['active', 'inactive'])) {
                 send_json_response(false, 'Invalid status value provided.');
            }
            
            if (updateUserStatus($pdo, $userId, $status)) {
                 send_json_response(true, 'User status updated successfully.');
            } else {
                 send_json_response(false, 'Failed to update status. User may not exist or status is unchanged.');
            }
            break;

        default:
            send_json_response(false, 'Invalid action specified.');
            break;
    }
} catch (PDOException $e) {
    error_log("Handle User Actions - PDOException: " . $e->getMessage());
    send_json_response(false, 'A database error occurred. Please try again.');
}