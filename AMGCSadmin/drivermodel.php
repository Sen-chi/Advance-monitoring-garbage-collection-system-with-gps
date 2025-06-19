<?php
// drivermodel.php

// Error reporting - leave this to the main script/config
// ini_set('display_errors', 0);
// ini_set('display_startup_errors', 0);
// error_reporting(E_ALL);

// --- NO DATABASE CONNECTION HERE ---
// This file contains functions that *use* a PDO connection passed to them.

// Include the truck model as we might need truck-related helper functions here later,
// or the handler needs access to both. For now, the handler includes both.

// require_once("truckmodel.php"); // Decided to include truckmodel directly in the handler

/**
 * Fetches all driver records from the truck_driver table.
 * Joins with truck_info to include the assigned truck's plate number.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of driver records (associative arrays). Returns an empty array on failure or no data.
 */
function getAllDrivers(PDO $pdo): array {
    try {
        // Select all columns from truck_driver (td.*) and the plate_number from truck_info (ti.plate_number)
        // LEFT JOIN is used so drivers without a truck assigned (truck_id IS NULL) are still included.
        $sql = "SELECT td.*, ti.plate_number
                FROM truck_driver td
                LEFT JOIN truck_info ti ON td.truck_id = ti.truck_id
                ORDER BY td.last_name, td.first_name"; // Order alphabetically by name
        $stmt = $pdo->prepare($sql); // Use prepare for consistency, though query is fine here
        $stmt->execute();
        // Fetch all results as associative arrays
        $drivers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        return $drivers;
    } catch (\PDOException $e) {
        error_log("Database Query Error in drivermodel.php::getAllDrivers(): " . $e->getMessage());
        // Return an empty array to signify no data was retrieved
        return []; // Return empty array on error
    }
}

/**
 * Fetches a single driver record by ID from truck_driver.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $driver_id The driver_id of the record to fetch. Must be a positive integer.
 * @return array|null An associative array of driver data, or null if not found or on error.
 */
function getDriverById(PDO $pdo, int $driver_id): ?array {
    // Basic validation for positive integer ID
    if ($driver_id <= 0) {
        error_log("Invalid driver ID passed to getDriverById: " . $driver_id);
        return null;
    }

    $sql = "SELECT driver_id, first_name, middle_name, last_name, contact_no, truck_id, status
            FROM truck_driver WHERE driver_id = :driver_id LIMIT 1";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':driver_id', $driver_id, PDO::PARAM_INT);
        $stmt->execute();
        // Fetch a single row as an associative array
        $driver = $stmt->fetch(PDO::FETCH_ASSOC);
        // fetch() returns false if no row is found, convert to null
        return $driver ?: null;
    } catch (\PDOException $e) {
        error_log("Database Query Error in drivermodel.php::getDriverById(ID: $driver_id): " . $e->getMessage());
        return null; // Return null on database error
    }
}


// --- Removed getAllTrucksForDropdown ---
// This logic is moved to truckmodel.php and handled client-side filtering.
// function getAllTrucksForDropdown(PDO $pdo): array { ... }


/**
 * Adds a new driver record to the truck_driver table.
 *
 * @param PDO $pdo The database connection.
 * @param string $firstName
 * @param string|null $middleName
 * @param string $lastName
 * @param string $contactNo
 * @param int|null $truckId The truck_id (can be null for unassigned).
 * @param string $status 'Active' or 'Inactive'.
 * @return bool True on success, false on failure.
 */
function addDriver(PDO $pdo, string $firstName, ?string $middleName, string $lastName, string $contactNo, ?int $truckId, string $status): bool {
     // Basic validation handled in handler
     // Add more specific validation if needed (e.g., contact format, status enum check)

    $sql = "INSERT INTO truck_driver (first_name, middle_name, last_name, contact_no, truck_id, status)
            VALUES (:first_name, :middle_name, :last_name, :contact_no, :truck_id, :status)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        // Bind middle_name, handling null/empty string as DB NULL
        $stmt->bindParam(':middle_name', $middleName, $middleName === null || $middleName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        $stmt->bindParam(':contact_no', $contactNo, PDO::PARAM_STR);
        // Bind truck_id, handling null
        $stmt->bindParam(':truck_id', $truckId, $truckId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        return $stmt->execute(); // Returns true on success, false on failure
    } catch (\PDOException $e) {
        error_log("Database Insert Error in drivermodel.php::addDriver(): " . $e->getMessage());
        // Consider specific error codes for duplicate entries, etc.
        return false;
    }
}

/**
 * Updates an existing driver record in the truck_driver table.
 *
 * @param PDO $pdo The database connection.
 * @param int $driverId The ID of the driver to update. Must be positive.
 * @param string $firstName
 * @param string|null $middleName
 * @param string $lastName
 * @param string $contactNo
 * @param int|null $newTruckId The new truck_id (can be null).
 * @param string $status The new status.
 * @return int|false Number of rows affected on success, false on failure.
 */
function updateDriver(PDO $pdo, int $driverId, string $firstName, ?string $middleName, string $lastName, string $contactNo, ?int $newTruckId, string $status): int|false {
    // Basic validation handled in handler
    // Add more specific validation if needed

    // Note: Truck status update logic is handled in the handler (handle_driver_actions.php)
    // to allow for transactional consistency between updating the driver and updating the truck status.

    $sql = "UPDATE truck_driver
            SET first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                contact_no = :contact_no,
                truck_id = :truck_id, -- This is the new truck_id
                status = :status
            WHERE driver_id = :driver_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
         // Bind middle_name, handling null/empty string
        $stmt->bindParam(':middle_name', $middleName, $middleName === null || $middleName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        $stmt->bindParam(':contact_no', $contactNo, PDO::PARAM_STR);
        // Bind the NEW truck_id, handling null
        $stmt->bindParam(':truck_id', $newTruckId, $newTruckId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);

        $success = $stmt->execute(); // Returns true on success, false on failure

        if ($success) {
             return $stmt->rowCount(); // Return number of affected rows (0 if no change, >0 if updated)
        } else {
             return false; // Indicate execution failure
        }

    } catch (\PDOException $e) {
        error_log("Database Update Error in drivermodel.php::updateDriver(ID: $driverId): " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a driver record from the truck_driver table.
 *
 * @param PDO $pdo The database connection.
 * @param int $driverId The ID of the driver to delete. Must be positive.
 * @return int|false Number of rows deleted on success, false on failure.
 */
function deleteDriver(PDO $pdo, int $driverId): int|false {
     // Basic validation handled in handler
    if ($driverId <= 0) {
        error_log("Invalid driver ID passed to deleteDriver: " . $driverId);
        return false;
    }

    // Note: Truck status update logic is handled in the handler (handle_driver_actions.php)
    // to allow for transactional consistency.

    $sql = "DELETE FROM truck_driver WHERE driver_id = :driver_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
        $success = $stmt->execute(); // Returns true on success, false on failure

        if ($success) {
             return $stmt->rowCount(); // Return number of rows deleted (0 or 1)
        } else {
             return false; // Indicate execution failure
        }

    } catch (\PDOException $e) {
        error_log("Database Delete Error in drivermodel.php::deleteDriver(ID: $driverId): " . $e->getMessage());
        return false;
    }
}

?>