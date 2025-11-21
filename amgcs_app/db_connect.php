<?php
// Centralized Database Connection
$host = 'localhost';
$dbname = 'amgcs_db';
$username = 'root';
$password = '';
$charset = 'utf8mb4';

// Set up the DSN (Data Source Name)
$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// The 'return' statement makes the PDO object available
// to any script that uses 'require_once' on this file.
try {
    return new PDO($dsn, $username, $password, $options);
} catch (\PDOException $e) {
    // If connection fails, stop everything and return a generic error.
    // This prevents leaking database credentials in error messages.
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database connection failed.'
    ]);
    exit;
}
?>