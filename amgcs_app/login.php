<?php
header('Content-Type: application/json'); 
header('Access-Control-Allow-Origin: *'); 
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);

    
    if ($data !== null && isset($data['username'], $data['password'])) {
        $username = $data['username'];
        $password = $data['password'];

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            $sql = "SELECT user_id, username, password, role, status FROM user_table WHERE username = :username LIMIT 1";
            $stmt = $pdo->prepare($sql);

            $stmt->bindParam(':username', $username);

            $stmt->execute();

            $user = $stmt->fetch();

            if ($user) {

                 if (password_verify($password, $user['password'])) {
            
                     if ($user['status'] === 'active') {
                        $response['success'] = true;
                        $response['message'] = 'Login successful!';
                        $response['user_id'] = $user['user_id'];
                        $response['role'] = $user['role'];

                        // =================================================================
                        // === MODIFIED LOGIC: START - Find device_id for any collector ===
                        // =================================================================
                        
                        $deviceId = null; // Initialize deviceId as null

                        if ($user['role'] === 'collector') {
                            
                            // STEP 1: Check if the user is a DRIVER with a permanent truck assignment.
                            $driverStmt = $pdo->prepare("SELECT truck_id FROM truck_driver WHERE user_id = :user_id LIMIT 1");
                            $driverStmt->execute(['user_id' => $user['user_id']]);
                            $driverInfo = $driverStmt->fetch();

                            if ($driverInfo && !empty($driverInfo['truck_id'])) {
                                // Found a permanent assignment in the driver table. Use this truck_id.
                                $deviceId = $driverInfo['truck_id'];
                            } else {
                                // STEP 2: If not a driver, check if the user is an ASSISTANT.
                                $assistantStmt = $pdo->prepare("SELECT assistant_id FROM truck_assistant WHERE user_id = :user_id LIMIT 1");
                                $assistantStmt->execute(['user_id' => $user['user_id']]);
                                $assistantInfo = $assistantStmt->fetch();

                                if ($assistantInfo && !empty($assistantInfo['assistant_id'])) {
                                    $assistantId = $assistantInfo['assistant_id'];
                                    
                                    // STEP 3: If they are an assistant, find their truck in TODAY'S schedule.
                                    // CURDATE() gets the current date from the server.
                                    $scheduleStmt = $pdo->prepare(
                                        "SELECT truck_id FROM schedules WHERE assistant_id = :assistant_id AND date = CURDATE() LIMIT 1"
                                    );
                                    $scheduleStmt->execute(['assistant_id' => $assistantId]);
                                    $scheduleInfo = $scheduleStmt->fetch();

                                    if ($scheduleInfo && !empty($scheduleInfo['truck_id'])) {
                                        // Found a schedule for today! Use this truck_id.
                                        $deviceId = $scheduleInfo['truck_id'];
                                    }
                                    // If no schedule is found for today, $deviceId remains null, which is correct.
                                }
                            }
                        }

                        // Assign the found deviceId (or null if none was found) to the response.
                        // The Flutter app expects 'device_id'. We cast to string to match the successful log examples.
                        $response['device_id'] = $deviceId ? (string)$deviceId : null;

                        // ===============================================================
                        // === MODIFIED LOGIC: END                                     ===
                        // ===============================================================

                     } else {
                         
                         $response['message'] = 'Your account is not active.';
                     }
                 } else {
    
                    $response['message'] = 'Invalid username or password.';
                 }
            } else {
                
                $response['message'] = 'Invalid username or password.';
            }

        } catch (\PDOException $e) {
            
            error_log("Database error during login: " . $e->getMessage()); 
            $response['message'] = 'An error occurred during login.'; 
        }
    } else {
         $response['message'] = 'Invalid input data format.';
    }
} else {
    
    $response['message'] = 'Only POST requests are allowed.';
}


echo json_encode($response);

?>