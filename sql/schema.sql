-- Eduvos Marketplace Schema
-- Run once on first boot via Docker entrypoint

SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name        VARCHAR(100) NOT NULL,
    email       VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone       VARCHAR(20),
    is_admin    TINYINT(1) NOT NULL DEFAULT 0,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS listings (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED NOT NULL,
    title       VARCHAR(200) NOT NULL,
    description TEXT,
    price       DECIMAL(10,2),
    price_type  ENUM('fixed','wanted') NOT NULL DEFAULT 'fixed',
    category    VARCHAR(60) NOT NULL,
    image       VARCHAR(255),
    phone       VARCHAR(20),
    isbn        VARCHAR(20),
    author      VARCHAR(150),
    edition     VARCHAR(60),
    is_business TINYINT(1) NOT NULL DEFAULT 0,
    status      ENUM('available','pending','completed') NOT NULL DEFAULT 'available',
    qr_token    CHAR(64) NOT NULL UNIQUE,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS promotions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title       VARCHAR(150) NOT NULL,
    description TEXT,
    image       VARCHAR(255),
    link_url    VARCHAR(255),
    active      TINYINT(1) NOT NULL DEFAULT 1,
    created_at  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS transactions (
    id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    listing_id  INT UNSIGNED NOT NULL,
    seller_id   INT UNSIGNED NOT NULL,
    buyer_id    INT UNSIGNED NOT NULL,
    qr_token    CHAR(64) NOT NULL,
    confirmed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (listing_id) REFERENCES listings(id) ON DELETE CASCADE,
    FOREIGN KEY (seller_id)  REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (buyer_id)   REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

SET FOREIGN_KEY_CHECKS = 1;
