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
    <div class="sidebar-item">
      <a href="settings.php">Settings</a>
    </div>
    <div class="sidebar-item" id="logout-btn">Logout</div>
  </div>

  <div class="sidebar-overlay" id="sidebar-overlay"></div>

  <script src="sidebar.js"></script>
  <script src="dashboard.js"></script>
</body>
</html> 