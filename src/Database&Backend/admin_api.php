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
    // Check if user is logged in and is admin
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['logged_in']) || $_SESSION['logged_in'] !== true) {
        $response['message'] = 'Unauthorized. Please log in.';
        echo json_encode($response);
        exit;
    }
    
    // Check if user has admin role
    if (!isset($_SESSION['roles']) || !in_array('admin', $_SESSION['roles'])) {
        $response['message'] = 'Access denied. Admin privileges required.';
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
    
    // Handle GET requests
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'get_users':
                getUsersList($conn);
                break;
                
            case 'get_user':
                if (isset($_GET['user_id'])) {
                    getUserDetails($conn, $_GET['user_id']);
                } else {
                    $response['message'] = 'User ID is required';
                }
                break;
                
            case 'get_stats':
                getSystemStats($conn);
                break;
                
            case 'get_mentees':
                if (isset($_GET['mentor_id'])) {
                    getMenteesByMentor($conn, $_GET['mentor_id']);
                } else {
                    $response['message'] = 'Mentor ID is required';
                }
                break;
                
            case 'get_clients':
                if (isset($_GET['counsellor_id'])) {
                    getClientsByCounsellor($conn, $_GET['counsellor_id']);
                } else {
                    $response['message'] = 'Counsellor ID is required';
                }
                break;
                
            case 'search_users':
                if (isset($_GET['search'])) {
                    searchUsers($conn, $_GET['search']);
                } else {
                    $response['message'] = 'Search term is required';
                }
                break;
            
            default:
                $response['message'] = 'Invalid action';
        }
    }
    
    // Handle POST requests
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Get JSON data
        $data = json_decode(file_get_contents("php://input"), true);
        
        if (!$data) {
            // If not JSON, try regular POST
            $data = $_POST;
        }
        
        $action = $data['action'] ?? '';
        
        switch ($action) {
            case 'update_user':
                if (isset($data['user_id'])) {
                    updateUser($conn, $data);
                } else {
                    $response['message'] = 'User ID is required';
                }
                break;
                
            case 'update_user_status':
                if (isset($data['user_id']) && isset($data['status'])) {
                    updateUserStatus($conn, $data['user_id'], $data['status']);
                } else {
                    $response['message'] = 'User ID and status are required';
                }
                break;
                
            case 'update_user_roles':
                if (isset($data['user_id']) && isset($data['roles'])) {
                    updateUserRoles($conn, $data['user_id'], $data['roles']);
                } else {
                    $response['message'] = 'User ID and roles are required';
                }
                break;
            
            case 'delete_user':
                if (isset($data['user_id'])) {
                    deleteUser($conn, $data['user_id']);
                } else {
                    $response['message'] = 'User ID is required';
                }
                break;
                
            case 'assign_mentees':
                if (isset($data['mentor_id']) && isset($data['mentee_ids'])) {
                    assignMentees($conn, $data['mentor_id'], $data['mentee_ids']);
                } else {
                    $response['message'] = 'Mentor ID and mentee IDs are required';
                }
                break;
                
            case 'assign_clients':
                if (isset($data['counsellor_id']) && isset($data['client_ids'])) {
                    assignClients($conn, $data['counsellor_id'], $data['client_ids']);
                } else {
                    $response['message'] = 'Counsellor ID and client IDs are required';
                }
                break;
            
            default:
                $response['message'] = 'Invalid action';
        }
    }
    
} catch (Exception $e) {
    $response['message'] = 'Error: ' . $e->getMessage();
    error_log('Admin API error: ' . $e->getMessage());
}

// Return JSON response
echo json_encode($response);
exit;

/**
 * Get list of all users
 */
function getUsersList($conn) {
    global $response;
    
    $searchTerm = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    
    // Check if status column exists in users table
    $statusExists = false;
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'status'");
        $stmt->execute();
        $statusExists = ($stmt->rowCount() > 0);
    } catch (Exception $e) {
        // Ignore error, assume status doesn't exist
    }
    
    // Build query based on whether status column exists
    if ($statusExists) {
        $query = "SELECT u.user_id, u.email, u.username, u.full_name, u.profile_image, u.last_login, 
                  u.created_at, u.updated_at, u.status,
                  GROUP_CONCAT(r.role_name) as roles
                  FROM users u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  WHERE u.email LIKE :search OR u.username LIKE :search OR u.full_name LIKE :search
                  GROUP BY u.user_id
                  ORDER BY u.created_at DESC";
    } else {
        $query = "SELECT u.user_id, u.email, u.username, u.full_name, u.profile_image, u.last_login, 
                  u.created_at, u.updated_at, 
                  'active' as status, -- Default status if column doesn't exist
                  GROUP_CONCAT(r.role_name) as roles
                  FROM users u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  WHERE u.email LIKE :search OR u.username LIKE :search OR u.full_name LIKE :search
                  GROUP BY u.user_id
                  ORDER BY u.created_at DESC";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':search', $searchTerm);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Users retrieved successfully';
    $response['users'] = $users;
    
    echo json_encode($response);
    exit;
}

/**
 * Get user details by ID
 */
function getUserDetails($conn, $userId) {
    global $response;
    
    // Check if status column exists in users table
    $statusExists = false;
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'status'");
        $stmt->execute();
        $statusExists = ($stmt->rowCount() > 0);
    } catch (Exception $e) {
        // Ignore error, assume status doesn't exist
    }
    
    // Build query based on whether status column exists
    if ($statusExists) {
        $query = "SELECT u.user_id, u.email, u.username, u.full_name, u.profile_image, u.bio,
                  u.last_login, u.created_at, u.updated_at, u.status,
                  GROUP_CONCAT(r.role_name) as roles
                  FROM users u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  WHERE u.user_id = :user_id
                  GROUP BY u.user_id";
    } else {
        $query = "SELECT u.user_id, u.email, u.username, u.full_name, u.profile_image, u.bio,
                  u.last_login, u.created_at, u.updated_at,
                  'active' as status, -- Default status if column doesn't exist
                  GROUP_CONCAT(r.role_name) as roles
                  FROM users u
                  LEFT JOIN user_roles ur ON u.user_id = ur.user_id
                  LEFT JOIN roles r ON ur.role_id = r.role_id
                  WHERE u.user_id = :user_id
                  GROUP BY u.user_id";
    }
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($user) {
        // Get user's journal entries count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM journal_entries WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user['journal_count'] = $stmt->fetchColumn();
        
        // Get user's friends count
        $stmt = $conn->prepare("SELECT COUNT(*) FROM friendships 
                               WHERE (user_id = :user_id OR friend_id = :user_id) 
                               AND status = 'accepted'");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        $user['friends_count'] = $stmt->fetchColumn();
        
        $response['status'] = 'success';
        $response['message'] = 'User details retrieved successfully';
        $response['user'] = $user;
    } else {
        $response['message'] = 'User not found';
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Update user information
 */
function updateUser($conn, $data) {
    global $response;
    
    $userId = $data['user_id'];
    $email = $data['email'] ?? null;
    $username = $data['username'] ?? null;
    $fullName = $data['full_name'] ?? null;
    $bio = $data['bio'] ?? null;
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Update user data
        $query = "UPDATE users SET ";
        $params = [];
        
        if ($email) {
            $query .= "email = :email, ";
            $params[':email'] = $email;
        }
        
        if ($username) {
            $query .= "username = :username, ";
            $params[':username'] = $username;
        }
        
        if ($fullName) {
            $query .= "full_name = :full_name, ";
            $params[':full_name'] = $fullName;
        }
        
        if ($bio) {
            $query .= "bio = :bio, ";
            $params[':bio'] = $bio;
        }
        
        $query .= "updated_at = NOW() WHERE user_id = :user_id";
        $params[':user_id'] = $userId;
        
        $stmt = $conn->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'User updated successfully';
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Update user status (active/disabled)
 */
function updateUserStatus($conn, $userId, $status) {
    global $response;
    
    // Check if status column exists in users table
    $statusExists = false;
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM users LIKE 'status'");
        $stmt->execute();
        $statusExists = ($stmt->rowCount() > 0);
    } catch (Exception $e) {
        // Ignore error, assume status doesn't exist
    }
    
    if (!$statusExists) {
        $response['status'] = 'error';
        $response['message'] = 'Status column does not exist in users table';
        echo json_encode($response);
        exit;
    }
    
    // Validate status
    if (!in_array($status, ['active', 'disabled'])) {
        $response['message'] = 'Invalid status value';
        echo json_encode($response);
        exit;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit;
    }
    
    // Update user status
    $stmt = $conn->prepare("UPDATE users SET status = :status, updated_at = NOW() WHERE user_id = :user_id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    $response['status'] = 'success';
    $response['message'] = 'User status updated successfully';
    
    echo json_encode($response);
    exit;
}

/**
 * Update user roles
 */
function updateUserRoles($conn, $userId, $roles) {
    global $response;
    
    // Validate roles
    $validRoles = ['admin', 'user', 'mentor', 'counsellor'];
    $rolesToAdd = array_intersect($roles, $validRoles);
    
    if (empty($rolesToAdd)) {
        $response['message'] = 'No valid roles provided';
        echo json_encode($response);
        exit;
    }
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete current roles
        $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // Add new roles
        foreach ($rolesToAdd as $role) {
            // Get role ID
            $stmt = $conn->prepare("SELECT role_id FROM roles WHERE role_name = :role_name");
            $stmt->bindParam(':role_name', $role);
            $stmt->execute();
            $roleId = $stmt->fetchColumn();
            
            if ($roleId) {
                $stmt = $conn->prepare("INSERT INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)");
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':role_id', $roleId);
                $stmt->execute();
            }
        }
        
        // Handle special roles (mentor/counsellor)
        // If mentor role is added, ensure they are properly set up in mentor_mentee table
        if (in_array('mentor', $rolesToAdd)) {
            // Get all friendships where this user is a friend and relationship_type is mentor
            $stmt = $conn->prepare("
                SELECT user_id FROM friendships 
                WHERE friend_id = :user_id AND relationship_type = 'mentor' AND status = 'accepted'
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $menteeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // For each mentee, ensure there's an entry in mentor_mentee table
            foreach ($menteeIds as $menteeId) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM mentor_mentee 
                    WHERE mentor_id = :mentor_id AND mentee_id = :mentee_id
                ");
                $stmt->bindParam(':mentor_id', $userId);
                $stmt->bindParam(':mentee_id', $menteeId);
                $stmt->execute();
                
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO mentor_mentee (mentor_id, mentee_id, created_at)
                        VALUES (:mentor_id, :mentee_id, NOW())
                    ");
                    $stmt->bindParam(':mentor_id', $userId);
                    $stmt->bindParam(':mentee_id', $menteeId);
                    $stmt->execute();
                }
            }
        }
        
        // If counsellor role is added, ensure they are properly set up in counsellor_client table
        if (in_array('counsellor', $rolesToAdd)) {
            // Get all friendships where this user is a friend and relationship_type is counsellor
            $stmt = $conn->prepare("
                SELECT user_id FROM friendships 
                WHERE friend_id = :user_id AND relationship_type = 'counsellor' AND status = 'accepted'
            ");
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $clientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            // For each client, ensure there's an entry in counsellor_client table
            foreach ($clientIds as $clientId) {
                $stmt = $conn->prepare("
                    SELECT COUNT(*) FROM counsellor_client 
                    WHERE counsellor_id = :counsellor_id AND client_id = :client_id
                ");
                $stmt->bindParam(':counsellor_id', $userId);
                $stmt->bindParam(':client_id', $clientId);
                $stmt->execute();
                
                if ($stmt->fetchColumn() == 0) {
                    $stmt = $conn->prepare("
                        INSERT INTO counsellor_client (counsellor_id, client_id, created_at)
                        VALUES (:counsellor_id, :client_id, NOW())
                    ");
                    $stmt->bindParam(':counsellor_id', $userId);
                    $stmt->bindParam(':client_id', $clientId);
                    $stmt->execute();
                }
            }
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'User roles updated successfully';
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        throw $e;
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Get system statistics
 */
function getSystemStats($conn) {
    global $response;
    
    // Total users
    $stmt = $conn->query("SELECT COUNT(*) FROM users");
    $totalUsers = $stmt->fetchColumn();
    
    // Active users today
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE last_login >= CURDATE()");
    $activeToday = $stmt->fetchColumn();
    
    // Total journal entries
    $stmt = $conn->query("SELECT COUNT(*) FROM journal_entries");
    $totalEntries = $stmt->fetchColumn();
    
    // New users in last 7 days
    $stmt = $conn->query("SELECT COUNT(*) FROM users WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
    $newUsers7d = $stmt->fetchColumn();
    
    $response['status'] = 'success';
    $response['message'] = 'Stats retrieved successfully';
    $response['stats'] = [
        'total_users' => $totalUsers,
        'active_today' => $activeToday,
        'total_entries' => $totalEntries,
        'new_users_7d' => $newUsers7d
    ];
    
    echo json_encode($response);
    exit;
} 

/**
 * Delete user and all associated data
 */
function deleteUser($conn, $userId) {
    global $response;
    
    // Check if user exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :user_id");
    $stmt->bindParam(':user_id', $userId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'User not found';
        echo json_encode($response);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Delete all related data in the correct order to respect foreign key constraints
        
        // 1. Delete from activity_logs
        $stmt = $conn->prepare("DELETE FROM activity_logs WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 2. Delete from messages (both sent and received)
        $stmt = $conn->prepare("DELETE FROM messages WHERE sender_id = :user_id OR receiver_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 3. Delete from reactions
        $stmt = $conn->prepare("DELETE FROM reactions WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 4. Delete from comments
        $stmt = $conn->prepare("DELETE FROM comments WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 5. Delete from counselling_sessions (both as counsellor and client)
        $stmt = $conn->prepare("DELETE FROM counselling_sessions WHERE counsellor_id = :user_id OR client_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 6. Delete from counsellor_client (both as counsellor and client)
        $stmt = $conn->prepare("DELETE FROM counsellor_client WHERE counsellor_id = :user_id OR client_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 7. Delete from mentor_mentee (both as mentor and mentee)
        $stmt = $conn->prepare("DELETE FROM mentor_mentee WHERE mentor_id = :user_id OR mentee_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 8. Delete from friendships (both as user and friend)
        $stmt = $conn->prepare("DELETE FROM friendships WHERE user_id = :user_id OR friend_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 9. Delete comments on user's journal entries
        $stmt = $conn->prepare("
            DELETE FROM comments 
            WHERE entry_id IN (SELECT entry_id FROM journal_entries WHERE user_id = :user_id)
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 10. Delete reactions on user's journal entries
        $stmt = $conn->prepare("
            DELETE FROM reactions 
            WHERE entry_id IN (SELECT entry_id FROM journal_entries WHERE user_id = :user_id)
        ");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 11. Delete journal entries
        $stmt = $conn->prepare("DELETE FROM journal_entries WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 12. Delete user roles
        $stmt = $conn->prepare("DELETE FROM user_roles WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // 13. Finally, delete the user
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        // Commit the transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'User and all associated data deleted successfully';
        
    } catch (Exception $e) {
        // Rollback the transaction on error
        $conn->rollBack();
        $response['message'] = 'Error deleting user: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
} 

/**
 * Search for users
 */
function searchUsers($conn, $searchTerm) {
    global $response;
    
    // Sanitize search term
    $searchTerm = '%' . trim($searchTerm) . '%';
    
    // Build query
    $query = "SELECT u.user_id, u.username, u.email, u.full_name
              FROM users u
              WHERE (u.username LIKE :search_term OR u.email LIKE :search_term OR u.full_name LIKE :search_term)
              ORDER BY u.username
              LIMIT 20";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':search_term', $searchTerm);
    $stmt->execute();
    
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Users found';
    $response['users'] = $users;
    
    echo json_encode($response);
    exit;
}

/**
 * Get mentees for a mentor
 */
function getMenteesByMentor($conn, $mentorId) {
    global $response;
    
    // Check if mentor exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :mentor_id");
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Mentor not found';
        echo json_encode($response);
        exit;
    }
    
    // Get mentees from both tables
    $query = "SELECT DISTINCT u.user_id, u.username, u.email, u.full_name
              FROM users u
              LEFT JOIN mentor_mentee mm ON u.user_id = mm.mentee_id
              LEFT JOIN friendships f ON (u.user_id = f.user_id AND f.friend_id = :mentor_id) 
                                      OR (u.user_id = f.friend_id AND f.user_id = :mentor_id)
              WHERE (mm.mentor_id = :mentor_id) 
                 OR (f.status = 'accepted' AND f.relationship_type = 'mentor' AND 
                    ((f.user_id = :mentor_id AND f.friend_id = u.user_id) OR
                     (f.friend_id = :mentor_id AND f.user_id = u.user_id)))";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    
    $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Mentees retrieved successfully';
    $response['mentees'] = $mentees;
    
    echo json_encode($response);
    exit;
}

/**
 * Get clients for a counsellor
 */
function getClientsByCounsellor($conn, $counsellorId) {
    global $response;
    
    // Check if counsellor exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :counsellor_id");
    $stmt->bindParam(':counsellor_id', $counsellorId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Counsellor not found';
        echo json_encode($response);
        exit;
    }
    
    // Get clients from both tables
    $query = "SELECT DISTINCT u.user_id, u.username, u.email, u.full_name
              FROM users u
              LEFT JOIN counsellor_client cc ON u.user_id = cc.client_id
              LEFT JOIN friendships f ON (u.user_id = f.user_id AND f.friend_id = :counsellor_id) 
                                      OR (u.user_id = f.friend_id AND f.user_id = :counsellor_id)
              WHERE (cc.counsellor_id = :counsellor_id) 
                 OR (f.status = 'accepted' AND f.relationship_type = 'counsellor' AND 
                    ((f.user_id = :counsellor_id AND f.friend_id = u.user_id) OR
                     (f.friend_id = :counsellor_id AND f.user_id = u.user_id)))";
    
    $stmt = $conn->prepare($query);
    $stmt->bindParam(':counsellor_id', $counsellorId);
    $stmt->execute();
    
    $clients = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $response['status'] = 'success';
    $response['message'] = 'Clients retrieved successfully';
    $response['clients'] = $clients;
    
    echo json_encode($response);
    exit;
}

/**
 * Assign mentees to a mentor
 */
function assignMentees($conn, $mentorId, $menteeIds) {
    global $response;
    
    // Check if mentor exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :mentor_id");
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Mentor not found';
        echo json_encode($response);
        exit;
    }
    
    // Check if mentor has the mentor role
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = :mentor_id AND r.role_name = 'mentor'
    ");
    $stmt->bindParam(':mentor_id', $mentorId);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $response['message'] = 'User is not a mentor';
        echo json_encode($response);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Get current mentees
        $stmt = $conn->prepare("
            SELECT mentee_id FROM mentor_mentee WHERE mentor_id = :mentor_id
        ");
        $stmt->bindParam(':mentor_id', $mentorId);
        $stmt->execute();
        
        $currentMenteeIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Mentees to add
        $menteesToAdd = array_diff($menteeIds, $currentMenteeIds);
        
        // Mentees to remove
        $menteesToRemove = array_diff($currentMenteeIds, $menteeIds);
        
        // Add new mentees
        foreach ($menteesToAdd as $menteeId) {
            // First check if the mentee exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :mentee_id");
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                continue; // Skip if mentee doesn't exist
            }
            
            // Add to mentor_mentee table
            $stmt = $conn->prepare("
                INSERT INTO mentor_mentee (mentor_id, mentee_id, created_at)
                VALUES (:mentor_id, :mentee_id, NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
            
            // Check if friendship exists
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM friendships
                WHERE ((user_id = :mentor_id AND friend_id = :mentee_id) OR
                       (user_id = :mentee_id AND friend_id = :mentor_id))
                AND status = 'accepted'
            ");
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                // Create friendship
                $stmt = $conn->prepare("
                    INSERT INTO friendships (user_id, friend_id, relationship_type, status)
                    VALUES (:mentor_id, :mentee_id, 'mentor', 'accepted')
                ");
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->bindParam(':mentee_id', $menteeId);
                $stmt->execute();
            } else {
                // Update existing friendship
                $stmt = $conn->prepare("
                    UPDATE friendships
                    SET relationship_type = 'mentor', status = 'accepted'
                    WHERE (user_id = :mentor_id AND friend_id = :mentee_id)
                ");
                $stmt->bindParam(':mentor_id', $mentorId);
                $stmt->bindParam(':mentee_id', $menteeId);
                $stmt->execute();
            }
        }
        
        // Remove mentees
        foreach ($menteesToRemove as $menteeId) {
            // Remove from mentor_mentee table
            $stmt = $conn->prepare("
                DELETE FROM mentor_mentee
                WHERE mentor_id = :mentor_id AND mentee_id = :mentee_id
            ");
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
            
            // Update friendship if it exists
            $stmt = $conn->prepare("
                UPDATE friendships
                SET relationship_type = 'friend'
                WHERE ((user_id = :mentor_id AND friend_id = :mentee_id) OR
                       (user_id = :mentee_id AND friend_id = :mentor_id))
                AND relationship_type = 'mentor'
            ");
            $stmt->bindParam(':mentor_id', $mentorId);
            $stmt->bindParam(':mentee_id', $menteeId);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'Mentees assigned successfully';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $response['message'] = 'Error assigning mentees: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
}

/**
 * Assign clients to a counsellor
 */
function assignClients($conn, $counsellorId, $clientIds) {
    global $response;
    
    // Check if counsellor exists
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :counsellor_id");
    $stmt->bindParam(':counsellor_id', $counsellorId);
    $stmt->execute();
    
    if ($stmt->rowCount() === 0) {
        $response['message'] = 'Counsellor not found';
        echo json_encode($response);
        exit;
    }
    
    // Check if counsellor has the counsellor role
    $stmt = $conn->prepare("
        SELECT COUNT(*) FROM user_roles ur
        JOIN roles r ON ur.role_id = r.role_id
        WHERE ur.user_id = :counsellor_id AND r.role_name = 'counsellor'
    ");
    $stmt->bindParam(':counsellor_id', $counsellorId);
    $stmt->execute();
    
    if ($stmt->fetchColumn() == 0) {
        $response['message'] = 'User is not a counsellor';
        echo json_encode($response);
        exit;
    }
    
    // Start transaction
    $conn->beginTransaction();
    
    try {
        // Get current clients
        $stmt = $conn->prepare("
            SELECT client_id FROM counsellor_client WHERE counsellor_id = :counsellor_id
        ");
        $stmt->bindParam(':counsellor_id', $counsellorId);
        $stmt->execute();
        
        $currentClientIds = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Clients to add
        $clientsToAdd = array_diff($clientIds, $currentClientIds);
        
        // Clients to remove
        $clientsToRemove = array_diff($currentClientIds, $clientIds);
        
        // Add new clients
        foreach ($clientsToAdd as $clientId) {
            // First check if the client exists
            $stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = :client_id");
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                continue; // Skip if client doesn't exist
            }
            
            // Add to counsellor_client table
            $stmt = $conn->prepare("
                INSERT INTO counsellor_client (counsellor_id, client_id, created_at)
                VALUES (:counsellor_id, :client_id, NOW())
                ON DUPLICATE KEY UPDATE updated_at = NOW()
            ");
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
            
            // Check if friendship exists
            $stmt = $conn->prepare("
                SELECT COUNT(*) FROM friendships
                WHERE ((user_id = :counsellor_id AND friend_id = :client_id) OR
                       (user_id = :client_id AND friend_id = :counsellor_id))
                AND status = 'accepted'
            ");
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
            
            if ($stmt->fetchColumn() == 0) {
                // Create friendship
                $stmt = $conn->prepare("
                    INSERT INTO friendships (user_id, friend_id, relationship_type, status)
                    VALUES (:counsellor_id, :client_id, 'counsellor', 'accepted')
                ");
                $stmt->bindParam(':counsellor_id', $counsellorId);
                $stmt->bindParam(':client_id', $clientId);
                $stmt->execute();
            } else {
                // Update existing friendship
                $stmt = $conn->prepare("
                    UPDATE friendships
                    SET relationship_type = 'counsellor', status = 'accepted'
                    WHERE (user_id = :counsellor_id AND friend_id = :client_id)
                ");
                $stmt->bindParam(':counsellor_id', $counsellorId);
                $stmt->bindParam(':client_id', $clientId);
                $stmt->execute();
            }
        }
        
        // Remove clients
        foreach ($clientsToRemove as $clientId) {
            // Remove from counsellor_client table
            $stmt = $conn->prepare("
                DELETE FROM counsellor_client
                WHERE counsellor_id = :counsellor_id AND client_id = :client_id
            ");
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
            
            // Update friendship if it exists
            $stmt = $conn->prepare("
                UPDATE friendships
                SET relationship_type = 'friend'
                WHERE ((user_id = :counsellor_id AND friend_id = :client_id) OR
                       (user_id = :client_id AND friend_id = :counsellor_id))
                AND relationship_type = 'counsellor'
            ");
            $stmt->bindParam(':counsellor_id', $counsellorId);
            $stmt->bindParam(':client_id', $clientId);
            $stmt->execute();
        }
        
        // Commit transaction
        $conn->commit();
        
        $response['status'] = 'success';
        $response['message'] = 'Clients assigned successfully';
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollBack();
        $response['message'] = 'Error assigning clients: ' . $e->getMessage();
    }
    
    echo json_encode($response);
    exit;
} 