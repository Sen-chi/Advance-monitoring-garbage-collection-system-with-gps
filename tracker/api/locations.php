<?php

date_default_timezone_set('Asia/Manila');

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

$data_file_path = __DIR__ . '/truck_locations_data.json';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $json_data = file_get_contents('php://input');
    $new_location = json_decode($json_data, true);

    if ($new_location && isset($new_location['deviceId'], $new_location['latitude'], $new_location['longitude'])) {
        $current_locations = [];
        if (file_exists($data_file_path) && filesize($data_file_path) > 0) {
            $current_locations = json_decode(file_get_contents($data_file_path), true) ?: [];
        }

        $found = false;
        foreach ($current_locations as &$loc) {
            if ($loc['deviceId'] === $new_location['deviceId']) {
                $loc['latitude'] = (float)$new_location['latitude'];
                $loc['longitude'] = (float)$new_location['longitude'];
                $loc['timestamp'] = date('Y-m-d H:i:s'); 
                $loc['locationName'] = $new_location['locationName'] ?? 'N/A';
                $loc['username'] = $new_location['username'] ?? 'N/A';
                $found = true;
                break;
            }
        }
        unset($loc);

        if (!$found) {
            
            $new_location['timestamp'] = date('Y-m-d H:i:s');
            $current_locations[] = $new_location;
        }

        file_put_contents($data_file_path, json_encode($current_locations, JSON_PRETTY_PRINT));
        echo json_encode(['status' => 'success', 'message' => 'Location updated.']);
    } else {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid data provided.']);
    }

} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!file_exists($data_file_path) || filesize($data_file_path) === 0) {
        echo json_encode([]);
        exit();
    }

    $locations_from_file = json_decode(file_get_contents($data_file_path), true);
    if (!is_array($locations_from_file)) {
        echo json_encode([]);
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

    try {
        $pdo = new PDO($dsn, $user, $pass, $options);
        $enriched_locations = [];

        foreach ($locations_from_file as $location) {
            $truck_id = $location['deviceId'];
            $sql = "SELECT ti.plate_number, ti.capacity_kg, CONCAT(td.first_name, ' ', td.last_name) AS driver_name
                    FROM truck_info ti
                    LEFT JOIN truck_driver td ON ti.truck_id = td.truck_id
                    WHERE ti.truck_id = :truck_id";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['truck_id' => $truck_id]);
            $truck_info = $stmt->fetch();

            $location['plate_number'] = $truck_info['plate_number'] ?? 'N/A';
            $location['driver_name'] = trim($truck_info['driver_name'] ?? 'Unassigned');
            $location['capacity'] = $truck_info['capacity_kg'] ? $truck_info['capacity_kg'] . ' kg' : 'N/A';
            
            $enriched_locations[] = $location;
        }
        echo json_encode($enriched_locations);
    } catch (\PDOException $e) {
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Database connection failed: ' . $e->getMessage()]);
    }

} else {
    http_response_code(405);
    echo json_encode(['status' => 'error', 'message' => 'Method not allowed.']);
}
?>