<?php
session_start();
require 'db_connect.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $date = $_POST['date'] ?? null;
    $startTime = $_POST['start_time'] ?? null;
    $endTime = $_POST['end_time'] ?? null;
    $wasteType = $_POST['waste_type'] ?? null;
    $days = $_POST['days'] ?? []; // This will be an array from the new dropdown
    $routeDescription = $_POST['route_description'] ?? null;
    $driverId = $_POST['driver_id'] ?? null;
    $truckId = $_POST['truck_id'] ?? null;

    if (empty($date) || empty($startTime) || empty($endTime) || empty($wasteType) || empty($days) || empty($routeDescription) || empty($driverId) || empty($truckId)) {
        $_SESSION['error'] = "Error: Please fill all fields.";
        header("Location: add_schedule.php");
        exit();
    }

    // Your existing logic for handling $days remains the same as it's an array
    $daysString = implode(',', $days);

    $driverName = 'Unknown Driver';
    try {
        $stmtDriver = $pdo->prepare(
            "SELECT CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS full_name
             FROM truck_driver WHERE driver_id = :driver_id"
        );
        $stmtDriver->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
        $stmtDriver->execute();
        $driverResult = $stmtDriver->fetch(PDO::FETCH_ASSOC);

        if ($driverResult) {
            $driverName = $driverResult['full_name'];
        }
    } catch (\PDOException $e) {
        error_log("Error in getting the name of the driver: " . $e->getMessage());
    }

    $quarter_to_add = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;
    $sql = "INSERT INTO schedules (date, start_time, end_time, waste_type, days, route_description, driver_name, truck_id, quarter)
            VALUES (:date, :start_time, :end_time, :waste_type, :days, :route_description, :driver_name, :truck_id, :quarter)";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        $stmt->bindParam(':waste_type', $wasteType);
        $stmt->bindParam(':days', $daysString);
        $stmt->bindParam(':route_description', $routeDescription);
        $stmt->bindParam(':driver_name', $driverName);
        $stmt->bindParam(':truck_id', $truckId, PDO::PARAM_INT);
        $stmt->bindParam(':quarter', $quarter_to_add, PDO::PARAM_INT);
        $stmt->execute();

        $_SESSION['message'] = "Schedule added successfully!";
        header("Location: dashboard_schedule.php");
        exit();

    } catch (\PDOException $e) {
        error_log("Error sa pag-add ng schedule: " . $e->getMessage());
        $_SESSION['error'] = "Database error: Hindi ma-save ang schedule.(Can't save schedule)" . $e->getMessage();
        header("Location: add_schedule.php");
        exit();
    }
}

$pageTitle = "Add New Schedule";
require_once 'templates/header.php';
require_once 'templates/sidebar.php';

$trucks = [];
try {
    $stmtTrucks = $pdo->query("SELECT truck_id, plate_number FROM truck_info ORDER BY plate_number");
    $trucks = $stmtTrucks->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("CRITICAL ERROR in getting TRUCKS: " . $e->getMessage());
}

$drivers = [];
try {
    $query = "SELECT driver_id, CONCAT(last_name, ', ', first_name, ' ', LEFT(middle_name, 1), '.') AS formatted_name
              FROM truck_driver ORDER BY last_name, first_name";
    $stmtDrivers = $pdo->query($query);
    $drivers = $stmtDrivers->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die("CRITICAL ERROR in getting DRIVERS: " . $e->getMessage());
}

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
        /* Your existing CSS */
        .add-schedule-form {
            background-color: #fff;
            padding: 20px 30px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 900px;
            margin: 20px auto;
        }
        .form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            flex: 1;
            min-width: 200px;
        }
        .add-schedule-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 14px;
        }
        .add-schedule-form input,
        .add-schedule-form select,
        .add-schedule-form textarea {
            width: 100%;
            padding: 8px 10px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box;
            font-size: 14px;
        }
        .add-schedule-form select[multiple] {
            height: auto;
            min-height: 100px;
        }
        .form-actions {
            display: flex;
            justify-content: flex-end;
            gap: 12px;
            margin-top: 20px;
        }
        .btn {
            padding: 10px 22px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            letter-spacing: 0.5px;
            border: 1px solid rgba(0, 0, 0, 0.2);
            box-shadow: 0 3px 5px rgba(0, 0, 0, 0.15);
            transition: all 0.2s ease-in-out;
        }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 5px 10px rgba(0, 0, 0, 0.2); }
        .btn:active { transform: translateY(1px); box-shadow: 0 1px 2px rgba(0, 0, 0, 0.2); }
        .btn-primary { background-color: #28a745; color: white; border-color: #1c7430; }
        .btn-primary:hover { background-color: #2dab4f; }
        .btn-secondary { background-color: #6c757d; color: white; border-color: #545b62; }
        .btn-secondary:hover { background-color: #7a838b; }
        .flash-message { padding: 15px; margin-bottom: 20px; border-radius: 4px; max-width: 900px; margin-left: auto; margin-right: auto; }
        .flash-success { background-color: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .flash-error { background-color: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info-text { font-size: 0.9em; color: #555; margin-bottom: 20px; text-align: center; }
        @media (max-width: 768px) {
            .form-row { flex-direction: column; gap: 0; }
            .form-group { margin-bottom: 15px; }
            .form-actions { flex-direction: column-reverse; align-items: stretch; }
        }

        /* NEW CSS for the multi-select dropdown */
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
        <h2>Add New Schedule</h2>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); unset($_SESSION['message']); ?></div>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); unset($_SESSION['error']); ?></div>
        <?php endif; ?>

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
                    <!-- START OF NEW DAYS DROPDOWN HTML -->
                    <div class="form-group">
                        <label for="days-select-toggle">Days:</label>
                        <div class="multi-select-dropdown">
                            <button type="button" class="dropdown-toggle" id="days-select-toggle">Select Days <span class="arrow">&#9660;</span></button>
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
                    <!-- END OF NEW DAYS DROPDOWN HTML -->
                    <div class="form-group">
                        <label for="driver_id">Driver Name:</label>
                        <select id="driver_id" name="driver_id" required>
                            <option value="">-- Select Driver --</option>
                            <?php foreach ($drivers as $driver): ?>
                                <option value="<?= htmlspecialchars($driver['driver_id']); ?>"><?= htmlspecialchars($driver['formatted_name']); ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($drivers)): ?><option value="" disabled>Walang driver na nahanap.</option><?php endif; ?>
                        </select>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="truck_id">Truck (Plate Number):</label>
                        <select id="truck_id" name="truck_id" required>
                            <option value="">-- Select Truck --</option>
                            <?php foreach ($trucks as $truck): ?>
                                <option value="<?= htmlspecialchars($truck['truck_id']); ?>"><?= htmlspecialchars($truck['plate_number']); ?></option>
                            <?php endforeach; ?>
                            <?php if (empty($trucks)): ?><option value="" disabled>Walang truck na nahanap.</option><?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group" style="flex: 100%;">
                        <label for="route_description">Route Description:</label>
                        <textarea id="route_description" name="route_description" rows="3" required placeholder="e.g., Brgy. San Juan to Brgy. San Pedro"></textarea>
                    </div>
                </div>

                <div class="form-actions">
                    <a href="dashboard_schedule.php" class="btn btn-secondary">Back</a>
                    <button type="submit" class="btn btn-primary">Add Schedule</button>
                </div>

            </form>
        </div>
    </div>

    <!-- NEW JAVASCRIPT FOR THE MULTI-SELECT DROPDOWN -->
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