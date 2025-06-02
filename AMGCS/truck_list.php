<?php
// Define the page title *before* including the header
$pageTitle = "Trucks";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';

// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. ESTABLISH DATABASE CONNECTION FIRST
$pdo = require_once("db_connect.php");
if (!$pdo instanceof PDO) {
     // Log error but show user-friendly message
     error_log("Failed to get a valid database connection object in truck_list.php.");
     die("Could not connect to the database. Please try again later.");
}

// 2. INCLUDE THE MODEL FILE
require_once("truckmodel.php");

// 3. CALL THE FUNCTION FROM THE MODEL (Only for initial load)
// This function now fetches data from truck_info including availability
$truckData = getAllTrucks($pdo);

// Check if fetching data failed
if ($truckData === false) { // Assuming getAllTrucks could return false on critical error (though model now returns empty array)
    $fetchError = "Failed to retrieve truck data from the database.";
    $truckData = []; // Ensure it's an empty array for the loop
} else {
     $fetchError = null; // No fetch error
}

?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | AMGCS</title>
    <!-- Your existing CSS links -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
    <!-- FontAwesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- jQuery (necessary for DataTables and AJAX) -->
    <script src="https://code.jquery.com/jquery-3.7.0.js"></script>
    <!-- DataTables JS -->
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <!-- Add SweetAlert2 for better alerts -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

</head>
<body>

  <div class="content">
    <h2>Truck Management</h2>
    <hr>

<!-- Add New Button -->
<button class="add-user-btn" onclick="openAddModal()">
    <i class="fa-solid fa-plus"></i> Add Truck
</button>

<?php if (!empty($fetchError)): ?>
     <div class='message error'><?php echo htmlspecialchars($fetchError); ?></div>
 <?php endif; ?>


<!-- Adjusted table headers and column count -->
<table id="truckTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Plate Number/CS Number</th>
            <th>Capacity (kg)</th>
            <th>Model</th>
            <!-- Added Availability Status Header -->
            <th>Availability Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        // Check if $truckData is an array and not empty before looping
        if (is_array($truckData) && !empty($truckData)) {
            foreach ($truckData as $truck) {
                // Get the truck_id (required for actions)
                $truck_id = htmlspecialchars($truck['truck_id'] ?? '');
                // Get plate_number
                $plate_number = htmlspecialchars($truck['plate_number'] ?? '');
                // Get capacity and model - use ?? '' to handle potential nulls gracefully for display
                $capacity_kg = htmlspecialchars($truck['capacity_kg'] ?? '');
                $model = htmlspecialchars($truck['model'] ?? '');
                // Get availability_status (will be one of the new values from DB)
                $availability_status = htmlspecialchars($truck['availability_status'] ?? 'N/A'); // Default to N/A if null/missing


                echo "<tr>";
                // Display plate number
                echo "<td>" . $plate_number . "</td>";
                // Display capacity (show empty string if null/empty, or the value)
                echo "<td>" . ($capacity_kg !== '' ? $capacity_kg : '') . "</td>";
                // Display model
                echo "<td>" . ($model !== '' ? $model : '') . "</td>"; // Also handle empty model
                // Display availability_status
                echo "<td>" . $availability_status . "</td>";

                // Add Edit and Delete buttons - pass truck_id
                echo "<td class='action-buttons'>";
                // Pass truck_id to JS functions - ensure it's a valid integer
                if (!empty($truck_id) && is_numeric($truck_id)) {
                    echo "<button class='edit-btn' onclick='openEditModal(" . $truck_id . ")'><i class='fas fa-edit'></i> Edit</button>";
                    echo "<button class='delete-btn' onclick='openDeleteConfirmModal(" . $truck_id . ")'><i class='fas fa-trash'></i> Delete</button>";
                } else {
                     echo "Invalid Truck ID"; // Should not happen if data fetch is correct
                }
                echo "</td>";
                echo "</tr>";
            }
        } else {
            // Message if no data or fetch error
            // Changed colspan to 5
            echo '<tr><td colspan="5" style="text-align:center;">No truck data found.</td></tr>';
        }
        ?>
    </tbody>
</table>

  </div> <!-- Content div closed -->

  <!-- Add/Edit Modal Form -->

  <div id="addEditModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
      <h3 id="modalTitle">Add New Truck</h3>
      <form id="addEditForm" novalidate>
          <!-- Hidden field for truck_id (used for editing) -->
          <input type="hidden" id="truckId" name="truck_id">
          <!-- Hidden field for action (add/edit) -->
          <input type="hidden" id="actionType" name="action">

            <label for="plateNumber">Plate Number:</label>
            <input type="text" id="plateNumber" name="plate_number" required>
            <span class="error-message" id="plateNumberError"></span>

            <label for="capacityKg">Capacity (kg):</label>
            <input type="number" id="capacityKg" name="capacity_kg" min="0">
            <span class="error-message" id="capacityKgError"></span>

            <label for="truckModel">Model:</label>
            <input type="text" id="truckModel" name="model">
            <span class="error-message" id="modelError"></span>

            <!-- *** MODIFIED: Updated Availability Status Select Options *** -->
            <label for="availabilityStatus">Availability Status:</label>
            <select id="availabilityStatus" name="availability_status" required>
                <option value="">-- Select Status --</option>
                <option value="Available">Available</option>
                <option value="Assigned">Assigned</option>
                <option value="Maintenance">Maintenance</option>
                <option value="Inactive">Inactive</option>
            </select>
            <span class="error-message" id="availabilityStatusError"></span>

            <button type="button" class="save-btn" onclick="submitForm()">Save</button>
            <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
        </form>
    </div>
  </div>

  <!-- Delete Confirmation Modal -->

  <div id="deleteConfirmModal" class="modal">
      <div class="modal-content">
          <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
          <h3>Confirm Deletion</h3>
          <p>Are you sure you want to delete this truck? This action cannot be undone and will unassign any drivers currently assigned to it.</p>
          <!-- Hidden field to store truck_id for deletion -->
          <input type="hidden" id="deleteTruckId">
          <button class="confirm-btn" onclick="confirmDelete()">Yes, Delete</button>
          <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
      </div>
  </div>

  <script>
     // --- General Utility Functions ---
     function clearFormErrors(formId) {
          $('#' + formId + ' .error-message').text('').hide();
          $('#' + formId + ' input, #' + formId + ' select').removeClass('error'); // Added select
     }

    // --- DataTables Initialization ---
    $(document).ready(function() {
        // Check if the table exists and has more than one row (including header) before initializing
        // and check if the first tbody row has the expected number of cells (5 for Plate, Capacity, Model, Availability, Actions)
        // This check prevents DataTables from initializing on the "No data" row.
        const $tbodyRows = $('#truckTable tbody tr');
        if ($tbodyRows.length > 0) {
            const $firstRowCells = $tbodyRows.first().children('td');
            // Check if it's not the "No data" row (which has colspan=5)
            if ($firstRowCells.length === 5 && $firstRowCells.attr('colspan') != 5) {
                 // Initialize DataTables if there is data and it has the correct number of columns
                  $('#truckTable').DataTable({
                    "order": [[ 0, "asc" ]], // Order by Plate Number (column 0) ascending
                    "columnDefs": [
                       // Actions column is now at index 4
                       { "orderable": false, "targets": 4 }
                    ]
                 });
             } else if ($firstRowCells.length > 1 && $firstRowCells.attr('colspan') != 5) {
                 // Handle cases during transition where the number of columns might be unexpected but not the colspan=5 message
                  console.warn("DataTables: Initializing with unexpected number of columns in the first row (" + $firstRowCells.length + "). Expected 5.");
                  $('#truckTable').DataTable({
                    "order": [[ 0, "asc" ]],
                    "columnDefs": [
                       // Assume actions is the last column regardless of count if not the "No data" row
                       { "orderable": false, "targets": -1 }
                    ]
                 });
            }
            // Else: It's the "No data" row, do not initialize DataTables.
        }
    });

    // --- Modal Control Functions ---
    function openModal(modalId) {
        document.getElementById(modalId).style.display = "flex";
    }

    function closeModal(modalId) {
        document.getElementById(modalId).style.display = "none";
        // Clear form fields and errors when closing Add/Edit modal
        if (modalId === 'addEditModal') {
             $('#addEditForm')[0].reset(); // Reset the form using jQuery
             $('#truckId').val(''); // Clear hidden truck_id
             $('#actionType').val(''); // Clear hidden action type
             clearFormErrors('addEditForm'); // Clear any previous validation errors
             // Reset availability status dropdown as well
             $('#availabilityStatus').val(''); // Explicitly reset dropdown to the empty option
        }
        if (modalId === 'deleteConfirmModal') {
            $('#deleteTruckId').val(''); // Clear hidden delete truck_id
        }
    }

    // --- Add Functionality ---
    function openAddModal() {
        closeModal('addEditModal'); // Ensure form is reset
        $('#modalTitle').text('Add New Truck'); // Set modal title
        $('#actionType').val('add'); // Set action type for the form submission
        openModal('addEditModal');
         $('#plateNumber').focus(); // Focus the first field
    }

    // --- Edit Functionality ---
    function openEditModal(truck_id) { // Expects truck_id
        closeModal('addEditModal'); // Ensure form is reset
        $('#modalTitle').text('Edit Truck'); // Set modal title
        $('#actionType').val('edit'); // Set action type
        $('#truckId').val(truck_id); // Set the hidden truck_id field

        // Fetch existing data for this truck using truck_id
        $.ajax({
            url: 'handle_truck_actions.php',
            type: 'POST',
            data: {
                action: 'get',
                truck_id: truck_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.data) {
                    // Populate the form with fetched data
                    const truck = response.data;
                    $('#plateNumber').val(truck.plate_number);
                    $('#capacityKg').val(truck.capacity_kg);
                    $('#truckModel').val(truck.model);
                    // *** MODIFIED: Populate availability_status dropdown with NEW values ***
                    $('#availabilityStatus').val(truck.availability_status); // This will select the correct option based on its value

                    openModal('addEditModal'); // Open modal after data is loaded
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: 'Error fetching truck data: ' + (response.message || 'Unknown error')
                    });
                    console.error("Fetch Error:", response.message);
                }
            },
            error: function(xhr, status, error) {
                 Swal.fire({
                    icon: 'error',
                    title: 'AJAX Error',
                    text: 'An AJAX error occurred while fetching data: ' + status + '\nError: ' + error
                 });
                 console.error("AJAX Error:", xhr.responseText);
            }
        });
    }

    // --- Delete Functionality ---
    function openDeleteConfirmModal(truck_id) {
        $('#deleteTruckId').val(truck_id); // Store the truck_id in the hidden field
        openModal('deleteConfirmModal');
    }

    function confirmDelete() {
        const truck_id = $('#deleteTruckId').val();
        if (!truck_id) {
             Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error: Truck ID not found for deletion.'
             });
            return;
        }

        $.ajax({
            url: 'handle_truck_actions.php',
            type: 'POST',
            data: {
                action: 'delete',
                truck_id: truck_id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                     Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: response.message
                     }).then(() => {
                         closeModal('deleteConfirmModal');
                         location.reload(); // Reload the page to see changes
                     });
                } else {
                    Swal.fire({
                       icon: 'error',
                       title: 'Error',
                       text: 'Error deleting truck: ' + (response.message || 'Unknown error')
                    });
                    closeModal('deleteConfirmModal');
                }
            },
            error: function(xhr, status, error) {
                Swal.fire({
                   icon: 'error',
                   title: 'AJAX Error',
                   text: 'An AJAX error occurred: ' + status + '\nError: ' + error
                });
                console.error("AJAX Error:", xhr.responseText);
                closeModal('deleteConfirmModal');
            }
        });
    }


    // --- Form Submission (Add/Edit) ---
    function submitForm() {
        const form = $('#addEditForm');
        const action = $('#actionType').val();
        const truck_id = $('#truckId').val();

        clearFormErrors('addEditForm'); // Clear previous errors

        // --- Client-side Validation ---
        let isValid = true;
        let firstErrorField = null;

        function markError(fieldId, message) {
             $('#' + fieldId).addClass('error');
             $('#' + fieldId + 'Error').text(message).show();
             if (isValid && !firstErrorField) { // Only set firstErrorField if this is the first error found
                 firstErrorField = $('#' + fieldId);
             }
             isValid = false;
         }

        const plate_number = $('#plateNumber').val().trim();
        const capacityKgInput = $('#capacityKg').val().trim();
        const truckModel = $('#truckModel').val().trim();
        // Get availability status value from the updated select
        const availabilityStatus = $('#availabilityStatus').val();


        if (!plate_number) {
             markError('plateNumber', 'Plate Number cannot be empty.');
        }

         // Basic validation for capacity (allow empty or valid number)
        let capacityValue = null; // Will be null if input is empty or invalid
        if (capacityKgInput !== '') {
             const parsedCapacity = parseInt(capacityKgInput);
            if (isNaN(parsedCapacity) || parsedCapacity < 0) {
                 markError('capacityKg', 'Capacity must be a non-negative number or left empty.');
            } else {
                 capacityValue = parsedCapacity;
            }
        }

        // Validate availability status (check if the required select has a value)
        // The HTML 'required' attribute and this check are sufficient for client-side
        // Server-side will validate against the allowed ENUM values
        if (!availabilityStatus) {
             markError('availabilityStatus', 'Availability Status is required.');
        }


        if (!isValid) {
             if(firstErrorField) firstErrorField.focus();
            return; // Stop submission if validation fails
        }
        // --- End Client-side Validation ---


        let formData = {
            action: action,
            plate_number: plate_number,
            capacity_kg: capacityValue,
            model: truckModel,
            // Add availability status to formData
            availability_status: availabilityStatus // Send the selected value
        };

        // Add truck_id only if it's an edit action
        if (action === 'edit' && truck_id) {
            formData.truck_id = truck_id;
        } else if (action === 'edit' && !truck_id) {
             Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Error: Truck ID is missing for update.'
             });
             return;
        }

        // Disable the save button to prevent double clicks
        $('.save-btn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'handle_truck_actions.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                     Swal.fire({
                        icon: 'success',
                        title: 'Success',
                        text: 'Truck ' + (action === 'add' ? 'added' : 'updated') + ' successfully!'
                     }).then(() => {
                        closeModal('addEditModal');
                        location.reload(); // Reload page to see changes
                     });
                } else {
                    // Handle server-side validation errors or general errors
                    if (response.errors) {
                        // Map backend error keys to frontend field IDs
                        const fieldMap = {
                            'truck_id': 'truckId',
                            'plate_number': 'plateNumber',
                            'capacity_kg': 'capacityKg',
                            'model': 'truckModel',
                            // Mapping for availability_status error
                            'availability_status': 'availabilityStatus'
                        };
                         let focused = false;
                        $.each(response.errors, function(key, message) {
                            const fieldId = fieldMap[key] || key;
                             const $field = $('#' + fieldId);
                             if ($field.length) {
                                 markError(fieldId, message);
                                 if (!focused) {
                                     $field.focus();
                                     focused = true;
                                 }
                             } else {
                                console.error("Server error for unknown field:", key, message);
                                if (!focused) {
                                     Swal.fire({
                                        icon: 'error',
                                        title: 'Server Error',
                                        text: 'Error processing data: ' + message
                                     });
                                     focused = true;
                                }
                            }
                        });

                    } else {
                         Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: 'Error: ' + (response.message || 'Operation failed. Please check the details.')
                         });
                         console.error("Form Submission Error:", response.message);
                    }
                }
            },
            error: function(xhr, status, error) {
                 Swal.fire({
                   icon: 'error',
                   title: 'AJAX Error',
                   text: 'An AJAX error occurred: ' + status + '\nError: ' + error + '\nResponse: ' + xhr.responseText
                 });
                 console.error("AJAX Error:", xhr.responseText);
            },
             complete: function() {
                 $('.save-btn').prop('disabled', false).text('Save');
             }
        });
    }

    // --- Logout Modal Functions (Existing) ---
    function openLogoutModal() {
          openModal('logoutModal');
      }

    function logout() {
          window.location.href = "sign_in.php";
      }

    // Close modal if clicked outside the content area
    window.onclick = function(event) {
      $('.modal').each(function() {
        if (event.target === this) {
           closeModal(this.id);
        }
      });
    }

  </script>

<?php
// Include the footer template file
require_once 'templates/footer.php';

// Close the connection at the end
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}
?>

</body>
</html>