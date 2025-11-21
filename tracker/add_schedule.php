<?php
// add_schedule.php
session_start();
require 'db_connect.php';

// --- POST REQUEST HANDLING (SERVER-SIDE LOGIC) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve POST data
    $date = $_POST['date'] ?? null;
    $startTime = $_POST['start_time'] ?? null;
    $endTime = $_POST['end_time'] ?? null;
    $wasteType = $_POST['waste_type'] ?? null;
    $days = $_POST['days'] ?? []; 
    $routeDescriptionArray = $_POST['route_description'] ?? [];
    $driverId = filter_input(INPUT_POST, 'driver_id', FILTER_VALIDATE_INT);
    $assistantId = filter_input(INPUT_POST, 'assistant_id', FILTER_VALIDATE_INT);
    $truckId = filter_input(INPUT_POST, 'truck_id', FILTER_VALIDATE_INT);

    // Basic validation
    if (empty($date) || empty($startTime) || empty($endTime) || empty($wasteType) || empty($days) || empty($routeDescriptionArray) || empty($driverId) || empty($truckId)) {
        $_SESSION['error'] = "Error: Please fill all required fields.";
        header("Location: add_schedule.php");
        exit();
    }
    
    $assistantId = $assistantId ?: null;
    $daysString = implode(',', $days);
    $routeDescriptionString = implode(', ', $routeDescriptionArray);

    $driverName = 'Unknown Driver';
    try {
        $stmtDriver = $pdo->prepare("SELECT CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS full_name FROM truck_driver WHERE driver_id = :driver_id");
        $stmtDriver->execute([':driver_id' => $driverId]);
        $driverResult = $stmtDriver->fetch(PDO::FETCH_ASSOC);
        if ($driverResult) $driverName = $driverResult['full_name'];
    } catch (\PDOException $e) { 
        error_log("Error getting driver name: " . $e->getMessage()); 
    }
    
    $quarter_to_add = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;

    // --- FIX: The SQL now includes driver_id ---
    $sql = "INSERT INTO schedules (date, start_time, end_time, waste_type, days, route_description, driver_name, driver_id, assistant_id, truck_id, quarter)
            VALUES (:date, :start_time, :end_time, :waste_type, :days, :route_description, :driver_name, :driver_id, :assistant_id, :truck_id, :quarter)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            ':date' => $date,
            ':start_time' => $startTime,
            ':end_time' => $endTime,
            ':waste_type' => $wasteType,
            ':days' => $daysString,
            ':route_description' => $routeDescriptionString,
            ':driver_name' => $driverName,
            ':driver_id' => $driverId, // Saving the driver's ID
            ':assistant_id' => $assistantId,
            ':truck_id' => $truckId,
            ':quarter' => $quarter_to_add
        ]);

        // --- NOTIFICATION LOGIC ---
        $userIds = [];
        // Find user_id for the driver
        if (!empty($driverId)) {
            $userStmt = $pdo->prepare("SELECT user_id FROM truck_driver WHERE driver_id = ? AND user_id IS NOT NULL");
            $userStmt->execute([$driverId]);
            $driverUser = $userStmt->fetch();
            if ($driverUser) $userIds[] = $driverUser['user_id'];
        }
        // Find user_id for the assistant
        if (!empty($assistantId)) {
            $userStmt = $pdo->prepare("SELECT user_id FROM truck_assistant WHERE assistant_id = ? AND user_id IS NOT NULL");
            $userStmt->execute([$assistantId]);
            $assistantUser = $userStmt->fetch();
            if ($assistantUser) $userIds[] = $assistantUser['user_id'];
        }

        // Insert a notification row for each person
        foreach (array_unique($userIds) as $userId) {
            $message = "You have a new schedule: $routeDescriptionString";
            $notifStmt = $pdo->prepare("INSERT INTO notifications (user_id, message, type) VALUES (?, ?, 'created')");
            $notifStmt->execute([$userId, $message]);
        }
        // --- END NOTIFICATION LOGIC ---

        $_SESSION['message'] = "Schedule added successfully!";
        header("Location: dashboard_schedule.php");
        exit();
    } catch (\PDOException $e) {
        error_log("Error adding schedule: " . $e->getMessage());
        $_SESSION['error'] = "Database error: Couldn't save schedule. " . $e->getMessage();
        header("Location: add_schedule.php");
        exit();
    }
}

$pageTitle = "Add New Schedule";
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

// --- DATA FETCHING FOR THE FORM ---
$trucks = $drivers = $assistants = $routes = [];
try {
    $trucks = $pdo->query("SELECT ti.truck_id, ti.plate_number, td.driver_id FROM truck_info ti LEFT JOIN truck_driver td ON ti.truck_id = td.truck_id WHERE ti.availability_status IN ('Available', 'Assigned') ORDER BY ti.plate_number")->fetchAll(PDO::FETCH_ASSOC);
    $drivers = $pdo->query("SELECT driver_id, truck_id, CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name FROM truck_driver WHERE status = 'Active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
    $assistants = $pdo->query("SELECT assistant_id, CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name FROM truck_assistant WHERE status = 'Active' ORDER BY last_name, first_name")->fetchAll(PDO::FETCH_ASSOC);
    $routes = $pdo->query("SELECT route_id, CONCAT(origin, ' to ', destination) AS route_name FROM routes ORDER BY origin")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) { die("Database Error: " . $e->getMessage()); }

$current_quarter_in_session = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        .add-schedule-form { background-color:#fff; padding:20px 30px; border-radius:8px; box-shadow:0 2px 4px rgba(0,0,0,0.1); max-width:900px; margin:20px auto; }
        .form-row { display:flex; flex-wrap:wrap; gap:20px; margin-bottom:20px; }
        .form-group { flex:1; min-width:200px; }
        .add-schedule-form label { display:block; margin-bottom:5px; font-weight:bold; font-size:14px; }
        .add-schedule-form input, .add-schedule-form select, .add-schedule-form textarea { width:100%; padding:8px 10px; border:1px solid #ccc; border-radius:4px; box-sizing:border-box; font-size:14px; }
        .form-actions { display:flex; justify-content:flex-end; gap:12px; margin-top:20px; }
        .btn { padding:10px 22px; border-radius:5px; cursor:pointer; font-size:14px; font-weight:bold; text-decoration:none; display:inline-block; text-align:center; letter-spacing:0.5px; border:1px solid rgba(0,0,0,0.2); box-shadow:0 3px 5px rgba(0,0,0,0.15); transition: all 0.2s ease-in-out; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 10px rgba(0,0,0,0.2); }
        .btn:active { transform: translateY(1px); box-shadow: 0 1px 2px rgba(0,0,0,0.2); }
        .btn-primary { background-color:#28a745; color:white; border-color:#1c7430; }
        .btn-secondary { background-color:#6c757d; color:white; border-color:#545b62; }
        .multi-select-dropdown { position:relative; display:inline-block; width:100%; box-sizing:border-box; }
        .dropdown-toggle { width:100%; padding:8px 12px; border:1px solid #ccc; border-radius:4px; background-color:#fff; cursor:pointer; text-align:left; display:flex; justify-content:space-between; align-items:center; }
        .dropdown-toggle span.arrow { font-size:0.7em; margin-left:8px; transition: transform 0.2s; }
        .multi-select-dropdown.active .dropdown-toggle span.arrow { transform:rotate(180deg); }
        .dropdown-menu { display:none; position:absolute; top:100%; left:0; width:100%; border:1px solid #ccc; border-radius:4px; background-color:#fff; box-shadow:0 2px 5px rgba(0,0,0,0.1); z-index:1000; max-height:200px; overflow-y:auto; }
        .dropdown-menu label { display:flex; align-items:center; padding:8px 12px; cursor:pointer; gap:8px; }
        .dropdown-menu label:hover { background-color:#f5f5f5; }
        .dropdown-menu input[type="checkbox"] { width:auto; margin:0; }
        .multi-select-dropdown.active .dropdown-menu { display:block; }
    </style>
</head>
<body>
<div class="content">
    <h2>Add New Schedule</h2>

    <?php if(isset($_SESSION['message'])): ?><div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div><?php endif; ?>
    <?php if(isset($_SESSION['error'])): ?><div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div><?php endif; ?>

    <p class="info-text">Adding schedule for <strong>Quarter <?= htmlspecialchars($current_quarter_in_session); ?></strong>.</p>

    <div class="add-schedule-form">
        <form action="add_schedule.php" method="POST">
            <div class="form-row">
                <div class="form-group"><label for="date">Date:</label><input type="date" id="date" name="date" required></div>
                <div class="form-group"><label for="start_time">Start Time:</label><input type="time" id="start_time" name="start_time" required></div>
                <div class="form-group"><label for="end_time">End Time:</label><input type="time" id="end_time" name="end_time" required></div>
            </div>

            <div class="form-row">
                <div class="form-group">
                    <label for="waste_type">Waste Type:</label>
                    <select id="waste_type" name="waste_type" required>
                        <option value="">-- Select Waste Type --</option>
                        <option value="Biodegradable">Biodegradable</option>
                        <option value="Non-Biodegradable">Non-Biodegradable</option>
                        <option value="Recyclable">Recyclable</option>
                        <option value="Residual">Residual</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Days:</label>
                    <div class="multi-select-dropdown">
                        <button type="button" class="dropdown-toggle" data-placeholder="Select Days">Select Days <span class="arrow">&#9660;</span></button>
                        <div class="dropdown-menu">
                            <label><input type="checkbox" name="days[]" value="Monday"> Monday</label>
                            <label><input type="checkbox" name="days[]" value="Tuesday"> Tuesday</label>
                            <label><input type="checkbox" name="days[]" value="Wednesday"> Wednesday</label>
                            <label><input type="checkbox" name="days[]" value="Thursday"> Thursday</label>
                            <label><input type="checkbox" name="days[]" value="Friday"> Friday</label>
                            <label><input type="checkbox" name="days[]" value="Saturday"> Saturday</label>
                            <label><input type="checkbox" name="days[]" value="Sunday"> Sunday</label>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="driver_id">Driver Name:</label>
                    <select id="driver_id" name="driver_id" required>
                        <option value="">-- Select Driver --</option>
                        <?php foreach($drivers as $driver): ?>
                        <option value="<?= htmlspecialchars($driver['driver_id']); ?>" data-truck-id="<?= htmlspecialchars($driver['truck_id'] ?? ''); ?>">
                            <?= htmlspecialchars($driver['formatted_name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="truck_id">Truck (Plate Number):</label>
                    <select id="truck_id" name="truck_id" required>
                        <option value="">-- Select Truck --</option>
                        <?php foreach($trucks as $truck): ?>
                        <option value="<?= htmlspecialchars($truck['truck_id']); ?>" data-driver-id="<?= htmlspecialchars($truck['driver_id'] ?? ''); ?>">
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
                        <option value="<?= htmlspecialchars($assistant['assistant_id']); ?>">
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
                            <?php if (empty($routes)): ?>
                                <label>No routes found.</label>
                            <?php else: ?>
                                <?php foreach($routes as $route): ?>
                                <label><input type="checkbox" name="route_description[]" value="<?= htmlspecialchars($route['route_name']); ?>"> <?= htmlspecialchars($route['route_name']); ?></label>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="form-actions">
                <a href="dashboard_schedule.php" class="btn btn-secondary">Back</a>
                <button type="submit" class="btn btn-primary">Add Schedule</button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const driverSelect = document.getElementById('driver_id');
    const truckSelect = document.getElementById('truck_id');
    if (driverSelect && truckSelect) {
        driverSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const truckId = selectedOption.getAttribute('data-truck-id');
            if (truckId) truckSelect.value = truckId;
        });
        truckSelect.addEventListener('change', function() {
            const selectedOption = this.options[this.selectedIndex];
            const driverId = selectedOption.getAttribute('data-driver-id');
            if (driverId) driverSelect.value = driverId;
        });
    }

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
        updateToggleText();
    });

    document.addEventListener('click', (event) => {
        document.querySelectorAll('.multi-select-dropdown').forEach(dropdown => {
            if (!dropdown.contains(event.target)) dropdown.classList.remove('active');
        });
    });
});
</script>

<?php require_once 'templates/footer.php'; ?> 
</body> 
</html>