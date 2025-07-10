document.addEventListener("DOMContentLoaded", () => {
  // Signup (index.html)
  if (window.location.pathname.includes("index.html")) {
    const signupBtn = document.getElementById("signup-btn");
    if (signupBtn) {
      signupBtn.addEventListener("click", () => {
        const email = document.getElementById("signup-email").value;
        const password = document.getElementById("signup-password").value;

        if (email && password) {
          fetch("backend/signup.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ email, password }),
          })
            .then((res) => res.json())
            .then((data) => {
              if (data.status === "success") {
                alert(data.message);
                window.location.href = "index.html"; // redirect to signup after successful signup
              } else {
                alert("Signup failed: " + data.message);
              }
            })
            .catch((err) => {
              console.error("Signup error:", err);
              alert("Something went wrong during signup.");
            });
        } else {
          alert("Please fill in all fields.");
        }
      });
    }
  }

  // Signin (also index.html for now)
  const signinBtn = document.getElementById("signin-btn");
  if (signinBtn) {
    signinBtn.addEventListener("click", () => {
      const email = document.getElementById("signin-email").value;
      const password = document.getElementById("signin-password").value;

      fetch("backend/signin.php", {
        method: "POST",
        headers: { "Content-Type": "application/json" },
        body: JSON.stringify({ email, password }),
      })
        .then((res) => res.json())
        .then((data) => {
          if (data.status === "success") {
            sessionStorage.setItem("moodiary_logged_in", "true");
            sessionStorage.setItem("moodiary_user_email", email);
            window.location.href = "home.html";
          } else {
            alert("Signin failed: " + data.message);
          }
        })
        .catch((err) => {
          console.error("Signin error:", err);
          alert("Something went wrong during signin.");
        });
    });
  }

  // Protect home page
  if (window.location.pathname.includes("home.html")) {
    const isLoggedIn = sessionStorage.getItem("moodiary_logged_in");
    if (!isLoggedIn) {
      window.location.href = "index.html"; // redirect to signup if not logged in
    }
  }

  // Logout
  const logoutBtn = document.getElementById("logout-btn");
  if (logoutBtn) {
    logoutBtn.addEventListener("click", () => {
      sessionStorage.removeItem("moodiary_logged_in");
      sessionStorage.removeItem("moodiary_user_email");
      window.location.href = "src/DatabaseBackend/logout.php"; // redirect to PHP logout script
    });
  }
});
