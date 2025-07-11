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

    // Get request method
    $method = $_SERVER['REQUEST_METHOD'];

    // Create database connection
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }
    
    switch ($method) {
        case 'GET':
            // Get comments for a specific entry
            if (isset($_GET['entry_id'])) {
                getComments($conn, $_GET['entry_id']);
            } else {
                $response['message'] = 'Entry ID is required';
                echo json_encode($response);
            }
            break;
            
        case 'POST':
            // Handle POST request for adding comments
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $response['message'] = 'Invalid request data';
                echo json_encode($response);
                exit;
            }
            
            if (empty($data['entry_id']) || empty($data['content'])) {
                $response['message'] = 'Entry ID and content are required';
                echo json_encode($response);
                exit;
            }
            
            addComment($conn, $userId, $data['entry_id'], $data['content']);
            break;
            
        case 'DELETE':
            // Handle DELETE request for removing comments
            if (isset($_GET['comment_id'])) {
                deleteComment($conn, $userId, $_GET['comment_id']);
            } else {
                $response['message'] = 'Comment ID is required';
                echo json_encode($response);
            }
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            echo json_encode($response);
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Comments API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get comments for a specific entry
 */
function getComments($conn, $entryId) {
    global $response;
    
    // Validate entry ID
    $entryId = filter_var($entryId, FILTER_VALIDATE_INT);
    if (!$entryId) {
        $response['message'] = 'Invalid entry ID';
        echo json_encode($response);
        exit;
    }
    
    // Get comments with user information
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.username,
            u.full_name,
            u.profile_image
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.entry_id = :entry_id
        ORDER BY c.created_at ASC
    ");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->execute();
    
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = $comments;
    
    echo json_encode($response);
    exit;
}

/**
 * Add a comment to an entry
 */
function addComment($conn, $userId, $entryId, $content) {
    global $response;
    
    // Validate entry ID
    $entryId = filter_var($entryId, FILTER_VALIDATE_INT);
    if (!$entryId) {
        $response['message'] = 'Invalid entry ID';
        echo json_encode($response);
        exit;
    }
    
    // Sanitize content
    $content = htmlspecialchars(trim($content));
    if (empty($content)) {
        $response['message'] = 'Comment content cannot be empty';
        echo json_encode($response);
        exit;
    }
    
    // Check if entry exists
    $stmt = $conn->prepare("SELECT * FROM journal_entries WHERE entry_id = :entry_id");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Entry not found';
        echo json_encode($response);
        exit;
    }
    
    // Insert comment
    $stmt = $conn->prepare("
        INSERT INTO comments (entry_id, user_id, content, created_at)
        VALUES (:entry_id, :user_id, :content, NOW())
    ");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':content', $content);
    $stmt->execute();
    
    $commentId = $conn->lastInsertId();
    
    // Get the created comment with user information
    $stmt = $conn->prepare("
        SELECT 
            c.*,
            u.username,
            u.full_name,
            u.profile_image
        FROM comments c
        JOIN users u ON c.user_id = u.user_id
        WHERE c.comment_id = :comment_id
    ");
    $stmt->bindParam(':comment_id', $commentId);
    $stmt->execute();
    
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Comment added successfully';
    $response['data'] = $comment;
    
    echo json_encode($response);
    exit;
}

/**
 * Delete a comment
 */
function deleteComment($conn, $userId, $commentId) {
    global $response;
    
    // Validate comment ID
    $commentId = filter_var($commentId, FILTER_VALIDATE_INT);
    if (!$commentId) {
        $response['message'] = 'Invalid comment ID';
        echo json_encode($response);
        exit;
    }
    
    // Check if comment exists and belongs to the user
    $stmt = $conn->prepare("
        SELECT * FROM comments 
        WHERE comment_id = :comment_id
    ");
    $stmt->bindParam(':comment_id', $commentId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Comment not found';
        echo json_encode($response);
        exit;
    }
    
    $comment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Check if user is the comment owner or the entry owner
    if ($comment['user_id'] !== $userId) {
        // Check if user is the entry owner
        $stmt = $conn->prepare("
            SELECT * FROM journal_entries
            WHERE entry_id = :entry_id AND user_id = :user_id
        ");
        $stmt->bindParam(':entry_id', $comment['entry_id']);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        if ($stmt->rowCount() === 0) {
            $response['message'] = 'You do not have permission to delete this comment';
            echo json_encode($response);
            exit;
        }
    }
    
    // Delete comment
    $stmt = $conn->prepare("
        DELETE FROM comments 
        WHERE comment_id = :comment_id
    ");
    $stmt->bindParam(':comment_id', $commentId);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'Comment deleted successfully';
    
    echo json_encode($response);
    exit;
} 