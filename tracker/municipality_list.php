<?php
session_start();

$pageTitle = "Municipality Records";

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
        throw new Exception("Failed to get a valid database connection object from db_connect.php.");
    }
} catch (Exception $e) {
    $dbError = "Database Connection Error: " . $e->getMessage();
    error_log($dbError);
    $dbError = "Could not connect to the database. Please try again later.";
}

$municipalityRecords = [];
$fetchError = '';

if (!$dbError && $pdo instanceof PDO) {
    $sql = "SELECT municipal_record_id, entry_date, entry_time, lgu_municipality, private_company, plate_number, estimated_volume_per_truck_kg, driver_name
            FROM municipalities_record
            ORDER BY entry_date DESC, entry_time DESC";

    try {
        $stmt = $pdo->query($sql);
        if ($stmt) {
             $municipalityRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
             throw new Exception("Database query failed.");
        }
        if (empty($municipalityRecords)) {
             $no_records_message = "No municipality records found.";
        } else {
             $no_records_message = null;
        }
    } catch (\PDOException $e) {
        $fetchError = 'Could not retrieve municipality records: ' . $e->getMessage();
        error_log('Error fetching municipality records: ' . $e->getMessage());
        $municipalityRecords = [];
    } catch (Exception $e) {
        $fetchError = 'An error occurred during data retrieval: ' . $e->getMessage();
        error_log('General Error fetching municipality records: ' . $e->getMessage());
        $municipalityRecords = [];
    }
}

?>

<html>
<head>
    <link rel="stylesheet" href="css/municipality.css">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <style>
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
                                 . $recordId . ", " . json_encode($entryDate) . ", " . json_encode($entryTime) . ", " . json_encode($selected_type_value) . ", " . json_encode($lguMunicipality) . ", " . json_encode($privateCompany) . ", " . json_encode($plateNumber) . ", " . json_encode($estimatedVolume) . ", " . json_encode($driverName) . ")'><i class='fas fa-edit'></i> </button>";
                            echo "<button class='delete-btn' onclick='openDeleteConfirmModalMunicipality(" . $recordId . ", " . json_encode($company_name_display) . ")'><i class='fas fa-trash'></i> </button>";
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

 <!-- Add/Edit Municipality Modal -->
    <div id="addEditMunicipalityModal" class="modal">
        <div class="modal-content">
            <span class="close-btn" onclick="closeModal('addEditMunicipalityModal')">×</span>
            <h3 id="municipalityModalTitle">Add New Record</h3>
            <form id="addEditMunicipalityForm" novalidate>
                <input type="hidden" id="recordId" name="id">
                <input type="hidden" id="actionTypeMunicipality" name="action">

                <div class="form-row">
                    <div class="form-field">
                        <label for="entry_date">Date:</label>
                        <input type="date" id="entry_date" name="entry_date" required>
                        <span class="error-message" id="entry_dateError"></span>
                    </div>
                    <div class="form-field">
                        <label for="entry_time">Time:</label>
                        <input type="time" id="entry_time" name="entry_time">
                        <span class="error-message" id="entry_timeError"></span>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-field">
                        <label for="type">Type:</label>
                        <select id="type" name="type" required onchange="handleTypeChange()">
                            <option value="">-- Select Type --</option>
                            <option value="LGU">LGU</option>
                            <option value="Private">Private</option>
                        </select>
                        <span class="error-message" id="typeError"></span>
                    </div>
                    <div class="form-field">
                        <label for="plate_number">Plate Number:</label>
                        <input type="text" id="plate_number" name="plate_number" required>
                        <span class="error-message" id="plate_numberError"></span>
                    </div>
                </div>

                <!-- LGU/Private Group - ito ang dynamic field na magpapakita o magtatago -->
                <!-- It's initially hidden and shown via JS when 'Type' is selected -->
                <div class="form-row lgu-private-container" style="display: none;"> 
                    <div id="lgu_municipality_group" class="form-field" style="display: none;">
                        <label for="lgu_municipality">LGU Municipality:</label>
                        <input type="text" id="lgu_municipality" name="lgu_municipality">
                        <span class="error-message" id="lgu_municipalityError"></span>
                    </div>
                    <div id="private_company_group" class="form-field" style="display: none;">
                        <label for="private_company">Private Company:</label>
                        <input type="text" id="private_company" name="private_company">
                        <span class="error-message" id="private_companyError"></span>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-field">
                        <label for="driver_name">Driver's Name:</label>
                        <input type="text" id="driver_name" name="driver_name" required>
                        <span class="error-message" id="driver_nameError"></span>
                    </div>
                     <div class="form-field">
                        <label for="estimated_volume_per_truck_kg">Estimated Volume (in kg):</label>
                        <input type="number" id="estimated_volume_per_truck_kg" name="estimated_volume_per_truck_kg" required step="0.01">
                        <span class="error-message" id="estimated_volume_per_truck_kgError"></span>
                    </div>
                </div>

                <div class="modal-footer-buttons">
                    <button type="button" class="save-btn" onclick="submitMunicipalityForm()">Save</button>
                    <button type="button" class="cancel-btn" onclick="closeModal('addEditMunicipalityModal')">Cancel</button>
                </div>
            </form>
        </div>
    </div>

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

<script>
    let municipalityDataTable;
    $(document).ready(function() {
        const $tableBody = $('#municipalityTable tbody');
        const hasDataRows = $tableBody.find('tr').length > 0 && $tableBody.find('tr:first').find('td, th').length > 1;
        if ($('#municipalityTable').length && hasDataRows) {
            $.fn.dataTable.ext.search.push(
                function(settings, data, dataIndex) {
                    const selectedType = $('#type-filter').val();
                    const typeColumnData = data[2];
                    if (selectedType === '') { return true; }
                    return typeColumnData === selectedType;
                }
            );
            municipalityDataTable = $('#municipalityTable').DataTable({
                "order": [[ 0, "desc" ], [ 1, "desc" ]],
                "columnDefs": [ { "orderable": false, "targets": 7 } ],
                "pageLength": 10, "serverSide": false, "processing": false, "ajax": null
            });
            $('#type-filter').on('change', function() {
                municipalityDataTable.draw();
            });
        } else {
            console.log("DataTables not initialized on #municipalityTable due to no data or table not found.");
        }
    });

    function clearFormErrors(formId) {
        $('#' + formId + ' .error-message').text('').hide();
        $('#' + formId + ' input, #' + formId + ' select').removeClass('error');
    }

    // PASTE THIS NEW FUNCTION IN ITS PLACE
    function handleTypeChange() {
        const type = $('#type').val();
        const $lguContainer = $('#lgu_municipality_group');
        const $privateContainer = $('#private_company_group');
        const $lguPrivateRow = $('.lgu-private-container'); // This is the parent row

        // First, reset everything
        $lguContainer.hide().find('input').prop('required', false).removeClass('error');
        $privateContainer.hide().find('input').prop('required', false).removeClass('error');
        $('#lgu_municipalityError').text('').hide();
        $('#private_companyError').text('').hide();
        $lguPrivateRow.hide(); // Hide the entire parent row by default

        // Now, show the correct field AND its parent row
        if (type === 'LGU') {
            $lguContainer.show().find('input').prop('required', true);
            $lguPrivateRow.css('display', 'flex'); // This is the crucial line to show the row
        } else if (type === 'Private') {
            $privateContainer.show().find('input').prop('required', true);
            $lguPrivateRow.css('display', 'flex'); // This is the crucial line to show the row
        }
    }

    function openAddModalMunicipality() {
        $('#addEditMunicipalityForm')[0].reset();
        clearFormErrors('addEditMunicipalityForm');

        $('#municipalityModalTitle').text('Add New Municipality Record');
        $('#actionTypeMunicipality').val('add');
        $('#recordId').val('');
        const today = new Date();
        const yyyy = today.getFullYear();
        const mm = String(today.getMonth() + 1).padStart(2, '0');
        const dd = String(today.getDate()).padStart(2, '0');
        $('#entry_date').val(`${yyyy}-${mm}-${dd}`);
        $('#entry_time').val('');
        $('#type').val('');
        handleTypeChange();
        openModal('addEditMunicipalityModal');
        $('#entry_date').focus();
    }

    function openEditModalMunicipality(id, entryDate, entryTime, type, lguMunicipality, privateCompany, plateNumber, estimatedVolume, driverName) {
        $('#addEditMunicipalityForm')[0].reset();
        clearFormErrors('addEditMunicipalityForm');

        $('#municipalityModalTitle').text('Edit Municipality Record');
        $('#actionTypeMunicipality').val('edit');
        $('#recordId').val(id);
        $('#entry_date').val(entryDate);
        $('#entry_time').val(entryTime);
        $('#type').val(type);
        if (type === 'LGU') {
            $('#lgu_municipality').val(lguMunicipality);
        } else if (type === 'Private') {
            $('#private_company').val(privateCompany);
        }
        $('#plate_number').val(plateNumber);
        $('#estimated_volume_per_truck_kg').val(estimatedVolume);
        $('#driver_name').val(driverName);
        
        handleTypeChange();
        openModal('addEditMunicipalityModal');
        $('#entry_date').focus();
    }

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
            data: { action: 'delete', id: id },
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

        if (!$('#entry_date').val().trim()) { markError('entry_date', 'Date is required.'); }
        const typeVal = $('#type').val();
        if (!typeVal) { markError('type', 'Type is required.'); }
        const volumeVal = $('#estimated_volume_per_truck_kg').val();
        if (volumeVal !== '' && volumeVal !== null && (isNaN(volumeVal) || parseFloat(volumeVal) < 0)) {
            markError('estimated_volume_per_truck_kg', 'Volume must be a positive number if entered.');
        }
        if (action === 'add') {
            if (typeVal === 'LGU' && !$('#lgu_municipality').val().trim()) { markError('lgu_municipality', 'LGU Municipality is required for LGU type when adding.'); }
            if (typeVal === 'Private' && !$('#private_company').val().trim()) { markError('private_company', 'Private Company is required for Private type when adding.'); }
            if (!$('#plate_number').val().trim()) { markError('plate_number', 'Plate Number is required for new records.'); }
            if (volumeVal === '' || volumeVal === null) { markError('estimated_volume_per_truck_kg', 'Estimated Volume is required for new records.'); }
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

    $(document).ready(function() {
         handleTypeChange();
    });
</script>
<?php
if (isset($pdo) && $pdo instanceof PDO) {
    $pdo = null;
}
require_once 'templates/footer.php';
?>

</body>
</html>