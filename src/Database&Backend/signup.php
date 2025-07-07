<?php
$host = 'localhost';
$user = 'root';
$password = 'Sebalimo06!';
$database = 'moodiary';

// Connect to database
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die(json_encode(['status' => 'error', 'message' => 'Database connection failed.']));
}

// Get raw POST data
$data = json_decode(file_get_contents("php://input"), true);

$email = $conn->real_escape_string($data['email']);
$password = password_hash($data['password'], PASSWORD_DEFAULT);

// Check if user already exists
$check = $conn->query("SELECT * FROM users WHERE email = '$email'");
if ($check->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
    exit;
}

// Insert new user
$sql = "INSERT INTO users (email, password) VALUES ('$email', '$password')";
if ($conn->query($sql)) {
    echo json_encode(['status' => 'success', 'message' => 'User registered successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
}
$conn->close();
?>