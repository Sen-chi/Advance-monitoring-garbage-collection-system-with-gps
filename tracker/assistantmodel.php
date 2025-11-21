<?php
// assistantmodel.php

function getAllAssistants(PDO $pdo): array {
    try {
        // This query correctly fetches the new 'contact_number' column with ta.*
        $sql = "SELECT ta.*, ut.username
                FROM truck_assistant ta
                LEFT JOIN user_table ut ON ta.user_id = ut.user_id
                ORDER BY ta.last_name, ta.first_name";
        $stmt = $pdo->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Database Query Error in assistantmodel.php::getAllAssistants(): " . $e->getMessage());
        return [];
    }
}


function getAvailableUserAccountsForAssistants(PDO $pdo, ?int $currentUserId = null): array {
    try {
        $sql = "SELECT user_id, username 
                FROM user_table 
                WHERE role = 'collector' 
                AND (
                    user_id NOT IN (
                        SELECT user_id FROM truck_driver WHERE user_id IS NOT NULL
                        UNION
                        SELECT user_id FROM truck_assistant WHERE user_id IS NOT NULL
                    ) 
                    OR user_id = :current_user_id
                )";
        
        $stmt = $pdo->prepare($sql);
        $stmt->bindValue(':current_user_id', $currentUserId, $currentUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Database Query Error in assistantmodel.php::getAvailableUserAccountsForAssistants(): " . $e->getMessage());
        return [];
    }
}

// --- UPDATED FUNCTION ---
function addAssistant(PDO $pdo, string $firstName, ?string $middleName, string $lastName, string $contactNumber, ?int $userId, string $status): bool {
    // SQL updated to use contact_number
    $sql = "INSERT INTO truck_assistant (first_name, middle_name, last_name, contact_number, user_id, status)
            VALUES (:first_name, :middle_name, :last_name, :contact_number, :user_id, :status)";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':middle_name', $middleName, $middleName === null || $middleName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        // Bind the correct parameter to the correct placeholder
        $stmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $userId, $userId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);

        return $stmt->execute();
    } catch (\PDOException $e) {
        error_log("Database Insert Error in assistantmodel.php::addAssistant(): " . $e->getMessage());
        return false;
    }
}

// --- UPDATED FUNCTION ---
function updateAssistant(PDO $pdo, int $assistantId, string $firstName, ?string $middleName, string $lastName, string $contactNumber, ?int $newUserId, string $status): int|false {
    // SQL updated to use contact_number
    $sql = "UPDATE truck_assistant
            SET first_name = :first_name,
                middle_name = :middle_name,
                last_name = :last_name,
                contact_number = :contact_number,
                user_id = :user_id,
                status = :status
            WHERE assistant_id = :assistant_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':first_name', $firstName, PDO::PARAM_STR);
        $stmt->bindParam(':middle_name', $middleName, $middleName === null || $middleName === '' ? PDO::PARAM_NULL : PDO::PARAM_STR);
        $stmt->bindParam(':last_name', $lastName, PDO::PARAM_STR);
        // Bind the correct parameter to the correct placeholder
        $stmt->bindParam(':contact_number', $contactNumber, PDO::PARAM_STR);
        $stmt->bindParam(':user_id', $newUserId, $newUserId === null ? PDO::PARAM_NULL : PDO::PARAM_INT);
        $stmt->bindParam(':status', $status, PDO::PARAM_STR);
        $stmt->bindParam(':assistant_id', $assistantId, PDO::PARAM_INT);

        $success = $stmt->execute();

        return $success ? $stmt->rowCount() : false;

    } catch (\PDOException $e) {
        error_log("Database Update Error in assistantmodel.php::updateAssistant(ID: $assistantId): " . $e->getMessage());
        return false;
    }
}


function deleteAssistant(PDO $pdo, int $assistantId): int|false {
    if ($assistantId <= 0) {
        error_log("Invalid assistant ID passed to deleteAssistant: " . $assistantId);
        return false;
    }

    $sql = "DELETE FROM truck_assistant WHERE assistant_id = :assistant_id";
    try {
        $stmt = $pdo->prepare($sql);
        $stmt->bindParam(':assistant_id', $assistantId, PDO::PARAM_INT);
        $success = $stmt->execute();

        return $success ? $stmt->rowCount() : false;

    } catch (\PDOException $e) {
        error_log("Database Delete Error in assistantmodel.php::deleteAssistant(ID: $assistantId): " . $e->getMessage());
        return false;
    }
}