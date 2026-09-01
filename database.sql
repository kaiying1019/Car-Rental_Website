CREATE DATABASE IF NOT EXISTS `carrentalwebsite`;
USE `carrentalwebsite`;
DROP TABLE IF EXISTS `cart_items`;
DROP TABLE IF EXISTS `bookings`;
DROP TABLE IF EXISTS `reviews`;
DROP TABLE IF EXISTS `inquiries`;
DROP TABLE IF EXISTS `branches`;
DROP TABLE IF EXISTS `cars`;
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(50) UNIQUE NOT NULL,
  `email` VARCHAR(100) UNIQUE NOT NULL,
  `password` VARCHAR(255) NOT NULL,
  `role` ENUM('user', 'admin') DEFAULT 'user',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);
INSERT INTO `users` (`username`, `email`, `password`, `role`) 
VALUES ('admin', 'admin@gocar.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');
CREATE TABLE `cars` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `brand` VARCHAR(50) NOT NULL,
  `model` VARCHAR(50) NOT NULL,
  `year` INT NOT NULL,
  `price_per_day` DECIMAL(10,2) NOT NULL,
  `transmission` ENUM('automatic', 'manual') NOT NULL DEFAULT 'automatic',
  `fuel_type` ENUM('petrol', 'diesel', 'hybrid', 'electric') NOT NULL DEFAULT 'petrol',
  `seats` INT NOT NULL DEFAULT 5,
  `image` VARCHAR(255) DEFAULT NULL,
  `description` TEXT,
  `status` ENUM('available', 'rented', 'maintenance') NOT NULL DEFAULT 'available',
  `added_by` INT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`added_by`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
INSERT INTO `cars` (`brand`, `model`, `year`, `price_per_day`, `transmission`, `fuel_type`, `seats`, `image`, `description`, `status`, `added_by`) VALUES
('Toyota', 'Vios', 2023, 150.00, 'automatic', 'petrol', 5, 'toyota_vios.jpg', 'Reliable and fuel-efficient sedan, great for city driving.', 'available', 1),
('Honda', 'City', 2022, 160.00, 'automatic', 'petrol', 5, 'honda_city.jpg', 'Comfortable compact sedan with good boot space.', 'available', 1),
('Perodua', 'Myvi', 2023, 100.00, 'manual', 'petrol', 5, 'perodua_myvi.jpg', 'Malaysia''s favourite hatchback, cheap and easy to drive.', 'available', 1),
('Perodua', 'Bezza', 2022, 90.00, 'manual', 'petrol', 5, 'perodua_bezza.jpg', 'Budget-friendly sedan, perfect for new drivers.', 'available', 1),
('Proton', 'Saga', 2021, 85.00, 'manual', 'petrol', 5, 'proton_saga.jpg', 'Affordable and easy to maintain, ideal for short trips.', 'available', 1),
('Honda', 'CR-V', 2023, 220.00, 'automatic', 'petrol', 7, 'honda_crv.jpg', 'Spacious SUV with plenty of room for family trips.', 'available', 1),
('Toyota', 'Innova', 2022, 200.00, 'automatic', 'diesel', 8, 'toyota_innova.jpg', 'Roomy MPV, great for group travel or long journeys.', 'rented', 1),
('Honda', 'HR-V', 2023, 190.00, 'automatic', 'hybrid', 5, 'honda_hrv.jpg', 'Fuel-sipping hybrid crossover with a smooth ride.', 'available', 1),
('Tesla', 'Model 3', 2024, 350.00, 'automatic', 'electric', 5, 'tesla_model3.jpg', 'Fully electric sedan with autopilot features.', 'maintenance', 1),
('BYD', 'Atto 3', 2024, 280.00, 'automatic', 'electric', 5, 'byd_atto3.jpg', 'Electric SUV with long range and fast charging support.', 'available', 1);
CREATE TABLE `branches` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `address` VARCHAR(255) NOT NULL,
  `city` VARCHAR(100) NOT NULL,
  `latitude` DECIMAL(10,7) DEFAULT NULL,
  `longitude` DECIMAL(10,7) DEFAULT NULL
);
INSERT INTO `branches` (`name`, `address`, `city`, `latitude`, `longitude`) VALUES
('KL Sentral Branch', 'Level 1, KL Sentral, 50470 Kuala Lumpur', 'Kuala Lumpur', 3.1347, 101.6869),
('KLIA Branch', 'Arrival Hall, KLIA Terminal 1, 64000 Sepang', 'Sepang', 2.7456, 101.7099),
('Bukit Bintang Branch', 'Jalan Bukit Bintang, 55100 Kuala Lumpur', 'Kuala Lumpur', 3.1466, 101.7106),
('Penang Branch', 'Jalan Sultan Ahmad Shah, 10050 George Town', 'Penang', 5.4141, 100.3288),
('Johor Bahru Branch', 'Jalan Wong Ah Fook, 80000 Johor Bahru', 'Johor Bahru', 1.4590, 103.7625);
CREATE TABLE `reviews` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `car_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `rating` TINYINT NOT NULL,
  `comment` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  CONSTRAINT `chk_rating` CHECK (`rating` BETWEEN 1 AND 5)
);
INSERT INTO `reviews` (`car_id`, `user_id`, `rating`, `comment`) VALUES
(1, 1, 5, 'Very clean and comfortable car.'),
(2, 1, 4, 'Affordable rental price.'),
(3, 1, 5, 'Excellent service and smooth booking process.'),
(4, 1, 4, 'Good value for money.'),
(5, 1, 5, 'Highly recommended!'),
(6, 1, 5, 'Perfect for family vacations.'),
(7, 1, 4, 'Large interior and comfortable ride.'),
(8, 1, 5, 'Very fuel efficient.'),
(9, 1, 5, 'Amazing electric car experience.'),
(10, 1, 4, 'Modern SUV with excellent performance.');
CREATE TABLE `bookings` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `car_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `pickup_branch_id` INT NOT NULL,
  `dropoff_branch_id` INT NOT NULL,
  `pickup_datetime` DATETIME NOT NULL,
  `dropoff_datetime` DATETIME NOT NULL,
  `licence_type` ENUM('original_idp', 'original_licence', 'malaysia_licence') NOT NULL,
  `payment_method` ENUM('visa', 'mastercard', 'amex', 'jcb', 'unionpay') NOT NULL,
  `total_days` INT NOT NULL,
  `total_price` DECIMAL(10,2) NOT NULL,
  `deposit_amount` DECIMAL(10,2) NOT NULL DEFAULT 200.00,
  `status` ENUM('pending', 'confirmed', 'completed', 'cancelled') NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`pickup_branch_id`) REFERENCES `branches`(`id`),
  FOREIGN KEY (`dropoff_branch_id`) REFERENCES `branches`(`id`)
);
CREATE TABLE `inquiries` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `car_id` INT DEFAULT NULL,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(100) NOT NULL,
  `email` VARCHAR(100) NOT NULL,
  `phone` VARCHAR(30) DEFAULT NULL,
  `message` TEXT NOT NULL,
  `status` ENUM('new', 'responded') NOT NULL DEFAULT 'new',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);
CREATE TABLE `cart_items` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `car_id` INT NOT NULL,
  `days` INT NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `unique_user_car` (`user_id`, `car_id`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`car_id`) REFERENCES `cars`(`id`) ON DELETE CASCADE
);