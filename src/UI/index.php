<?php
// Start session
session_start();

// If already logged in, redirect to home page
if (isset($_SESSION['user_id']) && isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    header("Location: home.php");
    exit();
}

// Set page title
$pageTitle = "Sign Up";
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <meta name="description" content="Moodiary - Track your moods, share vibes, heal together" />
  <meta name="theme-color" content="#7371fc" />
  <title>Signup - Moodiary</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css"/>
</head>
<body>
  <img src="../../images/moodiarylogo.png" alt="Moodiary Logo" class="main-logo" style="display:block;margin:0 auto 20px auto;max-width:120px;" />
  <div class="screen auth-container">
    <div class="logo">
      <h1>Moodiary</h1>
      <p>Track moods, share vibes, heal together ❤️</p>
    </div>
    
    <div class="form-group">
      <label for="signup-email">Email *</label>
      <input type="email" id="signup-email" placeholder="Enter your email" required />
    </div>
    <div class="form-group">
      <label for="signup-username">Username *</label>
      <input type="text" id="signup-username" placeholder="Choose a username" required />
    </div>
    <div class="form-group">
      <label for="signup-password">Password *</label>
      <input type="password" id="signup-password" placeholder="Enter your password (min. 8 characters)" required />
    </div>
    <div class="form-group">
      <label for="signup-fullname">Full Name</label>
      <input type="text" id="signup-fullname" placeholder="Enter your full name (optional)" />
    </div>

    <button class="auth-button" id="signup-btn">Sign Up</button>

    <div class="auth-link">
      <span>Already have an account?</span>
      <a href="signin.php">Sign in</a>
    </div>
    
    <img src="../../images/Catchingup.png" class="bottom-left-img" alt="Moodiary Logo" />
    <img src="../../images/Loving.png" class="right-image" alt="Hugging character" />
  </div>

  <script>
    // Update signup functionality to redirect to home.php
    document.addEventListener('DOMContentLoaded', function() {
      const signupBtn = document.getElementById('signup-btn');
      if (signupBtn) {
        signupBtn.addEventListener('click', function() {
          const email = document.getElementById('signup-email').value;
          const username = document.getElementById('signup-username').value;
          const password = document.getElementById('signup-password').value;
          const fullname = document.getElementById('signup-fullname').value;
          
          // Validate inputs
          if (!email || !username || !password) {
            alert('Please fill in all required fields');
            return;
          }
          
          if (password.length < 8) {
            alert('Password must be at least 8 characters');
            return;
          }
          
          // Send signup request
          fetch('../Database&Backend/signup.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json'
            },
            body: JSON.stringify({
              email: email,
              username: username,
              password: password,
              full_name: fullname
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              // Redirect to home page
              window.location.href = 'home.php';
            } else {
              alert(data.message || 'Signup failed');
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