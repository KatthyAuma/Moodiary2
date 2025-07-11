<?php
// Include the header
include_once('header.php');

// Set page title
$pageTitle = "Home";
?>

<div class="main-app active">
  <div class="menu-button" id="menu-button">
    <span></span><span></span><span></span>
  </div>

  <div id="home-screen">
    <div class="header">
      <h1>How are you feeling today?</h1>
      <p>Don't forget to record your journal entry!</p>
    </div>

    <div class="dashboard-container">
      <!-- Left Column -->
      <div class="dashboard-column">
        <!-- User Profile Summary -->
        <div class="profile-summary">
          <div class="profile-avatar" id="user-avatar"></div>
          <div class="profile-info">
            <h2 id="user-name"><?php echo htmlspecialchars($fullName ?: $username); ?></h2>
            <p id="user-streak"></p>
          </div>
          <div class="profile-mood" id="current-mood">
            <span class="mood-emoji"></span>
            <span class="mood-text"></span>
          </div>
        </div>
        
        <!-- Quick Journal Entry -->
        <div class="quick-journal">
          <h3>Quick Journal Entry</h3>
          <div class="mood-tags">
            <div class="mood-tag" data-mood-id="1" data-mood="Happy">
              <span class="mood-tag-emoji">😊</span>Happy
            </div>
            <div class="mood-tag" data-mood-id="2" data-mood="Sad">
              <span class="mood-tag-emoji">😢</span>Sad
            </div>
            <div class="mood-tag" data-mood-id="3" data-mood="Angry">
              <span class="mood-tag-emoji">😠</span>Angry
            </div>
            <div class="mood-tag" data-mood-id="4" data-mood="Anxious">
              <span class="mood-tag-emoji">😰</span>Anxious
            </div>
            <div class="mood-tag" data-mood-id="5" data-mood="Calm">
              <span class="mood-tag-emoji">😌</span>Calm
            </div>
            <div class="mood-tag" data-mood-id="6" data-mood="Excited">
              <span class="mood-tag-emoji">🤩</span>Excited
            </div>
            <div class="mood-tag" data-mood-id="7" data-mood="Tired">
              <span class="mood-tag-emoji">😴</span>Tired
            </div>
            <div class="mood-tag" data-mood-id="8" data-mood="Grateful">
              <span class="mood-tag-emoji">🙏</span>Grateful
            </div>
          </div>
          <textarea placeholder="How are you feeling today?" class="quick-journal-input"></textarea>
          <div class="quick-journal-actions">
            <label>
              <input type="checkbox" id="quick-journal-public"> 
              Share with friends
            </label>
            <button class="quick-journal-submit">Save</button>
          </div>
        </div>
        
        <!-- Mood Insights -->
        <div class="mood-insights">
          <h3>Your Mood Trends</h3>
          <div class="mood-chart">
            <div class="mood-chart-placeholder">
              <p>Track your moods to see trends over time</p>
              <a href="journal.php" class="btn-small">View All</a>
            </div>
          </div>
        </div>
      </div>
      
      <!-- Middle Column -->
      <div class="dashboard-column">
        <!-- Feed Section -->
        <div class="feed-section">
          <h2>Friend Activity</h2>
          <!-- Feed content will be loaded dynamically by JavaScript -->
        </div>
      </div>
      
      <!-- Right Column -->
      <div class="dashboard-column">
        <!-- Friends Section -->
        <div class="friends-section">
          <h2>Friends</h2>
          <div class="friends-search">
            <input type="text" placeholder="Search for friends..." id="friends-search-input" />
            <button id="friends-search-btn">Search</button>
          </div>
          
          <div class="section-tabs">
            <div class="section-tab active" data-tab="friends-list">My Friends</div>
            <div class="section-tab" data-tab="friend-requests">Requests <span class="badge" id="request-count">0</span></div>
            <div class="section-tab" data-tab="friend-search">Find Friends</div>
          </div>
          
          <div class="tab-content active" id="friends-list">
            <div class="friends-list">
              <!-- Friends list will be loaded dynamically by JavaScript -->
            </div>
          </div>
          
          <div class="tab-content" id="friend-requests">
            <!-- Friend requests will be loaded dynamically by JavaScript -->
          </div>
          
          <div class="tab-content" id="friend-search">
            <div class="friends-search">
              <input type="text" placeholder="Search by name or email..." id="find-friends-input" />
              <button id="find-friends-btn">Search</button>
            </div>
            <div class="search-results">
              <!-- Search results will be loaded dynamically by JavaScript -->
            </div>
          </div>
        </div>

        <!-- Recommendations Section -->
        <div class="recommendations">
          <h2>Recommended for You</h2>
          <!-- Recommendations will be loaded dynamically by JavaScript -->
        </div>
        
        <?php if ($isAdmin || $isMentor || $isCounsellor): ?>
        <!-- Role-specific features -->
        <div class="role-specific-features">
          <h2>Special Features</h2>
          
          <?php if ($isAdmin): ?>
          <div class="admin-quick-actions">
            <h3>Admin Actions</h3>
            <a href="admin.php" class="btn-primary">Manage Users</a>
            <a href="admin.php?section=stats" class="btn-primary">System Statistics</a>
            <a href="admin.php?section=settings" class="btn-primary">System Settings</a>
          </div>
          <?php endif; ?>
          
          <?php if ($isMentor): ?>
          <div class="mentor-quick-actions">
            <h3>Mentor Actions</h3>
            <a href="mentees.php" class="btn-primary">View Mentees</a>
            <div id="mentees-needing-attention">
              <p>Loading mentees data...</p>
            </div>
          </div>
          <?php endif; ?>
          
          <?php if ($isCounsellor): ?>
          <div class="counsellor-quick-actions">
            <h3>Counsellor Actions</h3>
            <a href="clients.php" class="btn-primary">View Clients</a>
            <div id="priority-clients">
              <p>Loading clients data...</p>
            </div>
            <div id="upcoming-sessions">
              <p>Loading sessions data...</p>
            </div>
          </div>
          <?php endif; ?>
        </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

<?php
// Include the footer
include_once('footer.php');
?>
<script>
  // Set global role variables for JavaScript
  const isAdmin = <?php echo $isAdmin ? 'true' : 'false'; ?>;
  const isMentor = <?php echo $isMentor ? 'true' : 'false'; ?>;
  const isCounsellor = <?php echo $isCounsellor ? 'true' : 'false'; ?>;
</script>
<script src="dashboard.js"></script>