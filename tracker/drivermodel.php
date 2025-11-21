<?php
// drivermodel.php

/**
 * Fetches all driver records, joining with truck_info and user_table.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of driver records. Returns an empty array on failure.
 */
function getAllDrivers(PDO $pdo): array {
    try {
        // This query uses td.*, which will automatically fetch the renamed column 'contact_number'.
        $sql = "SELECT td.*, ti.plate_number, ut.username
                FROM truck_driver td
                LEFT JOIN truck_info ti ON td.truck_id = ti.truck_id
                LEFT JOIN user_table ut ON td.user_id = ut.user_id
                ORDER BY td.last_name, td.first_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Database Query Error in drivermodel.php::getAllDrivers(): " . $e->getMessage());
        return [];
    }
}

/**
 * Fetches available 'collector' user accounts that are not yet assigned to a driver.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int|null $currentUserId The user_id of the driver currently being edited, to ensure they appear in the list.
 * @return array An array of available user accounts.
 */
function getAvailableUserAccounts(PDO $pdo, ?int $currentUserId = null): array {
    try {
        $sql = "SELECT user_id, username FROM user_table 
                WHERE role = 'collector' AND (user_id NOT IN 
                    (SELECT user_id FROM truck_driver WHERE user_id IS NOT NULL) 
                OR user_id = :current_user_id)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['current_user_id' => $currentUserId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Database Query Error in drivermodel.php::getAvailableUserAccounts(): " . $e->getMessage());
        return [];
    }
}


/**
 * Adds a new driver record to the truck_driver table.
 *
 * @param PDO $pdo The database connection.
 * @param string $firstName
 * @param string|null $middleName
 * @param string $lastName
 * @param string $contactNumber  // Changed from $contactNo
 * @param int|null $truckId
 * @param int|null $userId
 * @param string $status
 * @return bool True on success, false on failure.
 */
function addDriver(PDO $pdo, string $firstName, ?string $middleName, string $lastName, string $contactNumber, ?int $truckId, ?int $userId, string $status): bool {
    // The SQL query already correctly uses 'contact_number'
    $sql = "INSERT INTO truck_driver (first_name, middle_name, last_name, contact_number, truck_id, user_id, status)
            VALUES (:first_name, :middle_name, :last_name, :contact_number, :truck_id, :user_id, :status)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':middle_name', $middleName, $middleName === null || $middleName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        // Updated variable to bind
        $stmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
        $stmt->bindParam(':truck_id', $truckId, $truckId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (\PDOException $e) {
        error_log("Database Insert Error in drivermodel.php::addDriver(): " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing driver record in the truck_driver table.
 *
 * @param PDO $pdo The database connection.
 * @param int $driverId
 * @param string $firstName
 * @param string|null $middleName
 * @param string $lastName
 * @param string $contactNumber // Changed from $contactNo
 * @param int|null $newTruckId
 * @param int|null $newUserId
 * @param string $status
 * @return int|false Number of rows affected on success, false on failure.
 */
function updateDriver(PDO $pdo, int $driverId, string $firstName, ?string $middleName, string $lastName, string $contactNumber, ?int $newTruckId, ?int $newUserId, string $status): int|false {
    // The SQL query already correctly uses 'contact_number'
    $sql = "UPDATE truck_driver
            SET first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                contact_number = :contact_number,
                truck_id = :truck_id,
                user_id = :user_id,
                status = :status
            WHERE driver_id = :driver_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':middle_name', $middleName, $middleName === null || $middleName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        // Updated variable to bind
        $stmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
        $stmt->bindParam(':truck_id', $newTruckId, $newTruckId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $newUserId, $newUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);

        $success = $stmt->execute();

        return $success ? $stmt->rowCount() : false;

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
    if ($driverId <= 0) {
        error_log("Invalid driver ID passed to deleteDriver: " . $driverId);
        return false;
    }

    $sql = "DELETE FROM truck_driver WHERE driver_id = :driver_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':driver_id', $driverId, PDO::PARAM_INT);
        $success = $stmt->execute();

        return $success ? $stmt->rowCount() : false;

    } catch (\PDOException $e) {
        error_log("Database Delete Error in drivermodel.php::deleteDriver(ID: $driverId): " . $e->getMessage());
        return false;
    }
}