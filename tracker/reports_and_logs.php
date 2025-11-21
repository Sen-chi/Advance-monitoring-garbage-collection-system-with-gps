<?php
// Define the page title *before* including the header
$pageTitle = "Reports";

// Include the header template file
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
require_once 'db_connect.php';
$currentPage = basename($_SERVER['PHP_SELF']);
?>

<div class="content">
  <h2>Completed Collection Logs</h2>
  <div class="box-container">
    <table>
      <thead>
        <tr>
          <th>Completion Date</th>
          <th>Days of Week</th>
          <th>Time Range</th>
          <th>Route</th>
          <th>Driver</th>
          <th>Truck Plate</th>
          <th>Waste Type</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        // THIS IS THE CORRECTED QUERY TO SHOW ONLY COMPLETED SCHEDULES
        $sql = "SELECT 
                    s.date,
                    s.days,
                    s.start_time,
                    s.end_time,
                    s.route_description,
                    s.waste_type,
                    s.status,
                    CONCAT(td.first_name, ' ', td.last_name) AS driver_name,
                    ti.plate_number
                FROM schedules s
                LEFT JOIN truck_info ti ON s.truck_id = ti.truck_id
                LEFT JOIN truck_driver td ON s.truck_id = td.truck_id
                WHERE s.status = 'Completed'  -- This line filters for completed tasks only
                ORDER BY s.date DESC, s.start_time DESC";
        
        $stmt = $pdo->query($sql);

        if ($stmt->rowCount() > 0) {
            while($row = $stmt->fetch()) {
                // Formatting for display
                $date = date("F d, Y", strtotime($row['date']));
                $time = date("g:i A", strtotime($row['start_time'])) . " - " . date("g:i A", strtotime($row['end_time']));
                
                echo "<tr>";
                echo "<td>" . htmlspecialchars($date) . "</td>";
                echo "<td>" . htmlspecialchars($row['days'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($time) . "</td>";
                echo "<td>" . htmlspecialchars($row['route_description']) . "</td>";
                echo "<td>" . htmlspecialchars($row['driver_name'] ?? 'Unassigned') . "</td>";
                echo "<td>" . htmlspecialchars($row['plate_number'] ?? 'N/A') . "</td>";
                echo "<td>" . htmlspecialchars($row['waste_type']) . "</td>";
                // Add styling for status for better readability
                $statusClass = 'status-completed'; // It will always be completed here
                echo "<td><span class='status " . $statusClass . "'>" . htmlspecialchars($row['status']) . "</span></td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='8' style='text-align: center; padding: 20px;'>No completed schedules found.</td></tr>";
        }
        ?>
      </tbody>
    </table>
  </div>
</div>
<!-- Add some basic CSS for the status badges -->
<style>
.status {
    padding: 5px 10px;
    border-radius: 12px;
    color: white;
    font-weight: bold;
    display: inline-block;
}
.status-completed {
    background-color: #28a745; /* Green */
}
/* You can keep this for other pages if needed */
.status-pending {
    background-color: #ffc107; /* Orange */
    color: black;
}
</style>

<?php require_once 'templates/footer.php'; ?>

</body>
</html>