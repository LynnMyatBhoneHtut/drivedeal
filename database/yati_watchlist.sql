-- DriveDeal - Price-Drop Alert & Watchlist (Yati's D/HD advanced feature). OWNER: Yati.
--
-- Run AFTER car_marketplace_group.sql and car_marketplace_sample_data.sql.
-- Adds 3 tables and some sample rows. Safe to run again: it drops and recreates only these 3 tables.
--
--   watchlist      - which user is watching which car, the price when they saved it,
--                    and an optional target price ("tell me when it goes below RM 45,000")
--   price_history  - one row every time a seller changes a car's price (written by api/price_alerts.php)
--   notifications  - one in-app alert per watcher when a watched car's price drops

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS price_history;
DROP TABLE IF EXISTS watchlist;
SET FOREIGN_KEY_CHECKS = 1;

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

-- ---------- Sample data ----------
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
