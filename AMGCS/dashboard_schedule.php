<?php
session_start();
require 'db_connect.php';

// Define the page title *before* including the header
$pageTitle = "Schedule Management";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
require_once 'templates/footer.php';

// *** MODIFIED: Select new/changed columns, remove old 'time' ***
$sql = "SELECT
            s.schedule_id,
            s.date,
            s.start_time, -- Changed from time
            s.end_time,   -- Added
            s.status,
            s.waste_type, -- Will contain days now
            s.route_description,
            s.driver_name, -- Added
            t.truck_id,
            COALESCE(t.plate_number, 'N/A') AS plate_number
        FROM schedules s
        LEFT JOIN truck_info t ON s.truck_id = t.truck_id
        ORDER BY s.date DESC, s.start_time ASC"; // Order by start_time

try {
    $stmt = $pdo->query($sql);
    $schedules = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log("Error fetching schedules: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving schedule data.";
    $schedules = [];
}
?>
<html>
<div class="content">
    <h2>Schedule Management</h2>

     <!-- Flash Messages -->
     <?php if (isset($_SESSION['message'])): ?>
        <div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); ?></div>
        <?php unset($_SESSION['message']); ?>
     <?php endif; ?>
     <?php if (isset($_SESSION['error'])): ?>
        <div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); ?></div>
        <?php unset($_SESSION['error']); ?>
     <?php endif; ?>

    <div class="btno-container">
      <a href="add_schedule.php" class="btn-add"> <i class="fa-solid fa-calendar-plus"></i> Add schedule </a>
      <table>
        <thead>
          <tr>
            <!-- *** MODIFIED: Table Headers *** -->
            <th>DATE</th>
            <th>TIME RANGE</th>
            <th>ROUTE DESCRIPTION</th>
            <th>DRIVER NAME</th>
            <th>TRUCK (Plate No.)</th>
            <th>WASTE TYPE / DAYS</th>
            <th>STATUS</th>
            <th>ACTION</th>
            <!-- *** END MODIFICATION *** -->
          </tr>
        </thead>
        <tbody>
          <?php if (!empty($schedules)): ?>
            <?php foreach ($schedules as $schedule): ?>
              <tr>
                <td><?= htmlspecialchars(date("M d, Y", strtotime($schedule['date']))) ?></td>
                <!-- *** MODIFIED: Display new/changed data *** -->
                <td>
                    <?= htmlspecialchars(date("g:i A", strtotime($schedule['start_time']))) ?> -
                    <?= htmlspecialchars(date("g:i A", strtotime($schedule['end_time']))) ?>
                </td>
                <td><?= htmlspecialchars($schedule['route_description'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($schedule['driver_name'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($schedule['plate_number']) ?></td>
                <td><?= htmlspecialchars($schedule['waste_type']) ?></td>
                <td>
                  <?php
                  $status_class = 'status-' . strtolower(htmlspecialchars($schedule['status']));
                  echo '<span class="' . $status_class . '">' . htmlspecialchars($schedule['status']) . '</span>';
                  ?>
                </td>
                <!-- *** END MODIFICATION *** -->
                <td>
                  <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-edit"><i class="fa-solid fa-pen-to-square"></i></a>
                  <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?');"><i class="fa-solid fa-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <!-- *** MODIFIED: Colspan adjusted *** -->
              <td colspan="8" class="no-schedules">No schedules found.</td>
              <!-- *** END MODIFICATION *** -->
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</div>

 <!-- Logout Confirmation Modal -->
 <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 350px; text-align: center;"> <!-- Adjusted style -->
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <button class="yes-btn" onclick="logout()">Yes</button> <!-- Use confirm-btn style -->
            <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
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

      // --- New Dropdown Logic ---
const settingsButton = document.getElementById('settings-button');
const settingsMenu = document.getElementById('settings-menu');

// Function to explicitly close the dropdown
function closeDropdown() {
  if (settingsMenu && settingsButton) {
    settingsMenu.classList.remove('show');
    settingsButton.setAttribute('aria-expanded', 'false');
  }
}

// Toggle dropdown ONLY if button and menu exist
if (settingsButton && settingsMenu) {
  settingsButton.addEventListener('click', function(event) {
    event.stopPropagation(); // Prevent click from immediately closing menu
    const isExpanded = settingsMenu.classList.toggle('show');
    settingsButton.setAttribute('aria-expanded', isExpanded);
  });

  // Close the dropdown if the user clicks outside of it
  window.addEventListener('click', function(event) {
    // Check if the click was outside the button AND outside the menu
    if (!settingsButton.contains(event.target) && !settingsMenu.contains(event.target)) {
      if (settingsMenu.classList.contains('show')) {
        closeDropdown();
      }
    }
  });

  // Optional: Close dropdown on Escape key press
  window.addEventListener('keydown', function(event) {
      if (event.key === 'Escape' && settingsMenu.classList.contains('show')) {
          closeDropdown();
      }
  });
} else {
    console.error("Settings dropdown button or menu element not found!");
}
  </script>
</body>
</html>