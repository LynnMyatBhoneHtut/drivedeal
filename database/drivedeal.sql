-- =============================================================
-- DriveDeal - the whole database in one file (COS30043 group project)
--
-- Import this ONE file. It creates every table and fills it with sample data.
--   Local:   in phpMyAdmin create an empty database called car_marketplace,
--            select it, then Import this file.
--   Mercury: select your own database (s<id>_db) and import - no CREATE DATABASE needed.
--
-- Re-importing is safe: every table is dropped and created again, so this also
-- resets the data to the samples.
--
-- Contents
--   Part 1  Core marketplace tables  users, car_listings, car_images, listing_likes,
--                                     inquiries, contact_messages            (group)
--   Part 2  Price-Drop Alert & Watchlist  watchlist, price_history,
--                                     notifications                          (Yati)
--   Part 3  Make-an-Offer & Negotiation  offers, offer_events, test_drives   (Lynn)
--   Part 4  Sample data for all of the above
--
-- Every sample user's password is:  password123
--   user 1 Mei Ling Tan (admin), user 2 Ahmad Faizal (the fake-login user),
--   user 3 Priya Raj, user 4 Jason Wong, user 5 Sarah Lim
-- Sample photos live in uploads/sample/ (committed to Git).
--
-- Changing a table? Tell the group first - everyone's pages depend on these names.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- Children first, then the tables they point to
DROP TABLE IF EXISTS test_drives;
DROP TABLE IF EXISTS offer_events;
DROP TABLE IF EXISTS offers;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS price_history;
DROP TABLE IF EXISTS watchlist;
DROP TABLE IF EXISTS contact_messages;
DROP TABLE IF EXISTS inquiries;
DROP TABLE IF EXISTS listing_likes;
DROP TABLE IF EXISTS car_images;
DROP TABLE IF EXISTS car_listings;
DROP TABLE IF EXISTS users;

SET FOREIGN_KEY_CHECKS = 1;

-- =============================================================
-- Part 1 - Core marketplace tables (group)
-- =============================================================

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

-- =============================================================
-- Part 2 - Price-Drop Alert & Watchlist (Yati's advanced feature)
--   watchlist      - which user watches which car, the price when they saved it, and an
--                    optional target price
--   price_history  - one row every time a seller changes a car's price (api/price_alerts.php)
--   notifications  - one in-app alert per watcher when a watched car's price drops
-- =============================================================

CREATE TABLE watchlist (
  user_id           INT UNSIGNED  NOT NULL,
  listing_id        INT UNSIGNED  NOT NULL,
  price_when_added  DECIMAL(12,2) NOT NULL,          -- RM, so the page can show "RM 3,000 cheaper since you saved it"
  target_price      DECIMAL(12,2) NULL,              -- optional alert threshold
  added_at          DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (user_id, listing_id),                 -- a user watches a car at most once
  KEY idx_watchlist_listing (listing_id),
  CONSTRAINT fk_watchlist_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_watchlist_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE price_history (
  history_id    INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  listing_id    INT UNSIGNED  NOT NULL,
  old_price     DECIMAL(12,2) NOT NULL,
  new_price     DECIMAL(12,2) NOT NULL,
  changed_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (history_id),
  KEY idx_history_listing (listing_id, changed_at),
  CONSTRAINT fk_history_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
  notification_id INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  user_id         INT UNSIGNED  NOT NULL,             -- who receives it
  listing_id      INT UNSIGNED  NOT NULL,
  history_id      INT UNSIGNED  NULL,                 -- the price change that caused it
  type            ENUM('price_drop', 'target_reached') NOT NULL DEFAULT 'price_drop',
  message         VARCHAR(255)  NOT NULL,
  old_price       DECIMAL(12,2) NOT NULL,
  new_price       DECIMAL(12,2) NOT NULL,
  is_read         TINYINT(1)    NOT NULL DEFAULT 0,
  created_at      DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (notification_id),
  KEY idx_notifications_user (user_id, is_read, notification_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id)
    REFERENCES users (user_id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_notifications_history FOREIGN KEY (history_id)
    REFERENCES price_history (history_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- Part 3 - Make-an-Offer & Negotiation with Test Drive Request (Lynn's advanced feature)
--   A buyer opens one negotiation per car. Buyer and seller take turns to counter or accept;
--   every step is an offer_events row. Within 10% of the seller's price the buyer can request a
--   test drive. Accepting a price reserves the car and closes the other open negotiations.
--   The rules are checked in api/offers_common.php and src/services/offerRules.js.
-- =============================================================

-- -------------------------------------------------------------
-- offers - one negotiation between a buyer and the seller of a car
-- -------------------------------------------------------------
CREATE TABLE offers (
  offer_id      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  listing_id    INT UNSIGNED  NOT NULL,
  buyer_id      INT UNSIGNED  NOT NULL,
  status        ENUM('open', 'accepted', 'rejected', 'withdrawn', 'closed') NOT NULL DEFAULT 'open',
                -- open: still negotiating; accepted: deal agreed (car reserved);
                -- rejected: seller ended it; withdrawn: buyer ended it;
                -- closed: the car was reserved for another buyer
  agreed_price  DECIMAL(12,2) NULL,               -- RM, set when a price is accepted
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (offer_id),
  KEY idx_offers_listing (listing_id),
  KEY idx_offers_buyer (buyer_id),
  CONSTRAINT fk_offers_listing FOREIGN KEY (listing_id)
    REFERENCES car_listings (listing_id) ON DELETE CASCADE,
  CONSTRAINT fk_offers_buyer FOREIGN KEY (buyer_id)
    REFERENCES users (user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- offer_events - the negotiation history, oldest first
-- -------------------------------------------------------------
CREATE TABLE offer_events (
  event_id      INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  offer_id      INT UNSIGNED  NOT NULL,
  actor         ENUM('buyer', 'seller', 'system') NOT NULL,
  action        ENUM('offer', 'counter', 'accept', 'reject', 'withdraw', 'close') NOT NULL,
                -- offer: buyer's first price; counter: a new price from either side;
                -- accept: took the other side's latest price; reject / withdraw / close: ended
  amount        DECIMAL(12,2) NULL,               -- RM, for offer / counter / accept
  message       VARCHAR(500)  NULL,
  created_at    DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (event_id),
  KEY idx_events_offer (offer_id),
  CONSTRAINT fk_events_offer FOREIGN KEY (offer_id)
    REFERENCES offers (offer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- -------------------------------------------------------------
-- test_drives - a buyer's request to view / test drive the car
-- -------------------------------------------------------------
CREATE TABLE test_drives (
  test_drive_id  INT UNSIGNED NOT NULL AUTO_INCREMENT,
  offer_id       INT UNSIGNED NOT NULL,
  preferred_date DATE         NOT NULL,
  preferred_time CHAR(5)      NOT NULL,           -- 'HH:MM', on the hour from 09:00 to 17:00
  note           VARCHAR(300) NULL,
  status         ENUM('requested', 'confirmed', 'declined', 'cancelled') NOT NULL DEFAULT 'requested',
  created_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at     DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (test_drive_id),
  KEY idx_test_drives_offer (offer_id),
  CONSTRAINT fk_test_drives_offer FOREIGN KEY (offer_id)
    REFERENCES offers (offer_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================================
-- Part 4 - Sample data
-- =============================================================

-- ---------- Core: users, cars, photos, likes, inquiries, contact messages ----------

INSERT INTO users (user_id, full_name, email, password_hash, phone, role, created_at) VALUES
  (1, 'Mei Ling Tan', 'meiling@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '012-345 6789', 'admin', NOW() - INTERVAL 59 DAY),
  (2, 'Ahmad Faizal', 'ahmad@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '013-222 4455', 'user', NOW() - INTERVAL 58 DAY),
  (3, 'Priya Raj', 'priya@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '016-777 1020', 'user', NOW() - INTERVAL 57 DAY),
  (4, 'Jason Wong', 'jason@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '017-888 3141', 'user', NOW() - INTERVAL 56 DAY),
  (5, 'Sarah Lim', 'sarah@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '019-555 2718', 'user', NOW() - INTERVAL 55 DAY);

INSERT INTO car_listings (listing_id, seller_id, make, model, year, price, mileage_km, transmission, fuel_type, body_type, colour, location, description, status, created_at) VALUES
  (1, 1, 'Toyota', 'Corolla Cross', 2022, 105000.00, 35000, 'Automatic', 'Petrol', 'SUV', 'White', 'Subang Jaya, Selangor', 'Full Toyota service record, one owner, still under warranty. Reverse camera and 360 sensors.', 'available', NOW() - INTERVAL 2 DAY),
  (2, 3, 'Honda', 'Civic', 2021, 89000.00, 48000, 'Automatic', 'Petrol', 'Sedan', 'Black', 'Petaling Jaya, Selangor', '1.5 TC-P turbo. Non-smoker, no accident, new tyres fitted last month.', 'available', NOW() - INTERVAL 5 DAY),
  (3, 2, 'Perodua', 'Myvi', 2023, 48000.00, 18000, 'Automatic', 'Petrol', 'Hatchback', 'Red', 'Shah Alam, Selangor', '1.5 AV with Advanced Safety Assist. Low mileage, like new.', 'available', NOW() - INTERVAL 1 DAY),
  (4, 4, 'Proton', 'X50', 2022, 82000.00, 30000, 'Automatic', 'Petrol', 'SUV', 'Grey', 'Kuala Lumpur', '1.5 TGDi Flagship. Panoramic roof, ADAS, full Proton service history.', 'available', NOW() - INTERVAL 8 DAY),
  (5, 5, 'Perodua', 'Axia', 2020, 26500.00, 62000, 'Automatic', 'Petrol', 'Hatchback', 'Silver', 'Klang, Selangor', 'Very fuel efficient city car. Ideal first car for students.', 'available', NOW() - INTERVAL 12 DAY),
  (6, 2, 'Proton', 'Saga', 2019, 22000.00, 78000, 'Manual', 'Petrol', 'Sedan', 'Blue', 'Ipoh, Perak', 'Manual 1.3 Standard. Cheap to maintain, road tax until next year.', 'available', NOW() - INTERVAL 20 DAY),
  (7, 1, 'Honda', 'City', 2020, 62000.00, 55000, 'Automatic', 'Petrol', 'Sedan', 'White', 'Cheras, Kuala Lumpur', '1.5 V spec. Spacious boot, Honda Sensing, serviced at Honda.', 'available', NOW() - INTERVAL 4 DAY),
  (8, 3, 'Toyota', 'Vios', 2018, 48500.00, 90000, 'Automatic', 'Petrol', 'Sedan', 'Silver', 'Johor Bahru, Johor', 'Reliable daily car. Recently serviced, aircond cold.', 'available', NOW() - INTERVAL 15 DAY),
  (9, 4, 'Mazda', 'CX-5', 2021, 118000.00, 41000, 'Automatic', 'Petrol', 'SUV', 'Red', 'George Town, Penang', '2.5 High spec in Soul Red. Bose sound system, leather seats.', 'available', NOW() - INTERVAL 6 DAY),
  (10, 5, 'Toyota', 'Hilux', 2019, 86000.00, 98000, 'Manual', 'Diesel', 'Pickup', 'White', 'Seremban, Negeri Sembilan', '2.4 4x4 with canopy. Strong engine, good for work and outstation trips.', 'available', NOW() - INTERVAL 18 DAY),
  (11, 2, 'Honda', 'HR-V', 2023, 128000.00, 12000, 'Automatic', 'Hybrid', 'SUV', 'Blue', 'Puchong, Selangor', 'e:HEV RS. Hybrid, very low mileage, full warranty. Deposit received.', 'reserved', NOW() - INTERVAL 3 DAY),
  (12, 1, 'Proton', 'X70', 2021, 95000.00, 46000, 'Automatic', 'Petrol', 'SUV', 'Black', 'Cyberjaya, Selangor', '1.8 TGDi Premium. 7-speed DCT, powered tailgate.', 'available', NOW() - INTERVAL 10 DAY),
  (13, 3, 'Perodua', 'Alza', 2022, 68000.00, 28000, 'Automatic', 'Petrol', 'MPV', 'Grey', 'Bangi, Selangor', '1.5 AV, 7 seats. Perfect family car with Perodua Smart Drive Assist.', 'available', NOW() - INTERVAL 7 DAY),
  (14, 4, 'BMW', '330e', 2020, 168000.00, 52000, 'Automatic', 'Hybrid', 'Sedan', 'White', 'Mont Kiara, Kuala Lumpur', 'M Sport plug-in hybrid. Full BMW service record, extended warranty.', 'available', NOW() - INTERVAL 9 DAY),
  (15, 5, 'Nissan', 'Almera', 2021, 64000.00, 39000, 'Automatic', 'Petrol', 'Sedan', 'Orange', 'Kajang, Selangor', '1.0 Turbo VLT. Sold - kept here so the sold status can be tested.', 'sold', NOW() - INTERVAL 25 DAY);

INSERT INTO car_images (listing_id, image_path, sort_order) VALUES
  (1, 'uploads/sample/listing-1-1.jpg', 0),
  (1, 'uploads/sample/listing-1-2.jpg', 1),
  (2, 'uploads/sample/listing-2-1.jpg', 0),
  (2, 'uploads/sample/listing-2-2.jpg', 1),
  (3, 'uploads/sample/listing-3-1.jpg', 0),
  (3, 'uploads/sample/listing-3-2.jpg', 1),
  (4, 'uploads/sample/listing-4-1.jpg', 0),
  (4, 'uploads/sample/listing-4-2.jpg', 1),
  (5, 'uploads/sample/listing-5-1.jpg', 0),
  (5, 'uploads/sample/listing-5-2.jpg', 1),
  (6, 'uploads/sample/listing-6-1.jpg', 0),
  (6, 'uploads/sample/listing-6-2.jpg', 1),
  (7, 'uploads/sample/listing-7-1.jpg', 0),
  (7, 'uploads/sample/listing-7-2.jpg', 1),
  (8, 'uploads/sample/listing-8-1.jpg', 0),
  (8, 'uploads/sample/listing-8-2.jpg', 1),
  (9, 'uploads/sample/listing-9-1.jpg', 0),
  (9, 'uploads/sample/listing-9-2.jpg', 1),
  (10, 'uploads/sample/listing-10-1.jpg', 0),
  (10, 'uploads/sample/listing-10-2.jpg', 1),
  (11, 'uploads/sample/listing-11-1.jpg', 0),
  (11, 'uploads/sample/listing-11-2.jpg', 1),
  (12, 'uploads/sample/listing-12-1.jpg', 0),
  (12, 'uploads/sample/listing-12-2.jpg', 1),
  (13, 'uploads/sample/listing-13-1.jpg', 0),
  (13, 'uploads/sample/listing-13-2.jpg', 1),
  (14, 'uploads/sample/listing-14-1.jpg', 0),
  (14, 'uploads/sample/listing-14-2.jpg', 1),
  (15, 'uploads/sample/listing-15-1.jpg', 0),
  (15, 'uploads/sample/listing-15-2.jpg', 1);

INSERT INTO listing_likes (user_id, listing_id) VALUES
  (2, 1),
  (2, 4),
  (2, 9),
  (2, 14),
  (3, 1),
  (3, 3),
  (4, 1),
  (4, 3),
  (4, 11),
  (5, 1),
  (5, 9),
  (5, 3),
  (1, 3),
  (1, 11),
  (3, 9),
  (5, 14);

INSERT INTO inquiries (listing_id, buyer_id, message, created_at) VALUES
  (3, 4, 'Hi, is the Myvi still available? Can I view it this weekend?', NOW() - INTERVAL 1 DAY),
  (3, 5, 'Would you accept RM 45,000? I can pay in cash.', NOW() - INTERVAL 0 DAY),
  (6, 3, 'Hello, is the price negotiable? And has the timing belt been changed?', NOW() - INTERVAL 4 DAY),
  (11, 1, 'Hi Ahmad, if the deposit falls through please let me know. Very interested.', NOW() - INTERVAL 2 DAY),
  (1, 2, 'Hi, how many keys come with the car? Any scratches?', NOW() - INTERVAL 1 DAY),
  (9, 2, 'Is the CX-5 still under warranty?', NOW() - INTERVAL 3 DAY),
  (4, 5, 'Can I bring a mechanic to inspect the X50?', NOW() - INTERVAL 2 DAY);

INSERT INTO contact_messages (name, email, message) VALUES
  ('Daniel Lee', 'daniel@example.com', 'Hi, do you charge sellers a fee to list a car on DriveDeal?');

-- ---------- Watchlist (Yati) ----------

-- Sarah (user 5) watches 3 cars. The Corolla Cross (car 1) has already dropped twice, so the
-- watchlist page, price chart and bell have something to show straight away.

INSERT INTO price_history (history_id, listing_id, old_price, new_price, changed_at) VALUES
  (1, 1, 112000.00, 109000.00, NOW() - INTERVAL 40 HOUR),
  (2, 1, 109000.00, 105000.00, NOW() - INTERVAL 6 HOUR),
  (3, 2, 92000.00, 89000.00, NOW() - INTERVAL 3 DAY);

INSERT INTO watchlist (user_id, listing_id, price_when_added, target_price, added_at) VALUES
  (5, 1, 112000.00, 106000.00, NOW() - INTERVAL 2 DAY),
  (5, 2, 92000.00, NULL, NOW() - INTERVAL 4 DAY),
  (5, 4, 82000.00, 75000.00, NOW() - INTERVAL 1 DAY),
  (4, 1, 109000.00, NULL, NOW() - INTERVAL 30 HOUR);

INSERT INTO notifications (user_id, listing_id, history_id, type, message, old_price, new_price, is_read, created_at) VALUES
  (5, 2, 3, 'price_drop', 'Honda Civic dropped RM 3,000 (3.3%) to RM 89,000.', 92000.00, 89000.00, 1, NOW() - INTERVAL 3 DAY),
  (5, 1, 1, 'price_drop', 'Toyota Corolla Cross dropped RM 3,000 (2.7%) to RM 109,000.', 112000.00, 109000.00, 1, NOW() - INTERVAL 40 HOUR),
  (5, 1, 2, 'target_reached', 'Toyota Corolla Cross is now RM 105,000 - below your target of RM 106,000!', 109000.00, 105000.00, 0, NOW() - INTERVAL 6 HOUR),
  (4, 1, 2, 'price_drop', 'Toyota Corolla Cross dropped RM 4,000 (3.7%) to RM 105,000.', 109000.00, 105000.00, 0, NOW() - INTERVAL 6 HOUR);

-- ---------- Offers (Lynn) ----------

-- -------------------------------------------------------------
-- Demo negotiations (users and cars are the core sample data above)
--   user 1 Mei Ling (sells car 7 Honda City), user 2 Ahmad (sells car 3 Perodua Myvi),
--   user 3 Priya, user 4 Jason
-- -------------------------------------------------------------

-- 1. Priya and Ahmad on the Myvi: within 10%, test drive requested, Priya's turn
INSERT INTO offers (offer_id, listing_id, buyer_id, status, created_at) VALUES
  (1, 3, 3, 'open', NOW() - INTERVAL 2 DAY);
INSERT INTO offer_events (offer_id, actor, action, amount, message, created_at) VALUES
  (1, 'buyer',  'offer',   42000.00, 'Hi Ahmad, would you take RM 42,000? I can pay in full.', NOW() - INTERVAL 2 DAY),
  (1, 'seller', 'counter', 46000.00, 'Thanks Priya. It has a full service record, so RM 46,000 is my best.', NOW() - INTERVAL 1 DAY);
INSERT INTO test_drives (offer_id, preferred_date, preferred_time, note, status) VALUES
  (1, CURDATE() + INTERVAL 3 DAY, '10:00', 'Could we meet at the car park near your area?', 'requested');

-- 2. Jason and Ahmad on the Myvi: far apart (more than 10%), Ahmad's turn
INSERT INTO offers (offer_id, listing_id, buyer_id, status, created_at) VALUES
  (2, 3, 4, 'open', NOW() - INTERVAL 1 DAY);
INSERT INTO offer_events (offer_id, actor, action, amount, message, created_at) VALUES
  (2, 'buyer', 'offer', 38000.00, 'Is RM 38,000 possible?', NOW() - INTERVAL 1 DAY);

-- 3. Ahmad buying Mei Ling's Honda City: within 10%, Ahmad's turn to reply
INSERT INTO offers (offer_id, listing_id, buyer_id, status, created_at) VALUES
  (3, 7, 2, 'open', NOW() - INTERVAL 3 DAY);
INSERT INTO offer_events (offer_id, actor, action, amount, message, created_at) VALUES
  (3, 'buyer',  'offer',   55000.00, 'Hello, I am interested. Would RM 55,000 work?', NOW() - INTERVAL 3 DAY),
  (3, 'seller', 'counter', 60000.00, 'I can do RM 60,000.', NOW() - INTERVAL 2 DAY);
