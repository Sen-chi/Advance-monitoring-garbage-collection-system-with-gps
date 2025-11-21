<?php
header("Content-Type: application/json");

// Use our new centralized connection file.
// The '$pdo' variable will be the database connection object.
$pdo = require_once("db_connect.php");

$response = ['success' => false, 'message' => 'An error occurred.'];
$userId = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);

if (!$userId) {
    $response['message'] = 'Invalid or missing user_id.';
    echo json_encode($response);
    exit;
}

try {
    // This single, more robust query joins the user_table with the three possible roles.
    // It correctly gathers all user information in one go.
    $sql = "
        SELECT
            ut.user_id,
            ut.username,
            ut.email,
            ut.role,
            ut.status,
            COALESCE(e.first_name, td.first_name, ta.first_name) as first_name,
            COALESCE(e.middle_name, td.middle_name, ta.middle_name) as middle_name,
            COALESCE(e.last_name, td.last_name, ta.last_name) as last_name,
            COALESCE(e.contact_number, td.contact_number, ta.contact_number) as contact_number,
            COALESCE(e.profile_picture_filename, td.profile_picture_filename, ta.profile_picture_filename) as profile_picture_filename
        FROM user_table ut
        LEFT JOIN employee e ON ut.user_id = e.user_id
        LEFT JOIN truck_driver td ON ut.user_id = td.user_id
        LEFT JOIN truck_assistant ta ON ut.user_id = ta.user_id
        WHERE ut.user_id = :user_id
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $userProfile = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($userProfile) {
        $response['success'] = true;
        // IMPORTANT: The key is 'data' to match your Flutter code's expectation.
        $response['data'] = $userProfile;
        $response['message'] = 'Profile fetched successfully.';
    } else {
        $response['message'] = 'User not found.';
    }

} catch (PDOException $e) {
    error_log("Fetch Profile Error: " . $e->getMessage());
    $response['message'] = 'Database query failed.';
}

echo json_encode($response);
$pdo = null;
?>