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
    // Check if user is logged in and is counsellor
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $response['message'] = 'Unauthorized. Please log in.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has counsellor role
    if (!isset($_SESSION['roles']) || !in_array('counsellor', $_SESSION['roles'])) {
        $response['message'] = 'Access denied. Counsellor privileges required.';
        echo json_encode($response);
        exit;
    }
    
    // Get user ID from session
    $counsellorId = $_SESSION['user_id'];
    
    // Create database connection
    $conn = getDbConnection();
    
    if (!$conn) {
        throw new Exception("Database connection failed");
    }

    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = isset($_GET['action']) ? $_GET['action'] : '';
        
        switch ($action) {
            case 'get_clients':
                getClients($conn, $counsellorId);
                break;
            case 'get_client_details':
                $clientId = isset($_GET['client_id']) ? intval($_GET['client_id']) : 0;
                if ($clientId) {
                    getClientDetails($conn, $counsellorId, $clientId);
                } else {
                    $response['message'] = 'Client ID is required';
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
                $clientId = isset($data['client_id']) ? intval($data['client_id']) : 0;
                $notes = isset($data['notes']) ? $data['notes'] : '';
                if ($clientId) {
                    saveNotes($conn, $counsellorId, $clientId, $notes);
                } else {
                    $response['message'] = 'Client ID is required';
                    echo json_encode($response);
                    exit;
                }
                break;
            case 'update_priority':
                $clientId = isset($data['client_id']) ? intval($data['client_id']) : 0;
                $priority = isset($data['priority']) ? (bool)$data['priority'] : false;
                if ($clientId) {
                    updatePriority($conn, $counsellorId, $clientId, $priority);
                } else {
                    $response['message'] = 'Client ID is required';
                    echo json_encode($response);
                    exit;
                }
                break;
            case 'schedule_session':
                $clientId = isset($data['client_id']) ? intval($data['client_id']) : 0;
                $sessionDate = isset($data['session_date']) ? $data['session_date'] : '';
                $sessionType = isset($data['session_type']) ? $data['session_type'] : '';
                
                if ($clientId && $sessionDate && $sessionType) {
                    scheduleSession($conn, $counsellorId, $clientId, $sessionDate, $sessionType);
                } else {
                    $response['message'] = 'Client ID, session date, and session type are required';
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
    error_log('Counsellor API error: ' . $e->getMessage());
    echo json_encode($response);
    exit;
}

/**
 * Get all clients assigned to the counsellor
 */
function getClients($conn, $counsellorId) {
    global $response;
    
    try {
        // Query to get all clients
        $query = "
            SELECT u.user_id, u.username, u.full_name, u.email, u.created_at, u.last_login,
                   cc.priority, 
                   CASE WHEN s.session_id IS NOT NULL THEN 1 ELSE 0 END as upcoming_session,
                   (SELECT m.mood_name FROM moods m 
                    JOIN journal_entries je ON m.mood_id = je.mood_id
                    WHERE je.user_id = u.user_id 
                    ORDER BY je.created_at DESC LIMIT 1) as recent_mood
            FROM users u
            JOIN friendships f ON u.user_id = f.friend_id
            LEFT JOIN counsellor_client cc ON u.user_id = cc.client_id AND cc.counsellor_id = :counsellor_id
            LEFT JOIN counselling_sessions s ON u.user_id = s.client_id AND s.counsellor_id = :counsellor_id 
                AND s.session_date BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 1 DAY)
            WHERE f.user_id = :counsellor_id 
            AND f.status = 'accepted' 
            AND f.relationship_type = 'counsellor'
            ORDER BY cc.priority DESC, upcoming_session DESC, u.last_login DESC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->execute();
        
        $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $response['status'] = 'success';
        $response['message'] = 'Clients retrieved successfully';
        $response['clients'] = $clients;
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Get details for a specific client
 */
function getClientDetails($conn, $counsellorId, $clientId) {
    global $response;
    
    try {
        // Check if client belongs to this counsellor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :counsellor_id 
                 AND friend_id = :client_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'counsellor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Client not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Get client details
        $query = "
            SELECT u.user_id, u.username, u.full_name, u.email, u.created_at, u.last_login,
                   (SELECT cc.notes FROM counsellor_client cc WHERE cc.counsellor_id = :counsellor_id AND cc.client_id = u.user_id) as notes,
                   (SELECT cc.priority FROM counsellor_client cc WHERE cc.counsellor_id = :counsellor_id AND cc.client_id = u.user_id) as priority,
                   (SELECT COUNT(*) FROM journal_entries WHERE user_id = u.user_id) as mood_count,
                   (SELECT COUNT(*) FROM counselling_sessions WHERE client_id = u.user_id AND counsellor_id = :counsellor_id) as session_count
            FROM users u
            WHERE u.user_id = :client_id
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        $client = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$client) {
            $response['message'] = 'Client not found';
            echo json_encode($response);
            exit;
        }
        
        // Get mood history
        $query = "
            SELECT je.created_at, je.content, m.mood_name
            FROM journal_entries je
            JOIN moods m ON je.mood_id = m.mood_id
            WHERE je.user_id = :client_id
            ORDER BY je.created_at DESC
            LIMIT 10
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        $moodHistory = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $client['mood_history'] = $moodHistory;
        
        // Get upcoming sessions
        $query = "
            SELECT session_id, session_date, session_type, notes
            FROM counselling_sessions
            WHERE client_id = :client_id AND counsellor_id = :counsellor_id AND session_date >= NOW()
            ORDER BY session_date ASC
        ";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->execute();
        
        $upcomingSessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $client['upcoming_sessions'] = $upcomingSessions;
        
        // Log this access in activity logs
        logActivity($conn, $counsellorId, 'viewed_client', $clientId);
        
        $response['status'] = 'success';
        $response['message'] = 'Client details retrieved successfully';
        $response['client'] = $client;
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Save notes about a client
 */
function saveNotes($conn, $counsellorId, $clientId, $notes) {
    global $response;
    
    try {
        // Check if client belongs to this counsellor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :counsellor_id 
                 AND friend_id = :client_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'counsellor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Client not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Check if entry exists in counsellor_client table
        $query = "SELECT COUNT(*) FROM counsellor_client 
                 WHERE counsellor_id = :counsellor_id 
                 AND client_id = :client_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            // Update existing record
            $query = "UPDATE counsellor_client 
                     SET notes = :notes, updated_at = NOW() 
                     WHERE counsellor_id = :counsellor_id 
                     AND client_id = :client_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':notes', $notes);
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
        } else {
            // Insert new record
            $query = "INSERT INTO counsellor_client (counsellor_id, client_id, notes, created_at) 
                     VALUES (:counsellor_id, :client_id, :notes, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->bindParam(':notes', $notes);
            $stmt->execute();
        }
        
        // Log this action
        logActivity($conn, $counsellorId, 'updated_notes', $clientId);
        
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
 * Update priority status for a client
 */
function updatePriority($conn, $counsellorId, $clientId, $priority) {
    global $response;
    
    try {
        // Check if client belongs to this counsellor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :counsellor_id 
                 AND friend_id = :client_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'counsellor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Client not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Check if entry exists in counsellor_client table
        $query = "SELECT COUNT(*) FROM counsellor_client 
                 WHERE counsellor_id = :counsellor_id 
                 AND client_id = :client_id";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        $priorityValue = $priority ? 1 : 0;
        
        if ($stmt->fetchColumn() > 0) {
            // Update existing record
            $query = "UPDATE counsellor_client 
                     SET priority = :priority, updated_at = NOW() 
                     WHERE counsellor_id = :counsellor_id 
                     AND client_id = :client_id";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':priority', $priorityValue, PDO::PARAM_INT);
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
        } else {
            // Insert new record
            $query = "INSERT INTO counsellor_client (counsellor_id, client_id, priority, created_at) 
                     VALUES (:counsellor_id, :client_id, :priority, NOW())";
            
            $stmt = $conn->prepare($query);
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->bindParam(':priority', $priorityValue, PDO::PARAM_INT);
            $stmt->execute();
        }
        
        // Log this action
        logActivity($conn, $counsellorId, 'updated_priority', $clientId);
        
        $response['status'] = 'success';
        $response['message'] = 'Priority status updated successfully';
        
        echo json_encode($response);
        exit;
    } catch (Exception $e) {
        $response['message'] = 'Database error: ' . $e->getMessage();
        echo json_encode($response);
        exit;
    }
}

/**
 * Schedule a counselling session
 */
function scheduleSession($conn, $counsellorId, $clientId, $sessionDate, $sessionType) {
    global $response;
    
    try {
        // Check if client belongs to this counsellor
        $query = "SELECT COUNT(*) FROM friendships 
                 WHERE user_id = :counsellor_id 
                 AND friend_id = :client_id 
                 AND status = 'accepted' 
                 AND relationship_type = 'counsellor'";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->execute();
        
        if ($stmt->fetchColumn() == 0) {
            $response['message'] = 'Client not found or not assigned to you';
            echo json_encode($response);
            exit;
        }
        
        // Validate session date
        try {
            $date = new DateTime($sessionDate);
            $formattedDate = $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            $response['message'] = 'Invalid date format. Please use YYYY-MM-DD HH:MM format.';
            echo json_encode($response);
            exit;
        }
        
        // Insert new session
        $query = "INSERT INTO counselling_sessions (counsellor_id, client_id, session_date, session_type) 
                 VALUES (:counsellor_id, :client_id, :session_date, :session_type)";
        
        $stmt = $conn->prepare($query);
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->bindParam(':client_id', $clientId);
        $stmt->bindParam(':session_date', $formattedDate);
        $stmt->bindParam(':session_type', $sessionType);
        $stmt->execute();
        
        // Log this action
        logActivity($conn, $counsellorId, 'scheduled_session', $clientId);
        
        $response['status'] = 'success';
        $response['message'] = 'Session scheduled successfully';
        $response['session_id'] = $conn->lastInsertId();
        
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