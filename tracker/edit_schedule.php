<?php
// edit_schedule.php
session_start();
require 'db_connect.php';

// Validate Schedule ID
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    die("Error: Invalid or missing schedule ID.");
}
$schedule_id = intval($_GET['id']);

// --- DATA FETCHING ---
try {
    $stmt_fetch = $pdo->prepare("SELECT * FROM schedules WHERE schedule_id = :schedule_id");
    $stmt_fetch->execute([':schedule_id' => $schedule_id]);
    $schedule_data = $stmt_fetch->fetch(PDO::FETCH_ASSOC);
    if (!$schedule_data) {
        die("Error: Schedule with ID " . htmlspecialchars($schedule_id) . " not found.");
    }
} catch (\PDOException $e) { die("Database Error: " . $e->getMessage()); }

// Prepare arrays of selected items for the form
$selected_days = explode(',', $schedule_data['days'] ?? '');
$selected_routes = array_map('trim', explode(',', $schedule_data['route_description'] ?? ''));

// Fetch Trucks, Drivers, Assistants, and Routes (same as before)
try {
    $trucks = $pdo->query("SELECT ti.truck_id, ti.plate_number, td.driver_id FROM truck_info ti LEFT JOIN truck_driver td ON ti.truck_id = td.truck_id WHERE ti.availability_status IN ('Available', 'Assigned') ORDER BY ti.plate_number")->fetchAll(PDO::FETCH_ASSOC);
    $drivers = $pdo->query("SELECT driver_id, truck_id, CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name FROM truck_driver WHERE status = 'Active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
    $assistants = $pdo->query("SELECT assistant_id, CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name FROM truck_assistant WHERE status = 'Active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
    $routes = $pdo->query("SELECT route_id, CONCAT(origin, ' to ', destination) AS route_name FROM routes ORDER BY origin")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) { /* Handle errors gracefully */ $trucks = $drivers = $assistants = $routes = []; }

// Find the Driver ID from the saved Driver Name
$selected_driver_id = null;
foreach ($drivers as $driver) {
    if ($driver['formatted_name'] === $schedule_data['driver_name']) {
        $selected_driver_id = $driver['driver_id'];
        break;
    }
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
    /* Your CSS styles are fine, keeping them the same */
    .add-schedule-form { background-color: #fff; padding: 20px 30px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); max-width: 900px; margin: 20px auto; }
    .form-row { display: flex; flex-wrap: wrap; gap: 20px; margin-bottom: 20px; }
    .form-group { flex: 1; min-width: 200px; }
    .add-schedule-form label { display: block; margin-bottom: 5px; font-weight: bold; font-size: 14px; }
    .add-schedule-form input, .add-schedule-form select { width: 100%; padding: 8px 10px; border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box; font-size: 14px; }
    .form-actions { display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px; }
    .btn { padding: 10px 22px; border-radius: 5px; cursor: pointer; font-size: 14px; font-weight: bold; text-decoration: none; display: inline-block; text-align: center; }
    .btn-primary { background-color: #28a745; color: white; }
    .btn-secondary { background-color: #6c757d; color: white; }
    .multi-select-dropdown { position:relative; display:inline-block; width:100%; }
    .dropdown-toggle { width:100%; padding:8px 12px; border:1px solid #ccc; border-radius:4px; background-color:#fff; cursor:pointer; text-align:left; display:flex; justify-content:space-between; align-items:center; }
    .dropdown-toggle span.arrow { transition: transform 0.2s; }
    .multi-select-dropdown.active .dropdown-toggle span.arrow { transform:rotate(180deg); }
    .dropdown-menu { display:none; position:absolute; top:100%; left:0; width:100%; border:1px solid #ccc; border-radius:4px; background-color:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1); z-index:1000; max-height:200px; overflow-y:auto; }
    .dropdown-menu label { display:flex; align-items:center; padding:8px 12px; cursor:pointer; gap:8px; }
    .dropdown-menu input[type="checkbox"] { width:auto; margin:0; }
    .multi-select-dropdown.active .dropdown-menu { display:block; }
  </style>
</head>
<body>
<div class="content">
  <div class="add-schedule-form">
    <h2>Edit Schedule</h2>
    
    <form action="update_schedule_process.php" method="POST">
      <input type="hidden" name="schedule_id" value="<?= htmlspecialchars($schedule_data['schedule_id']); ?>" />
      
      <!-- All your form fields are correct, no changes needed here -->
      <div class="form-row">
        <div class="form-group"><label for="date">Date:</label><input type="date" id="date" name="date" value="<?= htmlspecialchars($schedule_data['date']); ?>" required /></div>
        <div class="form-group"><label for="start_time">Start Time:</label><input type="time" id="start_time" name="start_time" value="<?= htmlspecialchars($schedule_data['start_time']); ?>" required /></div>
        <div class="form-group"><label for="end_time">End Time:</label><input type="time" id="end_time" name="end_time" value="<?= htmlspecialchars($schedule_data['end_time']); ?>" required /></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="waste_type">Waste Type:</label>
          <select id="waste_type" name="waste_type" required>
            <option value="Biodegradable" <?= ($schedule_data['waste_type'] == 'Biodegradable') ? 'selected' : ''; ?>>Biodegradable</option>
            <option value="Non-Biodegradable" <?= ($schedule_data['waste_type'] == 'Non-Biodegradable') ? 'selected' : ''; ?>>Non-Biodegradable</option>
            <option value="Recyclable" <?= ($schedule_data['waste_type'] == 'Recyclable') ? 'selected' : ''; ?>>Recyclable</option>
            <option value="Residual" <?= ($schedule_data['waste_type'] == 'Residual') ? 'selected' : ''; ?>>Residual</option>
          </select>
        </div>
        <div class="form-group">
            <label>Days:</label>
            <div class="multi-select-dropdown">
                <button type="button" class="dropdown-toggle" data-placeholder="Select Days">Select Days <span class="arrow">&#9660;</span></button>
                <div class="dropdown-menu">
                    <?php
                    $all_days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                    foreach ($all_days as $day) {
                        $checked = in_array($day, $selected_days) ? 'checked' : '';
                        echo "<label><input type='checkbox' name='days[]' value='{$day}' {$checked}> {$day}</label>";
                    }
                    ?>
                </div>
            </div>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label for="driver_id">Driver Name:</label>
          <select id="driver_id" name="driver_id" required>
            <option value="">-- Select Driver --</option>
            <?php foreach ($drivers as $driver): ?>
              <option value="<?= htmlspecialchars($driver['driver_id']); ?>" data-truck-id="<?= htmlspecialchars($driver['truck_id'] ?? ''); ?>" <?= ($selected_driver_id == $driver['driver_id']) ? 'selected' : ''; ?>>
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
              <option value="<?= htmlspecialchars($truck['truck_id']); ?>" data-driver-id="<?= htmlspecialchars($truck['driver_id'] ?? ''); ?>" <?= ($schedule_data['truck_id'] == $truck['truck_id']) ? 'selected' : ''; ?>>
                <?= htmlspecialchars($truck['plate_number']); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
            <label for="assistant_id">Assistant Name:</label>
            <select id="assistant_id" name="assistant_id">
                <option value="">-- Optional: Select Assistant --</option>
                <?php foreach($assistants as $assistant): ?>
                <option value="<?= htmlspecialchars($assistant['assistant_id']); ?>" <?= ($schedule_data['assistant_id'] == $assistant['assistant_id']) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($assistant['formatted_name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="form-group">
            <label>Route Description:</label>
            <div class="multi-select-dropdown">
                <button type="button" class="dropdown-toggle" data-placeholder="Select Route(s)">Select Route(s) <span class="arrow">&#9660;</span></button>
                <div class="dropdown-menu">
                    <?php foreach($routes as $route): 
                        $checked = in_array($route['route_name'], $selected_routes) ? 'checked' : '';
                    ?>
                    <label><input type="checkbox" name="route_description[]" value="<?= htmlspecialchars($route['route_name']); ?>" <?= $checked ?>> <?= htmlspecialchars($route['route_name']); ?></label>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
      </div>

      <div class="form-actions">
        <a href="dashboard_schedule.php" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-primary">Update Schedule</button>
      </div>
    </form>
  </div>
</div>

<!-- FIX: ADDED THE FULL JAVASCRIPT BLOCK -->
<script>
// SCRIPT FOR DYNAMIC DRIVER/TRUCK PAIRING
document.addEventListener('DOMContentLoaded', function() {
    const driverSelect = document.getElementById('driver_id');
    const truckSelect = document.getElementById('truck_id');
    if (driverSelect && truckSelect) {
        driverSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const truckId = selectedOption.getAttribute('data-truck-id');
            if (truckId) {
                truckSelect.value = truckId;
            }
        });
        truckSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const driverId = selectedOption.getAttribute('data-driver-id');
            if (driverId) {
                driverSelect.value = driverId;
            }
        });
    }
});

// SCRIPT FOR ALL MULTI-SELECT DROPDOWNS
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.multi-select-dropdown').forEach(dropdown => {
        const toggle = dropdown.querySelector('.dropdown-toggle');
        const checkboxes = dropdown.querySelectorAll('input[type="checkbox"]');
        const placeholder = toggle.getAttribute('data-placeholder');

        toggle.addEventListener('click', () => dropdown.classList.toggle('active'));

        const updateToggleText = () => {
            const selected = Array.from(checkboxes)
                                .filter(cb => cb.checked)
                                .map(cb => cb.parentElement.textContent.trim());
            
            if (selected.length === 0) {
                toggle.innerHTML = `${placeholder} <span class="arrow">&#9660;</span>`;
            } else if (selected.length > 2) {
                 toggle.innerHTML = `${selected.length} items selected <span class="arrow">&#9660;</span>`;
            } else {
                toggle.innerHTML = selected.join(', ') + ' <span class="arrow">&#9660;</span>';
            }
        };
        checkboxes.forEach(cb => cb.addEventListener('change', updateToggleText));
        updateToggleText(); // Initial call to set text on page load
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.multi-select-dropdown').forEach(dropdown => {
            if (!dropdown.contains(event.target)) {
                dropdown.classList.remove('active');
            }
        });
    });
});
</script>

<?php require_once 'templates/footer.php'; ?>
</body>
</html>