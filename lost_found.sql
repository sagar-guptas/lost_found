-- ============================================
-- Lost & Found System — Full Database Setup
-- Version 2.0: OTP Auth, Admin Hierarchy,
-- Approval Workflows, Claims, Analytics
-- ============================================

CREATE DATABASE IF NOT EXISTS lost_found_db;
USE lost_found_db;

-- Drop existing tables (order matters for foreign keys)
DROP TABLE IF EXISTS claims;
DROP TABLE IF EXISTS items;
DROP TABLE IF EXISTS users;

-- ============================================
-- USERS TABLE
-- Roles: user, sub_admin, super_admin
-- OTP fields for email verification
-- ============================================
CREATE TABLE users (
    id            INT          AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    phone         VARCHAR(20)  NOT NULL,
    password      VARCHAR(255) NOT NULL,
    user_type     ENUM('student', 'faculty', 'admin_request') NOT NULL DEFAULT 'student',
    role          ENUM('user', 'sub_admin', 'super_admin') DEFAULT 'user',
    is_verified   TINYINT(1)   DEFAULT 0,
    otp           VARCHAR(6)   NULL,
    otp_expiry    DATETIME     NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- ITEMS TABLE
-- Status workflow: pending → approved → claimed → resolved
--                  pending → rejected
-- ============================================
CREATE TABLE items (
    id            INT          AUTO_INCREMENT PRIMARY KEY,
    title         VARCHAR(255) NOT NULL,
    description   TEXT         NOT NULL,
    type          ENUM('Lost', 'Found') NOT NULL,
    category      VARCHAR(50)  NOT NULL DEFAULT 'Other',
    location      VARCHAR(255) NOT NULL DEFAULT '',
    status        ENUM('pending', 'approved', 'rejected', 'claimed', 'resolved') DEFAULT 'pending',
    user_id       INT          NULL,
    approved_by   INT          NULL,
    resolved_at   DATETIME     NULL,
    date          TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)     REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- CLAIMS TABLE
-- When someone claims they found a lost item
-- ============================================
CREATE TABLE claims (
    id            INT          AUTO_INCREMENT PRIMARY KEY,
    item_id       INT          NOT NULL,
    claimer_id    INT          NOT NULL,
    message       TEXT         NOT NULL,
    status        ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    reviewed_by   INT          NULL,
    created_at    TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (item_id)     REFERENCES items(id) ON DELETE CASCADE,
    FOREIGN KEY (claimer_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================
-- DEFAULT SUPER ADMIN
-- Email: admin@lostfound.com | Password: admin123
-- ============================================
INSERT INTO users (full_name, email, phone, password, user_type, role, is_verified) VALUES
('Super Admin', 'admin@lostfound.com', '9999999999',
 '$2y$10$rM3lPFxiA6VEw6XAV.BpD.kZPzHL.bDsRdsFI/aQ.HRLqsbWdUlgG',
 'admin_request', 'super_admin', 1);

-- ============================================
-- SAMPLE DATA: 3 approved items
-- ============================================
INSERT INTO items (title, description, type, category, location, status, user_id) VALUES
('Blue Leather Wallet', 'Found a blue leather wallet near the campus library entrance. Contains some cards but no cash. Contact to verify contents and claim.', 'Found', 'Wallets', 'Campus Library', 'approved', 1),
('Silver Wristwatch', 'Lost my silver analog wristwatch with a brown leather strap somewhere around the main parking lot on March 25th. Sentimental value.', 'Lost', 'Accessories', 'Main Parking Lot', 'approved', 1),
('Set of Car Keys', 'Found a set of car keys with a red keychain and two silver keys on the bench outside the cafeteria.', 'Found', 'Keys', 'Cafeteria', 'approved', 1);
