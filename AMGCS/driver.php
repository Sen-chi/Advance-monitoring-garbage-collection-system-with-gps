<?php
session_start(); // Start the session at the very beginning

// Define the page title *before* including the header
$pageTitle = "Driver Management";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
// footer.php is included at the very end

// Configuration: Error Reporting & Display
// Turn off display_errors in production environments.
ini_set('display_errors', 1); // Keep for development debugging
ini_set('display_startup_errors', 1); // Keep for development debugging
error_reporting(E_ALL); // Log all errors

// 1. ESTABLISH DATABASE CONNECTION FIRST
$pdo = null; // Initialize $pdo
$dbError = '';
try {
    // Assume db_connect.php returns a PDO instance or throws an exception
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Failed to get a valid database connection object.");
    }
} catch (Exception $e) {
    $dbError = "Database Connection Error: " . $e->getMessage();
    // Log the detailed error for the admin
    error_log($dbError);
    // Set a user-friendly message
    $dbError = "Could not connect to the database. Please try again later.";
}

// 2. INCLUDE THE MODEL FILES
require_once("drivermodel.php"); // Driver related functions
require_once("truckmodel.php");  // Truck related functions


// 3. CALL FUNCTIONS FROM THE MODELS (Only for initial load)
$driverData = [];
// Corrected function call to match your truckmodel.php
$allTrucksForDropdown = []; // Renamed variable for clarity
$fetchError = '';

// Only fetch if DB connection is okay
if (empty($dbError) && $pdo instanceof PDO) {
    try {
        // Fetch driver data (this now includes plate_number via JOIN in the model)
        $driverData = getAllDrivers($pdo); // Call getAllDrivers function
        if ($driverData === false) { // Check for explicit false return from model on error
             throw new Exception('Failed to retrieve driver data from the model.');
        }

        // Fetch ALL trucks basic info for client-side dropdown population
        // *** CORRECTED FUNCTION NAME HERE ***
        $allTrucksForDropdown = getAllTrucksForDriverDropdown($pdo);
        if ($allTrucksForDropdown === false) { // Check for explicit false return
            throw new Exception('Failed to retrieve truck list from the model.');
        }

    } catch (Exception $e) {
         $fetchError = 'Could not retrieve data: ' . $e->getMessage(); // Error message
         error_log('Error fetching data in driver.php: ' . $e->getMessage()); // Log detailed error
         $driverData = []; // Ensure data variables are empty arrays on fetch error
         $allTrucksForDropdown = []; // Use corrected variable name
    }
} else if (!empty($dbError)) {
    $fetchError = $dbError; // Use the DB connection error message if that failed
}

?>
<html>
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?> - Your App Name</title>
     <!-- Your head content here (meta, links, scripts like jQuery and DataTables) -->
    <!-- Ensure you have jQuery, DataTables included in your header.php or here -->
    <!-- Example includes (adjust paths as necessary): -->
    <!-- <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script> -->
    <!-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css"> -->
    <!-- <script type="text/javascript" charset="utf8" src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.js"></script> -->
    <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
    <!-- Link to your styles -->
    <!-- <link rel="stylesheet" href="path/to/your/styles.css"> -->
</head>
<body>
    <div class="content">
        <div class="clearfix"> <!-- Wrap header and button to clear float -->
            <h2>Driver Management</h2>
        </div>
        <hr>

        <button class="add-user-btn" onclick="openAddDriverModal()">
            <i class="fa-solid fa-user-plus"></i> Add New Driver
        </button>

        <?php
        // Display fetch errors, if any
        if (!empty($fetchError)) {
            echo "<div class='message error'>" . htmlspecialchars($fetchError) . "</div>";
        }
        ?>

        <?php // Only show table if there wasn't a fatal fetch error and data is an array ?>
        <?php if (empty($fetchError) || (!empty($fetchError) && is_array($driverData) && !empty($driverData))): ?>
        <table id="driverTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Contact No</th>
                    <th>Assigned Truck</th> <!-- Header -->
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                // Check if $driverData is an array and not empty before looping
                if (is_array($driverData) && !empty($driverData)) {
                    foreach ($driverData as $driver) {
                        // Escape all output
                        $driverId = htmlspecialchars($driver['driver_id'] ?? ''); // Check for empty ID too
                         // Combine name parts, handle potential empty middle name gracefully
                        $fullName = htmlspecialchars($driver['first_name'] ?? '');
                        if (!empty($driver['middle_name'])) {
                            $fullName .= ' ' . htmlspecialchars($driver['middle_name']);
                        }
                         $fullName .= ' ' . htmlspecialchars($driver['last_name'] ?? '');

                        $contactNo = htmlspecialchars($driver['contact_no'] ?? '');
                        // Display Plate Number if available, otherwise show "Unassigned"
                        // This assumes the SQL query in getAllDrivers correctly joins and selects plate_number
                        $assignedTruck = htmlspecialchars($driver['plate_number'] ?? 'Unassigned');
                        $status = htmlspecialchars(ucfirst($driver['status'] ?? '')); // Capitalize and handle empty

                        echo "<tr>";
                        echo "<td>" . $fullName . "</td>";
                        echo "<td>" . $contactNo . "</td>";
                        echo "<td>" . $assignedTruck . "</td>"; // Display plate number or "Unassigned"
                        echo "<td>" . $status . "</td>";
                        // Add Edit and Delete buttons with data attributes passed to JS
                        echo "<td class='action-buttons'>";
                        // Pass necessary data for editing to the JS function (CHANGED fields)
                        // Ensure driverId is a valid integer before creating buttons
                        if (!empty($driverId) && is_numeric($driverId)) {
                            echo "<button class='edit-btn' onclick='openEditDriverModal("
                                 . $driverId . ", "
                                  . json_encode($driver['first_name'] ?? '') . ", "
                                  . json_encode($driver['middle_name'] ?? null) . ", " // Pass null if middle is empty
                                  . json_encode($driver['last_name'] ?? '') . ", "
                                  . json_encode($driver['contact_no'] ?? '') . ", "
                                  . json_encode($driver['truck_id'] ?? null) . ", " // Pass truck_id (allow null)
                                  . json_encode($driver['status'] ?? '') // Pass status
                                 . ")'><i class='fas fa-edit'></i></button>";
                            // Pass ID and full name for deletion confirmation
                            echo "<button class='delete-btn' onclick='openDeleteConfirmDriverModal(" . $driverId . ", " . json_encode($fullName) . ")'><i class='fas fa-trash'></i></button>";
                        } else {
                             echo "Invalid Driver ID"; // Should not happen if data fetch is correct
                        }
                        echo "</td>";
                        echo "</tr>";
                    }
                } else { // Only show "No drivers found" if there wasn't a fetch error or data is empty
                    echo '<tr><td colspan="5" style="text-align:center;">No driver data found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div> <!-- Content div closed -->

    <!-- Add/Edit Driver Modal Form -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
            <h3 id="modalTitle">Add/Edit Driver</h3>

            <form id="addEditForm" novalidate> <!-- Added novalidate -->
                <input type="hidden" id="driverId" name="id"> <!-- Correct name 'id' as expected by handler -->
                <input type="hidden" id="actionType" name="action"> <!-- Correct name 'action' as expected by handler -->

                <!-- Form fields for driver data -->
                <label for="firstName">First Name:</label>
                <input type="text" id="firstName" name="first_name" required>
                <span class="error-message" id="firstNameError"></span>

                 <label for="middleName">Middle Name:</label>
                <input type="text" id="middleName" name="middle_name">
                <span class="error-message" id="middleNameError"></span>

                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="last_name" required>
                <span class="error-message" id="lastNameError"></span>

                 <label for="contactNo">Contact No:</label>
                <input type="text" id="contactNo" name="contact_no" required>
                 <span class="error-message" id="contactNoError"></span>

                 <!-- Select dropdown for Truck ID - This will be populated by JS -->
                 <label for="assignedTruckId">Assigned Truck:</label>
                 <select id="assignedTruckId" name="truck_id"> <!-- Correct name 'truck_id' -->
                     <!-- Options will be added by JavaScript -->
                 </select>
                 <span class="error-message" id="assignedTruckIdError"></span> <!-- Error span for the select -->

                <label for="status">Status:</label>
                <select id="status" name="status" required>
                     <option value="">-- Select Status --</option>
                     <option value="active">Active</option>
                     <option value="inactive">Inactive</option>
                </select>
                <span class="error-message" id="statusError"></span>

                <button type="button" class="save-btn" onclick="submitDriverForm()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
            <h3>Confirm Deletion</h3>
            <p id="deleteConfirmText">Are you sure you want to delete this driver?</p>
            <input type="hidden" id="deleteDriverId"> <!-- Correct name 'id' for handler -->
            <button class="confirm-btn" onclick="confirmDeleteDriver()">Yes, Delete</button>
            <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button> <!-- Changed to type="button" -->
        </div>
    </div>

    <script>
        // Pass the trucks data from PHP to JavaScript
        // Includes truck_id, plate_number, and availability_status
        // *** CORRECTED VARIABLE NAME HERE ***
        var allTrucks = <?php echo json_encode($allTrucksForDropdown); ?>;
        console.log("Loaded trucks:", allTrucks); // For debugging


        // --- DataTables Initialization ---
        $(document).ready(function() {
            // Check if the table tbody exists and has more than one td in the first row (to skip "No data" message)
            if ($('#driverTable tbody tr').length > 0 && $('#driverTable tbody tr:first td').length > 1) {
                 $('#driverTable').DataTable({
                    "order": [[ 0, "asc" ]], // Order by Driver Name ascending
                    "columnDefs": [
                       { "orderable": false, "targets": 4 } // Disable sorting on Actions column (index 4)
                    ]
                 });
            }
            // else: Don't initialize if table is empty or just has the "No data" message
        });

        // --- General Utility Functions ---
        function clearFormErrors(formId) {
             $('#' + formId + ' .error-message').text('').hide();
             $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
        }

        // --- Modal Control Functions ---
        // Reusing openModal and closeModal - their logic is generic
        function openModal(modalId) {
            $('#' + modalId).css('display', 'flex');
        }

        function closeModal(modalId) {
             $('#' + modalId).css('display', 'none');
            // Clear form fields and errors when closing Add/Edit modal
            if (modalId === 'addEditModal') {
                 $('#addEditForm')[0].reset(); // Reset the form elements
                 $('#driverId').val('');      // Clear hidden ID
                 $('#actionType').val('');    // Clear hidden action type
                 clearFormErrors('addEditForm'); // Clear any previous validation errors
                 // Don't reset the truck dropdown value here, as it's dynamic on open
                 // $('#assignedTruckId').val('');
            }
            if (modalId === 'deleteConfirmModal') {
                $('#deleteDriverId').val(''); // Clear hidden ID
                $('#deleteConfirmText').text('Are you sure you want to delete this driver?'); // Reset text
            }
        }

        // --- Function to Populate Truck Dropdown ---
        function populateTruckDropdown(currentTruckId = null) {
            const $select = $('#assignedTruckId');
            $select.empty(); // Clear existing options

            // Add the default "Unassigned" option
            $select.append($('<option>', {
                value: '',
                text: '-- Unassigned --'
            }));

            // Add trucks based on availability and whether it's the current truck being edited
            allTrucks.forEach(function(truck) {
                // Add the truck if it's 'Available' OR if its ID matches the currentTruckId
                // Use loose comparison (==) because currentTruckId might be passed as string or number,
                // and truck.truck_id from the PHP array might be string.
                if (truck.availability_status === 'Available' || (currentTruckId !== null && truck.truck_id == currentTruckId)) {
                    const option = $('<option>', {
                        value: truck.truck_id,
                        text: truck.plate_number + (truck.truck_id == currentTruckId ? ' (Current)' : '') // Optional: Add indicator for current
                    });
                     // If this is the current truck, mark it as selected
                    if (currentTruckId !== null && truck.truck_id == currentTruckId) {
                        option.prop('selected', true);
                    }
                    $select.append(option);
                }
            });
             // If editing and the currentTruckId is provided but not in the 'Available' list (e.g. Maintenance/Inactive and not the current truck)
             // It won't be added unless it's the currently assigned truck. This is the desired behaviour.
        }


        // --- Add Driver Functionality ---
        function openAddDriverModal() {
            closeModal('addEditModal'); // Ensure form is reset before opening
            $('#modalTitle').text('Add New Driver'); // Title
            $('#actionType').val('add'); // action
            $('#driverId').val(''); // Ensure ID is empty for add
            $('#status').val('active'); // Set default status

            populateTruckDropdown(); // Populate dropdown with only 'Available' trucks

            openModal('addEditModal');
            $('#firstName').focus(); // Focus the first field
        }

        // --- Edit Driver Functionality ---
        // Parameters include truckId
        function openEditDriverModal(id, firstName, middleName, lastName, contactNo, currentTruckId, status) {
             closeModal('addEditModal'); // Ensure form is reset before opening
            $('#modalTitle').text('Edit Driver'); // Title
            $('#actionType').val('edit'); // action
            $('#driverId').val(id); // Set the hidden ID field

            // Populate the form fields
            $('#firstName').val(firstName);
            $('#middleName').val(middleName);
            $('#lastName').val(lastName);
            $('#contactNo').val(contactNo);

            // Populate dropdown including 'Available' + the current truck
            // *** Pass the currentTruckId parameter ***
            populateTruckDropdown(currentTruckId);

            // Set the dropdown value. If currentTruckId is null or '', the 'Unassigned' option will be selected by default.
            // If populateTruckDropdown already set the 'selected' property, this might be redundant but harmless.
             $('#assignedTruckId').val(currentTruckId === null || currentTruckId === '' ? '' : currentTruckId);

            $('#status').val(status);

            openModal('addEditModal');
        }

        // --- Delete Driver Functionality ---
        function openDeleteConfirmDriverModal(id, driverName) {
            $('#deleteDriverId').val(id); // Store the driver ID in the hidden field
            // Use text() to prevent potential XSS if name contains HTML
            $('#deleteConfirmText').text('Are you sure you want to delete driver: ' + driverName + '?'); // Text
            openModal('deleteConfirmModal');
        }

        function confirmDeleteDriver() {
            const id = $('#deleteDriverId').val(); // Get driver ID
            if (!id) {
                alert('Error: Driver ID not found.'); // Message
                return;
            }

            $.ajax({
                url: 'handle_driver_actions.php', // Target handler file
                type: 'POST',
                data: {
                    action: 'delete',
                    id: id // Sending driver ID
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Driver deleted successfully!'); // Message
                        closeModal('deleteConfirmModal');
                        location.reload(); // Simple way to refresh the table and data
                    } else {
                        alert('Error deleting driver: ' + (response.message || 'Unknown error')); // Message
                        console.error("Delete Error:", response.message);
                        closeModal('deleteConfirmModal');
                    }
                },
                error: function(xhr, status, error) {
                    alert('An AJAX error occurred: ' + status + '\nError: ' + error);
                    console.error("AJAX Error:", xhr.responseText);
                    closeModal('deleteConfirmModal');
                }
            });
        }

        // --- Form Submission (Add/Edit Driver) ---
        function submitDriverForm() {
            const form = $('#addEditForm');
            const action = $('#actionType').val(); // 'add' or 'edit'
            clearFormErrors('addEditForm'); // Clear previous errors

            // --- Client-side Validation ---
            let isValid = true;
            let firstErrorField = null;

            // Helper to mark fields with errors
            function markError(fieldId, message) {
                $('#' + fieldId).addClass('error');
                $('#' + fieldId + 'Error').text(message).show();
                 if (isValid) { // Only set firstErrorField if this is the first error found
                     firstErrorField = $('#' + fieldId);
                 }
                 isValid = false; // Mark form as invalid
            }

            // Get form values
            const firstName = $('#firstName').val().trim();
            const lastName = $('#lastName').val().trim();
            const contactNo = $('#contactNo').val().trim();
            const status = $('#status').val();
            // The selected truckId value from the select element
            const selectedTruckId = $('#assignedTruckId').val(); // This will be '' for "Unassigned"

            // Perform validation checks
            if (!firstName) { markError('firstName', 'First name is required.'); }
            // middleName is optional
            if (!lastName) { markError('lastName', 'Last name is required.'); }
            if (!contactNo) { markError('contactNo', 'Contact number is required.'); }
            // Truck ID is now optional (can be unassigned) - backend will validate if a specific ID exists.
            if (!status) { markError('status', 'Please select a status.'); }

             if (!isValid) {
                 if(firstErrorField) firstErrorField.focus(); // Focus the first field with an error
                 return; // Stop submission if validation fails
             }
            // --- End Client-side Validation ---


            // Prepare data using FormData
            let formData = new FormData(form[0]); // Use FormData

            // FormData already includes all input/select elements with 'name' attributes.
            // It will include 'truck_id' with the selected value (either a truck ID or '').

            // Disable the save button to prevent double clicks and provide feedback
            $('.save-btn').prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'handle_driver_actions.php', // Target handler file
                type: 'POST',
                data: formData,
                processData: false, // Required for FormData
                contentType: false, // Required for FormData
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Operation successful!'); // Use message from server
                        closeModal('addEditModal');
                        location.reload(); // Reload page to see changes
                    } else {
                        // Handle server-side validation errors or general errors
                        if (response.errors) {
                            // Map backend error keys to frontend field IDs
                            const fieldMap = {
                                'first_name': 'firstName',
                                'middle_name': 'middleName',
                                'last_name': 'lastName',
                                'contact_no': 'contactNo',
                                'truck_id': 'assignedTruckId', // Map backend 'truck_id' error to frontend 'assignedTruckId' select
                                'status': 'status',
                                'general': 'general' // Add mapping for general errors if you have a place to show them
                            };
                            let firstErrorElement = null; // To track the first element to focus

                            $.each(response.errors, function(key, message) {
                                const fieldId = fieldMap[key] || key; // Use mapped ID or the key itself
                                const errorElement = $('#' + fieldId + 'Error');
                                if (errorElement.length) {
                                     $('#' + fieldId).addClass('error');
                                     errorElement.text(message).show();
                                     if (!firstErrorElement) { // Set the first element to focus
                                         firstErrorElement = $('#' + fieldId);
                                     }
                                } else {
                                     // Handle errors that don't map to a specific field, e.g., display in an alert
                                     alert("Server Error: " + message);
                                     console.error(`Server Error for field ${key}:`, message);
                                }
                            });

                            if(firstErrorElement) {
                                firstErrorElement.focus(); // Focus the first field with an error
                            }

                        } else {
                             // Handle general errors (not tied to a specific field)
                             alert('Error: ' + (response.message || 'Operation failed. Please check the details.'));
                             console.error("Form Submission Error:", response.message);
                        }
                    }
                },
                error: function(xhr, status, error) {
                     alert('An AJAX error occurred: ' + status + '\nError: ' + error);
                     console.error("AJAX Error:", xhr.responseText);
                     // Log server response if available
                     if (xhr.responseText) {
                         console.error("Server Response:", xhr.responseText);
                     }
                },
                complete: function() {
                     // Re-enable the save button regardless of success/error
                     $('.save-btn').prop('disabled', false).text('Save');
                }
            });
        }
    </script>

<?php
// Include the footer template file
require_once 'templates/footer.php'; // Adjust path if needed

// Close the connection at the end
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}
?>
</body>
</html>