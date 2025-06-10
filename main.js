// main.js
window.addEventListener("DOMContentLoaded", () => {
  const user = localStorage.getItem("moodiary-user");
  if (user) {
    document.getElementById("login-screen").classList.add("hidden");
    document.getElementById("signup-screen").classList.add("hidden");
    document.getElementById("main-app").classList.add("active");
  }
});
