<?php
// Enable error reporting for development but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

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
    'data' => null
];

try {
    // Only allow POST requests
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        $response['message'] = 'Method not allowed';
        echo json_encode($response);
        exit;
    }

    // Get and validate input data
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        $response['message'] = 'Invalid request data';
        echo json_encode($response);
        exit;
    }

    // Validate required fields (no confirm_password)
    $requiredFields = ['username', 'email', 'password'];
    foreach ($requiredFields as $field) {
        if (empty($data[$field])) {
            $response['message'] = 'All required fields must be filled.';
            echo json_encode($response);
            exit;
        }
    }

    // Extract and sanitize data
    $username = filter_var(trim($data['username']), FILTER_SANITIZE_STRING);
    $email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
    $password = $data['password'];
    $fullName = isset($data['full_name']) ? filter_var(trim($data['full_name']), FILTER_SANITIZE_STRING) : '';

    // Validate email
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $response['message'] = 'Invalid email format';
        echo json_encode($response);
        exit;
    }

    // Validate username (alphanumeric and underscores only)
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
        $response['message'] = 'Username can only contain letters, numbers, and underscores';
        echo json_encode($response);
        exit;
    }

    // Validate username length
    if (strlen($username) < 3 || strlen($username) > 20) {
        $response['message'] = 'Username must be between 3 and 20 characters';
        echo json_encode($response);
        exit;
    }

    // Create database connection
    $conn = getDbConnection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Check if username already exists
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE username = :username');
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $response['message'] = 'Username already exists';
        echo json_encode($response);
        exit;
    }

    // Check if email already exists
    $stmt = $conn->prepare('SELECT user_id FROM users WHERE email = :email');
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    if ($stmt->rowCount() > 0) {
        $response['message'] = 'Email already exists';
        echo json_encode($response);
        exit;
    }

    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // Begin transaction
    $conn->beginTransaction();

    try {
        // Insert new user
        $stmt = $conn->prepare('INSERT INTO users (username, email, password, full_name, created_at) VALUES (:username, :email, :password, :full_name, NOW())');
        $stmt->bindParam(':username', $username);
        $stmt->bindParam(':email', $email);
        $stmt->bindParam(':password', $hashedPassword);
        $stmt->bindParam(':full_name', $fullName);
        $stmt->execute();

        $userId = $conn->lastInsertId();

        // Assign default 'user' role
        $stmt = $conn->prepare('SELECT role_id FROM roles WHERE role_name = :role_name');
        $roleName = 'user';
        $stmt->bindParam(':role_name', $roleName);
        $stmt->execute();
        $roleId = $stmt->fetchColumn();

        if ($roleId) {
            $stmt = $conn->prepare('INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindParam(':role_id', $roleId);
            $stmt->execute();
        }

        // Commit transaction
        $conn->commit();

        // Set session variables
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['email'] = $email;
        $_SESSION['logged_in'] = true;
        $_SESSION['login_time'] = time();
        $_SESSION['roles'] = ['user']; // Set default role in session

        // Return success response
        $response['status'] = 'success';
        $response['message'] = 'Account created successfully';
        $response['data'] = [
            'user_id' => $userId,
            'username' => $username,
            'full_name' => $fullName,
            'email' => $email,
            'roles' => ['user']
        ];
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        // Roll back transaction on error
        $conn->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    $response['message'] = 'Signup failed: ' . $e->getMessage();
    error_log('Signup error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}
?>