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

    // Check for latest mood request
    if (isset($_GET['latest_mood'])) {
        getLatestMood($userId);
        exit;
    }

    // Check for streak request
    if (isset($_GET['streak'])) {
        getUserStreak($userId);
        exit;
    }

    // Check for feed request
    if (isset($_GET['feed'])) {
        getFeed($userId);
        exit;
    }

    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    // Create database connection
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    // Handle different HTTP methods
    switch ($method) {
        case 'GET':
            // Get journal entries
            handleGetRequest($conn, $userId);
            break;
            
        case 'POST':
            // Create new journal entry
            handlePostRequest($conn, $userId);
            break;
            
        case 'PUT':
            // Update existing journal entry
            handlePutRequest($conn, $userId);
            break;
            
        case 'DELETE':
            // Delete journal entry
            handleDeleteRequest($conn, $userId);
            break;
            
        default:
            $response['message'] = 'Unsupported request method';
            echo json_encode($response);
            exit;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Journal API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Handle GET requests - Retrieve journal entries
 */
function handleGetRequest($conn, $userId) {
    global $response;
    
    // Check if specific entry ID is provided
    $entryId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    
    if ($entryId) {
        // Get specific entry
        $stmt = $conn->prepare("SELECT je.*, m.mood_name, m.emoji 
                               FROM journal_entries je
                               JOIN moods m ON je.mood_id = m.mood_id
                               WHERE je.entry_id = :entry_id AND je.user_id = :user_id");
        $stmt->bindParam(':entry_id', $entryId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $entry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$entry) {
            $response['message'] = 'Entry not found';
            echo json_encode($response);
            exit;
        }
        
        $response['status'] = 'success';
        $response['data'] = $entry;
        
    } else {
        // Get all entries for the user
        $limit = isset($_GET['limit']) ? filter_var($_GET['limit'], FILTER_VALIDATE_INT) : 10;
        $offset = isset($_GET['offset']) ? filter_var($_GET['offset'], FILTER_VALIDATE_INT) : 0;
        
        $stmt = $conn->prepare("SELECT je.*, m.mood_name, m.emoji 
                               FROM journal_entries je
                               JOIN moods m ON je.mood_id = m.mood_id
                               WHERE je.user_id = :user_id
                               ORDER BY je.created_at DESC
                               LIMIT :limit OFFSET :offset");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get total count for pagination
        $stmt = $conn->prepare("SELECT COUNT(*) FROM journal_entries WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $totalCount = $stmt->fetchColumn();
        
        $response['status'] = 'success';
        $response['data'] = [
            'entries' => $entries,
            'total' => $totalCount,
            'limit' => $limit,
            'offset' => $offset
        ];
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Handle POST requests - Create new journal entry
 */
function handlePostRequest($conn, $userId) {
    global $response;
    
    // Get and validate input data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        // If not JSON, try regular POST
        $data = $_POST;
    }
    
    // Validate required fields
    if (empty($data['mood_id']) && empty($data['mood'])) {
        $response['message'] = 'Mood ID or mood name is required.';
        echo json_encode($response);
        exit;
    }
    
    if (empty($data['content'])) {
        $response['message'] = 'Content is required.';
        echo json_encode($response);
        exit;
    }
    
    // Sanitize and validate inputs
    $content = htmlspecialchars(trim($data['content']));
    $isPublic = isset($data['is_public']) ? (bool)$data['is_public'] : false;
    
    try {
        // Get mood ID from input
        $moodId = null;
        
        if (!empty($data['mood_id'])) {
            // Use mood_id directly if provided
            $moodId = filter_var($data['mood_id'], FILTER_VALIDATE_INT);
            
            // Verify mood_id exists
            $stmt = $conn->prepare("SELECT COUNT(*) FROM moods WHERE mood_id = :mood_id");
            $stmt->bindParam(':mood_id', $moodId);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                $response['message'] = 'Invalid mood ID.';
                echo json_encode($response);
                exit;
            }
        } else {
            // Get mood ID from mood name
            $moodName = htmlspecialchars(trim($data['mood']));
            $stmt = $conn->prepare("SELECT mood_id FROM moods WHERE mood_name = :mood_name");
            $stmt->bindParam(':mood_name', $moodName);
            $stmt->execute();
            
            $moodId = $stmt->fetchColumn();
            
            if (!$moodId) {
                $response['message'] = 'Invalid mood name.';
                echo json_encode($response);
                exit;
            }
        }
        
        // Insert new journal entry
        $stmt = $conn->prepare("INSERT INTO journal_entries (user_id, mood_id, content, is_public) 
                               VALUES (:user_id, :mood_id, :content, :is_public)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':mood_id', $moodId);
        $stmt->bindParam(':content', $content);
        $stmt->bindParam(':is_public', $isPublic, PDO::PARAM_BOOL);
        $stmt->execute();
        
        $newEntryId = $conn->lastInsertId();
        
        // Log the activity
        $activityType = 'journal_entry';
        $description = 'Created new journal entry';
        $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description) 
                               VALUES (:user_id, :activity_type, :description)");
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':activity_type', $activityType);
        $stmt->bindParam(':description', $description);
        $stmt->execute();
        
        // Get the newly created entry
        $stmt = $conn->prepare("SELECT je.*, m.mood_name, m.emoji 
                               FROM journal_entries je
                               JOIN moods m ON je.mood_id = m.mood_id
                               WHERE je.entry_id = :entry_id");
        $stmt->bindParam(':entry_id', $newEntryId);
        $stmt->execute();
        
        $newEntry = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['message'] = 'Journal entry created successfully.';
        $response['data'] = $newEntry;
        
    } catch (Exception $e) {
        $response['message'] = 'Error creating journal entry: ' . $e->getMessage();
        error_log('Journal entry creation error: ' . $e->getMessage());
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Handle PUT requests - Update existing journal entry
 */
function handlePutRequest($conn, $userId) {
    global $response;
    
    // Get and validate input data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        $response['message'] = 'Invalid request data.';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (empty($data['entry_id'])) {
        $response['message'] = 'Entry ID is required.';
        echo json_encode($response);
        exit;
    }
    
    $entryId = filter_var($data['entry_id'], FILTER_VALIDATE_INT);
    
    // Check if entry exists and belongs to the user
    $stmt = $conn->prepare("SELECT * FROM journal_entries WHERE entry_id = :entry_id AND user_id = :user_id");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Entry not found or you do not have permission to edit it.';
        echo json_encode($response);
        exit;
    }
    
    // Prepare update data
    $updateFields = [];
    $params = [':entry_id' => $entryId, ':user_id' => $userId];
    
    if (isset($data['mood'])) {
        // Get mood ID from mood name
        $moodName = htmlspecialchars(trim($data['mood']));
        $stmt = $conn->prepare("SELECT mood_id FROM moods WHERE mood_name = :mood_name");
        $stmt->bindParam(':mood_name', $moodName);
        $stmt->execute();
        
        $moodId = $stmt->fetchColumn();
        
        if ($moodId) {
            $updateFields[] = "mood_id = :mood_id";
            $params[':mood_id'] = $moodId;
        }
    }
    
    if (isset($data['content'])) {
        $content = htmlspecialchars(trim($data['content']));
        $updateFields[] = "content = :content";
        $params[':content'] = $content;
    }
    
    if (isset($data['is_public'])) {
        $isPublic = (bool)$data['is_public'];
        $updateFields[] = "is_public = :is_public";
        $params[':is_public'] = $isPublic;
    }
    
    if (empty($updateFields)) {
        $response['message'] = 'No fields to update.';
        echo json_encode($response);
        exit;
    }
    
    // Update the entry
    $sql = "UPDATE journal_entries SET " . implode(", ", $updateFields) . ", updated_at = NOW() 
           WHERE entry_id = :entry_id AND user_id = :user_id";
    
    $stmt = $conn->prepare($sql);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    
    // Log the activity
    $activityType = 'journal_update';
    $description = 'Updated journal entry';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description) 
                           VALUES (:user_id, :activity_type, :description)");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':activity_type', $activityType);
    $stmt->bindParam(':description', $description);
    $stmt->execute();
    
    // Get the updated entry
    $stmt = $conn->prepare("SELECT je.*, m.mood_name, m.emoji 
                           FROM journal_entries je
                           JOIN moods m ON je.mood_id = m.mood_id
                           WHERE je.entry_id = :entry_id");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->execute();
    
    $updatedEntry = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Journal entry updated successfully.';
    $response['data'] = $updatedEntry;
    
    echo json_encode($response);
    exit;
}

/**
 * Handle DELETE requests - Delete journal entry
 */
function handleDeleteRequest($conn, $userId) {
    global $response;
    
    // Get entry ID from query parameters
    $entryId = isset($_GET['id']) ? filter_var($_GET['id'], FILTER_VALIDATE_INT) : null;
    
    if (!$entryId) {
        $response['message'] = 'Entry ID is required.';
        echo json_encode($response);
        exit;
    }
    
    // Check if entry exists and belongs to the user
    $stmt = $conn->prepare("SELECT * FROM journal_entries WHERE entry_id = :entry_id AND user_id = :user_id");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Entry not found or you do not have permission to delete it.';
        echo json_encode($response);
        exit;
    }
    
    // Delete the entry
    $stmt = $conn->prepare("DELETE FROM journal_entries WHERE entry_id = :entry_id AND user_id = :user_id");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    // Log the activity
    $activityType = 'journal_delete';
    $description = 'Deleted journal entry';
    $stmt = $conn->prepare("INSERT INTO activity_logs (user_id, activity_type, description) 
                           VALUES (:user_id, :activity_type, :description)");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':activity_type', $activityType);
    $stmt->bindParam(':description', $description);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Journal entry deleted successfully.';
    
    echo json_encode($response);
    exit;
}

/**
 * Get latest mood for user
 */
function getLatestMood($userId) {
    global $response;
    
    try {
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        $stmt = $conn->prepare("
            SELECT m.mood_id, m.mood_name, m.emoji
            FROM journal_entries je
            JOIN moods m ON je.mood_id = m.mood_id
            WHERE je.user_id = :user_id
            ORDER BY je.created_at DESC
            LIMIT 1
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $mood = $stmt->fetch(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['data'] = $mood ?: [
            'mood_id' => 1,
            'mood_name' => 'Happy',
            'emoji' => '😊'
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching latest mood: ' . $e->getMessage();
        error_log('Latest mood error: ' . $e->getMessage());
        echo json_encode($response);
    }
}

/**
 * Get user streak (consecutive days with journal entries)
 */
function getUserStreak($userId) {
    global $response;
    
    try {
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Get current streak (consecutive days with entries)
        $streak = calculateStreak($conn, $userId);
        
        $response['status'] = 'success';
        $response['data'] = [
            'streak' => $streak
        ];
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching streak: ' . $e->getMessage();
        error_log('Streak error: ' . $e->getMessage());
        echo json_encode($response);
    }
}

/**
 * Calculate user streak
 */
function calculateStreak($conn, $userId) {
    // Get dates of journal entries for the user
    $stmt = $conn->prepare("
        SELECT DATE(created_at) as entry_date
        FROM journal_entries
        WHERE user_id = :user_id
        ORDER BY created_at DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($dates)) {
        return 0;
    }
    
    // Check if there's an entry for today
    $today = date('Y-m-d');
    $hasEntryToday = ($dates[0] === $today);
    
    // If no entry today, check if there was one yesterday to continue streak
    if (!$hasEntryToday) {
        $yesterday = date('Y-m-d', strtotime('-1 day'));
        if (!in_array($yesterday, $dates)) {
            // No entry yesterday or today, streak is broken
            return 0;
        }
    }
    
    // Count consecutive days
    $streak = 1; // Start with today or yesterday
    $currentDate = new DateTime($dates[0]);
    
    for ($i = 1; $i < count($dates); $i++) {
        $prevDate = new DateTime($dates[$i]);
        $diff = $currentDate->diff($prevDate)->days;
        
        if ($diff == 1) {
            // Consecutive day
            $streak++;
            $currentDate = $prevDate;
        } else if ($diff > 1) {
            // Break in streak
            break;
        }
    }
    
    return $streak;
}

/**
 * Get feed of journal entries (user's and friends')
 */
function getFeed($userId) {
    global $response;
    
    try {
        $conn = getDbConnection();
        
        if (!$conn) {
            throw new Exception("Database connection failed");
        }
        
        // Get public entries from user and friends
        $stmt = $conn->prepare("
            SELECT je.entry_id, je.content, je.created_at,
                   u.user_id, u.username, u.full_name, u.profile_image,
                   m.mood_name, m.emoji,
                   (SELECT COUNT(*) FROM reactions r WHERE r.entry_id = je.entry_id AND r.reaction_type = 'like') as likes,
                   (SELECT COUNT(*) FROM reactions r WHERE r.entry_id = je.entry_id AND r.reaction_type = 'support') as support,
                   (SELECT COUNT(*) FROM reactions r WHERE r.entry_id = je.entry_id AND r.reaction_type = 'hug') as hugs
            FROM journal_entries je
            JOIN users u ON je.user_id = u.user_id
            JOIN moods m ON je.mood_id = m.mood_id
            WHERE je.is_public = 1 AND (
                je.user_id = :user_id OR
                je.user_id IN (
                    SELECT f.friend_id FROM friendships f 
                    WHERE f.user_id = :user_id AND f.status = 'accepted'
                    UNION
                    SELECT f.user_id FROM friendships f 
                    WHERE f.friend_id = :user_id AND f.status = 'accepted'
                )
            )
            ORDER BY je.created_at DESC
            LIMIT 20
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['data'] = $entries;
        
        echo json_encode($response);
        
    } catch (Exception $e) {
        $response['message'] = 'Error fetching feed: ' . $e->getMessage();
        error_log('Feed error: ' . $e->getMessage());
        echo json_encode($response);
    }
}
?> 