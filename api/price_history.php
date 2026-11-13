<?php
/*
 * price_history.php - Every price a car has had, for the chart on the Car detail page.
 * OWNER: Yati (D/HD feature).
 *
 * GET ?listing_id=1 ->
 * {
 *   "listing_id": 1,
 *   "points": [ { "price": 112000, "at": "2026-11-10 09:00:00" }, ..., { "price": 105000, "at": "now" } ],
 *   "lowest": 105000, "highest": 112000, "changes": 2
 * }
 * The first point is the price the car was listed at; the last is its price right now.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/price_alerts.php';

if (request_method() !== 'GET') fail('Method not allowed. Use GET.', 405);
require_watchlist_tables();

$listingId = positive_id(int_param('listing_id', 0));
if (!$listingId) fail('listing_id is required and must be a whole number.');

// NOW() from MySQL, so every time on the chart uses the database's clock
$car = db_select_one('SELECT price, created_at, NOW() AS now FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
if ($car === null) fail('Car not found.', 404);

$changes = db_select(
  'SELECT old_price, new_price, changed_at FROM price_history WHERE listing_id = ? ORDER BY changed_at, history_id',
  'i', array($listingId)
);

$points = array();
if (count($changes)) {
  // Starting price: before the first change. Listed date if the change came later, else the change time.
  $firstAt = strtotime($changes[0]['changed_at']) > strtotime($car['created_at']) ? $car['created_at'] : $changes[0]['changed_at'];
  $points[] = array('price' => (float) $changes[0]['old_price'], 'at' => $firstAt);
  foreach ($changes as $change) {
    $points[] = array('price' => (float) $change['new_price'], 'at' => $change['changed_at']);
  }
} else {
  $points[] = array('price' => (float) $car['price'], 'at' => $car['created_at']);
}
// Today's price as the last point, so the line reaches "now"
$points[] = array('price' => (float) $car['price'], 'at' => $car['now']);

$prices = array_map(function ($p) { return $p['price']; }, $points);
reply(array(
  'listing_id' => $listingId,
  'points' => $points,
  'lowest' => min($prices),
  'highest' => max($prices),
  'changes' => count($changes)
));
