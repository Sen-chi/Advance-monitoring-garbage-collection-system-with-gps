<?php

// Assumes db_connect.php is included before this file or connection is passed

/**
 * Fetches all users from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of user data associative arrays, or an empty array on failure/no users.
 * @throws PDOException on query failure.
 */
function getAllUsers(PDO $pdo): array
{
    // Select the columns needed for the user management table
    $sql = "SELECT user_id, username, email, role, status FROM user_table ORDER BY username ASC";
    $stmt = $pdo->query($sql);
    // Set fetch mode before fetching
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    return $stmt->fetchAll();
}

/**
 * Checks if an email address is already taken, optionally excluding a specific user ID.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $email The email to check.
 * @param int|null $excludeUserId The user ID to exclude from the check (for updates).
 * @return bool True if the email is taken, false otherwise.
 * @throws PDOException on query failure.
 */
function isEmailTaken(PDO $pdo, string $email, ?int $excludeUserId = null): bool
{
    $sql = "SELECT user_id FROM user_table WHERE email = :email";
    if ($excludeUserId !== null) {
        $sql .= " AND user_id != :user_id";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    if ($excludeUserId !== null) {
        $stmt->bindParam(':user_id', $excludeUserId, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

/**
 * Adds a new user to the database.
 * Timestamps (created_at, updated_at) are handled automatically by the database schema.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param array $userData Associative array containing user data ('username', 'email', 'password' (hashed), 'role', 'status').
 * @return bool True on success, false on failure.
 * @throws PDOException on query failure.
 */
function addUser(PDO $pdo, array $userData): bool
{
    // Removed created_at, updated_at - DB handles them
    $sql = "INSERT INTO user_table (username, email, password, role, status)
            VALUES (:username, :email, :password, :role, :status)";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':username', $userData['username'], PDO::PARAM_STR);
    $stmt->bindParam(':email', $userData['email'], PDO::PARAM_STR);
    $stmt->bindParam(':password', $userData['password'], PDO::PARAM_STR); // The hashed password
    $stmt->bindParam(':role', $userData['role'], PDO::PARAM_STR);
    $stmt->bindParam(':status', $userData['status'], PDO::PARAM_STR);

    return $stmt->execute();
}

/**
 * Updates an existing user's data.
 * The updated_at timestamp is handled automatically by the database schema.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $userId The ID of the user to update.
 * @param array $userData Associative array containing data to update. Can include 'username', 'email', 'password' (hashed, optional), 'role', 'status'.
 * @return bool True if update was successful (affected rows > 0), false otherwise.
 * @throws PDOException on query failure.
 */
function updateUser(PDO $pdo, int $userId, array $userData): bool
{
    $setParts = [];
    $params = [':user_id' => $userId];

    // Define allowed fields to prevent arbitrary updates
    $allowedFields = ['username', 'email', 'password', 'role', 'status'];

    foreach ($allowedFields as $field) {
        if (isset($userData[$field])) {
             // Basic check for empty password, don't add to update if empty unless it's explicitly needed to clear? (Unlikely)
             if ($field === 'password' && empty($userData[$field])) {
                  continue; // Skip updating password if the provided value is empty
             }
            $setParts[] = "$field = :$field";
            $params[":$field"] = $userData[$field];
        }
    }


    if (empty($setParts)) {
        // No fields to update, consider it a success for the user request,
        // but return false to indicate nothing changed in the DB if needed by caller.
        return false; // Or true, depending on desired behavior when no data changes. Let's return false if no update was attempted.
    }

    // Removed "updated_at = NOW()" - DB handles it automatically via ON UPDATE clause
    $sql = "UPDATE user_table SET " . implode(', ', $setParts) . " WHERE user_id = :user_id";

    $stmt = $pdo->prepare($sql);

    // Bind parameters dynamically
    foreach ($params as $key => &$value) {
        // Determine PDO type - simple heuristic, could be more specific if needed
        $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
         // Use binary safe string type for password if hashing results in binary output (e.g. BINARY/VARBINARY column)
         // For typical VARCHAR columns storing base64/hex hash, PARAM_STR is fine.
         if ($key === ':password') {
             // If your password column is BINARY/VARBINARY, you might need PDO::PARAM_LOB or ensure the hash is suitable for STR
             // Assuming VARCHAR for base64/hex hashes, PARAM_STR is correct
             $type = PDO::PARAM_STR;
         }
        $stmt->bindParam($key, $value, $type);
    }
    unset($value); // Unset reference


    $stmt->execute();

    // Check if any rows were affected
    return $stmt->rowCount() > 0;
}

/**
 * Updates the status of an existing user.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $userId The ID of the user to update.
 * @param string $status The new status ('active' or 'inactive').
 * @return bool True if update was successful (affected rows > 0), false otherwise.
 * @throws PDOException on query failure.
 */
function updateUserStatus(PDO $pdo, int $userId, string $status): bool
{
     // Basic validation to ensure status is one of the allowed values
     $allowed_statuses = ['active', 'inactive'];
     if (!in_array($status, $allowed_statuses)) {
         // This should ideally be caught by handle_user_actions.php validation,
         // but adding a check here provides defense in depth.
         error_log("Attempted to set invalid status '$status' for user ID $userId");
         return false;
     }

     $sql = "UPDATE user_table SET status = :status WHERE user_id = :user_id";
     $stmt = $pdo->prepare($sql);
     $stmt->bindParam(':status', $status, PDO::PARAM_STR);
     $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);

     $stmt->execute();

     // Check if any rows were affected (user exists and status was different)
     return $stmt->rowCount() > 0;
}


/**
 * Deletes a user from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $userId The ID of the user to delete.
 * @return bool True if deletion was successful (affected rows > 0), false otherwise.
 * @throws PDOException on query failure.
 */
function deleteUser(PDO $pdo, int $userId): bool
{
    $sql = "DELETE FROM user_table WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}

?>