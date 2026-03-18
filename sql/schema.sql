-- =============================================
-- Sales Dashboard - Database Schema
-- Run this script in phpMyAdmin or MySQL CLI
-- =============================================

-- Create database
CREATE DATABASE IF NOT EXISTS sales_dashboard;
USE sales_dashboard;

-- Users table (admin/user roles)
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    email VARCHAR(100) NOT NULL,
    role ENUM('admin', 'user') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Product groups table
CREATE TABLE IF NOT EXISTS product_groups (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Products table
CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_group_id INT NOT NULL,
    name VARCHAR(150) NOT NULL,
    sku VARCHAR(50) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_group_id) REFERENCES product_groups(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS sales (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    period INT NOT NULL,
    year INT NOT NULL,
    amount DECIMAL(12, 2) NOT NULL DEFAULT 0,
    quantity INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    UNIQUE KEY unique_sale (product_id, period, year)
) ENGINE=InnoDB;


CREATE TABLE IF NOT EXISTS invoices (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    invoice_date DATE NOT NULL,
    customer_name VARCHAR(150) NOT NULL,
    status ENUM('draft', 'sent', 'paid', 'cancelled') DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS invoice_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(12, 2) NOT NULL,
    line_total DECIMAL(12, 2) AS (quantity * unit_price) STORED,
    FOREIGN KEY (invoice_id) REFERENCES invoices(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;


INSERT INTO users (username, password, email, role) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@example.com', 'admin'),
('user', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user@example.com', 'user');


INSERT INTO product_groups (name, description) VALUES
('CHEWIES - MV LYSI', 'Chewies Multi-Vitamin Lysine'),
('CHEWIES - MV TAU', 'Chewies Multi-Vitamin Taurine'),
('CHEWIES - POSM', 'Chewies POSM'),
('PENTASURE - 2.0', 'Pentasure 2.0'),
('PENTASURE - DLS', 'Pentasure DLS'),
('LAMISOPT', 'Lamisopt Products'),
('DIVERSEY', 'Diversey Products');


INSERT INTO products (product_group_id, name, sku) VALUES
(1, 'CHEWIES B/FAST B COMPLEX', 'CHW-BF-001'),
(1, 'CHEWIES EYEQ & MINERALS', 'CHW-EQ-002'),
(2, 'CHEWIES IMMUNITY', 'CHW-IM-003'),
(2, 'CHEWIES KIDS MV PLUS', 'CHW-KM-004'),
(3, 'PENTASURE BALANCE', 'PEN-BA-005'),
(4, 'PENTASURE DM', 'PEN-DM-006');


-- (No sample invoices here; application should create real data.)

-- Sample sales data
INSERT INTO sales (product_id, period, year, amount, quantity) VALUES
(1, 1, 2023, 150000, 500),
(1, 2, 2023, 180000, 600),
(1, 3, 2023, 220000, 700),
(1, 4, 2023, 195000, 650),
(2, 1, 2023, 120000, 400),
(2, 2, 2023, 145000, 480),
(2, 3, 2023, 165000, 550),
(3, 1, 2023, 95000, 320),
(3, 2, 2023, 110000, 370),
(4, 1, 2023, 85000, 280),
(4, 3, 2023, 105000, 350),
(5, 1, 2023, 200000, 650),
(5, 2, 2023, 250000, 800),
(1, 1, 2022, 135000, 450),
(1, 2, 2022, 160000, 530),
(2, 1, 2022, 100000, 330);
