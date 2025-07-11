<?php
/**
 * Database Configuration File
 * 
 * Contains database connection parameters and basic configuration settings
 */

// Database connection parameters
$db_host = 'localhost';
$db_name = 'moodiary';
$db_user = 'root';
$db_pass = ''; // Set your password here

// Application settings
$app_name = 'Moodiary';
$app_url = 'http://localhost/Moodiary2';

// Set session parameters
session_set_cookie_params(86400); // 24 hours
ini_set('session.cookie_lifetime', 86400);
ini_set('session.gc_maxlifetime', 86400);

// Set timezone
date_default_timezone_set('UTC');

/**
 * Database connection function
 * Returns a PDO connection object
 */
function getDbConnection() {
    global $db_host, $db_name, $db_user, $db_pass;
    
    try {
        $conn = new PDO("mysql:host=$db_host;dbname=$db_name", $db_user, $db_pass);
        $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $conn;
    } catch (PDOException $e) {
        error_log('Database connection failed: ' . $e->getMessage());
        return false;
    }
}
?> 