<?php
// Enable error reporting for development
// Remove in production
error_reporting(E_ALL);
ini_set('display_errors', 0); // Disable HTML error output

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');

// Database connection configuration
require_once 'config.php';

// Response array
$response = [
    'status' => 'success',
    'message' => 'Logged out successfully',
    'redirect' => '../UI/signin.html'
];

// Check if user is logged in
if (isset($_SESSION['user_id'])) {
    try {
        // Create database connection
        $conn = getDbConnection();
        
        if ($conn) {
            // Log the activity
            $userId = $_SESSION['user_id'];
            $activityType = 'logout';
            $description = 'User logged out';
            
            $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description) 
                                   VALUES (:user_id, :activity_type, :description)");
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':activity_type', $activityType);
            $stmt->bindParam(':description', $description);
            $stmt->execute();
        }
    } catch (Exception $e) {
        error_log('Logout activity log error: ' . $e->getMessage());
        // Continue with logout even if logging fails
    }
}

// Destroy session
$_SESSION = array();

// If a session cookie is used, destroy it
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Destroy the session
session_destroy();

// Return response
echo json_encode($response);
exit;
?> 