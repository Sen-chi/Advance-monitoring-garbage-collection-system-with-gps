<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *'); // WARNING: Be specific in production!
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Database credentials - CHANGE THESE
$host = 'localhost';
$db   = 'amgcs_db';
$user = 'root'; // <-- Your database username
$pass = ''; // <-- Your database password
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get the user_id from the query string (e.g., profile.php?user_id=123)
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0; // Get as integer

    if ($userId > 0) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // Prepare the SQL query to find the user by user_id
            // *** FIX: JOIN user_table and employee table to get all necessary columns ***
            // Using aliases ut for user_table and e for employee for clarity
            $sql = "SELECT
                        ut.user_id,
                        ut.username,
                        ut.email,
                        ut.role,
                        ut.status,
                        e.first_name,
                        e.middle_name,
                        e.last_name,
                        e.contact_number
                    FROM
                        user_table ut
                    JOIN
                        employee e ON ut.user_id = e.user_id
                    WHERE
                        ut.user_id = :user_id
                    LIMIT 1";

            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();

            $user = $stmt->fetch();

            if ($user) {
                // User found, return profile data

                // Add profile_picture_url - **NOTE:** This column is NOT in your schema dump.
                // If you have a profile_picture_url stored somewhere (e.g., employee table, or derived path)
                // you need to add it here. For now, we'll add a placeholder or assume it's null
                // based on the provided schema. Your Flutter model expects it.
                 $user['profile_picture_url'] = null; // Set to null or fetch from wherever it's stored if not in employee/user tables

                $response['success'] = true;
                $response['message'] = 'Profile fetched successfully';
                $response['profile'] = $user; // Return the user data array

            } else {
                // User not found for the given ID or no matching employee record
                http_response_code(404); // Indicate Not Found
                $response['message'] = 'User profile or associated employee data not found.';
            }

        } catch (\PDOException $e) {
            // This will log the actual database error message
            error_log("Database error fetching profile: " . $e->getMessage());
            http_response_code(500); // Indicate Internal Server Error
            // Keep the generic message returned to the app for security
            $response['message'] = 'An error occurred while fetching profile.';
        }
    } else {
        // user_id was missing or invalid in the request
        http_response_code(400); // Indicate Bad Request
        $response['message'] = 'Missing or invalid user ID.';
    }
} else {
    // Not a GET request
    http_response_code(405); // Indicate Method Not Allowed
    $response['message'] = 'Only GET requests are allowed.';
}

echo json_encode($response);
?>