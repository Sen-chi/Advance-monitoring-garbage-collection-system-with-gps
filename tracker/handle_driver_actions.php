<?php
// handle_driver_actions.php

ob_start();

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

$pdo = null;
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Failed to get a valid database connection object.");
    }
} catch (Exception $e) {
     error_log("Database Connection Error in driver actions handler: " . $e->getMessage());
     ob_clean();
     header('Content-Type: application/json');
     echo json_encode(['success' => false, 'message' => 'Database connection failed. Please try again later.']);
     ob_end_flush();
     exit;
}

require_once("drivermodel.php");
require_once("truckmodel.php");

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Invalid request method. Only POST requests are allowed.']);
    ob_end_flush();
    exit;
}

$action = isset($_POST['action']) ? trim($_POST['action']) : '';
if (empty($action)) {
    ob_clean();
    echo json_encode(['success' => false, 'message' => 'Action not specified.']);
    ob_end_flush();
    exit;
}

$response = ['success' => false, 'message' => 'An internal server error occurred.'];
$errors = [];

try {
    if (in_array($action, ['add', 'edit', 'delete'])) {
        $pdo->beginTransaction();
    }

    switch ($action) {
        case 'add':
            $firstName = htmlspecialchars(trim($_POST['first_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $middleName = htmlspecialchars(trim($_POST['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $lastName = htmlspecialchars(trim($_POST['last_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $contactNo = htmlspecialchars(trim($_POST['contact_number'] ?? ''), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars(trim($_POST['status'] ?? ''), ENT_QUOTES, 'UTF-8');
            $truckIdInput = $_POST['truck_id'] ?? '';
            $userIdInput = $_POST['user_id'] ?? '';

            $newTruckId = !empty($truckIdInput) && filter_var($truckIdInput, FILTER_VALIDATE_INT) ? (int)$truckIdInput : null;
            $newUserId = !empty($userIdInput) && filter_var($userIdInput, FILTER_VALIDATE_INT) ? (int)$userIdInput : null;

            if (empty($firstName)) $errors['firstName'] = 'First name is required.';
            if (empty($lastName)) $errors['lastName'] = 'Last name is required.';
            if (empty($contactNo)) $errors['contactNumber'] = 'Contact number is required.';
            if (empty($status)) $errors['status'] = 'Status is required.';

            if (empty($errors)) {
                $insertSuccess = addDriver($pdo, $firstName, $middleName, $lastName, $contactNo, $newTruckId, $newUserId, $status);

                if ($insertSuccess) {
                    if ($newTruckId !== null) {
                         updateTruckAvailabilityStatus($pdo, $newTruckId, 'Assigned');
                    }
                    $pdo->commit();
                    $response = ['success' => true, 'message' => 'Driver added successfully!'];
                } else {
                     $pdo->rollBack();
                     $response = ['success' => false, 'message' => 'Failed to add driver.', 'errors' => ['general' => 'Database operation failed.']];
                }
            } else {
                 $response = ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
                 $pdo->rollBack();
            }
            break;

        case 'edit':
            $driverId = isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT) ? (int)$_POST['id'] : null;
            
            if (!$driverId) {
                $errors['general'] = 'Invalid or missing driver ID for update.';
            }

            $firstName = htmlspecialchars(trim($_POST['first_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $middleName = htmlspecialchars(trim($_POST['middle_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            $lastName = htmlspecialchars(trim($_POST['last_name'] ?? ''), ENT_QUOTES, 'UTF-8');
            // --- FIXED HERE ---
            $contactNo = htmlspecialchars(trim($_POST['contact_number'] ?? ''), ENT_QUOTES, 'UTF-8');
            $status = htmlspecialchars(trim($_POST['status'] ?? ''), ENT_QUOTES, 'UTF-8');
            $truckIdInput = $_POST['truck_id'] ?? '';
            $userIdInput = $_POST['user_id'] ?? '';

            $newTruckId = !empty($truckIdInput) && filter_var($truckIdInput, FILTER_VALIDATE_INT) ? (int)$truckIdInput : null;
            $newUserId = !empty($userIdInput) && filter_var($userIdInput, FILTER_VALIDATE_INT) ? (int)$userIdInput : null;

            if (empty($firstName)) $errors['firstName'] = 'First name is required.';
            if (empty($lastName)) $errors['lastName'] = 'Last name is required.';
            // --- AND HERE ---
            if (empty($contactNo)) $errors['contactNumber'] = 'Contact number is required.';
            if (empty($status)) $errors['status'] = 'Status is required.';

            if (empty($errors) && $driverId) {
                $oldTruckId = getAssignedTruckIdForDriver($pdo, $driverId);
                $updateResult = updateDriver($pdo, $driverId, $firstName, $middleName, $lastName, $contactNo, $newTruckId, $newUserId, $status);

                if ($updateResult !== false) {
                    $truckUpdateSuccess = true;
                    if ($oldTruckId !== null && $oldTruckId != $newTruckId) {
                        $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $oldTruckId, 'Available');
                    }
                    if ($newTruckId !== null && $newTruckId != $oldTruckId && $truckUpdateSuccess) {
                        $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $newTruckId, 'Assigned');
                    }

                    if ($truckUpdateSuccess) {
                        $pdo->commit();
                        $response = ['success' => true, 'message' => 'Driver updated successfully!'];
                    } else {
                        $pdo->rollBack();
                        $response = ['success' => false, 'message' => 'Failed to update truck assignment status. Operation cancelled.'];
                    }
                } else {
                    $pdo->rollBack();
                    $response = ['success' => false, 'message' => 'Failed to update driver. Database error.'];
                }
            } else {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Validation failed.', 'errors' => $errors];
            }
            break;

        case 'delete':
            $driverId = isset($_POST['id']) && filter_var($_POST['id'], FILTER_VALIDATE_INT) ? (int)$_POST['id'] : null;

            if (!$driverId) {
                $errors['general'] = 'Invalid or missing driver ID for deletion.';
            }

            if (empty($errors)) {
                $oldTruckId = getAssignedTruckIdForDriver($pdo, $driverId);
                $deleteResult = deleteDriver($pdo, $driverId);

                if ($deleteResult) {
                    $truckUpdateSuccess = true;
                    if ($oldTruckId !== null) {
                        $truckUpdateSuccess = updateTruckAvailabilityStatus($pdo, $oldTruckId, 'Available');
                    }
                    if ($truckUpdateSuccess) {
                        $pdo->commit();
                        $response = ['success' => true, 'message' => 'Driver deleted successfully!'];
                    } else {
                        $pdo->rollBack();
                        $response = ['success' => false, 'message' => 'Driver deleted, but failed to update truck status.'];
                    }
                } else {
                    $pdo->rollBack();
                    $response = ['success' => false, 'message' => 'Driver not found or already deleted.'];
                }
            } else {
                $pdo->rollBack();
                $response = ['success' => false, 'message' => 'Deletion failed.', 'errors' => $errors];
            }
            break;

        default:
             $response['message'] = 'Unknown action specified.';
             if ($pdo->inTransaction()) {
                $pdo->rollBack();
             }
            break;
    }
} catch (\PDOException $e) {
     if ($pdo && $pdo->inTransaction()) {
        $pdo->rollBack();
     }
     error_log("PDO Exception in handle_driver_actions.php [Action: $action]: " . $e->getMessage());
     $response['success'] = false;
     $response['message'] = 'A database error occurred while processing your request.';
}

ob_clean();
echo json_encode($response);
ob_end_flush();
exit;