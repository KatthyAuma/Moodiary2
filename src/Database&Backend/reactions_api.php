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
            // Get reactions for a specific entry
            if (isset($_GET['entry_id'])) {
                getReactions($conn, $_GET['entry_id'], $userId);
            } else {
                $response['message'] = 'Entry ID is required';
                echo json_encode($response);
            }
            break;
            
        case 'POST':
            // Handle POST request for adding/removing reactions
            $data = json_decode(file_get_contents('php://input'), true);
            
            if (!$data) {
                $response['message'] = 'Invalid request data';
                echo json_encode($response);
                exit;
            }
            
            if (empty($data['entry_id']) || empty($data['reaction_type'])) {
                $response['message'] = 'Entry ID and reaction type are required';
                echo json_encode($response);
                exit;
            }
            
            toggleReaction($conn, $userId, $data['entry_id'], $data['reaction_type']);
            break;
            
        default:
            $response['message'] = 'Method not allowed';
            echo json_encode($response);
            break;
    }
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Reactions API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get reactions for a specific entry
 */
function getReactions($conn, $entryId, $userId) {
    global $response;
    
    // Validate entry ID
    $entryId = filter_var($entryId, FILTER_VALIDATE_INT);
    if (!$entryId) {
        $response['message'] = 'Invalid entry ID';
        echo json_encode($response);
        exit;
    }
    
    // Get reaction counts
    $stmt = $conn->prepare("
        SELECT 
            reaction_type, 
            COUNT(*) as count 
        FROM reactions 
        WHERE entry_id = :entry_id 
        GROUP BY reaction_type
    ");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->execute();
    
    $reactions = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $reactions[$row['reaction_type']] = $row['count'];
    }
    
    // Check if user has reacted
    $stmt = $conn->prepare("
        SELECT reaction_type 
        FROM reactions 
        WHERE entry_id = :entry_id AND user_id = :user_id
    ");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $userReaction = $stmt->fetch(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['data'] = [
        'reactions' => $reactions,
        'user_reaction' => $userReaction ? $userReaction['reaction_type'] : null
    ];
    
    echo json_encode($response);
    exit;
}

/**
 * Toggle a reaction for an entry
 */
function toggleReaction($conn, $userId, $entryId, $reactionType) {
    global $response;
    
    // Validate entry ID and reaction type
    $entryId = filter_var($entryId, FILTER_VALIDATE_INT);
    if (!$entryId) {
        $response['message'] = 'Invalid entry ID';
        echo json_encode($response);
        exit;
    }
    
    $validReactions = ['like', 'support', 'hug'];
    if (!in_array($reactionType, $validReactions)) {
        $response['message'] = 'Invalid reaction type';
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
    
    // Check if user already reacted with this reaction type
    $stmt = $conn->prepare("
        SELECT * FROM reactions 
        WHERE entry_id = :entry_id AND user_id = :user_id AND reaction_type = :reaction_type
    ");
    $stmt->bindParam(':entry_id', $entryId);
    $stmt->bindParam(':user_id', $userId);
    $stmt->bindParam(':reaction_type', $reactionType);
    $stmt->execute();
    
    if ($stmt->rowCount() > 0) {
        // User already reacted, so remove the reaction
        $stmt = $conn->prepare("
            DELETE FROM reactions 
            WHERE entry_id = :entry_id AND user_id = :user_id AND reaction_type = :reaction_type
        ");
        $stmt->bindParam(':entry_id', $entryId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':reaction_type', $reactionType);
        $stmt->execute();
        
        $response['status'] = 'success';
        $response['message'] = 'Reaction removed';
        $response['data'] = [
            'action' => 'removed',
            'reaction_type' => $reactionType
        ];
    } else {
        // User hasn't reacted with this type yet, so add it
        
        // First, remove any existing reactions of different types from this user on this entry
        $stmt = $conn->prepare("
            DELETE FROM reactions 
            WHERE entry_id = :entry_id AND user_id = :user_id
        ");
        $stmt->bindParam(':entry_id', $entryId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // Add the new reaction
        $stmt = $conn->prepare("
            INSERT INTO reactions (entry_id, user_id, reaction_type, created_at)
            VALUES (:entry_id, :user_id, :reaction_type, NOW())
        ");
        $stmt->bindParam(':entry_id', $entryId);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':reaction_type', $reactionType);
        $stmt->execute();
        
        $response['status'] = 'success';
        $response['message'] = 'Reaction added';
        $response['data'] = [
            'action' => 'added',
            'reaction_type' => $reactionType
        ];
    }
    
    echo json_encode($response);
    exit;
} 