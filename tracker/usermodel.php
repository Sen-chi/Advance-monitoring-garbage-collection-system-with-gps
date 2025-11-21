<?php
/**
 * Fetches all users from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @return array An array of user data, or an empty array on failure.
 * @throws PDOException On database query failure.
 */
function getAllUsers(PDO $pdo): array
{
    $sql = "SELECT user_id, username, email, role, status FROM user_table ORDER BY username ASC";
    $stmt = $pdo->query($sql);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Checks if an email is already in use by another user.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param string $email The email address to check.
 * @param int|null $excludeUserId An optional user ID to exclude from the search (for updates).
 * @return bool True if the email is taken, false otherwise.
 * @throws PDOException On database query failure.
 */
function isEmailTaken(PDO $pdo, string $email, ?int $excludeUserId = null): bool
{
    $sql = "SELECT 1 FROM user_table WHERE email = :email";
    if ($excludeUserId !== null) {
        $sql .= " AND user_id != :user_id";
    }
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':email', $email, PDO::PARAM_STR);
    if ($excludeUserId !== null) {
        $stmt->bindValue(':user_id', $excludeUserId, PDO::PARAM_INT);
    }
    $stmt->execute();
    return $stmt->fetchColumn() !== false;
}

/**
 * Adds a new user to the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param array $userData Associative array with keys: 'username', 'email', 'password' (hashed), 'role', 'status'.
 * @return bool True on success, false on failure.
 * @throws PDOException On database query failure.
 */
function addUser(PDO $pdo, array $userData): bool
{
    $sql = "INSERT INTO user_table (username, email, password, role, status)
            VALUES (:username, :email, :password, :role, :status)";
    $stmt = $pdo->prepare($sql);
    
    return $stmt->execute([
        ':username' => $userData['username'],
        ':email'    => $userData['email'],
        ':password' => $userData['password'],
        ':role'     => $userData['role'],
        ':status'   => $userData['status'],
    ]);
}

/**
 * Updates a user's profile data (username, email, password, role).
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $userId The ID of the user to update.
 * @param array $userData Associative array of data to update. Keys can include 'username', 'email', 'password', 'role'.
 * @return bool True if the update affected at least one row, false otherwise.
 * @throws PDOException On database query failure.
 */
function updateUser(PDO $pdo, int $userId, array $userData): bool
{
    $allowedFields = ['username', 'email', 'password', 'role'];
    $setParts = [];
    $params = [':user_id' => $userId];

    foreach ($allowedFields as $field) {
        if (isset($userData[$field]) && !empty($userData[$field])) {
            $setParts[] = "$field = :$field";
            $params[":$field"] = $userData[$field];
        }
    }

    if (empty($setParts)) {
        return false; // Nothing to update
    }

    $sql = "UPDATE user_table SET " . implode(', ', $setParts) . " WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->rowCount() > 0;
}

/**
 * Updates only the status of a user.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $userId The ID of the user whose status to update.
 * @param string $status The new status ('active' or 'inactive').
 * @return bool True if the update affected at least one row, false otherwise.
 * @throws PDOException On database query failure.
 */
function updateUserStatus(PDO $pdo, int $userId, string $status): bool
{
     $allowed_statuses = ['active', 'inactive'];
     if (!in_array($status, $allowed_statuses)) {
         return false; // Invalid status value
     }
     
     $sql = "UPDATE user_table SET status = :status WHERE user_id = :user_id";
     $stmt = $pdo->prepare($sql);
     $stmt->execute([
        ':status'   => $status,
        ':user_id'  => $userId
     ]);

     return $stmt->rowCount() > 0;
}


/**
 * Deletes a user from the database.
 *
 * @param PDO $pdo The PDO database connection object.
 * @param int $userId The ID of the user to delete.
 * @return bool True if the deletion affected at least one row, false otherwise.
 * @throws PDOException On database query failure.
 */
function deleteUser(PDO $pdo, int $userId): bool
{
    $sql = "DELETE FROM user_table WHERE user_id = :user_id";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->rowCount() > 0;
}