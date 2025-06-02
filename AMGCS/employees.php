<?php
$isFileManagement = true;
session_start(); // Start the session at the very beginning

// Define the page title *before* including the header
$pageTitle = "Employees";

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php'; // Adjust path if needed
// Footer included at the very end

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
require_once("employeemodel.php"); // INCLUDE THE NEW EMPLOYEE MODEL
// NOTE: You MUST update employeemodel.php as well to remove 'job_title'
// from your SQL queries (e.g., in getAllEmployees, add/edit queries)

// 3. CALL THE FUNCTION FROM THE MODEL (Only for initial load)
$employeeData = []; // Initialize empty
$unassignedUsers = []; // Initialize empty for the add modal dropdown
$fetchError = '';
$unassignedUsersError = '';

if (!$dbError && $pdo instanceof PDO) { // Only fetch if DB connection is okay
    try {
        // Make sure getAllEmployees in employeemodel.php no longer selects job_title
        $employeeData = getAllEmployees($pdo); // Assumes getAllEmployees returns an array or throws Exception
        if ($employeeData === false) { // Or check if it's explicitly false if the function returns that on error
             throw new Exception('The getAllEmployees function returned false, indicating an error.');
        }
    } catch (Exception $e) {
        $fetchError = 'Could not retrieve employee data: ' . $e->getMessage();
         error_log('Error in getAllEmployees: ' . $e->getMessage()); // Log detailed error
         $employeeData = []; // Ensure it's an empty array for the loop
    }

    // Also fetch unassigned users for the 'Add' modal
    try {
        $unassignedUsers = getUnassignedUsers($pdo);
         if ($unassignedUsers === false) {
             throw new Exception('The getUnassignedUsers function returned false, indicating an error.');
         }
    } catch (Exception $e) {
         $unassignedUsersError = 'Could not retrieve unassigned users for adding: ' . $e->getMessage();
         error_log('Error in getUnassignedUsers: ' . $e->getMessage()); // Log detailed error
         $unassignedUsers = []; // Ensure it's an empty array
    }

} else if ($dbError) {
    $fetchError = $dbError; // Use the DB connection error message for both
    $unassignedUsersError = $dbError;
}

?>
<html>
<body>
    <div class="content">
        <div class="clearfix"> <!-- Wrap header and button to clear float -->
            <h2>Employee Management</h2> <!-- Changed title -->
        </div>
        <hr>

        <button class="add-user-btn" onclick="openAddModal()">
            <i class="fa-solid fa-user-plus"></i> Add New Employee <!-- Changed button text -->
            </button>

        <?php
        // Display success message from redirect (if any - though AJAX is preferred now)
        // Note: With AJAX, messages are usually handled by the JS response
        // if (isset($_GET['status']) && $_GET['status'] == 'employee_added_success') {
        //     echo "<div class='message success'>Employee added successfully!</div>";
        // }
        // Display fetch errors, if any
        if (!empty($fetchError)) {
            echo "<div class='message error'>" . htmlspecialchars($fetchError) . "</div>";
        }
         if (!empty($unassignedUsersError) && empty($unassignedUsers) && empty($employeeData)) {
             // Only show this specific error if no employees were loaded and no unassigned users were loaded
             echo "<div class='message warning'>" . htmlspecialchars($unassignedUsersError) . " You may not be able to add new employees.</div>";
         }
        ?>

        <?php if (!$fetchError): // Only show table if there wasn't a fatal fetch error ?>
        <table id="employeeTable" class="display" style="width:100%"> <!-- Changed table ID -->
            <thead>
                <tr>
                    <th>Username</th> <!-- Display username from user_table -->
                    <th>First Name</th> <!-- Added Employee Fields -->
                    <th>Last Name</th>
                    <!-- REMOVED: <th>Job Title</th> -->
                    <!-- REMOVED: <th>Hire Date</th> -->
                    <th>Contact Number</th>
                    <th>Status</th> <!-- Employee Status -->
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if (!empty($employeeData)) {
                    foreach ($employeeData as $employee) {
                        // Escape all output
                        $employeeId = htmlspecialchars($employee['employee_id'] ?? 'N/A');
                        $userId = htmlspecialchars($employee['user_id'] ?? 'N/A'); // Keep user_id for edit modal
                        $username = htmlspecialchars($employee['username'] ?? 'N/A'); // From JOIN
                        $firstName = htmlspecialchars($employee['first_name'] ?? 'N/A');
                        $middleName = htmlspecialchars($employee['middle_name'] ?? ''); // Middle name can be empty
                        $lastName = htmlspecialchars($employee['last_name'] ?? 'N/A');
                        // REMOVED: $jobTitle = htmlspecialchars($employee['job_title'] ?? 'N/A');
                        // Removed hire_date variable, it's no longer needed
                        // $hireDate = htmlspecialchars($employee['hire_date'] ?? 'N/A');
                        $contactNumber = htmlspecialchars($employee['contact_number'] ?? ''); // Contact number can be empty
                        $employeeStatus = htmlspecialchars(ucfirst($employee['employee_status'] ?? 'N/A')); // Capitalize

                        echo "<tr>";
                        echo "<td>" . $username . "</td>";
                        echo "<td>" . $firstName . "</td>";
                        echo "<td>" . $lastName . "</td>";
                        // REMOVED: echo "<td>" . $jobTitle . "</td>";
                        // REMOVED: echo "<td>" . $hireDate . "</td>";
                        echo "<td>" . $contactNumber . "</td>";
                        echo "<td>" . $employeeStatus . "</td>";
                        // Add Edit and Delete buttons with data attributes passed to JS
                        echo "<td class='action-buttons'>";
                        // Pass necessary data for editing to the JS function
                        // Use json_encode for safe JS string passing
                        // REMOVED json_encode($jobTitle) and json_encode($hireDate) from the parameters
                        echo "<button class='edit-btn' onclick='openEditModal("
                             . $employeeId . ", "
                             . json_encode($userId) . ", " // Pass user_id as well
                             . json_encode($firstName) . ", "
                             . json_encode($middleName) . ", "
                             . json_encode($lastName) . ", "
                             . json_encode($contactNumber) . ", " // jobTitle and hireDate removed
                             . json_encode($employee['employee_status'] ?? '') // Pass raw status value
                             . ")'><i class='fas fa-edit'></i></button>";
                        echo "<button class='delete-btn' onclick='openDeleteConfirmModal(" . $employeeId . ", " . json_encode($firstName . ' ' . $lastName) . ")'><i class='fas fa-trash'></i></button>"; // Pass ID and full name
                        echo "</td>";
                        echo "</tr>";
                    }
                } else { // Only show "No employees found" if there wasn't a fetch error
                     // Count columns: Username, First, Last, Contact, Status, Actions = 6
                    echo '<tr><td colspan="6" style="text-align:center;">No employee data found.</td></tr>';
                }
                ?>
            </tbody>
        </table>
        <?php endif; ?>

    </div> <!-- Content div closed -->

    <!-- Add/Edit Employee Modal Form -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
            <h3 id="modalTitle">Add/Edit Employee</h3> <!-- Dynamic Title -->

            <form id="addEditForm" novalidate> <!-- Disable browser validation, we use JS/Server -->
                <input type="hidden" id="employeeId" name="id"> <!-- Hidden field for ID (used for editing) -->
                <input type="hidden" id="actionType" name="action"> <!-- Hidden field for action (add/edit) -->
                 <input type="hidden" id="originalUserId" name="original_user_id"> <!-- Store user_id for edit reference -->


                <div id="userSelectContainer"> <!-- Container for User Select (only shown in Add mode) -->
                    <label for="userId">Associated User:</label>
                    <select id="userId" name="user_id" required>
                        <option value="">-- Select User --</option>
                        <?php
                        // Populate dropdown with unassigned users
                        if (!empty($unassignedUsers)) {
                            foreach ($unassignedUsers as $user) {
                                echo "<option value='" . htmlspecialchars($user['user_id']) . "'>" . htmlspecialchars($user['username']) . "</option>";
                            }
                        }
                        ?>
                    </select>
                     <?php if (empty($unassignedUsers) && empty($unassignedUsersError)): ?>
                        <p class="message warning">No unassigned users found. Cannot add new employees until user accounts are created.</p>
                     <?php elseif (!empty($unassignedUsersError)): ?>
                         <p class="message error">Error loading users: <?php echo htmlspecialchars($unassignedUsersError); ?></p>
                     <?php endif; ?>
                    <span class="error-message" id="userIdError"></span>
                </div>


                <label for="firstName">First Name:</label>
                <input type="text" id="firstName" name="first_name" required>
                <span class="error-message" id="first_nameError"></span> <!-- Error span name should match input name -->

                <label for="middleName">Middle Name:</label>
                <input type="text" id="middleName" name="middle_name"> <!-- Optional -->
                <span class="error-message" id="middle_nameError"></span>

                <label for="lastName">Last Name:</label>
                <input type="text" id="lastName" name="last_name" required>
                 <span class="error-message" id="last_nameError"></span>

                <!-- REMOVED JOB TITLE INPUT FIELDS -->
                <!--
                <label for="jobTitle">Job Title:</label>
                <input type="text" id="jobTitle" name="job_title" required>
                <span class="error-message" id="job_titleError"></span>
                -->

                <!-- REMOVED HIRE DATE INPUT FIELDS -->
                <!--
                <label for="hireDate">Hire Date:</label>
                <input type="date" id="hireDate" name="hire_date" required>
                <span class="error-message" id="hire_dateError"></span>
                -->

                <label for="contactNumber">Contact Number:</label>
                <input type="text" id="contactNumber" name="contact_number"> <!-- Optional -->
                <span class="error-message" id="contact_numberError"></span>

                <label for="employeeStatus">Status:</label> <!-- Changed from 'status' to 'employeeStatus' -->
                <select id="employeeStatus" name="employee_status" required>
                     <option value="">-- Select Status --</option>
                     <option value="Active">Active</option>
                     <option value="Inactive">Inactive</option>
                     <option value="On Leave">On Leave</option>
                     <option value="Terminated">Terminated</option>
                </select>
                <span class="error-message" id="employee_statusError"></span>

                <button type="button" class="save-btn" onclick="submitEmployeeForm()">Save</button> <!-- Changed function name -->
                <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
            <h3>Confirm Deletion</h3>
            <p id="deleteConfirmText">Are you sure you want to delete this employee?</p> <!-- Changed text -->
            <input type="hidden" id="deleteEmployeeId"> <!-- Changed ID -->
            <button class="confirm-btn" onclick="confirmDeleteEmployee()">Yes, Delete</button> <!-- Changed function name -->
            <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
        </div>
    </div>

    <script>
        // Store unassigned users fetched initially
        let unassignedUsersData = <?php echo json_encode($unassignedUsers); ?>;

        // --- DataTables Initialization ---
        $(document).ready(function() {
            // Check if the table exists and has rows before initializing DataTable
             // Check if there's more than 1 row (header + data) OR if the single row is data (not 'No data found')
            const $tableBodyRows = $('#employeeTable tbody tr');
            // The colspan for "No data" is now 6. Check if the first row has only one td with colspan 6
            const hasData = $tableBodyRows.length > 1 || ($tableBodyRows.length === 1 && $tableBodyRows.find('td').length !== 6);

            if (hasData) {
                 $('#employeeTable').DataTable({
                    "order": [[ 1, "asc" ]], // Order by First Name ascending (assuming Username is col 0, First Name 1)
                    // columnDefs target needs to be updated because Job Title (col 3) was removed
                    // Current columns: Username (0), First (1), Last (2), Contact (3), Status (4), Actions (5)
                    "columnDefs": [
                       { "orderable": false, "targets": 5 } // Disable sorting on Actions column (now column 5)
                    ]
                 });
            }
            // else: Don't initialize if table is empty or just has the "No data" message
        });

        // --- General Utility Functions ---
        function clearFormErrors(formId) {
             // Clear text inside elements with class 'error-message' within the form
             $('#' + formId + ' .error-message').text('').hide();
             // Remove 'error' class from form inputs and selects
             $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
        }

        // --- Modal Control Functions ---
        function openModal(modalId) {
            $('#' + modalId).css('display', 'flex'); // Use flexbox for centering
        }

        function closeModal(modalId) {
             $('#' + modalId).css('display', 'none');
            // Clear form fields and errors when closing Add/Edit modal
            if (modalId === 'addEditModal') {
                 $('#addEditForm')[0].reset(); // Reset the form elements
                 $('#employeeId').val('');        // Clear hidden ID
                 $('#actionType').val('');    // Clear hidden action type
                 $('#originalUserId').val(''); // Clear original user ID

                 clearFormErrors('addEditForm'); // Clear any previous validation errors

                 // Reset User Select visibility/state
                 $('#userSelectContainer').show(); // Ensure visible for Add
                 $('#userId').prop('disabled', false); // Ensure enabled for Add
                 populateUserSelect(unassignedUsersData); // Repopulate in case a user was added/used
            }
            if (modalId === 'deleteConfirmModal') {
                $('#deleteEmployeeId').val('');
                $('#deleteConfirmText').text('Are you sure you want to delete this employee?');
            }
        }

         // Helper function to populate the User Select dropdown
         function populateUserSelect(users) {
            const $select = $('#userId');
            $select.empty(); // Clear existing options
            $select.append('<option value="">-- Select User --</option>');
            if (users && users.length > 0) {
                users.forEach(user => {
                    $select.append(`<option value="${user.user_id}">${user.username}</option>`);
                });
                $('#userSelectContainer p.message').hide(); // Hide warnings if users are available
            } else {
                 // Show warning if no unassigned users
                 $('#userSelectContainer p.message.warning').show();
                 // Check if there's an error message from PHP and show that instead if present
                 if ('<?php echo !empty($unassignedUsersError); ?>') {
                      $('#userSelectContainer p.message.error').text('<?php echo htmlspecialchars($unassignedUsersError); ?>').show();
                      $('#userSelectContainer p.message.warning').hide(); // Hide warning if error is shown
                 }
            }
         }


        // --- Add Employee Functionality ---
        function openAddModal() {
            closeModal('addEditModal'); // Ensure form is reset before opening
            $('#modalTitle').text('Add New Employee');
            $('#actionType').val('add');
            $('#employeeId').val(''); // Make sure ID is empty for add
            $('#originalUserId').val(''); // No original user ID for add

            // Configure User Select for Add mode
            $('#userSelectContainer').show();
            $('#userId').prop('disabled', false).val(''); // Ensure enabled and reset
            // The populateUserSelect call happens in closeModal, ensuring fresh data potentially

            openModal('addEditModal');
            $('#userId').focus(); // Focus the first field (User Select)
        }

        // --- Edit Employee Functionality ---
        // Function updated to remove the jobTitle parameter
        function openEditModal(id, userId, firstName, middleName, lastName, contactNumber, employeeStatus) {
             closeModal('addEditModal'); // Ensure form is reset before opening
            $('#modalTitle').text('Edit Employee');
            $('#actionType').val('edit');
            $('#employeeId').val(id);
            $('#originalUserId').val(userId); // Store the user ID for this employee


            // Populate the form fields with employee data
            $('#firstName').val(firstName);
            $('#middleName').val(middleName);
            $('#lastName').val(lastName);
            // REMOVED: $('#jobTitle').val(jobTitle);
            // REMOVED: $('#hireDate').val(hireDate); // Date input expects YYYY-MM-DD
            $('#contactNumber').val(contactNumber);
            $('#employeeStatus').val(employeeStatus);

            // Configure User Select for Edit mode
            // In edit mode, the user association is typically not changed via this form.
            // Hide or disable the user select. We'll just store the original user ID.
            $('#userSelectContainer').hide(); // Hide the select dropdown
            $('#userId').prop('disabled', true); // Disable it just in case it's somehow visible


            openModal('addEditModal');
            $('#firstName').focus(); // Focus the first editable field
        }

        // --- Delete Employee Functionality ---
        function openDeleteConfirmModal(id, fullName) { // Changed to accept full name
            $('#deleteEmployeeId').val(id);
            // Use text() to prevent potential XSS if name contains HTML
            $('#deleteConfirmText').text('Are you sure you want to delete employee: ' + fullName + '?'); // Changed text
            openModal('deleteConfirmModal');
        }

        function confirmDeleteEmployee() { // Changed function name
            const id = $('#deleteEmployeeId').val();
            if (!id) {
                alert('Error: Employee ID not found.');
                return;
            }

            $.ajax({
                url: 'handle_employee_actions.php', // Target the new handler
                type: 'POST',
                data: {
                    action: 'delete',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Employee deleted successfully!'); // Changed message
                        closeModal('deleteConfirmModal');
                        location.reload(); // Simple way to refresh the table
                    } else {
                        alert('Error deleting employee: ' + (response.message || 'Unknown error')); // Changed message
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

        // --- Form Submission (Add/Edit Employee) ---
        function submitEmployeeForm() { // Changed function name
            const form = $('#addEditForm');
            const action = $('#actionType').val(); // 'add' or 'edit'
            clearFormErrors('addEditForm'); // Clear previous errors

            // --- Client-side Validation ---
            let isValid = true;
            let firstErrorField = null;

            function markError(fieldId, message) {
                // Use the specific error span IDs which match input names
                $('#' + fieldId).addClass('error');
                // The error span ID is typically the input name + 'Error'
                let errorSpanId = $('#' + fieldId).attr('name') + 'Error';
                if (fieldId === 'userId') errorSpanId = 'userIdError'; // Special case for userId
                 $('#' + errorSpanId).text(message).show();
                isValid = false;
                if (!firstErrorField) {
                    firstErrorField = $('#' + fieldId);
                }
            }

            // Validate user_id only if action is 'add'
            if (action === 'add') {
                 if (!$('#userId').val()) {
                     markError('userId', 'Please select an associated user.');
                 }
                 // Note: Server will do the final check if user is already assigned
            }

            // First Name
            if (!$('#firstName').val().trim()) { markError('firstName', 'First Name is required.'); }
            // Last Name
            if (!$('#lastName').val().trim()) { markError('lastName', 'Last Name is required.'); }
            // REMOVED Job Title Validation
            // REMOVED HIRE DATE VALIDATION
             // Employee Status
             if (!$('#employeeStatus').val()) { markError('employeeStatus', 'Please select a status.'); }

            // Middle Name and Contact Number are optional, no client-side check needed unless specific format required.

             if (!isValid) {
                 if(firstErrorField) firstErrorField.focus(); // Focus the first field with an error
                 return; // Stop submission if validation fails
             }
            // --- End Client-side Validation ---


            // Prepare data using FormData
            let formData = new FormData(form[0]); // Use FormData

            $('.save-btn').prop('disabled', true).text('Saving...'); // Disable button prevent double clicks

            $.ajax({
                url: 'handle_employee_actions.php', // Target the new handler
                type: 'POST',
                data: formData,
                processData: false, // Required for FormData
                contentType: false, // Required for FormData
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Operation successful!');
                        closeModal('addEditModal');
                        location.reload(); // Reload page to see changes
                    } else {
                        // Display server-side validation errors or general errors
                        if (response.errors) {
                             // Map server errors to the correct form fields
                            $.each(response.errors, function(key, message) {
                                // Error keys from server should match input names (e.g., 'first_name', 'employee_status')
                                // Need to map input name to element ID (e.g., first_name -> firstName)
                                let fieldId = key.replace(/_([a-z])/g, function (g) { return g[1].toUpperCase(); }); // Convert snake_case to camelCase
                                // Handle mapping for user_id error specifically if needed, although the input id is already 'userId'
                                if (fieldId === 'userId' || key === 'user_id') { // Check both camelCase and original key
                                     $('#userId').addClass('error'); // Add error class to the select
                                     $('#userIdError').text(message).show(); // Target specific error span
                                } else {
                                     // Target the input element and its corresponding error span
                                     const inputElement = $('[name="' + key + '"]');
                                     if (inputElement.length) {
                                          inputElement.addClass('error');
                                          $('#' + key + 'Error').text(message).show(); // Target error span using original key name + 'Error'
                                     } else {
                                          // Fallback for unmapped errors
                                          alert('Server Error: ' + key + ': ' + message); // Show key for debugging
                                          console.error('Server validation error for unknown field:', key, message);
                                     }
                                }
                            });
                             // Attempt to focus the first field with an error if possible
                             // Re-evaluate firstErrorField after server errors are processed
                             let firstErrorElement = $('#addEditForm').find('.error:visible').first();
                             if(firstErrorElement.length) {
                                 firstErrorElement.focus();
                             }


                         } else {
                            alert('Error: ' + (response.message || 'Operation failed. Please check the details.'));
                         }
                    }
                },
                error: function(xhr, status, error) {
                     alert('An AJAX error occurred: ' + status + '\nError: ' + error);
                     console.error("AJAX Error:", xhr.responseText);
                     console.error("AJAX response:", xhr.responseText); // Log the response text
                },
                complete: function() {
                    // Re-enable button regardless of success/error
                     $('.save-btn').prop('disabled', false).text('Save');
                }
            });
        }


    </script>

<?php
// Close the connection at the end
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}

// Include the footer template file
require_once 'templates/footer.php'; // Adjust path if needed
?>
</body>
</html>