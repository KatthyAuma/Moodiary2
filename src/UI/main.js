// Moodiary - Main JavaScript file
// Handles global functionality across all pages

document.addEventListener("DOMContentLoaded", () => {
  // Check if user is logged in
  const isLoggedIn = sessionStorage.getItem("moodiary_logged_in");
  const protectedPages = ["home.html", "journal.html", "message.html"];
  const currentPage = window.location.pathname.split('/').pop();
  
  // Add page transitions
  document.body.classList.add('page-loaded');
  
  // Handle image loading errors
  const images = document.querySelectorAll('img');
  images.forEach(img => {
    img.onerror = function() {
      // If image fails to load, replace with a placeholder or hide
      this.style.display = 'none';
    };
  });

  // Add smooth scrolling to all anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function(e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth'
        });
      }
    });
  });

  // Add theme toggle functionality (future feature)
  const addThemeToggle = () => {
    const themeToggle = document.createElement('div');
    themeToggle.classList.add('theme-toggle');
    themeToggle.innerHTML = '🌙';
    themeToggle.title = 'Toggle dark mode';
    document.body.appendChild(themeToggle);
    
    themeToggle.addEventListener('click', () => {
      document.body.classList.toggle('dark-theme');
      themeToggle.innerHTML = document.body.classList.contains('dark-theme') ? '☀️' : '🌙';
      localStorage.setItem('moodiary-theme', document.body.classList.contains('dark-theme') ? 'dark' : 'light');
    });
    
    // Check for saved theme preference
    const savedTheme = localStorage.getItem('moodiary-theme');
    if (savedTheme === 'dark') {
      document.body.classList.add('dark-theme');
      themeToggle.innerHTML = '☀️';
    }
  };
  
  // Only add theme toggle to logged in pages
  if (isLoggedIn && protectedPages.some(page => currentPage.includes(page))) {
    // Uncomment when dark theme is implemented
    // addThemeToggle();
  }
});
