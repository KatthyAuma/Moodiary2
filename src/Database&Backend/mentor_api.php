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
    'message' => 'Unknown error occurred',
    'data' => null
];

try {
    // Check if user is logged in and is mentor
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $response['message'] = 'Unauthorized. Please log in.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has mentor role
    if (!isset($_SESSION['roles']) || !in_array('mentor', $_SESSION['roles'])) {
        $response['message'] = 'Access denied. Mentor privileges required.';
        echo json_encode($response);
        exit;
    }
    
    // Get user ID from session
    $mentorId = $_SESSION['user_id'];
    
    // Create database connection
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'get_mentees':
                getMentees($conn, $mentorId);
                break;
            case 'get_mentee_details':
                $menteeId = isset($_GET['mentee_id']) ? intval($_GET['mentee_id']) : 0;
                if ($menteeId) {
                    getMenteeDetails($conn, $mentorId, $menteeId);
                } else {
                    $response['message'] = 'Mentee ID is required';
                    echo json_encode($response);
                    exit;
                }
                break;
            default:
                $response['message'] = 'Invalid action';
                echo json_encode($response);
                exit;
        }
    }

    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON data
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (!$data) {
            // If not JSON, try regular POST
            $data = $_POST;
        }
        
        $action = isset($data['action']) ? $data['action'] : '';
        
        switch ($action) {
            case 'save_notes':
                $menteeId = isset($data['mentee_id']) ? intval($data['mentee_id']) : 0;
                $notes = isset($data['notes']) ? $data['notes'] : '';
                if ($menteeId) {
                    saveNotes($conn, $mentorId, $menteeId, $notes);
                } else {
                    $response['message'] = 'Mentee ID is required';
                    echo json_encode($response);
                    exit;
                }
                break;
            case 'mark_reviewed':
                $menteeId = isset($data['mentee_id']) ? intval($data['mentee_id']) : 0;
                if ($menteeId) {
                    markReviewed($conn, $mentorId, $menteeId);
                } else {
                    $response['message'] = 'Mentee ID is required';
                    echo json_encode($response);
                    exit;
                }
                break;
            default:
                $response['message'] = 'Invalid action';
                echo json_encode($response);
                exit;
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Mentor API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get all mentees assigned to the mentor
 */
function getMentees($conn, $mentorId) {
    global $response;
    
    try {
        // Query to get all mentees
        $query = "
            SELECT u.user_id, u.username, u.full_name, u.email, u.created_at, u.last_login,
                   mm.needs_attention, 
                   (SELECT m.mood_name FROM moods m 
                    JOIN journal_entries je ON m.mood_id = je.mood_id
                    WHERE je.user_id = u.user_id 
                    ORDER BY je.created_at DESC LIMIT 1) as recent_mood
            FROM users u
            JOIN friendships f ON u.user_id = f.friend_id
            LEFT JOIN mentor_mentee mm ON u.user_id = mm.mentee_id AND mm.mentor_id = :mentor_id
            WHERE f.user_id = :mentor_id 
            AND f.status = 'accepted' 
            AND f.relationship_type = 'mentor'
            ORDER BY mm.needs_attention DESC, u.last_login DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        
        $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['message'] = 'Mentees retrieved successfully';
        $response['mentees'] = $mentees;
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Get details for a specific mentee
 */
function getMenteeDetails($conn, $mentorId, $menteeId) {
    global $response;
    
    try {
        // Check if mentee belongs to this mentor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :mentor_id 
                 AND friend_id = :mentee_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'mentor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Mentee not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Get mentee details
        $query = "
            SELECT u.user_id, u.username, u.full_name, u.email, u.created_at, u.last_login,
                   (SELECT mm.notes FROM mentor_mentee mm WHERE mm.mentor_id = :mentor_id AND mm.mentee_id = u.user_id) as notes,
                   (SELECT mm.needs_attention FROM mentor_mentee mm WHERE mm.mentor_id = :mentor_id AND mm.mentee_id = u.user_id) as needs_attention,
                   (SELECT COUNT(*) FROM journal_entries WHERE user_id = u.user_id) as mood_count
            FROM users u
            WHERE u.user_id = :mentee_id
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        $mentee = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$mentee) {
            $response['message'] = 'Mentee not found';
            echo json_encode($response);
            exit;
        }
        
        // Get recent activity
        $query = "
            SELECT 'mood' as type, je.content as description, je.created_at, m.mood_name
            FROM journal_entries je
            JOIN moods m ON je.mood_id = m.mood_id
            WHERE je.user_id = :mentee_id
            ORDER BY je.created_at DESC
            LIMIT 5
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        $recentActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $mentee['recent_activity'] = $recentActivity;
        
        // Log this access in activity logs
        logActivity($conn, $mentorId, 'viewed_mentee', $menteeId);
        
        $response['status'] = 'success';
        $response['message'] = 'Mentee details retrieved successfully';
        $response['mentee'] = $mentee;
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Save notes about a mentee
 */
function saveNotes($conn, $mentorId, $menteeId, $notes) {
    global $response;
    
    try {
        // Check if mentee belongs to this mentor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :mentor_id 
                 AND friend_id = :mentee_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'mentor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Mentee not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Check if entry exists in mentor_mentee table
        $query = "SELECT COUNT(*) FROM mentor_mentee 
                 WHERE mentor_id = :mentor_id 
                 AND mentee_id = :mentee_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Update existing record
            $query = "UPDATE mentor_mentee 
                     SET notes = :notes, updated_at = NOW() 
                     WHERE mentor_id = :mentor_id 
                     AND mentee_id = :mentee_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
        } else {
            // Insert new record
            $query = "INSERT INTO mentor_mentee (mentor_id, mentee_id, notes, created_at) 
                     VALUES (:mentor_id, :mentee_id, :notes, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->bindParam(':notes', $notes);
            $stmt->execute();
        }
        
        // Log this action
        logActivity($conn, $mentorId, 'updated_notes', $menteeId);
        
        $response['status'] = 'success';
        $response['message'] = 'Notes saved successfully';
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Mark a mentee as reviewed (clear needs_attention flag)
 */
function markReviewed($conn, $mentorId, $menteeId) {
    global $response;
    
    try {
        // Check if mentee belongs to this mentor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :mentor_id 
                 AND friend_id = :mentee_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'mentor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Mentee not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Check if entry exists in mentor_mentee table
        $query = "SELECT COUNT(*) FROM mentor_mentee 
                 WHERE mentor_id = :mentor_id 
                 AND mentee_id = :mentee_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->bindParam(':mentee_id', $menteeId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Update existing record
            $query = "UPDATE mentor_mentee 
                     SET needs_attention = 0, updated_at = NOW() 
                     WHERE mentor_id = :mentor_id 
                     AND mentee_id = :mentee_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
        } else {
            // Insert new record
            $query = "INSERT INTO mentor_mentee (mentor_id, mentee_id, needs_attention, created_at) 
                     VALUES (:mentor_id, :mentee_id, 0, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
        }
        
        // Log this action
        logActivity($conn, $mentorId, 'marked_reviewed', $menteeId);
        
        $response['status'] = 'success';
        $response['message'] = 'Mentee marked as reviewed';
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Log activity in the system
 */
function logActivity($conn, $userId, $activityType, $relatedId = null) {
    try {
        $query = "INSERT INTO activity_logs (user_id, activity_type, related_id) VALUES (:user_id, :activity_type, :related_id)";
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindParam(':activity_type', $activityType);
        $stmt->bindParam(':related_id', $relatedId);
        $stmt->execute();
    } catch (Exception $e) {
        // Silently fail - logging should not interrupt the main flow
        error_log('Activity logging error: ' . $e->getMessage());
    }
}
?> 