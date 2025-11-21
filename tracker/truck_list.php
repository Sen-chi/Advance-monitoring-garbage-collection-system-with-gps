<?php
$pageTitle = "Trucks";

require_once 'templates/header.php';
require_once 'templates/sidebar.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

$pdo = require_once("db_connect.php");
if (!$pdo instanceof PDO) {
     error_log("Failed to get a valid database connection object in truck_list.php.");
     die("Could not connect to the database. Please try again later.");
}

require_once("truckmodel.php");

$truckData = getAllTrucks($pdo);

if ($truckData === false) {
    $fetchError = "Failed to retrieve truck data from the database.";
    $truckData = [];
} else {
     $fetchError = null;
}

?>

<!DOCTYPE html>

<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> | AMGCS</title>
    <link rel="stylesheet" href="css/truck.css">
   <style>
            /* --- Modal Content Sizing --- */
            /* --- DataTables Customization (Universal) --- */
        .dataTables_filter input[type="search"] {
            border-radius: 25px !important;
            border: 1px solid #ccc !important;
            padding: 8px 12px !important;
            outline: none !important;
        }

        .dataTables_length, .dataTables_filter {
            margin-bottom: 15px !important;
        }

        /* --- Modal Form Grid --- */
        .modal-form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px; /* Mas maliit na gap */
            margin-bottom: 10px; /* Less space below */
        }

        .modal-form-grid .form-group {
            display: flex;
            flex-direction: column;
        }

        .modal-form-grid label {
            margin-bottom: 4px;
            font-weight: 500; /* Normal, not bold */
            font-size: 0.9em;
        }

        .modal-form-grid input[type="text"],
        .modal-form-grid input[type="number"],
        .modal-form-grid select {
            padding: 6px; /* Mas maliit */
            border: 1px solid #ccc;
            border-radius: 4px;
            width: 100%;
            box-sizing: border-box;
            font-size: 0.9em;
        }

        .modal-form-grid .error-message {
            color: red;
            font-size: 0.8em;
            margin-top: 2px;
            display: none;
        }

        /* --- Buttons --- */
        .modal-content .save-btn,
        .modal-content .cancel-btn {
            padding: 6px 14px; /* Mas maliit */
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 0.9em;
            font-weight: normal; /* Not bold */
            margin-top: 5px;
        }

        .modal-content .save-btn {
            background-color: #28a745;
            color: white;
        }

        .modal-content .cancel-btn {
            background-color: #dc3545;
            color: white;
        }

        .modal-buttons {
            display: flex;
            justify-content: flex-end;
            gap: 8px; /* Mas dikit */
            margin-top: 10px;
        }

        /* Responsive */
        @media (max-width: 768px) {
            .modal-form-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>


</head>
<body>

  <div class="content">
    <h2>Truck Management</h2>
    <hr>

<button class="add-user-btn" onclick="openAddModal()">
    <i class="fa-solid fa-plus"></i> Add Truck
</button>

<?php if (!empty($fetchError)): ?>

<div class='message error'><?php echo htmlspecialchars($fetchError); ?></div>
 <?php endif; ?>

<table id="truckTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Plate Number/CS Number</th>
            <th>Capacity (kg)</th>
            <th>Model</th>
            <th>Availability Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (is_array($truckData) && !empty($truckData)) {
            foreach ($truckData as $truck) {
                $truck_id = htmlspecialchars($truck['truck_id'] ?? '');
                $plate_number = htmlspecialchars($truck['plate_number'] ?? '');
                $capacity_kg = htmlspecialchars($truck['capacity_kg'] ?? '');
                $model = htmlspecialchars($truck['model'] ?? '');
                $availability_status = htmlspecialchars($truck['availability_status'] ?? 'N/A');

echo "<tr>";
            echo "<td>" . $plate_number . "</td>";
            echo "<td>" . ($capacity_kg !== '' ? $capacity_kg : '') . "</td>";
            echo "<td>" . ($model !== '' ? $model : '') . "</td>";
            echo "<td>" . $availability_status . "</td>";

            echo "<td class='action-buttons'>";
            if (!empty($truck_id) && is_numeric($truck_id)) {
                echo "<button class='edit-btn' onclick='openEditModal(" . $truck_id . ")'><i class='fas fa-edit'></i> </button>";
                echo "<button class='delete-btn' onclick='openDeleteConfirmModal(" . $truck_id . ")'><i class='fas fa-trash'></i> </button>";
            } else {
                 echo "Invalid Truck ID";
            }
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="5" style="text-align:center;">No truck data found.</td></tr>';
    }
    ?>
</tbody>
</table>

  </div>

  <div id="addEditModal" class="modal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
      <h3 id="modalTitle">Add New Truck</h3>
      <form id="addEditForm" novalidate>
          <input type="hidden" id="truckId" name="truck_id">
          <input type="hidden" id="actionType" name="action">

            <div class="modal-form-grid">
                <div class="form-group">
                    <label for="plateNumber">Plate Number:</label>
                    <input type="text" id="plateNumber" name="plate_number" required>
                    <span class="error-message" id="plateNumberError"></span>
                </div>

                <div class="form-group">
                    <label for="capacityKg">Capacity (kg):</label>
                    <input type="number" id="capacityKg" name="capacity_kg" min="0">
                    <span class="error-message" id="capacityKgError"></span>
                </div>

                <div class="form-group">
                    <label for="truckModel">Model:</label>
                    <input type="text" id="truckModel" name="model">
                    <span class="error-message" id="modelError"></span>
                </div>

                <div class="form-group">
                    <label for="availabilityStatus">Availability Status:</label>
                    <select id="availabilityStatus" name="availability_status" required>
                        <option value="">-- Select Status --</option>
                        <option value="Available">Available</option>
                        <option value="Assigned">Assigned</option>
                        <option value="Maintenance">Maintenance</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                    <span class="error-message" id="availabilityStatusError"></span>
                </div>
            </div>

            <div class="modal-buttons">
                <button type="button" class="save-btn" onclick="submitForm()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
            </div>
        </form>
    </div>
  </div>

  <div id="deleteConfirmModal" class="modal">
      <div class="modal-content">
          <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
          <h3>Confirm Deletion</h3>
          <p>Are you sure you want to delete this truck? This action cannot be undone and will unassign any drivers currently assigned to it.</p>
          <input type="hidden" id="deleteTruckId">
          <button class="confirm-btn" onclick="confirmDelete()">Yes, Delete</button>
          <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
      </div>
  </div>

  <script>
     function clearFormErrors(formId) {
          $('#' + formId + ' .error-message').text('').hide();
          $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
     }

    $(document).ready(function() {
        const $tbodyRows = $('#truckTable tbody tr');
        if ($tbodyRows.length > 0) {
            const $firstRowCells = $tbodyRows.first().children('td');
            if ($firstRowCells.length === 5 && $firstRowCells.attr('colspan') != 5) {
                  $('#truckTable').DataTable({
                    "order": [[ 0, "asc" ]],
                    "columnDefs": [
                       { "orderable": false, "targets": 4 }
                    ]
                 });
             } else if ($firstRowCells.length > 1 && $firstRowCells.attr('colspan') != 5) {
                  console.warn("DataTables: Initializing with unexpected number of columns in the first row (" + $firstRowCells.length + "). Expected 5.");
                  $('#truckTable').DataTable({
                    "order": [[ 0, "asc" ]],
                    "columnDefs": [
                       { "orderable": false, "targets": -1 }
                    ]
                 });
            }
        }
    });

    function openAddModal() {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#truckId').val('');
        $('#actionType').val('add');
        $('#modalTitle').text('Add New Truck');
        openModal('addEditModal');
        $('#plateNumber').focus();
    }

    function openEditModal(truck_id) {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Edit Truck');
        $('#actionType').val('edit');
        $('#truckId').val(truck_id);

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
                    const truck = response.data;
                    $('#plateNumber').val(truck.plate_number);
                    $('#capacityKg').val(truck.capacity_kg);
                    $('#truckModel').val(truck.model);
                    $('#availabilityStatus').val(truck.availability_status);

                    openModal('addEditModal');
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

    function openDeleteConfirmModal(truck_id) {
        $('#deleteTruckId').val(truck_id);
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
                         location.reload();
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


    function submitForm() {
        const form = $('#addEditForm');
        const action = $('#actionType').val();
        const truck_id = $('#truckId').val();

        clearFormErrors('addEditForm');

        let isValid = true;
        let firstErrorField = null;

        function markError(fieldId, message) {
             $('#' + fieldId).addClass('error');
             $('#' + fieldId + 'Error').text(message).show();
             if (isValid && !firstErrorField) {
                 firstErrorField = $('#' + fieldId);
             }
             isValid = false;
         }

        const plate_number = $('#plateNumber').val().trim();
        const capacityKgInput = $('#capacityKg').val().trim();
        const truckModel = $('#truckModel').val().trim();
        const availabilityStatus = $('#availabilityStatus').val();


        if (!plate_number) {
             markError('plateNumber', 'Plate Number cannot be empty.');
        }

        let capacityValue = null;
        if (capacityKgInput !== '') {
             const parsedCapacity = parseInt(capacityKgInput);
            if (isNaN(parsedCapacity) || parsedCapacity < 0) {
                 markError('capacityKg', 'Capacity must be a non-negative number or left empty.');
            } else {
                 capacityValue = parsedCapacity;
            }
        }

        if (!availabilityStatus) {
             markError('availabilityStatus', 'Availability Status is required.');
        }


        if (!isValid) {
             if(firstErrorField) firstErrorField.focus();
            return;
        }


        let formData = {
            action: action,
            plate_number: plate_number,
            capacity_kg: capacityValue,
            model: truckModel,
            availability_status: availabilityStatus
        };

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
                        location.reload();
                     });
                } else {
                    if (response.errors) {
                        const fieldMap = {
                            'truck_id': 'truckId',
                            'plate_number': 'plateNumber',
                            'capacity_kg': 'capacityKg',
                            'model': 'truckModel',
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
  </script>

<?php
require_once 'templates/footer.php';

if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}
?>

</body>
</html>