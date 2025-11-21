<?php
session_start(); 

$pageTitle = "Assistant Management";

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

require_once("assistantmodel.php"); 

$assistantData = [];
$availableUsers = [];
$fetchError = '';

if (empty($dbError) && $pdo instanceof PDO) {
    try {
        $assistantData = getAllAssistants($pdo);
        $availableUsers = getAvailableUserAccountsForAssistants($pdo);

    } catch (Exception $e) {
         $fetchError = 'Could not retrieve data: ' . $e->getMessage();
         error_log('Error fetching data in assistant.php: ' . $e->getMessage());
         $assistantData = [];
         $availableUsers = [];
    }
} else if (!empty($dbError)) {
    $fetchError = $dbError;
}

?>

<html>
<head>
    <link rel="stylesheet" href="css/driver.css"> 
    <title><?php echo htmlspecialchars($pageTitle); ?> - Your App Name</title>
    <style>
        .dataTables_filter input[type="search"] {
            border-radius: 25px !important;
            border: 1px solid #ccc !important;
            padding: 8px 12px !important;
            outline: none !important;
        }
        .dataTables_length, .dataTables_filter {
            margin-bottom: 20px !important;
        }
    </style>
</head>
<body>
    <div class="content">
        <h2>Assistant Management</h2>
        <hr>

        <button class="add-user-btn" onclick="openAddAssistantModal()">
            <i class="fa-solid fa-user-plus"></i> Add New Assistant
        </button>

        <?php if (!empty($fetchError)): ?>
            <div class='message error'><?php echo htmlspecialchars($fetchError); ?></div>
        <?php endif; ?>

        <table id="assistantTable" class="display" style="width:100%">
            <thead>
                <tr>
                    <th>Assistant Name</th>
                    <th>Contact No</th>
                    <th>Linked Account</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (is_array($assistantData) && !empty($assistantData)): ?>
                    <?php foreach ($assistantData as $assistant): ?>
                        <?php
                            $assistantId = htmlspecialchars($assistant['assistant_id'] ?? '');
                            $fullName = htmlspecialchars($assistant['first_name'] ?? '');
                            if (!empty($assistant['middle_name'])) {
                                $fullName .= ' ' . htmlspecialchars($assistant['middle_name']);
                            }
                            $fullName .= ' ' . htmlspecialchars($assistant['last_name'] ?? '');
                            // --- CHANGED HERE ---
                            $contactNumber = htmlspecialchars($assistant['contact_number'] ?? '');
                            $linkedAccount = htmlspecialchars($assistant['username'] ?? 'Unassigned');
                            $status = htmlspecialchars(ucfirst($assistant['status'] ?? ''));
                        ?>
                        <tr>
                            <td><?php echo $fullName; ?></td>
                            <!-- --- AND HERE --- -->
                            <td><?php echo $contactNumber; ?></td>
                            <td><?php echo $linkedAccount; ?></td>
                            <td><?php echo $status; ?></td>
                            <td class='action-buttons'>
                                <?php if (!empty($assistantId) && is_numeric($assistantId)): ?>
                                    <button class='edit-btn' onclick='openEditAssistantModal(
                                        <?php echo $assistantId; ?>, 
                                        <?php echo json_encode($assistant['first_name'] ?? ''); ?>, 
                                        <?php echo json_encode($assistant['middle_name'] ?? null); ?>, 
                                        <?php echo json_encode($assistant['last_name'] ?? ''); ?>, 
                                        // --- AND HERE ---
                                        <?php echo json_encode($assistant['contact_number'] ?? ''); ?>, 
                                        <?php echo json_encode($assistant['user_id'] ?? null); ?>, 
                                        <?php echo json_encode($assistant['status'] ?? ''); ?>
                                    )'><i class='fas fa-edit'></i></button>
                                    <button class='delete-btn' onclick='openDeleteConfirmAssistantModal(<?php echo $assistantId; ?>, <?php echo json_encode($fullName); ?>)'><i class='fas fa-trash'></i></button>
                                <?php else: ?>
                                    Invalid Assistant ID
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr><td colspan="5" style="text-align:center;">No assistant data found.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Add/Edit Assistant Modal Form -->
    <div id="addEditModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditModal')">×</span>
            <h3 id="modalTitle">Add/Edit Assistant</h3>

            <form id="addEditForm" novalidate>
                <input type="hidden" id="assistantId" name="id">
                <input type="hidden" id="actionType" name="action">

                <div class="modal-form-grid">
                    <div class="form-group">
                        <label for="firstName">First Name:</label>
                        <input type="text" id="firstName" name="first_name" required>
                        <span class="error-message" id="firstNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="middleName">Middle Name:</label>
                        <input type="text" id="middleName" name="middle_name">
                        <span class="error-message" id="middleNameError"></span>
                    </div>

                    <div class="form-group">
                        <label for="lastName">Last Name:</label>
                        <input type="text" id="lastName" name="last_name" required>
                        <span class="error-message" id="lastNameError"></span>
                    </div>

                    <!-- --- CHANGES IN THIS BLOCK --- -->
                    <div class="form-group">
                        <label for="contactNumber">Contact No:</label>
                        <input type="text" id="contactNumber" name="contact_number" required>
                        <span class="error-message" id="contactNumberError"></span>
                    </div>

                    <div class="form-group">
                        <label for="userId">Link to User Account:</label>
                        <select id="userId" name="user_id"></select>
                        <span class="error-message" id="userIdError"></span>
                    </div>

                    <div class="form-group">
                        <label for="status">Status:</label>
                        <select id="status" name="status" required>
                            <option value="">-- Select Status --</option>
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                        <span class="error-message" id="statusError"></span>
                    </div>
                </div>

                <div class="modal-buttons">
                    <button type="button" class="save-btn" onclick="submitAssistantForm()">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('addEditModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('deleteConfirmModal')">×</span>
            <h3>Confirm Deletion</h3>
            <p id="deleteConfirmText">Are you sure you want to delete this assistant?</p>
            <input type="hidden" id="deleteAssistantId">
            <button class="confirm-btn" onclick="confirmDeleteAssistant()">Yes, Delete</button>
            <button type="button" class="cancel-btn" onclick="closeModal('deleteConfirmModal')">Cancel</button>
        </div>
    </div>

<script>
    var availableUsers = <?php echo json_encode($availableUsers); ?>;

    $(document).ready(function() {
        if ($('#assistantTable tbody tr').length > 0 && $('#assistantTable tbody tr:first td').length > 1) {
             $('#assistantTable').DataTable({ 
                "order": [[ 0, "asc" ]],
                "columnDefs": [
                   { "orderable": false, "targets": 4 } 
                ]
             });
        }
    });

    function clearFormErrors(formId) {
         $('#' + formId + ' .error-message').text('').hide();
         $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
    }

    function populateUserDropdown(currentUserId = null) {
        const $select = $('#userId');
        $select.empty().append($('<option>', { value: '', text: '-- Unassigned --' }));

        let currentUserFound = false;
        availableUsers.forEach(function(user) {
            const option = $('<option>', { value: user.user_id, text: user.username });
            if (currentUserId !== null && user.user_id == currentUserId) {
                option.prop('selected', true);
                currentUserFound = true;
            }
            $select.append(option);
        });

        if (currentUserId && !currentUserFound) {
             console.warn("Current user ID not in available list. This should be handled by the backend model.");
        }
    }

    function openAddAssistantModal() {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Add New Assistant');
        $('#actionType').val('add');
        $('#assistantId').val('');
        $('#status').val('active');
        
        populateUserDropdown();

        openModal('addEditModal');
        $('#firstName').focus();
    }
    
    // --- CHANGED FUNCTION SIGNATURE AND CONTENT ---
    function openEditAssistantModal(id, firstName, middleName, lastName, contactNumber, currentUserId, status) {
        $('#addEditForm')[0].reset();
        clearFormErrors('addEditForm');
        $('#modalTitle').text('Edit Assistant');
        $('#actionType').val('edit');
        $('#assistantId').val(id);

        $('#firstName').val(firstName);
        $('#middleName').val(middleName);
        $('#lastName').val(lastName);
        $('#contactNumber').val(contactNumber); // <-- Changed ID here
        $('#status').val(status);

        populateUserDropdown(currentUserId);
        
        $('#userId').val(currentUserId === null ? '' : currentUserId);

        openModal('addEditModal');
    }

    function openDeleteConfirmAssistantModal(id, assistantName) {
        $('#deleteAssistantId').val(id);
        $('#deleteConfirmText').text('Are you sure you want to delete assistant: ' + assistantName + '?');
        openModal('deleteConfirmModal');
    }

    function confirmDeleteAssistant() {
        const id = $('#deleteAssistantId').val();
        if (!id) {
            alert('Error: Assistant ID not found.');
            return;
        }

        $.ajax({
            url: 'handle_assistant_actions.php',
            type: 'POST',
            data: { action: 'delete', id: id },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    alert(response.message || 'Assistant deleted successfully!');
                    location.reload();
                } else {
                    alert('Error deleting assistant: ' + (response.message || 'Unknown error'));
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
    
    function submitAssistantForm() {
        const form = $('#addEditForm');
        clearFormErrors('addEditForm');
        let isValid = true;

        if (!$('#firstName').val().trim()) { isValid = false; $('#firstNameError').text('First name is required.').show(); }
        if (!$('#lastName').val().trim()) { isValid = false; $('#lastNameError').text('Last name is required.').show(); }
        // --- CHANGED VALIDATION LOGIC ---
        if (!$('#contactNumber').val().trim()) { isValid = false; $('#contactNumberError').text('Contact number is required.').show(); }
        if (!$('#status').val()) { isValid = false; $('#statusError').text('Please select a status.').show(); }

        if (!isValid) return;

        $('.save-btn').prop('disabled', true).text('Saving...');

        $.ajax({
            url: 'handle_assistant_actions.php',
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