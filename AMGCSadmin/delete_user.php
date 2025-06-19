<?php
session_start();
require_once 'db_connect.php'; // Connect to the database

// Check if ID is provided and is numeric
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $user_id = intval($_GET['id']); // Sanitize to integer

    // You might want to add a check here: prevent deleting the currently logged-in user
    // if (isset($_SESSION['user_id']) && $_SESSION['user_id'] == $user_id) {
    //     $_SESSION['message'] = ['type' => 'error', 'text' => 'You cannot delete your own account.'];
    //     header('Location: user_management.php');
    //     exit;
    // }

    // Prepare DELETE statement to prevent SQL injection
    $sql = "DELETE FROM user_table WHERE user_id = ?";
    $stmt = $conn->prepare($sql);

    if ($stmt) {
        $stmt->bind_param('i', $user_id); // 'i' for integer

        if ($stmt->execute()) {
            // Check if any row was actually deleted
            if ($stmt->affected_rows > 0) {
                $_SESSION['message'] = ['type' => 'success', 'text' => 'User deleted successfully!'];
            } else {
                $_SESSION['message'] = ['type' => 'error', 'text' => 'User not found or already deleted.'];
            }
        } else {
            // Handle execution error
            error_log("Error deleting user ID $user_id: " . $stmt->error);
            $_SESSION['message'] = ['type' => 'error', 'text' => 'Error deleting user. Please try again.'];
        }
        $stmt->close(); // Close the statement
    } else {
        // Handle statement preparation error
        error_log("Error preparing delete statement: " . $conn->error);
        $_SESSION['message'] = ['type' => 'error', 'text' => 'Database error during delete preparation.'];
    }

} else {
    // ID not provided or invalid
    $_SESSION['message'] = ['type' => 'error', 'text' => 'Invalid request: No user ID specified for deletion.'];
}

// Close the connection
if (isset($conn)) {
    $conn->close();
}


// Redirect back to the user list page regardless of success/failure
header('Location: user_management.php');
exit; // Important to stop script execution after redirection
?>