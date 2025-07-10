
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// Set role only if provided via URL (e.g., ?role=admin)
if (isset($_GET['role'])) {
    $_SESSION['role'] = $_GET['role'];
}

// Get current role from session (default: guest)
$role = $_SESSION['role'] ?? 'guest';
?>
<!DOCTYPE html>
<html>
<head>
    <title>Moodiary Dashboard</title>
</head>
<body>
    <h1>Welcome to Moodiary Dashboard</h1>
    <p>Current Role: <strong><?= htmlspecialchars($role) ?></strong></p>

    <p>Switch Role:</p>
    <ul>
        <li><a href="?role=admin">Admin</a></li>
        <li><a href="?role=user">User</a></li>
        <li><a href="?role=guest">Guest</a></li>
    </ul>

    <?php if ($role === 'admin'): ?>
        <h2>Admin View</h2>
        <ul>
            <li>Manage Users</li>
            <li>View All Mood Entries</li>
            <li>Site Settings</li>
        </ul>
    <?php elseif ($role === 'user'): ?>
        <h2>User View</h2>
        <ul>
            <li>My Mood Journal</li>
            <li>Submit New Entry</li>
            <li>My Stats</li>
        </ul>
    <?php else: ?>
        <h2>Guest View</h2>
        <p>Please <a href="login.php">Login</a> to access your dashboard.</p>
    <?php endif; ?>
</body>
</html>
