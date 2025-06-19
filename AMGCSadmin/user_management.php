<?php
session_start(); // Start the session at the very beginning

// Define the page title *before* including the header
$pageTitle = "User management";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
// Don't require footer yet, it's at the end of the HTML

// Enable error reporting for debugging (remove or adjust for production)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

// 2. INCLUDE THE MODEL FILE
require_once("usermodel.php"); // Make sure this file exists and defines the functions

// 3. CALL THE FUNCTION FROM THE MODEL (Only for initial load)
$userData = []; // Initialize empty
$fetchError = '';
if (!$dbError && $pdo instanceof PDO) { // Only fetch if DB connection is okay
    try {
        $userData = getAllUsers($pdo); // Assumes getAllUsers returns an array or throws Exception
        if ($userData === false) { // Or check if it's explicitly false if the function returns that on error
             throw new Exception('The getAllUsers function returned false, indicating an error.');
        }
    } catch (Exception $e) {
         $fetchError = 'Could not retrieve user data: ' . $e->getMessage();
         error_log('Error in getAllUsers: ' . $e->getMessage()); // Log detailed error
         $userData = []; // Ensure it's an empty array for the loop
    }
} else if ($dbError) {
    $fetchError = $dbError; // Use the DB connection error message
}

?>

<html>
<head>
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <!-- Include your CSS files here -->
    <link rel="stylesheet" href="css/style.css">
    <!-- Include Font Awesome if you're using icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
     <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/jquery.dataTables.min.css">

<!-- Add the following CSS for the toggle switch -->
 <style>
    /* --- Toggle Switch Styles --- */
    .switch {
      position: relative;
      display: inline-block;
      width: 40px; /* Adjust width as needed */
      height: 20px; /* Adjust height as needed */
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
      background-color: #ccc; /* Default background (inactive) */
      -webkit-transition: .4s;
      transition: .4s;
      border-radius: 20px; /* Makes the slider round */
    }

    .slider:before {
      position: absolute;
      content: "";
      height: 16px; /* Adjust size */
      width: 16px; /* Adjust size */
      left: 2px; /* Adjust position */
      bottom: 2px; /* Adjust position */
      background-color: white;
      -webkit-transition: .4s;
      transition: .4s;
      border-radius: 50%; /* Makes the circle round */
    }

    input:checked + .slider {
      background-color:rgb(23, 207, 44); /* Active background color */
    }

    input:focus + .slider {
      box-shadow: 0 0 1px rgb(23, 207, 44);
    }

    input:checked + .slider:before {
      -webkit-transform: translateX(20px); /* Move the circle */
      -ms-transform: translateX(20px);
      transform: translateX(20px);
    }

    /* Optional: Rounded sliders */
    .slider.round {
      border-radius: 20px; /* Same as height/2 + padding */
    }

    .slider.round:before {
      border-radius: 50%;
    }

    /* Adjust layout in the table cell */
     .status-cell {
         text-align: center; /* Center the toggle */
         vertical-align: middle; /* Vertically align in cell */
     }

    /* --- Other styles you already have --- */
    .content {
        margin-left: 250px; /* Adjust based on your sidebar width */
        padding: 20px;
    }
    .clearfix::after {
        content: "";
        clear: both;
        display: table;
    }

     table.dataTable {
         width: 100% !important; /* Ensure DataTables uses full width */
         margin-top: 20px !important; /* Add space above table */
     }


     .error-message {
         color: red;
         font-size: 0.9em;
         margin-top: -10px; /* Pull up closer to the field */
         margin-bottom: 10px;
         display: block; /* Ensure it takes up space */
     }
     input.error, select.error {
         border-color: red !important; /* Override default border */
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


</style>

</head>
<body>
    <div class="content">
        <div class="clearfix"> <!-- Wrap header and button to clear float -->
            <h2>User Management</h2>
             <button class="add-user-btn" onclick="openAddModal()">
                 <i class="fa-solid fa-user-plus"></i> Add New User
             </button>
        </div>
        <hr>

<?php
// Display success message from redirect (if any - though AJAX is preferred now)
if (isset($_GET['status']) && $_GET['status'] == 'user_added_success') {
    echo "<div class='message success'>User added successfully!</div>";
}
// Display fetch errors, if any
if (!empty($fetchError)) {
    echo "<div class='message error'>" . htmlspecialchars($fetchError) . "</div>";
}
?>

<?php if (!$fetchError): // Only show table if there wasn't a fatal fetch error ?>
<table id="userTable" class="display" style="width:100%">
    <thead>
        <tr>
            <th>Username</th>
            <th>Email</th>
            <th>Role</th>
            <th class="status-cell">Status</th> <!-- Use the class for alignment -->
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php
        if (!empty($userData)) {
            foreach ($userData as $user) {
                // Escape all output
                $userId = htmlspecialchars($user['user_id'] ?? 'N/A');
                $username = htmlspecialchars($user['username'] ?? 'N/A');
                $email = htmlspecialchars($user['email'] ?? 'N/A');
                $role = htmlspecialchars(ucfirst($user['role'] ?? 'N/A')); // Capitalize
                $status = htmlspecialchars($user['status'] ?? 'inactive'); // Default to inactive

                echo "<tr>";
                echo "<td>" . $username . "</td>";
                echo "<td>" . $email . "</td>";
                echo "<td>" . $role . "</td>";

                // --- Status Toggle Cell ---
                echo "<td class='status-cell'>";
                // Use a label wrapping the input for better accessibility
                echo "<label class='switch'>";
                // Checkbox input - checked if status is 'active'
                // Add data attributes for user ID and current status
                echo "<input type='checkbox' class='status-toggle' "
                   . "data-user-id='" . $userId . "' "
                   . "data-current-status='" . $status . "' "
                   . ($status === 'active' ? 'checked' : '') . ">"; // Set checked state
                echo "<span class='slider round'></span>"; // The visual slider part
                echo "</label>";
                echo "</td>";
                // --- End Status Toggle Cell ---


                // Add Edit and Delete buttons with data attributes passed to JS
                echo "<td class='action-buttons'>";
                // Pass necessary data for editing to the JS function
                echo "<button class='edit-btn' onclick='openEditModal("
                     . $userId . ", "
                      . json_encode($username) . ", " // Use json_encode for safe JS string passing
                      . json_encode($email) . ", "
                      . json_encode($user['role'] ?? '') // Pass the role here
                     . ")'><i class='fas fa-edit'></i></button>";
                echo "<button class='delete-btn' onclick='openDeleteConfirmModal(" . $userId . ", " . json_encode($username) . ")'><i class='fas fa-trash'></i></button>"; // Pass ID and username
                echo "</td>";
                echo "</tr>";
            }
        } else { // Only show "No users found" if there wasn't a fetch error
            echo '<tr><td colspan="5" style="text-align:center;">No user data found.</td></tr>';
        }
        ?>
    </tbody>
</table>
<?php endif; ?>
</div> <!-- Content div closed -->

<!-- Add/Edit User Modal Form -->

<div id="addEditModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
        <h3 id="modalTitle">Add/Edit User</h3> <!-- Dynamic Title -->

<form id="addEditForm" novalidate> <!-- Disable browser validation, we use JS/Server -->
        <input type="hidden" id="userId" name="id"> <!-- Hidden field for ID (used for editing) -->
        <input type="hidden" id="actionType" name="action"> <!-- Hidden field for action (add/edit) -->

        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <span class="error-message" id="usernameError"></span>

        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
         <span class="error-message" id="emailError"></span>

        <label for="role">Role:</label>
        <select id="role" name="role" required>
             <option value="">-- Select Role --</option>
             <option value="admin">Admin</option>
             <option value="collector">Collector</option>
             <!-- Add other options here if needed in the future -->
        </select>
         <span class="error-message" id="roleError"></span>

        <label for="password">Password:</label>
        <input type="password" id="password" name="password"> <!-- Required attribute set by JS -->
        <p id="password-note" class="password-note">Password details</p>
        <span class="error-message" id="passwordError"></span>
        <!-- Optional: Add Confirm Password field for adding -->

        <label for="confirmPassword">Confirm Password:</label>
        <input type="password" id="confirmPassword" name="confirmPassword">
        <span class="error-message" id="confirmPasswordError"></span>

        <?php
        // The status field was removed from the modal as per previous state,
        // status is now controlled by the toggle in the table row.
        // No change needed here.
        ?>


        <button type="button" class="save-btn" onclick="submitUserForm()">Save</button>
        <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
    </form>
</div>
</div>

<!-- Delete Confirmation Modal (Keep this as is) -->

<div id="deleteConfirmModal" class="modal">
    <div class="modal-content">
        <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
        <h3>Confirm Deletion</h3>
        <p id="deleteConfirmText">Are you sure you want to delete this user?</p>
        <input type="hidden" id="deleteUserId"> <!-- Hidden field to store ID for deletion -->
        <button class="confirm-btn" onclick="confirmDeleteUser()">Yes, Delete</button>
        <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
    </div>
</div>

<!-- Include jQuery and DataTables JS -->

<script src="https://code.jquery.com/jquery-3.7.0.js"></script>

<script src="https://cdn.datatables.net/1.13.7/js/jquery.dataTables.min.js"></script>

<script>
    // --- DataTables Initialization ---
    $(document).ready(function() {
        // Check if the table exists and has rows before initializing DataTable
        // DataTables requires at least one row with actual data, not just the colspan message
        const $userTableTbody = $('#userTable tbody');
         // Check if there's at least one TR and the first TD's colspan is not 5 (meaning no data)
        if ($userTableTbody.find('tr').length > 0 && $userTableTbody.find('td[colspan]').length === 0) {
             $('#userTable').DataTable({
                "order": [[ 0, "asc" ]], // Order by Username ascending
                "columnDefs": [
                   { "orderable": false, "targets": [3, 4] } // Disable sorting on Status and Actions columns
                ]
             });
        }
        // else: Don't initialize if table is empty or just has the "No data" message

        // --- Event listener for dynamic Confirm Password field in Edit mode ---
        $('#addEditModal #password').on('input', function() {
            const $passwordField = $(this);
            const $confirmPasswordField = $('#confirmPassword');
            // Get the label element immediately preceding the confirm password input
            const $confirmPasswordLabel = $confirmPasswordField.prev('label');
            const actionType = $('#actionType').val(); // Get the current action type ('add' or 'edit')

            // This logic applies ONLY when the modal is in 'edit' mode
            if (actionType === 'edit') {
                if ($passwordField.val()) { // If the password field is NOT empty
                    $confirmPasswordLabel.show();
                    $confirmPasswordField.show();
                } else { // If the password field IS empty
                    $confirmPasswordLabel.hide();
                    $confirmPasswordField.hide().val(''); // Hide the field and clear its value
                    // Also clear any validation errors specifically for confirm password
                    $('#confirmPasswordError').text('').hide();
                }
            }
            // In 'add' mode, openAddModal already ensures confirm password is shown
        });
         // --- End Event listener ---

    });

    // --- General Utility Functions ---
    function clearFormErrors(formId) {
         $('#' + formId + ' .error-message').text('').hide();
         // Only remove error class from elements that exist
         $('#' + formId + ' input, #' + formId + ' select').each(function() {
             if ($(this).length) { // Check if the element exists before removing class
                 $(this).removeClass('error');
             }
         });
    }

    // --- Modal Control Functions ---
    function openModal(modalId) {
        $('#' + modalId).css('display', 'flex');
    }

    function closeModal(modalId) {
         $('#' + modalId).css('display', 'none');
        // Clear form fields and errors when closing Add/Edit modal
        if (modalId === 'addEditModal') {
             // Reset the form elements - this often clears most fields
             $('#addEditForm')[0].reset();
             // Explicitly clear hidden fields and potentially other fields for certainty
             $('#userId').val('');        // Clear hidden ID
             $('#actionType').val('');    // Clear hidden action type
             $('#role').val('');          // Clear the role select

             $('#password').prop('required', false); // Ensure password is not required by default on close
             clearFormErrors('addEditForm'); // Clear any previous validation errors
              $('#password-note').text('Password details'); // Reset password note
              // Ensure confirm password is hidden and its value is cleared on *any* modal close
              // This resets the state for the next open (add or edit)
              $('#confirmPassword').hide().prev('label').hide();
              $('#confirmPassword').val('');
        }
        if (modalId === 'deleteConfirmModal') {
            $('#deleteUserId').val('');
            $('#deleteConfirmText').text('Are you sure you want to delete user?');
        }
    }

    // --- Add User Functionality ---
    function openAddModal() {
        // Always close first to ensure reset and clear previous state
        closeModal('addEditModal'); // This hides confirm password initially

        $('#modalTitle').text('Add New User');
        $('#actionType').val('add');
        $('#userId').val(''); // Make sure ID is empty for add

        // **Explicitly clear input fields for 'Add'**
        // (closeModal already calls reset, but explicitly setting to empty
        // provides extra certainty that fields are blank for a new user)
        $('#username').val('');
        $('#email').val('');
        $('#password').val('');
        $('#confirmPassword').val('');
        $('#role').val(''); // Ensure role is reset to the default "-- Select Role --" option

        $('#password').prop('required', true); // Password IS required for add
        // Show confirm password for add (label is the element before the input)
        $('#confirmPassword').show().prev('label').show();
         $('#password-note').text('Password is required for new users (min 6 characters recommended).');
        // Status for ADD is handled on the server side, typically defaulting to 'active' or 'inactive'
        // We removed the status field from the modal, so no client-side setting needed here.

        openModal('addEditModal');
        $('#username').focus(); // Focus the first field for easy data entry
    }

    // --- Edit User Functionality ---
    // Added the role parameter to the function signature
    function openEditModal(id, username, email, role) {
         // Always close first to ensure reset and clear previous state
         closeModal('addEditModal'); // This hides confirm password initially

        $('#modalTitle').text('Edit User');
        $('#actionType').val('edit');
        $('#userId').val(id);

        // Populate the form fields
        $('#username').val(username);
        $('#email').val(email);
        $('#role').val(role); // Populate the role select
        // Removed: $('#status').val(status); // Don't populate status as field is gone

        $('#password').val(''); // Clear password field for editing (user leaves blank to keep current)
        $('#password').prop('required', false); // Password not required when editing
        // Confirm password is HIDDEN by default in edit mode by closeModal().
        // It will only be shown by the 'input' event listener on the password field
        // if the user starts typing a new password.
        $('#confirmPassword').val(''); // Clear confirm password field

        $('#password-note').text('Leave blank to keep the current password.');


        openModal('addEditModal');
         $('#username').focus(); // Focus the first field
    }

    // --- Delete User Functionality ---
    function openDeleteConfirmModal(id, username) {
        $('#deleteUserId').val(id);
        // Use text() to prevent potential XSS if username contains HTML
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
            url: 'handle_user_actions.php', // The PHP script to handle actions
            type: 'POST',
            data: {
                action: 'delete',
                id: id
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'User deleted successfully!');
                    closeModal('deleteConfirmModal');
                    // Instead of full reload, you could potentially remove the row if using DataTables API
                    location.reload(); // Simple way to refresh the table
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

    // --- Form Submission (Add/Edit User) ---
    function submitUserForm() {
        const form = $('#addEditForm');
        const action = $('#actionType').val(); // 'add' or 'edit'
        clearFormErrors('addEditForm'); // Clear previous errors

        // --- Client-side Validation ---
        let isValid = true;
        let firstErrorField = null;

        function markError(fieldId, message) {
            // Check if the element exists before trying to mark it
            const $field = $('#' + fieldId);
            const $errorSpan = $('#' + fieldId + 'Error');

            if ($field.length) {
                $field.addClass('error');
                 if ($errorSpan.length) {
                    $errorSpan.text(message).show();
                 }
                 if (isValid) { // Mark the first field with an error
                     firstErrorField = $field;
                 }
                isValid = false;
            } else {
                 // Log a warning if an error tries to mark a non-existent field
                 console.warn("Validation attempting to mark non-existent field:", fieldId);
            }
        }

        // Username
        if (!$('#username').val().trim()) { markError('username', 'Username is required.'); }
        // Email
        const emailVal = $('#email').val().trim();
        if (!emailVal) {
             markError('email', 'Email is required.');
        } else {
             // Basic email format check (more thorough validation on server)
             const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
             if (!emailPattern.test(emailVal)) {
                 markError('email', 'Please enter a valid email address.');
             }
             // Email existence check is done server-side
        }

         // Role
         const roleVal = $('#role').val();
         if (!roleVal) {
             markError('role', 'Please select a role.');
         }
         // More specific validation ('admin', 'collector') is handled server-side


         // Password & Confirm Password validation
         const passwordVal = $('#password').val();
         const confirmPasswordVal = $('#confirmPassword').val();

         if (action === 'add') {
             // Password is required for adding a user
             if (!passwordVal) {
                 markError('password', 'Password is required for new users.');
             } else if (passwordVal.length < 6) {
                 markError('password', 'Password must be at least 6 characters long.');
             }

             // Confirm password is required and must match for adding a user
              if (!confirmPasswordVal) { // This check implicitly relies on the field being visible for 'add'
                  markError('confirmPassword', 'Confirm password is required.');
              } else if (passwordVal !== confirmPasswordVal) {
                  markError('confirmPassword', 'Passwords do not match.');
              }

         } else if (action === 'edit') {
              // For edit, password is NOT required.
              // Validation only happens if the user HAS entered a new password.
              if (passwordVal) { // Check if password field has a value (meaning user wants to change it)
                   if (passwordVal.length < 6) {
                       markError('password', 'Password must be at least 6 characters long.');
                   }
                   // Confirm password is required and must match ONLY IF password is provided
                   // The client-side listener shows/hides confirmPassword, so we check if it's visible
                   if ($('#confirmPassword').is(':visible')) {
                        if (!confirmPasswordVal) {
                             markError('confirmPassword', 'Confirm password is required when changing password.');
                        } else if (passwordVal !== confirmPasswordVal) {
                             markError('confirmPassword', 'Passwords do not match.');
                        }
                   }
                   // If passwordVal is not empty but confirmPassword field isn't visible,
                   // something is wrong with the JS show/hide logic or the user somehow
                   // bypassed it. The server-side check will catch this mismatch anyway.
              } else {
                 // User is NOT changing the password (password field is empty)
                 // Ensure confirm password field is empty and hidden (should be handled by input listener, but double check)
                 $('#confirmPassword').val(''); // Clear it just in case
                 $('#confirmPasswordError').text('').hide(); // Clear any error just in case
                 // No password validation needed if passwordVal is empty during edit.
              }
         }

         // REMOVED STATUS VALIDATION (field is gone)


         if (!isValid) {
             if(firstErrorField) firstErrorField.focus(); // Focus the first field with an error
             return; // Stop submission if validation fails
         }
        // --- End Client-side Validation ---


        // Prepare data using FormData
        let formData = new FormData(form[0]); // Use FormData for easier file handling later if needed
        // We can also use form.serialize() if no file uploads: let formData = form.serialize();

        $('.save-btn').prop('disabled', true).text('Saving...'); // Disable button prevent double clicks

        $.ajax({
            url: 'handle_user_actions.php',
            type: 'POST',
            data: formData,
            processData: false, // Required for FormData
            contentType: false, // Required for FormData
            // data: form.serialize(), // Use this if not using FormData
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Operation successful!');
                    closeModal('addEditModal');
                    location.reload(); // Reload page to see changes (simple, but loses DataTables state)
                    // A better approach would be to update the DataTables row directly if possible
                } else {
                    // Display server-side validation errors or general errors
                    if (response.errors) {
                        // Clear all errors first before displaying new ones
                        clearFormErrors('addEditForm');
                        let serverFirstErrorField = null; // To track the first field for focusing

                        $.each(response.errors, function(key, message) {
                            // Assumes error keys match input names (e.g., 'username', 'email', 'role', 'password', 'confirmPassword')
                            markError(key, message); // Re-mark errors based on server response
                            // Find the first field that has a server error key
                             if (!serverFirstErrorField && $('#' + key).length) {
                                serverFirstErrorField = $('#' + key);
                             }
                        });

                        if (serverFirstErrorField) {
                            serverFirstErrorField.focus(); // Focus the first field with a server error
                        }


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
                // Re-enable button regardless of success/error
                 $('.save-btn').prop('disabled', false).text('Save');
            }
        });
    }

    // --- Status Toggle Functionality ---
     // Use event delegation on the table body to handle toggles within DataTables
    $('#userTable tbody').on('change', '.status-toggle', function() {
        const $toggle = $(this); // The checkbox element that was clicked
        const userId = $toggle.data('userId');
        // Determine the *new* status based on the checked state
        const newStatus = $toggle.is(':checked') ? 'active' : 'inactive';

        if (!userId) {
            alert('Error: User ID not found for status toggle.');
            // Revert the toggle state immediately if there's a client-side issue
            $toggle.prop('checked', !$toggle.is(':checked'));
            return;
        }

        // Optional: Disable the toggle temporarily while the request is in progress
        $toggle.prop('disabled', true);

        $.ajax({
            url: 'handle_user_actions.php', // The PHP script
            type: 'POST',
            data: {
                action: 'toggle_status',
                id: userId,
                status: newStatus // Send the intended new status
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    // Optionally show a small temporary success message
                    // console.log('Status updated successfully for user ' + userId);
                    // The toggle state is already correct because the user clicked it
                    // Update the data-current-status attribute for consistency (optional)
                    $toggle.data('current-status', newStatus);
                } else {
                    // Server-side error occurred, revert the toggle state
                    alert('Failed to update status: ' + (response.message || 'Unknown error'));
                    $toggle.prop('checked', !$toggle.is(':checked')); // Revert state
                     // Revert the data-current-status attribute
                    $toggle.data('current-status', $toggle.is(':checked') ? 'active' : 'inactive');
                }
            },
            error: function(xhr, status, error) {
                // AJAX error occurred, revert the toggle state
                alert('An AJAX error occurred while updating status: ' + status + '\nError: ' + error);
                console.error("AJAX Error:", xhr.responseText);
                $toggle.prop('checked', !$toggle.is(':checked')); // Revert state
                 // Revert the data-current-status attribute
                $toggle.data('current-status', $toggle.is(':checked') ? 'active' : 'inactive');
            },
            complete: function() {
                // Re-enable the toggle after the request is complete
                $toggle.prop('disabled', false);
            }
        });
    });


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