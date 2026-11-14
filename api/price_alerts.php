<?php
/*
 * Price-drop alerts - the "event" side of Yati's watchlist feature. OWNER: Yati.
 *
 * listings.php calls record_price_change() right after a seller saves a listing.
 * If the price changed, this:
 *   1. writes a row to price_history (for the price chart), and
 *   2. if the price went DOWN, creates one notification for every user watching that car
 *      ("price_drop"), or a stronger "target_reached" alert for watchers whose
 *      target price has just been reached.
 * The browser never sends notifications itself: they are created on the server at the moment
 * the price changes, so they cannot be skipped or faked. The bell in the navbar picks them up.
 */

/** "RM 105,000" or "RM 105,000.50" */
function format_rm($amount) {
  $amount = (float) $amount;
  $decimals = floor($amount) == $amount ? 0 : 2;
  return 'RM ' . number_format($amount, $decimals);
}

/**
 * Call after a listing's price is saved. Returns how many watchers were alerted.
 * Does nothing if the watchlist tables have not been created yet, so Lynn's edit page
 * keeps working on a database that only has the group tables.
 */
function record_price_change($listingId, $oldPrice, $newPrice) {
  $oldPrice = round((float) $oldPrice, 2);
  $newPrice = round((float) $newPrice, 2);
  if ($oldPrice == $newPrice) return 0;
  if (!watchlist_tables_exist()) return 0;

  $stmt = db()->prepare('INSERT INTO price_history (listing_id, old_price, new_price) VALUES (?, ?, ?)');
  $stmt->bind_param('idd', $listingId, $oldPrice, $newPrice);
  $stmt->execute();
  $historyId = $stmt->insert_id;
  $stmt->close();

  // A price rise is recorded for the chart, but nobody is alerted
  if ($newPrice > $oldPrice) return 0;

  $car = db_select_one('SELECT make, model FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
  $name = $car ? $car['make'] . ' ' . $car['model'] : 'A car you are watching';
  $drop = $oldPrice - $newPrice;
  $percent = $oldPrice > 0 ? round($drop / $oldPrice * 100, 1) : 0;

  $watchers = db_select(
    'SELECT user_id, target_price FROM watchlist WHERE listing_id = ?',
    'i', array($listingId)
  );

  $insert = db()->prepare(
    'INSERT INTO notifications (user_id, listing_id, history_id, type, message, old_price, new_price)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
  );
  foreach ($watchers as $watcher) {
    $target = $watcher['target_price'] === null ? null : (float) $watcher['target_price'];
    // "Target reached" only the first time the price crosses the target
    if ($target !== null && $newPrice <= $target && $oldPrice > $target) {
      $type = 'target_reached';
      $message = sprintf('%s is now %s - below your target of %s!', $name, format_rm($newPrice), format_rm($target));
    } else {
      $type = 'price_drop';
      $message = sprintf('%s dropped %s (%s%%) to %s.', $name, format_rm($drop), $percent, format_rm($newPrice));
    }
    $userId = (int) $watcher['user_id'];
    $insert->bind_param('iiissdd', $userId, $listingId, $historyId, $type, $message, $oldPrice, $newPrice);
    $insert->execute();
  }
  $insert->close();

  return count($watchers);
}

/** true once database/drivedeal.sql (with the watchlist tables) has been imported. */
function watchlist_tables_exist() {
  static $exists = null;
  if ($exists === null) {
    $row = db_select_one(
      "SELECT COUNT(*) AS n FROM information_schema.tables
        WHERE table_schema = DATABASE() AND table_name IN ('watchlist', 'price_history', 'notifications')"
    );
    $exists = $row !== null && (int) $row['n'] === 3;
  }
  return $exists;
}

/** Shared check for the watchlist endpoints: id from the JSON body or the query string. */
function positive_id($value) {
  return is_numeric($value) && (int) $value > 0 ? (int) $value : 0;
}

/** Fail with a clear message if the watchlist tables are missing. */
function require_watchlist_tables() {
  if (!watchlist_tables_exist()) {
    fail('The watchlist tables are missing. Import database/drivedeal.sql first.', 500);
  }
}
