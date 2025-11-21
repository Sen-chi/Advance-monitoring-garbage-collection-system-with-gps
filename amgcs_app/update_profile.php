<?php
header('Content-Type: application/json');

// Use our new centralized connection file.
$conn = require_once("db_connect.php");

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

if (!isset($_POST['user_id'], $_POST['email'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'User ID and Email are required.']);
    exit();
}

$userId = $_POST['user_id'];
$email = $_POST['email'];
$contactNumber = $_POST['contact_number'] ?? ''; // Default to empty string if not provided

$conn->beginTransaction();

try {
    // 1. Update the central user_table for email
    $userStmt = $conn->prepare("UPDATE user_table SET email = ? WHERE user_id = ?");
    $userStmt->execute([$email, $userId]);

    // 2. Update contact number in all three possible tables.
    // It's safe to run all three; they will only update if a matching user_id exists.

    // Fixed column name: contact_number
    $empStmt = $conn->prepare("UPDATE employee SET contact_number = ? WHERE user_id = ?");
    $empStmt->execute([$contactNumber, $userId]);

    // Fixed column name: contact_number
    $driverStmt = $conn->prepare("UPDATE truck_driver SET contact_number = ? WHERE user_id = ?");
    $driverStmt->execute([$contactNumber, $userId]);

    // Fixed column name: contact_number
    $assistantStmt = $conn->prepare("UPDATE truck_assistant SET contact_number = ? WHERE user_id = ?");
    $assistantStmt->execute([$contactNumber, $userId]);

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Profile updated successfully!']);

} catch (Exception $e) {
    $conn->rollBack();
    http_response_code(500);
    error_log("Update Profile Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'An error occurred during the update.']);
}

$conn = null;
?>