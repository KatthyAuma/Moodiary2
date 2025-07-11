# Dynamism and Backend Integration in Moodiary2

Moodiary2 achieves a dynamic, interactive user experience by tightly integrating frontend JavaScript with backend PHP APIs and a MySQL database. This allows for real-time updates, seamless user interactions, and a modern web app feel.

## How It Works: The Dynamic Flow

1. **User Action (Frontend)**
   - The user interacts with the UI (e.g., submits a journal entry, sends a friend request, searches for users).

2. **JavaScript Event Handling**
   - JavaScript (mainly in `dashboard.js`) listens for these actions (e.g., button clicks, form submissions).
   - It collects input data and sends it to the backend using `fetch()` with AJAX (asynchronous HTTP requests).

3. **API Endpoint (PHP Backend)**
   - The request is received by a PHP API file (e.g., `journal_api.php`, `friends_api.php`, `admin_api.php`).
   - The PHP script processes the request, interacts with the database (using PDO), and returns a JSON response.

4. **Database Interaction**
   - The PHP API performs SQL queries (SELECT, INSERT, UPDATE, DELETE) on the MySQL database, using prepared statements for security.

5. **Response and UI Update**
   - The backend returns a JSON response (success, error, or data).
   - JavaScript receives the response and updates the UI dynamically (e.g., shows a notification, refreshes a list, displays new data).

### Example: Submitting a Journal Entry (Full Flow)

**JavaScript (dashboard.js):**
```js
function submitJournalEntry(journalData) {
  return fetch('../Database&Backend/journal_api.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
    },
    body: JSON.stringify(journalData)
  })
  .then(response => response.json())
  .catch(error => {
    console.error('Error submitting journal entry:', error);
    return { status: 'error', message: 'Failed to submit journal entry' };
  });
}
```
- This function is called when the user submits a journal entry. It sends the data to the backend API and returns the response.

**PHP (journal_api.php):**
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    // Validate and sanitize $data ...
    $stmt = $conn->prepare('INSERT INTO journal_entries (user_id, mood_id, content, is_public, created_at) VALUES (?, ?, ?, ?, NOW())');
    $stmt->execute([
        $_SESSION['user_id'],
        $data['mood_id'],
        $data['content'],
        $data['is_public'] ? 1 : 0
    ]);
    echo json_encode(['status' => 'success']);
    exit;
}
```
- This PHP code receives the POST request, inserts the journal entry into the database, and returns a JSON response.

---

## Example 2: Fetching Friends List

- **User** opens the friends tab.
- **JS** (`dashboard.js`, `initFriendsList()`):
  - Calls `fetchFriends()` which sends a GET request to `friends_api.php`.
- **PHP** (`friends_api.php`):
  - Queries the `friendships` and `users` tables to get the user's friends.
  - Returns a JSON array of friends.
- **JS**: Renders the friends list dynamically in the UI.

---

## Main Files Involved

- **Frontend JS:**
  - `dashboard.js` (main dashboard interactivity)
  - `sidebar.js`, `auth.js` (navigation, authentication)
- **Backend PHP APIs:**
  - `journal_api.php`, `friends_api.php`, `admin_api.php`, `comments_api.php`, etc.
- **Database:**
  - MySQL tables defined in `schema.sql`
- **HTML/PHP UI:**
  - `home.php`, `admin.php`, `index.php`, etc.

---

## Summary

This architecture allows Moodiary2 to provide a responsive, modern web experience. User actions trigger JavaScript, which communicates with PHP APIs, which in turn interact with the database and return results that update the UI in real time. 