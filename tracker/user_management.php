<?php
session_start();

$pageTitle = "User management";

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

require_once("usermodel.php");

$userData = [];
$fetchError = '';
if (!$dbError && $pdo instanceof PDO) {
    try {
        $userData = getAllUsers($pdo);
        if ($userData === false) {
             throw new Exception('The getAllUsers function returned false, indicating an error.');
        }
    } catch (Exception $e) {
         $fetchError = 'Could not retrieve user data: ' . $e->getMessage();
         error_log('Error in getAllUsers: ' . $e->getMessage());
         $userData = [];
    }
} else if ($dbError) {
    $fetchError = $dbError;
}

?>

<html>
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">
    <link rel="stylesheet" href="css/user.css">

 <style>
    .switch {
      position: relative;
      display: inline-block;
      width: 40px;
      height: 20px;
    }
    .switch input {
      opacity: 0;
      width: 0;
      height: 0;
    }
    .slider {
      position: absolute;
      cursor: pointer;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background-color: #ccc;
      -webkit-transition: .4s;
      transition: .4s;
      border-radius: 20px;
    }
    .slider:before {
      position: absolute;
      content: "";
      height: 16px;
      width: 16px;
      left: 2px;
      bottom: 2px;
      background-color: white;
      -webkit-transition: .4s;
      transition: .4s;
      border-radius: 50%;
    }
    input:checked + .slider {
      background-color:rgb(23, 207, 44);
    }
    input:focus + .slider {
      box-shadow: 0 0 1px rgb(23, 207, 44);
    }
    input:checked + .slider:before {
      -webkit-transform: translateX(20px);
      -ms-transform: translateX(20px);
      transform: translateX(20px);
    }
    .slider.round {
      border-radius: 20px;
    }
    .slider.round:before {
      border-radius: 50%;
    }
    .status-cell {
         text-align: center;
         vertical-align: middle;
    }
    .content {
        margin-left: 250px;
        padding: 20px;
    }
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }
    table.dataTable {
         width: 100% !important;
         margin-top: 20px !important;
    }
    .error-message {
         color: red;
         font-size: 0.9em;
         margin-top: -10px;
         margin-bottom: 10px;
         display: block;
    }
    input.error, select.error {
         border-color: red !important;
    }
    .password-note {
         font-size: 0.8em;
         color: #666;
         margin-top: -10px;
         margin-bottom: 10px;
    }
    .message {
         padding: 10px;
         margin-bottom: 15px;
         border-radius: 4px;
         font-weight: bold;
    }
    .success {
         background-color: #d4edda;
         color: #155724;
         border: 1px solid #c3e6cb;
    }
    .error {
         background-color: #f8d7da;
         color: #721c24;
         border: 1px solid #f5c6cb;
    }

        /* --- DataTables Customization (Universal) --- */

        /* 1. Makes the search box rounded on ANY table */
        .dataTables_filter input[type="search"] {
            border-radius: 25px !important;
            border: 1px solid #ccc !important;
            padding: 8px 12px !important;
            outline: none !important;
        }

        /* 2. Adds space below the "Show entries" dropdown and the search box */
        .dataTables_length, .dataTables_filter {
            margin-bottom: 20px !important;
        }
</style>

</head>
<body>
    <div class="content">
        <div class="clearfix">
            <h2>User Accounts Management</h2>
             <button class="add-user-btn" onclick="openAddModal()">
                 <i class="fa-solid fa-user-plus"></i> Add New User
             </button>
        </div>
        <hr>

<?php
if (isset($_GET['status']) && $_GET['status'] == 'user_added_success') {
    echo "<div class='message success'>User added successfully!</div>";
}
if (!empty($fetchError)) {
    echo "<div class='message error'>" . htmlspecialchars($fetchError) . "</div>";
}
?>

<?php if (!$fetchError): ?>

<table id="userTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th class="status-cell">Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($userData)) {
            foreach ($userData as $user) {
                $userId = htmlspecialchars($user['user_id'] ?? 'N/A');
                $username = htmlspecialchars($user['username'] ?? 'N/A');
                $email = htmlspecialchars($user['email'] ?? 'N/A');
                $role = htmlspecialchars(ucfirst($user['role'] ?? 'N/A'));
                $status = htmlspecialchars($user['status'] ?? 'inactive');

echo "<tr>";
            echo "<td>" . $username . "</td>";
            echo "<td>" . $email . "</td>";
            echo "<td>" . $role . "</td>";
            echo "<td class='status-cell'>";
            echo "<label class='switch'>";
            echo "<input type='checkbox' class='status-toggle' "
               . "data-user-id='" . $userId . "' "
               . "data-current-status='" . $status . "' "
               . ($status === 'active' ? 'checked' : '') . ">";
            echo "<span class='slider round'></span>";
            echo "</label>";
            echo "</td>";
            echo "<td class='action-buttons'>";
            echo "<button class='edit-btn' onclick='openEditModal("
                 . $userId . ", "
                  . json_encode($username) . ", "
                  . json_encode($email) . ", "
                  . json_encode($user['role'] ?? '')
                 . ")'><i class='fas fa-edit'></i></button>";
            echo "<button class='delete-btn' onclick='openDeleteConfirmModal(" . $userId . ", " . json_encode($username) . ")'><i class='fas fa-trash'></i></button>";
            echo "</td>";
            echo "</tr>";
        }
    } else {
        echo '<tr><td colspan="5" style="text-align:center;">No user data found.</td></tr>';
    }
    ?>
</tbody>
</table>
<?php endif; ?>
</div>

<div id="addEditModal" class="modal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
    <h3 id="modalTitle">Add/Edit User</h3>

    <form id="addEditForm" novalidate>
  <input type="hidden" id="userId" name="id">
  <input type="hidden" id="actionType" name="action">

  <div class="form-grid compact">
    <div class="form-column">
      <label for="username">Username:</label>
      <input type="text" id="username" name="username" required>
      <span class="error-message" id="usernameError"></span>
    </div>

    <div class="form-column">
      <label for="email">Email:</label>
      <input type="email" id="email" name="email" required>
      <span class="error-message" id="emailError"></span>
    </div>

    <div class="form-column">
      <label for="password">Password:</label>
      <div class="password-input-container">
        <input type="password" id="password" name="password">
        <span class="toggle-password" onclick="togglePasswordVisibility('password')">
            <i class="fas fa-eye"></i>
        </span>
      </div>
      <span class="error-message" id="passwordError"></span>
      <p class="password-note" id="password-note" style="display: none;"></p>
    </div>

     <div class="form-column">
      <label for="role">Role:</label>
      <select id="role" name="role" required>
        <option value="">-- Select Role --</option>
        <option value="admin">Admin</option>
        <option value="collector">Collector</option>
      </select>
      <span class="error-message" id="roleError"></span>
    </div>

    <div class="form-column confirm-password-container">
        <label for="confirm_password">Confirm Password:</label>
        <div class="password-input-container">
            <input type="password" id="confirm_password" name="confirm_password" required>
            <span class="toggle-password" onclick="togglePasswordVisibility('confirm_password')">
                <i class="fas fa-eye"></i>
            </span>
        </div>
        <span class="error-message" id="confirm_passwordError"></span> 
    </div> 

    
  <div class="form-buttons"> 
    <button type="button" class="save-btn" onclick="submitUserForm()">Save</button>
    <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
  </div>
</form>
</div>
</div>

<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
        <h3>Confirm Deletion</h3>
        <p id="deleteConfirmText">Are you sure you want to delete this user?</p>
        <input type="hidden" id="deleteUserId">
        <button class="confirm-btn" onclick="confirmDeleteUser()">Yes, Delete</button>
        <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
    </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>
<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    $(document).ready(function() {
        const $userTableTbody = $('#userTable tbody');
        if ($userTableTbody.find('tr').length > 0 && $userTableTbody.find('td[colspan]').length === 0) {
             $('#userTable').DataTable({
                "order": [[ 0, "asc" ]],
                "columnDefs": [
                   { "orderable": false, "targets": [3, 4] }
                ]
             });
        }

        $('#addEditModal #password').on('input', function() {
            const $passwordField = $(this);
            const $confirmPasswordField = $('#confirmPassword');
            const $confirmPasswordLabel = $confirmPasswordField.prev('label');
            const actionType = $('#actionType').val();

            if (actionType === 'edit') {
                if ($passwordField.val()) {
                    $confirmPasswordLabel.show();
                    $confirmPasswordField.show();
                } else {
                    $confirmPasswordLabel.hide();
                    $confirmPasswordField.hide().val('');
                    $('#confirmPasswordError').text('').hide();
                }
            }
        });
    });

    function clearFormErrors(formId) {
         $('#' + formId + ' .error-message').text('').hide();
         $('#' + formId + ' input, #' + formId + ' select').each(function() {
             if ($(this).length) {
                 $(this).removeClass('error');
             }
         });
    }

    function openAddModal() {
        // Reset form state before opening
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        
        $('#modalTitle').text('Add New User');
        $('#actionType').val('add');
        $('#userId').val('');
        $('#username').val('');
        $('#email').val('');
        $('#password').val('');
        $('#confirmPassword').val('');
        $('#role').val('');
        $('#password').prop('required', true);
        $('#confirmPassword').show().prev('label').show();
        $('#password-note').text('Password is required for new users (min 6 characters recommended).');
        
        openModal('addEditModal');
        $('#username').focus();
    }

    function openEditModal(id, username, email, role) {
        // Reset form state before opening
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');

        $('#modalTitle').text('Edit User');
        $('#actionType').val('edit');
        $('#userId').val(id);
        $('#username').val(username);
        $('#email').val(email);
        $('#role').val(role);
        $('#password').val('');
        $('#password').prop('required', false);
        $('#confirmPassword').val('').hide().prev('label').hide();
        $('#password-note').text('Leave blank to keep the current password.');
        
        openModal('addEditModal');
        $('#username').focus();
    }

     // Function to toggle password visibility
    function togglePasswordVisibility(fieldId) {
        const passwordField = document.getElementById(fieldId);
        const toggleIcon = passwordField.nextElementSibling.querySelector('i'); 

        if (passwordField.type === 'password') {
            passwordField.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordField.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    }


    function openDeleteConfirmModal(id, username) {
        $('#deleteUserId').val(id);
        $('#deleteConfirmText').text('Are you sure you want to delete user: ' + username + '?');
        openModal('deleteConfirmModal');
    }

    function confirmDeleteUser() {
        const id = $('#deleteUserId').val();
        if (!id) {
            alert('Error: User ID not found.');
            return;
        }

        $.ajax({
            url: 'handle_user_actions.php',
            type: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'User deleted successfully!');
                    closeModal('deleteConfirmModal');
                    location.reload();
                } else {
                    alert('Error deleting user: ' + (response.message || 'Unknown error'));
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

    function submitUserForm() {
        const form = $('#addEditForm');
        const action = $('#actionType').val();
        clearFormErrors('addEditForm');
        let isValid = true;
        let firstErrorField = null;

        function markError(fieldId, message) {
            const $field = $('#' + fieldId);
            const $errorSpan = $('#' + fieldId + 'Error');
            if ($field.length) {
                $field.addClass('error');
                if ($errorSpan.length) {
                    $errorSpan.text(message).show();
                }
                if (isValid) { firstErrorField = $field; }
                isValid = false;
            } else {
                console.warn("Validation attempting to mark non-existent field:", fieldId);
            }
        }

        if (!$('#username').val().trim()) { markError('username', 'Username is required.'); }
        const emailVal = $('#email').val().trim();
        if (!emailVal) {
             markError('email', 'Email is required.');
        } else {
             const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
             if (!emailPattern.test(emailVal)) {
                 markError('email', 'Please enter a valid email address.');
             }
        }
        if (!$('#role').val()) { markError('role', 'Please select a role.'); }

        const passwordVal = $('#password').val();
        const confirmPasswordVal = $('#confirmPassword').val();
        if (action === 'add') {
            if (!passwordVal) { markError('password', 'Password is required for new users.'); }
            else if (passwordVal.length < 6) { markError('password', 'Password must be at least 6 characters long.'); }
            if (!confirmPasswordVal) { markError('confirmPassword', 'Confirm password is required.'); }
            else if (passwordVal !== confirmPasswordVal) { markError('confirmPassword', 'Passwords do not match.'); }
        } else if (action === 'edit' && passwordVal) {
            if (passwordVal.length < 6) { markError('password', 'Password must be at least 6 characters long.'); }
            if ($('#confirmPassword').is(':visible')) {
                if (!confirmPasswordVal) { markError('confirmPassword', 'Confirm password is required when changing password.'); }
                else if (passwordVal !== confirmPasswordVal) { markError('confirmPassword', 'Passwords do not match.'); }
            }
        }

        if (!isValid) {
            if (firstErrorField) firstErrorField.focus();
            return;
        }

        let formData = new FormData(form[0]);
        $('.save-btn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'handle_user_actions.php',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Operation successful!');
                    closeModal('addEditModal');
                    location.reload();
                } else {
                    if (response.errors) {
                        clearFormErrors('addEditForm');
                        let serverFirstErrorField = null;
                        $.each(response.errors, function(key, message) {
                            markError(key, message);
                            if (!serverFirstErrorField && $('#' + key).length) {
                                serverFirstErrorField = $('#' + key);
                            }
                        });
                        if (serverFirstErrorField) { serverFirstErrorField.focus(); }
                    } else {
                        alert('Error: ' + (response.message || 'Operation failed. Please check the details.'));
                    }
                }
            },
            error: function(xhr, status, error) {
                 alert('An AJAX error occurred: ' + status + '\nError: ' + error);
                 console.error("AJAX Error:", xhr.responseText);
            },
            complete: function() {
                 $('.save-btn').prop('disabled', false).text('Save');
            }
        });
    }

    $('#userTable tbody').on('change', '.status-toggle', function() {
        const $toggle = $(this);
        const userId = $toggle.data('userId');
        const newStatus = $toggle.is(':checked') ? 'active' : 'inactive';

        if (!userId) {
            alert('Error: User ID not found for status toggle.');
            $toggle.prop('checked', !$toggle.is(':checked'));
            return;
        }
        $toggle.prop('disabled', true);

        $.ajax({
            url: 'handle_user_actions.php',
            type: 'POST',
            data: { action: 'toggle_status', id: userId, status: newStatus },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $toggle.data('current-status', newStatus);
                } else {
                    alert('Failed to update status: ' + (response.message || 'Unknown error'));
                    $toggle.prop('checked', !$toggle.is(':checked'));
                    $toggle.data('current-status', $toggle.is(':checked') ? 'active' : 'inactive');
                }
            },
            error: function(xhr, status, error) {
                alert('An AJAX error occurred while updating status: ' + status + '\nError: ' + error);
                console.error("AJAX Error:", xhr.responseText);
                $toggle.prop('checked', !$toggle.is(':checked'));
                $toggle.data('current-status', $toggle.is(':checked') ? 'active' : 'inactive');
            },
            complete: function() {
                $toggle.prop('disabled', false);
            }
        });
    });
</script>

<?php
require_once 'templates/footer.php';

if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}
?>

</body>
</html>