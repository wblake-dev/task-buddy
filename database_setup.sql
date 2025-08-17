-- TaskBuddy Database Setup
-- This file creates all necessary tables for the TaskBuddy application

-- Create database if it doesn't exist
CREATE DATABASE IF NOT EXISTS task_buddy_db;
USE task_buddy_db;

-- Users table
CREATE TABLE IF NOT EXISTS users (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    balance DECIMAL(10,2) DEFAULT 0.00,
    completed_tasks INT(11) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- User profiles table
CREATE TABLE IF NOT EXISTS user_profiles (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    first_name VARCHAR(50),
    last_name VARCHAR(50),
    email VARCHAR(100),
    mobile_number VARCHAR(20),
    age INT(3),
    address TEXT,
    state VARCHAR(50),
    zip_code VARCHAR(10),
    city VARCHAR(50),
    country VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Admins table
CREATE TABLE IF NOT EXISTS admins (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    admin_name VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Surveys table
CREATE TABLE IF NOT EXISTS surveys (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    questions JSON,
    reward DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    status ENUM('active', 'inactive', 'completed') DEFAULT 'active',
    created_by INT(11),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES admins(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Survey responses table
CREATE TABLE IF NOT EXISTS survey_responses (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    survey_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    response_data JSON,
    completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (survey_id) REFERENCES surveys(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_survey (user_id, survey_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Transactions table
CREATE TABLE IF NOT EXISTS transactions (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    type ENUM('withdrawal', 'earning', 'bonus') NOT NULL,
    payment_method VARCHAR(50),
    bank_name VARCHAR(100),
    account_number VARCHAR(50),
    bkash_number VARCHAR(20),
    status ENUM('pending', 'approved', 'completed', 'rejected') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Support tickets table
CREATE TABLE IF NOT EXISTS tickets (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    user_id INT(11) NOT NULL,
    subject VARCHAR(255) NOT NULL,
    message TEXT NOT NULL,
    priority ENUM('low', 'medium', 'high', 'urgent') DEFAULT 'medium',
    status ENUM('open', 'in_progress', 'resolved', 'closed') DEFAULT 'open',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Ticket replies table
CREATE TABLE IF NOT EXISTS ticket_replies (
    id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
    ticket_id INT(11) NOT NULL,
    user_id INT(11) NOT NULL,
    reply_message TEXT NOT NULL,
    is_admin TINYINT(1) DEFAULT 0,
    replied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ticket_id) REFERENCES tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Insert default admin user
INSERT INTO admins (admin_name, password) 
VALUES ('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Insert admin user 'H' with password '8'
INSERT INTO admins (admin_name, password) 
VALUES ('H', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Insert sample surveys
INSERT INTO surveys (title, description, questions, reward, created_by) VALUES
('Customer Satisfaction Survey', 'Help us improve our services by sharing your feedback', 
'[{"question": "How satisfied are you with our service?", "type": "rating", "options": ["1", "2", "3", "4", "5"]}, {"question": "What can we improve?", "type": "text"}]', 
5.00, 1),
('Product Feedback Survey', 'Share your thoughts about our latest product', 
'[{"question": "How likely are you to recommend this product?", "type": "rating", "options": ["1", "2", "3", "4", "5"]}, {"question": "What features do you like most?", "type": "text"}]', 
3.50, 1),
('Market Research Survey', 'Help us understand market trends', 
'[{"question": "What is your age group?", "type": "multiple_choice", "options": ["18-25", "26-35", "36-45", "46-55", "55+"]}, {"question": "What is your preferred shopping method?", "type": "multiple_choice", "options": ["Online", "In-store", "Both"]}]', 
7.00, 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);