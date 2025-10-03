<?php
header('Content-Type: application/json');


header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');


header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}


$host = 'localhost';
$db   = 'amgcs_db';
$user = 'root';
$pass = '';
$charset = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

$response = ['success' => false, 'message' => 'Invalid request'];

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $userId = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;

    if ($userId > 0) {
        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            $sql = "SELECT
                        ut.user_id, ut.username, ut.email, ut.role, ut.status,
                        e.first_name, e.middle_name, e.last_name, e.contact_number,
                        e.profile_picture_filename AS profile_picture_url
                    FROM user_table ut
                    LEFT JOIN employee e ON ut.user_id = e.user_id
                    WHERE ut.user_id = :user_id LIMIT 1";

            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();
            $user = $stmt->fetch();

            if ($user) {
                $response['success'] = true;
                $response['message'] = 'Profile fetched successfully';
                $response['profile'] = $user;
            } else {
                http_response_code(404);
                $response['message'] = 'User profile not found.';
            }
        } catch (\PDOException $e) {
            error_log("Database error fetching profile: " . $e->getMessage());
            http_response_code(500);
            $response['message'] = 'An error occurred while fetching the profile.';
        }
    } else {
        http_response_code(400);
        $response['message'] = 'Missing or invalid user ID.';
    }
} else {
    http_response_code(405);
    $response['message'] = 'Only GET requests are allowed.';
}

echo json_encode($response);
?>