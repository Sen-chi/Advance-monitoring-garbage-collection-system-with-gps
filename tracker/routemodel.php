<?php
// routemodel.php

function getAllRoutes(PDO $pdo): array 
{
    try {
        // SELECT * automatically gets the new destination_lat/lon columns
        $stmt = $pdo->prepare("SELECT * FROM routes ORDER BY origin, destination");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (\PDOException $e) {
        error_log("Route Model Error (getAllRoutes): " . $e->getMessage());
        return [];
    }
}

/**
 * UPDATED: Adds a new route with coordinates to the database.
 */
function addRoute(PDO $pdo, string $origin, string $destination, ?string $waypoints, float $destLat, float $destLon): bool 
{
    // UPDATED: SQL query includes new coordinate columns
    $sql = "INSERT INTO routes (origin, destination, waypoints, destination_lat, destination_lon) 
            VALUES (:origin, :destination, :waypoints, :dest_lat, :dest_lon)";
    try {
        $stmt = $pdo->prepare($sql);
        // UPDATED: Execute with new coordinate parameters
        return $stmt->execute([
            ':origin' => $origin,
            ':destination' => $destination,
            ':waypoints' => empty($waypoints) ? null : $waypoints,
            ':dest_lat' => $destLat,
            ':dest_lon' => $destLon
        ]);
    } catch (\PDOException $e) {
        error_log("Route Model Error (addRoute): " . $e->getMessage());
        return false;
    }
}

/**
 * UPDATED: Updates an existing route with coordinates in the database.
 */
function updateRoute(PDO $pdo, int $routeId, string $origin, string $destination, ?string $waypoints, float $destLat, float $destLon): bool 
{
    // UPDATED: SQL query includes new coordinate columns
    $sql = "UPDATE routes 
            SET origin = :origin, destination = :destination, waypoints = :waypoints, 
                destination_lat = :dest_lat, destination_lon = :dest_lon 
            WHERE route_id = :route_id";
    
    try {
        $stmt = $pdo->prepare($sql);
        // UPDATED: Execute with new coordinate parameters
        return $stmt->execute([
            ':route_id' => $routeId,
            ':origin' => $origin,
            ':destination' => $destination,
            ':waypoints' => empty($waypoints) ? null : $waypoints,
            ':dest_lat' => $destLat,
            ':dest_lon' => $destLon
        ]);
    } catch (\PDOException $e) {
        error_log("Route Model Error (updateRoute): " . $e->getMessage());
        return false;
    }
}

/**
 * Deletes a route from the database.
 */
function deleteRoute(PDO $pdo, int $routeId): bool 
{
    // (This function does not need any changes)
    try {
        $stmt = $pdo->prepare("DELETE FROM routes WHERE route_id = :route_id");
        return $stmt->execute([':route_id' => $routeId]);
    } catch (\PDOException $e) {
        error_log("Route Model Error (deleteRoute): " . $e->getMessage());
        return false;
    }
}