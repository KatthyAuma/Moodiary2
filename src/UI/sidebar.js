document.addEventListener('DOMContentLoaded', function() {
  const menuButton = document.getElementById('menu-button');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebar-overlay');
  
  // Create sidebar if it doesn't exist
  if (!sidebar) {
    createSidebar();
  }
  
  // Get sidebar and overlay after potential creation
  const updatedSidebar = document.getElementById('sidebar');
  const updatedOverlay = document.getElementById('sidebar-overlay');
  
  // Toggle sidebar
  if (menuButton && updatedSidebar && updatedOverlay) {
    menuButton.addEventListener('click', function() {
      updatedSidebar.classList.toggle('active');
      updatedOverlay.classList.toggle('active');
    });

    // Close sidebar when clicking overlay
    updatedOverlay.addEventListener('click', function() {
      updatedSidebar.classList.remove('active');
      updatedOverlay.classList.remove('active');
    });
  }
  
  // Add active class to current page in sidebar
  const currentPage = window.location.pathname.split('/').pop();
  const sidebarItems = document.querySelectorAll('.sidebar-item a');
  
  sidebarItems.forEach(item => {
    const href = item.getAttribute('href');
    if (href === currentPage || (currentPage === '' && href === 'home.php')) {
      item.parentElement.classList.add('active');
    }
  });

  // Add unread badge to Messages sidebar item
  fetch('../Database&Backend/messages_api.php')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && Array.isArray(data.data)) {
        const totalUnread = data.data.reduce((sum, thread) => sum + (parseInt(thread.unread_count, 10) || 0), 0);
        if (totalUnread > 0) {
          const messagesSidebar = Array.from(document.querySelectorAll('.sidebar-item a')).find(a => a.getAttribute('href') === 'message.php');
          if (messagesSidebar) {
            let badge = messagesSidebar.parentElement.querySelector('.sidebar-unread-badge');
            if (!badge) {
              badge = document.createElement('span');
              badge.className = 'sidebar-unread-badge';
              badge.style.cssText = 'background:red;color:white;border-radius:50%;padding:2px 7px;font-size:0.8em;position:absolute;right:18px;top:10px;z-index:2;';
              messagesSidebar.parentElement.style.position = 'relative';
              messagesSidebar.parentElement.appendChild(badge);
            }
            badge.textContent = totalUnread > 99 ? '99+' : totalUnread;
          }
        }
      }
    })
    .catch(error => {
      console.error('Error fetching unread messages:', error);
    });
});

/**
 * Create sidebar and overlay elements
 */
function createSidebar() {
  // Create sidebar
  const sidebar = document.createElement('div');
  sidebar.id = 'sidebar';
  sidebar.className = 'sidebar';
  
  // Create sidebar items
  let sidebarContent = `
    <div class="sidebar-item">
      <a href="home.php">Home</a>
    </div>
    <div class="sidebar-item">
      <a href="journal.php">Journal</a>
    </div>
    <div class="sidebar-item">
      <a href="message.php">Messages</a>
    </div>
  `;
  
  // Add role-specific items
  if (typeof isAdmin !== 'undefined' && isAdmin) {
    sidebarContent += `
      <div class="sidebar-item">
        <a href="admin.php">Admin Dashboard</a>
      </div>
    `;
  }
  
  if (typeof isMentor !== 'undefined' && isMentor) {
    sidebarContent += `
      <div class="sidebar-item">
        <a href="mentees.php">My Mentees</a>
      </div>
    `;
  }
  
  if (typeof isCounsellor !== 'undefined' && isCounsellor) {
    sidebarContent += `
      <div class="sidebar-item">
        <a href="clients.php">My Clients</a>
      </div>
    `;
  }
  
  // Add logout button
  sidebarContent += `<div class="sidebar-item" id="logout-btn">Logout</div>`;
  
  sidebar.innerHTML = sidebarContent;
  document.body.appendChild(sidebar);
  
  // Create overlay
  const overlay = document.createElement('div');
  overlay.id = 'sidebar-overlay';
  overlay.className = 'sidebar-overlay';
  document.body.appendChild(overlay);
  
  // Add logout functionality
  const logoutBtn = document.getElementById('logout-btn');
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
}
