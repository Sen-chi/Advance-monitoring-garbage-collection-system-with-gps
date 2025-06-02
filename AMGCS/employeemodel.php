<?php
// employeemodel.php

/**
 * Fetches all employees, joining with user_table to get username.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array|false An array of employee data, or false on failure.
 */
function getAllEmployees(PDO $pdo)
{
    $sql = "SELECT
                e.employee_id,
                e.user_id,
                e.first_name,
                e.middle_name,
                e.last_name,
                -- REMOVED: e.job_title, -- Removed as column was dropped
                -- REMOVED: e.hire_date, -- Removed as column was dropped
                e.contact_number,
                e.employee_status,
                u.username -- Join to get username
                -- REMOVED: u.email -- Removed based on original code (was commented out anyway)
            FROM
                employee e
            JOIN
                user_table u ON e.user_id = u.user_id"; // Using INNER JOIN assumes every employee has a user
    try {
        $stmt = $pdo->query($sql);
        // Fetch associative array
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        // Log the error and return false or throw exception
        error_log("Database error in getAllEmployees: " . $e->getMessage());
        return false; // Indicate failure
    }
}

/**
 * Fetches a single employee by ID.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $employeeId The ID of the employee to fetch.
 * @return array|false An associative array of employee data, or false if not found or on error.
 */
function getEmployeeById(PDO $pdo, int $employeeId)
{
    $sql = "SELECT
                employee_id,
                user_id,
                first_name,
                middle_name,
                last_name,
                -- REMOVED: job_title, -- Removed as column was dropped
                -- REMOVED: hire_date, -- Removed as column was dropped
                contact_number,
                employee_status
            FROM
                employee
            WHERE
                employee_id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getEmployeeById: " . $e->getMessage());
        return false;
    }
}

/**
 * Fetches users who are not currently linked to an employee record.
 * Useful for the "Add Employee" form dropdown.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array|false An array of user data (user_id, username), or false on failure.
 */
function getUnassignedUsers(PDO $pdo)
{
     $sql = "SELECT user_id, username
            FROM user_table
            WHERE user_id NOT IN (SELECT user_id FROM employee)";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getUnassignedUsers: " . $e->getMessage());
        return false;
    }
}


/**
 * Adds a new employee record.
 * Assumes the user account already exists.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param array $data Associative array of employee data (must include user_id).
 * @return int|false The ID of the newly inserted employee, or false on failure.
 */
function addEmployee(PDO $pdo, array $data)
{
    // Removed 'job_title' and 'hire_date' from the INSERT columns and VALUES
    $sql = "INSERT INTO employee (user_id, first_name, middle_name, last_name, contact_number, employee_status)
            VALUES (:user_id, :first_name, :middle_name, :last_name, :contact_number, :employee_status)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':middle_name', $data['middle_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        // REMOVED: $stmt->bindParam(':job_title', $data['job_title']); -- Removed binding
        // REMOVED: $stmt->bindParam(':hire_date', $data['hire_date']); -- Removed binding
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':employee_status', $data['employee_status']);

        if ($stmt->execute()) {
            return $pdo->lastInsertId(); // Return the ID of the new employee
        } else {
            error_log("PDO Error executing addEmployee: " . implode(":", $stmt->errorInfo()));
            return false;
        }
    } catch (PDOException $e) {
        error_log("Database error in addEmployee: " . $e->getMessage());
        return false;
    }
}

/**
 * Updates an existing employee record.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $employeeId The ID of the employee to update.
 * @param array $data Associative array of employee data.
 * @return bool True on success, false on failure.
 */
function updateEmployee(PDO $pdo, int $employeeId, array $data)
{
    // Note: user_id is not typically updated here as it links to a specific user account.
    // Removed 'job_title = :job_title,' and 'hire_date = :hire_date,' from the SET clause
    $sql = "UPDATE employee
            SET
                first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                contact_number = :contact_number,
                employee_status = :employee_status
            WHERE employee_id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $employeeId, PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':middle_name', $data['middle_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        // REMOVED: $stmt->bindParam(':job_title', $data['job_title']); -- Removed binding
         // REMOVED: $stmt->bindParam(':hire_date', $data['hire_date']); -- Removed binding
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':employee_status', $data['employee_status']);

        return $stmt->execute(); // Returns true on success, false on failure
    } catch (PDOException $e) {
        error_log("Database error in updateEmployee: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes an employee record by ID.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $employeeId The ID of the employee to delete.
 * @return bool|string True on success, false on failure, or 'foreign_key_error' on FK constraint violation.
 */
function deleteEmployee(PDO $pdo, int $employeeId)
{
    $sql = "DELETE FROM employee WHERE employee_id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        // Check if any rows were affected to confirm deletion
        return $stmt->rowCount() > 0; // Returns true if row was deleted, false if ID not found
    } catch (PDOException $e) {
        error_log("Database error in deleteEmployee: " . $e->getMessage());
        // Check if the error is due to a foreign key constraint violation
        if ($e->getCode() === '23000') { // SQLSTATE for Integrity Constraint Violation
             error_log("Foreign key constraint violation when deleting employee ID " . $employeeId);
             return 'foreign_key_error'; // Custom indicator for FK error
        }
        return false;
    }
}

?>