<?php
$host = 'localhost';
$db   = 'moodiary';
$user = 'root';
$pass = '';
$port = 3310; // ✅ Your custom MySQL port (default is 3306)

try {
    $pdo = new PDO("mysql:host=$host;port=$port;dbname=$db", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die(json_encode([
        "status" => "error",
        "message" => "Database connection failed: " . $e->getMessage()
    ]));
}
?>
