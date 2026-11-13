<?php
/*
 * watchlist.php - Cars a user is watching for a price drop. OWNER: Yati (D/HD feature).
 *
 * GET    ?user_id=5                 -> { "cars": [ car + price_when_added, target_price, added_at ] }
 * GET    ?user_id=5&listing_id=3    -> { "watching": true, "price_when_added": 48000, "target_price": null }
 * POST   { user_id, listing_id, target_price? }  start watching  -> the watchlist row
 * PUT    { user_id, listing_id, target_price }   change target (null = no target) -> the watchlist row
 * DELETE { user_id, listing_id }                 stop watching   -> { "watching": false }
 *
 * The page works out "RM / % cheaper since you saved it" itself (computed properties),
 * so this file only sends the two prices.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cars_common.php';
require_once __DIR__ . '/price_alerts.php';

require_watchlist_tables();

switch (request_method()) {
  case 'GET':
    $userId = positive_id(int_param('user_id', 0));
    if (!$userId) fail('Please log in to see your watchlist.', 401);

    $listingId = positive_id(int_param('listing_id', 0));
    if ($listingId) reply(watch_state($userId, $listingId));

    $rows = db_select(
      CAR_SELECT . '
        JOIN watchlist w ON w.listing_id = c.listing_id
       WHERE w.user_id = ?
       ORDER BY w.added_at DESC',
      'i', array($userId)
    );
    // CAR_SELECT does not include the watchlist columns, so read them separately (one query)
    $watchRows = db_select(
      'SELECT listing_id, price_when_added, target_price, added_at FROM watchlist WHERE user_id = ?',
      'i', array($userId)
    );
    $byListing = array();
    foreach ($watchRows as $w) $byListing[(int) $w['listing_id']] = $w;

    $cars = array();
    foreach ($rows as $row) {
      $car = car_row($row);
      $w = $byListing[$car['listing_id']];
      $car['price_when_added'] = (float) $w['price_when_added'];
      $car['target_price'] = $w['target_price'] === null ? null : (float) $w['target_price'];
      $car['added_at'] = $w['added_at'];
      $cars[] = $car;
    }
    reply(array('cars' => $cars));

  case 'POST':
    list($userId, $listingId, $car) = watch_ids();
    if ((int) $car['seller_id'] === $userId) fail('This is your own listing, so there is no need to watch it.');
    $input = json_input();
    $target = target_price_from($input, (float) $car['price']);

    // Watching twice just updates the target; the saved price stays the first one
    $stmt = db()->prepare(
      'INSERT INTO watchlist (user_id, listing_id, price_when_added, target_price) VALUES (?, ?, ?, ?)
       ON DUPLICATE KEY UPDATE target_price = VALUES(target_price)'
    );
    $price = (float) $car['price'];
    $stmt->bind_param('iidd', $userId, $listingId, $price, $target);
    $stmt->execute();
    $stmt->close();
    reply(watch_state($userId, $listingId), 201);

  case 'PUT':
    list($userId, $listingId, $car) = watch_ids();
    if (!watch_state($userId, $listingId)['watching']) fail('You are not watching this car.', 404);
    $target = target_price_from(json_input(), (float) $car['price']);
    $stmt = db()->prepare('UPDATE watchlist SET target_price = ? WHERE user_id = ? AND listing_id = ?');
    $stmt->bind_param('dii', $target, $userId, $listingId);
    $stmt->execute();
    $stmt->close();
    reply(watch_state($userId, $listingId));

  case 'DELETE':
    list($userId, $listingId) = watch_ids();
    $stmt = db()->prepare('DELETE FROM watchlist WHERE user_id = ? AND listing_id = ?');
    $stmt->bind_param('ii', $userId, $listingId);
    $stmt->execute();
    $stmt->close();
    reply(array('watching' => false, 'listing_id' => $listingId));

  default:
    fail('Method not allowed. Use GET, POST, PUT, DELETE.', 405);
}

// ---- Helpers ----

/** user_id + listing_id from the body, after checking both exist. Returns [userId, listingId, car]. */
function watch_ids() {
  $input = json_input();
  $userId = positive_id(isset($input['user_id']) ? $input['user_id'] : 0);
  $listingId = positive_id(isset($input['listing_id']) ? $input['listing_id'] : 0);
  if (!$userId) fail('Please log in to use the watchlist.', 401);
  if (!$listingId) fail('listing_id is required and must be a whole number.');
  if (db_select_one('SELECT 1 AS yes FROM users WHERE user_id = ?', 'i', array($userId)) === null) {
    fail('Your account was not found. Please log in again.', 401);
  }
  $car = db_select_one('SELECT listing_id, seller_id, price FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
  if ($car === null) fail('Car not found.', 404);
  return array($userId, $listingId, $car);
}

/**
 * Optional target price: empty/null = no target. Must be a positive number below the current price
 * (a target above the price would be reached already).
 */
function target_price_from($input, $currentPrice) {
  if (!array_key_exists('target_price', $input)) return null;
  $value = $input['target_price'];
  if ($value === null || $value === '') return null;
  if (!is_numeric($value) || (float) $value <= 0) {
    reply(array('error' => 'Target price must be a number above 0.', 'fields' => array('target_price' => 'Target price must be a number above 0.')), 400);
  }
  $value = round((float) $value, 2);
  if ($value >= $currentPrice) {
    $message = 'Target price must be lower than the current price (' . format_rm($currentPrice) . ').';
    reply(array('error' => $message, 'fields' => array('target_price' => $message)), 400);
  }
  return $value;
}

/** Is this user watching this car, and with what saved price / target? */
function watch_state($userId, $listingId) {
  $row = db_select_one(
    'SELECT price_when_added, target_price, added_at FROM watchlist WHERE user_id = ? AND listing_id = ?',
    'ii', array($userId, $listingId)
  );
  if ($row === null) return array('watching' => false, 'listing_id' => $listingId);
  return array(
    'watching' => true,
    'listing_id' => $listingId,
    'price_when_added' => (float) $row['price_when_added'],
    'target_price' => $row['target_price'] === null ? null : (float) $row['target_price'],
    'added_at' => $row['added_at']
  );
}
