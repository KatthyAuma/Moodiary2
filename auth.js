document.addEventListener("DOMContentLoaded", () => {
  // Signup
  const signupBtn = document.getElementById("signup-btn");
  if (signupBtn) {
    signupBtn.addEventListener("click", () => {
      const email = document.getElementById("signup-email").value;
      const password = document.getElementById("signup-password").value;

      if (email && password) {
        localStorage.setItem("moodiary_user", JSON.stringify({ email, password }));
        alert("Signup successful!");
        window.location.href = "signin.html";
      } else {
        alert("Please fill in all fields.");
      }
    });
  }

  // Signin
  const signinBtn = document.getElementById("signin-btn");
  if (signinBtn) {
    signinBtn.addEventListener("click", () => {
      const email = document.getElementById("signin-email").value;
      const password = document.getElementById("signin-password").value;
      const storedUser = JSON.parse(localStorage.getItem("moodiary_user"));

      if (storedUser && email === storedUser.email && password === storedUser.password) {
        sessionStorage.setItem("moodiary_logged_in", "true");
        window.location.href = "home.html";
      } else {
        alert("Invalid credentials.");
      }
    });
  }

  // Protect Home Page
  if (window.location.pathname.includes("home.html")) {
    const isLoggedIn = sessionStorage.getItem("moodiary_logged_in");
    if (!isLoggedIn) {
      window.location.href = "signin.html";
    }
  }

  // Logout
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      sessionStorage.removeItem("moodiary_logged_in");
      window.location.href = "signin.html";
    });
  }
});
