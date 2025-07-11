<?php
// Start session
session_start();

// If already logged in, redirect to home page
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit();
}

// Set page title
$pageTitle = "Sign In";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Moodiary - Track your moods, share vibes, heal together" />
  <meta name="theme-color" content="#7371fc" />
  <title>Sign In - Moodiary</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <div class="screen auth-container">
    <div class="logo">
      <h1>Moodiary</h1>
      <p>Track moods, share vibes, heal together ❤️</p>
    </div>
    
    <div class="form-group">
      <label for="signin-email">Email</label>
      <input type="email" id="signin-email" placeholder="Enter your email" required />
    </div>
    <div class="form-group">
      <label for="signin-password">Password</label>
      <input type="password" id="signin-password" placeholder="Enter your password" required />
    </div>

    <button class="auth-button" id="signin-btn">Sign In</button>

    <div class="auth-link">
      <span>Don't have an account?</span>
      <a href="index.php">Sign up</a>
    </div>
    
    <img src="../images/Catchingup.png" class="bottom-left-img" alt="Moodiary Logo" />
    <img src="../images/Loving.png" class="right-image" alt="Hugging character" />
  </div>

  <script>
    // Update auth.js to redirect to home.php instead of home.html
    document.addEventListener('DOMContentLoaded', function() {
      const signinBtn = document.getElementById('signin-btn');
      if (signinBtn) {
        signinBtn.addEventListener('click', function() {
          const email = document.getElementById('signin-email').value;
          const password = document.getElementById('signin-password').value;
          
          // Validate inputs
          if (!email || !password) {
            alert('Please enter both email and password');
            return;
          }
          
          // Send login request
          fetch('../Database&Backend/login.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              email: email,
              password: password
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              // Redirect to home page
              window.location.href = 'home.php';
            } else {
              alert(data.message || 'Login failed');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('An error occurred. Please try again.');
          });
        });
      }
    });
  </script>
</body>
</html> 