-- ============================================
-- MINI POS SYSTEM - FULL DATABASE SETUP (FINAL)
-- Termasuk: Login, Inventory, Kiosk, Logs, Settings, Tax
-- ============================================

DROP DATABASE IF EXISTS db_mini_pos;
CREATE DATABASE db_mini_pos CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE db_mini_pos;

-- =====================
-- TABEL USERS
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
-- ✅ TABEL TRANSAKSI (dengan kolom pajak)
-- =====================
CREATE TABLE transactions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    invoice_number VARCHAR(50) NOT NULL UNIQUE,
    total_amount DECIMAL(12,2) NOT NULL,
    subtotal_amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Total sebelum pajak',
    tax_amount DECIMAL(12,2) NOT NULL DEFAULT 0 COMMENT 'Nilai pajak',
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
-- TABEL MUTASI STOK
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
-- TABEL ACTIVITY LOGS
-- =====================
CREATE TABLE activity_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(50) NOT NULL,
    entity_type VARCHAR(50) DEFAULT NULL,
    entity_id INT DEFAULT NULL,
    description TEXT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    INDEX idx_entity (entity_type, entity_id)
) ENGINE=InnoDB;

-- =====================
-- TABEL PASSWORD HISTORY
-- =====================
CREATE TABLE password_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    changed_by_user_id INT DEFAULT NULL,
    changed_by_name VARCHAR(100) DEFAULT NULL,
    change_method ENUM('default_reset', 'custom', 'initial') NOT NULL DEFAULT 'custom',
    notes TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by_user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_created (created_at)
) ENGINE=InnoDB;

-- =====================
-- TABEL STORE SETTINGS
-- =====================
CREATE TABLE IF NOT EXISTS store_settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(50) NOT NULL UNIQUE,
    setting_value TEXT DEFAULT NULL,
    setting_label VARCHAR(100) DEFAULT NULL,
    setting_group VARCHAR(50) DEFAULT 'general',
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =====================
-- DATA USER DEFAULT
-- Password akan di-auto-fix oleh config/database.php
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

-- =====================
-- DATA ACTIVITY LOG AWAL
-- =====================
INSERT INTO activity_logs (action, entity_type, entity_id, description, ip_address) VALUES 
('system_init', NULL, NULL, 'Database initialized with default data', '127.0.0.1');

-- =====================
-- DATA STORE SETTINGS
-- =====================
INSERT INTO store_settings (setting_key, setting_value, setting_label, setting_group) VALUES 
('store_name', 'Mini PoS Restaurant', 'Nama Toko', 'general'),
('store_address', 'Jl. Teknologi No. 123, Jakarta', 'Alamat Toko', 'general'),
('store_phone', '(021) 1234-5678', 'Nomor Telepon', 'general'),
('store_email', 'info@minipos.com', 'Email Toko', 'general'),
('store_logo', '', 'Logo Toko (path file)', 'general'),
('tax_enabled', '1', 'Aktifkan Pajak', 'tax'),
('tax_rate', '11', 'Tarif Pajak (%)', 'tax'),
('tax_label', 'PPN 11%', 'Label Pajak', 'tax'),
('receipt_header', 'Terima kasih atas kunjungan Anda!', 'Header Struk', 'receipt'),
('receipt_footer', 'Barang yang sudah dibeli tidak dapat ditukar/dikembalikan', 'Footer Struk', 'receipt'),
('receipt_show_logo', '1', 'Tampilkan Logo di Struk', 'receipt'),
('receipt_show_address', '1', 'Tampilkan Alamat di Struk', 'receipt'),
('currency_symbol', 'Rp', 'Simbol Mata Uang', 'general'),
('timezone', 'Asia/Jakarta', 'Zona Waktu', 'general')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);