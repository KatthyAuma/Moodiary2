-- Moodiary Database Schema

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `moodiary` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Use the moodiary database
USE `moodiary`;
-- Drop existing tables if they exist to avoid conflicts
DROP TABLE IF EXISTS friends;
DROP TABLE IF EXISTS journal_entries;
DROP TABLE IF EXISTS moods;
DROP TABLE IF EXISTS users;

-- Users table
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    username VARCHAR(50),
    profile_image VARCHAR(255) DEFAULT 'default_profile.png',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Moods table
CREATE TABLE moods (
    mood_id INT AUTO_INCREMENT PRIMARY KEY,
    mood_name VARCHAR(50) NOT NULL,
    mood_emoji VARCHAR(10),
    mood_color VARCHAR(20),
    mood_description TEXT
);

-- Journal entries table
CREATE TABLE journal_entries (
    entry_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    mood_id INT,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (mood_id) REFERENCES moods(mood_id) ON DELETE SET NULL
);

-- Friends table
CREATE TABLE friends (
    friendship_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    friend_id INT NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (friend_id) REFERENCES users(user_id) ON DELETE CASCADE,
    UNIQUE KEY unique_friendship (user_id, friend_id)
);

-- Insert default mood types
INSERT INTO moods (mood_name, mood_emoji, mood_color, mood_description) VALUES
('Happy', '😊', '#FFD700', 'Feeling joyful and content'),
('Sad', '😢', '#6495ED', 'Feeling down or upset'),
('Angry', '😠', '#FF4500', 'Feeling frustrated or annoyed'),
('Anxious', '😰', '#9370DB', 'Feeling worried or nervous'),
('Calm', '😌', '#90EE90', 'Feeling peaceful and relaxed'),
('Tired', '😴', '#A9A9A9', 'Feeling exhausted or sleepy'),
('Excited', '🤩', '#FF69B4', 'Feeling enthusiastic and eager'),
('Bored', '😑', '#D3D3D3', 'Feeling uninterested or dull'); 