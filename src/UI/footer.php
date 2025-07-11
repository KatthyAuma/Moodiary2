  <div class="sidebar" id="sidebar">
    <div class="sidebar-item">
      <a href="home.php">Home</a>
    </div>
    <div class="sidebar-item">
      <a href="message.php">Friends</a>
    </div>
    <div class="sidebar-item">
      <a href="journal.php">Journal</a>
    </div>
    <?php if ($isAdmin): ?>
    <div class="sidebar-item">
      <a href="admin.php">Admin Dashboard</a>
    </div>
    <?php endif; ?>
    <?php if ($isMentor): ?>
    <div class="sidebar-item">
      <a href="mentees.php">My Mentees</a>
    </div>
    <?php endif; ?>
    <?php if ($isCounsellor): ?>
    <div class="sidebar-item">
      <a href="clients.php">My Clients</a>
    </div>
    <?php endif; ?>
    <?php if (basename($_SERVER['PHP_SELF']) !== 'home.php'): ?>
    <div class="sidebar-item">
      <a href="settings.php">Settings</a>
    </div>
    <?php endif; ?>
    <div class="sidebar-item" id="logout-btn">Logout</div>
  </div>

  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <script src="sidebar.js"></script>
  <script src="dashboard.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      var logoutBtn = document.getElementById('logout-btn');
      if (logoutBtn) {
        logoutBtn.addEventListener('click', function() {
          fetch('../Database&Backend/logout.php', {
            method: 'POST',
            headers: {
              'X-Requested-With': 'XMLHttpRequest'
            }
          })
          .then(response => response.json())
          .then(data => {
            if (data.status === 'success') {
              window.location.href = 'signin.php';
            }
          })
          .catch(error => {
            console.error('Logout error:', error);
            window.location.href = 'signin.php';
          });
        });
      }
    });
  </script>
</body>
</html> 