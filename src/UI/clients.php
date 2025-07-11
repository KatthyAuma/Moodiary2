<?php
// Include the header
include_once('header.php');

// Check if user is a counsellor
if (!$isCounsellor) {
    header("Location: home.php");
    exit();
}

// Set page title
$pageTitle = "My Clients";
?>

<div class="main-app active">
  <div class="menu-button" id="menu-button">
    <span></span><span></span><span></span>
  </div>

  <div id="clients-dashboard">
    <div class="header">
      <h1>My Clients</h1>
      <p>View and manage your clients</p>
    </div>

    <div class="mentees-container">
      <div class="mentees-sidebar">
        <div class="mentees-search">
          <input type="text" id="client-search" placeholder="Search clients...">
        </div>
        
        <div class="mentees-filter">
          <h3>Filter</h3>
          <div class="filter-options">
            <label>
              <input type="checkbox" data-filter="priority" checked> Priority Cases
            </label>
            <label>
              <input type="checkbox" data-filter="upcoming" checked> Upcoming Sessions
            </label>
            <label>
              <input type="checkbox" data-filter="all" checked> All Clients
            </label>
          </div>
        </div>
        
        <div class="mentees-list" id="clients-list">
          <!-- Clients will be loaded here -->
          <p>Loading clients...</p>
        </div>
      </div>
      
      <div class="mentees-content">
        <div class="mentee-placeholder" id="client-placeholder">
          <h2>Select a client to view details</h2>
          <p>Click on a client from the list to view their profile, mood history, and session notes.</p>
        </div>
        
        <div class="mentee-details" id="client-details" style="display: none;">
          <!-- Client details will be loaded here -->
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
  
  // Load clients
  loadClients();
  
  // Search functionality
  document.getElementById('client-search').addEventListener('input', function() {
    filterClients(this.value);
  });
  
  // Filter functionality
  document.querySelectorAll('.filter-options input').forEach(checkbox => {
    checkbox.addEventListener('change', function() {
      applyFilters();
    });
  });
});

function loadClients() {
  const clientsList = document.getElementById('clients-list');
  
  // Fetch clients from API
  fetch('../Database&Backend/counsellor_api.php?action=get_clients')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.clients) {
        clientsList.innerHTML = '';
        
        if (data.clients.length === 0) {
          clientsList.innerHTML = '<p>No clients assigned to you yet.</p>';
          return;
        }
        
        data.clients.forEach(client => {
          const clientCard = document.createElement('div');
          clientCard.className = 'mentee-card';
          clientCard.dataset.clientId = client.user_id;
          clientCard.dataset.priority = client.priority ? 'true' : 'false';
          clientCard.dataset.upcomingSession = client.upcoming_session ? 'true' : 'false';
          
          const initialLetter = client.full_name ? client.full_name.charAt(0) : client.username.charAt(0);
          
          clientCard.innerHTML = `
            <div class="mentee-avatar">${initialLetter}</div>
            <div class="mentee-info">
              <h3>${client.full_name || client.username}</h3>
              <p>${client.recent_mood ? client.recent_mood : 'No recent mood'}</p>
            </div>
            ${client.priority ? '<div class="priority-badge">Priority</div>' : ''}
            ${client.upcoming_session ? '<div class="session-badge">Session Today</div>' : ''}
          `;
          
          clientCard.addEventListener('click', function() {
            // Remove active class from all cards
            document.querySelectorAll('.mentee-card').forEach(card => {
              card.classList.remove('active');
            });
            
            // Add active class to clicked card
            this.classList.add('active');
            
            // Load client details
            loadClientDetails(client.user_id);
          });
          
          clientsList.appendChild(clientCard);
        });
      } else {
        clientsList.innerHTML = '<p>Error loading clients</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching clients:', error);
      clientsList.innerHTML = '<p>Error loading clients</p>';
    });
}

function loadClientDetails(clientId) {
  const clientDetails = document.getElementById('client-details');
  const clientPlaceholder = document.getElementById('client-placeholder');
  
  // Show loading
  clientDetails.innerHTML = '<div class="loading-spinner"><div class="spinner"></div></div>';
  clientDetails.style.display = 'block';
  clientPlaceholder.style.display = 'none';
  
  // Fetch client details from API
  fetch(`../Database&Backend/counsellor_api.php?action=get_client_details&client_id=${clientId}`)
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.client) {
        const client = data.client;
        const initialLetter = client.full_name ? client.full_name.charAt(0) : client.username.charAt(0);
        
        clientDetails.innerHTML = `
          <div class="mentee-header">
            <div class="mentee-avatar large">${initialLetter}</div>
            <div class="mentee-header-info">
              <h2>${client.full_name || client.username}</h2>
              <p>Member since: ${new Date(client.created_at).toLocaleDateString()}</p>
            </div>
          </div>
          
          <div class="mentee-stats">
            <div class="stat-card">
              <h3>Mood Entries</h3>
              <div class="stat-value">${client.mood_count || 0}</div>
            </div>
            <div class="stat-card">
              <h3>Last Active</h3>
              <div class="stat-value">${client.last_active ? new Date(client.last_active).toLocaleDateString() : 'Never'}</div>
            </div>
            <div class="stat-card">
              <h3>Sessions</h3>
              <div class="stat-value">${client.session_count || 0}</div>
            </div>
          </div>
          
          <div class="mentee-actions">
            <a href="message.html?user=${client.username}" class="btn-primary">Message</a>
            <button class="btn-primary" id="schedule-session">Schedule Session</button>
            <button class="btn-primary" id="update-priority">
              ${client.priority ? 'Remove Priority' : 'Mark as Priority'}
            </button>
          </div>
          
          <div class="mentee-notes">
            <h3>Session Notes</h3>
            <textarea id="client-notes-text">${client.notes || ''}</textarea>
            <button class="btn-primary" id="save-notes">Save Notes</button>
          </div>
          
          <div class="mentee-recent-activity">
            <h3>Mood History</h3>
            <div class="activity-list">
              ${client.mood_history && client.mood_history.length > 0 ? 
                client.mood_history.map(mood => `
                  <div class="activity-item">
                    <div class="activity-date">${new Date(mood.created_at).toLocaleDateString()}</div>
                    <div class="activity-type">${mood.mood_name}</div>
                    <div class="activity-description">${mood.content || 'No description'}</div>
                  </div>
                `).join('') : 
                '<p>No mood history available</p>'
              }
            </div>
          </div>
          
          <div class="mentee-recent-activity">
            <h3>Upcoming Sessions</h3>
            <div class="activity-list">
              ${client.upcoming_sessions && client.upcoming_sessions.length > 0 ? 
                client.upcoming_sessions.map(session => `
                  <div class="activity-item">
                    <div class="activity-date">${new Date(session.session_date).toLocaleString()}</div>
                    <div class="activity-type">${session.session_type}</div>
                    <div class="activity-description">${session.notes || 'No notes'}</div>
                  </div>
                `).join('') : 
                '<p>No upcoming sessions</p>'
              }
            </div>
          </div>
        `;
        
        // Add event listeners
        document.getElementById('save-notes').addEventListener('click', function() {
          saveNotes(clientId, document.getElementById('client-notes-text').value);
        });
        
        document.getElementById('update-priority').addEventListener('click', function() {
          updatePriority(clientId, !client.priority);
        });
        
        document.getElementById('schedule-session').addEventListener('click', function() {
          scheduleSession(clientId);
        });
      } else {
        clientDetails.innerHTML = '<p>Error loading client details</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching client details:', error);
      clientDetails.innerHTML = '<p>Error loading client details</p>';
    });
}

function saveNotes(clientId, notes) {
  fetch('../Database&Backend/counsellor_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'save_notes',
      client_id: clientId,
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

function updatePriority(clientId, isPriority) {
  fetch('../Database&Backend/counsellor_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'update_priority',
      client_id: clientId,
      priority: isPriority
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification(`Client ${isPriority ? 'marked as priority' : 'removed from priority'}`, 'success');
      
      // Update UI
      const clientCard = document.querySelector(`.mentee-card[data-client-id="${clientId}"]`);
      if (clientCard) {
        clientCard.dataset.priority = isPriority ? 'true' : 'false';
        
        // Update badge
        const existingBadge = clientCard.querySelector('.priority-badge');
        if (isPriority && !existingBadge) {
          const badge = document.createElement('div');
          badge.className = 'priority-badge';
          badge.textContent = 'Priority';
          clientCard.appendChild(badge);
        } else if (!isPriority && existingBadge) {
          existingBadge.remove();
        }
        
        // Update button text
        document.getElementById('update-priority').textContent = isPriority ? 'Remove Priority' : 'Mark as Priority';
      }
      
      // Reapply filters
      applyFilters();
    } else {
      showNotification(data.message || 'Error updating priority status', 'error');
    }
  })
  .catch(error => {
    console.error('Error updating priority:', error);
    showNotification('Error updating priority status', 'error');
  });
}

function scheduleSession(clientId) {
  // In a real app, this would open a modal with a form
  const sessionDate = prompt('Enter session date and time (YYYY-MM-DD HH:MM):');
  if (!sessionDate) return;
  
  const sessionType = prompt('Enter session type (e.g., Initial Consultation, Follow-up):');
  if (!sessionType) return;
  
  fetch('../Database&Backend/counsellor_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      action: 'schedule_session',
      client_id: clientId,
      session_date: sessionDate,
      session_type: sessionType
    })
  })
  .then(response => response.json())
  .then(data => {
    if (data.status === 'success') {
      showNotification('Session scheduled successfully', 'success');
      
      // Reload client details to show the new session
      loadClientDetails(clientId);
    } else {
      showNotification(data.message || 'Error scheduling session', 'error');
    }
  })
  .catch(error => {
    console.error('Error scheduling session:', error);
    showNotification('Error scheduling session', 'error');
  });
}

function filterClients(searchTerm) {
  const clientCards = document.querySelectorAll('.mentee-card');
  searchTerm = searchTerm.toLowerCase();
  
  clientCards.forEach(card => {
    const clientInfo = card.querySelector('.mentee-info').textContent.toLowerCase();
    
    if (clientInfo.includes(searchTerm)) {
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
    document.getElementById('client-search').value = '';
  }
  
  const priorityFilter = document.querySelector('input[data-filter="priority"]').checked;
  const upcomingFilter = document.querySelector('input[data-filter="upcoming"]').checked;
  const allFilter = document.querySelector('input[data-filter="all"]').checked;
  
  const clientCards = document.querySelectorAll('.mentee-card:not(.hidden)');
  
  clientCards.forEach(card => {
    const isPriority = card.dataset.priority === 'true';
    const hasUpcomingSession = card.dataset.upcomingSession === 'true';
    
    if ((priorityFilter && isPriority) || 
        (upcomingFilter && hasUpcomingSession) || 
        (allFilter)) {
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