<?php
$isFileManagement = true;
session_start();

$pageTitle = "Employees";

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

require_once("employeemodel.php");

$employeeData = [];
$unassignedUsers = [];
$fetchError = '';
$unassignedUsersError = '';

if (!$dbError && $pdo instanceof PDO) {
    try {
        // *** USE THE NEW UNIFIED FUNCTION ***
        $personnelData = getAllPersonnel($pdo);
    } catch (Exception $e) {
        $fetchError = 'Could not retrieve personnel data: ' . $e->getMessage();
         error_log('Error in getAllPersonnel: ' . $e->getMessage());
         $personnelData = [];
    }
    try {
        // This function is also updated in the model
        $unassignedUsers = getUnassignedUsers($pdo);
    } catch (Exception $e) {
         $unassignedUsersError = 'Could not retrieve unassigned users: ' . $e->getMessage();
         error_log('Error in getUnassignedUsers: ' . $e->getMessage());
         $unassignedUsers = [];
    }
} else if ($dbError) {
    $fetchError = $dbError;
    $unassignedUsersError = $dbError;
}

?>

<html>
    <head>

<style>
    /* --- DataTables Customization (Universal) --- */
    .dataTables_filter input[type="search"] {
        border-radius: 25px !important;
        border: 1px solid #ccc !important;
        padding: 6px 10px !important;
        outline: none !important;
    }

    .dataTables_length, .dataTables_filter {
        margin-bottom: 12px !important;
    }

    /* --- Modal Form Grid (Compressed) --- */
    .modal-form-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px; /* Mas dikit bawat input */
        margin-bottom: 8px;
    }

    .modal-form-grid .form-group {
        display: flex;
        flex-direction: column;
    }

    .modal-form-grid label {
        margin-bottom: 3px;
        font-weight: 500; /* Normal, hindi bold */
        font-size: 0.9em;
    }

    .modal-form-grid input[type="text"],
    .modal-form-grid select {
        padding: 5px 8px; /* Mas maliit */
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

    /* --- Buttons (Compressed + Aligned Right) --- */
    .modal-content .save-btn,
    .modal-content .cancel-btn {
        padding: 6px 14px; /* Mas maliit */
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-weight: normal; /* Not bold */
        font-size: 0.9em;
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

    /* --- Modal Box Compression --- */
    .modal-dialog {
        max-width: 600px; /* Compress width */
    }

    .modal-content {
        padding: 15px 20px; /* Mas maliit na loob */
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
        <div class="clearfix">
            <h2>Employees and Account Management</h2>
        </div>
        <hr>

<button class="add-user-btn" onclick="openAddModal()">
        <i class="fa-solid fa-user-plus"></i> Add New Employee
    </button>

    <?php if (!empty($fetchError)): ?>
        <div class='message error'><?php echo htmlspecialchars($fetchError); ?></div>
    <?php endif; ?>

    <?php if (!$fetchError): ?>
    <table id="employeeTable" class="display" style="width:100%">
        <thead>
            <tr>
                <th>Username</th>
                <th>First Name</th>
                <th>Last Name</th>
                <th>Type</th> <!-- ADDED COLUMN FOR TYPE -->
                <th>Role</th>
                <th>Contact Number</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
             <?php if (!empty($personnelData)): // CHANGED: Use the correct variable ?>
                <?php foreach ($personnelData as $person): // CHANGED: Use the correct variable and a generic name ?>
                    <?php
                    // CHANGED: Use the correct column names from the SQL UNION query
                    $personnelId = htmlspecialchars($person['personnel_id'] ?? 'N/A');
                    $personnelType = htmlspecialchars($person['personnel_type'] ?? 'N/A');
                    $userId = htmlspecialchars($person['user_id'] ?? 'N/A');
                    $username = htmlspecialchars($person['username'] ?? 'N/A');
                    $firstName = htmlspecialchars($person['first_name'] ?? 'N/A');
                    $middleName = htmlspecialchars($person['middle_name'] ?? '');
                    $lastName = htmlspecialchars($person['last_name'] ?? 'N/A');
                    $role = htmlspecialchars(ucfirst($person['role'] ?? 'N/A'));
                    $contactNumber = htmlspecialchars($person['contact_number'] ?? '');
                    $status = htmlspecialchars(ucfirst($person['status'] ?? 'N/A'));
                    
                    // This logic correctly disables buttons for non-staff
                    $isStaff = ($personnelType === 'Staff');
                    ?>
                    <tr>
                        <td><?php echo $username; ?></td>
                        <td><?php echo $firstName; ?></td>
                        <td><?php echo $lastName; ?></td>
                        <td><b><?php echo $personnelType; ?></b></td> <!-- DISPLAY THE TYPE -->
                        <td><?php echo $role; ?></td>
                        <td><?php echo $contactNumber; ?></td>
                        <td><?php echo $status; ?></td>
                        <td class='action-buttons'>
                            <?php if ($isStaff): ?>
                                <!-- Buttons for 'Staff' which are editable from this page -->
                                <button class='edit-btn' onclick='openEditModal(
                                    <?php echo $personnelId; ?>,
                                    <?php echo json_encode($userId); ?>,
                                    <?php echo json_encode($firstName); ?>,
                                    <?php echo json_encode($middleName); ?>,
                                    <?php echo json_encode($lastName); ?>,
                                    <?php echo json_encode($contactNumber); ?>,
                                    <?php echo json_encode($person['status'] ?? ''); ?>
                                )'><i class='fas fa-edit'></i></button>
                                <button class='delete-btn' onclick='openDeleteConfirmModal(<?php echo $personnelId; ?>, <?php echo json_encode($firstName . ' ' . $lastName); ?>)'><i class='fas fa-trash'></i></button>
                            <?php else: ?>
                                <!-- Display a message for Drivers/Assistants -->
                                <small><i>Not editable here</i></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                 <!-- CHANGED: Updated the "no data" message -->
                <tr><td colspan="8" style="text-align:center;">No personnel with user accounts found.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
    <?php endif; ?>

</div>

<!-- MODALS AND SCRIPT remain the same as your previous version, with minor JS adjustments -->
<div id="addEditModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
        <h3 id="modalTitle">Add/Edit Employee</h3>

        <form id="addEditForm" novalidate>
            <input type="hidden" id="employeeId" name="id">
            <input type="hidden" id="actionType" name="action">
            <input type="hidden" id="originalUserId" name="original_user_id">

            <div class="modal-form-grid">
                <div class="form-group" id="userSelectContainer">
                    <label for="userId">Associated User:</label>
                    <select id="userId" name="user_id" required>
                        <option value="">-- Select User --</option>
                        <?php if (!empty($unassignedUsers)): ?>
                            <?php foreach ($unassignedUsers as $user): ?>
                                <option value="<?php echo htmlspecialchars($user['user_id']); ?>"><?php echo htmlspecialchars($user['username']); ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                     <?php if (empty($unassignedUsers) && empty($unassignedUsersError)): ?>
                        <p class="message warning">No unassigned non-collector users found.</p>
                     <?php elseif (!empty($unassignedUsersError)): ?>
                         <p class="message error">Error loading users: <?php echo htmlspecialchars($unassignedUsersError); ?></p>
                     <?php endif; ?>
                    <span class="error-message" id="userIdError"></span>
                </div>

                <div class="form-group">
                    <label for="firstName">First Name:</label>
                    <input type="text" id="firstName" name="first_name" required>
                    <span class="error-message" id="first_nameError"></span>
                </div>

                <div class="form-group">
                    <label for="middleName">Middle Name:</label>
                    <input type="text" id="middleName" name="middle_name">
                    <span class="error-message" id="middle_nameError"></span>
                </div>

                <div class="form-group">
                    <label for="lastName">Last Name:</label>
                    <input type="text" id="lastName" name="last_name" required>
                    <span class="error-message" id="last_nameError"></span>
                </div>

                <div class="form-group">
                    <label for="contactNumber">Contact Number:</label>
                    <input type="text" id="contactNumber" name="contact_number">
                    <span class="error-message" id="contact_numberError"></span>
                </div>

                <div class="form-group">
                    <label for="employeeStatus">Status:</label>
                    <select id="employeeStatus" name="employee_status" required>
                         <option value="">-- Select Status --</option>
                         <option value="Active">Active</option>
                         <option value="Inactive">Inactive</option>
                         <option value="On Leave">On Leave</option>
                         <option value="Terminated">Terminated</option>
                    </select>
                    <span class="error-message" id="employee_statusError"></span>
                </div>
            </div> <!-- End of modal-form-grid -->

            <div class="modal-buttons">
                <button type="button" class="save-btn" onclick="submitEmployeeForm()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
            </div>
        </form>
    </div>
</div>

<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
        <h3>Confirm Deletion</h3>
        <p id="deleteConfirmText">Are you sure you want to delete this employee?</p>
        <input type="hidden" id="deleteEmployeeId">
        <button class="confirm-btn" onclick="confirmDeleteEmployee()">Yes, Delete</button>
        <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
    </div>
</div>

<script>
   let unassignedUsersData = <?php echo json_encode($unassignedUsers); ?>;

    $(document).ready(function() {
        const $tableBodyRows = $('#employeeTable tbody tr');
        const hasData = $tableBodyRows.length > 0 && $tableBodyRows.find('td').length > 1;

        if (hasData) {
             $('#employeeTable').DataTable({
                "order": [[ 1, "asc" ]],
                "columnDefs": [
                   { "orderable": false, "targets": 7 } // CHANGED: Actions column is now index 7
                ]
             });
        }
    });

    function clearFormErrors(formId) {
         $('#' + formId + ' .error-message').text('').hide();
         $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
    }

    // This function is not directly used in openAddModal/openEditModal but might be useful elsewhere
    function populateUserSelect(users) {
        const $select = $('#userId');
        $select.empty().append('<option value="">-- Select User --</option>');
        if (users && users.length > 0) {
            users.forEach(user => {
                $select.append(`<option value="${user.user_id}">${user.username}</option>`);
            });
            $('#userSelectContainer p.message').hide();
        } else {
             $('#userSelectContainer p.message.warning').show();
        }
     }
    function openAddModal() {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Add New Employee');
        $('#actionType').val('add');
        $('#employeeId').val('');
        $('#originalUserId').val('');
        $('#userSelectContainer').show(); // Show user selection for adding
        $('#userId').prop('disabled', false).val('');

        // Repopulate with unassigned users when adding
        const $userIdSelect = $('#userId');
        $userIdSelect.empty().append('<option value="">-- Select User --</option>');
        if (unassignedUsersData && unassignedUsersData.length > 0) {
            unassignedUsersData.forEach(user => {
                $userIdSelect.append(`<option value="${user.user_id}">${user.username}</option>`);
            });
             $('#userSelectContainer p.message').hide(); // Hide any previous warning/error
        } else {
             // Show warning if no users available for assignment
             if ($('#userSelectContainer p.message.warning').length === 0) {
                $('#userSelectContainer').append('<p class="message warning">No unassigned non-collector users found.</p>');
             } else {
                $('#userSelectContainer p.message.warning').show();
             }
        }


        openModal('addEditModal');
        $('#userId').focus();
    }
    function openEditModal(id, userId, firstName, middleName, lastName, contactNumber, employeeStatus) {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Edit Employee');
        $('#actionType').val('edit');
        $('#employeeId').val(id);
        $('#originalUserId').val(userId);
        $('#firstName').val(firstName);
        $('#middleName').val(middleName);
        $('#lastName').val(lastName);
        $('#contactNumber').val(contactNumber);
        $('#employeeStatus').val(employeeStatus);

        $('#userSelectContainer').hide(); // Hide user selection for editing
        $('#userId').prop('disabled', true); // Disable the select to prevent changes

        openModal('addEditModal');
        $('#firstName').focus();
    }
    function openDeleteConfirmModal(id, fullName) {
        $('#deleteEmployeeId').val(id);
        $('#deleteConfirmText').text('Are you sure you want to delete employee: ' + fullName + '?');
        openModal('deleteConfirmModal');
    }
    function confirmDeleteEmployee() {
        const id = $('#deleteEmployeeId').val();
        if (!id) {
            alert('Error: Employee ID not found.');
            return;
        }
        $.ajax({
            url: 'handle_employee_actions.php',
            type: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Employee deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting employee: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                alert('An AJAX error occurred: ' + status + '\nError: ' + error);
            },
            complete: function(){
                closeModal('deleteConfirmModal');
            }
        });
    }
    function submitEmployeeForm() {
        const form = $('#addEditForm');
        const action = $('#actionType').val();
        clearFormErrors('addEditForm');
        let isValid = true;

        function markError(fieldId, message){
            // Simplified helper for submitEmployeeForm
            let targetElement = $('#' + fieldId);
            if (!targetElement.length && fieldId === 'userId') {
                targetElement = $('#userSelectContainer select'); // Target the select element itself
            }
            targetElement.addClass('error');
            let errorSpanId = targetElement.attr('name') + 'Error';
            if (fieldId === 'userId') errorSpanId = 'userIdError'; // Ensure correct ID for userIdError
            $('#' + errorSpanId).text(message).show();
            isValid = false;
        }

        if (action === 'add' && !$('#userId').val()) { markError('userId', 'Please select an associated user.'); }
        if (!$('#firstName').val().trim()) { markError('firstName', 'First Name is required.'); }
        if (!$('#lastName').val().trim()) { markError('lastName', 'Last Name is required.'); }
        if (!$('#employeeStatus').val()) { markError('employeeStatus', 'Please select a status.'); }

        if (!isValid) return;

        $('.save-btn').prop('disabled', true).text('Saving...');
        $.ajax({
            url: 'handle_employee_actions.php',
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
                            let fieldId = key.replace(/_([a-z])/g, g => g[1].toUpperCase());
                            if(fieldId === 'userId' || key === 'user_id'){
                                $('#userId').addClass('error');
                                $('#userIdError').text(message).show();
                            } else {
                                const inputElement = $('[name="' + key + '"]');
                                if(inputElement.length){
                                    inputElement.addClass('error');
                                    // Use specific error ID if defined, otherwise derive
                                    const errorSpanToUse = $('#' + key + 'Error').length ? $('#' + key + 'Error') : $('#' + inputElement.attr('id') + 'Error');
                                    errorSpanToUse.text(message).show();
                                }
                            }
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