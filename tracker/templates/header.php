<?php
// FILE: tracker/templates/header.php
$currentPage = basename($_SERVER['PHP_SELF']);

$isFileManagement = in_array($currentPage, ['driver.php', 'truck_list.php', 'employees.php','assistant.php','route.php']);
$isMunicipality = in_array($currentPage, ['municipality_list.php']);

$pageTitle = isset($pageTitle) ? $pageTitle : "AMGCS";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?> - AMGCS</title> 
  <link rel="stylesheet" href="css/home.css"> 
  <link rel="stylesheet" href="css/table.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
  <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
  <script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>
  <?php if (isset($pageCSS) && is_array($pageCSS)): ?>
    <?php foreach ($pageCSS as $cssFile): ?>
      <link rel="stylesheet" href="<?php echo htmlspecialchars($cssFile); ?>">
    <?php endforeach; ?>
  <?php endif; ?>

  <!-- Styling for the notification dropdown and counter -->
  <style>
    .notif-dropdown .icon-trigger {
        position: relative;
    }
    .notification-counter {
        position: absolute;
        top: -5px;
        right: -5px;
        background-color: red;
        color: white;
        border-radius: 50%;
        padding: 2px 6px;
        font-size: 10px;
        font-weight: bold;
        display: none; /* Hidden by default */
    }
    #notification-list {
        list-style: none;
        padding: 0;
        margin: 0;
        max-height: 300px;
        overflow-y: auto;
    }
    #notification-list li {
        padding: 10px 15px;
        border-bottom: 1px solid #eee;
        display: flex;
        align-items: center;
    }
    #notification-list li:last-child {
        border-bottom: none;
    }
    .notif-icon {
        margin-right: 15px;
        color: #28a745; /* Green for completed tasks */
        font-size: 1.2em;
    }
    .notif-content p {
        margin: 0;
        font-size: 14px;
        font-weight: bold;
    }
    .notif-content small {
        color: #666;
        display: block;
        font-size: 12px;
    }
    .no-notifications {
        text-align: center;
        color: #888;
        padding: 20px;
    }
  </style>

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

    <div class="user-profile">
      <i class="fas fa-user-circle"></i>
      <span>
        <?php 
          if (isset($_SESSION['username'])) {
              echo htmlspecialchars($_SESSION['username']);
          } else {
              echo 'Guest';
          }
        ?>
      </span>
    </div>

    <div class="notif-dropdown">
      <button id="notif-button" class="icon-trigger notif-trigger" title="Notifications">
        <i class="fas fa-bell"></i>
        <span id="notif-counter" class="notification-counter"></span>
      </button>
      <div id="notif-menu" class="dropdown-content">
         <div class="notification-container">
            <h2>Notifications</h2>
            <ul id="notification-list">
                <li class="no-notifications">No new notifications.</li>
            </ul>
         </div>
      </div>
    </div>
    
    <div class="settings-dropdown">
      <button id="settings-button" class="icon-trigger settings-trigger" title="Settings">
        <i class="fas fa-gear"></i>
      </button>
      <div id="settings-menu" class="dropdown-content">
        <a href="#account-settings">Account</a>
        <hr>
        <a href="#" class="logout-trigger">Log Out</a>
      </div>
    </div>
  </div>
</div>

<!-- JAVASCRIPT FOR REAL-TIME NOTIFICATIONS -->
<script>
$(document).ready(function() {
    const notifCounter = $('#notif-counter');
    const notifList = $('#notification-list');
    let currentNotifCount = 0;

    function fetchNotifications() {
        // We use a GET request to fetch the notification data.
        $.ajax({
            url: 'get_admin_notifications.php', // The new PHP file in the 'tracker' folder
            type: 'GET',
            dataType: 'json',
            success: function(response) {
                if (response.success && response.notifications.length > 0) {
                    
                    // Update the red counter badge
                    currentNotifCount += response.notifications.length;
                    notifCounter.text(currentNotifCount).show();

                    // Remove the "No new notifications" message
                    notifList.find('.no-notifications').remove();

                    // Add each new notification to the top of the dropdown list
                    response.notifications.forEach(function(notif) {
                        const notifHtml = `
                            <li>
                                <div class="notif-icon"><i class="fas fa-check-circle"></i></div>
                                <div class="notif-content">
                                    <p>Task Completed</p>
                                    <small>${notif.route_description} by ${notif.driver_name}</small>
                                </div>
                            </li>`;
                        notifList.prepend(notifHtml); // prepend adds it to the top
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error("Failed to fetch notifications:", error);
            }
        });
    }

    // When the admin clicks the bell, hide the counter as they have "seen" them.
    $('#notif-button').on('click', function() {
        currentNotifCount = 0;
        notifCounter.hide();
    });

    // Check for new notifications every 15 seconds (15000 milliseconds).
    setInterval(fetchNotifications, 15000);

    // Also check once as soon as the page loads.
    fetchNotifications();
});
</script>

</body>
</html>