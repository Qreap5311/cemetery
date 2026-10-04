CREATE DATABASE cemetery_db;
USE cemetery_db;
CREATE TABLE user (
  user_id INT AUTO_INCREMENT PRIMARY KEY,
  fname VARCHAR(50) NOT NULL, lname VARCHAR(50) NOT NULL,
  email VARCHAR(100), username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL, role ENUM('admin','staff') NOT NULL DEFAULT 'staff'
);
CREATE TABLE cemetery_section (
  section_id INT AUTO_INCREMENT PRIMARY KEY,
  section_name VARCHAR(80) NOT NULL, location VARCHAR(120)
);
CREATE TABLE cemetery_lot (
  lot_id INT AUTO_INCREMENT PRIMARY KEY,
  section_id INT NOT NULL, lot_no VARCHAR(20) NOT NULL,
  status ENUM('available','reserved','occupied') NOT NULL DEFAULT 'available',
  price DECIMAL(10,2) NOT NULL DEFAULT 0,
  UNIQUE (section_id, lot_no), INDEX (status),
  FOREIGN KEY (section_id) REFERENCES cemetery_section(section_id)
);
CREATE TABLE reservation (
  reservation_id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL, reservation_date DATE NOT NULL,
  reservation_status ENUM('pending','confirmed','cancelled') NOT NULL DEFAULT 'pending',
  FOREIGN KEY (lot_id) REFERENCES cemetery_lot(lot_id)
);
CREATE TABLE payment (
  payment_id INT AUTO_INCREMENT PRIMARY KEY,
  reservation_id INT NOT NULL, amount DECIMAL(10,2) NOT NULL,
  payment_date DATE NOT NULL,
  payment_status ENUM('pending','paid') NOT NULL DEFAULT 'paid',
  payment_method VARCHAR(30) NOT NULL,
  FOREIGN KEY (reservation_id) REFERENCES reservation(reservation_id)
);
CREATE TABLE burial_record (
  record_id INT AUTO_INCREMENT PRIMARY KEY,
  lot_id INT NOT NULL, deceased_name VARCHAR(120) NOT NULL,
  birthdate DATE, death_date DATE, burial_date DATE,
  relative_name VARCHAR(120), contact_no VARCHAR(30),
  INDEX (deceased_name),
  FOREIGN KEY (lot_id) REFERENCES cemetery_lot(lot_id)
);
INSERT INTO cemetery_section (section_name, location) VALUES
 ('Section A','North Gate'), ('Section B','Near Chapel');
INSERT INTO cemetery_lot (section_id, lot_no, price) VALUES
 (1,'A-01',15000),(1,'A-02',15000),(1,'A-03',15000),(2,'B-01',20000),(2,'B-02',20000);
