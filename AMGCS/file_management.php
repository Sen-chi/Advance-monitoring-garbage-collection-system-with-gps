<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>File Management - AMGCS</title>
  <link rel="stylesheet" href="css/home.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<div class="layout">

  <!-- HEADER -->
  <div class="header">
    <div class="logo">
      <img src="images/gso.png" alt="Logo" />
      <div>
        <strong>AMGCS</strong><br/>
        <small>Advanced Monitoring Garbage Collection System</small>
      </div>
    </div>

    <div class="header-icons">
      <div class="notif-dropdown">
        <button id="notif-button" class="icon-trigger" title="Notifications"><i class="fas fa-bell"></i></button>
        <div id="notif-menu" class="dropdown-content">
          <div class="tabs">
            <button onclick="filterNotifications('all', event)" class="active">All Unread</button>
            <button onclick="filterNotifications('system', event)">System Alerts</button>
            <button onclick="filterNotifications('garbage', event)">Garbage Updates</button>
          </div>
          <div class="notification-container">
            <h2>Notifications</h2>
            <table>
              <thead><tr><th>Status</th><th>Date</th><th>Message</th></tr></thead>
              <tbody id="notificationTableDropdown">
                <tr class="new"><td class="status-new">● New</td><td>2025-03-22</td><td>Truck has stopped at Brgy. 1</td></tr>
                <tr class="new"><td class="status-new">● New</td><td>2025-03-22</td><td>Delay: Truck 1 stuck in traffic</td></tr>
                <tr class="read"><td class="status-read">Read</td><td>2025-03-21</td><td>Collection Completed in Brgy. 2</td></tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="settings-dropdown">
        <button id="settings-button" class="icon-trigger" title="Settings"><i class="fas fa-gear"></i></button>
        <div id="settings-menu" class="dropdown-content">
          <a href="#">Account</a>
          <hr>
          <a class="logout-btn" onclick="openLogoutModal()">Log Out</a>
        </div>
      </div>
    </div>
  </div>

  <!-- SIDEBAR -->
  <div class="sidebar">
    <a href="dashboard.php" class="<?= $currentPage === 'dashboard.php' ? 'active' : '' ?>"><i class="fas fa-table-columns"></i> Dashboard</a>
    <a href="dashboard_tracking.php" class="<?= $currentPage === 'dashboard_tracking.php' ? 'active' : '' ?>"><i class="fas fa-truck"></i> Garbage Truck Tracking</a>
    <a href="dashboard_schedule.php" class="<?= $currentPage === 'dashboard_schedule.php' ? 'active' : '' ?>"><i class="fas fa-calendar-alt"></i> Schedule Management</a>
    <a href="reports_and_logs.php" class="<?= $currentPage === 'reports_and_logs.php' ? 'active' : '' ?>"><i class="fas fa-file-alt"></i> Reports</a>
    <a href="user_management.php" class="<?= $currentPage === 'user_management.php' ? 'active' : '' ?>"><i class="fas fa-users"></i> User Management</a>
    <a href="file_management.php" class="active"><i class="fas fa-file-pen"></i> File Management</a>
  </div>

  <!-- MAIN CONTENT -->
  <div class="content">
    <h2>File Management</h2>
    <div class="box-container">
      <table>
        <thead>
          <tr>
            <th>Document Name</th>
            <th>Category</th>
            <th>Linked To</th>
            <th>Uploaded Date</th>
            <th>Status</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>Request_Form_Brgy1.pdf</td>
            <td>Dumping Request</td>
            <td>Request #1123</td>
            <td>April 15, 2025</td>
            <td><span style="color:green; font-weight:bold;">Approved</span></td>
          </tr>
          <tr>
            <td>Collection_Report_April2025.pdf</td>
            <td>Schedule Report</td>
            <td>Schedule ID 320</td>
            <td>April 14, 2025</td>
            <td><span style="color:blue;">Archived</span></td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- LOGOUT MODAL -->
<div id="logoutModal" class="modal">
  <div class="modal-content">
    <p>Are you sure you want to logout?</p>
    <button class="yes-btn" onclick="logout()">Yes</button>
    <button class="cancel-btn" onclick="closeModal()">Cancel</button>
  </div>
</div>

<!-- JS -->
<script>
  const notifButton = document.getElementById("notif-button");
  const notifMenu = document.getElementById("notif-menu");
  const settingsButton = document.getElementById("settings-button");
  const settingsMenu = document.getElementById("settings-menu");

  function openLogoutModal() {
    document.getElementById("logoutModal").style.display = "flex";
  }

  function closeModal() {
    document.getElementById("logoutModal").style.display = "none";
  }

  function logout() {
    window.location.href = "sign_in.php";
  }

  notifButton?.addEventListener("click", function (e) {
    e.stopPropagation();
    notifMenu.classList.toggle("show");
    settingsMenu.classList.remove("show");
  });

  settingsButton?.addEventListener("click", function (e) {
    e.stopPropagation();
    settingsMenu.classList.toggle("show");
    notifMenu.classList.remove("show");
  });

  window.addEventListener("click", function (event) {
    if (!notifButton.contains(event.target)) notifMenu.classList.remove("show");
    if (!settingsButton.contains(event.target)) settingsMenu.classList.remove("show");
  });

  window.addEventListener("keydown", function (event) {
    if (event.key === "Escape") {
      notifMenu.classList.remove("show");
      settingsMenu.classList.remove("show");
    }
  });

  function filterNotifications(type, event) {
    const rows = document.querySelectorAll("#notificationTableDropdown tr");
    rows.forEach(row => row.style.display = "table-row");

    if (type === "system") {
      rows.forEach(row => {
        if (!row.textContent.includes("Truck")) row.style.display = "none";
      });
    } else if (type === "garbage") {
      rows.forEach(row => {
        if (!row.textContent.includes("Collection") && !row.textContent.includes("SMS Alert")) {
          row.style.display = "none";
        }
      });
    }

    const buttons = document.querySelectorAll("#notif-menu .tabs button");
    buttons.forEach(btn => btn.classList.remove("active"));
    if (event && event.target.tagName === "BUTTON") {
      event.target.classList.add("active");
    }
  }
</script>
</body>
</html>
