document.addEventListener('DOMContentLoaded', function() {
  const menuButton = document.getElementById('menu-button');
  const sidebar = document.getElementById('sidebar');
  const sidebarOverlay = document.getElementById('sidebar-overlay');
  const logoutBtn = document.getElementById('logout-btn');

  // Toggle sidebar
  menuButton.addEventListener('click', function() {
    sidebar.classList.toggle('active');
    sidebarOverlay.classList.toggle('active');
    });

  // Close sidebar when clicking overlay
  sidebarOverlay.addEventListener('click', function() {
    sidebar.classList.remove('active');
    sidebarOverlay.classList.remove('active');
    });

  // Handle logout
  if (logoutBtn) {
    logoutBtn.addEventListener('click', function() {
      // Clear session storage
      sessionStorage.clear();
      localStorage.removeItem('user_token');
      
      // Redirect to login page
      window.location.href = 'index.html';
    });
  }
  
  // Add active class to current page in sidebar
  const currentPage = window.location.pathname.split('/').pop();
  const sidebarItems = document.querySelectorAll('.sidebar-item a');
  
  sidebarItems.forEach(item => {
    const href = item.getAttribute('href');
    if (href === currentPage) {
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
          const messagesSidebar = Array.from(document.querySelectorAll('.sidebar-item a')).find(a => a.getAttribute('href') === 'message.html');
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
    });
});
