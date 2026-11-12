-- =============================================================
-- DriveDeal - database tables (COS30043 group project)
--
-- Import order:
--   1. car_marketplace_group.sql        (this file - creates the tables)
--   2. car_marketplace_sample_data.sql  (fills them with sample rows)
--
-- Local:   create an empty database called car_marketplace first,
--          select it in phpMyAdmin, then Import this file.
-- Mercury: select your own database (s<id>_db) and import - no CREATE DATABASE needed.
--
-- Re-importing is safe: every table is dropped and created again.
-- Changing a table? Tell the group first - everyone's pages depend on these names.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS inquiries;
DROP TABLE IF EXISTS listing_likes;
DROP TABLE IF EXISTS car_images;
DROP TABLE IF EXISTS car_listings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- -------------------------------------------------------------
-- users - everyone who can log in (buyers and sellers are both "user")
-- -------------------------------------------------------------
CREATE TABLE users (
  user_id       INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  full_name     VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL,
  password_hash VARCHAR(255)  NOT NULL,           -- from PHP password_hash(), never plain text
  phone         VARCHAR(20)   NULL,
  role          ENUM('user', 'admin') NOT NULL DEFAULT 'user',
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- car_listings - one row per car for sale (seller_id = who listed it)
-- -------------------------------------------------------------
CREATE TABLE car_listings (
  listing_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  seller_id     INT UNSIGNED  NOT NULL,
  make          VARCHAR(50)   NOT NULL,           -- e.g. Perodua
  model         VARCHAR(50)   NOT NULL,           -- e.g. Myvi
  year          SMALLINT UNSIGNED NOT NULL,
  price         DECIMAL(12,2) NOT NULL,           -- RM
  mileage_km    INT UNSIGNED  NOT NULL DEFAULT 0,
  transmission  ENUM('Automatic', 'Manual') NOT NULL DEFAULT 'Automatic',
  fuel_type     ENUM('Petrol', 'Diesel', 'Hybrid', 'Electric') NOT NULL DEFAULT 'Petrol',
  body_type     VARCHAR(30)   NULL,               -- Sedan, Hatchback, SUV, MPV, Pickup
  colour        VARCHAR(30)   NULL,
  location      VARCHAR(100)  NULL,               -- e.g. Subang Jaya, Selangor
  description   TEXT          NULL,
  status        ENUM('available', 'reserved', 'sold') NOT NULL DEFAULT 'available',
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (listing_id),
  KEY idx_listings_seller (seller_id),
  KEY idx_listings_make (make),
  KEY idx_listings_price (price),
  CONSTRAINT fk_listings_seller FOREIGN KEY (seller_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- car_images - photos of a car; the lowest sort_order is the cover photo
-- -------------------------------------------------------------
CREATE TABLE car_images (
  image_id      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  listing_id    INT UNSIGNED  NOT NULL,
  image_path    VARCHAR(255)  NOT NULL,           -- relative, e.g. uploads/abc123.jpg
  sort_order    TINYINT UNSIGNED NOT NULL DEFAULT 0,
  uploaded_at   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (image_id),
  KEY idx_images_listing (listing_id, sort_order),
  CONSTRAINT fk_images_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- listing_likes - who liked which car (the pair is the key, so nobody can like twice)
-- -------------------------------------------------------------
CREATE TABLE listing_likes (
  user_id       INT UNSIGNED  NOT NULL,
  listing_id    INT UNSIGNED  NOT NULL,
  liked_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, listing_id),
  KEY idx_likes_listing (listing_id),
  CONSTRAINT fk_likes_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_likes_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- inquiries - "Contact seller" messages from a buyer about one car
-- -------------------------------------------------------------
CREATE TABLE inquiries (
  inquiry_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  listing_id    INT UNSIGNED  NOT NULL,
  buyer_id      INT UNSIGNED  NOT NULL,
  message       TEXT          NOT NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (inquiry_id),
  KEY idx_inquiries_listing (listing_id),
  KEY idx_inquiries_buyer (buyer_id),
  CONSTRAINT fk_inquiries_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_inquiries_buyer FOREIGN KEY (buyer_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- contact_messages - About page contact form (no login needed)
-- -------------------------------------------------------------
CREATE TABLE contact_messages (
  message_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  name          VARCHAR(100)  NOT NULL,
  email         VARCHAR(150)  NOT NULL,
  message       TEXT          NOT NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (message_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
