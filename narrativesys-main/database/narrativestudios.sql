-- Narrative Studios | MySQL schema
CREATE DATABASE IF NOT EXISTS narrativestudios CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE narrativestudios;

CREATE TABLE IF NOT EXISTS packages (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name          VARCHAR(120)  NOT NULL,                 -- e.g. "All-In"
  description   VARCHAR(255)  NOT NULL,                 -- e.g. "Perfect for Groups"
  price         DECIMAL(10,2) NOT NULL,
  duration      VARCHAR(60)   NOT NULL DEFAULT '',      -- e.g. "1 hour"
  banner_photo  VARCHAR(255)  NULL,                     -- file name in /uploads/packages
  status        ENUM('available','unavailable') NOT NULL DEFAULT 'available',
  is_popular    TINYINT(1)    NOT NULL DEFAULT 0,
  sort_order    INT           NOT NULL DEFAULT 0,
  created_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX idx_status (status)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS package_inclusions (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  package_id  INT UNSIGNED NOT NULL,
  inclusion   VARCHAR(255) NOT NULL,
  sort_order  INT NOT NULL DEFAULT 0,
  CONSTRAINT fk_incl_package FOREIGN KEY (package_id) REFERENCES packages(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Sample record
INSERT INTO packages (name, description, price, duration, status, is_popular, sort_order)
VALUES ('All-In', 'Perfect for Groups', 1750.00, '1 hour', 'available', 1, 1);

SET @pid = LAST_INSERT_ID();
INSERT INTO package_inclusions (package_id, inclusion, sort_order) VALUES
(@pid, 'Good for 1 to 6 pax', 1),
(@pid, '30 Minutes Unlimited Self-Photo Shoot', 2),
(@pid, '3 Plain Backdrop Colors', 3),
(@pid, '2 pcs 4R Prints', 4),
(@pid, '2 pcs Photo Strip Prints', 5),
(@pid, '4 pcs Photocard Prints', 6),
(@pid, 'Digital Copies of All Printed Photos', 7),
(@pid, 'Digital copies of All shots taken', 8),
(@pid, 'Studio use duration: 60 minutes (includes setup/prep time, shooting time & selection of photos)', 9),
(@pid, 'Photo processing time: Within 24 hours', 10);


-- ---------------------------------------------------------------
-- Accounts (login / register). Roles: customer (default), staff, admin.
-- Create the first admin from the command line:
--   php auth/create_admin.php admin@narrativestudio.ph "Admin@12345"
-- ---------------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
  id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  first_name      VARCHAR(60)  NOT NULL,
  last_name       VARCHAR(60)  NOT NULL,
  email           VARCHAR(190) NOT NULL UNIQUE,
  phone           VARCHAR(20)  NOT NULL,
  password_hash   VARCHAR(255) NOT NULL,                           -- bcrypt hash, never the plain password
  face_descriptor JSON NULL,                                       -- face-api.js 128-value face template; biometric data
  role            ENUM('customer', 'staff', 'admin') NOT NULL DEFAULT 'customer',
  consent_at      DATETIME NULL,                                   -- when the user accepted the data privacy terms
  failed_attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,             -- wrong passwords in a row
  locked_until    INT UNSIGNED NOT NULL DEFAULT 0,                 -- unix time; login blocked until then
  created_at      DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
