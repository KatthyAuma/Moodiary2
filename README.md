# Moodiary2 - Mood Tracking and Mental Health Support Application

## System Analysis and Explanation

### Home Page/Index Page (Landing Page)

The landing page of Moodiary2 is implemented in `src/UI/index.php` (lines 1-115). This page serves as the entry point for new users.

#### Parts of the Index/Landing Page:

1. **Primary Call to Action**:

   - Sign-up form (lines 32-48) with fields for email, username, password, and full name
   - Sign-up button (line 50) that submits user data to the backend

2. **Logo and Tagline**:

   - The logo is displayed in the `.logo` div (lines 27-30)
   - The logo text is "Moodiary" with the tagline "Track moods, share vibes, heal together ❤️"

3. **Visual Elements**:

   - Two images are displayed at the bottom of the page:
     - `Catchingup.png` (line 57) - positioned at the bottom-left
     - `Loving.png` (line 58) - positioned at the right side

4. **Authentication Flow**:

   - The page checks if a user is already logged in (lines 4-8)
   - If logged in, it redirects to home.php
   - If not, it displays the sign-up form
   - A link to the sign-in page is provided (lines 52-55)

5. **Registration Functionality**:
   - JavaScript code (lines 61-96) handles the form submission
   - Validates required fields and password length
   - Sends data to `../Database&Backend/signup.php`
   - Redirects to home.php upon successful registration

### User Registration System

#### User Types in the System:

The system supports multiple user types, defined in the `roles` table (schema.sql lines 170-173):

1. **Admin**: Full access to all features and system management
2. **User**: Regular users with standard access
3. **Mentor**: Users who can guide other users
4. **Counsellor**: Professional counsellors who can provide mental health support

#### Registration Process:

1. **Sign-up Form** (`src/UI/index.php`):

   - Collects email, username, password, and full name
   - Validates input client-side (lines 71-79)

2. **Backend Processing** (`src/Database&Backend/signup.php`):

   - Validates all inputs (lines 31-61)
   - Checks for existing usernames and emails (lines 80-96)
   - Hashes the password (line 99)
   - Creates a new user record (lines 105-111)
   - Assigns the default 'user' role (lines 114-123)
   - Sets up the user session (lines 129-134)

3. **Session Management**:
   - Sessions are initialized in `header.php` (lines 2-14)
   - User information is stored in session variables:
     - `$_SESSION['user_id']`
     - `$_SESSION['username']`
     - `$_SESSION['full_name']`
     - `$_SESSION['email']`
     - `$_SESSION['roles']`
     - `$_SESSION['logged_in']`

#### Session Implementation:

The system uses PHP sessions to maintain user state across pages:

1. **Session Initialization**:

   - `session_start()` is called in `header.php` (line 2)
   - Session variables are set during login (`login.html` lines 88-94) and signup (`signup.php` lines 129-134)

2. **Session Checking**:

   - `check_session.php` verifies if a user is logged in (lines 20-22)
   - It retrieves user data including roles from the database (lines 31-37)
   - Updates session with current user information (lines 58-70)

3. **Session Display**:

   - The header displays the username on all pages (`src/UI/header.php` lines 49-50)
   - User's profile image or initials are shown (lines 43-48)
   - User roles are displayed if available (lines 51-53)

4. **Session Protection**:
   - Non-public pages are protected from unauthorized access (`header.php` lines 17-22)
   - Session timeout is set to 24 hours (`config.php` lines 17-19)

### Admin Functionality

The admin dashboard is implemented in `src/UI/admin.php` with backend support in `src/Database&Backend/admin_api.php`.

#### Admin Privileges:

1. **User Management**:

   - View all users in the system (admin.php lines 57-67)
   - Search for specific users (lines 52-55)
   - Update user details (admin_api.php lines 301-364)
   - Change user status (active/disabled) (admin_api.php lines 365-418)
   - Assign roles to users (admin_api.php lines 419-554)
   - Delete users (admin_api.php lines 590-699)

2. **System Analytics**:

   - View system statistics (admin.php lines 71-83)
   - Track total users, active users, journal entries, and new users
   - Access detailed reports (admin_api.php lines 555-589)

3. **System Settings**:
   - Configure site name and description (admin.php lines 89-98)
   - Control user registration settings (admin.php lines 100-110)

#### User Table Display:

The admin dashboard displays all users in an HTML table (`admin.php` lines 57-67) with the following columns:

- User ID
- Username
- Full Name
- Email
- Roles
- Last Login
- Actions (edit, disable, delete)

The table is populated dynamically via JavaScript that fetches data from `admin_api.php`.

#### User Search Functionality:

Admins can search for users by name, email, or username:

- Search input field (admin.php line 53)
- Search button (line 54)
- Backend search function (admin_api.php lines 700-729)

#### User Update Process:

1. Admin clicks on edit button for a user
2. A modal form is displayed with user details
3. Changes are submitted to `admin_api.php` with action "update_user"
4. The backend validates and applies changes (admin_api.php lines 301-364)
5. The user table is refreshed to show updated information

#### User Disable Functionality:

1. Admin clicks on disable/enable button
2. A confirmation dialog is shown
3. Request is sent to `admin_api.php` with action "update_user_status"
4. The backend updates the user's status in the database (admin_api.php lines 365-418)
5. The change is reflected in the database's `users` table, `status` column

### User Type 1 (Regular User) Functionality

Regular users can access the home dashboard implemented in `src/UI/home.php` with functionality in `dashboard.js`.

#### Main Features:

1. **Journal Entry Creation**:

   - Quick journal entry form (home.php lines 56-73)
   - Mood selection with emoji tags (lines 57-72)
   - Privacy control for sharing with friends (lines 74-77)
   - Submit functionality (dashboard.js lines 356-427)

2. **Feed and Social Features**:

   - View friends' public journal entries (home.php lines 105-109)
   - React to entries with like, support, or hug (dashboard.js lines 1171-1226)
   - Comment on entries (dashboard.js lines 1228-1322)
   - Send private replies (dashboard.js lines 1324-1332)

3. **Friends Management**:

   - View friends list (home.php lines 116-130)
   - Search for friends (lines 117-121)
   - Send and respond to friend requests (dashboard.js lines 503-571)
   - Find new friends (home.php lines 133-142)

4. **Mood Tracking**:
   - View mood trends (home.php lines 81-88)
   - See personalized recommendations (home.php lines 145-148)

#### Adding a Journal Entry:

1. User selects a mood tag (dashboard.js lines 362-367)
2. User writes content in the text area (home.php line 73)
3. User decides whether to make it public (line 76)
4. User clicks "Save" button (line 77)
5. The entry is submitted to `journal_api.php` (dashboard.js lines 377-398)
6. The new entry is stored in the `journal_entries` table
7. The UI is updated with success message and refreshed data (dashboard.js lines 399-426)

#### Editing Journal Entries:

Users can edit their entries through the feed interface:

1. The entry appears in the feed (dashboard.js lines 1066-1167)
2. User can click on their own entries to edit
3. Changes are submitted to `journal_api.php`
4. The database is updated in the `journal_entries` table

#### Viewing Entry History:

Users can view their journal history:

1. Entries are displayed in the feed section (home.php lines 105-109)
2. The feed is populated from `journal_api.php` (dashboard.js lines 1048-1167)
3. Each entry shows the mood, content, date, and reactions

### User Type 2 (Mentor) Functionality

Mentors have additional features beyond regular users, implemented in the home dashboard with mentor-specific sections.

#### Mentor-Specific Features:

1. **Mentee Management**:

   - View assigned mentees (home.php lines 159-166)
   - See mentees needing attention (line 164)
   - Access detailed mentee information (dashboard.js lines 1914-1943)

2. **Adding Mentee Notes**:

   - Mentors can add notes about their mentees
   - Notes are stored in the `mentor_mentee` table, `notes` column
   - The mentee's record includes a `needs_attention` flag

3. **Identifying Mentee Data**:
   - Mentee relationships are stored in the `mentor_mentee` table
   - Each record links a mentor_id with a mentee_id
   - Additional metadata includes notes and attention status

### User Type 3 (Counsellor) Functionality

Counsellors have specialized features for client management, implemented in the home dashboard with counsellor-specific sections.

#### Counsellor-Specific Features:

1. **Client Management**:

   - View assigned clients (home.php lines 170-177)
   - See priority clients (line 173)
   - Track upcoming sessions (line 176)
   - Access detailed client information (dashboard.js lines 1945-1985)

2. **Client Data Management**:

   - Counsellors can add notes about their clients
   - Notes are stored in the `counsellor_client` table
   - Each client can be marked as priority if needed

3. **Identifying Client Data**:
   - Client relationships are stored in the `counsellor_client` table
   - Each record links a counsellor_id with a client_id
   - Additional metadata includes notes and priority status

### Code Structure and Organization

#### Classes and Methods:

While the application primarily uses procedural PHP, it employs a structured approach with well-defined functions:

1. **Database Functions**:

   - `getDbConnection()` in `config.php` (lines 25-34) - Creates and returns a PDO database connection

2. **Authentication Functions**:

   - Login processing in `login.html`
   - Signup processing in `signup.php`
   - Session checking in `check_session.php`

3. **API Functions**:

   - Journal API functions in `journal_api.php`
   - Friends API functions in `friends_api.php`
   - Admin API functions in `admin_api.php`
   - Recommendations API functions in `recommendations_api.php`

4. **Frontend JavaScript Functions**:
   - `initUserProfile()` (dashboard.js lines 429-477) - Initializes user profile section
   - `initQuickJournal()` (dashboard.js lines 479-571) - Sets up journal entry form
   - `initFriendsList()` (dashboard.js lines 573-627) - Loads and displays friends list
   - `initFeedContent()` (dashboard.js lines 1048-1167) - Loads and displays feed content

#### Database Query Structure:

The application uses PDO for database interactions with prepared statements for security:

1. **Query Pattern**:

   - Prepare statement with placeholders (e.g., admin_api.php lines 31-37)
   - Bind parameters (line 38)
   - Execute query (line 39)
   - Fetch results (line 41)

2. **Transaction Management**:
   - Begin transaction (signup.php line 102)
   - Execute multiple queries
   - Commit on success (line 126)
   - Rollback on error (line 152)

#### Code Redundancy Reduction:

Several techniques are used to reduce code redundancy:

1. **Shared Configuration**:

   - `config.php` contains database connection parameters and shared settings
   - `getDbConnection()` function is reused across all backend files

2. **Included Headers and Footers**:

   - `header.php` is included at the top of each page (e.g., home.php line 2)
   - `footer.php` is included at the bottom (home.php line 186)
   - This eliminates duplicating HTML structure and session handling code

3. **Reusable JavaScript Functions**:

   - Common functions like `showNotification()` (dashboard.js lines 429-446)
   - Helper functions for creating UI elements (e.g., `createFriendCard()`, lines 629-668)

4. **Shared Session Management**:
   - Session handling is centralized in `check_session.php`
   - All pages use the same session verification logic

#### Potential Improvements:

1. **Object-Oriented Approach**:

   - Refactor to use classes for Users, Journal Entries, etc.
   - Implement proper Model-View-Controller architecture

2. **API Standardization**:

   - Create a unified API structure with consistent response formats
   - Implement proper REST endpoints

3. **Frontend Framework**:

   - Consider using a modern JavaScript framework (React, Vue, etc.)
   - Improve component reusability and state management

4. **Security Enhancements**:

   - Implement CSRF protection
   - Add rate limiting for authentication attempts
   - Improve input validation and sanitization

5. **Performance Optimization**:
   - Implement caching for frequently accessed data
   - Optimize database queries with proper indexing
   - Add pagination for large data sets

## Conclusion

Moodiary2 is a comprehensive mood tracking and mental health support application with different user roles and features. The system allows users to journal their moods, connect with friends, and receive personalized recommendations. Special roles like mentors and counsellors provide additional support mechanisms, while administrators can manage the entire system. The application uses PHP for backend processing, MySQL for data storage, and JavaScript for dynamic frontend functionality.
