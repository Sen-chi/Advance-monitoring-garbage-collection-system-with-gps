<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");

// --- CONFIGURATION ---
$uploadDir = __DIR__ . '/uploads/profile_pictures/';
$response = ['success' => false, 'message' => 'An error occurred.'];

// --- DATABASE CONNECTION ---
$pdo = null;
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Database connection failed.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    echo json_encode($response);
    exit;
}

// --- VALIDATE INPUT ---
$userId = filter_input(INPUT_POST, 'user_id', FILTER_VALIDATE_INT);
if (!$userId) {
    $response['message'] = 'Invalid or missing user ID.';
    echo json_encode($response);
    exit;
}

if (!isset($_FILES['profile_picture']) || $_FILES['profile_picture']['error'] !== UPLOAD_ERR_OK) {
    $response['message'] = 'No file uploaded or an upload error occurred.';
    echo json_encode($response);
    exit;
}

// --- DETERMINE USER ROLE AND TABLE ---
$tableToUpdate = '';
$idColumn = '';
try {
    // Check employee table
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM employee WHERE user_id = :user_id");
    $stmt->execute(['user_id' => $userId]);
    if ($stmt->fetchColumn() > 0) {
        $tableToUpdate = 'employee';
    }

    // Check truck_driver table
    if (empty($tableToUpdate)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM truck_driver WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        if ($stmt->fetchColumn() > 0) {
            $tableToUpdate = 'truck_driver';
        }
    }

    // Check truck_assistant table
    if (empty($tableToUpdate)) {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM truck_assistant WHERE user_id = :user_id");
        $stmt->execute(['user_id' => $userId]);
        if ($stmt->fetchColumn() > 0) {
            $tableToUpdate = 'truck_assistant';
        }
    }

    if (empty($tableToUpdate)) {
        throw new Exception("Could not find a profile for the specified user ID.");
    }
} catch (Exception $e) {
    $response['message'] = $e->getMessage();
    error_log("Upload Profile Pic Error: " . $e->getMessage());
    echo json_encode($response);
    exit;
}

// --- GET OLD FILENAME (NEW) ---
$oldFileName = '';
try {
    $sql = "SELECT profile_picture_filename FROM " . $tableToUpdate . " WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['user_id' => $userId]);
    $oldFileName = $stmt->fetchColumn();
} catch (PDOException $e) {
    // Log the error but don't exit, as the main goal is to upload the new picture.
    error_log("Could not fetch old profile picture filename: " . $e->getMessage());
}


// --- PROCESS FILE UPLOAD ---
$file = $_FILES['profile_picture'];
$fileExtension = pathinfo($file['name'], PATHINFO_EXTENSION);
$newFileName = $userId . '_' . uniqid() . '.' . $fileExtension;
$destination = $uploadDir . $newFileName;

if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    $response['message'] = 'Server configuration error: Upload directory is not writable.';
    error_log('Upload directory not writable: ' . $uploadDir);
    echo json_encode($response);
    exit;
}

if (move_uploaded_file($file['tmp_name'], $destination)) {
    // --- UPDATE DATABASE ---
    try {
        $sql = "UPDATE " . $tableToUpdate . " SET profile_picture_filename = :filename WHERE user_id = :user_id";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['filename' => $newFileName, 'user_id' => $userId]);

        // --- DELETE OLD FILE (NEW) ---
        if ($oldFileName && file_exists($uploadDir . $oldFileName)) {
            unlink($uploadDir . $oldFileName);
        }

        $response['success'] = true;
        $response['message'] = 'Profile picture updated successfully.';
        $response['filepath'] = $newFileName;

    } catch (PDOException $e) {
        // If DB update fails, try to delete the uploaded file to prevent orphans
        unlink($destination);
        $response['message'] = 'Database update failed after file upload.';
        error_log("DB update error after upload: " . $e->getMessage());
    }
} else {
    $response['message'] = 'Failed to move uploaded file.';
}

echo json_encode($response);
$pdo = null;
?>