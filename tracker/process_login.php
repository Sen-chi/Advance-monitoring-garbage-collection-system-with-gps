<?php
session_start();
header('Content-Type: application/json');

function send_json_response($status, $message) {
    echo json_encode(['status' => $status, 'message' => $message]);
    exit;
}

try {
    // We now assign the returned value from db_connect.php to the $pdo variable.
    $pdo = require_once 'db_connect.php'; 

    // --- Check if the request is valid ---
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        send_json_response('error', 'Invalid request method.');
    }

    // --- Get form data ---
    $identifier = $_POST['username_email'] ?? '';
    $password = $_POST['password'] ?? '';

    if (empty($identifier) || empty($password)) {
        send_json_response('error', 'Username/Email and Password are required.');
    }

    // --- FIX APPLIED HERE ---
    // The SQL query now has two unique placeholders (:username and :email)
    $sql = "SELECT user_id, username, email, password, role, status FROM user_table WHERE username = :username OR email = :email";
    $stmt = $pdo->prepare($sql);
    // We now provide a value for each unique placeholder in the execute() array.
    $stmt->execute([
        'username' => $identifier,
        'email'    => $identifier
    ]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    // Verify the user and password
    if ($user && password_verify($password, $user['password'])) {
        
        // Check if the user's account is active
        if ($user['status'] !== 'active') {
            send_json_response('error', 'Your account is inactive. Please contact an administrator.');
        }

        // Check if the user's role is 'collector' and block login if it is.
        if ($user['role'] === 'collector') {
            send_json_response('error', 'Collectors are not allowed to log in through this page.');
        }

        // If password is correct AND status is active, create the session
        session_regenerate_id(true);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['logged_in'] = true;

        send_json_response('success', 'Login successful! Redirecting...');

    } else {
        send_json_response('error', 'Invalid username, email, or password.');
    }

} catch (PDOException $e) {
    // Log the detailed error for the developer.
    error_log('Login/DB Error: ' . $e->getMessage());
    // --- RESTORED GENERIC ERROR MESSAGE ---
    // Send a generic error message to the user for security.
    send_json_response('error', 'A database error occurred. Please try again later.');
}
?>