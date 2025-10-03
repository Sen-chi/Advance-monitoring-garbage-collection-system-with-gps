<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>AMGCS Dashboard</title>
  <style>
    html, body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100%;
    }

    .header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 70px;
      background-color: #1e1e1e;
      color: white;
      padding: 15px 30px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      z-index: 1000;
      box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
    }

    .logo {
      display: flex;
      align-items: center;
      gap: 15px;
    }

    .logo img {
      width: 40px;
      height: 40px;
    }

    .sidebar {
      position: fixed;
      top: 70px;
      left: 0;
      bottom: 0;
      width: 270px;
      background-color: #1e1e1e;
      color: white;
      padding-top: 30px;
      font-weight: bold;
    }

    .sidebar a {
      display: flex;
      align-items: center;
      text-decoration: none;
      color: white;
      padding: 18px 30px;
      font-size: 13px;
      letter-spacing: 1px;
      text-transform: uppercase;
    }

    .sidebar a:hover {
      background-color: #2c2c2c;
      border-radius: 5px;
    }

    .sidebar i {
      font-size: 18px;
      margin-right: 15px;
      width: 25px;
      text-align: center;
    }

    .sidebar a.active {
      background-color: #c5d7bd;
      color: black;
    }

    .content-wrapper {
      position: fixed;
      top: 70px;
      left: 270px;
      right: 0;
      bottom: 0;
      overflow-y: auto;
      background-color: #f8f8f8;
      display: flex;
      justify-content: center;
      align-items: center;
    }

    .logout-text {
      font-weight: bold;
      font-size: 16px;
      white-space: nowrap;
      margin-right: 50px;
    }

    .logout-text a {
      color: white;
      text-decoration: none;
      padding: 0;
    }

    .logout-text a:hover {
      text-decoration: underline;
    }

    .modal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      overflow: auto;
      background-color: rgba(0, 0, 0, 0.4);
    }

    .modal-content {
      background-color: #fff;
      margin: 15% auto;
      padding: 20px;
      border: 1px solid #888;
      width: 300px;
      text-align: center;
      border-radius: 5px;
    }

    .modal-content p {
      font-weight: bold;
      margin-bottom: 20px;
    }

    .modal-buttons {
      display: flex;
      justify-content: center;
      gap: 10px;
    }

    .modal-buttons a {
      padding: 8px 20px;
      color: white;
      text-decoration: none;
      border-radius: 3px;
      font-weight: bold;
    }

    .modal-buttons .yes {
      background-color: #194d0f;
    }

    .modal-buttons .no {
      background-color: #b32222;
    }
  </style>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" />
</head>
<body>
<div class="header">
    <div class="logo">
      <img src="images/gso.png" alt="Logo" />
      <div>
        <strong>AMGCS</strong><br />
        <small>Advanced Monitoring Garbage Collection System</small>
      </div>
    </div>
    <div><strong>Log Out</strong></div>
  </div>

  <div class="sidebar">
    <a href="dashboard.php"><i class="fas fa-table-columns"></i> DASHBOARD</a>
    <a href="dashboard_tracking.php"><i class="fas fa-truck"></i> GARBAGE TRUCK TRACKING</a>
    <a href="dashboard_schedule.php"><i class="fas fa-calendar-alt"></i> SCHEDULE MANAGEMENT</a>
    <a href="dashboard_notification.php"><i class="fas fa-bell"></i> NOTIFICATIONS</a>
    <a href="reports_and_logs.php"><i class="fas fa-file-alt"></i> REPORTS & LOGS</a>
    <a href="user_management.php"><i class="fas fa-users"></i> USER MANAGEMENT</a>  
    <a href="system_settings.php"><i class="fas fa-gear"></i> SYSTEM SETTINGS</a> 
  </div>

  <div id="logoutModal" class="modal">
    <div class="modal-content">
      <p>Are you sure you want to log out?</p>
      <div class="modal-buttons">
        <a href="logout.php" class="yes">YES</a>
        <a href="#" class="no" id="cancelLogout">NO</a>
      </div>
    </div>
  </div>

  <script>
    const logoutBtn = document.getElementById("logoutBtn");
    const logoutModal = document.getElementById("logoutModal");
    const cancelLogout = document.getElementById("cancelLogout");

    logoutBtn.onclick = function (event) {
      event.preventDefault();
      logoutModal.style.display = "block";
    };

    cancelLogout.onclick = function (event) {
      event.preventDefault();
      logoutModal.style.display = "none";
    };

    window.onclick = function (event) {
      if (event.target === logoutModal) {
        logoutModal.style.display = "none";
      }
    };
  </script>
</body>
</html>