<?php
// truckmodel.php

// --- NO DATABASE CONNECTION HERE ---
// This file contains functions that *use* a PDO connection passed to them.
// Error reporting configuration should be handled by the calling script (e.g., driver.php, handler.php).


/**
 * Fetches all truck records from the truck_info table.
 * Includes truck_id, plate_number, capacity_kg, model, and availability_status.
 * Orders by truck_id ascending.
 *
 * @param PDO $db_connection The PDO database connection object.
 * @return array An array of truck records (associative arrays). Returns an empty array on failure or no data.
 */
function getAllTrucks(PDO $db_connection): array {
    // Select capacity_kg and model columns, including availability_status
    $sql = "SELECT truck_id, plate_number, capacity_kg, model, availability_status FROM truck_info ORDER BY truck_id ASC";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->execute();
        // Fetch all results as associative arrays
        $trucks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $trucks;
    } catch (\PDOException $e) {
        // Log the error but don't expose details to the user
        error_log("Database Query Error in truckmodel.php::getAllTrucks(): " . $e->getMessage());
        // Return an empty array to signify no data was retrieved
        return [];
    }
}

/**
 * Fetches basic info (ID, plate, status) for all trucks from truck_info.
 * Used to populate the client-side truck list for the driver management page dropdown filtering.
 *
 * @param PDO $db_connection The PDO database connection object.
 * @return array An array of truck records (associative arrays with truck_id, plate_number, availability_status). Returns an empty array on failure or no data.
 */
function getAllTrucksForDriverDropdown(PDO $db_connection): array {
    try {
        // Select only necessary columns for the dropdown and client-side filtering
        $sql = "SELECT truck_id, plate_number, availability_status
                FROM truck_info
                ORDER BY plate_number ASC"; // Order by plate number for better dropdown usability
        $stmt = $db_connection->prepare($sql); // Use prepare for consistency
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
         error_log("Database Query Error in truckmodel.php::getAllTrucksForDriverDropdown(): " . $e->getMessage());
         return []; // Return empty array on error
    }
}


/**
 * Fetches a single truck record by ID from truck_info.
 * Includes truck_id, plate_number, capacity_kg, model, and availability_status.
 * Used for populating the edit modal.
 *
 * @param PDO $db_connection The PDO database connection object.
 * @param int $truck_id The truck_id of the record to fetch. Must be a positive integer.
 * @return array|null An associative array of truck data, or null if not found or on error.
 */
function getTruckById(PDO $db_connection, int $truck_id): ?array {
    // Basic validation for positive integer ID
    if ($truck_id <= 0) {
        error_log("Invalid truck ID passed to getTruckById: " . $truck_id);
        return null;
    }

    // Select capacity_kg and model columns, including availability_status
    $sql = "SELECT truck_id, plate_number, capacity_kg, model, availability_status FROM truck_info WHERE truck_id = :truck_id LIMIT 1";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
        $stmt->execute();
        // Fetch a single row as an associative array
        $truck = $stmt->fetch(PDO::FETCH_ASSOC);
        // fetch() returns false if no row is found, convert to null
        return $truck ?: null;
    } catch (\PDOException $e) {
        error_log("Database Query Error in truckmodel.php::getTruckById(ID: $truck_id): " . $e->getMessage());
        return null; // Return null on database error
    }
}


/**
 * Adds a new truck record to truck_info.
 * Inserts plate_number, capacity_kg, model, and availability_status.
 *
 * @param PDO $db_connection The PDO database connection object.
 * @param string $plate_number The plate number. Cannot be empty.
 * @param int|null $capacity_kg The capacity in kg (can be null). Must be non-negative if not null.
 * @param string|null $model The truck model (can be null or empty string).
 * @param string $availability_status The availability status ('Available', 'Assigned', 'Maintenance', 'Inactive').
 * @return bool True on success, false on failure.
 */
// MODIFIED: Parameter $availability_status expected to be one of the NEW enum values
function addTruck(PDO $db_connection, string $plate_number, ?int $capacity_kg, ?string $model, string $availability_status): bool {
    // Basic validation before query
    if (empty($plate_number)) {
        error_log("Attempted to add truck with empty plate number.");
        return false;
    }
     if ($capacity_kg !== null && $capacity_kg < 0) {
         error_log("Attempted to add truck with negative capacity: " . $capacity_kg);
         return false;
     }
     // Basic validation for status - rely more heavily on handler validation
     $allowed_statuses = ['Available', 'Assigned', 'Maintenance', 'Inactive']; // *** UPDATED statuses ***
     if (!in_array($availability_status, $allowed_statuses)) {
         error_log("Attempted to add truck with invalid status: " . $availability_status);
         return false;
     }


    // Insert plate_number, capacity_kg, model, and availability_status
    // Note: The SQL itself doesn't change, it just expects a valid ENUM value now.
    $sql = "INSERT INTO truck_info (plate_number, capacity_kg, model, availability_status)
            VALUES (:plate_number, :capacity_kg, :model, :availability_status)";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->bindParam(':plate_number', $plate_number, PDO::PARAM_STR);
        // Correctly bind capacity, using PARAM_NULL if the value is null
        $stmt->bindParam(':capacity_kg', $capacity_kg, $capacity_kg === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        // Correctly bind model
        $stmt->bindParam(':model', $model, $model === null || $model === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // Bind availability_status
        $stmt->bindParam(':availability_status', $availability_status, PDO::PARAM_STR);


        // execute() returns true on success, false on failure (e.g., constraint violation, invalid enum value)
        return $stmt->execute();
    } catch (\PDOException $e) {
        error_log("Database Insert Error in truckmodel.php::addTruck(): " . $e->getMessage());
        // More specific error handling (e.g., check $e->getCode() for duplicate entry) could go here
        return false;
    }
}

/**
 * Updates an existing truck record in truck_info.
 * Updates plate_number, capacity_kg, model, and availability_status based on truck_id.
 *
 * @param PDO $db_connection The PDO database connection object.
 * @param int $truck_id The truck_id of the record to update. Must be a positive integer.
 * @param string $plate_number The new plate number. Cannot be empty.
 * @param int|null $capacity_kg The new capacity in kg (can be null). Must be non-negative if not null.
 * @param string|null $model The new truck model (can be null or empty string).
 * @param string $availability_status The new availability status ('Available', 'Assigned', 'Maintenance', 'Inactive').
 * @return int|false Number of rows affected on success, false on failure.
 */
// MODIFIED: Parameter $availability_status expected to be one of the NEW enum values
function updateTruck(PDO $db_connection, int $truck_id, string $plate_number, ?int $capacity_kg, ?string $model, string $availability_status): int|false {
    // Basic validation before query
     if ($truck_id <= 0) {
        error_log("Invalid truck ID passed to updateTruck: " . $truck_id);
        return false;
    }
    if (empty($plate_number)) {
        error_log("Attempted to update truck ID " . $truck_id . " with empty plate number.");
        return false;
    }
     if ($capacity_kg !== null && $capacity_kg < 0) {
         error_log("Attempted to update truck ID " . $truck_id . " with negative capacity: " . $capacity_kg);
         return false;
     }
      // Basic validation for status
     $allowed_statuses = ['Available', 'Assigned', 'Maintenance', 'Inactive']; // *** UPDATED statuses ***
     if (!in_array($availability_status, $allowed_statuses)) {
         error_log("Attempted to update truck ID " . $truck_id . " with invalid status: " . $availability_status);
         return false;
     }


    // Update plate_number, capacity_kg, model, and availability_status
     // Note: The SQL itself doesn't change, it just expects a valid ENUM value now.
    $sql = "UPDATE truck_info
            SET plate_number = :plate_number,
                capacity_kg = :capacity_kg,
                model = :model,
                availability_status = :availability_status
            WHERE truck_id = :truck_id";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->bindParam(':plate_number', $plate_number, PDO::PARAM_STR);
         // Correctly bind capacity
        $stmt->bindParam(':capacity_kg', $capacity_kg, $capacity_kg === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
         // Correctly bind model
        $stmt->bindParam(':model', $model, $model === null || $model === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        // Bind availability_status
        $stmt->bindParam(':availability_status', $availability_status, PDO::PARAM_STR);
        $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);

        // execute() returns true on success, false on failure
        $success = $stmt->execute();

        if ($success) {
            return $stmt->rowCount(); // Return number of affected rows (0 if no change, >0 if updated)
        } else {
            return false; // Indicate execution failure (e.g., constraint violation, invalid enum value)
        }

    } catch (\PDOException $e) {
        error_log("Database Update Error in truckmodel.php::updateTruck(ID: $truck_id): " . $e->getMessage());
        return false;
    }
}

/**
 * Updates the availability status of a truck.
 * This function is intended for logic external to standard truck management (e.g., driver assignment).
 *
 * @param PDO $db_connection The database connection.
 * @param int $truckId The ID of the truck to update.
 * @param string $status The new status ('Available', 'Assigned', 'Maintenance', 'Inactive').
 * @return bool True on success, false on failure.
 */
function updateTruckAvailabilityStatus(PDO $db_connection, int $truckId, string $status): bool {
    // Basic validation
    if ($truckId <= 0 || empty($status)) {
        error_log("Invalid truck ID or status passed to updateTruckAvailabilityStatus.");
        return false;
    }
    // Validate status against allowed enum values for robustness
    $allowed_statuses = ['Available', 'Assigned', 'Maintenance', 'Inactive'];
     if (!in_array($status, $allowed_statuses)) {
         error_log("Attempted to set invalid status '" . $status . "' for truck ID: " . $truckId);
         return false;
     }


    $sql = "UPDATE truck_info SET availability_status = :status WHERE truck_id = :truck_id";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':truck_id', $truckId, PDO::PARAM_INT);
        return $stmt->execute(); // Returns true on success, false on failure
    } catch (\PDOException $e) {
        error_log("Database Update Error in truckmodel.php::updateTruckAvailabilityStatus(ID: $truckId, Status: $status): " . $e->getMessage());
        return false;
    }
}

/**
 * Gets the truck_id currently assigned to a specific driver.
 * Used by the driver handler to check the old truck assignment before updating.
 *
 * @param PDO $db_connection The database connection.
 * @param int $driverId The ID of the driver.
 * @return int|null The truck_id or null if no truck is assigned or driver not found.
 */
function getAssignedTruckIdForDriver(PDO $db_connection, int $driverId): ?int {
    if ($driverId <= 0) {
        error_log("Invalid driver ID passed to getAssignedTruckIdForDriver: " . $driverId);
        return null;
    }
    $sql = "SELECT truck_id FROM truck_driver WHERE driver_id = :driver_id LIMIT 1";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
        $stmt->execute();
        $result = $stmt->fetchColumn();
        // fetchColumn returns false if no rows or column is null, or the value itself.
        // We want null if the value is NULL or driver not found.
        return ($result === false) ? null : $result;
    } catch (\PDOException $e) {
        error_log("Database Query Error in truckmodel.php::getAssignedTruckIdForDriver(ID: $driverId): " . $e->getMessage());
        return null;
    }
}


/**
 * Deletes a truck record from truck_info based on truck_id.
 * Note: ON DELETE SET NULL on truck_driver will handle driver assignment.
 *
 * @param PDO $db_connection The PDO database connection object.
 * @param int $truck_id The truck_id of the record to delete. Must be a positive integer.
 * @return int|false Number of rows deleted on success, false on failure.
 */
function deleteTruck(PDO $db_connection, int $truck_id): int|false {
     // Basic validation before query
     if ($truck_id <= 0) {
        error_log("Invalid truck ID passed to deleteTruck: " . $truck_id);
        return false;
    }

    $sql = "DELETE FROM truck_info WHERE truck_id = :truck_id";
    try {
        $stmt = $db_connection->prepare($sql);
        $stmt->bindParam(':truck_id', $truck_id, PDO::PARAM_INT);
        $success = $stmt->execute(); // Returns true on success, false on failure

        if ($success) {
             return $stmt->rowCount(); // Return number of rows deleted (0 or 1)
        } else {
             return false; // Indicate execution failure
        }

    } catch (\PDOException $e) {
        error_log("Database Delete Error in truckmodel.php::deleteTruck(ID: $truck_id): " . $e->getMessage());
        return false;
    }
}

?>