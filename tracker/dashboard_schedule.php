<?php
session_start();
require 'db_connect.php';

$current_quarter = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;
if ($current_quarter < 1 || $current_quarter > 4) {
    $current_quarter = 1;
    $_SESSION['selected_quarter'] = 1;
}

$pageTitle = "Schedule Management";
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

$sql = "SELECT
            s.schedule_id, s.date, s.start_time, s.end_time, 
            s.waste_type, s.days, 
            s.route_description, s.driver_name, t.truck_id,
            COALESCE(t.plate_number, 'N/A') AS plate_number
        FROM schedules s
        LEFT JOIN truck_info t ON s.truck_id = t.truck_id
        WHERE s.quarter = :current_quarter
        ORDER BY s.date DESC, s.start_time ASC";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':current_quarter', $current_quarter, PDO::PARAM_INT);
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

    <?php if (isset($_SESSION['message'])): ?>
        <div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <div class="btno-container">
      <a href="add_schedule.php" class="btn-add"> <i class="fa-solid fa-calendar-plus"></i> Add schedule </a>
    <br> <br>
      <h3><a href="quarters.php">Schedules for Quarter <?= htmlspecialchars($current_quarter); ?></a></h3>
      <table>
        <thead>
          <tr>
            <th>DATE</th>
            <th>TIME RANGE</th>
            <th>ROUTE DESCRIPTION</th>
            <th>DRIVER NAME</th>
            <th>TRUCK (Plate No.)</th>
            <th>WASTE TYPE</th>
            <th>DAYS</th>
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
                <td><?= htmlspecialchars($schedule['waste_type'] ?? 'N/A') ?></td>
                <td><?= htmlspecialchars($schedule['days'] ?? 'N/A') ?></td>
                <td>
                  <div class="action-buttons">
                    <a href="edit_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-edit" title="Edit"><i class="fa-solid fa-pen-to-square"></i></a>
                    <a href="delete_schedule.php?id=<?= $schedule['schedule_id'] ?>" class="btn btn-delete" onclick="return confirm('Are you sure?');" title="Delete"><i class="fa-solid fa-trash"></i></a>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          <?php else: ?>
            <tr>
              <td colspan="8" class="no-schedules">No schedules found for Quarter <?= htmlspecialchars($current_quarter); ?>.</td>
            </tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
</div>

 <!-- Logout Confirmation Modal and other scripts... -->
 <div id="logoutModal" class="modal">
    <div class="modal-content" style="max-width: 350px; text-align: center;">
        <h3>Confirm Logout</h3>
        <p>Are you sure you want to logout?</p>
        <button class="yes-btn" onclick="logout()">Yes</button>
        <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
    </div>
</div>

<script>
    function openLogoutModal() { document.getElementById("logoutModal").style.display = "flex"; }
    function closeModal(modalId) { document.getElementById(modalId).style.display = "none"; }
    function logout() { window.location.href = "sign_in.php"; }

    document.addEventListener('DOMContentLoaded', (event) => {
        const flashMessages = document.querySelectorAll('.flash-message');
        flashMessages.forEach(message => {
            if (message.offsetHeight > 0) {
                const fadeTimeout = setTimeout(() => {
                    message.style.opacity = '0';
                    message.style.transition = 'opacity 0.5s ease-out';
                }, 5000);
                const hideTimeout = setTimeout(() => {
                    message.style.display = 'none';
                }, 5500);
                message.addEventListener('click', () => {
                    clearTimeout(fadeTimeout);
                    clearTimeout(hideTimeout);
                    message.style.display = 'none';
                });
            }
        });
    });
</script>

<?php require_once 'templates/footer.php'; ?>
</body>
</html>
