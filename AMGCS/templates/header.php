<?php
// Start session if needed (should be at the very top usually)
// session_start();

// --- Configuration and Page Variables ---
// Include database connection or other config if necessary
// require_once __DIR__ . '/../includes/config.php'; // Example path

$currentPage = basename($_SERVER['PHP_SELF']);

// Calculate active states (needed by sidebar, so define here)
$isFileManagement = in_array($currentPage, [
  'driver.php',
  'truck_list.php',
  'employees.php'
]);

$isMunicipality = in_array($currentPage, [
  'add_municipality.php',
  'municipality_list.php'
]);

// Define a default page title, can be overridden by individual pages
$pageTitle = isset($pageTitle) ? $pageTitle : "AMGCS"; // Use variable set before include

?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> - AMGCS</title> <!-- Use dynamic title -->
  <link rel="stylesheet" href="css/home.css"> <!-- Adjust path if needed -->
  <link rel="stylesheet" href="css/table.css">
  <link rel="stylesheet" href="css/addsched.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <!-- Add common CSS here (like the modal/dropdown styles from previous example) -->

<!-- DataTables CSS -->
<link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<!-- jQuery and DataTables JS -->
<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>


  <!-- Add page-specific CSS if needed (passed via variable) -->
  <?php if (isset($pageCSS) && is_array($pageCSS)): ?>
    <?php foreach ($pageCSS as $cssFile): ?>
      <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
    <?php endforeach; ?>
  <?php endif; ?>

</head>
<body>

<div class="header">
  <div class="logo">
    <img src="images/gso.png" alt="GSO Logo" />
    <div>
      <strong>AMGCS</strong><br/>
      <small>Advanced Monitoring Garbage Collection System</small>
    </div>
  </div>
  <div class="header-icons">
        <!-- Notification Dropdown -->
        <div class="notif-dropdown">
          <button id="notif-button" class="icon-trigger notif-trigger" title="Notifications" aria-label="Notifications menu" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-bell"></i>
            <!-- Badge -->
          </button>
          <div id="notif-menu" class="dropdown-content">
            <!-- Notification Content -->
             <div class="tabs">
                <button onclick="filterNotifications('all', event)" class="tab-button active" data-filter="all">All Unread</button>
                <button onclick="filterNotifications('system', event)" class="tab-button" data-filter="system">System Alerts</button>
                <button onclick="filterNotifications('garbage', event)" class="tab-button" data-filter="garbage">Garbage Collection Updates</button>
             </div>
             <div class="notification-container">
                <h2>Notifications</h2>
                <table id="notificationTableDropdownContainer"> <!-- Changed ID slightly -->
                  <thead><tr><th>Status</th><th>Date</th><th>Message</th></tr></thead>
                  <tbody id="notificationTableDropdown">
                    <!-- Notifications will be loaded here, perhaps via AJAX or PHP loop -->
                  </tbody>
                </table>
             </div>
          </div>
        </div>

        <!-- Settings Dropdown -->
        <div class="settings-dropdown">
          <button id="settings-button" class="icon-trigger settings-trigger" title="Settings" aria-label="Settings menu" aria-haspopup="true" aria-expanded="false">
            <i class="fas fa-gear"></i>
          </button>
          <div id="settings-menu" class="dropdown-content">
            <a href="#account-settings">Account</a>
            <hr>
            <a href="#" class="logout-btn" onclick="openLogoutModal(event)">Log Out</a>
          </div>
        </div>
    </div> <!-- End header-icons -->
</div> <!-- End .header -->

<!-- *** NOTE: Sidebar is NOT included here, but AFTER the header in the main page file *** -->
<!-- *** The main content div <div class="content"> starts in the specific page file *** -->