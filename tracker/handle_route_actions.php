<?php
// handle_route_actions.php

ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json');

$response = ['success' => false, 'message' => 'An unknown error occurred.'];

try {
    $pdo = require_once("db_connect.php");
    require_once("routemodel.php");

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method.');
    }

    $action = $_POST['action'] ?? '';

    switch ($action) {
        case 'add':
        case 'edit':
            // Validation
            $origin = trim($_POST['origin'] ?? '');
            $destination = trim($_POST['destination'] ?? '');
            $waypoints = trim($_POST['waypoints'] ?? '');
            
            // NEW: Get and validate coordinates from the hidden form fields
            $destLat = filter_var($_POST['destination_lat'] ?? '', FILTER_VALIDATE_FLOAT);
            $destLon = filter_var($_POST['destination_lon'] ?? '', FILTER_VALIDATE_FLOAT);
            
            $errors = [];
            if (empty($origin)) $errors['origin'] = 'Origin is required.';
            if (empty($destination)) $errors['destination'] = 'Destination is required.';
            if ($destLat === false || $destLon === false) {
                 $errors['destination_lat'] = 'Please select a valid destination on the map.';
            }
            
            if (!empty($errors)) {
                $response['message'] = 'Validation failed.';
                $response['errors'] = $errors;
                break;
            }
            
            // Action logic
            if ($action === 'add') {
                // UPDATED: Pass new coordinates to the model function
                if (addRoute($pdo, $origin, $destination, $waypoints, $destLat, $destLon)) {
                    $response = ['success' => true, 'message' => 'Route added successfully!'];
                } else {
                    $response['message'] = 'Failed to add the route.';
                }
            } else { // 'edit'
                $routeId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
                if (!$routeId) throw new Exception('Invalid Route ID for update.');

                // UPDATED: Pass new coordinates to the model function
                if (updateRoute($pdo, $routeId, $origin, $destination, $waypoints, $destLat, $destLon)) {
                    $response = ['success' => true, 'message' => 'Route updated successfully!'];
                } else {
                    $response['message'] = 'Failed to update the route.';
                }
            }
            break;

        case 'delete':
            // (This case remains unchanged)
            $routeId = filter_var($_POST['id'] ?? 0, FILTER_VALIDATE_INT);
            if (!$routeId) throw new Exception('Invalid Route ID for deletion.');
            
            if (deleteRoute($pdo, $routeId)) {
                $response = ['success' => true, 'message' => 'Route deleted successfully!'];
            } else {
                $response['message'] = 'Failed to delete the route.';
            }
            break;

        default:
            throw new Exception('Invalid action specified.');
    }

} catch (Exception $e) {
    error_log("Route Actions Error: " . $e->getMessage());
    $response['message'] = 'A server error occurred: ' . $e->getMessage();
}

echo json_encode($response);