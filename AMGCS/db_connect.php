<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "amgcs_db";
$charset = 'utf8mb4'; // Recommended charset

// Data Source Name (DSN)
$dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";

// PDO Options
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Enable exception mode
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
    PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
];

try {
    // Create PDO instance
    $pdo = new PDO($dsn, $username, $password, $options);

    // *** ADD THIS LINE ***
    // Return the connection object if successful
    return $pdo;

} catch (PDOException $e) {
    // Log error message for debugging
    error_log("Database Connection Failed: " . $e->getMessage());

    // Display a user-friendly error on the page instead of just JSON
    // Stop script execution as we cannot proceed without a database
    die("<html><body><h1>Database Connection Error</h1><p>Could not connect to the database. Please check the server logs or contact the administrator.</p><p><small>Error details (for debugging): " . htmlspecialchars($e->getMessage()) . "</small></p></body></html>");
}

// Code here won't be reached if connection fails due to die()
// or if it succeeds due to return
?>