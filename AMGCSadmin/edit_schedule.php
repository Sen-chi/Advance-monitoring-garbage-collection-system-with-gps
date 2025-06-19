<?php
session_start();
require 'db_connect.php';

// Define the page title *before* including the header
$pageTitle = "Edit Schedule";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
require_once 'templates/footer.php';

$schedule_id = null;
$schedule_data = null;

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    // ... handle invalid ID ...
}
$schedule_id = intval($_GET['id']);

// *** MODIFIED: Fetch new/changed columns ***
$sql_fetch = "SELECT schedule_id, date, start_time, end_time, route_description, driver_name, truck_id, waste_type, status
              FROM schedules WHERE schedule_id = ?";
// *** END MODIFICATION ***

try {
    $stmt_fetch = $pdo->prepare($sql_fetch);
    $stmt_fetch->execute([$schedule_id]);
    $schedule_data = $stmt_fetch->fetch();

    if (!$schedule_data) {
        // ... handle not found error ...
    }
} catch (\PDOException $e) {
    // ... handle fetch error ...
}

// Fetch available trucks (Keep this)
try {
    $truckStmt = $pdo->query("SELECT truck_id, plate_number FROM truck_info WHERE availability_status = 'Active' ORDER BY plate_number");
    $trucks = $truckStmt->fetchAll();
} catch (\PDOException $e) { /* ... error handling ... */ $trucks = []; }

// Get potentially stored form data if redirected back on error
$form_data = $_SESSION['form_data'] ?? $schedule_data; // Use session data first, then db data
unset($_SESSION['form_data']);

?>
<html>

<div class="content">
  <div class="form-container">
    <h2>Edit Collection Schedule (ID: <?= htmlspecialchars($schedule_id) ?>)</h2>

     <?php if (isset($_SESSION['error'])): ?>
        <div class="flash-error">
             <?= nl2br(htmlspecialchars($_SESSION['error'])); ?>
        </div>
        <?php unset($_SESSION['error']); ?>
     <?php endif; ?>

    <?php if ($schedule_data): // Check if initial fetch was successful ?>
    <form action="update_schedule_process.php" method="POST">
        <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($form_data['schedule_id']); ?>" />

        <label for="date">Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($form_data['date']); ?>" required />

        <!-- *** MODIFIED: Time Range Inputs *** -->
        <label for="start_time">Start Time:</label>
        <input type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($form_data['start_time']); ?>" required />

        <label for="end_time">End Time:</label>
        <input type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($form_data['end_time']); ?>" required />
        <!-- *** END MODIFICATION *** -->

        <label for="route_description">Route Description:</label>
        <input type="text" id="route_description" name="route_description" value="<?= htmlspecialchars($form_data['route_description']); ?>" placeholder="e.g., Barangay Zone 1 -> Landfill" required />

        <!-- *** MODIFIED: Driver Name Input *** -->
        <label for="driver_name">Driver Name:</label>
        <input type="text" id="driver_name" name="driver_name" value="<?= htmlspecialchars($form_data['driver_name']); ?>" placeholder="Enter driver's full name" required />
        <!-- *** END MODIFICATION *** -->

        <label for="truck_id">Assign Truck:</label>
        <select id="truck_id" name="truck_id" required>
            <option value="">-- Select Truck --</option>
             <?php foreach ($trucks as $truck): ?>
                <option value="<?= htmlspecialchars($truck['truck_id']); ?>" <?= ($form_data['truck_id'] == $truck['truck_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($truck['plate_number']); ?>
                </option>
            <?php endforeach; ?>
             <?php if (empty($trucks)): ?><option value="" disabled>No active trucks available</option><?php endif; ?>
        </select>

         <!-- *** MODIFIED: Waste Type/Days Input *** -->
        <label for="waste_type">Waste Type / Days:</label>
        <input type="text" id="waste_type" name="waste_type" value="<?= htmlspecialchars($form_data['waste_type']); ?>" placeholder="e.g., M/W/F - Biodegradable" required />
        <!-- *** END MODIFICATION *** -->

        <label for="status">Status:</label>
        <select id="status" name="status" required>
            <?php $statuses = ["Active", "Completed", "Cancelled"]; ?>
            <?php foreach ($statuses as $status_option): ?>
                <option value="<?= htmlspecialchars($status_option); ?>" <?= ($form_data['status'] == $status_option) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($status_option); ?>
                </option>
            <?php endforeach; ?>
        </select>

        <div class="form-actions">
            <button type="submit" class="btn-save">Update Schedule</button>
            <a href="dashboard_schedule.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
    <?php else: ?>
        <p>Could not load schedule data for editing.</p>
        <a href="dashboard_schedule.php" class="btn-cancel">Back to List</a>
    <?php endif; ?>
  </div>
</div>

<!-- Logout Confirmation Modal -->
<div id="logoutModal" class="modal">
      <div class="modal-content">
          <p>Are you sure you want to logout?</p>
          <button class="yes-btn" onclick="logout()">Yes</button>
          <button class="cancel-btn" onclick="closeModal()">Cancel</button>
      </div>
  </div>

  <script>
      function openLogoutModal() {
          document.getElementById("logoutModal").style.display = "flex";
      }

      function closeModal() {
          document.getElementById("logoutModal").style.display = "none";
      }

      function logout() {
          window.location.href = "sign_in.php"; // Redirect to sign-in page
      }
  </script>
</body>
</html>