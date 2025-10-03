<?php
session_start(); // Start the session

// Get the requested quarter from the URL (GET parameter 'q')
$requested_quarter = isset($_GET['q']) ? (int)$_GET['q'] : null;

// Validate the requested quarter
if ($requested_quarter >= 1 && $requested_quarter <= 4) {
    // Store the valid quarter in the session
    $_SESSION['selected_quarter'] = $requested_quarter;
    $_SESSION['message'] = "Switched to Quarter " . htmlspecialchars($requested_quarter); // Optional: Add a success message
} else {
    // If the requested quarter is invalid or missing, default to 1st quarter
    $_SESSION['selected_quarter'] = 1; // Default quarter
    $_SESSION['error'] = "Invalid quarter specified. Showing 1st Quarter."; // Optional: Add an error message
}

// Redirect the user back to the dashboard schedule page
header("Location: dashboard_schedule.php");
exit(); // Stop script execution after redirection
?>