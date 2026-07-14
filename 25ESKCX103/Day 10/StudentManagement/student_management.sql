CREATE DATABASE student_management;

USE student_management;

CREATE TABLE students(
id INT AUTO_INCREMENT PRIMARY KEY,
photo VARCHAR(255),
name VARCHAR(100),
email VARCHAR(100),
branch VARCHAR(50),
cgpa DECIMAL(3,2),
status ENUM('Active','Inactive') DEFAULT 'Active'
);