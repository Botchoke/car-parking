<?php
include "db.php";

$sql1 = "CREATE TABLE IF NOT EXISTS users(
id INT AUTO_INCREMENT PRIMARY KEY,
fullname VARCHAR(100),
email VARCHAR(100),
phone VARCHAR(20),
username VARCHAR(50) UNIQUE,
password VARCHAR(255),
role ENUM('admin','user') DEFAULT 'user',
status ENUM('active','banned') DEFAULT 'active',
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql2 = "CREATE TABLE IF NOT EXISTS vehicles(
id INT AUTO_INCREMENT PRIMARY KEY,
plate_number VARCHAR(20),
owner_name VARCHAR(100),
slot_number INT,
time_in DATETIME DEFAULT CURRENT_TIMESTAMP,
fee INT DEFAULT 25
)";

$sql3 = "CREATE TABLE IF NOT EXISTS history(
id INT AUTO_INCREMENT PRIMARY KEY,
plate_number VARCHAR(20),
owner_name VARCHAR(100),
slot_number INT,
time_in DATETIME,
time_out DATETIME,
total_fee INT,
payment_method VARCHAR(30) DEFAULT 'Cash'
)";

$sql4 = "CREATE TABLE IF NOT EXISTS user_vehicles(
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT,
plate_number VARCHAR(20),
vehicle_type VARCHAR(50),
color VARCHAR(30),
model VARCHAR(100),
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

$sql5 = "CREATE TABLE IF NOT EXISTS parking_sessions(
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT,
vehicle_id INT,
plate_number VARCHAR(20),
slot_number INT,
plan_name VARCHAR(50),
duration_hours INT,
time_in DATETIME DEFAULT CURRENT_TIMESTAMP,
time_out DATETIME NULL,
total_fee DECIMAL(10,2),
payment_method VARCHAR(30),
status ENUM('active','completed','cancelled') DEFAULT 'active'
)";

$sql6 = "CREATE TABLE IF NOT EXISTS reservations(
id INT AUTO_INCREMENT PRIMARY KEY,
user_id INT,
vehicle_id INT,
plate_number VARCHAR(20),
plan_name VARCHAR(50),
duration_hours INT,
reservation_date DATE,
reservation_time TIME,
price DECIMAL(10,2),
payment_method VARCHAR(30),
status ENUM('pending','approved','rejected','completed','cancelled') DEFAULT 'pending',
admin_note TEXT,
created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)";

mysqli_query($conn, $sql1);
mysqli_query($conn, $sql2);
mysqli_query($conn, $sql3);
mysqli_query($conn, $sql4);
mysqli_query($conn, $sql5);
mysqli_query($conn, $sql6);

echo "All tables created successfully!";
?>