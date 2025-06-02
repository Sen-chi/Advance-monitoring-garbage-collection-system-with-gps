<?php
// Start the session (must be at the top of the file)
session_start();

// Set JSON response header BEFORE any output
header('Content-Type: application/json');

// Enable error reporting for development (disable in production)
// Make sure errors are logged even if not displayed
error_reporting(E_ALL);
ini_set('display_errors', 0); // Display errors can break JSON output
ini_set('log_errors', 1);     // Ensure errors are logged
// ini_set('error_log', '/path/to/your/php-error.log'); // Optional: Specify log file if needed

// Include PDO database connection
// The $pdo variable will be available in this scope if db_connect.php succeeds
require_once('db_connect.php'); // Just require it, don't assign the return value

// Ensure request is POST
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Invalid request method"]);
    exit();
}

// Validate input
if (empty($_POST['username_email']) || empty($_POST['password'])) {
    echo json_encode(["status" => "error", "message" => "Username/Email and Password are required"]);
    exit();
}

$username_email = trim($_POST['username_email']);
$password = $_POST['password'];

try {
    // Check if the $pdo variable was successfully created in db_connect.php
    if (!isset($pdo) || !$pdo instanceof PDO) {
        error_log("Database connection object (\$pdo) not available in process_login.php.");
        throw new Exception("Database connection unavailable.");
    }

    // --- MODIFICATION START ---
    // Prepare the query - Use distinct placeholders
    $stmt = $pdo->prepare("SELECT user_id, username, password FROM user_table WHERE username = :uname OR email = :email LIMIT 1");

    // Bind BOTH placeholders (using the same PHP variable)
    $stmt->bindValue(':uname', $username_email, PDO::PARAM_STR);
    $stmt->bindValue(':email', $username_email, PDO::PARAM_STR); // Bind the same variable to the second placeholder
    // --- MODIFICATION END ---

    // Execute the statement
    $stmt->execute(); // This should now work

    // Fetch user data
    $user = $stmt->fetch(PDO::FETCH_ASSOC); // Ensure associative array

    // Verify user existence and password
    if ($user && password_verify($password, $user['password'])) {
        session_regenerate_id(true); // Prevent session fixation

        // Store user data in session
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];

        echo json_encode(["status" => "success", "message" => "Login successful", "username" => $user['username']]);

    } else {
        // Log failed login attempt
        // Use htmlspecialchars to prevent potential XSS if viewing logs in a browser
        error_log("Login failed for user input: " . htmlspecialchars($username_email) . ". User found: " . ($user ? 'Yes' : 'No') . ". IP: " . $_SERVER['REMOTE_ADDR']);
        echo json_encode(["status" => "error", "message" => "Invalid credentials"]);
    }

} catch (PDOException $e) {
    // Log the error code along with the message for better diagnosis
    error_log("PDOException in process_login.php: [" . $e->getCode() . "] " . $e->getMessage());
    echo json_encode(["status" => "error", "message" => "Database query error. Please try again."]); // Slightly more specific user message
} catch (Exception $e) {
    error_log("Exception in process_login.php: " . $e->getMessage());
    // Optionally include more specific error message for debugging if needed, but be cautious in production
    echo json_encode(["status" => "error", "message" => "An unexpected error occurred." /* . $e->getMessage() */ ]);
} finally {
    // Cleanup
    if (isset($stmt) && $stmt instanceof PDOStatement) {
        $stmt->closeCursor();
    }
    $pdo = null;
}

?>