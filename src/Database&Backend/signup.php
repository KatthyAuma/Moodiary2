<?php
// Set headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Disable error display
ini_set('display_errors', 0);

// Initialize response array
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred',
    'data' => null
];

// Include database connection
require_once 'db_connect.php';

// Check if it's a POST request
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

// Validate required fields
$requiredFields = ['username', 'email', 'password', 'confirm_password'];
foreach ($requiredFields as $field) {
    if (empty($data[$field])) {
        $response['message'] = 'All fields are required';
        echo json_encode($response);
        exit;
    }
}

// Extract and sanitize data
$username = filter_var(trim($data['username']), FILTER_SANITIZE_STRING);
$email = filter_var(trim($data['email']), FILTER_SANITIZE_EMAIL);
$password = $data['password'];
$confirmPassword = $data['confirm_password'];
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

// Validate password length
if (strlen($password) < 8) {
    $response['message'] = 'Password must be at least 8 characters long';
    echo json_encode($response);
    exit;
}

// Validate password complexity
if (!preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/', $password)) {
    $response['message'] = 'Password must contain at least one uppercase letter, one lowercase letter, and one number';
    echo json_encode($response);
    exit;
}

// Check if passwords match
if ($password !== $confirmPassword) {
    $response['message'] = 'Passwords do not match';
    echo json_encode($response);
    exit;
}

try {
    $conn = getConnection();
    
    // Check if username already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE username = :username");
    $stmt->bindParam(':username', $username);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $response['message'] = 'Username already exists';
        echo json_encode($response);
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = :email");
    $stmt->bindParam(':email', $email);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        $response['message'] = 'Email already exists';
        echo json_encode($response);
        exit;
    }
    
    // Hash password
    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
    
    // Insert new user
    $stmt = $conn->prepare("
        INSERT INTO users (username, email, password, full_name, created_at)
        VALUES (:username, :email, :password, :full_name, NOW())
    ");
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':email', $email);
    $stmt->bindParam(':password', $hashedPassword);
    $stmt->bindParam(':full_name', $fullName);
    $stmt->execute();
    
    $userId = $conn->lastInsertId();
    
    // Create user session
    session_start();
    $_SESSION['user_id'] = $userId;
    $_SESSION['username'] = $username;
    
    // Return success response
    $response['status'] = 'success';
    $response['message'] = 'Account created successfully';
    $response['data'] = [
        'user_id' => $userId,
        'username' => $username,
        'full_name' => $fullName
    ];
    
    echo json_encode($response);
    
} catch (PDOException $e) {
    $response['message'] = 'Database error: ' . $e->getMessage();
    echo json_encode($response);
}
?>