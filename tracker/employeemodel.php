<?php
// employeemodel.php

/**
 * Fetches all non-collector employees, joining with user_table to get username and role.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array|false An array of employee data, or false on failure.
 */
function getAllEmployees(PDO $pdo)
{
    // MODIFIED: Added u.role to SELECT and a WHERE clause to exclude 'collector'
    $sql = "SELECT
                e.employee_id,
                e.user_id,
                e.first_name,
                e.middle_name,
                e.last_name,
                e.contact_number,
                e.employee_status,
                u.username,
                u.role
            FROM
                employee e
            JOIN
                user_table u ON e.user_id = u.user_id
            WHERE
                u.role != 'collector'";
    try {
        $stmt = $pdo->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database error in getAllEmployees: " . $e->getMessage());
        return false;
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
                employee_id, user_id, first_name, middle_name, last_name,
                contact_number, employee_status
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
 * Fetches users (excluding collectors) who are not linked to an employee record.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array|false An array of user data (user_id, username), or false on failure.
 */
function getUnassignedUsers(PDO $pdo)
{
     // MODIFIED: Added a WHERE clause to exclude 'collector' roles
     $sql = "SELECT user_id, username
            FROM user_table
            WHERE role != 'collector' AND user_id NOT IN (SELECT user_id FROM employee)";
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
 * Updates an existing employee record.
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
 * Deletes an employee record by ID.
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