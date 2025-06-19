<?php
session_start(); // Ensure session is started
require 'db_connect.php';

// --- Get currently selected quarter ---
// Check session, default to 1 if not set or if somehow invalid
$current_quarter = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;

// Basic validation
if ($current_quarter < 1 || $current_quarter > 4) {
    $current_quarter = 1; // Reset to default if invalid value somehow gets into session
    $_SESSION['selected_quarter'] = 1; // Update session with valid default
}


// Define the page title *before* including the header
$pageTitle = "Schedule Management";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
// require_once 'templates/footer.php'; // Footer often goes after the content

// *** MODIFIED: Select schedules filtered by the selected quarter - REMOVED s.status ***
$sql = "SELECT
            s.schedule_id,
            s.date,
            s.start_time,
            s.end_time,
            -- s.status, -- REMOVED
            s.waste_type,
            s.route_description,
            s.driver_name,
            t.truck_id,
            COALESCE(t.plate_number, 'N/A') AS plate_number
        FROM schedules s
        LEFT JOIN truck_info t ON s.truck_id = t.truck_id
        WHERE s.quarter = :current_quarter -- Filter by the selected quarter
        ORDER BY s.date DESC, s.start_time ASC";

try {
    // Use prepared statement with parameter for security
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':current_quarter', $current_quarter, PDO::PARAM_INT); // Bind the quarter parameter
    $stmt->execute();
    $schedules = $stmt->fetchAll();
} catch (\PDOException $e) {
    error_log("Error fetching schedules: " . $e->getMessage());
    $_SESSION['error'] = "An error occurred while retrieving schedule data.";
    $schedules = [];
}
?>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
</head>
<body>
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
    <br> <br>
      <!-- Display the currently selected quarter -->
      <h3><a href="quarters.php">Schedules for Quarter <?= htmlspecialchars($current_quarter); ?></a></h3> <!-- Updated text -->
      <table>
        <thead>
          <tr>
            <th>DATE</th>
            <th>TIME RANGE</th>
            <th>ROUTE DESCRIPTION</th>
            <th>DRIVER NAME</th>
            <th>TRUCK (Plate No.)</th>
            <th>WASTE TYPE / DAYS</th>
            <!-- <th>STATUS</th> -- REMOVED -->
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
                <td><?= htmlspecialchars($schedule['plate_number']) ?></td>
                <td><?= htmlspecialchars($schedule['waste_type']) ?></td>
                <?php
                // Removed the status display code here
                /*
                  $status_class = 'status-' . strtolower(htmlspecialchars($schedule['status']));
                  echo '<td><span class="' . $status_class . '">' . htmlspecialchars($schedule['status']) . '</span></td>';
                */
                ?>
                <td>
                  <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-edit"><i class="fa-solid fa-pen-to-square"></i></a>
                  <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?');"><i class="fa-solid fa-trash"></i></a>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="7" class="no-schedules">No schedules found for Quarter <?= htmlspecialchars($current_quarter); ?>.</td> <!-- Colspan changed from 8 to 7 -->
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</div>

 <!-- Logout Confirmation Modal -->
 <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 350px; text-align: center;">
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <button class="yes-btn" onclick="logout()">Yes</button>
            <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
        </div>
    </div>

  <script>
     function openLogoutModal() {
          document.getElementById("logoutModal").style.display = "flex";
      }

      function closeModal(modalId) {
          document.getElementById(modalId).style.display = "none";
      }

      function logout() {
          window.location.href = "sign_in.php"; // Redirect to sign-in page
      }

      // --- New Dropdown Logic ---
const settingsButton = document.getElementById('settings-button');
const settingsMenu = document.getElementById('settings-menu');

function closeDropdown() {
  if (settingsMenu) {
    settingsMenu.classList.remove('show');
    if (settingsButton) {
        settingsButton.setAttribute('aria-expanded', 'false');
    }
  }
}

if (settingsButton && settingsMenu) {
  settingsButton.addEventListener('click', function(event) {
    event.stopPropagation();
    const isExpanded = settingsMenu.classList.toggle('show');
    settingsButton.setAttribute('aria-expanded', isExpanded);
  });

  window.addEventListener('click', function(event) {
    if (settingsButton && settingsMenu && !settingsButton.contains(event.target) && !settingsMenu.contains(event.target)) {
      if (settingsMenu.classList.contains('show')) {
        closeDropdown();
      }
    }
  });

  window.addEventListener('keydown', function(event) {
      if (settingsMenu && event.key === 'Escape' && settingsMenu.classList.contains('show')) {
          closeDropdown();
      }
  });
} else {
    console.warn("Settings dropdown button or menu element not found. Dropdown functionality may be limited.");
}

    // --- START: Auto-hide flash messages ---
    document.addEventListener('DOMContentLoaded', (event) => {
        const flashMessages = document.querySelectorAll('.flash-message');

        flashMessages.forEach(message => {
            // Check if the message is currently visible by checking its height
            // We use offsetHeight > 0 because display: none results in offsetHeight = 0
            if (message.offsetHeight > 0) {
                 // Set a timeout to start the fade-out effect
                const fadeTimeout = setTimeout(() => {
                    message.style.opacity = '0'; // Start fading out
                    message.style.transition = 'opacity 0.5s ease-out'; // Apply smooth transition
                }, 5000); // Time before fading starts (5 seconds)

                // After the fade-out transition is complete, hide the element completely
                const hideTimeout = setTimeout(() => {
                    message.style.display = 'none';
                }, 5500); // Total time: 5s delay + 0.5s transition

                // Optional: Allow clicking the message to close it immediately
                message.addEventListener('click', () => {
                    clearTimeout(fadeTimeout); // Clear the fade timer
                    clearTimeout(hideTimeout); // Clear the hide timer
                    message.style.display = 'none'; // Hide immediately on click
                });
            }
        });
    });
    // --- END: Auto-hide flash messages ---

  </script>

<?php require_once 'templates/footer.php'; ?>
</body>
</html>