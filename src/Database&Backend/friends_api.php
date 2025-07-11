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
            // Get friends or search for users
            handleGetRequest($conn, $userId);
            break;
            
        case 'POST':
            // Send friend request or accept/reject request
            handlePostRequest($conn, $userId);
            break;
            
        case 'DELETE':
            // Remove friend or cancel request
            handleDeleteRequest($conn, $userId);
            break;
            
        default:
            $response['message'] = 'Unsupported request method';
            echo json_encode($response);
            exit;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Friends API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Handle GET requests - Get friends list or search for users
 */
function handleGetRequest($conn, $userId) {
    global $response;
    
    // Check if this is a search request
    if (isset($_GET['search'])) {
        searchUsers($conn, $userId, $_GET['search']);
        return;
    }
    
    // Check if this is a friend requests list
    if (isset($_GET['requests'])) {
        getFriendRequests($conn, $userId);
        return;
    }
    
    // Default: Get friends list
    getFriendsList($conn, $userId);
}

/**
 * Get friends list
 */
function getFriendsList($conn, $userId) {
    global $response;
    
    // Get friends (both ways - user is requester or receiver)
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, u.username, u.full_name, u.profile_image, u.last_login,
            f.relationship_type,
            (
                SELECT m.mood_name
                FROM journal_entries je
                JOIN moods m ON je.mood_id = m.mood_id
                WHERE je.user_id = u.user_id
                ORDER BY je.created_at DESC
                LIMIT 1
            ) as mood_name,
            (
                SELECT m.emoji
                FROM journal_entries je
                JOIN moods m ON je.mood_id = m.mood_id
                WHERE je.user_id = u.user_id
                ORDER BY je.created_at DESC
                LIMIT 1
            ) as emoji
        FROM friendships f
        JOIN users u ON (
            (f.user_id = :user_id AND f.friend_id = u.user_id) OR
            (f.friend_id = :user_id AND f.user_id = u.user_id)
        )
        WHERE f.status = 'accepted'
        ORDER BY u.last_login DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $friends = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = $friends;
    
    echo json_encode($response);
    exit;
}

/**
 * Get friend requests
 */
function getFriendRequests($conn, $userId) {
    global $response;
    
    // Get received requests
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, u.username, u.full_name, u.profile_image,
            f.relationship_type, f.created_at
        FROM friendships f
        JOIN users u ON f.user_id = u.user_id
        WHERE f.friend_id = :user_id AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $receivedRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get sent requests
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, u.username, u.full_name, u.profile_image,
            f.relationship_type, f.created_at
        FROM friendships f
        JOIN users u ON f.friend_id = u.user_id
        WHERE f.user_id = :user_id AND f.status = 'pending'
        ORDER BY f.created_at DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $sentRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = [
        'received' => $receivedRequests,
        'sent' => $sentRequests
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Search for users
 */
function searchUsers($conn, $userId, $searchTerm) {
    global $response;
    
    // Sanitize search term
    $searchTerm = '%' . htmlspecialchars(trim($searchTerm)) . '%';
    
    // Search for users by username or full name
    $stmt = $conn->prepare("
        SELECT 
            u.user_id, u.username, u.full_name, u.profile_image,
            CASE
                WHEN EXISTS (
                    SELECT 1 FROM friendships 
                    WHERE (user_id = :user_id AND friend_id = u.user_id) OR
                          (user_id = u.user_id AND friend_id = :user_id)
                    AND status = 'accepted'
                ) THEN 'friend'
                WHEN EXISTS (
                    SELECT 1 FROM friendships 
                    WHERE user_id = :user_id AND friend_id = u.user_id
                    AND status = 'pending'
                ) THEN 'sent'
                WHEN EXISTS (
                    SELECT 1 FROM friendships 
                    WHERE user_id = u.user_id AND friend_id = :user_id
                    AND status = 'pending'
                ) THEN 'received'
                ELSE 'none'
            END as friendship_status
        FROM users u
        WHERE (u.username LIKE :search_term OR u.full_name LIKE :search_term)
        AND u.user_id != :user_id
        ORDER BY 
            CASE 
                WHEN u.username = :exact_term OR u.full_name = :exact_term THEN 0
                ELSE 1
            END,
            u.last_login DESC
        LIMIT 20
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':search_term', $searchTerm);
    $stmt->bindParam(':exact_term', trim(str_replace('%', '', $searchTerm)));
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = $users;
    
    echo json_encode($response);
    exit;
}

/**
 * Handle POST requests - Send friend request or accept/reject request
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
    if (empty($data['action']) || empty($data['friend_id'])) {
        $response['message'] = 'Action and friend ID are required.';
        echo json_encode($response);
        exit;
    }
    
    // Sanitize inputs
    $action = htmlspecialchars(trim($data['action']));
    $friendId = filter_var($data['friend_id'], FILTER_VALIDATE_INT);
    $relationshipType = isset($data['relationship_type']) ? 
        htmlspecialchars(trim($data['relationship_type'])) : 'friend';
    
    // Validate friend ID
    if (!$friendId || $friendId == $userId) {
        $response['message'] = 'Invalid friend ID.';
        echo json_encode($response);
        exit;
    }
    
    // Check if friend exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :friend_id");
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    if (!$stmt->fetch()) {
        $response['message'] = 'User not found.';
        echo json_encode($response);
        exit;
    }
    
    // Process based on action
    switch ($action) {
        case 'request':
            // Send friend request
            sendFriendRequest($conn, $userId, $friendId, $relationshipType);
            break;
            
        case 'accept':
            // Accept friend request
            acceptFriendRequest($conn, $userId, $friendId, $relationshipType);
            break;
            
        case 'reject':
            // Reject friend request
            rejectFriendRequest($conn, $userId, $friendId);
            break;
            
        default:
            $response['message'] = 'Invalid action.';
            echo json_encode($response);
            exit;
    }
}

/**
 * Send friend request
 */
function sendFriendRequest($conn, $userId, $friendId, $relationshipType) {
    global $response;
    
    // Check if request already exists
    $stmt = $conn->prepare("
        SELECT * FROM friendships 
        WHERE (user_id = :user_id AND friend_id = :friend_id) OR
              (user_id = :friend_id AND friend_id = :user_id)
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    $existingFriendship = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($existingFriendship) {
        if ($existingFriendship['status'] === 'accepted') {
            $response['message'] = 'You are already friends with this user.';
        } else if ($existingFriendship['user_id'] == $userId) {
            $response['message'] = 'You have already sent a friend request to this user.';
        } else {
            $response['message'] = 'This user has already sent you a friend request.';
        }
        
        echo json_encode($response);
        exit;
    }
    
    // Create new friend request
    $stmt = $conn->prepare("
        INSERT INTO friendships (user_id, friend_id, relationship_type, status) 
        VALUES (:user_id, :friend_id, :relationship_type, 'pending')
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->bindParam(':relationship_type', $relationshipType);
    $stmt->execute();
    
    // Log the activity
    $activityType = 'friend_request_sent';
    $description = 'Sent friend request';
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, activity_type, description) 
        VALUES (:user_id, :activity_type, :description)
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':activity_type', $activityType);
    $stmt->bindParam(':description', $description);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Friend request sent successfully.';
    
    echo json_encode($response);
    exit;
}

/**
 * Accept friend request
 */
function acceptFriendRequest($conn, $userId, $friendId, $relationshipType) {
    global $response;
    
    // Check if request exists
    $stmt = $conn->prepare("
        SELECT * FROM friendships 
        WHERE user_id = :friend_id AND friend_id = :user_id AND status = 'pending'
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    $friendRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$friendRequest) {
        $response['message'] = 'No pending friend request found.';
        echo json_encode($response);
        exit;
    }
    
    // Update relationship type if provided
    if ($relationshipType && $relationshipType !== $friendRequest['relationship_type']) {
        $stmt = $conn->prepare("
            UPDATE friendships 
            SET relationship_type = :relationship_type 
            WHERE user_id = :friend_id AND friend_id = :user_id
        ");
        $stmt->bindParam(':relationship_type', $relationshipType);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':friend_id', $friendId);
        $stmt->execute();
    }
    
    // Accept friend request
    $stmt = $conn->prepare("
        UPDATE friendships 
        SET status = 'accepted', updated_at = NOW() 
        WHERE user_id = :friend_id AND friend_id = :user_id
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    // Log the activity
    $activityType = 'friend_request_accepted';
    $description = 'Accepted friend request';
    $stmt = $conn->prepare("
        INSERT INTO activity_logs (user_id, activity_type, description) 
        VALUES (:user_id, :activity_type, :description)
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':activity_type', $activityType);
    $stmt->bindParam(':description', $description);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Friend request accepted.';
    
    echo json_encode($response);
    exit;
}

/**
 * Reject friend request
 */
function rejectFriendRequest($conn, $userId, $friendId) {
    global $response;
    
    // Check if request exists
    $stmt = $conn->prepare("
        SELECT * FROM friendships 
        WHERE user_id = :friend_id AND friend_id = :user_id AND status = 'pending'
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    $friendRequest = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$friendRequest) {
        $response['message'] = 'No pending friend request found.';
        echo json_encode($response);
        exit;
    }
    
    // Delete friend request
    $stmt = $conn->prepare("
        DELETE FROM friendships 
        WHERE user_id = :friend_id AND friend_id = :user_id AND status = 'pending'
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Friend request rejected.';
    
    echo json_encode($response);
    exit;
}

/**
 * Handle DELETE requests - Remove friend or cancel request
 */
function handleDeleteRequest($conn, $userId) {
    global $response;
    
    // Get friend ID from query string
    $friendId = isset($_GET['friend_id']) ? filter_var($_GET['friend_id'], FILTER_VALIDATE_INT) : null;
    
    if (!$friendId) {
        $response['message'] = 'Friend ID is required.';
        echo json_encode($response);
        exit;
    }
    
    // Delete friendship (both ways)
    $stmt = $conn->prepare("
        DELETE FROM friendships 
        WHERE (user_id = :user_id AND friend_id = :friend_id) OR
              (user_id = :friend_id AND friend_id = :user_id)
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Friend removed successfully.';
    
    echo json_encode($response);
    exit;
}
?> 