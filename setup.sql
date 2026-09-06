-- ============================================
-- MINI POS SYSTEM - FULL DATABASE SETUP (FINAL)
-- Termasuk: Login, Inventory, Kiosk
-- ============================================

DROP DATABASE IF EXISTS db_mini_pos;
CREATE DATABASE db_mini_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_mini_pos;

-- =====================
-- TABEL USERS (LOGIN)
-- =====================
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'kasir') NOT NULL DEFAULT 'kasir',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================
-- TABEL PRODUK
-- =====================
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    price DECIMAL(12,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================
-- TABEL TRANSAKSI HEADER
-- Termasuk: order_type (kasir/kiosk), status, customer info
-- =====================
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(12,2) NOT NULL,
    pay_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    change_amount DECIMAL(12,2) NOT NULL DEFAULT 0,
    order_type ENUM('kasir', 'kiosk') NOT NULL DEFAULT 'kasir',
    status ENUM('pending', 'preparing', 'ready', 'completed', 'cancelled') NOT NULL DEFAULT 'completed',
    customer_name VARCHAR(50) DEFAULT NULL,
    table_number VARCHAR(10) DEFAULT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================
-- TABEL DETAIL TRANSAKSI
-- =====================
CREATE TABLE transaction_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    transaction_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    subtotal DECIMAL(12,2) NOT NULL,
    FOREIGN KEY (transaction_id) REFERENCES transactions(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

-- =====================
-- TABEL RIWAYAT MUTASI STOK
-- =====================
CREATE TABLE stock_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    product_id INT NOT NULL,
    type ENUM('in', 'out', 'adjustment') NOT NULL,
    quantity INT NOT NULL,
    reference VARCHAR(100) DEFAULT NULL,
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =====================
-- DATA USER DEFAULT
-- admin / admin123
-- kasir1 / kasir123
-- =====================
INSERT INTO users (username, password_hash, full_name, role, is_active) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin', 1),
('kasir1', '$2y$10$sZp7CqGJxVlLwMKzBwN7wuCmFqbJXhWdOvXnHqJfKmRkSjCKOHOa6', 'Kasir Satu', 'kasir', 1);

-- =====================
-- DATA PRODUK
-- =====================
INSERT INTO products (name, price, stock) VALUES 
('Kopi Susu Gula Aren', 18000, 50),
('Teh Tarik', 15000, 40),
('Croissant Coklat', 25000, 20),
('Air Mineral 600ml', 5000, 100),
('Nasi Goreng Spesial', 35000, 30);

-- =====================
-- DATA MUTASI STOK AWAL
-- =====================
INSERT INTO stock_history (product_id, type, quantity, reference, notes) VALUES 
(1, 'adjustment', 50, 'INITIAL', 'Stok awal produk baru'),
(2, 'adjustment', 40, 'INITIAL', 'Stok awal produk baru'),
(3, 'adjustment', 20, 'INITIAL', 'Stok awal produk baru'),
(4, 'adjustment', 100, 'INITIAL', 'Stok awal produk baru'),
(5, 'adjustment', 30, 'INITIAL', 'Stok awal produk baru');