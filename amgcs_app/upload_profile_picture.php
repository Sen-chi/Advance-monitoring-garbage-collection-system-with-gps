<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Database Configuration ---
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

// --- Upload Directory ---
$uploadDir = __DIR__ . '/uploads/profile_pictures/';

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response['message'] = 'Missing user ID.';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
        $response['message'] = 'File upload error. Please try again.';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }

    $userId = intval($_POST['user_id']);
    $file = $_FILES['profile_picture'];

    // --- Generate a new unique filename ---
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'];
    if (!in_array($fileExtension, $allowedTypes)) {
        $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, and GIF are allowed.';
        http_response_code(400);
        echo json_encode($response);
        exit();
    }
    $newFilename = $userId . '_' . uniqid() . '.' . $fileExtension;
    $destinationPath = $uploadDir . $newFilename;

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        
        // --- STEP 1: Find the old filename before we do anything else ---
        $sql = "SELECT profile_picture_filename FROM employee WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetch();
        $oldFilename = $result ? $result['profile_picture_filename'] : null;

        // --- STEP 2: Move the newly uploaded file to the destination ---
        if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
            
            // --- STEP 3: Update the database with the NEW filename ---
            $sql = "UPDATE employee SET profile_picture_filename = :filename WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);
            $stmt->bindParam(':filename', $newFilename, PDO::PARAM_STR);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->execute();

            if ($stmt->rowCount() > 0) {
                // --- STEP 4: If the database updated successfully, delete the old file ---
                if ($oldFilename) {
                    $oldFilePath = $uploadDir . $oldFilename;
                    if (file_exists($oldFilePath)) {
                        // Use @unlink to suppress warnings if file is somehow not deletable
                        @unlink($oldFilePath); 
                    }
                }
                $response['success'] = true;
                $response['message'] = 'Profile picture uploaded successfully.';
            } else {
                // This shouldn't happen if user_id is valid, but is a good safeguard.
                unlink($destinationPath); 
                $response['message'] = 'User not found in employee records.';
                http_response_code(404);
            }
        } else {
            // Failed to move uploaded file
            $response['message'] = 'Server failed to save the uploaded file.';
            http_response_code(500);
        }

    } catch (\PDOException $e) {
        // A database error occurred.
        error_log("Database error during upload for user $userId: " . $e->getMessage());
        // If the file was already moved, we should try to delete it to avoid orphans.
        if (file_exists($destinationPath)) {
            @unlink($destinationPath);
        }
        $response['message'] = 'A database error occurred on the server.';
        http_response_code(500);
    }
}

echo json_encode($response);
?>