<?php
session_start();
require 'db_connect.php';

// Define the page title *before* including the header
$pageTitle = "Add Schedule";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
require_once 'templates/footer.php';

// No longer need to fetch drivers
// Fetch available trucks (Keep this part)
try {
    $truckStmt = $pdo->query("SELECT truck_id, plate_number FROM truck_info WHERE availability_status = 'Active' ORDER BY plate_number");
    $trucks = $truckStmt->fetchAll();
} catch (\PDOException $e) {
    error_log("Error fetching trucks: " . $e->getMessage());
    $trucks = [];
    $_SESSION['error'] = "Could not fetch trucks for selection.";
}

// Get form data back on error (if available)
$form_data = $_SESSION['form_data'] ?? [];
unset($_SESSION['form_data']);

?>
<html>
<div class="content">
  <div class="form-container">
    <h2>Add New Collection Schedule</h2>

     <?php if (isset($_SESSION['error'])): ?>
        <div class="flash-error">
            <?= nl2br(htmlspecialchars($_SESSION['error'])); // Use nl2br for multi-line errors ?>
        </div>
        <?php unset($_SESSION['error']); ?>
     <?php endif; ?>

    <form action="add_schedule_process.php" method="POST">
        <label for="date">Date:</label>
        <input type="date" id="date" name="date" value="<?= htmlspecialchars($form_data['date'] ?? ''); ?>" required />

        <!-- *** MODIFIED: Time Range Inputs *** -->
        <label for="start_time">Start Time:</label>
        <input type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($form_data['start_time'] ?? ''); ?>" required />

        <label for="end_time">End Time:</label>
        <input type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($form_data['end_time'] ?? ''); ?>" required />
        <!-- *** END MODIFICATION *** -->

        <label for="route_description">Route Description:</label>
        <input type="text" id="route_description" name="route_description" value="<?= htmlspecialchars($form_data['route_description'] ?? ''); ?>" placeholder="e.g., Barangay Zone 1 -> Landfill" required />

        <!-- *** MODIFIED: Driver Name Input *** -->
        <label for="driver_name">Driver Name:</label>
        <input type="text" id="driver_name" name="driver_name" value="<?= htmlspecialchars($form_data['driver_name'] ?? ''); ?>" placeholder="Enter driver's full name" required />
        <!-- *** END MODIFICATION *** -->

        <label for="truck_id">Assign Truck:</label>
        <select id="truck_id" name="truck_id" required>
            <option value="">-- Select Truck --</option>
             <?php foreach ($trucks as $truck): ?>
                <option value="<?= htmlspecialchars($truck['truck_id']); ?>" <?= (isset($form_data['truck_id']) && $form_data['truck_id'] == $truck['truck_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($truck['plate_number']); ?>
                </option>
            <?php endforeach; ?>
             <?php if (empty($trucks)): ?>
                <option value="" disabled>No active trucks available</option>
            <?php endif; ?>
        </select>

        <!-- *** MODIFIED: Waste Type/Days Input *** -->
        <label for="waste_type">Waste Type / Days:</label>
        <input type="text" id="waste_type" name="waste_type" value="<?= htmlspecialchars($form_data['waste_type'] ?? ''); ?>" placeholder="e.g., M/W/F - Biodegradable OR T/Th/S - Recyclable" required />
        <!-- *** END MODIFICATION *** -->

        <label for="status">Status:</label>
        <select id="status" name="status" required>
             <?php $selected_status = $form_data['status'] ?? 'Active'; ?>
            <option value="Active" <?= ($selected_status == 'Active') ? 'selected' : ''; ?>>Active</option>
            <option value="Completed" <?= ($selected_status == 'Completed') ? 'selected' : ''; ?>>Completed</option>
            <option value="Cancelled" <?= ($selected_status == 'Cancelled') ? 'selected' : ''; ?>>Cancelled</option>
        </select>

        <div class="form-actions">
            <button type="submit" class="btn-save">Save Schedule</button>
            <a href="dashboard_schedule.php" class="btn-cancel">Cancel</a>
        </div>
    </form>
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