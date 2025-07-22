<?php
header('Content-Type: application/json');
// WARNING: In production, replace '*' with the specific origin(s) of your frontend application
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, Origin, X-Requested-With, Accept');

// Handle OPTIONS request (preflight)
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// --- Database credentials --- CHANGE THESE
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

// --- Upload Directory --- CHANGE THIS IF NECESSARY
// Define the directory where files will be uploaded relative to this script's location
// MAKE SURE this directory exists AND is writable by the web server!
$uploadDir = __DIR__ . '/uploads/profile_pictures/';

// Check if the upload directory exists and is writable
if (!is_dir($uploadDir) || !is_writable($uploadDir)) {
    $response = ['success' => false, 'message' => 'Upload directory does not exist or is not writable on the server.'];
    error_log("Upload error: Upload directory '$uploadDir' does not exist or is not writable.");
    http_response_code(500); // Internal Server Error
    echo json_encode($response);
    exit();
}


$response = ['success' => false, 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Check if user_id is provided
    if (!isset($_POST['user_id']) || empty($_POST['user_id'])) {
        $response['message'] = 'Missing user ID.';
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit();
    }

    $userId = intval($_POST['user_id']); // Get user ID as integer

    // 2. Check if file was uploaded
    // The file input name from Flutter is 'profile_picture' based on your code
    if (!isset($_FILES['profile_picture'])) {
        $response['message'] = 'No file uploaded or incorrect file field name.';
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit();
    }

    $file = $_FILES['profile_picture'];

    // 3. Check for file upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        switch ($file['error']) {
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                $response['message'] = 'Uploaded file exceeds maximum size.';
                break;
            case UPLOAD_ERR_PARTIAL:
                $response['message'] = 'File was only partially uploaded.';
                break;
            case UPLOAD_ERR_NO_FILE:
                $response['message'] = 'No file was uploaded.';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $response['message'] = 'Server missing temporary folder for uploads.';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $response['message'] = 'Server failed to write file to disk.';
                break;
            case UPLOAD_ERR_EXTENSION:
                $response['message'] = 'A PHP extension stopped the file upload.';
                break;
            default:
                $response['message'] = 'Unknown upload error.';
                break;
        }
        error_log("Upload error for user $userId: " . $response['message']);
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit();
    }

    // 4. Validate file type (optional but recommended)
    // You can check $file['type'] or better yet, the file extension
    $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']; // Allowed extensions
    $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

    if (!in_array($fileExtension, $allowedTypes)) {
        $response['message'] = 'Invalid file type. Only JPG, JPEG, PNG, GIF are allowed.';
        http_response_code(400); // Bad Request
        echo json_encode($response);
        exit();
    }

    // 5. Generate a unique filename
    // Using user ID + unique ID + original extension to minimize collision risk
    $uniqueFilename = $userId . '_' . uniqid() . '.' . $fileExtension;
    $destinationPath = $uploadDir . $uniqueFilename;

    // 6. Move the uploaded file to the destination directory
    if (move_uploaded_file($file['tmp_name'], $destinationPath)) {
        // File successfully moved, now update the database

        try {
            $pdo = new PDO($dsn, $user, $pass, $options);

            // --- FIX: Update the correct table and column ---
            // Assuming you added 'profile_picture_filename' to the 'employee' table
            $sql = "UPDATE employee SET profile_picture_filename = :filename WHERE user_id = :user_id";
            $stmt = $pdo->prepare($sql);

            // Bind the filename and user ID
            $stmt->bindParam(':filename', $uniqueFilename); // Store just the filename
            $stmt->bindParam(':user_id', $userId);

            $stmt->execute();

            // Check if a row was actually updated (meaning the user_id exists in employee table)
            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Profile picture uploaded and database updated successfully.';
                // Optionally, you could return the new URL or filename here
                $response['filename'] = $uniqueFilename;
            } else {
                // User ID exists but no matching row in employee table? Or update failed for another reason.
                 // It's generally better to log this server-side and return a generic error to the client
                error_log("Database update warning: No employee row updated for user_id: $userId");
                // Decide how to handle this - either success (file saved) or failure (DB not updated)
                // Let's treat it as a success for the file upload but note the DB issue
                $response['success'] = true; // File was uploaded, but DB update might have failed
                $response['message'] = 'Profile picture uploaded, but failed to update database record for user_id. Please check server logs.';

                // OR treat it as a failure and potentially clean up the file (more complex)
                // $response['success'] = false;
                // $response['message'] = 'Failed to update profile picture in database.';
                // // To clean up the file: unlink($destinationPath); // requires error handling for unlink itself
            }


        } catch (\PDOException $e) {
            // Database error occurred
            error_log("Database error updating profile picture for user $userId: " . $e->getMessage());
            // Return error response to the app
            $response['message'] = 'Database error occurred while saving picture info.';
            http_response_code(500); // Internal Server Error

            // Optional: Attempt to delete the uploaded file if the database update fails
            // This prevents orphan files, but adds complexity
             if (file_exists($destinationPath)) {
                 unlink($destinationPath);
             }

        } catch (\Exception $e) {
            // Other unexpected errors
             error_log("Unexpected error during profile picture database update for user $userId: " . $e->getMessage());
             $response['message'] = 'An unexpected error occurred.';
             http_response_code(500); // Internal Server Error
             if (file_exists($destinationPath)) {
                  unlink($destinationPath);
             }
        }

    } else {
        // Failed to move uploaded file
        $response['message'] = 'Failed to move uploaded file on the server. Check directory permissions.';
       error_log("Upload error for user $userId: Failed to move file '{$file['tmp_name']}' to '$destinationPath'. Check permissions.");
        http_response_code(500); // Internal Server Error
    }

} else {
    // Not a POST request
    $response['message'] = 'Only POST requests are allowed.';
    http_response_code(405); // Method Not Allowed
}

echo json_encode($response);
?>