<?php
header("Content-Type: application/json");
require_once 'config.php'; // ✅ Load DB config

// Decode JSON input
$data = json_decode(file_get_contents("php://input"), true);
$email = trim($data['email'] ?? '');
$password = trim($data['password'] ?? '');

// Validate input
if (!$email || !$password) {
    echo json_encode(['status' => 'error', 'message' => 'Missing email or password.']);
    exit;
}

// Check if user already exists
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
$stmt->execute([$email]);
if ($stmt->rowCount() > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Email already exists.']);
    exit;
}

// Insert new user
$hashedPassword = password_hash($password, PASSWORD_DEFAULT);
$insert = $pdo->prepare("INSERT INTO users (email, password) VALUES (?, ?)");
if ($insert->execute([$email, $hashedPassword])) {
    echo json_encode(['status' => 'success', 'message' => 'User registered successfully.']);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Registration failed.']);
}
