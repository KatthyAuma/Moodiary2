<?php
// Enable error reporting for development but don't display errors
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Start session
session_start();

// Set content type to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Database connection configuration
require_once 'config.php';

// Response array
$response = [
    'status' => 'error',
    'message' => 'Unknown error occurred',
    'data' => null
];

try {
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $response['message'] = 'Unauthorized. Please log in.';
        echo json_encode($response);
        exit;
    }

    // Get user ID from session
    $userId = $_SESSION['user_id'];

    // Get action from request
    $action = isset($_GET['action']) ? $_GET['action'] : '';

    // Create database connection
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    switch ($action) {
        case 'get_trends':
            getMoodTrends($conn, $userId);
            break;
            
        case 'get_moods':
            getAllMoods($conn);
            break;
            
        default:
            $response['message'] = 'Invalid action';
            echo json_encode($response);
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Moods API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get all available moods
 */
function getAllMoods($conn) {
    global $response;
    
    $stmt = $conn->prepare("SELECT * FROM moods ORDER BY mood_name");
    $stmt->execute();
    
    $moods = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = $moods;
    
    echo json_encode($response);
    exit;
}

/**
 * Get mood trends for a user
 */
function getMoodTrends($conn, $userId) {
    global $response;
    
    // Get mood counts
    $stmt = $conn->prepare("
        SELECT 
            m.mood_name,
            COUNT(*) as count
        FROM journal_entries j
        JOIN moods m ON j.mood_id = m.mood_id
        WHERE j.user_id = :user_id
        GROUP BY j.mood_id
        ORDER BY count DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $moodCounts = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $moodCounts[$row['mood_name']] = (int)$row['count'];
    }
    
    // Get mood emojis
    $stmt = $conn->prepare("
        SELECT mood_name, emoji FROM moods
    ");
    $stmt->execute();
    
    $moodEmojis = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $moodEmojis[$row['mood_name']] = $row['emoji'];
    }
    
    // Get mood history (last 30 days)
    $stmt = $conn->prepare("
        SELECT 
            j.created_at,
            m.mood_name,
            m.emoji
        FROM journal_entries j
        JOIN moods m ON j.mood_id = m.mood_id
        WHERE j.user_id = :user_id
        ORDER BY j.created_at DESC
        LIMIT 30
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $moodHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Generate basic insights
    $insights = [];
    
    // Check if we have enough data
    if (count($moodCounts) > 0) {
        // Most common mood
        $topMood = array_keys($moodCounts)[0];
        $insights[] = "Your most common mood is '$topMood'.";
        
        // Mood variety
        $moodVariety = count($moodCounts);
        if ($moodVariety >= 5) {
            $insights[] = "You've experienced a wide variety of moods ($moodVariety different moods).";
        } else if ($moodVariety <= 2) {
            $insights[] = "Your mood entries show limited variety. Try to be more specific about how you're feeling.";
        }
        
        // Check for mood patterns in history
        if (count($moodHistory) >= 7) {
            // Check for consistent positive or negative moods
            $positiveMoods = ['happy', 'excited', 'grateful', 'relaxed', 'content', 'peaceful', 'joyful'];
            $negativeMoods = ['sad', 'anxious', 'angry', 'stressed', 'frustrated', 'depressed', 'overwhelmed'];
            
            $positiveCount = 0;
            $negativeCount = 0;
            
            foreach (array_slice($moodHistory, 0, 7) as $entry) {
                if (in_array(strtolower($entry['mood_name']), $positiveMoods)) {
                    $positiveCount++;
                } else if (in_array(strtolower($entry['mood_name']), $negativeMoods)) {
                    $negativeCount++;
                }
            }
            
            if ($positiveCount >= 5) {
                $insights[] = "You've been feeling mostly positive lately. Keep it up!";
            } else if ($negativeCount >= 5) {
                $insights[] = "You've been experiencing more negative emotions lately. Consider reaching out to someone you trust.";
            }
        }
    } else {
        $insights[] = "Add more journal entries to see personalized insights about your moods.";
    }
    
    // Prepare response data
    $responseData = [
        'mood_counts' => $moodCounts,
        'mood_emojis' => $moodEmojis,
        'mood_history' => $moodHistory,
        'insights' => $insights
    ];
    
    $response['status'] = 'success';
    $response['data'] = $responseData;
    
    echo json_encode($response);
    exit;
} 