<?php
// Include the header
include_once('header.php');

// Set page title
$pageTitle = "Messages";
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="description" content="Moodiary - Track your moods, share vibes, heal together" />
  <meta name="theme-color" content="#d4b8a8" />
  <title>Moodiary - Messages</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Montserrat:wght@400;500;600;700&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="style.css" />
  <style>
    .messages-container {
      display: flex;
      height: calc(100vh - 100px);
      margin: 20px;
    }

    .contacts-list {
      width: 300px;
      background-color: #f5f5f5;
      border-radius: 10px 0 0 10px;
      overflow-y: auto;
      border-right: 1px solid #ddd;
    }

    .contacts-header {
      padding: 15px;
      border-bottom: 1px solid #ddd;
    }

    .contacts-header h2 {
      margin: 0;
      font-size: 1.2rem;
    }

    .contacts-search {
      padding: 10px 15px;
      border-bottom: 1px solid #ddd;
    }

    .contacts-search input {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #ddd;
      border-radius: 20px;
      font-size: 0.9rem;
    }

    .contact-item {
      display: flex;
      align-items: center;
      padding: 12px 15px;
      cursor: pointer;
      transition: background-color 0.2s;
      border-bottom: 1px solid #eee;
      position: relative;
      /* For unread indicator */
    }

    .unread-indicator {
      position: absolute;
      right: 18px;
      top: 18px;
      background: red;
      color: white;
      border-radius: 50%;
      min-width: 20px;
      height: 20px;
      font-size: 0.8rem;
      display: flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
      font-weight: bold;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.1);
    }

    .contact-item:hover {
      background-color: #e9e9e9;
    }

    .contact-item.active {
      background-color: #d4b8a8;
      color: white;
    }

    .contact-avatar {
      width: 40px;
      height: 40px;
      border-radius: 50%;
      background-color: #ccc;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-right: 10px;
      font-weight: bold;
      color: white;
    }

    .contact-info {
      flex: 1;
    }

    .contact-name {
      font-weight: 500;
      margin-bottom: 3px;
    }

    .contact-status {
      font-size: 0.8rem;
      color: #666;
    }

    .contact-item.active .contact-status {
      color: #f5f5f5;
    }

    .relationship-badge {
      font-size: 0.7rem;
      padding: 2px 6px;
      border-radius: 10px;
      margin-left: 5px;
      text-transform: capitalize;
    }

    .relationship-friend {
      background-color: #4caf50;
      color: white;
    }

    .relationship-mentor {
      background-color: #2196f3;
      color: white;
    }

    .relationship-counsellor {
      background-color: #9c27b0;
      color: white;
    }

    .relationship-family {
      background-color: #ff9800;
      color: white;
    }

    .chat-area {
      flex: 1;
      display: flex;
      flex-direction: column;
      background-color: #fff;
      border-radius: 0 10px 10px 0;
    }

    .chat-header {
      padding: 15px;
      border-bottom: 1px solid #ddd;
      display: flex;
      align-items: center;
    }

    .chat-header h2 {
      margin: 0;
      font-size: 1.2rem;
    }

    .chat-messages {
      flex: 1;
      padding: 15px;
      overflow-y: auto;
      display: flex;
      flex-direction: column;
    }

    .message {
      max-width: 70%;
      margin-bottom: 15px;
      padding: 10px 15px;
      border-radius: 10px;
      position: relative;
    }

    .message-sent {
      align-self: flex-end;
      background-color: #fff;
      color: #222;
      border-bottom-right-radius: 0;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }

    .message-received {
      align-self: flex-start;
      background-color: #f1f1f1;
      border-bottom-left-radius: 0;
      box-shadow: 0 1px 4px rgba(0, 0, 0, 0.04);
    }

    .message-time {
      font-size: 0.7rem;
      opacity: 0.7;
      margin-top: 5px;
      text-align: right;
    }

    .message-input {
      padding: 15px;
      border-top: 1px solid #ddd;
      display: flex;
      align-items: center;
    }

    .message-input textarea {
      flex: 1;
      padding: 10px 15px;
      border: 1px solid #ddd;
      border-radius: 20px;
      resize: none;
      height: 40px;
      font-family: inherit;
      margin-right: 10px;
    }

    .message-input button {
      padding: 8px 15px;
      background-color: #d4b8a8;
      color: white;
      border: none;
      border-radius: 20px;
      cursor: pointer;
      font-weight: 500;
    }

    .message-input button:hover {
      background-color: #c4a898;
    }

    .message-input button:disabled {
      background-color: #ccc;
      cursor: not-allowed;
    }

    .empty-state {
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #888;
      text-align: center;
      padding: 20px;
    }

    .empty-state p {
      margin: 10px 0;
    }

    .empty-state .btn-primary {
      margin-top: 15px;
    }

    .loading {
      display: flex;
      align-items: center;
      justify-content: center;
      height: 100%;
      color: #888;
    }

    .reply-to {
      background-color: #f5f5f5;
      padding: 10px;
      margin-bottom: 10px;
      border-radius: 5px;
      border-left: 3px solid #d4b8a8;
      font-size: 0.9rem;
    }

    .reply-to .close {
      float: right;
      cursor: pointer;
      font-weight: bold;
    }

    .message-content-row {
      display: flex;
      align-items: flex-end;
      gap: 6px;
    }

    .reply-arrow-btn {
      background: none;
      border: none;
      font-size: 1.2rem;
      cursor: pointer;
      margin-left: 6px;
      margin-right: 6px;
      padding: 0;
      color: #d4b8a8;
      transition: color 0.2s;
    }

    .reply-arrow-btn:hover {
      color: #b08a6b;
    }

    .message-sent .reply-arrow-btn {
      order: -1;
      margin-left: 0;
      margin-right: 8px;
    }

    .message-received .reply-arrow-btn {
      order: 1;
      margin-left: 8px;
      margin-right: 0;
    }

    .relationship-friend.message-received {
      background-color: #e8f5e9;
      color: #222;
    }

    .relationship-mentor.message-received {
      background-color: #e3f2fd;
      color: #222;
    }

    .relationship-counsellor.message-received {
      background-color: #f3e5f5;
      color: #222;
    }

    .relationship-family.message-received {
      background-color: #fff3e0;
      color: #222;
    }
  </style>
</head>

<body>
  <div class="main-app active">
    <div class="menu-button" id="menu-button">
      <span></span><span></span><span></span>
    </div>

    <div class="messages-container">
      <!-- Contacts List -->
      <div class="contacts-list">
        <div class="contacts-header">
          <h2>Messages</h2>
        </div>
        <div class="contacts-search">
          <input type="text" placeholder="Search friends..." id="contact-search">
        </div>
        <div id="contacts-container">
          <!-- Contacts will be loaded dynamically -->
          <div class="loading">Loading contacts...</div>
        </div>
      </div>

      <!-- Chat Area -->
      <div class="chat-area">
        <div id="empty-chat" class="empty-state">
          <p>Select a friend to start messaging</p>
          <p>Or find new friends to connect with</p>
          <button class="btn-primary" id="find-friends-btn">Find Friends</button>
        </div>

        <div id="active-chat" style="display: none; height: 100%; flex-direction: column;">
          <div class="chat-header">
            <div class="contact-avatar" id="chat-avatar"></div>
            <div class="contact-info">
              <h2 id="chat-name"></h2>
              <div id="chat-relationship"></div>
            </div>
          </div>

          <div class="chat-messages" id="chat-messages">
            <!-- Messages will be loaded dynamically -->
          </div>

          <div class="message-input">
            <textarea id="message-text" placeholder="Type a message..."></textarea>
            <button id="send-message">Send</button>
          </div>
        </div>
      </div>
    </div>

    <div class="sidebar" id="sidebar">
      <div class="sidebar-item">
        <a href="home.php">Home</a>
      </div>
      <div class="sidebar-item active">
        <a href="message.php">Messages</a>
      </div>
      <div class="sidebar-item">
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
            initMessages();
          }
        })
        .catch(error => {
          console.error('Error checking session:', error);
          window.location.href = 'signin.html';
        });

      function initMessages() {
        const contactsContainer = document.getElementById('contacts-container');
        const contactSearch = document.getElementById('contact-search');
        const emptyChat = document.getElementById('empty-chat');
        const activeChat = document.getElementById('active-chat');
        const chatAvatar = document.getElementById('chat-avatar');
        const chatName = document.getElementById('chat-name');
        const chatRelationship = document.getElementById('chat-relationship');
        const chatMessages = document.getElementById('chat-messages');
        const messageText = document.getElementById('message-text');
        const sendButton = document.getElementById('send-message');
        const findFriendsBtn = document.getElementById('find-friends-btn');

        let currentUser = null;
        let currentChat = null;
        let replyingTo = null;

        // Load contacts
        loadContacts();

        // Search contacts
        contactSearch.addEventListener('input', function() {
          const searchTerm = this.value.toLowerCase().trim();
          const contacts = document.querySelectorAll('.contact-item');

          contacts.forEach(contact => {
            const name = contact.querySelector('.contact-name').textContent.toLowerCase();
            if (name.includes(searchTerm) || searchTerm === '') {
              contact.style.display = '';
            } else {
              contact.style.display = 'none';
            }
          });
        });

        // Find friends button
        if (findFriendsBtn) {
          findFriendsBtn.addEventListener('click', function() {
            window.location.href = 'home.php';
          });
        }

        // Send message
        sendButton.addEventListener('click', sendMessage);
        messageText.addEventListener('keypress', function(e) {
          if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
          }
        });

        // Check for reply_to_id in URL
        const urlParams = new URLSearchParams(window.location.search);
        const replyToId = urlParams.get('reply_to_id');
        if (replyToId) {
          // Show the reply reference above the input
          const entryContent = decodeURIComponent(urlParams.get('entry_content') || '');
          const entryMood = decodeURIComponent(urlParams.get('entry_mood') || '');
          const entryEmoji = decodeURIComponent(urlParams.get('entry_emoji') || '');
          replyingTo = {
            message_id: replyToId,
            content: `${entryEmoji ? entryEmoji + ' ' : ''}${entryMood ? entryMood : ''}\n${entryContent}`
          };
          showReplyTo(`${entryEmoji ? entryEmoji + ' ' : ''}${entryMood ? entryMood : ''}\n${entryContent}`);
        }

        function sendMessage() {
          const text = messageText.value.trim();
          if (!text || !currentChat) return;
          sendButton.disabled = true;
          // If replying to a journal entry, send reply_to_journal_id
          const urlParams = new URLSearchParams(window.location.search);
          const replyToJournalId = urlParams.get('reply_to_id') || (replyingTo && replyingTo.isJournal ? replyingTo.message_id : null);
          const messageData = {
            receiver_id: currentChat.user_id,
            content: text,
            reply_to: replyingTo && !replyToJournalId ? {
              message_id: replyingTo.message_id
            } : null,
            reply_to_journal_id: replyToJournalId || null
          };
          const now = new Date();
          const tempMessage = {
            message_id: 'temp_' + Date.now(),
            sender_id: currentUser.user_id,
            recipient_id: currentChat.user_id,
            content: text,
            created_at: now.toISOString(),
            is_read: false,
            reply_to: replyingTo && !replyToJournalId ? {
              message_id: replyingTo.message_id,
              content: replyingTo.content
            } : null,
            reply_to_journal: replyToJournalId ? {
              entry_id: replyToJournalId,
              content: urlParams.get('entry_content') || '',
              mood_name: urlParams.get('entry_mood') || '',
              emoji: urlParams.get('entry_emoji') || ''
            } : null
          };
          addMessageToChat(tempMessage);
          messageText.value = '';
          clearReplyTo();
          // Remove reply_to_id from URL after sending
          const url = new URL(window.location);
          url.searchParams.delete('reply_to_id');
          url.searchParams.delete('entry_content');
          url.searchParams.delete('entry_mood');
          url.searchParams.delete('entry_emoji');
          window.history.replaceState({}, document.title, url.pathname + url.search);
          fetch('../Database&Backend/messages_api.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify(messageData)
            })
            .then(response => {
              if (!response.ok) {
                throw new Error('Network response was not ok');
              }
              return response.json();
            })
            .then(data => {
              sendButton.disabled = false;
              if (data.status === 'success') {
                const tempElement = document.querySelector(`[data-message-id="temp_${tempMessage.message_id}"]`);
                if (tempElement) {
                  tempElement.setAttribute('data-message-id', data.data.message_id);
                }
              } else {
                showNotification(data.message || 'Error sending message', 'error');
              }
            })
            .catch(error => {
              console.error('Error sending message:', error);
              showNotification('Error sending message', 'error');
              sendButton.disabled = false;
            });
        }

        function loadContacts() {
          contactsContainer.innerHTML = '<div class="loading">Loading contacts...</div>';

          // Get current user first
          fetch('../Database&Backend/check_session.php')
            .then(response => {
              if (!response.ok) throw new Error('Network response was not ok');
              return response.json();
            })
            .then(data => {
              if (data.status === 'success' && data.logged_in) {
                currentUser = {
                  user_id: data.user_id,
                  username: data.username,
                  full_name: data.full_name,
                  email: data.email,
                  profile_image: data.profile_image
                };

                // Now fetch friends
                return fetch('../Database&Backend/friends_api.php');
              } else {
                throw new Error('User not logged in');
              }
            })
            .then(response => {
              if (!response.ok) throw new Error('Network response was not ok');
              return response.json();
            })
            .then(data => {
              if (data.status === 'success' && data.data && data.data.length > 0) {
                contactsContainer.innerHTML = '';

                data.data.forEach(friend => {
                  const contactElement = createContactElement(friend);
                  contactsContainer.appendChild(contactElement);
                });

                // Check if URL has a user parameter
                const urlParams = new URLSearchParams(window.location.search);
                const userParam = urlParams.get('user');

                if (userParam) {
                  // Find the contact with matching username or user_id
                  const contact = data.data.find(f =>
                    f.username === userParam || f.user_id == userParam // allow numeric match
                  );
                  if (contact) {
                    const contactElement = document.querySelector(`[data-user-id="${contact.user_id}"]`);
                    if (contactElement) {
                      // Scroll into view and click
                      contactElement.scrollIntoView({
                        behavior: 'smooth',
                        block: 'center'
                      });
                      setTimeout(() => contactElement.click(), 100);
                    }
                  }
                }
                // Always check for private reply params after contacts are loaded
                const entryId = urlParams.get('entry_id');
                const entryContent = decodeURIComponent(urlParams.get('entry_content') || '');
                const entryMood = decodeURIComponent(urlParams.get('entry_mood') || '');
                const entryEmoji = decodeURIComponent(urlParams.get('entry_emoji') || '');
                if (entryId && entryContent) {
                  setTimeout(() => {
                    // Do NOT pre-fill the message input, just show the indicator
                    // messageText.value = privateReplyTemplate; // REMOVE THIS LINE
                    messageText.value = '';
                    messageText.focus();
                    // Create a visual indicator for private reply
                    const privateReplyIndicator = document.createElement('div');
                    privateReplyIndicator.className = 'private-reply-indicator';
                    privateReplyIndicator.innerHTML = `
                      <span class="close">&times;</span>
                      <div class="private-reply-header">
                        <span class="private-reply-icon">✉️</span>
                        <strong>Private Reply to Journal Entry</strong>
                      </div>
                      <div class="private-reply-content">
                        ${entryEmoji ? `<span class="private-reply-emoji">${entryEmoji}</span>` : ''}
                        ${entryMood ? `<span class="private-reply-mood">${entryMood}</span>` : ''}
                        <p class="private-reply-text">${entryContent}</p>
                      </div>
                    `;
                    // Add close button functionality
                    privateReplyIndicator.querySelector('.close').addEventListener('click', function() {
                      privateReplyIndicator.remove();
                      messageText.value = '';
                    });
                    // Insert before message input
                    const messageInput = document.querySelector('.message-input');
                    messageInput.insertBefore(privateReplyIndicator, messageInput.firstChild);
                  }, 600);
                }
              } else {
                contactsContainer.innerHTML = `
                  <div class="empty-state">
                    <p>You don't have any friends yet.</p>
                    <button class="btn-primary" id="find-friends-empty">Find Friends</button>
                  </div>
                `;

                const findFriendsEmpty = document.getElementById('find-friends-empty');
                if (findFriendsEmpty) {
                  findFriendsEmpty.addEventListener('click', function() {
                    window.location.href = 'home.php';
                  });
                }
              }
            })
            .catch(error => {
              console.error('Error loading contacts:', error);
              contactsContainer.innerHTML = `
                <div class="error-state">
                  <p>Error loading contacts. Please try again later.</p>
                </div>
              `;
            });
        }

        function createContactElement(friend) {
          const contactElement = document.createElement('div');
          contactElement.className = 'contact-item';
          contactElement.setAttribute('data-user-id', friend.user_id);

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

          // Create relationship badge
          const relationshipType = friend.relationship_type || 'friend';
          const relationshipBadge = `<span class="relationship-badge relationship-${relationshipType}">${relationshipType}</span>`;

          // Create HTML structure
          contactElement.innerHTML = `
            <div class="contact-avatar">${initials}</div>
            <div class="contact-info">
              <div class="contact-name">${friend.full_name || friend.username} ${relationshipBadge}</div>
              <div class="contact-status">
                ${friend.mood_name ? `${friend.emoji} ${friend.mood_name}` : 'No recent mood'}
              </div>
            </div>
          `;

          // Unread indicator
          const unreadCount = getUnreadCount(friend.user_id);
          if (unreadCount > 0) {
            const unreadEl = document.createElement('div');
            unreadEl.className = 'unread-indicator';
            unreadEl.textContent = unreadCount > 99 ? '99+' : unreadCount;
            contactElement.appendChild(unreadEl);
          }

          // Add click event to open chat
          contactElement.addEventListener('click', function() {
            // Remove active class from all contacts
            document.querySelectorAll('.contact-item').forEach(item => {
              item.classList.remove('active');
            });

            // Add active class to clicked contact
            this.classList.add('active');

            // Show chat area
            emptyChat.style.display = 'none';
            activeChat.style.display = 'flex';

            // Set current chat
            currentChat = friend;

            // Update chat header
            chatAvatar.textContent = initials;
            chatName.textContent = friend.full_name || friend.username;

            // Set relationship badge
            chatRelationship.innerHTML = relationshipBadge;

            // Load messages
            loadMessages(friend.user_id);
            // Remove unread indicator on open
            setUnreadCount(friend.user_id, 0);
            const unreadEl = this.querySelector('.unread-indicator');
            if (unreadEl) unreadEl.remove();
          });

          return contactElement;
        }

        function loadMessages(friendId) {
          chatMessages.innerHTML = '<div class="loading">Loading messages...</div>';

          fetch(`../Database&Backend/messages_api.php?friend_id=${friendId}`)
            .then(response => {
              if (!response.ok) throw new Error('Network response was not ok');
              return response.json();
            })
            .then(data => {
              chatMessages.innerHTML = '';
              let unread = 0;
              if (data.status === 'success' && data.data && data.data.length > 0) {
                data.data.forEach(message => {
                  addMessageToChat(message);
                  if (!message.is_read && message.sender_id !== currentUser.user_id) unread++;
                });
                setUnreadCount(friendId, unread);

                // Scroll to bottom
                chatMessages.scrollTop = chatMessages.scrollHeight;

                // Mark messages as read
                markMessagesAsRead(friendId);
              } else {
                setUnreadCount(friendId, 0);
                chatMessages.innerHTML = `
                  <div class="empty-state">
                    <p>No messages yet.</p>
                    <p>Start the conversation!</p>
                  </div>
                `;
              }
            })
            .catch(error => {
              console.error('Error loading messages:', error);
              chatMessages.innerHTML = `
                <div class="error-state">
                  <p>Error loading messages. Please try again later.</p>
                </div>
              `;
            });
        }

        function addMessageToChat(message) {
          const messageElement = document.createElement('div');
          messageElement.className = 'message';
          messageElement.setAttribute('data-message-id', message.message_id);
          const isSent = message.sender_id === currentUser.user_id;
          messageElement.classList.add(isSent ? 'message-sent' : 'message-received');
          // Relationship-based color for received messages
          if (!isSent && currentChat && currentChat.relationship_type) {
            messageElement.classList.add('relationship-' + currentChat.relationship_type);
          }
          // Format date
          const date = new Date(message.created_at);
          const formattedTime = date.toLocaleTimeString([], {
            hour: '2-digit',
            minute: '2-digit'
          });
          const formattedDate = date.toLocaleDateString();
          const today = new Date().toLocaleDateString();
          const displayDate = formattedDate === today ? formattedTime : `${formattedDate} ${formattedTime}`;
          // Check if it's a reply to a message
          let replyHtml = '';
          if (message.reply_to && message.reply_to.content) {
            replyHtml = `
              <div class="private-reply-indicator" style="margin-bottom: 6px;">
                <div class="private-reply-header">
                  <span class="private-reply-icon">✉️</span>
                  <strong>Private Reply to Message</strong>
                </div>
                <div class="private-reply-content">
                  <p class="private-reply-text">${message.reply_to.content}</p>
                </div>
              </div>
            `;
          }
          // Check if it's a reply to a journal entry
          if (message.reply_to_journal) {
            let ref = message.reply_to_journal;
            if (typeof ref === 'string') {
              try {
                ref = JSON.parse(ref);
              } catch (e) {
                ref = null;
              }
            }
            if (ref && ref.content) {
              replyHtml = `
                <div class="private-reply-indicator" style="margin-bottom: 6px;">
                  <div class="private-reply-header">
                    <span class="private-reply-icon">✉️</span>
                    <strong>Private Reply to Journal Entry</strong>
                  </div>
                  <div class="private-reply-content">
                    ${ref.emoji ? `<span class="private-reply-emoji">${ref.emoji}</span>` : ''}
                    ${ref.mood_name ? `<span class="private-reply-mood">${ref.mood_name}</span>` : ''}
                    <p class="private-reply-text">${ref.content}</p>
                  </div>
                </div>
              `;
            }
          }
          // Add reply arrow button OUTSIDE the bubble, right for received, left for sent
          let replyBtn = `<button class="reply-arrow-btn" title="Reply to this message">↩️</button>`;
          let contentRow = '';
          if (isSent) {
            contentRow = `${replyBtn}<div class="message-content">${message.content}</div>`;
          } else {
            contentRow = `<div class="message-content">${message.content}</div>${replyBtn}`;
          }
          messageElement.innerHTML = `
            ${replyHtml}
            <div class="message-content-row">
              ${contentRow}
            </div>
            <div class="message-time">${displayDate}</div>
          `;
          // Add reply arrow event
          if (!isSent) {
            const replyArrow = messageElement.querySelector('.reply-arrow-btn');
            if (replyArrow) {
              replyArrow.addEventListener('click', function(e) {
                e.stopPropagation();
                replyingTo = {
                  message_id: message.message_id,
                  content: message.content
                };
                showReplyTo(message.content);
              });
            }
          }
          // Add context menu for reply (right click)
          messageElement.addEventListener('contextmenu', function(e) {
            e.preventDefault();
            replyingTo = {
              message_id: message.message_id,
              content: message.content
            };
            showReplyTo(message.content);
          });
          chatMessages.appendChild(messageElement);
          chatMessages.scrollTop = chatMessages.scrollHeight;
        }

        function showReplyTo(content) {
          // Remove existing reply-to if any
          const existingReplyTo = document.querySelector('.reply-to');
          if (existingReplyTo) {
            existingReplyTo.remove();
          }

          // Create reply-to element
          const replyToElement = document.createElement('div');
          replyToElement.className = 'reply-to';

          // Truncate content if too long
          const truncatedContent = content.length > 50 ? content.substring(0, 50) + '...' : content;

          replyToElement.innerHTML = `
            <span class="close">&times;</span>
            <strong>Replying to:</strong> ${truncatedContent}
          `;

          // Add close button functionality
          replyToElement.querySelector('.close').addEventListener('click', clearReplyTo);

          // Insert before message input
          const messageInput = document.querySelector('.message-input');
          messageInput.insertBefore(replyToElement, messageInput.firstChild);

          // Focus on message input
          messageText.focus();
          persistReplyReference();
        }

        function clearReplyTo() {
          replyingTo = null;
          const replyToElement = document.querySelector('.reply-to');
          if (replyToElement) {
            replyToElement.remove();
          }
          persistReplyReference();
        }

        function markMessagesAsRead(friendId) {
          fetch(`../Database&Backend/messages_api.php?mark_read=${friendId}`, {
              method: 'POST'
            })
            .then(response => response.json())
            .catch(error => {
              console.error('Error marking messages as read:', error);
            });
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

        // --- Unread count persistence ---
        function getUnreadCount(userId) {
          const unread = localStorage.getItem('unread_' + userId);
          return unread ? parseInt(unread, 10) : 0;
        }

        function setUnreadCount(userId, count) {
          localStorage.setItem('unread_' + userId, count);
        }
        // --- Reply reference persistence ---
        // Save reply reference to localStorage
        function persistReplyReference() {
          if (replyingTo) {
            localStorage.setItem('reply_ref', JSON.stringify(replyingTo));
          } else {
            localStorage.removeItem('reply_ref');
          }
        }
        // Restore reply reference from localStorage
        function restoreReplyReference() {
          const ref = localStorage.getItem('reply_ref');
          if (ref) {
            try {
              const obj = JSON.parse(ref);
              replyingTo = obj;
              showReplyTo(obj.content);
            } catch (e) {
              replyingTo = null;
            }
          }
        }
        // Call restore on load
        restoreReplyReference();
      }
    });
  </script>
</body>

</html>