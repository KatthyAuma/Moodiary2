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
    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $response['message'] = 'Unauthorized. Please log in.';
        echo json_encode($response);
        exit;
    }

    // Get user ID from session
    $userId = $_SESSION['user_id'];

    // Create database connection
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Get request type
    $requestType = isset($_GET['type']) ? $_GET['type'] : 'default';
    
    switch ($requestType) {
        case 'mood':
            // Get recommendations based on specific mood
            if (isset($_GET['mood'])) {
                getMoodRecommendations($conn, $userId, $_GET['mood']);
            } else {
                $response['message'] = 'Mood parameter is required';
                echo json_encode($response);
                exit;
            }
            break;
            
        case 'today':
            // Get recommendations based on today's journal entry
            getTodayRecommendations($conn, $userId);
            break;
            
        case 'personalized':
            // Get personalized recommendations based on user's history
            getPersonalizedRecommendations($conn, $userId);
            break;
            
        default:
            // Get default recommendations
            getDefaultRecommendations($conn, $userId);
            break;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Recommendations API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get recommendations based on specific mood
 */
function getMoodRecommendations($conn, $userId, $moodName) {
    global $response;
    
    // Sanitize mood name
    $moodName = htmlspecialchars(trim($moodName));
    
    // Get mood ID
    $stmt = $conn->prepare("SELECT mood_id FROM moods WHERE mood_name = :mood_name");
    $stmt->bindParam(':mood_name', $moodName);
    $stmt->execute();
    
    $moodId = $stmt->fetchColumn();
    
    if (!$moodId) {
        $response['message'] = 'Invalid mood';
        echo json_encode($response);
        exit;
    }
    
    // Get recommendations for the mood
    $stmt = $conn->prepare("SELECT r.*, rc.category_name, m.mood_name, m.emoji
                           FROM recommendations r
                           JOIN recommendation_categories rc ON r.category_id = rc.category_id
                           JOIN moods m ON r.mood_id = m.mood_id
                           WHERE r.mood_id = :mood_id
                           ORDER BY rc.category_name, r.title");
    $stmt->bindParam(':mood_id', $moodId);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group recommendations by category
    $groupedRecommendations = [];
    foreach ($recommendations as $rec) {
        $category = $rec['category_name'];
        if (!isset($groupedRecommendations[$category])) {
            $groupedRecommendations[$category] = [];
        }
        $groupedRecommendations[$category][] = $rec;
    }
    
    $response['status'] = 'success';
    $response['data'] = [
        'mood' => $moodName,
        'emoji' => $recommendations[0]['emoji'] ?? '',
        'recommendations' => $groupedRecommendations
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Get recommendations based on today's journal entry
 */
function getTodayRecommendations($conn, $userId) {
    global $response;
    
    // Check if user has a journal entry for today
    $today = date('Y-m-d');
    $stmt = $conn->prepare("SELECT je.*, m.mood_name, m.emoji
                           FROM journal_entries je
                           JOIN moods m ON je.mood_id = m.mood_id
                           WHERE je.user_id = :user_id
                           AND DATE(je.created_at) = :today
                           ORDER BY je.created_at DESC
                           LIMIT 1");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':today', $today);
    $stmt->execute();
    
    $todayEntry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$todayEntry) {
        $response['status'] = 'success';
        $response['message'] = 'No journal entry found for today';
        $response['data'] = [
            'has_entry' => false,
            'recommendations' => null
        ];
        echo json_encode($response);
        exit;
    }
    
    // Get recommendations based on the mood of today's entry
    $moodId = $todayEntry['mood_id'];
    
    $stmt = $conn->prepare("SELECT r.*, rc.category_name, m.mood_name, m.emoji
                           FROM recommendations r
                           JOIN recommendation_categories rc ON r.category_id = rc.category_id
                           JOIN moods m ON r.mood_id = m.mood_id
                           WHERE r.mood_id = :mood_id
                           ORDER BY rc.category_name, r.title");
    $stmt->bindParam(':mood_id', $moodId);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group recommendations by category
    $groupedRecommendations = [];
    foreach ($recommendations as $rec) {
        $category = $rec['category_name'];
        if (!isset($groupedRecommendations[$category])) {
            $groupedRecommendations[$category] = [];
        }
        $groupedRecommendations[$category][] = $rec;
    }
    
    $response['status'] = 'success';
    $response['data'] = [
        'has_entry' => true,
        'mood' => $todayEntry['mood_name'],
        'emoji' => $todayEntry['emoji'],
        'entry' => $todayEntry,
        'recommendations' => $groupedRecommendations
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Get personalized recommendations based on user's history
 */
function getPersonalizedRecommendations($conn, $userId) {
    global $response;
    
    // Get user's most frequent mood in the last 30 days
    $stmt = $conn->prepare("SELECT m.mood_id, m.mood_name, m.emoji, COUNT(*) as frequency
                           FROM journal_entries je
                           JOIN moods m ON je.mood_id = m.mood_id
                           WHERE je.user_id = :user_id
                           AND je.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
                           GROUP BY m.mood_id
                           ORDER BY frequency DESC
                           LIMIT 1");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $frequentMood = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$frequentMood) {
        // If no entries in the last 30 days, get default recommendations
        getDefaultRecommendations($conn, $userId);
        return;
    }
    
    $moodId = $frequentMood['mood_id'];
    
    // Get recommendations for the most frequent mood
    $stmt = $conn->prepare("SELECT r.*, rc.category_name, m.mood_name, m.emoji
                           FROM recommendations r
                           JOIN recommendation_categories rc ON r.category_id = rc.category_id
                           JOIN moods m ON r.mood_id = m.mood_id
                           WHERE r.mood_id = :mood_id
                           ORDER BY rc.category_name, r.title");
    $stmt->bindParam(':mood_id', $moodId);
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group recommendations by category
    $groupedRecommendations = [];
    foreach ($recommendations as $rec) {
        $category = $rec['category_name'];
        if (!isset($groupedRecommendations[$category])) {
            $groupedRecommendations[$category] = [];
        }
        $groupedRecommendations[$category][] = $rec;
    }
    
    $response['status'] = 'success';
    $response['data'] = [
        'type' => 'personalized',
        'mood' => $frequentMood['mood_name'],
        'emoji' => $frequentMood['emoji'],
        'frequency' => $frequentMood['frequency'],
        'recommendations' => $groupedRecommendations
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Get default recommendations
 */
function getDefaultRecommendations($conn, $userId) {
    global $response;
    
    // Get a random selection of recommendations from different moods
    $stmt = $conn->prepare("SELECT r.*, rc.category_name, m.mood_name, m.emoji
                           FROM recommendations r
                           JOIN recommendation_categories rc ON r.category_id = rc.category_id
                           JOIN moods m ON r.mood_id = m.mood_id
                           GROUP BY r.mood_id, rc.category_id
                           ORDER BY RAND()
                           LIMIT 10");
    $stmt->execute();
    
    $recommendations = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Group recommendations by mood and category
    $groupedRecommendations = [];
    foreach ($recommendations as $rec) {
        $mood = $rec['mood_name'];
        $category = $rec['category_name'];
        
        if (!isset($groupedRecommendations[$mood])) {
            $groupedRecommendations[$mood] = [
                'emoji' => $rec['emoji'],
                'categories' => []
            ];
        }
        
        if (!isset($groupedRecommendations[$mood]['categories'][$category])) {
            $groupedRecommendations[$mood]['categories'][$category] = [];
        }
        
        $groupedRecommendations[$mood]['categories'][$category][] = $rec;
    }
    
    $response['status'] = 'success';
    $response['data'] = [
        'type' => 'default',
        'message' => 'Create a journal entry to get personalized recommendations',
        'recommendations' => $groupedRecommendations
    ];
    
    echo json_encode($response);
    exit;
}
?> 