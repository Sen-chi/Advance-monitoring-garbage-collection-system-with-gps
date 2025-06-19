<?php
session_start(); // Start the session

// Include the header template file
require_once 'templates/header.php'; // Adjust path if needed
require_once 'templates/sidebar.php';
// require_once 'templates/footer.php'; // Moved footer include to the very end after all HTML/JS

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
        // If db_connect.php doesn't throw, but also doesn't return PDO
        throw new Exception("Failed to get a valid database connection object from db_connect.php.");
    }
} catch (Exception $e) {
    $dbError = "Database Connection Error: " . $e->getMessage();
    // Log the detailed error for the admin
    error_log($dbError);
    // Set a user-friendly message
    $dbError = "Could not connect to the database. Please try again later.";
}

// 2. FETCH DATA
$municipalityRecords = []; // Initialize empty
$fetchError = ''; // Use a separate variable for fetch errors vs DB connection error

if (!$dbError && $pdo instanceof PDO) { // Only fetch if DB connection is okay
    $sql = "SELECT municipal_record_id, entry_date, entry_time, lgu_municipality, private_company, plate_number, estimated_volume_per_truck_kg, driver_name
            FROM mucipalities_record
            ORDER BY entry_date DESC, entry_time DESC"; // Added time to order for consistent sorting

    try {
        $stmt = $pdo->query($sql);
        if ($stmt) {
             $municipalityRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
             throw new Exception("Database query failed.");
        }

        if (empty($municipalityRecords) && !$dbError && !$fetchError) {
             $no_records_message = "No municipality records found.";
        } else {
             $no_records_message = null; // Clear message if data is found
        }

    } catch (\PDOException $e) {
        $fetchError = 'Could not retrieve municipality records: ' . $e->getMessage();
        error_log('Error fetching municipality records: ' . $e->getMessage()); // Log detailed error
        $municipalityRecords = []; // Ensure it's an empty array for the loop
    } catch (Exception $e) {
        $fetchError = 'An error occurred during data retrieval: ' . $e->getMessage();
        error_log('General Error fetching municipality records: ' . $e->getMessage());
        $municipalityRecords = [];
    }
} else if ($dbError) {
    // If DB connection failed, the $dbError message is already set.
}


// Set page title before including header
$pageTitle = "Municipality Records";

// Include header and sidebar templates (assuming these output HTML)
// require_once 'templates/header.php'; // Already included at the top
// require_once 'templates/sidebar.php'; // Already included at the top
?>
<!DOCTYPE html>
<html>
<head>
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php // Assuming header.php included necessary meta, links (CSS), etc. ?>
    <?php // If not, include them here ?>
     <!-- Make sure these paths are correct -->
     <!-- <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.11.5/css/jquery.dataTables.css"> -->
     <!-- <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css"> -->
     <?php // Link to your custom CSS ?>
     <!-- <link rel="stylesheet" href="path/to/your/css/styles.css"> -->
</head>
<body>
    <?php // Assuming sidebar content is output here ?>
    <?php // e.g., include 'templates/sidebar_content.html'; ?>

    <div class="content">
        <div class="clearfix">

            <h2><?= htmlspecialchars($pageTitle) ?></h2>

             <button class="add-record-btn" onclick="openAddModalMunicipality()">
                <i class="fas fa-plus"></i> Add New Record
            </button> <br>

            <div class="filter-area">
                <label for="type-filter">Filter by Type:</label>
                <select id="type-filter">
                    <option value="">All</option>
                    <option value="LGU">LGU</option>
                    <option value="Private">Private</option>
                     <option value="Unknown">Unknown</option>
                </select>
            </div>

        </div>
        <hr>

        <?php
         if (isset($_GET['status']) && $_GET['status'] == 'success') {
             echo "<div class='message success'>" . htmlspecialchars($_GET['message'] ?? 'Operation successful!') . "</div>";
         } else if (isset($_GET['status']) && $_GET['status'] == 'error') {
             echo "<div class='message error'>" . htmlspecialchars($_GET['message'] ?? 'Operation failed.') . "</div>";
         }

        if (!empty($dbError)) {
            echo "<div class='message error'>" . htmlspecialchars($dbError) . "</div>";
        } else if (!empty($fetchError)) {
            echo "<div class='message error'>" . htmlspecialchars($fetchError) . "</div>";
        }
        ?>

        <?php if (empty($dbError) && empty($fetchError)): ?>
            <?php if (isset($no_records_message)): ?>
                <div style="color: gray; text-align: center; padding: 20px;"><?= htmlspecialchars($no_records_message) ?></div>
            <?php else: ?>
                <table id="municipalityTable" class="display" style="width:100%">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Type</th>
                            <th>LGU / Company</th>
                            <th>Plate Number</th>
                            <th>Estimated Volume (kg)</th>
                            <th>Driver's Name</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($municipalityRecords)) {
                            foreach ($municipalityRecords as $row) {
                                $recordId = htmlspecialchars($row['municipal_record_id'] ?? '');
                                $entryDate = htmlspecialchars($row['entry_date'] ?? '');
                                $entryTime = htmlspecialchars($row['entry_time'] ?? '');
                                $lguMunicipality = htmlspecialchars($row['lgu_municipality'] ?? '');
                                $privateCompany = htmlspecialchars($row['private_company'] ?? '');
                                $plateNumber = htmlspecialchars($row['plate_number'] ?? '');
                                $estimatedVolume = htmlspecialchars($row['estimated_volume_per_truck_kg'] ?? '');
                                $driverName = htmlspecialchars($row['driver_name'] ?? '');

                                $type = '';
                                $company_name_display = '';
                                $selected_type_value = '';

                                if (!empty($lguMunicipality)) {
                                    $type = 'LGU';
                                    $company_name_display = $lguMunicipality;
                                    $selected_type_value = 'LGU';
                                } elseif (!empty($privateCompany)) {
                                    $type = 'Private';
                                    $company_name_display = $privateCompany;
                                    $selected_type_value = 'Private';
                                } else {
                                    $type = 'Unknown';
                                    $company_name_display = 'N/A';
                                    $selected_type_value = 'Unknown';
                                }

                                echo "<tr>";
                                echo "<td>" . $entryDate . "</td>";
                                echo "<td>" . ($entryTime !== '' ? date('g:i A', strtotime($entryTime)) : 'N/A') . "</td>";
                                echo "<td>" . htmlspecialchars($type) . "</td>";
                                echo "<td>" . $company_name_display . "</td>";
                                echo "<td>" . $plateNumber . "</td>";
                                echo "<td>" . $estimatedVolume . " kg</td>";
                                echo "<td>" . $driverName . "</td>";

                                echo "<td class='action-buttons'>";
                                echo "<button class='edit-btn' onclick='openEditModalMunicipality("
                                     . $recordId . ", "
                                     . json_encode($entryDate) . ", "
                                     . json_encode($entryTime) . ", "
                                     . json_encode($selected_type_value) . ", "
                                     . json_encode($lguMunicipality) . ", "
                                     . json_encode($privateCompany) . ", "
                                     . json_encode($plateNumber) . ", "
                                     . json_encode($estimatedVolume) . ", "
                                     . json_encode($driverName)
                                     . ")'><i class='fas fa-edit'></i> Edit</button>";

                                echo "<button class='delete-btn' onclick='openDeleteConfirmModalMunicipality(" . $recordId . ", " . json_encode($company_name_display) . ")'><i class='fas fa-trash'></i> Delete</button>";
                                echo "</td>";
                                echo "</tr>";
                            }
                        }
                        ?>
                    </tbody>
                </table>
            <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- Add/Edit Municipality Record Modal Form -->
    <div id="addEditMunicipalityModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditMunicipalityModal')">×</span>
            <h3 id="municipalityModalTitle">Add New Record</h3>

            <form id="addEditMunicipalityForm" novalidate>
                <input type="hidden" id="recordId" name="id">
                <input type="hidden" id="actionTypeMunicipality" name="action">

                <label for="entry_date">Date:</label>
                <input type="date" id="entry_date" name="entry_date" required>
                <span class="error-message" id="entry_dateError"></span>

                <label for="entry_time">Time:</label>
                <input type="time" id="entry_time" name="entry_time">
                <span class="error-message" id="entry_timeError"></span>

                <label for="type">Type:</label>
                <select id="type" name="type" required onchange="handleTypeChange()">
                    <option value="">-- Select Type --</option>
                    <option value="LGU">LGU</option>
                    <option value="Private">Private</option>
                </select>
                 <span class="error-message" id="typeError"></span>

                 <div class="lgu-private-group">
                     <div id="lgu_municipality_group" style="display: none;">
                         <label for="lgu_municipality">LGU Municipality:</label>
                         <input type="text" id="lgu_municipality" name="lgu_municipality">
                         <span class="error-message" id="lgu_municipalityError"></span>
                     </div>
                     <div id="private_company_group" style="display: none;">
                          <label for="private_company">Private Company:</label>
                         <input type="text" id="private_company" name="private_company">
                         <span class="error-message" id="private_companyError"></span>
                     </div>
                 </div>

                <label for="plate_number">Plate Number:</label>
                <input type="text" id="plate_number" name="plate_number" required>
                 <span class="error-message" id="plate_numberError"></span>

                <label for="estimated_volume_per_truck_kg">Estimated Volume (in kg):</label>
                <input type="number" id="estimated_volume_per_truck_kg" name="estimated_volume_per_truck_kg" required step="0.01">
                <span class="error-message" id="estimated_volume_per_truck_kgError"></span>

                <label for="driver_name">Driver's Name:</label>
                <input type="text" id="driver_name" name="driver_name" required>
                <span class="error-message" id="driver_nameError"></span>

                <button type="button" class="save-btn" onclick="submitMunicipalityForm()">Save</button>
                <button type="button" class="cancel-btn" onclick="closeModal('addEditMunicipalityModal')">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal (Municipality) -->
    <div id="deleteConfirmMunicipalityModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('deleteConfirmMunicipalityModal')">×</span>
            <h3>Confirm Deletion</h3>
            <p id="deleteConfirmMunicipalityText">Are you sure you want to delete this record?</p>
            <input type="hidden" id="deleteMunicipalityRecordId">
            <button class="confirm-btn" onclick="confirmDeleteMunicipalityRecord()">Yes, Delete</button>
            <button class="cancel-btn" onclick="closeModal('deleteConfirmMunicipalityModal')">Cancel</button>
        </div>
    </div>

     <!-- Logout Confirmation Modal -->
    <div id="logoutModal" class="modal">
        <div class="modal-content" style="max-width: 350px; text-align: center;">
            <span class="close-btn" onclick="closeModal('logoutModal')">×</span>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to logout?</p>
            <button class="confirm-btn" onclick="logout()">Yes</button>
            <button class="cancel-btn" onclick="closeModal('logoutModal')">Cancel</button>
        </div>
    </div>


    <script>
        // --- DataTables Initialization ---
        let municipalityDataTable;

        $(document).ready(function() {
             const $tableBody = $('#municipalityTable tbody');
             const hasDataRows = $tableBody.find('tr').length > 0 && $tableBody.find('tr:first').find('td, th').length > 1;

            if ($('#municipalityTable').length && hasDataRows) {
                $.fn.dataTable.ext.search.push(
                    function(settings, data, dataIndex) {
                        const selectedType = $('#type-filter').val();
                        const typeColumnData = data[2]; // Index for Type column

                         if (selectedType === '') {
                            return true;
                        }
                        return typeColumnData === selectedType;
                    }
                );

                 municipalityDataTable = $('#municipalityTable').DataTable({
                    "order": [[ 0, "desc" ], [ 1, "desc" ]],
                    "columnDefs": [
                       { "orderable": false, "targets": 7 } // Index for Actions column
                    ],
                     "pageLength": 10,
                      "serverSide": false,
                      "processing": false,
                      "ajax": null
                 });

                $('#type-filter').on('change', function() {
                    municipalityDataTable.draw();
                });

            } else {
                 console.log("DataTables not initialized on #municipalityTable due to no data or table not found.");
            }
        });

        // --- General Utility Functions ---
        function clearFormErrors(formId) {
             $('#' + formId + ' .error-message').text('').hide();
             $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
        }

         // Function to update LGU/Private input visibility based on Type selection
         // MODIFIED handleTypeChange - No longer clears values here
         function handleTypeChange() {
             console.log('handleTypeChange called'); // Debugging
             const type = $('#type').val();
             const $lguGroup = $('#lgu_municipality_group');
             const $privateGroup = $('#private_company_group');
             const $lguInput = $('#lgu_municipality');
             const $privateInput = $('#private_company');

            // Hide both groups initially
            $lguGroup.hide();
            $privateGroup.hide();

            // *** REMOVED: Value clearing moved to closeModal ***
            // $lguInput.val('');
            // $privateInput.val('');

            // Remove required attribute from both initially (required logic is handled in submit validation)
            $lguInput.prop('required', false);
            $privateInput.prop('required', false);

             // Clear any errors associated with these fields visually
             $('#lgu_municipalityError').text('').hide().removeClass('error');
             $('#private_companyError').text('').hide().removeClass('error');
             $lguInput.removeClass('error');
             $privateInput.removeClass('error');


             // Show the relevant field based on selected type and set required attribute (for visual)
             if (type === 'LGU') {
                 $lguGroup.show();
                 $lguInput.prop('required', true); // Keep visual required indicator if desired
             } else if (type === 'Private') {
                 $privateGroup.show();
                 $privateInput.prop('required', true); // Keep visual required indicator if desired
             }
             // 'Unknown' type cannot be selected in the form.
         }


        // --- Modal Control Functions ---
        function openModal(modalId) {
            $('#' + modalId).css('display', 'flex');
        }

        // MODIFIED closeModal - Ensures form reset and specific clears
        function closeModal(modalId) {
             $('#' + modalId).css('display', 'none');
            if (modalId === 'addEditMunicipalityModal') {
                 // *** MODIFICATION: Reset the form FIRST to clear standard fields ***
                 $('#addEditMunicipalityForm')[0].reset();

                 // Explicitly clear LGU/Private fields as reset might not fully clear them due to structure
                 $('#lgu_municipality').val('');
                 $('#private_company').val('');

                 $('#recordId').val('');
                 $('#actionTypeMunicipality').val('');
                 clearFormErrors('addEditMunicipalityForm');

                 // *** MODIFICATION: Call handleTypeChange *after* reset to hide groups for a clean form ***
                 handleTypeChange(); // Resets visibility and required state for the next open
            }
            if (modalId === 'deleteConfirmMunicipalityModal') {
                $('#deleteMunicipalityRecordId').val('');
                $('#deleteConfirmMunicipalityText').text('Are you sure you want to delete this record?');
            }
            if (modalId === 'logoutModal') {
                 // Reset logout modal state if needed (none currently)
            }
        }

        // --- Add Municipality Record Functionality ---
        function openAddModalMunicipality() {
            // Call closeModal FIRST to reset everything for a clean add form
            closeModal('addEditMunicipalityModal');

            $('#municipalityModalTitle').text('Add New Municipality Record');
            $('#actionTypeMunicipality').val('add');
            $('#recordId').val('');

            const today = new Date();
            const yyyy = today.getFullYear();
            const mm = String(today.getMonth() + 1).padStart(2, '0');
            const dd = String(today.getDate()).padStart(2, '0');
            $('#entry_date').val(`${yyyy}-${mm}-${dd}`);

            $('#entry_time').val(''); // Ensure time is empty by default for add

            // Set type to empty initially and trigger change to hide groups
            $('#type').val(''); // Set value first
            handleTypeChange(); // Call explicitly after setting type to empty to set initial state


            openModal('addEditMunicipalityModal');
            $('#entry_date').focus();
        }

        // --- Edit Municipality Record Functionality ---
        // MODIFIED openEditModalMunicipality - Ensures reset before populating and triggers type change correctly
        function openEditModalMunicipality(id, entryDate, entryTime, type, lguMunicipality, privateCompany, plateNumber, estimatedVolume, driverName) {
             // *** MODIFICATION: Call closeModal FIRST to reset form and hide groups ***
             closeModal('addEditMunicipalityModal'); // Ensures form is reset, values cleared, groups hidden initially

            $('#municipalityModalTitle').text('Edit Municipality Record');
            $('#actionTypeMunicipality').val('edit');
            $('#recordId').val(id);

            // Populate the form fields with EXISTING data from the record
            $('#entry_date').val(entryDate);
            $('#entry_time').val(entryTime); // Time value from DB is HH:MM:SS, input type="time" expects HH:MM

            $('#type').val(type);
            // Populate the correct name field based on type
            if (type === 'LGU') {
                $('#lgu_municipality').val(lguMunicipality);
                // The other field (#private_company) is already cleared by closeModal reset
            } else if (type === 'Private') {
                $('#private_company').val(privateCompany);
                 // The other field (#lgu_municipality) is already cleared by closeModal reset
            }
            // Populate other fields
            $('#plate_number').val(plateNumber);
            $('#estimated_volume_per_truck_kg').val(estimatedVolume);
            $('#driver_name').val(driverName);

            // *** MODIFICATION: Manually trigger the 'change' event on the #type dropdown AFTER populating it ***
            // This calls handleTypeChange() which will show the correct group (LGU or Private)
            // based on the type we just set, *without* clearing the name values we just set.
            $('#type').trigger('change');


            openModal('addEditMunicipalityModal');
             $('#entry_date').focus();
        }

        // --- Delete Municipality Record Functionality ---
        function openDeleteConfirmModalMunicipality(id, recordDisplayName) {
            $('#deleteMunicipalityRecordId').val(id);
            $('#deleteConfirmMunicipalityText').text('Are you sure you want to delete record for: ' + recordDisplayName + '?');
            openModal('deleteConfirmMunicipalityModal');
        }

        function confirmDeleteMunicipalityRecord() {
            const id = $('#deleteMunicipalityRecordId').val();
            if (!id) {
                alert('Error: Record ID not found for deletion.');
                return;
            }

             $('.confirm-btn').prop('disabled', true).text('Deleting...');

            $.ajax({
                url: 'handle_municipality_actions.php',
                type: 'POST',
                data: {
                    action: 'delete',
                    id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Record deleted successfully!');
                        closeModal('deleteConfirmMunicipalityModal');
                        location.reload();

                    } else {
                        alert('Error deleting record: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function(xhr, status, error) {
                     alert('An AJAX error occurred: ' + status + '\nError: ' + (xhr.responseText || error));
                     console.error("AJAX Error:", xhr.responseText);
                },
                 complete: function() {
                     $('.confirm-btn').prop('disabled', false).text('Yes, Delete');
                 }
            });
        }

        // --- Form Submission (Add/Edit Municipality Record) ---
        // This function's validation logic remains largely the same, enforcing 'required' only for 'add'
        function submitMunicipalityForm() {
            const form = $('#addEditMunicipalityForm');
            const action = $('#actionTypeMunicipality').val();
            clearFormErrors('addEditMunicipalityForm');

            let isValid = true;
            let firstErrorField = null;

            function markError(fieldId, message) {
                $('#' + fieldId).addClass('error');
                $('#' + fieldId + 'Error').text(message).show();
                isValid = false;
                if (!firstErrorField) {
                    firstErrorField = $('#' + fieldId);
                }
            }

            // --- Validation rules applied for both ADD and EDIT ---
            if (!$('#entry_date').val().trim()) { markError('entry_date', 'Date is required.'); }
            const typeVal = $('#type').val();
            if (!typeVal) {
                 markError('type', 'Type is required.');
            }

             const volumeVal = $('#estimated_volume_per_truck_kg').val();
             if (volumeVal !== '' && volumeVal !== null && (isNaN(volumeVal) || parseFloat(volumeVal) < 0)) {
                 markError('estimated_volume_per_truck_kg', 'Volume must be a positive number if entered.');
             }

            // --- Additional Validation rules applied ONLY for ADD ---
            if (action === 'add') {
                 if (typeVal === 'LGU' && !$('#lgu_municipality').val().trim()) {
                     markError('lgu_municipality', 'LGU Municipality is required for LGU type when adding.');
                 }
                  if (typeVal === 'Private' && !$('#private_company').val().trim()) {
                     markError('private_company', 'Private Company is required for Private type when adding.');
                 }
                if (!$('#plate_number').val().trim()) { markError('plate_number', 'Plate Number is required for new records.'); }
                 if (volumeVal === '' || volumeVal === null) {
                     markError('estimated_volume_per_truck_kg', 'Estimated Volume is required for new records.');
                 }
                if (!$('#driver_name').val().trim()) { markError('driver_name', 'Driver\'s Name is required for new records.'); }
            }

             if (!isValid) {
                 if(firstErrorField) firstErrorField.focus();
                 return;
             }

            let formData = form.serialize();

            $('.save-btn').prop('disabled', true).text('Saving...');

            $.ajax({
                url: 'handle_municipality_actions.php',
                type: 'POST',
                data: formData,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        alert(response.message || 'Operation successful!');
                        closeModal('addEditMunicipalityModal');
                        location.reload();

                    } else {
                        if (response.errors) {
                            $.each(response.errors, function(key, message) {
                                markError(key, message);
                            });
                            if(firstErrorField) firstErrorField.focus();
                         } else {
                            alert('Error: ' + (response.message || 'Operation failed. Please check the details.'));
                         }
                    }
                },
                error: function(xhr, status, error) {
                     alert('An AJAX error occurred: ' + status + '\nError: ' + (xhr.responseText || error));
                     console.error("AJAX Error:", xhr.responseText);
                },
                complete: function() {
                     $('.save-btn').prop('disabled', false).text('Save');
                }
            });
        }


        // --- Logout Modal Functions ---
        function openLogoutModal() {
            openModal('logoutModal');
        }

        function logout() {
            window.location.href = "sign_in.php";
        }


        // Close modal if clicked outside the content area
        $(window).on('click', function(event) {
             $('.modal').each(function() {
                 if ($(event.target).hasClass('modal')) {
                    if (!$(this).find('button:disabled').length) {
                         closeModal($(this).attr('id'));
                    }
                 }
             });
        });

         $(document).ready(function() {
             // Initial call ensures the form looks correct if it's displayed on page load
             handleTypeChange();
         });

    </script>

<?php
// Close the connection at the end
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}

// Include footer template content here
require_once 'templates/footer.php';
?>

</body>
</html>