<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/json; charset=UTF-8");

// Handle pre-flight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Ensure it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
    exit();
}

// Get the schedule_id from the request body
$json_data = file_get_contents('php://input');
$data = json_decode($json_data, true);

if (!isset($data['schedule_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Schedule ID is required.']);
    exit();
}

$schedule_id = $data['schedule_id'];

// --- Database Connection ---
$host = 'localhost';
$db   = 'amgcs_db';
$user = 'root';
$pass = ''; // Your database password
$charset = 'utf8mb4';
$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);

    // Update the status of the schedule to 'Completed'
    $sql = "UPDATE schedules SET status = 'Completed' WHERE schedule_id = :schedule_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['schedule_id' => $schedule_id]);

    // Check if any row was actually updated
    if ($stmt->rowCount() > 0) {
        echo json_encode(['status' => 'success', 'message' => 'Schedule marked as completed.']);
    } else {
        http_response_code(404);
        echo json_encode(['status' => 'error', 'message' => 'Schedule not found or already completed.']);
    }

} catch (\PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()]);
}
?>