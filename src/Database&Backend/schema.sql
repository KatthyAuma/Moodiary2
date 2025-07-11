-- Moodiary Database Schema
-- This file contains all the necessary tables and relationships for the Moodiary application

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS moodiary;
USE moodiary;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(50),
    full_name VARCHAR(100),
    profile_image VARCHAR(255),
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_login TIMESTAMP NULL,
    status ENUM('active', 'disabled', 'pending') DEFAULT 'active'
);

-- Roles table
CREATE TABLE IF NOT EXISTS roles (
    role_id INT AUTO_INCREMENT PRIMARY KEY,
    role_name VARCHAR(50) NOT NULL UNIQUE,
    description TEXT
);

-- User roles mapping
CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT,
    role_id INT,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(role_id) ON DELETE CASCADE
);

-- Moods table (predefined mood options)
CREATE TABLE IF NOT EXISTS moods (
    mood_id INT AUTO_INCREMENT PRIMARY KEY,
    mood_name VARCHAR(50) NOT NULL,
    emoji VARCHAR(10) NOT NULL,
    description TEXT
);

-- Journal entries
CREATE TABLE IF NOT EXISTS journal_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_id INT NOT NULL,
    content TEXT NOT NULL,
    is_public BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (mood_id) REFERENCES moods(mood_id)
);

-- Friends/connections
CREATE TABLE IF NOT EXISTS friendships (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected', 'blocked') DEFAULT 'pending',
    relationship_type ENUM('friend', 'mentor', 'counsellor', 'family') DEFAULT 'friend',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);

-- Recommendation categories
CREATE TABLE IF NOT EXISTS recommendation_categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    category_name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Recommendations
CREATE TABLE IF NOT EXISTS recommendations (
    recommendation_id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    mood_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES recommendation_categories(category_id),
    FOREIGN KEY (mood_id) REFERENCES moods(mood_id)
);

-- Comments on journal entries
CREATE TABLE IF NOT EXISTS comments (
    comment_id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    user_id INT NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES journal_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Reactions on journal entries (like, support, etc.)
CREATE TABLE IF NOT EXISTS reactions (
    reaction_id INT AUTO_INCREMENT PRIMARY KEY,
    entry_id INT NOT NULL,
    user_id INT NOT NULL,
    reaction_type ENUM('like', 'support', 'hug', 'celebrate') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (entry_id) REFERENCES journal_entries(entry_id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_reaction (entry_id, user_id, reaction_type)
);

-- Messages between users
CREATE TABLE IF NOT EXISTS messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    content TEXT NOT NULL,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reply_to_id INT DEFAULT NULL,
    reply_to_journal_id INT DEFAULT NULL,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reply_to_id) REFERENCES messages(message_id) ON DELETE SET NULL,
    FOREIGN KEY (reply_to_journal_id) REFERENCES journal_entries(entry_id) ON DELETE SET NULL
);

-- Activity logs table for tracking user activity
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    related_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Mentor-Mentee relationships
CREATE TABLE IF NOT EXISTS mentor_mentee (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mentor_id INT NOT NULL,
    mentee_id INT NOT NULL,
    notes TEXT,
    needs_attention BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (mentee_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_mentor_mentee (mentor_id, mentee_id)
);

-- Counsellor-Client relationships
CREATE TABLE IF NOT EXISTS counsellor_client (
    id INT AUTO_INCREMENT PRIMARY KEY,
    counsellor_id INT NOT NULL,
    client_id INT NOT NULL,
    notes TEXT,
    priority BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (counsellor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_counsellor_client (counsellor_id, client_id)
);

-- Counselling sessions
CREATE TABLE IF NOT EXISTS counselling_sessions (
    session_id INT AUTO_INCREMENT PRIMARY KEY,
    counsellor_id INT NOT NULL,
    client_id INT NOT NULL,
    session_date DATETIME NOT NULL,
    session_type VARCHAR(50) NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (counsellor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert default roles
INSERT INTO roles (role_name, description) VALUES 
('admin', 'Administrator with full access to all features'),
('user', 'Regular user with standard access'),
('mentor', 'Mentor who can guide other users'),
('counsellor', 'Professional counsellor who can provide mental health support');

-- Insert default mood options
INSERT INTO moods (mood_name, emoji, description) VALUES 
('happy', '😊', 'Feeling joyful and content'),
('sad', '😢', 'Feeling down or unhappy'),
('angry', '😠', 'Feeling frustrated or mad'),
('anxious', '😰', 'Feeling worried or nervous'),
('calm', '😌', 'Feeling peaceful and relaxed'),
('excited', '🤩', 'Feeling enthusiastic and eager'),
('tired', '😴', 'Feeling fatigued or exhausted'),
('grateful', '🙏', 'Feeling thankful and appreciative');

-- Insert recommendation categories
INSERT INTO recommendation_categories (category_name, description) VALUES 
('music', 'Music recommendations'),
('activities', 'Suggested activities'),
('meditation', 'Meditation and mindfulness'),
('reading', 'Book recommendations'),
('exercise', 'Physical activities');

-- Insert sample recommendations for different moods
INSERT INTO recommendations (category_id, mood_id, title, content) VALUES 
-- Happy mood recommendations
(1, 1, 'Upbeat Music', 'Pharrell Williams - "Happy"\nJustin Timberlake - "Can''t Stop the Feeling"\nMark Ronson ft. Bruno Mars - "Uptown Funk"'),
(2, 1, 'Activities for Happy Mood', 'Dance party\nCall a friend\nTry a new recipe\nGo for a walk in nature'),

-- Sad mood recommendations
(1, 2, 'Comforting Music', 'Adele - "Someone Like You"\nColdplay - "Fix You"\nSam Smith - "Stay With Me"'),
(2, 2, 'Activities for Sad Mood', 'Journal your feelings\nTake a warm bath\nWatch a comforting movie\nPractice self-compassion'),

-- Angry mood recommendations
(1, 3, 'Calming Music', 'Enya - "Caribbean Blue"\nBon Iver - "Skinny Love"\nYo-Yo Ma - "Bach: Cello Suite No. 1"'),
(2, 3, 'Activities for Angry Mood', 'Deep breathing exercises\nGo for a run\nWrite a letter (don''t send it)\nTalk to someone you trust'),

-- Anxious mood recommendations
(1, 4, 'Soothing Music', 'Brian Eno - "Ambient 1: Music for Airports"\nMax Richter - "Sleep"\nYanni - "Nightingale"'),
(2, 4, 'Activities for Anxious Mood', 'Practice 4-7-8 breathing\nProgressive muscle relaxation\nMindfulness meditation\nGo for a walk outside');

-- Create an admin user (password: admin123)
INSERT INTO users (email, password, username, full_name) VALUES
('admin@moodiary.com', '$2y$10$5vkMWXr6rV5Gr.Zk2XUZxOUEZXNKF5K2lS4M.xD5YhQIm2Kp0HbEa', 'admin', 'System Administrator');

-- Assign admin role
INSERT INTO user_roles (user_id, role_id)
SELECT 
    (SELECT user_id FROM users WHERE username = 'admin'),
    (SELECT role_id FROM roles WHERE role_name = 'admin');