<?php
// Include the header
include_once('header.php');

// Check if user is a mentor
if (!$isMentor) {
    header("Location: home.php");
    exit();
}

// Set page title
$pageTitle = "My Mentees";
?>

<div class="main-app active">
  <div class="menu-button" id="menu-button">
    <span></span><span></span><span></span>
  </div>

  <div id="mentees-dashboard">
    <div class="header">
      <h1>My Mentees</h1>
      <p>View and manage your mentees</p>
    </div>

    <div class="mentees-container">
      <div class="mentees-sidebar">
        <div class="mentees-search">
          <input type="text" id="mentee-search" placeholder="Search mentees...">
        </div>
        
        <div class="mentees-filter">
          <h3>Filter</h3>
          <div class="filter-options">
            <label>
              <input type="checkbox" data-filter="needs-attention" checked> Needs Attention
            </label>
            <label>
              <input type="checkbox" data-filter="all" checked> All Mentees
            </label>
          </div>
        </div>
        
        <div class="mentees-list" id="mentees-list">
          <!-- Mentees will be loaded here -->
          <p>Loading mentees...</p>
        </div>
      </div>
      
      <div class="mentees-content">
        <div class="mentee-placeholder" id="mentee-placeholder">
          <h2>Select a mentee to view details</h2>
          <p>Click on a mentee from the list to view their profile, mood history, and add notes.</p>
        </div>
        
        <div class="mentee-details" id="mentee-details" style="display: none;">
          <!-- Mentee details will be loaded here -->
        </div>
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
<script src="sidebar.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
  // Initialize sidebar
  if (typeof initMenuButton === 'function') {
    initMenuButton();
  }
  
  // Load mentees
  loadMentees();
  
  // Search functionality
  document.getElementById('mentee-search').addEventListener('input', function() {
    filterMentees(this.value);
  });
  
  // Filter functionality
  document.querySelectorAll('.filter-options input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      applyFilters();
    });
  });
});

function loadMentees() {
  const menteesList = document.getElementById('mentees-list');
  
  // Fetch mentees from API
  fetch('../Database&Backend/mentor_api.php?action=get_mentees')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.mentees) {
        menteesList.innerHTML = '';
        
        if (data.mentees.length === 0) {
          menteesList.innerHTML = '<p>No mentees assigned to you yet.</p>';
          return;
        }
        
        data.mentees.forEach(mentee => {
          const menteeCard = document.createElement('div');
          menteeCard.className = 'mentee-card';
          menteeCard.dataset.menteeId = mentee.user_id;
          menteeCard.dataset.needsAttention = mentee.needs_attention ? 'true' : 'false';
          
          const initialLetter = mentee.full_name ? mentee.full_name.charAt(0) : mentee.username.charAt(0);
          
          menteeCard.innerHTML = `
            <div class="mentee-avatar">${initialLetter}</div>
            <div class="mentee-info">
              <h3>${mentee.full_name || mentee.username}</h3>
              <p>${mentee.recent_mood ? mentee.recent_mood : 'No recent mood'}</p>
            </div>
            ${mentee.needs_attention ? '<div class="attention-badge">Needs Attention</div>' : ''}
          `;
          
          menteeCard.addEventListener('click', function() {
            // Remove active class from all cards
            document.querySelectorAll('.mentee-card').forEach(card => {
              card.classList.remove('active');
            });
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Load mentee details
            loadMenteeDetails(mentee.user_id);
          });
          
          menteesList.appendChild(menteeCard);
        });
      } else {
        menteesList.innerHTML = '<p>Error loading mentees</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching mentees:', error);
      menteesList.innerHTML = '<p>Error loading mentees</p>';
    });
}

function loadMenteeDetails(menteeId) {
  const menteeDetails = document.getElementById('mentee-details');
  const menteeplaceholder = document.getElementById('mentee-placeholder');
  
  // Show loading
  menteeDetails.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
  menteeDetails.style.display = 'block';
  menteeplaceholder.style.display = 'none';
  
  // Fetch mentee details from API
  fetch(`../Database&Backend/mentor_api.php?action=get_mentee_details&mentee_id=${menteeId}`)
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.mentee) {
        const mentee = data.mentee;
        const initialLetter = mentee.full_name ? mentee.full_name.charAt(0) : mentee.username.charAt(0);
        
        menteeDetails.innerHTML = `
          <div class="mentee-header">
            <div class="mentee-avatar large">${initialLetter}</div>
            <div class="mentee-header-info">
              <h2>${mentee.full_name || mentee.username}</h2>
              <p>Member since: ${new Date(mentee.created_at).toLocaleDateString()}</p>
            </div>
          </div>
          
          <div class="mentee-stats">
            <div class="stat-card">
              <h3>Mood Entries</h3>
              <div class="stat-value">${mentee.mood_count || 0}</div>
            </div>
            <div class="stat-card">
              <h3>Last Active</h3>
              <div class="stat-value">${mentee.last_active ? new Date(mentee.last_active).toLocaleDateString() : 'Never'}</div>
            </div>
          </div>
          
          <div class="mentee-actions">
            <a href="message.html?user=${mentee.username}" class="btn-primary">Message</a>
            <button class="btn-primary" id="mark-reviewed">Mark as Reviewed</button>
          </div>
          
          <div class="mentee-notes">
            <h3>Mentor Notes</h3>
            <textarea id="mentee-notes-text">${mentee.notes || ''}</textarea>
            <button class="btn-primary" id="save-notes">Save Notes</button>
          </div>
          
          <div class="mentee-recent-activity">
            <h3>Recent Activity</h3>
            <div class="activity-list">
              ${mentee.recent_activity && mentee.recent_activity.length > 0 ? 
                mentee.recent_activity.map(activity => `
                  <div class="activity-item">
                    <div class="activity-date">${new Date(activity.created_at).toLocaleDateString()}</div>
                    <div class="activity-type">${activity.type}</div>
                    <div class="activity-description">${activity.description}</div>
                  </div>
                `).join('') : 
                '<p>No recent activity</p>'
              }
            </div>
          </div>
        `;
        
        // Add event listeners
        document.getElementById('save-notes').addEventListener('click', function() {
          saveNotes(menteeId, document.getElementById('mentee-notes-text').value);
        });
        
        document.getElementById('mark-reviewed').addEventListener('click', function() {
          markAsReviewed(menteeId);
        });
      } else {
        menteeDetails.innerHTML = '<p>Error loading mentee details</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching mentee details:', error);
      menteeDetails.innerHTML = '<p>Error loading mentee details</p>';
    });
}

function saveNotes(menteeId, notes) {
  fetch('../Database&Backend/mentor_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'save_notes',
      mentee_id: menteeId,
      notes: notes
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification('Notes saved successfully', 'success');
    } else {
      showNotification(data.message || 'Error saving notes', 'error');
    }
  })
  .catch(error => {
    console.error('Error saving notes:', error);
    showNotification('Error saving notes', 'error');
  });
}

function markAsReviewed(menteeId) {
  fetch('../Database&Backend/mentor_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'mark_reviewed',
      mentee_id: menteeId
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification('Mentee marked as reviewed', 'success');
      
      // Update UI
      document.querySelector(`.mentee-card[data-mentee-id="${menteeId}"] .attention-badge`)?.remove();
      document.querySelector(`.mentee-card[data-mentee-id="${menteeId}"]`).dataset.needsAttention = 'false';
      
      // Reapply filters
      applyFilters();
    } else {
      showNotification(data.message || 'Error updating status', 'error');
    }
  })
  .catch(error => {
    console.error('Error marking as reviewed:', error);
    showNotification('Error updating status', 'error');
  });
}

function filterMentees(searchTerm) {
  const menteeCards = document.querySelectorAll('.mentee-card');
  searchTerm = searchTerm.toLowerCase();
  
  menteeCards.forEach(card => {
    const menteeInfo = card.querySelector('.mentee-info').textContent.toLowerCase();
    
    if (menteeInfo.includes(searchTerm)) {
      card.classList.remove('hidden');
    } else {
      card.classList.add('hidden');
    }
  });
  
  // Reapply other filters
  applyFilters(false);
}

function applyFilters(resetSearch = true) {
  if (resetSearch) {
    document.getElementById('mentee-search').value = '';
  }
  
  const needsAttentionFilter = document.querySelector('input[data-filter="needs-attention"]').checked;
  const allFilter = document.querySelector('input[data-filter="all"]').checked;
  
  const menteeCards = document.querySelectorAll('.mentee-card:not(.hidden)');
  
  menteeCards.forEach(card => {
    const needsAttention = card.dataset.needsAttention === 'true';
    
    if ((needsAttentionFilter && needsAttention) || (allFilter)) {
      card.style.display = '';
    } else {
      card.style.display = 'none';
    }
  });
}

function showNotification(message, type) {
  // Check if notification container exists
  let notificationContainer = document.querySelector('.notification');
  
  if (!notificationContainer) {
    // Create notification container
    notificationContainer = document.createElement('div');
    notificationContainer.className = 'notification';
    document.body.appendChild(notificationContainer);
  }
  
  // Set notification content and type
  notificationContainer.textContent = message;
  notificationContainer.className = `notification ${type}`;
  
  // Show notification
  notificationContainer.classList.add('show');
  
  // Hide notification after 3 seconds
  setTimeout(() => {
    notificationContainer.classList.remove('show');
  }, 3000);
}
</script> 