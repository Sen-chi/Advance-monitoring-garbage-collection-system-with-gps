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

                        // --- ADDED: START of logic to find the truck ID for the collector ---
                        
                        // Check if the logged-in user is a 'collector'
                        if ($user['role'] === 'collector') {
                            // Prepare a new SQL query to find the truck_id from the truck_driver table
                            // using the user_id we just confirmed.
                            $driverStmt = $pdo->prepare("SELECT truck_id FROM truck_driver WHERE user_id = :user_id");
                            $driverStmt->execute(['user_id' => $user['user_id']]);
                            $driver_info = $driverStmt->fetch();

                            // Check if we found a truck assignment for this driver
                            if ($driver_info && isset($driver_info['truck_id'])) {
                                // If yes, add the truck_id to our response as 'device_id'.
                                // The Flutter app is looking for 'device_id'.
                                $response['device_id'] = (string)$driver_info['truck_id'];
                            } else {
                                // The user is a collector but isn't assigned to a truck yet.
                                $response['device_id'] = null;
                            }
                        } else {
                            // If the user is an admin or any other role, they don't have a device.
                            $response['device_id'] = null;
                        }

                        // --- ADDED: END of logic to find truck ID ---

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