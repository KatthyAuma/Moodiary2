/**
 * Moodiary Dashboard Functionality
 * Handles all dynamic functionality for the home/dashboard page
 */

// Global variables
let currentUser = null;
let currentMood = null;
let isAdmin = false;
let isMentor = false;
let isCounsellor = false;

// Document ready
document.addEventListener('DOMContentLoaded', function() {
  // Check session
  checkSession();
  
  // Initialize UI components
  initMenuButton();
  initSectionTabs();
  
  // Initialize dashboard components
  initUserProfile();
  initQuickJournal();
  initFriendsList();
  initFriendRequests();
  initFriendSearch();
  initFeedContent();
  initRecommendations();
  initMoodTrends();
  
  // Initialize role-specific features
  initRoleSpecificFeatures();
  
  // Add CSS for quick-list
  addQuickListStyles();
});

/**
 * Initialize menu button functionality
 * Note: This is now handled by sidebar.js
 */
function initMenuButton() {
  // Sidebar functionality is now handled by sidebar.js
}

/**
 * Initialize section tabs functionality
 */
function initSectionTabs() {
  const tabs = document.querySelectorAll('.section-tab');
  
  tabs.forEach(tab => {
    tab.addEventListener('click', function() {
      // Remove active class from all tabs
      tabs.forEach(t => t.classList.remove('active'));
      // Add active class to clicked tab
      this.classList.add('active');
      
      // Hide all tab content
      document.querySelectorAll('.tab-content').forEach(content => {
        content.classList.remove('active');
      });
      
      // Show corresponding tab content
      const tabId = this.getAttribute('data-tab');
      document.getElementById(tabId).classList.add('active');
    });
  });
}

/**
 * Check if user is logged in via PHP session
 */
function checkSession() {
  fetch('../Database&Backend/check_session.php')
    .then(response => response.json())
    .then(data => {
      if (data.logged_in) {
        currentUser = data.user;
        
        // Set role variables
        if (data.roles && Array.isArray(data.roles)) {
          isAdmin = data.roles.includes('admin');
          isMentor = data.roles.includes('mentor');
          isCounsellor = data.roles.includes('counsellor');
          
          console.log('User roles:', data.roles);
          console.log('isAdmin:', isAdmin, 'isMentor:', isMentor, 'isCounsellor:', isCounsellor);
        }
        
        // Continue with initialization
      } else {
        // Redirect to login page if not logged in
        window.location.href = 'signin.php';
      }
    })
    .catch(error => {
      console.error('Error checking session:', error);
      // Redirect to login page on error
      window.location.href = 'signin.php';
    });
}

/**
 * Fetch user data from API
 */
function fetchUserData() {
  return fetch('../Database&Backend/check_session.php')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      console.log('User data response:', data);
      if (data.status === 'success' && data.logged_in) {
        // Return user data directly from the response
        return {
          user_id: data.user_id,
          username: data.username,
          full_name: data.full_name,
          email: data.email,
          profile_image: data.profile_image
        };
      } else {
        throw new Error(data.message || 'Error fetching user data');
      }
    });
}

/**
 * Fetch latest mood from API
 */
function fetchLatestMood() {
  return fetch('../Database&Backend/journal_api.php?latest_mood=1')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return null;
      }
    })
    .catch(error => {
      console.error('Error fetching latest mood:', error);
      return null;
    });
}

/**
 * Fetch user streak from API
 */
function fetchUserStreak() {
  return fetch('../Database&Backend/journal_api.php?streak=1')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return { streak: 0 };
      }
    })
    .catch(error => {
      console.error('Error fetching streak data:', error);
      return { streak: 0 };
    });
}

/**
 * Submit journal entry to API
 */
function submitJournalEntry(journalData) {
  // Get mood_id from the selected mood tag
  const selectedMoodTag = document.querySelector('.mood-tag.active');
  if (selectedMoodTag) {
    // Use mood_id instead of mood name
    journalData.mood_id = selectedMoodTag.getAttribute('data-mood-id');
  }
  
  return fetch('../Database&Backend/journal_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(journalData)
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .catch(error => {
    console.error('Error submitting journal entry:', error);
    return { status: 'error', message: 'Failed to submit journal entry' };
  });
}

/**
 * Fetch friends from API
 */
function fetchFriends() {
  return fetch('../Database&Backend/friends_api.php')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return [];
      }
    })
    .catch(error => {
      console.error('Error fetching friends:', error);
      return [];
    });
}

/**
 * Fetch friend requests from API
 */
function fetchFriendRequests() {
  return fetch('../Database&Backend/friends_api.php?requests=1')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return { received: [], sent: [] };
      }
    })
    .catch(error => {
      console.error('Error fetching friend requests:', error);
      return { received: [], sent: [] };
    });
}

/**
 * Accept friend request
 */
function acceptFriendRequest(friendId, relationshipType = 'friend') {
  return fetch('../Database&Backend/friends_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      action: 'accept',
      friend_id: friendId,
      relationship_type: relationshipType
    })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .catch(error => {
    console.error('Error accepting friend request:', error);
    return { status: 'error', message: 'Failed to accept friend request' };
  });
}

/**
 * Reject friend request
 */
function rejectFriendRequest(friendId) {
  return fetch('../Database&Backend/friends_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      action: 'reject',
      friend_id: friendId
    })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .catch(error => {
    console.error('Error rejecting friend request:', error);
    return { status: 'error', message: 'Failed to reject friend request' };
  });
}

/**
 * Send friend request
 */
function sendFriendRequest(friendId, relationshipType = 'friend') {
  return fetch('../Database&Backend/friends_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify({
      action: 'request',
      friend_id: friendId,
      relationship_type: relationshipType
    })
  })
  .then(response => {
    if (!response.ok) {
      throw new Error('Network response was not ok');
    }
    return response.json();
  })
  .catch(error => {
    console.error('Error sending friend request:', error);
    return { status: 'error', message: 'Failed to send friend request' };
  });
}

/**
 * Search users
 */
function searchUsers(searchTerm) {
  return fetch(`../Database&Backend/friends_api.php?search=${encodeURIComponent(searchTerm)}`)
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return [];
      }
    })
    .catch(error => {
      console.error('Error searching users:', error);
      return [];
    });
}

/**
 * Fetch feed content
 */
function fetchFeed() {
  return fetch('../Database&Backend/journal_api.php?feed=1')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return [];
      }
    })
    .catch(error => {
      console.error('Error fetching feed:', error);
      return [];
    });
}

/**
 * Fetch recommendations
 */
function fetchRecommendations() {
  return fetch('../Database&Backend/recommendations_api.php')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success') {
        return data.data;
      } else {
        return null;
      }
    })
    .catch(error => {
      console.error('Error fetching recommendations:', error);
      return null;
    });
}

/**
 * Show notification
 */
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

/**
 * Initialize user profile section
 */
function initUserProfile() {
  const userAvatar = document.getElementById('user-avatar');
  const userName = document.getElementById('user-name');
  const userStreak = document.getElementById('user-streak');
  const currentMood = document.getElementById('current-mood');
  
  // Get user data from session storage or fetch from API
  const userData = {
    id: sessionStorage.getItem('user_id'),
    username: sessionStorage.getItem('username'),
    fullName: sessionStorage.getItem('full_name'),
    email: sessionStorage.getItem('email'),
    profileImage: sessionStorage.getItem('profile_image')
  };
  
  // If we don't have user data in session storage, fetch it from the API
  if (!userData.username) {
    fetchUserData()
      .then(data => {
        updateUserProfile(data);
      })
      .catch(error => {
        console.error('Error fetching user data:', error);
      });
  } else {
    updateUserProfile(userData);
  }
  
  // Get user's current mood from latest journal entry
  fetchLatestMood()
    .then(moodData => {
      if (moodData && moodData.mood_name) {
        const moodEmoji = document.querySelector('.mood-emoji');
        const moodText = document.querySelector('.mood-text');
        
        moodEmoji.textContent = moodData.emoji || '😊';
        moodText.textContent = moodData.mood_name;
      }
    })
    .catch(error => {
      console.error('Error fetching mood data:', error);
    });
    
  // Get user's streak data
  fetchUserStreak()
    .then(streakData => {
      if (streakData && streakData.streak) {
        userStreak.textContent = `🔥 ${streakData.streak} day streak`;
      } else {
        userStreak.textContent = 'Start your streak today!';
      }
    })
    .catch(error => {
      console.error('Error fetching streak data:', error);
    });
}

/**
 * Update user profile with data
 */
function updateUserProfile(userData) {
  console.log('Updating profile with:', userData);
  const userAvatar = document.getElementById('user-avatar');
  const userName = document.getElementById('user-name');
  
  if (!userData) {
    console.error('No user data provided to updateUserProfile');
    return;
  }
  
  // Update session storage
  if (userData.username) sessionStorage.setItem('username', userData.username);
  if (userData.full_name) sessionStorage.setItem('full_name', userData.full_name);
  if (userData.email) sessionStorage.setItem('email', userData.email);
  if (userData.profile_image) sessionStorage.setItem('profile_image', userData.profile_image);
  
  // Update UI
  if (userData.full_name) {
    userName.textContent = userData.full_name;
  } else {
    userName.textContent = userData.username || 'User';
  }
  
  // Set avatar (either initials or profile image)
  if (userData.profile_image && userData.profile_image !== 'default.png') {
    userAvatar.innerHTML = `<img src="../images/profiles/${userData.profile_image}" alt="${userData.username}">`;
  } else {
    // Generate initials from name
    const fullName = userData.full_name || userData.username || 'User';
    const initials = fullName.split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
    
    userAvatar.textContent = initials;
  }
}

/**
 * Initialize quick journal entry functionality
 */
function initQuickJournal() {
  const moodTags = document.querySelectorAll('.mood-tag');
  const quickJournalInput = document.querySelector('.quick-journal-input');
  const quickJournalPublic = document.getElementById('quick-journal-public');
  const quickJournalSubmit = document.querySelector('.quick-journal-submit');
  
  let selectedMoodId = null;
  
  // Handle mood selection
  moodTags.forEach(tag => {
    tag.addEventListener('click', function() {
      moodTags.forEach(t => t.classList.remove('active'));
      this.classList.add('active');
      selectedMoodId = this.getAttribute('data-mood-id');
    });
  });
  
  // Handle journal submission
  if (quickJournalSubmit) {
    quickJournalSubmit.addEventListener('click', function() {
      if (!selectedMoodId) {
        showNotification('Please select a mood', 'error');
        return;
      }
      
      if (!quickJournalInput.value.trim()) {
        showNotification('Please write something in your journal', 'error');
        return;
      }
      
      const journalData = {
        mood_id: selectedMoodId,
        content: quickJournalInput.value.trim(),
        is_public: quickJournalPublic.checked
      };
      
      console.log('Submitting journal entry:', journalData);
      
      submitJournalEntry(journalData)
        .then(response => {
          console.log('Journal submission response:', response);
          if (response.status === 'success') {
            showNotification('Journal entry saved!', 'success');
            quickJournalInput.value = '';
            moodTags.forEach(t => t.classList.remove('active'));
            quickJournalPublic.checked = false;
            selectedMoodId = null;
            
            // Update streak
            fetchUserStreak()
              .then(streakData => {
                const userStreak = document.getElementById('user-streak');
                if (userStreak && streakData && streakData.streak) {
                  userStreak.textContent = `🔥 ${streakData.streak} day streak`;
                }
              });
            
            // Refresh feed and recommendations
            initFeedContent();
            initRecommendations();
            
            // Update user's current mood
            fetchLatestMood()
              .then(moodData => {
                if (moodData && moodData.mood_name) {
                  const moodEmoji = document.querySelector('.mood-emoji');
                  const moodText = document.querySelector('.mood-text');
                  
                  if (moodEmoji) moodEmoji.textContent = moodData.emoji || '😊';
                  if (moodText) moodText.textContent = moodData.mood_name;
                }
              });
          } else {
            showNotification(response.message || 'Error saving journal entry', 'error');
          }
        })
        .catch(error => {
          showNotification('Error saving journal entry', 'error');
          console.error('Error:', error);
        });
    });
  }
}

/**
 * Initialize friends list
 */
function initFriendsList() {
  const friendsList = document.getElementById('friends-list');
  const friendsListContainer = friendsList.querySelector('.friends-list');
  
  // Show loading state
  friendsListContainer.innerHTML = '<div class="loading">Loading friends...</div>';
  
  // Fetch friends from API
  fetchFriends()
    .then(friends => {
      if (friends && friends.length > 0) {
        // Clear loading state
        friendsListContainer.innerHTML = '';
        
        // Add friends to list
        friends.forEach(friend => {
          const friendCard = createFriendCard(friend);
          friendsListContainer.appendChild(friendCard);
        });
      } else {
        // Show empty state
        friendsListContainer.innerHTML = `
          <div class="empty-state">
            <p>You don't have any friends yet.</p>
            <button class="btn-primary" id="find-friends-btn">Find Friends</button>
          </div>
        `;
        
        // Add event listener to find friends button
        const findFriendsBtn = document.getElementById('find-friends-btn');
        if (findFriendsBtn) {
          findFriendsBtn.addEventListener('click', function() {
            // Switch to search tab
            document.querySelector('.section-tab[data-tab="friend-search"]').click();
          });
        }
      }
    })
    .catch(error => {
      console.error('Error fetching friends:', error);
      friendsListContainer.innerHTML = `
        <div class="error-state">
          <p>Error loading friends. Please try again later.</p>
        </div>
      `;
    });
}

/**
 * Create a friend card element
 */
function createFriendCard(friend) {
  const friendCard = document.createElement('div');
  friendCard.className = 'friend-card';
  
  // Generate initials for avatar
  let initials = 'U';
  if (friend.full_name) {
    initials = friend.full_name.split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  } else if (friend.username) {
    initials = friend.username.substring(0, 2).toUpperCase();
  }
  
  // Create avatar element
  const avatar = document.createElement('div');
  avatar.className = 'friend-avatar';
  if (friend.profile_image && friend.profile_image !== 'default.png') {
    avatar.innerHTML = `<img src="../images/profiles/${friend.profile_image}" alt="${friend.username}">`;
  } else {
    avatar.textContent = initials;
  }
  
  // Create name element
  const name = document.createElement('div');
  name.className = 'friend-name';
  name.textContent = friend.full_name || friend.username;
  
  // Create mood element
  const mood = document.createElement('div');
  mood.className = 'friend-mood';
  if (friend.emoji && friend.mood_name) {
    mood.textContent = `${friend.emoji} ${friend.mood_name}`;
  } else {
    mood.textContent = 'No recent mood';
  }
  
  // Create relationship badge if applicable
  if (friend.relationship_type && friend.relationship_type !== 'friend') {
    const badge = document.createElement('div');
    badge.className = `friend-badge ${friend.relationship_type}`;
    badge.textContent = friend.relationship_type;
    friendCard.appendChild(badge);
  }
  
  // Create action button
  const action = document.createElement('a');
  action.className = 'friend-action';
  action.href = `message.html?user=${friend.username || friend.user_id}`;
  action.textContent = 'Message';
  
  // Append elements to card
  friendCard.appendChild(avatar);
  friendCard.appendChild(name);
  friendCard.appendChild(mood);
  friendCard.appendChild(action);
  
  return friendCard;
}

/**
 * Initialize friend requests
 */
function initFriendRequests() {
  const requestsTab = document.getElementById('friend-requests');
  const requestCount = document.getElementById('request-count');
  
  // Fetch friend requests from API
  fetchFriendRequests()
    .then(requests => {
      if (requests && requests.received && requests.received.length > 0) {
        // Update request count badge
        requestCount.textContent = requests.received.length;
        requestCount.style.display = 'inline-flex';
        
        // Clear existing requests
        requestsTab.innerHTML = '';
        
        // Add received requests to list
        requests.received.forEach(request => {
          const requestElement = createFriendRequestElement(request);
          requestsTab.appendChild(requestElement);
        });
      } else {
        // Hide request count badge
        requestCount.style.display = 'none';
        
        // Show empty state
        requestsTab.innerHTML = `
          <div class="empty-state">
            <p>You don't have any friend requests.</p>
          </div>
        `;
      }
    })
    .catch(error => {
      console.error('Error fetching friend requests:', error);
      requestsTab.innerHTML = `
        <div class="error-state">
          <p>Error loading friend requests. Please try again later.</p>
        </div>
      `;
    });
}

/**
 * Create a friend request element
 */
function createFriendRequestElement(request) {
  const requestElement = document.createElement('div');
  requestElement.className = 'friend-request';
  requestElement.dataset.userId = request.user_id;
  
  // Generate initials for avatar
  let initials = 'U';
  if (request.full_name) {
    initials = request.full_name.split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  } else if (request.username) {
    initials = request.username.substring(0, 2).toUpperCase();
  }
  
  // Format request date
  const requestDate = new Date(request.request_date);
  const now = new Date();
  let timeAgo;
  
  const diffInSeconds = Math.floor((now - requestDate) / 1000);
  if (diffInSeconds < 60) {
    timeAgo = 'just now';
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    timeAgo = `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    timeAgo = `${hours} hour${hours > 1 ? 's' : ''} ago`;
  } else {
    const days = Math.floor(diffInSeconds / 86400);
    timeAgo = `${days} day${days > 1 ? 's' : ''} ago`;
  }
  
  // Create HTML structure
  requestElement.innerHTML = `
    <div class="friend-avatar">${initials}</div>
    <div class="friend-info">
      <div class="friend-name">${request.full_name || request.username}</div>
      <div class="friend-time">${timeAgo}</div>
    </div>
    <div class="friend-request-actions">
      <button class="btn-accept">Accept</button>
      <button class="btn-reject">Reject</button>
      <select class="relationship-type">
        <option value="friend">Friend</option>
        <option value="mentor">Mentor</option>
        <option value="counsellor">Counsellor</option>
        <option value="family">Family</option>
      </select>
    </div>
  `;
  
  // Add event listeners for accept/reject buttons
  const acceptBtn = requestElement.querySelector('.btn-accept');
  const rejectBtn = requestElement.querySelector('.btn-reject');
  const relationshipSelect = requestElement.querySelector('.relationship-type');
  
  acceptBtn.addEventListener('click', function() {
    const relationshipType = relationshipSelect.value;
    acceptFriendRequest(request.user_id, relationshipType)
      .then(response => {
        if (response.status === 'success') {
          showNotification(`${relationshipType} request accepted!`, 'success');
          requestElement.remove();
          
          // Update request count
          const requestCount = document.getElementById('request-count');
          const count = parseInt(requestCount.textContent) - 1;
          if (count > 0) {
            requestCount.textContent = count;
          } else {
            requestCount.style.display = 'none';
            document.getElementById('friend-requests').innerHTML = `
              <div class="empty-state">
                <p>You don't have any friend requests.</p>
              </div>
            `;
          }
          
          // Refresh friends list
          initFriendsList();
        } else {
          showNotification(response.message || 'Error accepting friend request', 'error');
        }
      })
      .catch(error => {
        showNotification('Error accepting friend request', 'error');
        console.error('Error:', error);
      });
  });
  
  rejectBtn.addEventListener('click', function() {
    rejectFriendRequest(request.user_id)
      .then(response => {
        if (response.status === 'success') {
          showNotification('Friend request rejected', 'success');
          requestElement.remove();
          
          // Update request count
          const requestCount = document.getElementById('request-count');
          const count = parseInt(requestCount.textContent) - 1;
          if (count > 0) {
            requestCount.textContent = count;
          } else {
            requestCount.style.display = 'none';
            document.getElementById('friend-requests').innerHTML = `
              <div class="empty-state">
                <p>You don't have any friend requests.</p>
              </div>
            `;
          }
        } else {
          showNotification(response.message || 'Error rejecting friend request', 'error');
        }
      })
      .catch(error => {
        showNotification('Error rejecting friend request', 'error');
        console.error('Error:', error);
      });
  });
  
  return requestElement;
}

/**
 * Initialize friend search functionality
 */
function initFriendSearch() {
  // Global search
  const searchInput = document.getElementById('friends-search-input');
  const searchBtn = document.getElementById('friends-search-btn');
  
  if (searchBtn && searchInput) {
    // Search when button is clicked
    searchBtn.addEventListener('click', function() {
      performFriendSearch(searchInput.value);
    });
    
    // Search when Enter key is pressed
    searchInput.addEventListener('keypress', function(event) {
      if (event.key === 'Enter') {
        performFriendSearch(searchInput.value);
      }
    });
  }
  
  // Find friends search (tab)
  const findFriendsInput = document.getElementById('find-friends-input');
  const findFriendsBtn = document.getElementById('find-friends-btn');
  const searchResults = document.querySelector('#friend-search .search-results');
  
  if (findFriendsBtn && findFriendsInput) {
    // Search when button is clicked
    findFriendsBtn.addEventListener('click', function() {
      performGlobalSearch(findFriendsInput.value, searchResults);
    });
    
    // Search when Enter key is pressed
    findFriendsInput.addEventListener('keypress', function(event) {
      if (event.key === 'Enter') {
        performGlobalSearch(findFriendsInput.value, searchResults);
      }
    });
    
    // Add clear search button
    const clearSearchBtn = document.createElement('button');
    clearSearchBtn.className = 'btn-secondary';
    clearSearchBtn.textContent = 'Clear Search';
    clearSearchBtn.style.marginTop = '10px';
    clearSearchBtn.style.display = 'none';
    
    searchResults.parentNode.insertBefore(clearSearchBtn, searchResults);
    
    clearSearchBtn.addEventListener('click', function() {
      findFriendsInput.value = '';
      searchResults.innerHTML = '';
      clearSearchBtn.style.display = 'none';
    });
    
    // Show clear button when search is performed
    findFriendsBtn.addEventListener('click', function() {
      if (findFriendsInput.value.trim()) {
        clearSearchBtn.style.display = 'block';
      }
    });
  }
}

/**
 * Perform search for global users
 */
function performGlobalSearch(searchTerm, resultsContainer) {
  // Show loading state
  resultsContainer.innerHTML = '<div class="loading">Searching...</div>';
  
  // Fetch search results from API
  searchUsers(searchTerm)
    .then(users => {
      // Clear loading state
      resultsContainer.innerHTML = '';
      
      if (users && users.length > 0) {
        // Create header with search term and clear button
        const searchHeader = document.createElement('div');
        searchHeader.className = 'search-results-header';
        searchHeader.innerHTML = `
          <h3>Search results for "${searchTerm}"</h3>
          <button class="search-results-clear">Clear</button>
        `;
        resultsContainer.appendChild(searchHeader);
        
        // Add clear button event listener
        const clearBtn = searchHeader.querySelector('.search-results-clear');
        clearBtn.addEventListener('click', function() {
          resultsContainer.innerHTML = '';
          document.querySelector('#global-search-input').value = '';
        });
        
        // Create results container
        const searchResults = document.createElement('div');
        searchResults.className = 'search-results';
        
        // Add user cards
        users.forEach(user => {
          const userCard = createSearchResultCard(user);
          searchResults.appendChild(userCard);
        });
        
        resultsContainer.appendChild(searchResults);
      } else {
        // Show empty state
        resultsContainer.innerHTML = `
          <div class="search-empty">
            <p>No users found matching "${searchTerm}"</p>
            <button class="search-results-clear">Clear search</button>
          </div>
        `;
        
        // Add clear button event listener
        const clearBtn = resultsContainer.querySelector('.search-results-clear');
        clearBtn.addEventListener('click', function() {
          resultsContainer.innerHTML = '';
          document.querySelector('#global-search-input').value = '';
        });
      }
    })
    .catch(error => {
      console.error('Error searching users:', error);
      resultsContainer.innerHTML = `
        <div class="error-state">
          <p>Error searching users. Please try again later.</p>
        </div>
      `;
    });
}

/**
 * Perform search within friends list
 */
function performFriendSearch(searchTerm) {
  const friendsList = document.querySelector('.friends-list');
  // Remove any previous search results
  const oldResults = friendsList.parentNode.querySelector('.search-results');
  if (oldResults) oldResults.remove();
  const searchResults = document.createElement('div');
  searchResults.className = 'search-results';
  
  // Get all friend cards
  const friendCards = Array.from(friendsList.querySelectorAll('.friend-card'));
  
  // Filter friends based on search term
  const filteredFriends = friendCards.filter(card => {
    const name = card.querySelector('.friend-name').textContent.toLowerCase();
    return name.includes(searchTerm.toLowerCase());
  });
  
  // Clear previous search results
  searchResults.innerHTML = '';
  
  // Create header with search term and clear button
  const searchHeader = document.createElement('div');
  searchHeader.className = 'search-results-header';
  searchHeader.innerHTML = `
    <h3>Search results for "${searchTerm}"</h3>
    <button class="search-results-clear">Clear</button>
  `;
  searchResults.appendChild(searchHeader);
  
  if (filteredFriends.length > 0) {
    // Clone and add filtered friend cards to search results
    filteredFriends.forEach(card => {
      const clone = card.cloneNode(true);
      searchResults.appendChild(clone);
      
      // Re-attach event listeners to cloned elements if needed
      const messageBtn = clone.querySelector('.friend-action');
      if (messageBtn) {
        const friendId = messageBtn.dataset.friendId;
        const friendUsername = messageBtn.dataset.username;
        
        messageBtn.addEventListener('click', function() {
          window.location.href = `message.php?user=${friendUsername || friendId}`;
        });
      }
    });
  } else {
    // Show empty state
    const emptyState = document.createElement('div');
    emptyState.className = 'search-empty';
    emptyState.innerHTML = `<p>No friends found matching "${searchTerm}"</p>`;
    searchResults.appendChild(emptyState);
  }
  
  // Add clear button event listener
  const clearBtn = searchHeader.querySelector('.search-results-clear');
  clearBtn.addEventListener('click', function() {
    // Remove search results
    if (searchResults.parentNode) {
      searchResults.parentNode.removeChild(searchResults);
    }
    
    // Clear search input
    document.querySelector('.friends-search-sidebar input').value = '';
    
    // Show all friend cards
    friendCards.forEach(card => {
      card.style.display = 'flex';
    });
  });
  
  // Insert search results before friends list
  friendsList.parentNode.insertBefore(searchResults, friendsList);
  
  // Hide all friend cards in the original list
  friendCards.forEach(card => {
    card.style.display = 'none';
  });
}

/**
 * Create a search result card element
 */
function createSearchResultCard(user) {
  const userCard = document.createElement('div');
  userCard.className = 'search-result-card';
  
  // Generate initials for avatar
  let initials = 'U';
  if (user.full_name) {
    initials = user.full_name.split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  } else if (user.username) {
    initials = user.username.substring(0, 2).toUpperCase();
  }
  
  // Determine action button based on friendship status
  let actionBtn;
  switch (user.friendship_status) {
    case 'friend':
      actionBtn = `<a href="message.php?user=${user.username || user.user_id}" class="btn-primary">Message</a>`;
      break;
    case 'pending':
      actionBtn = `<button class="btn-disabled" disabled>Request Sent</button>`;
      break;
    case 'request':
      actionBtn = `
        <button class="btn-accept" data-user-id="${user.user_id}">Accept</button>
        <button class="btn-reject" data-user-id="${user.user_id}">Reject</button>
      `;
      break;
    default:
      actionBtn = `<button class="btn-add-friend" data-user-id="${user.user_id}">Add Friend</button>`;
  }
  
  // Create avatar element
  let avatarHtml;
  if (user.profile_image && user.profile_image !== 'default.png') {
    avatarHtml = `<img src="../images/profiles/${user.profile_image}" alt="${user.username}">`;
  } else {
    avatarHtml = initials;
  }
  
  // Create HTML structure
  userCard.innerHTML = `
    <div class="search-result-avatar">${avatarHtml}</div>
    <div class="search-result-info">
      <div class="search-result-name">${user.full_name || user.username}</div>
      <div class="search-result-username">@${user.username}</div>
    </div>
    <div class="search-result-actions">
      ${actionBtn}
    </div>
  `;
  
  // Add event listeners for action buttons
  if (user.friendship_status === 'none') {
    const addFriendBtn = userCard.querySelector('.btn-add-friend');
    
    addFriendBtn.addEventListener('click', function() {
      const relationshipType = 'friend';
      sendFriendRequest(user.user_id, relationshipType)
        .then(response => {
          if (response.status === 'success') {
            showNotification('Friend request sent!', 'success');
            
            // Update UI to show pending status
            const userActions = userCard.querySelector('.search-result-actions');
            userActions.innerHTML = `<button class="btn-disabled" disabled>Request Sent</button>`;
          } else {
            showNotification(response.message || 'Error sending friend request', 'error');
          }
        })
        .catch(error => {
          showNotification('Error sending friend request', 'error');
          console.error('Error:', error);
        });
    });
  } else if (user.friendship_status === 'request') {
    const acceptBtn = userCard.querySelector('.btn-accept');
    const rejectBtn = userCard.querySelector('.btn-reject');
    
    acceptBtn.addEventListener('click', function() {
      acceptFriendRequest(user.user_id, 'friend')
        .then(response => {
          if (response.status === 'success') {
            showNotification('Friend request accepted!', 'success');
            
            // Update UI to show friend status
            const userActions = userCard.querySelector('.search-result-actions');
            userActions.innerHTML = `<a href="message.php?user=${user.username || user.user_id}" class="btn-primary">Message</a>`;
            
            // Refresh friends list
            initFriendsList();
          } else {
            showNotification(response.message || 'Error accepting friend request', 'error');
          }
        })
        .catch(error => {
          showNotification('Error accepting friend request', 'error');
          console.error('Error:', error);
        });
    });
    
    rejectBtn.addEventListener('click', function() {
      rejectFriendRequest(user.user_id)
        .then(response => {
          if (response.status === 'success') {
            showNotification('Friend request rejected', 'success');
            
            // Update UI to show add friend button
            const userActions = userCard.querySelector('.search-result-actions');
            userActions.innerHTML = `<button class="btn-add-friend" data-user-id="${user.user_id}">Add Friend</button>`;
            
            // Re-initialize event listener for add friend button
            const addFriendBtn = userActions.querySelector('.btn-add-friend');
            
            addFriendBtn.addEventListener('click', function() {
              const relationshipType = relationshipSelect.value;
              sendFriendRequest(user.user_id, relationshipType)
                .then(response => {
                  if (response.status === 'success') {
                    showNotification('Friend request sent!', 'success');
                    
                    // Update UI to show pending status
                    userActions.innerHTML = `<button class="btn-disabled" disabled>Request Sent</button>`;
                  } else {
                    showNotification(response.message || 'Error sending friend request', 'error');
                  }
                })
                .catch(error => {
                  showNotification('Error sending friend request', 'error');
                  console.error('Error:', error);
                });
            });
          } else {
            showNotification(response.message || 'Error rejecting friend request', 'error');
          }
        })
        .catch(error => {
          showNotification('Error rejecting friend request', 'error');
          console.error('Error:', error);
        });
    });
  }
  
  return userCard;
} 

/**
 * Initialize feed content
 */
function initFeedContent() {
  const feedSection = document.querySelector('.feed-section');
  
  if (!feedSection) return;
  
  // Show loading state
  feedSection.innerHTML = '<div class="feed-loading">Loading feed...</div>';
  
  // Fetch feed content from API
  fetchFeed()
    .then(entries => {
      if (entries && entries.length > 0) {
        // Clear loading state
        feedSection.innerHTML = '';
        
        // Add entries to feed
        entries.forEach(entry => {
          const feedItem = createFeedItem(entry);
          feedSection.appendChild(feedItem);
        });
      } else {
        // Show empty state
        feedSection.innerHTML = `
          <div class="feed-empty">
            <p>No entries to show. Add a journal entry or connect with friends to see their public entries.</p>
          </div>
        `;
      }
    })
    .catch(error => {
      console.error('Error fetching feed:', error);
      feedSection.innerHTML = `
        <div class="error-state">
          <p>Error loading feed. Please try again later.</p>
        </div>
      `;
    });
}

/**
 * Create a feed item element
 */
function createFeedItem(entry) {
  const feedItem = document.createElement('div');
  feedItem.className = 'feed-item';
  feedItem.dataset.entryId = entry.entry_id;
  
  // Format entry date
  const entryDate = new Date(entry.created_at);
  const now = new Date();
  let timeAgo;
  
  const diffInSeconds = Math.floor((now - entryDate) / 1000);
  
  if (diffInSeconds < 60) {
    timeAgo = `${diffInSeconds} seconds ago`;
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    timeAgo = `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    timeAgo = `${hours} hour${hours > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400);
    timeAgo = `${days} day${days > 1 ? 's' : ''} ago`;
  } else {
    timeAgo = entryDate.toLocaleDateString();
  }
  
  // Get reaction counts
  const likes = entry.likes || 0;
  const support = entry.support || 0;
  const hugs = entry.hugs || 0;
  
  // Check if user has reacted
  const userReacted = entry.user_reaction || false;
  
  // Generate initials for avatar
  let initials = 'U';
  if (entry.full_name) {
    initials = entry.full_name.split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  } else if (entry.username) {
    initials = entry.username.substring(0, 2).toUpperCase();
  }
  
  // Create avatar element
  let avatarHtml;
  if (entry.profile_image && entry.profile_image !== 'default.png') {
    avatarHtml = `<img src="../images/profiles/${entry.profile_image}" alt="${entry.username}">`;
  } else {
    avatarHtml = initials;
  }
  
  // Create HTML structure
  feedItem.innerHTML = `
    <div class="feed-header">
      <div class="feed-user">
        <div class="feed-avatar">${avatarHtml}</div>
        <div class="feed-user-info">
          <div class="feed-username">${entry.full_name || entry.username}</div>
          <div class="feed-time">${timeAgo}</div>
        </div>
      </div>
      <div class="feed-mood">
        <span class="feed-mood-emoji">${entry.emoji}</span>
        <span class="feed-mood-text">Feeling ${entry.mood_name}</span>
      </div>
    </div>
    <div class="feed-content">
      <p>${entry.content}</p>
    </div>
    <div class="feed-actions">
      <div class="feed-reactions">
        <button class="reaction-btn ${userReacted === 'like' ? 'active' : ''}" data-reaction="like" data-entry-id="${entry.entry_id}">
          <span class="reaction-icon">👍</span>
          <span class="reaction-count">${likes}</span>
        </button>
        <button class="reaction-btn ${userReacted === 'support' ? 'active' : ''}" data-reaction="support" data-entry-id="${entry.entry_id}">
          <span class="reaction-icon">🙌</span>
          <span class="reaction-count">${support}</span>
        </button>
        <button class="reaction-btn ${userReacted === 'hug' ? 'active' : ''}" data-reaction="hug" data-entry-id="${entry.entry_id}">
          <span class="reaction-icon">🤗</span>
          <span class="reaction-count">${hugs}</span>
        </button>
      </div>
      <div class="feed-comment-actions">
        <button class="comment-btn" data-entry-id="${entry.entry_id}">
          <span class="comment-icon">💬</span>
          <span class="comment-text">Comment</span>
        </button>
        <button class="private-reply-btn" data-user-id="${entry.user_id}" data-entry-id="${entry.entry_id}">
          <span class="private-reply-icon">✉️</span>
          <span class="private-reply-text">Private Reply</span>
        </button>
      </div>
    </div>
    <div class="feed-comments" id="comments-${entry.entry_id}" style="display: none;">
      <div class="comments-container"></div>
      <div class="comment-form">
        <textarea placeholder="Write a comment..." class="comment-input"></textarea>
        <button class="comment-submit">Post</button>
      </div>
    </div>
  `;
  
  // Add event listeners for reactions
  const reactionBtns = feedItem.querySelectorAll('.reaction-btn');
  reactionBtns.forEach(btn => {
    btn.addEventListener('click', function() {
      const reactionType = this.dataset.reaction;
      const entryId = this.dataset.entryId;
      const countEl = this.querySelector('.reaction-count');
      
      // Toggle active class
      const wasActive = this.classList.contains('active');
      
      // Update UI optimistically
      if (wasActive) {
        this.classList.remove('active');
        countEl.textContent = parseInt(countEl.textContent) - 1;
      } else {
        this.classList.add('active');
        countEl.textContent = parseInt(countEl.textContent) + 1;
      }
      
      // Send reaction to server
      fetch('../Database&Backend/reactions_api.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
        },
        body: JSON.stringify({
          entry_id: entryId,
          reaction_type: reactionType
        })
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.status !== 'success') {
          // Revert UI changes if server request failed
          if (wasActive) {
            this.classList.add('active');
            countEl.textContent = parseInt(countEl.textContent) + 1;
          } else {
            this.classList.remove('active');
            countEl.textContent = parseInt(countEl.textContent) - 1;
          }
          showNotification(data.message || 'Error updating reaction', 'error');
        }
      })
      .catch(error => {
        console.error('Error updating reaction:', error);
        // Revert UI changes if server request failed
        if (wasActive) {
          this.classList.add('active');
          countEl.textContent = parseInt(countEl.textContent) + 1;
        } else {
          this.classList.remove('active');
          countEl.textContent = parseInt(countEl.textContent) - 1;
        }
        showNotification('Error updating reaction', 'error');
      });
    });
  });
  
  // Add event listener for comment button
  const commentBtn = feedItem.querySelector('.comment-btn');
  const commentsSection = feedItem.querySelector('.feed-comments');
  const commentsContainer = feedItem.querySelector('.comments-container');
  
  commentBtn.addEventListener('click', function() {
    const entryId = this.dataset.entryId;
    
    // Toggle comments section
    if (commentsSection.style.display === 'none') {
      commentsSection.style.display = 'block';
      
      // Show loading state
      commentsContainer.innerHTML = '<div class="loading">Loading comments...</div>';
      
      // Fetch comments from server
      fetch(`../Database&Backend/comments_api.php?entry_id=${entryId}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (data.status === 'success' && data.data && data.data.length > 0) {
            // Clear loading state
            commentsContainer.innerHTML = '';
            
            // Add comments
            data.data.forEach(comment => {
              const commentEl = createCommentElement(comment);
              commentsContainer.appendChild(commentEl);
            });
          } else {
            // Show empty state
            commentsContainer.innerHTML = '<div class="empty-state">No comments yet. Be the first to comment!</div>';
          }
        })
        .catch(error => {
          console.error('Error loading comments:', error);
          commentsContainer.innerHTML = '<div class="error-state">Error loading comments. Please try again later.</div>';
        });
    } else {
      commentsSection.style.display = 'none';
    }
  });
  
  // Add event listener for comment submit
  const commentSubmit = feedItem.querySelector('.comment-submit');
  const commentInput = feedItem.querySelector('.comment-input');
  
  commentSubmit.addEventListener('click', function() {
    const entryId = commentBtn.dataset.entryId;
    const content = commentInput.value.trim();
    
    if (!content) {
      showNotification('Please write a comment', 'error');
      return;
    }
    
    // Disable button while submitting
    commentSubmit.disabled = true;
    commentSubmit.textContent = 'Posting...';
    
    // Send comment to server
    fetch('../Database&Backend/comments_api.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({
        entry_id: entryId,
        content: content
      })
    })
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      // Reset button
      commentSubmit.disabled = false;
      commentSubmit.textContent = 'Post';
      
      if (data.status === 'success') {
        // Clear input
        commentInput.value = '';
        
        // Add new comment to UI
        const commentEl = createCommentElement(data.data);
        
        // If empty state message is showing, clear it
        if (commentsContainer.querySelector('.empty-state')) {
          commentsContainer.innerHTML = '';
        }
        
        commentsContainer.appendChild(commentEl);
      } else {
        showNotification(data.message || 'Error posting comment', 'error');
      }
    })
    .catch(error => {
      console.error('Error posting comment:', error);
      showNotification('Error posting comment', 'error');
      
      // Reset button
      commentSubmit.disabled = false;
      commentSubmit.textContent = 'Post';
    });
  });
  
  // Add event listener for private reply button
  const privateReplyBtn = feedItem.querySelector('.private-reply-btn');
  
  privateReplyBtn.addEventListener('click', function() {
    const userId = this.dataset.userId;
    const entryId = this.dataset.entryId;
    const entryContent = entry.content;
    const entryMood = entry.mood_name;
    const entryEmoji = entry.emoji;
    // Pass reply_to_id in the URL
    window.location.href = `message.php?user=${userId}&reply_to_id=${entryId}&entry_content=${encodeURIComponent(entryContent)}&entry_mood=${encodeURIComponent(entryMood)}&entry_emoji=${encodeURIComponent(entryEmoji)}`;
  });
  
  return feedItem;
}

/**
 * Create a comment element
 */
function createCommentElement(comment) {
  const commentEl = document.createElement('div');
  commentEl.className = 'comment';
  commentEl.dataset.commentId = comment.comment_id;
  
  // Format comment date
  const commentDate = new Date(comment.created_at);
  const now = new Date();
  let timeAgo;
  
  const diffInSeconds = Math.floor((now - commentDate) / 1000);
  
  if (diffInSeconds < 60) {
    timeAgo = `${diffInSeconds} seconds ago`;
  } else if (diffInSeconds < 3600) {
    const minutes = Math.floor(diffInSeconds / 60);
    timeAgo = `${minutes} minute${minutes > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 86400) {
    const hours = Math.floor(diffInSeconds / 3600);
    timeAgo = `${hours} hour${hours > 1 ? 's' : ''} ago`;
  } else if (diffInSeconds < 604800) {
    const days = Math.floor(diffInSeconds / 86400);
    timeAgo = `${days} day${days > 1 ? 's' : ''} ago`;
  } else {
    timeAgo = commentDate.toLocaleDateString();
  }
  
  // Generate initials for avatar
  let initials = 'U';
  if (comment.full_name) {
    initials = comment.full_name.split(' ')
      .map(name => name.charAt(0))
      .join('')
      .toUpperCase()
      .substring(0, 2);
  } else if (comment.username) {
    initials = comment.username.substring(0, 2).toUpperCase();
  }
  
  // Create avatar element
  let avatarHtml;
  if (comment.profile_image && comment.profile_image !== 'default.png') {
    avatarHtml = `<img src="../images/profiles/${comment.profile_image}" alt="${comment.username}">`;
  } else {
    avatarHtml = initials;
  }
  
  // Create HTML structure
  commentEl.innerHTML = `
    <div class="comment-avatar">${avatarHtml}</div>
    <div class="comment-content">
      <div class="comment-header">
        <div class="comment-username">${comment.full_name || comment.username}</div>
        <div class="comment-time">${timeAgo}</div>
      </div>
      <div class="comment-text">${comment.content}</div>
    </div>
  `;
  
  return commentEl;
}

/**
 * Initialize recommendations
 */
function initRecommendations() {
  const recommendationsSection = document.querySelector('.recommendations');
  if (!recommendationsSection) return;
  recommendationsSection.innerHTML = '<div class="recommendations-loading">Loading recommendations...</div>';
  // Fetch recommendations for today from the API
  fetch('../Database&Backend/recommendations_api.php?type=today')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.data) {
        if (data.data.has_entry && data.data.recommendations) {
          recommendationsSection.innerHTML = '';
          const grouped = data.data.recommendations;
          for (const category in grouped) {
            const categoryElement = document.createElement('div');
            categoryElement.className = 'mood-category';
            const heading = document.createElement('h3');
            heading.textContent = category;
            categoryElement.appendChild(heading);
            grouped[category].forEach(rec => {
              const recommendation = document.createElement('div');
              recommendation.className = 'recommendation-item';
              const title = document.createElement('h4');
              title.textContent = rec.title;
              const content = document.createElement('p');
              content.textContent = rec.content;
              recommendation.appendChild(title);
              recommendation.appendChild(content);
              categoryElement.appendChild(recommendation);
            });
            recommendationsSection.appendChild(categoryElement);
          }
        } else if (data.data.entry && data.data.entry.created_at) {
          // Not journaled today, show last journal date and prompt
          const lastDate = new Date(data.data.entry.created_at).toLocaleDateString();
          recommendationsSection.innerHTML = `<div class="recommendations-empty"><p>Your last journal entry was on <strong>${lastDate}</strong>.<br>Journal today to get updated recommendations!</p></div>`;
        } else {
          recommendationsSection.innerHTML = `<div class="recommendations-empty"><p>No journal entries found. Add a journal entry to get personalized recommendations.</p></div>`;
        }
      } else {
        recommendationsSection.innerHTML = `<div class="recommendations-empty"><p>Error loading recommendations. Please try again later.</p></div>`;
      }
    });
} 

/**
 * Initialize mood trends section
 */
function initMoodTrends() {
  const moodTrendsSection = document.querySelector('.mood-insights');
  
  if (!moodTrendsSection) return;
  
  // Show loading state
  moodTrendsSection.innerHTML = '<div class="loading">Loading mood trends...</div>';
  
  // Fetch mood data from API
  fetch('../Database&Backend/moods_api.php?action=get_trends')
    .then(response => {
      if (!response.ok) {
        throw new Error('Network response was not ok');
      }
      return response.json();
    })
    .then(data => {
      if (data.status === 'success' && data.data) {
        // Clear loading state
        moodTrendsSection.innerHTML = '';
        
        // Create mood trends container
        const trendsContainer = document.createElement('div');
        trendsContainer.className = 'mood-trends-container';
        
        // Create header
        const header = document.createElement('div');
        header.className = 'mood-trends-header';
        header.innerHTML = `
          <h3>Your Mood Trends</h3>
          <p>Based on your recent journal entries</p>
        `;
        trendsContainer.appendChild(header);
        
        // Create mood frequency section
        const frequencySection = document.createElement('div');
        frequencySection.className = 'mood-frequency';
        
        // Get top moods
        const moodCounts = data.data.mood_counts || {};
        const topMoods = Object.entries(moodCounts)
          .sort((a, b) => b[1] - a[1])
          .slice(0, 5);
        
        if (topMoods.length > 0) {
          const frequencyHeader = document.createElement('h4');
          frequencyHeader.textContent = 'Your Top Moods';
          frequencySection.appendChild(frequencyHeader);
          
          const moodList = document.createElement('div');
          moodList.className = 'mood-list';
          
          topMoods.forEach(([mood, count]) => {
            const moodItem = document.createElement('div');
            moodItem.className = 'mood-item';
            
            // Get emoji for mood
            const emoji = data.data.mood_emojis[mood] || '😊';
            
            moodItem.innerHTML = `
              <div class="mood-item-emoji">${emoji}</div>
              <div class="mood-item-info">
                <div class="mood-item-name">${mood}</div>
                <div class="mood-item-count">${count} entries</div>
              </div>
              <div class="mood-item-bar">
                <div class="mood-item-bar-fill" style="width: ${(count / topMoods[0][1]) * 100}%"></div>
              </div>
            `;
            
            moodList.appendChild(moodItem);
          });
          
          frequencySection.appendChild(moodList);
        } else {
          frequencySection.innerHTML += `
            <p class="mood-empty">Not enough mood data yet. Add more journal entries to see your trends.</p>
          `;
        }
        trendsContainer.appendChild(frequencySection);
        // Restore the rest of the function to render mood timeline and insights
        // Create mood over time section
        const timeSection = document.createElement('div');
        timeSection.className = 'mood-time';
        // Get mood history
        const moodHistory = data.data.mood_history || [];
        if (moodHistory.length > 0) {
          const timeHeader = document.createElement('h4');
          timeHeader.textContent = 'Recent Mood Pattern';
          timeSection.appendChild(timeHeader);
          const moodTimeline = document.createElement('div');
          moodTimeline.className = 'mood-timeline';
          // Get last 7 entries or less
          const recentMoods = moodHistory.slice(-7);
          recentMoods.forEach(entry => {
            const moodPoint = document.createElement('div');
            moodPoint.className = 'mood-point';
            // Format date
            const date = new Date(entry.created_at);
            const formattedDate = date.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
            moodPoint.innerHTML = `
              <div class="mood-point-emoji">${entry.emoji}</div>
              <div class="mood-point-date">${formattedDate}</div>
            `;
            moodTimeline.appendChild(moodPoint);
          });
          timeSection.appendChild(moodTimeline);
        } else {
          timeSection.innerHTML += `
            <p class="mood-empty">Not enough mood data yet. Add more journal entries to see your trends over time.</p>
          `;
        }
        trendsContainer.appendChild(timeSection);
        // Add insights section
        if (data.data.insights) {
          const insightsSection = document.createElement('div');
          insightsSection.className = 'mood-insights-section';
          const insightsHeader = document.createElement('h4');
          insightsHeader.textContent = 'Insights';
          insightsSection.appendChild(insightsHeader);
          const insightsList = document.createElement('ul');
          insightsList.className = 'insights-list';
          data.data.insights.forEach(insight => {
            const insightItem = document.createElement('li');
            insightItem.textContent = insight;
            insightsList.appendChild(insightItem);
          });
          insightsSection.appendChild(insightsList);
          trendsContainer.appendChild(insightsSection);
        }
        moodTrendsSection.appendChild(trendsContainer);
      }
    })
    .catch(error => {
      console.error('Error loading mood trends:', error);
      moodTrendsSection.innerHTML = `
        <div class="error-state">
          <h3>Mood Trends</h3>
          <p>Error loading mood trends. Please try again later.</p>
        </div>
      `;
    });
} 

/**
 * Initialize role-specific features
 */
function initRoleSpecificFeatures() {
  // Load role-specific data
  loadRoleSpecificData();
}

/**
 * Load data specific to user roles
 */
function loadRoleSpecificData() {
  // Check if mentor section exists
  const mentorSection = document.getElementById('mentees-needing-attention');
  if (mentorSection) {
    loadMentorData();
  }
  
  // Check if counsellor section exists
  const priorityClientsSection = document.getElementById('priority-clients');
  const upcomingSessionsSection = document.getElementById('upcoming-sessions');
  if (priorityClientsSection && upcomingSessionsSection) {
    loadCounsellorData();
  }
}

/**
 * Load mentor data
 */
function loadMentorData() {
  const menteeSection = document.getElementById('mentees-needing-attention');
  
  fetch('../Database&Backend/mentor_api.php?action=get_mentees')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.mentees) {
        // Filter mentees needing attention
        const needAttention = data.mentees.filter(mentee => mentee.needs_attention);
        
        if (needAttention.length > 0) {
          let html = '<h4>Mentees Needing Attention</h4><ul class="quick-list">';
          
          needAttention.forEach(mentee => {
            html += `<li><a href="mentees.php?id=${mentee.user_id}">${mentee.full_name || mentee.username}</a></li>`;
          });
          
          html += '</ul>';
          menteeSection.innerHTML = html;
        } else {
          menteeSection.innerHTML = '<p>No mentees currently need attention</p>';
        }
      } else {
        menteeSection.innerHTML = '<p>Error loading mentee data</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching mentor data:', error);
      menteeSection.innerHTML = '<p>Error loading mentee data</p>';
    });
}

/**
 * Load counsellor data
 */
function loadCounsellorData() {
  const prioritySection = document.getElementById('priority-clients');
  const sessionsSection = document.getElementById('upcoming-sessions');
  
  // Load priority clients
  fetch('../Database&Backend/counsellor_api.php?action=get_clients')
    .then(response => response.json())
    .then(data => {
      if (data.status === 'success' && data.clients) {
        // Filter priority clients
        const priorityClients = data.clients.filter(client => client.priority);
        
        if (priorityClients.length > 0) {
          let html = '<h4>Priority Clients</h4><ul class="quick-list">';
          
          priorityClients.forEach(client => {
            html += `<li><a href="clients.php?id=${client.user_id}">${client.full_name || client.username}</a></li>`;
          });
          
          html += '</ul>';
          prioritySection.innerHTML = html;
        } else {
          prioritySection.innerHTML = '<p>No priority clients at this time</p>';
        }
        
        // Filter clients with upcoming sessions
        const upcomingSessions = data.clients.filter(client => client.upcoming_session);
        
        if (upcomingSessions.length > 0) {
          let html = '<h4>Today\'s Sessions</h4><ul class="quick-list">';
          
          upcomingSessions.forEach(client => {
            html += `<li><a href="clients.php?id=${client.user_id}">${client.full_name || client.username}</a></li>`;
          });
          
          html += '</ul>';
          sessionsSection.innerHTML = html;
        } else {
          sessionsSection.innerHTML = '<p>No sessions scheduled for today</p>';
        }
      } else {
        prioritySection.innerHTML = '<p>Error loading client data</p>';
        sessionsSection.innerHTML = '<p>Error loading session data</p>';
      }
    })
    .catch(error => {
      console.error('Error fetching counsellor data:', error);
      prioritySection.innerHTML = '<p>Error loading client data</p>';
      sessionsSection.innerHTML = '<p>Error loading session data</p>';
    });
}

/**
 * Add CSS styles for quick-list
 */
function addQuickListStyles() {
  const style = document.createElement('style');
  style.textContent = `
    .quick-list {
      list-style: none;
      padding: 0;
      margin: 8px 0;
    }
    
    .quick-list li {
      padding: 4px 0;
      border-bottom: 1px solid #e0d0d0;
      font-size: 0.9rem;
    }
    
    .quick-list li:last-child {
      border-bottom: none;
    }
    
    .quick-list a {
      color: #c48484;
      text-decoration: none;
    }
    
    .quick-list a:hover {
      text-decoration: underline;
    }
    
    .admin-quick-actions h4,
    .mentor-quick-actions h4,
    .counsellor-quick-actions h4 {
      margin: 16px 0 4px 0;
      font-size: 0.9rem;
      color: #333;
    }
  `;
  document.head.appendChild(style);
} 