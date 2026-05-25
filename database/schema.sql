-- MedIn Pharmacy Management System
-- Upgraded Database Schema

CREATE DATABASE IF NOT EXISTS medin_db;
USE medin_db;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL UNIQUE,
    phone_number VARCHAR(20) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'pharmacist', 'supplier', 'customer') NOT NULL,
    profile_image VARCHAR(255) DEFAULT NULL,
    status ENUM('active', 'inactive', 'suspended') DEFAULT 'active',
    is_verified BOOLEAN DEFAULT FALSE,
    last_login DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert a default admin for testing purposes
-- Password is 'Admin@123' (You should hash this in actual implementation)
-- The hash below is for 'Admin@123' using BCRYPT
INSERT INTO users (full_name, email, phone_number, password_hash, role, is_verified) 
VALUES ('Super Admin', 'admin@medin.com', '1234567890', '$2y$10$W9M7.E4kQcQ.s9rOaR.O..W.QoG5Qp/zXq8m9QO8tB3B5C.QjYqXG', 'admin', TRUE)
ON DUPLICATE KEY UPDATE id=id;
