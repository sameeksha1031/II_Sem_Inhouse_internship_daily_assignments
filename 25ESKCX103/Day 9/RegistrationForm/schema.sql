-- Database Schema for Registration Form
-- Can be imported directly into phpMyAdmin (XAMPP)

-- 1. Create the Database if it doesn't exist
CREATE DATABASE IF NOT EXISTS `registration_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `registration_db`;

-- 2. Create the Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
