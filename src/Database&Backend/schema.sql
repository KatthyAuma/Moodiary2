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
    last_login TIMESTAMP NULL
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

-- User activity log
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    activity_type VARCHAR(50) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Therapist/Psychologist profiles
CREATE TABLE IF NOT EXISTS professional_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL UNIQUE,
    license_number VARCHAR(100),
    specialization TEXT,
    education TEXT,
    years_experience INT,
    is_verified BOOLEAN DEFAULT FALSE,
    verified_at TIMESTAMP NULL,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Insert default roles
INSERT INTO roles (role_name, description) VALUES 
('admin', 'System administrator with full access'),
('user', 'Regular user'),
('psychologist', 'Mental health professional'),
('moderator', 'Community moderator');

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
(1, 2, 'Comforting Music', 'Frank Ocean - "Ivy"\nPhoebe Bridgers - "Funeral"\nAdele - "Someone Like You"'),
(2, 2, 'Activities for Sad Mood', 'Journal your feelings\nTake a warm bath\nWatch a comforting movie\nReach out to a supportive friend'),

-- Angry mood recommendations
(1, 3, 'Calming Music', 'Bon Iver - "Skinny Love"\nFleetwood Mac - "Landslide"\nSigur Rós - "Hoppípolla"'),
(2, 3, 'Activities for Angry Mood', 'Deep breathing exercises\nGo for a run\nWrite a letter (but don''t send it)\nPractice mindfulness meditation'),

-- Anxious mood recommendations
(1, 4, 'Soothing Music', 'Brian Eno - "Ambient 1: Music for Airports"\nYoYo Ma - "Bach: Cello Suites"\nMax Richter - "Sleep"'),
(3, 4, 'Meditation for Anxiety', '4-7-8 Breathing Technique\nBody Scan Meditation\nGuided Visualization\nProgressive Muscle Relaxation'),

-- Calm mood recommendations
(1, 5, 'Peaceful Music', 'Nils Frahm - "Says"\nOlafur Arnalds - "Near Light"\nJóhann Jóhannsson - "Flight from the City"'),
(4, 5, 'Reading for Calm Moments', '"The Alchemist" by Paulo Coelho\n"Siddhartha" by Hermann Hesse\n"Walden" by Henry David Thoreau'),

-- Excited mood recommendations
(1, 6, 'Energetic Music', 'Queen - "Don''t Stop Me Now"\nAvicii - "Wake Me Up"\nDaft Punk - "Get Lucky"'),
(5, 6, 'Channel Your Energy', 'Try a new workout routine\nStart a creative project\nPlan your next adventure\nLearn a new skill'),

-- Tired mood recommendations
(1, 7, 'Gentle Music', 'Explosions in the Sky - "Your Hand in Mine"\nIron & Wine - "Flightless Bird, American Mouth"\nThe Album Leaf - "The Light"'),
(2, 7, 'Activities for Tired Days', 'Take a power nap (20 minutes)\nLight stretching\nMake a nutritious snack\nTake a short walk in fresh air'),

-- Grateful mood recommendations
(1, 8, 'Reflective Music', 'Louis Armstrong - "What a Wonderful World"\nIsrael Kamakawiwo''ole - "Somewhere Over the Rainbow"\nColdplay - "Fix You"'),
(2, 8, 'Gratitude Activities', 'Write in a gratitude journal\nSend a thank you note\nPractice mindful appreciation\nVolunteer your time');