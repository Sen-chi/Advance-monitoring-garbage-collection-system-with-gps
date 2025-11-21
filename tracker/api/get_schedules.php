<?php
// FILE: tracker/api/get_schedules.php

header('Content-Type: application/json');

// --- DATABASE CONNECTION ---
// Your existing database connection code is perfectly fine.
$host = 'localhost';
$db_name = 'amgcs_db';
$username = 'root';
$password = '';

try {
    $conn = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // --- FETCH SCHEDULES WITH ASSISTANT NAME AND STATUS---
    // The query is updated to include the 'status' column from the schedules table.
    $stmt = $conn->prepare("
        SELECT 
            s.schedule_id, 
            s.date, 
            s.start_time, 
            s.end_time, 
            s.route_description, 
            s.truck_id, 
            s.driver_name, 
            s.waste_type, 
            s.days,
            s.status, -- THE ONLY LINE THAT WAS ADDED
            CONCAT_WS(' ', ta.first_name, ta.middle_name, ta.last_name) AS assistant_name
        FROM 
            schedules AS s
        LEFT JOIN 
            truck_assistant AS ta ON s.assistant_id = ta.assistant_id
        ORDER BY 
            s.date DESC, s.start_time DESC
    ");
    
    $stmt->execute();

    $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode($schedules);

} catch(PDOException $e) {
    // Return error as JSON
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}

$conn = null;
?>