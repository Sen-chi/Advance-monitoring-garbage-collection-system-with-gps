<?php
header('Content-Type: application/json'); // Set content type to JSON
header('Access-Control-Allow-Origin: *'); // WARNING: Use a specific origin in production (e.g., 'http://localhost:your_flutter_port')
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request (preflight for CORS)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database credentials - CHANGE THESE
$host = 'localhost';
$db   = 'amgcs_db';
$user = 'root'; // <-- Change this if your database username is different
$pass = ''; // <-- Change this if your database password is not empty
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

// Default response structure
$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get the raw POST data
    $json_data = file_get_contents('php://input');
    // Decode the JSON data
    $data = json_decode($json_data, true);

    // Check if data is valid JSON and contains username and password
    if ($data !== null && isset($data['username'], $data['password'])) {
        $username = $data['username'];
        $password = $data['password'];

        try {
            // Connect to the database using PDO for prepared statements
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Prepare the SQL query to find the user by username
            // Select user_id, username, password, role, and status
            $sql = "SELECT user_id, username, password, role, status FROM user_table WHERE username = :username LIMIT 1";
            $stmt = $pdo->prepare($sql);

            // Bind the username parameter
            $stmt->bindParam(':username', $username);

            // Execute the query
            $stmt->execute();

            // Fetch the user row
            $user = $stmt->fetch();

            // Check if user exists and verify the password and status
            if ($user) {
                 // Verify the password against the hash in the database
                 // The 'password' column in your DB seems to use bcrypt ($2y$)
                 if (password_verify($password, $user['password'])) {
                     // Check if the user status is 'active'
                     if ($user['status'] === 'active') {
                        // Password and status are correct! Login successful.
                        $response['success'] = true;
                        $response['message'] = 'Login successful!';
                        // *** IMPORTANT: Add the user_id to the response ***
                        $response['user_id'] = $user['user_id'];
                        // Optionally, return role too
                        $response['role'] = $user['role'];

                     } else {
                         // User is not active
                         $response['message'] = 'Your account is not active.';
                     }
                 } else {
                    // Password does not match
                    // Use a generic message for security
                    $response['message'] = 'Invalid username or password.';
                 }
            } else {
                // User does not exist
                // Use a generic message for security
                $response['message'] = 'Invalid username or password.';
            }

        } catch (\PDOException $e) {
            // Handle database connection or query errors
            // Log the error internally, but provide a generic message to the client for security
            error_log("Database error during login: " . $e->getMessage()); // Log error on server
            $response['message'] = 'An error occurred during login.'; // Generic message for client
        }
    } else {
         $response['message'] = 'Invalid input data format.'; // More specific error for invalid JSON
    }
} else {
    // Not a POST request
    $response['message'] = 'Only POST requests are allowed.';
}

// Send the JSON response back to the Flutter app
echo json_encode($response);

// PDO connection will close automatically when the script finishes
?>