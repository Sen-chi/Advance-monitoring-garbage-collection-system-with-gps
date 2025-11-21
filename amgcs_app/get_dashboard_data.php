<?php
// FILE: amgcs_app/get_dashboard_data.php

header('Content-Type: application/json');
ini_set('display_errors', 0);

// THE FIX: Use your original connection method.
// We "catch" the returned PDO object from your db_connect.php file.
$pdo = require_once 'db_connect.php'; 

// Check if the PDO connection object was created successfully
if (!$pdo) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed.']);
    exit;
}

try {
    // 1. Data for Waste Type Pie Chart
    $pie_sql = "SELECT waste_type, COUNT(*) as count FROM schedules GROUP BY waste_type";
    $pie_stmt = $pdo->query($pie_sql);
    $pie_data = $pie_stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Data for Weekly Summary Bar Chart
    $bar_sql = "SELECT 
                    WEEK(date, 1) as week_number, 
                    COUNT(*) as count
                FROM schedules
                WHERE 
                    status = 'Completed' AND 
                    date >= DATE_SUB(CURDATE(), INTERVAL 5 WEEK)
                GROUP BY week_number
                ORDER BY week_number DESC
                LIMIT 5";
    $bar_stmt = $pdo->query($bar_sql);
    $bar_data = $bar_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'pieChartData' => $pie_data,
        'barChartData' => $bar_data
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database query error: ' . $e->getMessage()]);
}
?>