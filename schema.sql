-- Create the users table
CREATE OR REPLACE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    -- VULNERABILITY: Storing passwords in plaintext.
    -- In a real application, you should always store hashed and salted passwords.
    password VARCHAR(255) NOT NULL,
    is_admin BOOLEAN DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Insert a default admin user for demonstration purposes.
-- This makes it easier to test login functionality from the start.
INSERT INTO users (username, password, is_admin) VALUES ('admin', 'password123', 1);

-- Insert a regular user for testing.
INSERT INTO users (username, password, is_admin) VALUES ('user', 'userpass', 0);

CREATE TABLE IF NOT EXISTS `questions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `message` TEXT NOT NULL,
  `is_answered` BOOLEAN NOT NULL DEFAULT 0,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- NEW: Create the 'transactions' table for the user dashboard.
-- This table will track all loans and repayments for each user.
CREATE TABLE IF NOT EXISTS `transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `transaction_type` ENUM('loan', 'repayment') NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
);
