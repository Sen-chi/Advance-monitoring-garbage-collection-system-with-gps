<?php
// handle_municipality_actions.php
session_start();

// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json'); // Indicate JSON response

$response = ['success' => false, 'message' => 'An unknown error occurred.', 'errors' => []];

// Include database connection
$pdo = null;
try {
    $pdo = require_once("db_connect.php");
    if (!$pdo instanceof PDO) {
         throw new Exception("Database connection failed.");
    }
} catch (Exception $e) {
    $response['message'] = "Database connection error: " . $e->getMessage();
    error_log("Municipality Action DB Error: " . $e->getMessage());
    echo json_encode($response);
    exit;
}

// Get action from POST request
$action = $_POST['action'] ?? '';
$id = $_POST['id'] ?? null; // record ID for edit/delete

switch ($action) {
    case 'add':
    case 'edit':
        // Get all potential data from POST
        $entryDate = $_POST['entry_date'] ?? '';
        $entryTime = $_POST['entry_time'] ?? ''; // HTML time input sends '' if empty
        $type = $_POST['type'] ?? ''; // 'LGU', 'Private'
        // Use null coalescing operator, but also check if the field was even submitted if needed
        // For form.serialize, these will always be submitted but might be empty strings
        $lguMunicipality = $_POST['lgu_municipality'] ?? '';
        $privateCompany = $_POST['private_company'] ?? '';
        $plateNumber = $_POST['plate_number'] ?? '';
        $estimatedVolume = $_POST['estimated_volume_per_truck_kg'] ?? '';
        $driverName = $_POST['driver_name'] ?? '';

        // --- Server-side Validation ---
        // Fields required for ADD have checks inside if ($action === 'add')
        // Fields required for BOTH ADD and EDIT have checks outside the if block
        // Fields optional on EDIT are only checked for format if a non-empty value is provided

        // Validation rules applied for both ADD and EDIT
        if (empty($entryDate)) {
            $response['errors']['entry_date'] = 'Date is required.';
        }
        if (empty($type)) {
             $response['errors']['type'] = 'Type is required.';
        } else {
             if ($type !== 'LGU' && $type !== 'Private') {
                 $response['errors']['type'] = 'Invalid type selected.';
             }
        }
         // Estimated Volume - Must be a valid number if *any* non-empty value is provided
         if (!empty($estimatedVolume) && (!is_numeric($estimatedVolume) || (float)$estimatedVolume < 0)) {
            $response['errors']['estimated_volume_per_truck_kg'] = 'Volume must be a positive number if entered.';
        }

        // Additional Validation rules applied ONLY for ADD
        if ($action === 'add') {
            if ($type === 'LGU' && empty($lguMunicipality)) {
                $response['errors']['lgu_municipality'] = 'LGU Municipality is required for LGU type when adding.';
            }
             if ($type === 'Private' && empty($privateCompany)) {
                $response['errors']['private_company'] = 'Private Company is required for Private type when adding.';
            }
            if (empty($plateNumber)) {
                 $response['errors']['plate_number'] = 'Plate Number is required for new records.';
            }
             if (empty($estimatedVolume)) {
                 $response['errors']['estimated_volume_per_truck_kg'] = 'Estimated Volume is required for new records.';
             }
            if (empty($driverName)) {
                 $response['errors']['driver_name'] = 'Driver\'s Name is required for new records.';
            }
        }

        // Add action specific validation (ID must be present and numeric for edit)
        if ($action === 'edit') {
            if (empty($id) || !is_numeric($id)) {
                 $response['errors']['id'] = 'Invalid record ID for editing.';
            }
        }

        // If validation fails, return errors
        if (!empty($response['errors'])) {
            $response['message'] = 'Validation failed. Please check the required fields.';
            echo json_encode($response);
            exit;
        }

        // --- Prepare for Database Operation ---

        try {
            if ($action === 'add') {
                // Prepare data for INSERT
                $data = [
                    'entry_date' => $entryDate,
                    'entry_time' => ($entryTime === '') ? null : $entryTime, // Convert empty time to null for DB
                    'lgu_municipality' => ($type === 'LGU') ? $lguMunicipality : null, // Store LGU name or null
                    'private_company' => ($type === 'Private') ? $privateCompany : null, // Store Private name or null
                    'plate_number' => $plateNumber, // Store plate number
                    'estimated_volume_per_truck_kg' => ($estimatedVolume !== '') ? (float)$estimatedVolume : null, // Convert empty volume to null for DB, else cast to float
                    'driver_name' => $driverName, // Store driver name
                ];

                // Perform INSERT query (all columns are included as they are required for add)
                $sql = "INSERT INTO mucipalities_record (entry_date, entry_time, lgu_municipality, private_company, plate_number, estimated_volume_per_truck_kg, driver_name)
                        VALUES (:entry_date, :entry_time, :lgu_municipality, :private_company, :plate_number, :estimated_volume_per_truck_kg, :driver_name)";
                $stmt = $pdo->prepare($sql);
                $stmt->execute($data);

                $response['success'] = true;
                $response['message'] = 'Record added successfully!';

            } elseif ($action === 'edit') {
                 // Dynamically build the UPDATE query and data array
                 $setClauses = [];
                 $updateData = [];

                 // Always include Date (it's required and essential)
                 $setClauses[] = 'entry_date = :entry_date';
                 $updateData['entry_date'] = $entryDate;

                 // Always include LGU and Private Company based on Type as the Type might change
                 // Use the submitted values, setting the unused one to NULL
                 // Also explicitly handle empty submitted strings for LGU/Private fields
                 $setClauses[] = 'lgu_municipality = :lgu_municipality';
                 // If type is LGU, use submitted value. If submitted is empty, store NULL. Otherwise, store NULL.
                 $updateData['lgu_municipality'] = ($type === 'LGU') ? ($lguMunicipality !== '' ? $lguMunicipality : null) : null;

                 $setClauses[] = 'private_company = :private_company';
                 // If type is Private, use submitted value. If submitted is empty, store NULL. Otherwise, store NULL.
                 $updateData['private_company'] = ($type === 'Private') ? ($privateCompany !== '' ? $privateCompany : null) : null;


                 // Conditionally include optional fields ONLY if they are submitted with a non-empty value OR if you specifically want to allow clearing them.
                 // The approach below allows clearing by submitting an empty value for these fields.
                 // If you submit empty, it sets the DB value to NULL. If you don't touch it (and it had a value), the PHP receives the old value via form.serialize().

                 // Time - Include if submitted. Set to NULL if empty string submitted.
                 $setClauses[] = 'entry_time = :entry_time';
                 $updateData['entry_time'] = ($entryTime !== '') ? $entryTime : null;


                 // Plate Number - Include if submitted. Set to NULL if empty string submitted.
                 $setClauses[] = 'plate_number = :plate_number';
                 $updateData['plate_number'] = ($plateNumber !== '') ? $plateNumber : null;


                 // Estimated Volume - Include if submitted. Set to NULL if empty string submitted.
                 $setClauses[] = 'estimated_volume_per_truck_kg = :estimated_volume_per_truck_kg';
                 $updateData['estimated_volume_per_truck_kg'] = ($estimatedVolume !== '') ? (float)$estimatedVolume : null;


                 // Driver's Name - Include if submitted. Set to NULL if empty string submitted.
                 $setClauses[] = 'driver_name = :driver_name';
                 $updateData['driver_name'] = ($driverName !== '') ? $driverName : null;


                 // Check if the ID is valid for editing
                 if (empty($id) || !is_numeric($id)) {
                     $response['message'] = 'Invalid record ID for editing.';
                     echo json_encode($response);
                     exit;
                 }

                 // Construct the final SQL query
                 // This will now always include ALL columns in the SET clause for edit,
                 // but the *values* in $updateData will be NULL if an optional field was submitted empty.
                 $sql = "UPDATE mucipalities_record SET " . implode(', ', array_keys($updateData)) . " WHERE municipal_record_id = :id";
                 // Let's rebuild the SET clause string explicitly for clarity based on updateData keys (excluding 'id')
                 $setClauses = [];
                 foreach($updateData as $key => $value) {
                     if ($key !== 'id') {
                         $setClauses[] = "`{$key}` = :{$key}"; // Use backticks for column names just in case
                     }
                 }
                 $sql = "UPDATE mucipalities_record SET " . implode(', ', $setClauses) . " WHERE municipal_record_id = :id";

                 $updateData['id'] = $id; // Add ID to data array for the WHERE clause

                 $stmt = $pdo->prepare($sql);
                 $stmt->execute($updateData);

                 // Check if record exists and report success even if no data changed (rowCount is 0)
                 $checkStmt = $pdo->prepare("SELECT COUNT(*) FROM mucipalities_record WHERE municipal_record_id = :id");
                 $checkStmt->execute(['id' => $id]);

                 if ($checkStmt->fetchColumn() == 0) {
                      $response['message'] = 'Error: Record not found.';
                 } else {
                      $response['success'] = true;
                      $response['message'] = 'Record updated successfully!';
                 }
            }

        } catch (\PDOException $e) {
             error_log("Municipality " . ($action === 'add' ? 'ADD' : 'EDIT') . " Database Error: " . $e->getMessage());
            $response['message'] = ($action === 'add' ? 'Add' : 'Edit') . " failed. A database error occurred.";
        }
        break;

    case 'delete':
        if (empty($id) || !is_numeric($id)) {
            $response['message'] = 'Invalid record ID for deletion.';
             echo json_encode($response);
            exit;
        }

        try {
            $sql = "DELETE FROM mucipalities_record WHERE municipal_record_id = :id";
            $stmt = $pdo->prepare($sql);
            $stmt->execute(['id' => $id]);

            if ($stmt->rowCount() > 0) {
                $response['success'] = true;
                $response['message'] = 'Record deleted successfully!';
            } else {
                $response['message'] = 'Error: Record not found or already deleted.';
            }

        } catch (\PDOException $e) {
             error_log("Municipality DELETE Database Error: " . $e->getMessage());
            $response['message'] = "Deletion failed. A database error occurred.";
        }
        break;

    default:
        $response['message'] = 'Invalid action specified.';
        break;
}

// Close connection
$pdo = null;

echo json_encode($response);
?>