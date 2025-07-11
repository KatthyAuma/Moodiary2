document.addEventListener("DOMContentLoaded", () => {
  // Get user info from localStorage
  const storedUser = JSON.parse(localStorage.getItem("moodiary_user"));
  
  // Update greeting based on time of day
  const greeting = document.querySelector('.header h1');
  if (greeting) {
    const hour = new Date().getHours();
    let timeGreeting = "How are you feeling today?";
    
    if (hour < 12) {
      timeGreeting = "Good morning! How are you feeling?";
    } else if (hour < 18) {
      timeGreeting = "Good afternoon! How are you feeling?";
    } else {
      timeGreeting = "Good evening! How are you feeling?";
    }
    
    greeting.textContent = timeGreeting;
  }
  
  // Add animation to mood categories
  const moodCategories = document.querySelectorAll('.mood-category');
  if (moodCategories.length > 0) {
    moodCategories.forEach((category, index) => {
      category.style.animationDelay = `${0.1 * (index + 1)}s`;
      category.style.animation = 'fadeInUp 0.6s ease both';
    });
  }
  
  // Handle journal link if no journal entry today
  const journalReminder = document.querySelector('.header p');
  if (journalReminder) {
    journalReminder.innerHTML = 'Don\'t forget to <a href="journal.html" class="journal-link">record your journal entry</a>!';
  }
  
  // Add smooth scroll to top when page loads
  window.scrollTo({
    top: 0,
    behavior: 'smooth'
  });
});
