<?php
// Start the session MUST be the very first thing
session_start();

// Include PDO database connection script
// Assumes db_connect.php returns a PDO object named $pdo
// and handles its own connection errors.
$pdo = require_once('db_connect.php');

// Set JSON response header
header('Content-Type: application/json');

// --- Development Error Reporting ---
// IMPORTANT: Disable or log errors instead of displaying them in production
error_reporting(E_ALL);
ini_set('display_errors', 1); // Turn off in production
// --- End Development Error Reporting ---

// Check if the request method is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405); // Method Not Allowed
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

// --- Input Validation ---
if (!isset($_POST['username'], $_POST['email'], $_POST['password'], $_POST['confirm_password'])) {
    echo json_encode(["status" => "error", "message" => "All fields are required"]);
    exit();
}

$username = trim($_POST['username']);
$email = trim($_POST['email']);
$password = $_POST['password']; // Don't trim password initially
$confirm_password = $_POST['confirm_password'];

// Basic validation checks
if (empty($username) || empty($email) || empty($password)) {
     echo json_encode(["status" => "error", "message" => "Username, Email, and Password cannot be empty"]);
     exit();
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(["status" => "error", "message" => "Invalid email format"]);
    exit();
}

// Check if passwords match
if ($password !== $confirm_password) {
    echo json_encode(["status" => "error", "message" => "Passwords do not match"]);
    exit();
}

// --- Database Connection Check (PDO specific) ---
if (!$pdo instanceof PDO) {
    error_log("Database connection object is not a PDO instance in register script.");
    echo json_encode(["status" => "error", "message" => "Internal Server Error: Database configuration issue."]);
    exit();
}

// --- Database Interaction using PDO ---
$stmt = null; // Initialize statement variable

try {
    // 1. Check if email or username already exists
    $sql_check = "SELECT user_id FROM user_table WHERE email = :email OR username = :username LIMIT 1";
    $stmt_check = $pdo->prepare($sql_check);

    // Bind values
    $stmt_check->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt_check->bindValue(':username', $username, PDO::PARAM_STR);

    $stmt_check->execute();

    // Check if a user was found
    if ($stmt_check->fetch()) {
        echo json_encode(["status" => "error", "message" => "Email or Username already exists"]);
        $stmt_check->closeCursor(); // Close this statement
        $pdo = null; // Close connection
        exit();
    }
    $stmt_check->closeCursor(); // Close the check statement cursor

    // 2. Hash the password (only if user doesn't exist)
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    if ($hashed_password === false) {
        // Handle password hashing failure
        error_log("Password hashing failed for user: " . $username);
        echo json_encode(["status" => "error", "message" => "Could not process registration. Please try again."]);
        $pdo = null;
        exit();
    }

    // 3. Insert new user
    $sql_insert = "INSERT INTO user_table (username, email, password) VALUES (:username, :email, :password)";
    $stmt_insert = $pdo->prepare($sql_insert);

    // Bind values for insert
    $stmt_insert->bindValue(':username', $username, PDO::PARAM_STR);
    $stmt_insert->bindValue(':email', $email, PDO::PARAM_STR);
    $stmt_insert->bindValue(':password', $hashed_password, PDO::PARAM_STR);

    // Execute the insert query
    if ($stmt_insert->execute()) {
        // Registration successful
        echo json_encode(["status" => "success", "message" => "Registration successful"]);
    } else {
        // Specific insert error (though exceptions are preferred)
         error_log("PDO execute failed for insert user: " . $username); // Log specific error if possible
         echo json_encode(["status" => "error", "message" => "Registration failed. Please try again."]);
    }
    $stmt_insert->closeCursor(); // Close insert statement cursor

} catch (PDOException $e) {
    // Catch any PDO-related errors during DB operations
    error_log("Register script PDOException: " . $e->getMessage()); // Log detailed error
    echo json_encode(["status" => "error", "message" => "An error occurred during registration. Please try again."]); // Generic message to user

} catch (Exception $e) {
    // Catch any other unexpected errors
    error_log("Register script general error: " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "An unexpected error occurred."]);

} finally {
    // --- Cleanup ---
    // PDO connection is typically closed when the script ends or $pdo is set to null
    $pdo = null;
}

// No exit() needed here as finally block runs and previous exits handle specific cases
?>