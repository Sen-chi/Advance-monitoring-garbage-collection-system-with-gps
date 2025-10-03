<?php
session_start();
require 'db_connect.php';


if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid or missing schedule ID.");
}
$schedule_id = intval($_GET['id']);

$sql_fetch_schedule = "SELECT * FROM schedules WHERE schedule_id = :schedule_id";
try {
    $stmt_fetch = $pdo->prepare($sql_fetch_schedule);
    $stmt_fetch->bindParam(':schedule_id', $schedule_id, PDO::PARAM_INT);
    $stmt_fetch->execute();
    $schedule_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);

    if (!$schedule_data) {
        die("Error: Schedule with ID " . htmlspecialchars($schedule_id) . " not found.");
    }
} catch (\PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Extract selected days from schedule_data
$selected_days = explode(',', $schedule_data['days']); // Assuming 'days' column stores comma-separated days

// Trucks
try {
    $trucks = $pdo->query("SELECT truck_id, plate_number FROM truck_info ORDER BY plate_number")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $trucks = [];
}

// Drivers
try {
    $query = "SELECT driver_id, CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name 
              FROM truck_driver ORDER BY last_name, first_name";
    $drivers = $pdo->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $drivers = [];
}

$pageTitle = "Edit Schedule";
require_once 'templates/header.php';
require_once 'templates/sidebar.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title><?= htmlspecialchars($pageTitle) ?></title>
  <link rel="stylesheet" href="css/style.css">
  <link rel="stylesheet" href="css/dashboard.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
  
  <style>
    .add-schedule-form { background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 900px; margin: 20px auto; }
    .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
    .form-group { flex: 1; min-width: 200px; }
    .add-schedule-form label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
    .add-schedule-form input, .add-schedule-form select, .add-schedule-form textarea { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
    .btn { padding: 10px 22px; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; text-align: center; letter-spacing: 0.5px; border: 1px solid rgba(0,0,0,0.2); box-shadow: 0 3px 5px rgba(0,0,0,0.15); transition: all 0.2s ease-in-out; }
    .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 10px rgba(0,0,0,0.2); }
    .btn:active { transform: translateY(1px); box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
    .btn-primary { background-color: #28a745; color: white; border-color: #1c7430; }
    .btn-primary:hover { background-color: #2dab4f; }
    .btn-secondary { background-color: #6c757d; color: white; border-color: #545b62; }
    .btn-secondary:hover { background-color: #7a838b; }
    .flash-error { padding: 15px; margin-bottom: 20px; border-radius: 4px; background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
    @media (max-width: 768px) { .form-row { flex-direction: column; gap: 0; } .form-group { margin-bottom: 15px; } .form-actions { flex-direction: column-reverse; align-items: stretch; } }

    .multi-select-dropdown {
            position: relative;
            display: inline-block;
            width: 100%; /* Adjust to fit form-group */
            box-sizing: border-box; /* Ensure padding and border are included in the width */
        }

        .dropdown-toggle {
            width: 100%;
            padding: 8px 12px;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            cursor: pointer;
            text-align: left;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box; /* Important for width calculation */
        }

        .dropdown-toggle span.arrow {
            font-size: 0.7em;
            margin-left: 8px;
            transition: transform 0.2s;
        }

        .multi-select-dropdown.active .dropdown-toggle span.arrow {
            transform: rotate(180deg);
        }

        .dropdown-menu {
            display: none;
            position: absolute;
            top: 100%; /* Position below the toggle button */
            left: 0;
            width: 100%;
            border: 1px solid #ccc;
            border-radius: 4px;
            background-color: #fff;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            z-index: 1000;
            max-height: 200px; /* Make it scrollable if too many options */
            overflow-y: auto;
            box-sizing: border-box; /* Important for width calculation */
        }

        .dropdown-menu label {
            display: flex; /* Use flexbox for alignment */
            align-items: center; /* Vertically center checkbox and text */
            padding: 8px 12px;
            cursor: pointer;
            white-space: nowrap;
            gap: 8px; /* Space between checkbox and text */
        }

        .dropdown-menu label:hover {
            background-color: #f5f5f5;
        }

        .dropdown-menu input[type="checkbox"] {
            /* Reset specific input styles if needed, ensuring it's not full width */
            width: auto;
            margin: 0; /* Remove default margin */
        }

        /* Show the dropdown menu when the parent is active */
        .multi-select-dropdown.active .dropdown-menu {
            display: block;
        }
  </style>
</head>
<body>
<div class="content">
  <div class="add-schedule-form">
    <h2>Edit Schedule</h2>

    <?php if (isset($_SESSION['error'])): ?>
      <div class="flash-error"><?= nl2br(htmlspecialchars($_SESSION['error'])); unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="update_schedule_process.php" method="POST">
      <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule_data['schedule_id']); ?>" />

      <!-- ROW 1: Date, Start Time, End Time -->
      <div class="form-row">
        <div class="form-group">
          <label for="date">Date:</label>
          <input type="date" id="date" name="date" value="<?= htmlspecialchars($schedule_data['date']); ?>" required />
        </div>
        <div class="form-group">
          <label for="start_time">Start Time:</label>
          <input type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($schedule_data['start_time']); ?>" required />
        </div>
        <div class="form-group">
          <label for="end_time">End Time:</label>
          <input type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($schedule_data['end_time']); ?>" required />
        </div>
      </div>

      <!-- ROW 2: Waste Type, Days, Driver, Truck -->
      <div class="form-row">
        <div class="form-group">
          <label for="waste_type">Waste Type:</label>
          <select id="waste_type" name="waste_type" required>
            <option value="">-- Select Waste Type --</option>
            <option value="Biodegradable" <?= ($schedule_data['waste_type'] == 'Biodegradable') ? 'selected' : ''; ?>>Biodegradable</option>
            <option value="Non-Biodegradable" <?= ($schedule_data['waste_type'] == 'Non-Biodegradable') ? 'selected' : ''; ?>>Non-Biodegradable</option>
            <option value="Recyclable" <?= ($schedule_data['waste_type'] == 'Recyclable') ? 'selected' : ''; ?>>Recyclable</option>
            <option value="Residual" <?= ($schedule_data['waste_type'] == 'Residual') ? 'selected' : ''; ?>>Residual</option>
          </select>
        </div>

        <div class="form-group">
            <label for="days-select-toggle">Days:</label>
            <div class="multi-select-dropdown">
                <button type="button" class="dropdown-toggle" id="days-select-toggle">Select Days <span class="arrow">&#9660;</span></button>
                <div class="dropdown-menu">
                    <?php
                    $all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($all_days as $day) {
                        $checked = in_array($day, $selected_days) ? 'checked' : '';
                        echo "<label><input type=\"checkbox\" name=\"days[]\" value=\"{$day}\" {$checked}> {$day}</label>";
                    }
                    ?>
                </div>
            </div>
        </div>

        <div class="form-group">
          <label for="driver_name">Driver Name:</label>
          <select id="driver_name" name="driver_name" required>
            <option value="">-- Select Driver --</option>
            <?php foreach ($drivers as $driver): ?>
              <option value="<?= htmlspecialchars($driver['formatted_name']); ?>" <?= ($schedule_data['driver_name'] == $driver['formatted_name']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($driver['formatted_name']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="form-group">
          <label for="truck_id">Truck (Plate Number):</label>
          <select id="truck_id" name="truck_id" required>
            <option value="">-- Select Truck --</option>
            <?php foreach ($trucks as $truck): ?>
              <option value="<?= htmlspecialchars($truck['truck_id']); ?>" <?= ($schedule_data['truck_id'] == $truck['truck_id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($truck['plate_number']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <!-- ROW 3: Route Description -->
      <div class="form-row">
        <div class="form-group" style="flex: 2;">
          <label for="route_description">Route Description:</label>
          <textarea id="route_description" name="route_description" rows="3" required><?= htmlspecialchars($schedule_data['route_description']); ?></textarea>
        </div>
      </div>

      <!-- ROW 4: Buttons -->
      <div class="form-actions">
        <a href="dashboard_schedule.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Schedule</button>
      </div>
    </form>
  </div>
</div>

<script>
        document.addEventListener('DOMContentLoaded', function() {
            const dropdownToggle = document.getElementById('days-select-toggle');
            const multiSelectDropdown = dropdownToggle.closest('.multi-select-dropdown');
            const dropdownMenu = multiSelectDropdown.querySelector('.dropdown-menu');
            const checkboxes = dropdownMenu.querySelectorAll('input[type="checkbox"]');
            const dropdownArrow = dropdownToggle.querySelector('.arrow');

            if (dropdownToggle && multiSelectDropdown && dropdownMenu && checkboxes.length > 0 && dropdownArrow) {
                dropdownToggle.addEventListener('click', function() {
                    multiSelectDropdown.classList.toggle('active');
                });

                // Close the dropdown if clicking outside
                document.addEventListener('click', function(event) {
                    if (!multiSelectDropdown.contains(event.target)) {
                        multiSelectDropdown.classList.remove('active');
                    }
                });

                // Update the button text with selected days
                function updateDropdownText() {
                    const selectedDays = Array.from(checkboxes)
                                            .filter(cb => cb.checked)
                                            .map(cb => cb.parentNode.textContent.trim()); // Get text content of the label

                    if (selectedDays.length === 0) {
                        dropdownToggle.innerHTML = 'Select Days <span class="arrow">&#9660;</span>';
                    } else {
                        dropdownToggle.innerHTML = selectedDays.join(', ') + ' <span class="arrow">&#9660;</span>';
                    }
                }

                checkboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateDropdownText);
                });

                // Initial update on load
                updateDropdownText();
            } else {
                console.warn("Multi-select dropdown elements not found. Check IDs and structure.");
            }
        });
    </script>
<?php require_once 'templates/footer.php'; ?>
</body>
</html>