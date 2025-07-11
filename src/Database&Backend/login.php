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
    'message' => '',
    'redirect' => '',
    'user' => null
];

// Process only POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        // If not JSON, try regular POST
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['email']) || empty($data['password'])) {
        $response['message'] = 'Please enter both email and password.';
        echo json_encode($response);
        exit;
    }
    
    // Sanitize inputs
    $email = filter_var($data['email'], FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    
    try {
        // Create database connection
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Get user by email
        $stmt = $conn->prepare("SELECT u.user_id, u.email, u.username, u.password, u.full_name, u.profile_image, 
                               GROUP_CONCAT(r.role_name) as roles 
                               FROM users u 
                               LEFT JOIN user_roles ur ON u.user_id = ur.user_id 
                               LEFT JOIN roles r ON ur.role_id = r.role_id 
                               WHERE u.email = :email 
                               GROUP BY u.user_id");
        $stmt->bindParam(':email', $email);
        $stmt->execute();
        
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$user) {
            $response['message'] = 'Invalid email or password.';
            echo json_encode($response);
            exit;
        }
        
        // Verify password
        if (!password_verify($password, $user['password'])) {
            $response['message'] = 'Invalid email or password.';
            echo json_encode($response);
            exit;
        }
        
        // Update last login time
        $stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->execute();
        
        // Log the activity
        $activityType = 'login';
        $description = 'User logged in';
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description) 
                               VALUES (:user_id, :activity_type, :description)");
        $stmt->bindParam(':user_id', $user['user_id']);
        $stmt->bindParam(':activity_type', $activityType);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
        
        // Set session variables
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['profile_image'] = $user['profile_image'] ?: 'default.png';
        $_SESSION['roles'] = explode(',', $user['roles']);
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        
        // Remove password from user data for response
        unset($user['password']);
        
        // Set success response
        $response['status'] = 'success';
        $response['message'] = 'Login successful!';
        $response['redirect'] = '../UI/home.php';
        $response['user'] = $user;
        
    } catch (Exception $e) {
        $response['message'] = 'Login failed: ' . $e->getMessage();
        error_log('Login error: ' . $e->getMessage());
    }
} else {
    $response['message'] = 'Invalid request method. Please use POST.';
}

// Always return JSON response
echo json_encode($response);
exit; 