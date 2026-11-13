-- =============================================================
-- DriveDeal - Make-an-Offer & Negotiation System with Test Drive Request
-- Individual advanced feature (Lynn). COS30043.
--
-- Import order (after the group files):
--   1. car_marketplace_group.sql
--   2. car_marketplace_sample_data.sql
--   3. offers.sql                        (this file - tables + demo negotiations)
--
-- Re-importing is safe: the three tables are dropped and created again.
-- Re-import this file whenever you re-import the group files, so the demo rows match.
--
-- How it works (the same rules are checked in api/offers_common.php and src/services/offerRules.js):
--   * A buyer opens ONE negotiation (offers row) per car, with a first price offer.
--   * Buyer and seller take turns: counter-offer, accept the other side's latest price,
--     or end it (seller rejects / buyer withdraws). Every step is an offer_events row,
--     so both sides see the full negotiation history.
--   * When the buyer's latest offer is within 10% of the seller's latest price
--     (their counter-offer, or the asking price), the buyer can request a test drive.
--   * When a price is accepted, the deal is agreed: the car becomes 'reserved' and every
--     other open negotiation on that car is closed.
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS test_drives;
DROP TABLE IF EXISTS offer_events;
DROP TABLE IF EXISTS offers;

SET FOREIGN_KEY_CHECKS = 1;

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

-- -------------------------------------------------------------
-- Demo negotiations (users and cars come from car_marketplace_sample_data.sql)
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
