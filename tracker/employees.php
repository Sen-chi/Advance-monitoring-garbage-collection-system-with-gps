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
        $employeeData = getAllEmployees($pdo);
    } catch (Exception $e) {
        $fetchError = 'Could not retrieve employee data: ' . $e->getMessage();
         error_log('Error in getAllEmployees: ' . $e->getMessage());
         $employeeData = [];
    }
    try {
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
                    <th>Role</th> <!-- ADDED ROLE COLUMN -->
                    <th>Contact Number</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($employeeData)): ?>
                    <?php foreach ($employeeData as $employee): ?>
                        <?php
                        $employeeId = htmlspecialchars($employee['employee_id'] ?? 'N/A');
                        $userId = htmlspecialchars($employee['user_id'] ?? 'N/A');
                        $username = htmlspecialchars($employee['username'] ?? 'N/A');
                        $firstName = htmlspecialchars($employee['first_name'] ?? 'N/A');
                        $middleName = htmlspecialchars($employee['middle_name'] ?? '');
                        $lastName = htmlspecialchars($employee['last_name'] ?? 'N/A');
                        $role = htmlspecialchars(ucfirst($employee['role'] ?? 'N/A')); // ADDED ROLE VARIABLE
                        $contactNumber = htmlspecialchars($employee['contact_number'] ?? '');
                        $employeeStatus = htmlspecialchars(ucfirst($employee['employee_status'] ?? 'N/A'));
                        ?>
                        <tr>
                            <td><?php echo $username; ?></td>
                            <td><?php echo $firstName; ?></td>
                            <td><?php echo $lastName; ?></td>
                            <td><?php echo $role; ?></td> <!-- DISPLAY ROLE -->
                            <td><?php echo $contactNumber; ?></td>
                            <td><?php echo $employeeStatus; ?></td>
                            <td class='action-buttons'>
                                <button class='edit-btn' onclick='openEditModal(
                                    <?php echo $employeeId; ?>, 
                                    <?php echo json_encode($userId); ?>, 
                                    <?php echo json_encode($firstName); ?>, 
                                    <?php echo json_encode($middleName); ?>, 
                                    <?php echo json_encode($lastName); ?>, 
                                    <?php echo json_encode($contactNumber); ?>, 
                                    <?php echo json_encode($employee['employee_status'] ?? ''); ?>
                                )'><i class='fas fa-edit'></i></button>
                                <button class='delete-btn' onclick='openDeleteConfirmModal(<?php echo $employeeId; ?>, <?php echo json_encode($firstName . ' ' . $lastName); ?>)'><i class='fas fa-trash'></i></button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="7" style="text-align:center;">No non-driver employee data found.</td></tr>
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

                <div id="userSelectContainer">
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

                <label for="firstName">First Name:</label>
                <input type="text" id="firstName" name="first_name" required>
                <span class="error-message" id="first_nameError"></span>

                <label for="middleName">Middle Name:</label>
                <input type="text" id="middleName" name="middle_name">
                <span class="error-message" id="middle_nameError"></span>

                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="last_name" required>
                <span class="error-message" id="last_nameError"></span>

                <label for="contactNumber">Contact Number:</label>
                <input type="text" id="contactNumber" name="contact_number">
                <span class="error-message" id="contact_numberError"></span>

                <label for="employeeStatus">Status:</label>
                <select id="employeeStatus" name="employee_status" required>
                     <option value="">-- Select Status --</option>
                     <option value="Active">Active</option>
                     <option value="Inactive">Inactive</option>
                     <option value="On Leave">On Leave</option>
                     <option value="Terminated">Terminated</option>
                </select>
                <span class="error-message" id="employee_statusError"></span>

                <button type="button" class="save-btn" onclick="submitEmployeeForm()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
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
                       { "orderable": false, "targets": 6 } // Actions column is now index 6
                    ]
                 });
            }
        });
        
        // --- ALL JAVASCRIPT FUNCTIONS (openModal, closeModal, submitEmployeeForm, etc.) ---
        // --- can remain EXACTLY THE SAME as in your original file. No changes needed. ---
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
                 $('#employeeId').val('');
                 $('#actionType').val('');
                 $('#originalUserId').val('');
                 clearFormErrors('addEditForm');
                 $('#userSelectContainer').show();
                 $('#userId').prop('disabled', false);
                 populateUserSelect(unassignedUsersData);
            }
            if (modalId === 'deleteConfirmModal') {
                $('#deleteEmployeeId').val('');
                $('#deleteConfirmText').text('Are you sure you want to delete this employee?');
            }
        }
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
            closeModal('addEditModal');
            $('#modalTitle').text('Add New Employee');
            $('#actionType').val('add');
            $('#employeeId').val('');
            $('#originalUserId').val('');
            $('#userSelectContainer').show();
            $('#userId').prop('disabled', false).val('');
            openModal('addEditModal');
            $('#userId').focus();
        }
        function openEditModal(id, userId, firstName, middleName, lastName, contactNumber, employeeStatus) {
            closeModal('addEditModal');
            $('#modalTitle').text('Edit Employee');
            $('#actionType').val('edit');
            $('#employeeId').val(id);
            $('#originalUserId').val(userId);
            $('#firstName').val(firstName);
            $('#middleName').val(middleName);
            $('#lastName').val(lastName);
            $('#contactNumber').val(contactNumber);
            $('#employeeStatus').val(employeeStatus);
            $('#userSelectContainer').hide();
            $('#userId').prop('disabled', true);
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
                                        $('#' + key + 'Error').text(message).show();
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
        function markError(fieldId, message){
            // Simplified helper for submitEmployeeForm
            $('#' + fieldId).addClass('error');
            let errorSpanId = $('#' + fieldId).attr('name') + 'Error';
            if (fieldId === 'userId') errorSpanId = 'userIdError';
            $('#' + errorSpanId).text(message).show();
            isValid = false;
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