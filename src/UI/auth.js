document.addEventListener("DOMContentLoaded", () => {
  // Show loading spinner
  function showLoading() {
    const loadingEl = document.createElement('div');
    loadingEl.className = 'loading-spinner';
    loadingEl.innerHTML = '<div class="spinner"></div>';
    document.body.appendChild(loadingEl);
  }

  // Hide loading spinner
  function hideLoading() {
    const loadingEl = document.querySelector('.loading-spinner');
    if (loadingEl) {
      loadingEl.remove();
    }
  }

  // Show notification
  function showNotification(message, type = 'error') {
    const notificationEl = document.createElement('div');
    notificationEl.className = `notification ${type}`;
    notificationEl.textContent = message;
    document.body.appendChild(notificationEl);
    
    setTimeout(() => {
      notificationEl.classList.add('show');
      
      setTimeout(() => {
        notificationEl.classList.remove('show');
        setTimeout(() => notificationEl.remove(), 300);
      }, 3000);
    }, 10);
  }

  // Signup
  const signupBtn = document.getElementById("signup-btn");
  if (signupBtn) {
    signupBtn.addEventListener("click", async () => {
      const email = document.getElementById("signup-email").value;
      const password = document.getElementById("signup-password").value;
      const username = document.getElementById("signup-username")?.value || email.split('@')[0];
      const fullName = document.getElementById("signup-fullname")?.value || '';

      if (!email || !password) {
        showNotification("Please fill in all required fields.");
        return;
      }

      try {
        showLoading();
        
        const response = await fetch('../Database&Backend/signup.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            email,
            password,
            username,
            full_name: fullName
          })
        });

        let data;
        try {
          data = await response.json();
        } catch (jsonError) {
          console.error('JSON parsing error:', jsonError);
          showNotification('Server returned invalid response. Please try again.');
          hideLoading();
          return;
        }
        
        if (data.status === 'success') {
          showNotification(data.message, 'success');
          setTimeout(() => {
            window.location.href = data.redirect || 'signin.php';
          }, 1500);
        } else {
          showNotification(data.message || 'Registration failed. Please try again.');
        }
      } catch (error) {
        console.error('Signup error:', error);
        showNotification('An error occurred during signup. Please try again.');
      } finally {
        hideLoading();
      }
    });
  }

  // Signin
  const signinBtn = document.getElementById("signin-btn");
  if (signinBtn) {
    signinBtn.addEventListener("click", async () => {
      const email = document.getElementById("signin-email").value;
      const password = document.getElementById("signin-password").value;

      if (!email || !password) {
        showNotification("Please enter both email and password.");
        return;
      }

      try {
        showLoading();
        
        const response = await fetch('../Database&Backend/login.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
          },
          body: JSON.stringify({
            email,
            password
          })
        });

        let data;
        try {
          data = await response.json();
        } catch (jsonError) {
          console.error('JSON parsing error:', jsonError);
          showNotification('Server returned invalid response. Please try again.');
          hideLoading();
          return;
        }
        
        if (data.status === 'success') {
          showNotification(data.message, 'success');
          setTimeout(() => {
            window.location.href = data.redirect || 'home.php';
          }, 1000);
        } else {
          showNotification(data.message || 'Login failed. Please try again.');
        }
      } catch (error) {
        console.error('Login error:', error);
        showNotification('An error occurred during login. Please try again.');
      } finally {
        hideLoading();
      }
    });
  }

  // Check session status
  async function checkSession() {
    try {
      const response = await fetch('../Database&Backend/check_session.php', {
        method: 'GET',
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });
      
      const data = await response.json();
      return data.logged_in === true;
    } catch (error) {
      console.error('Session check error:', error);
      return false;
    }
  }

  // Protect Pages
  const protectedPages = ["home", "journal", "message", "admin", "mentees", "clients"];
  const currentPage = window.location.pathname.split('/').pop().split('.')[0]; // Remove file extension
  
  if (protectedPages.some(page => currentPage.includes(page))) {
    (async () => {
      const isLoggedIn = await checkSession();
      if (!isLoggedIn) {
        window.location.href = "signin.php";
      }
    })();
  }

  // Logout
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", async () => {
      try {
        showLoading();
        
        const response = await fetch('../Database&Backend/logout.php', {
          method: 'POST',
          headers: {
            'X-Requested-With': 'XMLHttpRequest'
          }
        });
        
        const data = await response.json();
        
        if (data.status === 'success') {
          showNotification(data.message, 'success');
          setTimeout(() => {
            window.location.href = data.redirect || 'signin.php';
          }, 1000);
        } else {
          showNotification(data.message);
        }
      } catch (error) {
        console.error('Logout error:', error);
        showNotification('An error occurred during logout. Please try again.');
        window.location.href = 'signin.html';
      } finally {
        hideLoading();
      }
    });
  }
});
