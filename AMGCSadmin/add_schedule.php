<?php
session_start(); // Ensure session is started
require 'db_connect.php';

// --- Process Form Submission ---
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assuming you have collected other schedule data from $_POST like this:
    // $date = $_POST['date'];
    // $startTime = $_POST['start_time'];
    // $endTime = $_POST['end_time'];
    // $status = $_POST['status']; // Make sure status is validated/sanitized
    // $wasteType = $_POST['waste_type'];
    // $routeDescription = $_POST['route_description'];
    // $driverName = $_POST['driver_name']; // Make sure driverName is validated/sanitized
    // $truckId = $_POST['truck_id']; // Make sure truckId is validated/sanitized and exists

    // Example: Collect and sanitize/validate data from the form (adjust names as per your form)
    $date = $_POST['date'] ?? null;
    $startTime = $_POST['start_time'] ?? null;
    $endTime = $_POST['end_time'] ?? null;
    $status = $_POST['status'] ?? 'Pending'; // Default status? Or require it?
    $wasteType = $_POST['waste_type'] ?? null;
    $routeDescription = $_POST['route_description'] ?? null;
    $driverName = $_POST['driver_name'] ?? null;
    $truckId = $_POST['truck_id'] ?? null; // Assuming this is a dropdown or hidden input

    // Validate required fields (basic example)
    if (!$date || !$startTime || !$endTime || !$wasteType || !$routeDescription || !$driverName || !$truckId) {
        $_SESSION['error'] = "Please fill in all required fields.";
        // Redirect back to the form or handle error display
        header("Location: add_schedule.php"); // Redirect back to the add page
        exit();
    }

    // Get the current quarter from the session
    // This ensures the new schedule is added to the quarter the user was viewing/had selected
    $quarter_to_add = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1; // Default to 1 if session not set

    // Prepare your INSERT statement - make sure it includes the 'quarter' column
    $sql = "INSERT INTO schedules (date, start_time, end_time, status, waste_type, route_description, driver_name, truck_id, quarter)
            VALUES (:date, :start_time, :end_time, :status, :waste_type, :route_description, :driver_name, :truck_id, :quarter)";

    try {
        $stmt = $pdo->prepare($sql);

        // Bind parameters to the prepared statement
        $stmt->bindParam(':date', $date);
        $stmt->bindParam(':start_time', $startTime);
        $stmt->bindParam(':end_time', $endTime);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':waste_type', $wasteType);
        $stmt->bindParam(':route_description', $routeDescription);
        $stmt->bindParam(':driver_name', $driverName);
        $stmt->bindParam(':truck_id', $truckId, PDO::PARAM_INT); // Assuming truck_id is integer
        $stmt->bindParam(':quarter', $quarter_to_add, PDO::PARAM_INT); // Bind the quarter from session

        $stmt->execute();

        // Set success message and redirect to dashboard
        $_SESSION['message'] = "Schedule added successfully to Quarter " . htmlspecialchars($quarter_to_add) . "!";
        header("Location: dashboard_schedule.php");
        exit();

    } catch (\PDOException $e) {
        // Log the error and set an error message
        error_log("Error adding schedule: " . $e->getMessage()); // Log to server error logs
        $_SESSION['error'] = "An error occurred while adding the schedule: " . $e->getMessage(); // Provide some info to user (be cautious in production)

        // Redirect back to the add form or dashboard with error
        header("Location: add_schedule.php"); // Redirect back to the add page
        exit();
    }
}


// --- Display the Add Schedule Form (for GET requests) ---

// Define the page title
$pageTitle = "Add New Schedule";

// Include templates
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
// require_once 'templates/footer.php'; // Include footer at the bottom

// Fetch data needed for form (e.g., trucks, drivers)
$trucks = []; // Initialize empty array
try {
    $stmtTrucks = $pdo->query("SELECT truck_id, plate_number FROM truck_info");
    $trucks = $stmtTrucks->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log("Error fetching trucks: " . $e->getMessage());
    $_SESSION['error'] = ($_SESSION['error'] ?? '') . " Error loading truck data."; // Append error
}

// Fetch Drivers (Assuming you have a drivers table)
// $drivers = [];
// try {
//     $stmtDrivers = $pdo->query("SELECT driver_id, driver_name FROM drivers"); // Adjust query/table name
//     $drivers = $stmtDrivers->fetchAll(PDO::FETCH_ASSOC);
// } catch (\PDOException $e) {
//     error_log("Error fetching drivers: " . $e->getMessage());
//     $_SESSION['error'] = ($_SESSION['error'] ?? '') . " Error loading driver data."; // Append error
// }
// --- For now, driver_name seems to be directly stored, not linked to a driver ID.
// --- If drivers are stored in a table, you'll need to fetch them similarly to trucks.
// --- If driver_name is just a text input, no fetching needed here.
// --- Based on provided dashboard_schedule.php, driver_name seems to be direct.


// Get the currently selected quarter for display hint on the form
$current_quarter_in_session = isset($_SESSION['selected_quarter']) ? (int)$_SESSION['selected_quarter'] : 1;

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.1.1/css/all.min.css">
    <style>
        /* Add or adjust styles for your form */
        .add-schedule-form {
            background-color: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            max-width: 600px; /* Adjust as needed */
            margin: 20px auto; /* Center the form */
        }
        .add-schedule-form .form-group {
            margin-bottom: 15px;
        }
        .add-schedule-form label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .add-schedule-form input[type="date"],
        .add-schedule-form input[type="time"],
        .add-schedule-form input[type="text"],
        .add-schedule-form select,
        .add-schedule-form textarea {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Include padding and border in element's total width */
        }
        .add-schedule-form textarea {
             resize: vertical; /* Allow vertical resizing */
        }
        .add-schedule-form button[type="submit"] {
             padding: 10px 20px;
             background-color: #28a745; /* Green save button */
             color: white;
             border: none;
             border-radius: 4px;
             cursor: pointer;
             font-size: 16px;
             transition: background-color 0.3s ease;
        }
         .add-schedule-form button[type="submit"]:hover {
             background-color: #218838;
         }
         .info-text {
             font-size: 0.9em;
             color: #555;
             margin-bottom: 20px;
         }

    </style>
</head>
<body>
    <div class="content">
        <h2>Add New Schedule</h2>

        <!-- Flash Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="flash-message flash-success"><?= htmlspecialchars($_SESSION['message']); ?></div>
            <?php unset($_SESSION['message']); ?>
        <?php endif; ?>
        <?php if (isset($_SESSION['error'])): ?>
            <div class="flash-message flash-error"><?= htmlspecialchars($_SESSION['error']); ?></div>
            <?php unset($_SESSION['error']); ?>
        <?php endif; ?>

        <p class="info-text">Adding schedule for Quarter <?= htmlspecialchars($current_quarter_in_session); ?>.</p>

        <div class="add-schedule-form">
            <form action="add_schedule.php" method="POST"> // Form posts back to itself

                <div class="form-group">
                    <label for="date">Date:</label>
                    <input type="date" id="date" name="date" required>
                </div>

                 <div class="form-group">
                    <label for="start_time">Start Time:</label>
                    <input type="time" id="start_time" name="start_time" required>
                </div>

                <div class="form-group">
                    <label for="end_time">End Time:</label>
                    <input type="time" id="end_time" name="end_time" required>
                </div>

                 <div class="form-group">
                    <label for="waste_type">Waste Type / Days:</label>
                     <!-- You might want this to be a select if types are fixed -->
                    <input type="text" id="waste_type" name="waste_type" required>
                </div>

                <div class="form-group">
                    <label for="route_description">Route Description:</label>
                    <textarea id="route_description" name="route_description" rows="3" required></textarea>
                </div>

                 <div class="form-group">
                    <label for="driver_name">Driver Name:</label>
                     <!-- If drivers are in a table, use a select here -->
                    <input type="text" id="driver_name" name="driver_name" required>
                </div>

                 <div class="form-group">
                    <label for="truck_id">Truck (Plate Number):</label>
                    <select id="truck_id" name="truck_id" required>
                         <option value="">-- Select Truck --</option>
                        <?php foreach ($trucks as $truck): ?>
                            <option value="<?= htmlspecialchars($truck['truck_id']); ?>">
                                <?= htmlspecialchars($truck['plate_number']); ?>
                            </option>
                        <?php endforeach; ?>
                         <?php if (empty($trucks)): ?>
                              <option value="" disabled>No trucks available</option>
                         <?php endif; ?>
                    </select>
                </div>

                <!-- Hidden input to pass the quarter? No, read directly from session in PHP -->
                <!-- <input type="hidden" name="quarter" value="<?= htmlspecialchars($current_quarter_in_session); ?>"> -->


                <button type="submit">Add Schedule</button>

            </form>
        </div>

         <br>
        <p><a href="dashboard_schedule.php">Back to Schedule Dashboard</a></p>

    </div>

<?php require_once 'templates/footer.php'; ?>
</body>
</html>