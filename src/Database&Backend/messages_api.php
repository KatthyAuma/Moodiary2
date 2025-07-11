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
    
    // Handle mark as read request
    if (isset($_GET['mark_read'])) {
        markMessagesAsRead($conn, $userId, $_GET['mark_read']);
        exit;
    }
    
    // Handle different HTTP methods
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($method) {
        case 'GET':
            // Get messages with a specific friend
            if (isset($_GET['friend_id'])) {
                getMessages($conn, $userId, $_GET['friend_id']);
            }
            // Get message threads (conversations)
            else {
                getMessageThreads($conn, $userId);
            }
            break;
            
        case 'POST':
            // Send a message
            sendMessage($conn, $userId);
            break;
            
        case 'DELETE':
            // Delete a message
            deleteMessage($conn, $userId);
            break;
            
        default:
            $response['message'] = 'Unsupported request method';
            echo json_encode($response);
            exit;
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Messages API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get messages between the current user and a specific friend
 */
function getMessages($conn, $userId, $friendId) {
    global $response;
    
    // Validate friend ID
    $friendId = filter_var($friendId, FILTER_VALIDATE_INT);
    if (!$friendId) {
        $response['message'] = 'Invalid friend ID';
        echo json_encode($response);
        exit;
    }
    
    // Check if they are friends
    $stmt = $conn->prepare("
        SELECT * FROM friendships
        WHERE ((user_id = :user_id AND friend_id = :friend_id) OR
               (user_id = :friend_id AND friend_id = :user_id))
        AND status = 'accepted'
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'You are not friends with this user';
        echo json_encode($response);
        exit;
    }
    
    // Get messages, join journal_entries for reply_to_journal_id
    $stmt = $conn->prepare("
        SELECT m.*, 
               (SELECT JSON_OBJECT(
                   'message_id', r.message_id,
                   'content', r.content
               ) 
                FROM messages r 
                WHERE r.message_id = m.reply_to_id
               ) as reply_to,
               (SELECT JSON_OBJECT(
                   'entry_id', je.entry_id,
                   'content', je.content,
                   'mood_name', mo.mood_name,
                   'emoji', mo.emoji
               )
                FROM journal_entries je
                JOIN moods mo ON je.mood_id = mo.mood_id
                WHERE je.entry_id = m.reply_to_journal_id
               ) as reply_to_journal
        FROM messages m
        WHERE (m.sender_id = :user_id AND m.receiver_id = :friend_id) OR
              (m.sender_id = :friend_id AND m.receiver_id = :user_id)
        ORDER BY m.created_at ASC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':friend_id', $friendId);
    $stmt->execute();
    
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = $messages;
    
    echo json_encode($response);
    exit;
}

/**
 * Get message threads (conversations) for the current user
 */
function getMessageThreads($conn, $userId) {
    global $response;
    
    // Get latest message from each conversation
    $stmt = $conn->prepare("
        WITH LatestMessages AS (
            SELECT 
                CASE 
                    WHEN sender_id = :user_id THEN receiver_id
                    ELSE sender_id
                END as other_user_id,
                MAX(created_at) as latest_time
            FROM messages
            WHERE sender_id = :user_id OR receiver_id = :user_id
            GROUP BY other_user_id
        )
        SELECT 
            m.*,
            u.username, u.full_name, u.profile_image,
            (SELECT COUNT(*) FROM messages 
             WHERE sender_id = lm.other_user_id 
             AND receiver_id = :user_id 
             AND is_read = 0) as unread_count,
            (
                SELECT mo.mood_name
                FROM journal_entries je
                JOIN moods mo ON je.mood_id = mo.mood_id
                WHERE je.user_id = u.user_id
                ORDER BY je.created_at DESC
                LIMIT 1
            ) as mood_name,
            (
                SELECT mo.emoji
                FROM journal_entries je
                JOIN moods mo ON je.mood_id = mo.mood_id
                WHERE je.user_id = u.user_id
                ORDER BY je.created_at DESC
                LIMIT 1
            ) as emoji
        FROM LatestMessages lm
        JOIN messages m ON (
            ((m.sender_id = :user_id AND m.receiver_id = lm.other_user_id) OR
             (m.sender_id = lm.other_user_id AND m.receiver_id = :user_id))
            AND m.created_at = lm.latest_time
        )
        JOIN users u ON u.user_id = lm.other_user_id
        ORDER BY m.created_at DESC
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $threads = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = $threads;
    
    echo json_encode($response);
    exit;
}

/**
 * Send a message
 */
function sendMessage($conn, $userId) {
    global $response;
    
    // Get and validate input data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        $response['message'] = 'Invalid request data';
        echo json_encode($response);
        exit;
    }
    
    // Validate required fields
    if (empty($data['receiver_id']) || empty($data['content'])) {
        $response['message'] = 'Receiver ID and content are required';
        echo json_encode($response);
        exit;
    }
    
    // Sanitize inputs
    $receiverId = filter_var($data['receiver_id'], FILTER_VALIDATE_INT);
    $content = htmlspecialchars(trim($data['content']));
    $replyToId = isset($data['reply_to']) && isset($data['reply_to']['message_id']) ? 
        filter_var($data['reply_to']['message_id'], FILTER_VALIDATE_INT) : null;
    $replyToJournalId = isset($data['reply_to_journal_id']) ? filter_var($data['reply_to_journal_id'], FILTER_VALIDATE_INT) : null;
    
    // Check if receiver exists and is a friend
    $stmt = $conn->prepare("
        SELECT * FROM friendships
        WHERE ((user_id = :user_id AND friend_id = :receiver_id) OR
               (user_id = :receiver_id AND friend_id = :user_id))
        AND status = 'accepted'
    ");
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':receiver_id', $receiverId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'You can only send messages to your friends';
        echo json_encode($response);
        exit;
    }
    
    // Insert message
    $stmt = $conn->prepare("
        INSERT INTO messages (sender_id, receiver_id, content, reply_to_id, reply_to_journal_id)
        VALUES (:sender_id, :receiver_id, :content, :reply_to_id, :reply_to_journal_id)
    ");
    $stmt->bindParam(':sender_id', $userId);
    $stmt->bindParam(':receiver_id', $receiverId);
    $stmt->bindParam(':content', $content);
    $stmt->bindParam(':reply_to_id', $replyToId);
    $stmt->bindParam(':reply_to_journal_id', $replyToJournalId);
    $stmt->execute();
    
    $messageId = $conn->lastInsertId();
    
    // Get the created message
    $stmt = $conn->prepare("
        SELECT * FROM messages WHERE message_id = :message_id
    ");
    $stmt->bindParam(':message_id', $messageId);
    $stmt->execute();
    
    $message = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Message sent successfully';
    $response['data'] = $message;
    
    echo json_encode($response);
    exit;
}

/**
 * Delete a message
 */
function deleteMessage($conn, $userId) {
    global $response;
    
    // Get message ID from query string
    $messageId = isset($_GET['message_id']) ? filter_var($_GET['message_id'], FILTER_VALIDATE_INT) : null;
    
    if (!$messageId) {
        $response['message'] = 'Message ID is required';
        echo json_encode($response);
        exit;
    }
    
    // Check if the message belongs to the user
    $stmt = $conn->prepare("
        SELECT * FROM messages WHERE message_id = :message_id AND sender_id = :user_id
    ");
    $stmt->bindParam(':message_id', $messageId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'You can only delete your own messages';
        echo json_encode($response);
        exit;
    }
    
    // Delete the message
    $stmt = $conn->prepare("
        DELETE FROM messages WHERE message_id = :message_id
    ");
    $stmt->bindParam(':message_id', $messageId);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Message deleted successfully';
    
    echo json_encode($response);
    exit;
}

/**
 * Mark messages as read
 */
function markMessagesAsRead($conn, $userId, $senderId) {
    global $response;
    
    // Validate sender ID
    $senderId = filter_var($senderId, FILTER_VALIDATE_INT);
    if (!$senderId) {
        $response['message'] = 'Invalid sender ID';
        echo json_encode($response);
        exit;
    }
    
    // Mark messages as read
    $stmt = $conn->prepare("
        UPDATE messages
        SET is_read = 1
        WHERE sender_id = :sender_id AND receiver_id = :user_id AND is_read = 0
    ");
    $stmt->bindParam(':sender_id', $senderId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Messages marked as read';
    
    echo json_encode($response);
    exit;
}
?> 