-- Database schema for Hostel Room Request Tracker

CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `student_id` VARCHAR(50) UNIQUE DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
  `gender` ENUM('male', 'female', 'other') NOT NULL,
  `phone` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `hostels` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) UNIQUE NOT NULL,
  `type` ENUM('boys', 'girls', 'coed') NOT NULL DEFAULT 'coed',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rooms` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `hostel_id` INT NOT NULL,
  `room_number` VARCHAR(20) NOT NULL,
  `room_type` ENUM('AC', 'Non-AC') NOT NULL DEFAULT 'Non-AC',
  `capacity` INT NOT NULL DEFAULT 1,
  `current_occupancy` INT NOT NULL DEFAULT 0,
  `price` DECIMAL(10,2) NOT NULL,
  `status` ENUM('Available', 'Full', 'Maintenance') NOT NULL DEFAULT 'Available',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`hostel_id`) REFERENCES `hostels`(`id`) ON DELETE CASCADE,
  UNIQUE KEY `hostel_room_unique` (`hostel_id`, `room_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `requests` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `status` ENUM('Pending', 'Approved', 'Rejected', 'Cancelled') NOT NULL DEFAULT 'Pending',
  `duration_months` INT NOT NULL DEFAULT 12,
  `check_in_date` DATE NOT NULL,
  `student_remarks` TEXT DEFAULT NULL,
  `admin_remarks` TEXT DEFAULT NULL,
  `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `allocations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `room_id` INT NOT NULL,
  `request_id` INT DEFAULT NULL,
  `allocation_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `duration_months` INT NOT NULL DEFAULT 12,
  `status` ENUM('Active', 'CheckedOut') NOT NULL DEFAULT 'Active',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`room_id`) REFERENCES `rooms`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`request_id`) REFERENCES `requests`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Seed default hostels
INSERT INTO `hostels` (`id`, `name`, `type`) VALUES
(1, 'Nehru Premium Boys Hostel', 'boys'),
(2, 'Kalpana Chawla Girls Residence', 'girls'),
(3, 'Newton International Co-ed Block', 'coed')
ON DUPLICATE KEY UPDATE `name`=VALUES(`name`);

-- Seed default rooms
INSERT INTO `rooms` (`hostel_id`, `room_number`, `room_type`, `capacity`, `current_occupancy`, `price`, `status`) VALUES
(1, 'B101', 'AC', 1, 0, 7500.00, 'Available'),
(1, 'B102', 'Non-AC', 2, 0, 4500.00, 'Available'),
(1, 'B103', 'AC', 2, 0, 6000.00, 'Available'),
(2, 'G201', 'AC', 1, 0, 8000.00, 'Available'),
(2, 'G202', 'Non-AC', 2, 0, 5000.00, 'Available'),
(2, 'G203', 'AC', 3, 0, 5500.00, 'Available'),
(3, 'C301', 'AC', 1, 0, 9000.00, 'Available'),
(3, 'C302', 'Non-AC', 2, 0, 6000.00, 'Available')
ON DUPLICATE KEY UPDATE `price`=VALUES(`price`);
