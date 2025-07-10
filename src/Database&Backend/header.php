<?php
session_start();

// Redirect if session not set
if (!isset($_SESSION['username']) || !isset($_SESSION['user_image'])) {
    header("Location: login.php"); // Redirect to login page
    exit();
}
?>

<style>
.header {
    background-color: lightgray;  
    padding: 12px 20px;
    display: flex;
    align-items: center;
    gap: 15px;
    border-bottom: 1px solid gray;
}
.header img {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    object-fit: cover;
}
.header span {
    font-weight: bold;
    font-size: 16px;
    color: black; 
}
</style>

<div class="header">
    <img src="<?php echo htmlspecialchars($_SESSION['user_image']); ?>" alt="User Image">
    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
</div>
