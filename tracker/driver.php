<?php
session_start(); 

$pageTitle = "Driver Management";

require_once 'templates/header.php'; 
require_once 'templates/sidebar.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1); 
error_reporting(E_ALL); 

$pdo = null; 
$dbError = '';
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
        throw new Exception("Failed to get a valid database connection object.");
    }
} catch (Exception $e) {
    $dbError = "Database Connection Error: " . $e->getMessage();
    error_log($dbError);
    $dbError = "Could not connect to the database. Please try again later.";
}

require_once("drivermodel.php"); 
require_once("truckmodel.php");  

$driverData = [];
$allTrucksForDropdown = []; 
$availableUsers = [];
$fetchError = '';

if (empty($dbError) && $pdo instanceof PDO) {
    try {
        $driverData = getAllDrivers($pdo);
        $allTrucksForDropdown = getAllTrucksForDriverDropdown($pdo);
        $availableUsers = getAvailableUserAccounts($pdo);

    } catch (Exception $e) {
         $fetchError = 'Could not retrieve data: ' . $e->getMessage();
         error_log('Error fetching data in driver.php: ' . $e->getMessage());
         $driverData = [];
         $allTrucksForDropdown = [];
         $availableUsers = [];
    }
} else if (!empty($dbError)) {
    $fetchError = $dbError;
}

?>
<html>
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?> - Your App Name</title>
</head>
<body>
    <div class="content">
        <h2>Driver Management</h2>
        <hr>

        <button class="add-user-btn" onclick="openAddDriverModal()">
            <i class="fa-solid fa-user-plus"></i> Add New Driver
        </button>

        <?php if (!empty($fetchError)): ?>
            <div class='message error'><?php echo htmlspecialchars($fetchError); ?></div>
        <?php endif; ?>

        <table id="driverTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Driver Name</th>
                    <th>Contact No</th>
                    <th>Assigned Truck</th>
                    <th>Linked Account</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($driverData) && !empty($driverData)): ?>
                    <?php foreach ($driverData as $driver): ?>
                        <?php
                            $driverId = htmlspecialchars($driver['driver_id'] ?? '');
                            $fullName = htmlspecialchars($driver['first_name'] ?? '');
                            if (!empty($driver['middle_name'])) {
                                $fullName .= ' ' . htmlspecialchars($driver['middle_name']);
                            }
                            $fullName .= ' ' . htmlspecialchars($driver['last_name'] ?? '');
                            $contactNo = htmlspecialchars($driver['contact_no'] ?? '');
                            $assignedTruck = htmlspecialchars($driver['plate_number'] ?? 'Unassigned');
                            $linkedAccount = htmlspecialchars($driver['username'] ?? 'Unassigned');
                            $status = htmlspecialchars(ucfirst($driver['status'] ?? ''));
                        ?>
                        <tr>
                            <td><?php echo $fullName; ?></td>
                            <td><?php echo $contactNo; ?></td>
                            <td><?php echo $assignedTruck; ?></td>
                            <td><?php echo $linkedAccount; ?></td>
                            <td><?php echo $status; ?></td>
                            <td class='action-buttons'>
                                <?php if (!empty($driverId) && is_numeric($driverId)): ?>
                                    <button class='edit-btn' onclick='openEditDriverModal(
                                        <?php echo $driverId; ?>, 
                                        <?php echo json_encode($driver['first_name'] ?? ''); ?>, 
                                        <?php echo json_encode($driver['middle_name'] ?? null); ?>, 
                                        <?php echo json_encode($driver['last_name'] ?? ''); ?>, 
                                        <?php echo json_encode($driver['contact_no'] ?? ''); ?>, 
                                        <?php echo json_encode($driver['truck_id'] ?? null); ?>, 
                                        <?php echo json_encode($driver['user_id'] ?? null); ?>, 
                                        <?php echo json_encode($driver['status'] ?? ''); ?>
                                    )'><i class='fas fa-edit'></i></button>
                                    <button class='delete-btn' onclick='openDeleteConfirmDriverModal(<?php echo $driverId; ?>, <?php echo json_encode($fullName); ?>)'><i class='fas fa-trash'></i></button>
                                <?php else: ?>
                                    Invalid Driver ID
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="6" style="text-align:center;">No driver data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

    </div>

    <!-- Add/Edit Driver Modal Form -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
            <h3 id="modalTitle">Add/Edit Driver</h3>

            <form id="addEditForm" novalidate>
                <input type="hidden" id="driverId" name="id">
                <input type="hidden" id="actionType" name="action">

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

                <label for="assignedTruckId">Assigned Truck:</label>
                <select id="assignedTruckId" name="truck_id"></select>
                <span class="error-message" id="assignedTruckIdError"></span>

                <label for="userId">Link to User Account:</label>
                <select id="userId" name="user_id"></select>
                <span class="error-message" id="userIdError"></span>

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
            <input type="hidden" id="deleteDriverId">
            <button class="confirm-btn" onclick="confirmDeleteDriver()">Yes, Delete</button>
            <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
        </div>
    </div>

    <script>
        var allTrucks = <?php echo json_encode($allTrucksForDropdown); ?>;
        var availableUsers = <?php echo json_encode($availableUsers); ?>;

        $(document).ready(function() {
            if ($('#driverTable tbody tr').length > 0 && $('#driverTable tbody tr:first td').length > 1) {
                 $('#driverTable').DataTable({
                    "order": [[ 0, "asc" ]],
                    "columnDefs": [
                       { "orderable": false, "targets": 5 }
                    ]
                 });
            }
        });

        function clearFormErrors(formId) {
             $('#' + formId + ' .error-message').text('').hide();
             $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
        }

        function openModal(modalId) {
            $('#' + modalId).css('display', 'flex');
        }

        function closeModal(modalId) {
             $('#' + modalId).css('display', 'none');
            if (modalId === 'addEditModal') {
                 $('#addEditForm')[0].reset();
                 $('#driverId').val('');
                 $('#actionType').val('');
                 clearFormErrors('addEditForm');
            }
            if (modalId === 'deleteConfirmModal') {
                $('#deleteDriverId').val('');
                $('#deleteConfirmText').text('Are you sure you want to delete this driver?');
            }
        }

        function populateTruckDropdown(currentTruckId = null) {
            const $select = $('#assignedTruckId');
            $select.empty().append($('<option>', { value: '', text: '-- Unassigned --' }));

            allTrucks.forEach(function(truck) {
                if (truck.availability_status === 'Available' || (currentTruckId !== null && truck.truck_id == currentTruckId)) {
                    const option = $('<option>', {
                        value: truck.truck_id,
                        text: truck.plate_number + (truck.truck_id == currentTruckId ? ' (Current)' : '')
                    });
                    if (currentTruckId !== null && truck.truck_id == currentTruckId) {
                        option.prop('selected', true);
                    }
                    $select.append(option);
                }
            });
        }
        
        function populateUserDropdown(currentUserId = null) {
            const $select = $('#userId');
            $select.empty().append($('<option>', { value: '', text: '-- Unassigned --' }));

            let currentUserInList = false;
            availableUsers.forEach(function(user) {
                const option = $('<option>', { value: user.user_id, text: user.username });
                if (currentUserId !== null && user.user_id == currentUserId) {
                    option.prop('selected', true);
                    currentUserInList = true;
                }
                $select.append(option);
            });
            
            // This part is a failsafe for editing. If a driver's assigned user account is no longer 'available' 
            // (e.g., role changed), this ensures they still show up in the dropdown for the edit view.
            if(currentUserId && !currentUserInList){
                // We'd need an AJAX call to get this user's details. For simplicity,
                // the PHP model function already includes the current user, making this less necessary.
            }
        }

        function openAddDriverModal() {
            closeModal('addEditModal');
            $('#modalTitle').text('Add New Driver');
            $('#actionType').val('add');
            $('#driverId').val('');
            $('#status').val('active');

            populateTruckDropdown();
            populateUserDropdown();

            openModal('addEditModal');
            $('#firstName').focus();
        }

        function openEditDriverModal(id, firstName, middleName, lastName, contactNo, currentTruckId, currentUserId, status) {
            closeModal('addEditModal');
            $('#modalTitle').text('Edit Driver');
            $('#actionType').val('edit');
            $('#driverId').val(id);

            $('#firstName').val(firstName);
            $('#middleName').val(middleName);
            $('#lastName').val(lastName);
            $('#contactNo').val(contactNo);
            $('#status').val(status);

            populateTruckDropdown(currentTruckId);
            populateUserDropdown(currentUserId);
            
            $('#assignedTruckId').val(currentTruckId === null ? '' : currentTruckId);
            $('#userId').val(currentUserId === null ? '' : currentUserId);

            openModal('addEditModal');
        }

        function openDeleteConfirmDriverModal(id, driverName) {
            $('#deleteDriverId').val(id);
            $('#deleteConfirmText').text('Are you sure you want to delete driver: ' + driverName + '?');
            openModal('deleteConfirmModal');
        }

        function confirmDeleteDriver() {
            const id = $('#deleteDriverId').val();
            if (!id) {
                alert('Error: Driver ID not found.');
                return;
            }

            $.ajax({
                url: 'handle_driver_actions.php',
                type: 'POST',
                data: { action: 'delete', id: id },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Driver deleted successfully!');
                        location.reload();
                    } else {
                        alert('Error deleting driver: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                    alert('An AJAX error occurred: ' + status + '\nError: ' + error);
                },
                complete: function() {
                    closeModal('deleteConfirmModal');
                }
            });
        }

        function submitDriverForm() {
            const form = $('#addEditForm');
            clearFormErrors('addEditForm');
            let isValid = true;

            if (!$('#firstName').val().trim()) { isValid = false; $('#firstNameError').text('First name is required.').show(); }
            if (!$('#lastName').val().trim()) { isValid = false; $('#lastNameError').text('Last name is required.').show(); }
            if (!$('#contactNo').val().trim()) { isValid = false; $('#contactNoError').text('Contact number is required.').show(); }
            if (!$('#status').val()) { isValid = false; $('#statusError').text('Please select a status.').show(); }

            if (!isValid) return;

            $('.save-btn').prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'handle_driver_actions.php',
                type: 'POST',
                data: new FormData(form[0]),
                processData: false,
                contentType: false,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Operation successful!');
                        location.reload();
                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(key, message) {
                                $('#' + key + 'Error').text(message).show();
                            });
                        } else {
                             alert('Error: ' + (response.message || 'Operation failed.'));
                        }
                    }
                },
                error: function(xhr, status, error) {
                     alert('An AJAX error occurred: ' + status + '\nError: ' + error);
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