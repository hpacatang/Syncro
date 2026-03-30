-- Create database
CREATE DATABASE SyncroDB;
USE SyncroDB;
select * from users;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100),
    email VARCHAR(100),
    password VARCHAR(100)
);

-- Submissions table
CREATE TABLE submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    caption TEXT,
    status VARCHAR(50),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Feedback table
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    submission_id INT,
    user_id INT,
    message TEXT,
    FOREIGN KEY (submission_id) REFERENCES submissions(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);