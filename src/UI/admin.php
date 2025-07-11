<?php
// Include the header
include_once('header.php');

// Debug session
echo "<!-- Debug Session: ";
echo "isLoggedIn: " . ($isLoggedIn ? "true" : "false") . ", ";
echo "isAdmin: " . ($isAdmin ? "true" : "false") . ", ";
echo "User Roles: " . (isset($_SESSION['roles']) ? implode(", ", $_SESSION['roles']) : "none") . " -->";

// Check if user is admin
if (!$isAdmin) {
    header("Location: home.php");
    exit();
}

// Set page title
$pageTitle = "Admin Dashboard";
?>

<div class="main-app active">
  <div class="menu-button" id="menu-button">
    <span></span><span></span><span></span>
  </div>

  <div id="admin-dashboard">
    <div class="header">
      <h1>Admin Dashboard</h1>
      <p>Manage users and system settings</p>
    </div>

    <div class="admin-container">
      <div class="admin-sidebar">
        <div class="admin-menu-item active" data-section="users">
          <span class="admin-menu-icon">👥</span>
          <span class="admin-menu-text">Users</span>
        </div>
        <div class="admin-menu-item" data-section="reports">
          <span class="admin-menu-icon">📊</span>
          <span class="admin-menu-text">Reports</span>
        </div>
        <div class="admin-menu-item" data-section="settings">
          <span class="admin-menu-icon">⚙️</span>
          <span class="admin-menu-text">Settings</span>
        </div>
      </div>

      <div class="admin-content">
        <!-- Users Section -->
        <div class="admin-section active" id="users-section">
          <h2>User Management</h2>
          
          <div class="admin-search">
            <input type="text" id="user-search" placeholder="Search users by name, email or username">
            <button id="search-btn">Search</button>
          </div>
          
          <div class="admin-table-container">
            <table class="admin-table" id="users-table">
              <thead>
                <tr>
                  <th>ID</th>
                  <th>Username</th>
                  <th>Full Name</th>
                  <th>Email</th>
                  <th>Roles</th>
                  <th>Last Login</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody id="users-table-body">
                <!-- User data will be loaded here -->
                <tr>
                  <td colspan="8">Loading users...</td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Reports Section -->
        <div class="admin-section" id="reports-section">
          <h2>Reports & Analytics</h2>
          <div class="admin-stats">
            <div class="stat-card">
              <h3>Total Users</h3>
              <div class="stat-value" id="total-users">--</div>
            </div>
            <div class="stat-card">
              <h3>Active Today</h3>
              <div class="stat-value" id="active-users">--</div>
            </div>
            <div class="stat-card">
              <h3>Journal Entries</h3>
              <div class="stat-value" id="total-entries">--</div>
            </div>
            <div class="stat-card">
              <h3>New Users (7d)</h3>
              <div class="stat-value" id="new-users">--</div>
            </div>
          </div>
          
          <!-- Charts removed for MVP -->
        </div>
        
        <!-- Settings Section -->
        <div class="admin-section" id="settings-section">
          <h2>System Settings</h2>
          <form id="settings-form">
            <div class="settings-group">
              <h3>General Settings</h3>
              <div class="form-group">
                <label for="site-name">Site Name</label>
                <input type="text" id="site-name" value="Moodiary">
              </div>
              <div class="form-group">
                <label for="site-description">Site Description</label>
                <input type="text" id="site-description" value="Track moods, share vibes, heal together">
              </div>
            </div>
            
            <div class="settings-group">
              <h3>User Settings</h3>
              <div class="form-group">
                <label>
                  <input type="checkbox" id="allow-signups" checked>
                  Allow new user registrations
                </label>
              </div>
              <div class="form-group">
                <label>
                  <input type="checkbox" id="require-approval">
                  Require admin approval for new accounts
                </label>
              </div>
            </div>
            
            <button type="submit" class="btn-primary">Save Settings</button>
          </form>
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
    // Tab switching functionality
    const menuItems = document.querySelectorAll('.admin-menu-item');
    menuItems.forEach(item => {
      item.addEventListener('click', function() {
        // Remove active class from all menu items
        menuItems.forEach(i => i.classList.remove('active'));
        // Add active class to clicked item
        this.classList.add('active');
        
        // Hide all sections
        document.querySelectorAll('.admin-section').forEach(section => {
          section.classList.remove('active');
        });
        
        // Show corresponding section
        const sectionId = this.getAttribute('data-section') + '-section';
        document.getElementById(sectionId).classList.add('active');
      });
    });
    
    // Load users data
    loadUsers();
    
    // Load stats
    loadStats();
    
    // Search functionality
    document.getElementById('search-btn').addEventListener('click', function() {
      const searchTerm = document.getElementById('user-search').value;
      loadUsers(searchTerm);
    });
    
    // Settings form submission
    document.getElementById('settings-form').addEventListener('submit', function(e) {
      e.preventDefault();
      alert('Settings saved successfully!');
    });
  });
  
  // Function to load users
  function loadUsers(searchTerm = '') {
    const tableBody = document.getElementById('users-table-body');
    tableBody.innerHTML = '<tr><td colspan="8">Loading users...</td></tr>';
    
    // Fetch users from API
    fetch(`../Database&Backend/admin_api.php?action=get_users&search=${encodeURIComponent(searchTerm)}`)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success' && data.users) {
          tableBody.innerHTML = '';
          
          if (data.users.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="8">No users found</td></tr>';
            return;
          }
          
          data.users.forEach(user => {
            const row = document.createElement('tr');
            
            // Format roles
            const roles = user.roles ? user.roles.split(',').join(', ') : 'user';
            
            // Format last login
            const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString() : 'Never';
            
            // Create row
            row.innerHTML = `
              <td>${user.user_id}</td>
              <td>${user.username}</td>
              <td>${user.full_name || '-'}</td>
              <td>${user.email}</td>
              <td>${roles}</td>
              <td>${lastLogin}</td>
              <td>
                <button class="btn-edit" data-id="${user.user_id}">Edit</button>
                <button class="btn-delete" data-id="${user.user_id}">Delete</button>
              </td>
            `;
            
            tableBody.appendChild(row);
          });
          
          // Add event listeners for buttons
          document.querySelectorAll('.btn-edit').forEach(btn => {
            btn.addEventListener('click', function() {
              const userId = this.getAttribute('data-id');
              editUser(userId);
            });
          });
          
          document.querySelectorAll('.btn-delete').forEach(btn => {
            btn.addEventListener('click', function() {
              const userId = this.getAttribute('data-id');
              deleteUser(userId);
            });
          });
        } else {
          tableBody.innerHTML = '<tr><td colspan="8">Error loading users</td></tr>';
        }
      })
      .catch(error => {
        console.error('Error fetching users:', error);
        tableBody.innerHTML = '<tr><td colspan="8">Error loading users</td></tr>';
      });
  }
  
  // Function to edit user
  function editUser(userId) {
    // Fetch user details first
    fetch(`../Database&Backend/admin_api.php?action=get_user&user_id=${userId}`)
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success' && data.user) {
          const user = data.user;
          
          // Create modal for editing
          const modal = document.createElement('div');
          modal.className = 'admin-modal';
          modal.innerHTML = `
            <div class="admin-modal-content">
              <span class="close-modal">&times;</span>
              <h2>Edit User</h2>
              <form id="edit-user-form">
                <div class="form-group">
                  <label for="edit-username">Username</label>
                  <input type="text" id="edit-username" value="${user.username}" readonly>
                </div>
                <div class="form-group">
                  <label for="edit-fullname">Full Name</label>
                  <input type="text" id="edit-fullname" value="${user.full_name || ''}">
                </div>
                <div class="form-group">
                  <label for="edit-email">Email</label>
                  <input type="email" id="edit-email" value="${user.email}">
                </div>
                <div class="form-group">
                  <label>Roles</label>
                  <div class="checkbox-group">
                    <label>
                      <input type="checkbox" name="roles" value="admin" ${user.roles && user.roles.includes('admin') ? 'checked' : ''}>
                      Admin
                    </label>
                    <label>
                      <input type="checkbox" name="roles" value="user" ${user.roles && user.roles.includes('user') ? 'checked' : ''}>
                      User
                    </label>
                    <label>
                      <input type="checkbox" name="roles" value="mentor" id="mentor-role" ${user.roles && user.roles.includes('mentor') ? 'checked' : ''}>
                      Mentor
                    </label>
                    <label>
                      <input type="checkbox" name="roles" value="counsellor" id="counsellor-role" ${user.roles && user.roles.includes('counsellor') ? 'checked' : ''}>
                      Counsellor
                    </label>
                  </div>
                </div>
                
                <!-- Mentees section, shown only when mentor role is selected -->
                <div class="form-group" id="mentees-section" style="display: ${user.roles && user.roles.includes('mentor') ? 'block' : 'none'}">
                  <label>Assign Mentees</label>
                  <div class="user-search-container">
                    <input type="text" id="mentee-search" placeholder="Search users to add as mentees">
                    <button type="button" id="search-mentees-btn">Search</button>
                  </div>
                  <div class="search-results" id="mentee-search-results"></div>
                  <div class="selected-users" id="selected-mentees">
                    <h4>Selected Mentees</h4>
                    <ul id="mentee-list"></ul>
                  </div>
                </div>
                
                <!-- Clients section, shown only when counsellor role is selected -->
                <div class="form-group" id="clients-section" style="display: ${user.roles && user.roles.includes('counsellor') ? 'block' : 'none'}">
                  <label>Assign Clients</label>
                  <div class="user-search-container">
                    <input type="text" id="client-search" placeholder="Search users to add as clients">
                    <button type="button" id="search-clients-btn">Search</button>
                  </div>
                  <div class="search-results" id="client-search-results"></div>
                  <div class="selected-users" id="selected-clients">
                    <h4>Selected Clients</h4>
                    <ul id="client-list"></ul>
                  </div>
                </div>
                
                <div class="form-group">
                  <button type="submit" class="btn-primary">Save Changes</button>
                  <button type="button" class="btn-secondary close-btn">Cancel</button>
                </div>
              </form>
            </div>
          `;
          
          document.body.appendChild(modal);
          
          // Toggle mentees/clients sections when roles are checked/unchecked
          const mentorCheckbox = document.getElementById('mentor-role');
          const counsellorCheckbox = document.getElementById('counsellor-role');
          const menteesSection = document.getElementById('mentees-section');
          const clientsSection = document.getElementById('clients-section');
          
          mentorCheckbox.addEventListener('change', function() {
            menteesSection.style.display = this.checked ? 'block' : 'none';
          });
          
          counsellorCheckbox.addEventListener('change', function() {
            clientsSection.style.display = this.checked ? 'block' : 'none';
          });
          
          // Selected users arrays
          let selectedMentees = [];
          let selectedClients = [];
          
          // Load existing mentees if user is a mentor
          if (user.roles && user.roles.includes('mentor')) {
            fetch(`../Database&Backend/admin_api.php?action=get_mentees&mentor_id=${userId}`)
              .then(response => response.json())
              .then(data => {
                if (data.status === 'success' && data.mentees) {
                  selectedMentees = data.mentees;
                  updateMenteesList();
                }
              })
              .catch(error => console.error('Error loading mentees:', error));
          }
          
          // Load existing clients if user is a counsellor
          if (user.roles && user.roles.includes('counsellor')) {
            fetch(`../Database&Backend/admin_api.php?action=get_clients&counsellor_id=${userId}`)
              .then(response => response.json())
              .then(data => {
                if (data.status === 'success' && data.clients) {
                  selectedClients = data.clients;
                  updateClientsList();
                }
              })
              .catch(error => console.error('Error loading clients:', error));
          }
          
          // Search for mentees
          document.getElementById('search-mentees-btn').addEventListener('click', function() {
            const searchTerm = document.getElementById('mentee-search').value;
            if (searchTerm.trim() === '') return;
            
            fetch(`../Database&Backend/admin_api.php?action=search_users&search=${encodeURIComponent(searchTerm)}`)
              .then(response => response.json())
              .then(data => {
                if (data.status === 'success' && data.users) {
                  const resultsDiv = document.getElementById('mentee-search-results');
                  resultsDiv.innerHTML = '';
                  
                  if (data.users.length === 0) {
                    resultsDiv.innerHTML = '<p>No users found</p>';
                    return;
                  }
                  
                  const ul = document.createElement('ul');
                  data.users.forEach(user => {
                    // Skip if user is already selected or is the current user being edited
                    if (selectedMentees.some(m => m.user_id === user.user_id) || user.user_id === userId) return;
                    
                    const li = document.createElement('li');
                    li.innerHTML = `
                      <span>${user.username} (${user.email})</span>
                      <button type="button" class="btn-add-mentee" data-id="${user.user_id}" data-name="${user.username}">Add</button>
                    `;
                    ul.appendChild(li);
                  });
                  
                  resultsDiv.appendChild(ul);
                  
                  // Add event listeners for add buttons
                  document.querySelectorAll('.btn-add-mentee').forEach(btn => {
                    btn.addEventListener('click', function() {
                      const menteeId = this.getAttribute('data-id');
                      const menteeName = this.getAttribute('data-name');
                      
                      selectedMentees.push({
                        user_id: menteeId,
                        username: menteeName
                      });
                      
                      updateMenteesList();
                      this.closest('li').remove();
                    });
                  });
                }
              })
              .catch(error => console.error('Error searching users:', error));
          });
          
          // Search for clients
          document.getElementById('search-clients-btn').addEventListener('click', function() {
            const searchTerm = document.getElementById('client-search').value;
            if (searchTerm.trim() === '') return;
            
            fetch(`../Database&Backend/admin_api.php?action=search_users&search=${encodeURIComponent(searchTerm)}`)
              .then(response => response.json())
              .then(data => {
                if (data.status === 'success' && data.users) {
                  const resultsDiv = document.getElementById('client-search-results');
                  resultsDiv.innerHTML = '';
                  
                  if (data.users.length === 0) {
                    resultsDiv.innerHTML = '<p>No users found</p>';
                    return;
                  }
                  
                  const ul = document.createElement('ul');
                  data.users.forEach(user => {
                    // Skip if user is already selected or is the current user being edited
                    if (selectedClients.some(c => c.user_id === user.user_id) || user.user_id === userId) return;
                    
                    const li = document.createElement('li');
                    li.innerHTML = `
                      <span>${user.username} (${user.email})</span>
                      <button type="button" class="btn-add-client" data-id="${user.user_id}" data-name="${user.username}">Add</button>
                    `;
                    ul.appendChild(li);
                  });
                  
                  resultsDiv.appendChild(ul);
                  
                  // Add event listeners for add buttons
                  document.querySelectorAll('.btn-add-client').forEach(btn => {
                    btn.addEventListener('click', function() {
                      const clientId = this.getAttribute('data-id');
                      const clientName = this.getAttribute('data-name');
                      
                      selectedClients.push({
                        user_id: clientId,
                        username: clientName
                      });
                      
                      updateClientsList();
                      this.closest('li').remove();
                    });
                  });
                }
              })
              .catch(error => console.error('Error searching users:', error));
          });
          
          // Update mentees list
          function updateMenteesList() {
            const menteeList = document.getElementById('mentee-list');
            menteeList.innerHTML = '';
            
            selectedMentees.forEach(mentee => {
              const li = document.createElement('li');
              li.innerHTML = `
                <span>${mentee.username}</span>
                <button type="button" class="btn-remove-mentee" data-id="${mentee.user_id}">Remove</button>
              `;
              menteeList.appendChild(li);
            });
            
            // Add event listeners for remove buttons
            document.querySelectorAll('.btn-remove-mentee').forEach(btn => {
              btn.addEventListener('click', function() {
                const menteeId = this.getAttribute('data-id');
                selectedMentees = selectedMentees.filter(m => m.user_id !== menteeId);
                updateMenteesList();
              });
            });
          }
          
          // Update clients list
          function updateClientsList() {
            const clientList = document.getElementById('client-list');
            clientList.innerHTML = '';
            
            selectedClients.forEach(client => {
              const li = document.createElement('li');
              li.innerHTML = `
                <span>${client.username}</span>
                <button type="button" class="btn-remove-client" data-id="${client.user_id}">Remove</button>
              `;
              clientList.appendChild(li);
            });
            
            // Add event listeners for remove buttons
            document.querySelectorAll('.btn-remove-client').forEach(btn => {
              btn.addEventListener('click', function() {
                const clientId = this.getAttribute('data-id');
                selectedClients = selectedClients.filter(c => c.user_id !== clientId);
                updateClientsList();
              });
            });
          }
          
          // Close modal on X click
          modal.querySelector('.close-modal').addEventListener('click', () => {
            document.body.removeChild(modal);
          });
          
          // Close modal on Cancel click
          modal.querySelector('.close-btn').addEventListener('click', () => {
            document.body.removeChild(modal);
          });
          
          // Handle form submission
          modal.querySelector('#edit-user-form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Get form values
            const fullName = document.getElementById('edit-fullname').value;
            const email = document.getElementById('edit-email').value;
            
            // Get selected roles
            const roleCheckboxes = document.querySelectorAll('input[name="roles"]:checked');
            const roles = Array.from(roleCheckboxes).map(cb => cb.value);
            
            // Ensure at least one role is selected
            if (roles.length === 0) {
              alert('User must have at least one role');
              return;
            }
            
            // Get mentee and client IDs
            const menteeIds = selectedMentees.map(m => m.user_id);
            const clientIds = selectedClients.map(c => c.user_id);
            
            // Update user data
            const userData = {
              action: 'update_user',
              user_id: userId,
              full_name: fullName,
              email: email
            };
            
            // Update user basic info
            fetch('../Database&Backend/admin_api.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json'
              },
              body: JSON.stringify(userData)
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                // Update user roles
                return fetch('../Database&Backend/admin_api.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/json'
                  },
                  body: JSON.stringify({
                    action: 'update_user_roles',
                    user_id: userId,
                    roles: roles
                  })
                });
              } else {
                throw new Error(data.message || 'Error updating user data');
              }
            })
            .then(response => response.json())
            .then(data => {
              if (data.status === 'success') {
                // If user is a mentor, update mentees
                if (roles.includes('mentor')) {
                  return fetch('../Database&Backend/admin_api.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                      action: 'assign_mentees',
                      mentor_id: userId,
                      mentee_ids: menteeIds
                    })
                  });
                }
                return { status: 'success' };
              } else {
                throw new Error(data.message || 'Error updating user roles');
              }
            })
            .then(response => {
              if (response.status === 'success') {
                // If user is a counsellor, update clients
                if (roles.includes('counsellor')) {
                  return fetch('../Database&Backend/admin_api.php', {
                    method: 'POST',
                    headers: {
                      'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                      action: 'assign_clients',
                      counsellor_id: userId,
                      client_ids: clientIds
                    })
                  });
                }
                return { status: 'success' };
              } else {
                return response.json();
              }
            })
            .then(response => {
              if (response.status === 'success') {
                alert('User updated successfully');
                document.body.removeChild(modal);
                loadUsers(); // Reload the table
              } else {
                throw new Error(response.message || 'Error updating mentees/clients');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              alert(error.message || 'An error occurred. Please try again.');
            });
          });
        } else {
          alert(data.message || 'Error fetching user details');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
      });
  }
  
  // Function to delete user
  function deleteUser(userId) {
    // Confirm before deleting
    if (!confirm('Are you sure you want to delete this user? This will remove ALL data associated with this user and cannot be undone.')) {
      return;
    }
    
    // Send request to API
    fetch('../Database&Backend/admin_api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json'
      },
      body: JSON.stringify({
        action: 'delete_user',
        user_id: userId
      })
    })
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success') {
        alert('User deleted successfully');
        loadUsers(); // Reload the table
      } else {
        alert(data.message || 'Error deleting user');
      }
    })
    .catch(error => {
      console.error('Error:', error);
      alert('An error occurred. Please try again.');
    });
  }
  
  // Function to load stats
  function loadStats() {
    // Fetch stats from API
    fetch('../Database&Backend/admin_api.php?action=get_stats')
      .then(response => response.json())
      .then(data => {
        if (data.status === 'success') {
          document.getElementById('total-users').textContent = data.stats.total_users || '0';
          document.getElementById('active-users').textContent = data.stats.active_today || '0';
          document.getElementById('total-entries').textContent = data.stats.total_entries || '0';
          document.getElementById('new-users').textContent = data.stats.new_users_7d || '0';
        }
      })
      .catch(error => {
        console.error('Error fetching stats:', error);
      });
  }
</script> 