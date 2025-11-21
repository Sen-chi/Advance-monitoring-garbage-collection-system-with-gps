<?php

$servername = "localhost";
$username = "root";
$password = "";
$dbname = "amgcs_db"; // Make sure this is your correct database name
$charset = 'utf8mb4';

$dsn = "mysql:host=$servername;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
    
    // This is correct. The file will return the connection object.
    return $pdo; 

} catch (PDOException $e) {
    // Instead of dying here, we throw the exception.
    // This allows the script that called this file (process_login.php)
    // to catch the error and handle it properly by sending a JSON response.
    throw new PDOException($e->getMessage(), (int)$e->getCode());
}
?>