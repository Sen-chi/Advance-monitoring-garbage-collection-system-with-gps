<?php
session_start();
require 'db_connect.php';

// Your PHP code for fetching data is correct, no changes needed here.
$current_quarter = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;
if ($current_quarter < 1 || $current_quarter > 4) { $current_quarter = 1; }

$pageTitle = "Schedule Management";
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

$sql = "SELECT
            s.schedule_id, s.date, s.start_time, s.end_time,
            s.waste_type, s.days,
            s.route_description, s.driver_name,
            COALESCE(t.plate_number, 'N/A') AS plate_number,
            COALESCE(CONCAT(ta.first_name, ' ', ta.last_name), 'N/A') AS assistant_name
        FROM schedules s
        LEFT JOIN truck_info t ON s.truck_id = t.truck_id
        LEFT JOIN truck_assistant ta ON s.assistant_id = ta.assistant_id
        WHERE s.quarter = :current_quarter
        ORDER BY s.date DESC, s.start_time ASC";
try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':current_quarter', $current_quarter, PDO::PARAM_INT);
    $stmt->execute();
    $schedules = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log("Error fetching schedules: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving schedule data.";
    $schedules = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="css/home.css"> 
    <style>
        .table-container {
        overflow-x: auto; /* Enables horizontal scrolling */
        width: 100%;
        border: 1px solid #ddd; /* Adds a clean border around the table area */
        border-radius: 8px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }

        table {
        width: 100%;
        border-collapse: collapse;
        background-color: white;
        font-size: 14px; 
        /* We remove 'table-layout: fixed' to let columns breathe */
        }

        th, td {
        padding: 12px 15px; /* Give cells more breathing room */
        border-bottom: 1px solid #e0e0e0;
        text-align: left;
        vertical-align: middle; /* Aligns content vertically in the center */
        }

        th {
        background-color: #f7f9fc; /* A lighter, more modern header color */
        font-weight: 600; /* Use semibold for a cleaner look */
        color: #333;
        }

        /* Give specific columns a minimum width to prevent them from becoming too squished. */
        /* This allows other columns like Route and Days to use more space. */
        td:nth-child(1), th:nth-child(1) { min-width: 110px; } /* Date */
        td:nth-child(2), th:nth-child(2) { min-width: 120px; white-space: nowrap; } /* Time Range */
        td:nth-child(6), th:nth-child(6) { min-width: 90px; }  /* Truck */
        td:nth-child(7), th:nth-child(7) { min-width: 110px; } /* Waste Type */

        /* Action column styling */
        td:last-child, th:last-child {
            width: 100px; /* A fixed width for the action buttons */
            text-align: center;
        }

        table tbody tr:hover {
        background-color: #f1f5f9; /* A subtle, cool-toned hover effect */
        }
        table tbody tr:last-child td {
            border-bottom: none; /* Removes the bottom border on the last row */
        }


        /* --- CRITICAL FIX FOR ACTION BUTTONS --- */
        .action-buttons {
            display: flex;
            justify-content: center; /* Center the buttons inside the cell */
            align-items: center;
            gap: 10px; /* Adds space between the buttons */
        }

        .action-buttons .btn {
            padding: 6px 10px;
            border-radius: 5px;
            font-size: 14px;
            color: white;
            text-decoration: none;
            transition: opacity 0.2s;
        }
        .action-buttons .btn:hover {
            opacity: 0.8;
        }

        .btn-edit { background-color: #3498db; } /* Blue for Edit */
        .btn-delete { background-color: #e74c3c; } /* Red for Delete */
    </style>

</head>
<body>

<div class="content">
    <h2>Schedule Management</h2>

    <?php if (isset($_SESSION['message'])): ?>
        <div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="btno-container">
        <a href="add_schedule.php" class="btn-add"> <i class="fa-solid fa-calendar-plus"></i> Add schedule </a>
        <br><br>
        <h3><a href="quarters.php">Schedules for Quarter <?= htmlspecialchars($current_quarter); ?></a></h3>
    </div>

    <!-- FIX: Wrap the table in our new container -->
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>DATE</th>
                    <th>TIME RANGE</th>
                    <th>ROUTE</th>
                    <th>DRIVER</th>
                    <th>ASSISTANT</th>
                    <th>TRUCK</th>
                    <th>WASTE TYPE</th>
                    <th>DAYS</th>
                    <th>ACTION</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($schedules)): ?>
                    <?php foreach ($schedules as $schedule): ?>
                        <tr>
                            <td><?= htmlspecialchars(date("M d, Y", strtotime($schedule['date']))) ?></td>
                            <td>
                                <?= htmlspecialchars(date("g:i A", strtotime($schedule['start_time']))) ?> -
                                <?= htmlspecialchars(date("g:i A", strtotime($schedule['end_time']))) ?>
                            </td>
                            <td><?= htmlspecialchars($schedule['route_description'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($schedule['driver_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($schedule['assistant_name']) ?></td>
                            <td><?= htmlspecialchars($schedule['plate_number']) ?></td>
                            <td><?= htmlspecialchars($schedule['waste_type'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($schedule['days'] ?? 'N/A') ?></td>
                            <td>
                                <!-- FIX: Apply new classes for better button styling -->
                                <div class="action-buttons">
                                    <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                                    <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 20px;">No schedules found for Quarter <?= htmlspecialchars($current_quarter); ?>.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Your auto-hide script is fine.
    document.addEventListener('DOMContentLoaded', (event) => {
        const flashMessages = document.querySelectorAll('.flash-message');
        flashMessages.forEach(message => {
            setTimeout(() => {
                message.style.transition = 'opacity 0.5s ease-out';
                message.style.opacity = '0';
                setTimeout(() => { message.style.display = 'none'; }, 500);
            }, 5000);
        });
    });
</script>

<?php require_once 'templates/footer.php'; ?>
</body>
</html>