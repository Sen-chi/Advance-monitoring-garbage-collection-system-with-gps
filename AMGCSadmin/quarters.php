<?php
session_start(); // Ensure session is started
require 'db_connect.php';

// Define the page title
$pageTitle = "Quarter Settings";

// Include templates
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
// require_once 'templates/footer.php'; // Include footer at the bottom

// Get the currently selected quarter from the session to highlight the active link (optional)
$current_quarter_in_session = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1; // Default to 1

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
     <link rel="stylesheet" href="css/style.css"> // Ensure these paths are correct
     <link rel="stylesheet" href="css/dashboard.css">
      <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
      <style>
          /* Basic styling for the quarter selection links/buttons */
          .quarter-selector-buttons a {
              display: inline-block; /* Makes them behave like buttons */
              margin: 5px;
              padding: 10px 15px;
              background-color: #007bff; /* Standard button blue */
              color: white;
              text-decoration: none; /* Remove underline */
              border-radius: 5px;
              font-weight: bold;
              transition: background-color 0.3s ease; /* Smooth hover effect */
          }
          .quarter-selector-buttons a:hover {
              background-color: #0056b3; /* Darker blue on hover */
          }
           /* Style for the currently active quarter button */
          .quarter-selector-buttons a.active {
              background-color: #28a745; /* Green to indicate active */
              cursor: default; /* No hover effect on active */
          }
           .quarter-selector-buttons a.active:hover {
              background-color: #28a745; /* Keep green on hover for active */
           }

           /* Style for the quarter date spans info */
           .quarter-info {
               margin-top: 20px;
               padding: 15px;
               border: 1px solid #ccc;
               border-radius: 5px;
               background-color: #f9f9f9;
           }
            .quarter-info h4 {
                margin-top: 0;
            }
      </style>
</head>
<body>
    <div class="content">
        <h2>Quarter Settings</h2>

        <!-- Flash Messages (Optional - copy from dashboard_schedule.php if needed) -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>


        <div class="settings-container">

            <p>Select a quarter to display schedules on the Dashboard:</p>

            <div class="quarter-selector-buttons">
                <!-- Links pointing to the new set_quarter.php script -->
                <a href="set_quarter.php?q=1" class="<?= ($current_quarter_in_session === 1) ? 'active' : '' ?>">1st Quarter</a>
                <a href="set_quarter.php?q=2" class="<?= ($current_quarter_in_session === 2) ? 'active' : '' ?>">2nd Quarter</a>
                <a href="set_quarter.php?q=3" class="<?= ($current_quarter_in_session === 3) ? 'active' : '' ?>">3rd Quarter</a>
                <a href="set_quarter.php?q=4" class="<?= ($current_quarter_in_session === 4) ? 'active' : '' ?>">4th Quarter</a>
            </div>

            <div class="quarter-info">
                 <h4>Quarter Date Spans:</h4>
                 <ul>
                     <li>1st Quarter: January - March</li>
                     <li>2nd Quarter: April - June</li>
                     <li>3rd Quarter: July - September</li>
                     <li>4th Quarter: October - December</li>
                 </ul>
                 <p>Schedules added via the Dashboard will be associated with the currently selected quarter.</p>
                 <!-- Removed the date inputs as they aren't used for dashboard filtering here -->
            </div>

        </div>

        <br>
        <p><a href="dashboard_schedule.php">Back to Schedule Dashboard</a></p>

    </div>

<?php require_once 'templates/footer.php'; ?> // Include footer after content
</body>
</html>