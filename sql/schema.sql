CREATE DATABASE IF NOT EXISTS mathsolver_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE mathsolver_db;

CREATE TABLE IF NOT EXISTS app_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(120) NOT NULL UNIQUE,
    display_name VARCHAR(160) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS history (
    id BIGINT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    input_text TEXT NOT NULL,
    output_json LONGTEXT NOT NULL,
    result_value VARCHAR(64) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_history_user_id (user_id),
    INDEX idx_history_created_at (created_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS grammar_rules (
    id INT AUTO_INCREMENT PRIMARY KEY,
    keyword VARCHAR(120) NOT NULL,
    operation VARCHAR(40) NOT NULL,
    description VARCHAR(255) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_keyword (keyword)
) ENGINE=InnoDB;

INSERT IGNORE INTO grammar_rules (keyword, operation, description) VALUES
('sum', 'add', 'Prefix addition keyword'),
('difference', 'subtract', 'Prefix subtraction keyword'),
('product', 'multiply', 'Prefix multiplication keyword'),
('quotient', 'divide', 'Prefix division keyword'),
('add', 'add', 'Binary addition keyword'),
('subtract', 'subtract', 'Binary subtraction keyword'),
('multiply', 'multiply', 'Binary multiplication keyword'),
('divide', 'divide', 'Binary division keyword');
