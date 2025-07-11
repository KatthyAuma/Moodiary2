<?php
session_start();

// Check if user is logged in
$isLoggedIn = isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;

// Get user data from session
$username = $isLoggedIn ? $_SESSION['username'] : '';
$fullName = $isLoggedIn ? $_SESSION['full_name'] : '';
$profileImage = $isLoggedIn ? (isset($_SESSION['profile_image']) && $_SESSION['profile_image'] ? $_SESSION['profile_image'] : 'default.png') : 'default.png';
$userRoles = $isLoggedIn ? $_SESSION['roles'] : [];

// Check if user has admin role
$isAdmin = $isLoggedIn && in_array('admin', $userRoles);
$isMentor = $isLoggedIn && in_array('mentor', $userRoles);
$isCounsellor = $isLoggedIn && in_array('counsellor', $userRoles);

// Redirect if not logged in and trying to access protected page
$publicPages = ['index.php', 'signin.php'];
$currentPage = basename($_SERVER['PHP_SELF']);

if (!$isLoggedIn && !in_array($currentPage, $publicPages)) {
    header("Location: signin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Moodiary - Track your moods, share vibes, heal together" />
  <meta name="theme-color" content="#d4b8a8" />
  <title>Moodiary</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
<?php if ($isLoggedIn): ?>
<div class="main-header">
  <div class="header-left">
    <!-- This space is reserved for the menu button that will be added in each page -->
  </div>
  <div class="header-user">
    <?php if ($profileImage && $profileImage !== 'default.png'): ?>
      <img src="../images/profiles/<?php echo htmlspecialchars($profileImage); ?>" alt="User Image" class="header-avatar">
    <?php else: ?>
      <div class="header-avatar">
        <?php 
          $initials = '';
          if ($fullName) {
            $nameParts = explode(' ', $fullName);
            foreach ($nameParts as $part) {
              $initials .= strtoupper(substr($part, 0, 1));
              if (strlen($initials) >= 2) break;
            }
          } else {
            $initials = strtoupper(substr($username, 0, 2));
          }
          echo htmlspecialchars($initials);
        ?>
      </div>
    <?php endif; ?>
    <div class="header-user-info">
      <span class="header-username"><?php echo htmlspecialchars($username); ?></span>
      <?php if (!empty($userRoles)): ?>
        <span class="header-role"><?php echo htmlspecialchars(implode(', ', $userRoles)); ?></span>
      <?php endif; ?>
    </div>
  </div>
</div>
<?php endif; ?> 