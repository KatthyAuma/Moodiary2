<?php
// Include the header
include_once('header.php');

// Set page title
$pageTitle = "Journal";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Moodiary - Track your moods, share vibes, heal together" />
  <meta name="theme-color" content="#d4b8a8" />
  <title>Moodiary - Journal</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
</head>

<body>
  <div class="main-app active">
    <div class="menu-button" id="menu-button">
      <span></span><span></span><span></span>
    </div>

    <div class="journal-page">
      <h1>Your Journal</h1>

      <div class="journal-form">
        <h2>New Entry</h2>
        <h3>How are you feeling today?</h3>

        <div class="mood-tags">
          <div class="mood-tag" data-mood="1">
            <span class="mood-tag-emoji">😊</span>Happy
          </div>
          <div class="mood-tag" data-mood="2">
            <span class="mood-tag-emoji">😢</span>Sad
          </div>
          <div class="mood-tag" data-mood="3">
            <span class="mood-tag-emoji">😠</span>Angry
          </div>
          <div class="mood-tag" data-mood="4">
            <span class="mood-tag-emoji">😰</span>Anxious
          </div>
          <div class="mood-tag" data-mood="5">
            <span class="mood-tag-emoji">😌</span>Calm
          </div>
          <div class="mood-tag" data-mood="6">
            <span class="mood-tag-emoji">🤩</span>Excited
          </div>
          <div class="mood-tag" data-mood="7">
            <span class="mood-tag-emoji">😴</span>Tired
          </div>
          <div class="mood-tag" data-mood="8">
            <span class="mood-tag-emoji">🙏</span>Grateful
          </div>
        </div>

        <textarea id="journal-content" placeholder="Write about your day and how you're feeling..."></textarea>

        <div class="form-actions">
          <label class="checkbox-label">
            <input type="checkbox" id="journal-public">
            <span>Share with friends</span>
          </label>
          <button id="journal-submit" class="btn-primary">Save Entry</button>
        </div>
      </div>

      <div class="journal-history">
        <h2>Your Journal History</h2>
        <div id="journal-entries" class="journal-entries">
          <div class="loading">Loading your entries...</div>
        </div>
      </div>
    </div>

    <div class="sidebar" id="sidebar">
      <div class="sidebar-item">
        <a href="home.php">Home</a>
      </div>
      <div class="sidebar-item">
        <a href="message.php">Messages</a>
      </div>
      <div class="sidebar-item active">
        <a href="journal.php">Journal</a>
      </div>
      <div class="sidebar-item" id="logout-btn">Logout</div>
    </div>

    <div class="sidebar-overlay" id="sidebar-overlay"></div>
  </div>

  <script src="auth.js"></script>
  <script src="sidebar.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Check if user is logged in
      fetch('../Database&Backend/check_session.php')
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (!data.logged_in) {
            window.location.href = 'signin.html';
          } else {
            initJournal();
          }
        })
        .catch(error => {
          console.error('Error checking session:', error);
          window.location.href = 'signin.html';
        });

      function initJournal() {
        // Select mood tags
        const moodTags = document.querySelectorAll('.mood-tag');
        let selectedMood = null;

        moodTags.forEach(tag => {
          tag.addEventListener('click', function() {
            moodTags.forEach(t => t.classList.remove('active'));
            this.classList.add('active');
            selectedMood = this.dataset.mood;
          });
        });

        // Save journal entry
        const saveButton = document.getElementById('journal-submit');
        const journalText = document.getElementById('journal-content');
        const journalPublic = document.getElementById('journal-public');

        saveButton.addEventListener('click', function(e) {
          e.preventDefault();

          if (!selectedMood) {
            showNotification('Please select a mood', 'error');
            return;
          }

          if (!journalText.value.trim()) {
            showNotification('Please write something in your journal', 'error');
            return;
          }

          // Disable button while saving
          saveButton.disabled = true;
          saveButton.innerHTML = '<span class="spinner"></span> Saving...';

          // Create entry data
          const entryData = {
            mood_id: selectedMood,
            content: journalText.value.trim(),
            is_public: journalPublic.checked
          };

          // Send to server
          fetch('../Database&Backend/journal_api.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(entryData)
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              // Reset button
              saveButton.disabled = false;
              saveButton.textContent = 'Save Entry';

              if (data.status === 'success') {
                showNotification('Journal entry saved!', 'success');
                journalText.value = '';
                moodTags.forEach(t => t.classList.remove('active'));
                journalPublic.checked = false;
                selectedMood = null;

                // Refresh entries
                loadJournalEntries();
              } else {
                showNotification(data.message || 'Error saving journal entry', 'error');
              }
            })
            .catch(error => {
              console.error('Error saving journal entry:', error);
              showNotification('Error saving journal entry', 'error');

              // Reset button
              saveButton.disabled = false;
              saveButton.textContent = 'Save Entry';
            });
        });

        // Load journal entries
        loadJournalEntries();

        function loadJournalEntries() {
          const entriesContainer = document.getElementById('journal-entries');
          entriesContainer.innerHTML = '<div class="loading">Loading your entries...</div>';

          fetch('../Database&Backend/journal_api.php')
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              if (data.status === 'success' && data.data && data.data.entries && data.data.entries.length > 0) {
                entriesContainer.innerHTML = '';

                data.data.entries.forEach(entry => {
                  const entryElement = createEntryElement(entry);
                  entriesContainer.appendChild(entryElement);
                });
              } else {
                entriesContainer.innerHTML = `
                  <div class="empty-state">
                    <p>You don't have any journal entries yet.</p>
                    <p>Start by creating one above!</p>
                  </div>
                `;
              }
            })
            .catch(error => {
              console.error('Error loading journal entries:', error);
              entriesContainer.innerHTML = `
                <div class="error-state">
                  <p>Error loading journal entries. Please try again later.</p>
                </div>
              `;
            });
        }

        function createEntryElement(entry) {
          const entryElement = document.createElement('div');
          entryElement.className = 'journal-entry';

          // Format date
          const date = new Date(entry.created_at);
          const formattedDate = date.toLocaleDateString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
          });

          entryElement.innerHTML = `
            <div class="entry-header">
              <span class="entry-date">${formattedDate}</span>
              <span class="entry-mood">${entry.emoji} ${entry.mood_name}</span>
            </div>
            <div class="entry-content">
              <p>${entry.content}</p>
            </div>
            <div class="entry-footer">
              <span class="entry-visibility">${entry.is_public ? 'Shared with friends' : 'Private'}</span>
            </div>
          `;

          return entryElement;
        }

        // Show notification
        function showNotification(message, type = 'error') {
          const notificationEl = document.createElement('div');
          notificationEl.className = `notification ${type}`;
          notificationEl.textContent = message;
          document.body.appendChild(notificationEl);

          setTimeout(() => {
            notificationEl.classList.add('show');

            setTimeout(() => {
              notificationEl.classList.remove('show');
              setTimeout(() => notificationEl.remove(), 300);
            }, 3000);
          }, 10);
        }
      }
    });
  </script>
</body>

</html>