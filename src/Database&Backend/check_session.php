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
    'status' => 'error',
    'logged_in' => false,
    'user_id' => null,
    'username' => null,
    'full_name' => null,
    'email' => null,
    'profile_image' => null,
    'message' => 'Not logged in'
];

// Check if user is logged in
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    try {
        // Create database connection
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Get user information
        $stmt = $conn->prepare("SELECT user_id, email, username, full_name, profile_image 
                               FROM users 
                               WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $_SESSION['user_id']);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            // Check session timeout - 24 hours (86400 seconds)
            if (isset($_SESSION['login_time']) && (time() - $_SESSION['login_time'] > 86400)) {
                // Session expired
                session_unset();
                session_destroy();
                
                $response['message'] = 'Session expired. Please log in again.';
            } else {
                // Update session time
                $_SESSION['login_time'] = time();
                
                // Set success response
                $response['status'] = 'success';
                $response['logged_in'] = true;
                $response['user_id'] = $user['user_id'];
                $response['username'] = $user['username'];
                $response['full_name'] = $user['full_name'];
                $response['email'] = $user['email'];
                $response['profile_image'] = $user['profile_image'] ?: 'default.png';
                $response['user'] = [
                    'user_id' => $user['user_id'],
                    'email' => $user['email'],
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'profile_image' => $user['profile_image'] ?: 'default.png'
                ];
                $response['message'] = 'User is logged in';
            }
        } else {
            // User not found in database but session exists
            session_unset();
            session_destroy();
            $response['message'] = 'User not found. Please log in again.';
        }
    } catch (Exception $e) {
        $response['message'] = 'Session check failed: ' . $e->getMessage();
        error_log('Session check error: ' . $e->getMessage());
    }
} else {
    // Debug information
    $response['debug'] = [
        'session_exists' => isset($_SESSION),
        'user_id_exists' => isset($_SESSION['user_id']),
        'logged_in_exists' => isset($_SESSION['logged_in']),
        'logged_in_value' => isset($_SESSION['logged_in']) ? $_SESSION['logged_in'] : null
    ];
}

// Return response
echo json_encode($response);
exit;
?> 