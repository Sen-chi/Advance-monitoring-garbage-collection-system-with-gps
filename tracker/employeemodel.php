<?php
// employeemodel.php

/**
 * Fetches ALL personnel (staff, drivers, assistants) who have an associated user account.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array|false An array of all personnel data, or false on failure.
 */
function getAllPersonnel(PDO $pdo)
{
    // This query combines results from three different personnel tables
    $sql = "
        -- First, get the general employees
        SELECT
            e.employee_id AS personnel_id,
            'Staff' AS personnel_type,
            e.user_id,
            u.username,
            e.first_name,
            e.middle_name,
            e.last_name,
            u.role,
            e.contact_number,
            e.employee_status AS status
        FROM employee e
        JOIN user_table u ON e.user_id = u.user_id

        UNION ALL

        -- Second, get the truck drivers
        SELECT
            td.driver_id AS personnel_id,
            'Driver' AS personnel_type,
            td.user_id,
            u.username,
            td.first_name,
            td.middle_name,
            td.last_name,
            u.role,
            td.contact_number,
            td.status
        FROM truck_driver td
        JOIN user_table u ON td.user_id = u.user_id
        WHERE td.user_id IS NOT NULL

        UNION ALL

        -- Third, get the truck assistants
        SELECT
            ta.assistant_id AS personnel_id,
            'Assistant' AS personnel_type,
            ta.user_id,
            u.username,
            ta.first_name,
            ta.middle_name,
            ta.last_name,
            u.role,
            ta.contact_number,
            ta.status
        FROM truck_assistant ta
        JOIN user_table u ON ta.user_id = u.user_id
        WHERE ta.user_id IS NOT NULL
    ";

    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getAllPersonnel: " . $e->getMessage());
        return false;
    }
}


/**
 * Fetches users who are not yet linked to ANY personnel record (employee, driver, or assistant).
 * This is used to populate the 'Add Employee' dropdown.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array|false An array of user data (user_id, username), or false on failure.
 */
function getUnassignedUsers(PDO $pdo)
{
     $sql = "SELECT user_id, username
            FROM user_table
            WHERE user_id NOT IN (
                SELECT user_id FROM employee WHERE user_id IS NOT NULL
                UNION
                SELECT user_id FROM truck_driver WHERE user_id IS NOT NULL
                UNION
                SELECT user_id FROM truck_assistant WHERE user_id IS NOT NULL
            )";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getUnassignedUsers: " . $e->getMessage());
        return false;
    }
}


/**
 * Fetches a single employee by ID from the 'employee' table.
 * Note: This remains unchanged and only works for 'Staff' type employees.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $employeeId The ID of the employee to fetch.
 * @return array|false An associative array of employee data, or false if not found or on error.
 */
function getEmployeeById(PDO $pdo, int $employeeId)
{
    $sql = "SELECT * FROM employee WHERE employee_id = :id";
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
 * Adds a new employee record to the 'employee' table.
 * Note: This is for non-driver/assistant roles.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param array $data Associative array of employee data.
 * @return int|false The ID of the newly inserted employee, or false on failure.
 */
function addEmployee(PDO $pdo, array $data)
{
    $sql = "INSERT INTO employee (user_id, first_name, middle_name, last_name, contact_number, employee_status)
            VALUES (:user_id, :first_name, :middle_name, :last_name, :contact_number, :employee_status)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':user_id', $data['user_id'], PDO::PARAM_INT);
        $stmt->bindParam(':first_name', $data['first_name']);
        $stmt->bindParam(':middle_name', $data['middle_name']);
        $stmt->bindParam(':last_name', $data['last_name']);
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':employee_status', $data['employee_status']);

        if ($stmt->execute()) {
            return $pdo->lastInsertId();
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
 * Updates an existing employee record in the 'employee' table.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $employeeId The ID of the employee to update.
 * @param array $data Associative array of employee data.
 * @return bool True on success, false on failure.
 */
function updateEmployee(PDO $pdo, int $employeeId, array $data)
{
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
        $stmt->bindParam(':contact_number', $data['contact_number']);
        $stmt->bindParam(':employee_status', $data['employee_status']);

        return $stmt->execute();
    } catch (PDOException $e) {
        error_log("Database error in updateEmployee: " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes an employee record by ID from the 'employee' table.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $employeeId The ID of the employee to delete.
 * @return bool|string True on success, false on failure, or 'foreign_key_error'.
 */
function deleteEmployee(PDO $pdo, int $employeeId)
{
    $sql = "DELETE FROM employee WHERE employee_id = :id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':id', $employeeId, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    } catch (PDOException $e) {
        error_log("Database error in deleteEmployee: " . $e->getMessage());
        if ($e->getCode() === '23000') {
             return 'foreign_key_error';
        }
        return false;
    }
}

?>