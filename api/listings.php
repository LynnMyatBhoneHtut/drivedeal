<?php
/*
 * listings.php - Car listings
 *
 * Method Owner
 * GET    Lai (read-only version done by Lynn)
 * POST   Lynn
 * PUT    Lynn
 * DELETE Lynn
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cars_common.php';

/* ---------------------------------------------------------------
 * GET - read-only listing API (written by Lynn so Lai can connect; Lai can extend it)
 *
 *   listings.php?id=3&user_id=2
 *     -> one car (fields in cars_common.php) + "images": [...] + "liked": true/false
 *        user_id is optional; without it "liked" is false.
 *
 *   listings.php?q=myvi&make=Perodua&minPrice=20000&maxPrice=60000&sort=price_asc&page=1&perPage=6
 *     -> { "cars": [...], "total": 14, "page": 1, "perPage": 6, "totalPages": 3, "makes": ["BMW", ...] }
 *     All filters are optional:
 *       q             words in make or model ("honda civic")
 *       make          exact make, e.g. Toyota
 *       minPrice / maxPrice    RM
 *       body_type     Sedan, Hatchback, SUV, MPV, Pickup
 *       transmission  Automatic or Manual
 *       seller_id     only this seller's cars (My listings)
 *       status        available | reserved | sold | all
 *                     default: everything except sold; with seller_id: all
 *       sort          newest (default), price_asc, price_desc, year_desc, year_asc, mileage_asc
 *       page          default 1
 *       perPage       default 6, max 100 (use 100 to load everything for client-side filtering)
 * --------------------------------------------------------------- */

function get_one_listing($id) {
  $car = db_select_one(CAR_SELECT . ' WHERE c.listing_id = ?', 'i', array($id));
  if ($car === null) fail('Car not found.', 404);

  $car = car_row($car);
  $car['images'] = car_images($id);
  $car['liked'] = user_liked(int_param('user_id', 0), $id);
  reply($car);
}

function get_listing_page() {
  $where = array();
  $types = '';
  $values = array();

  $q = str_param('q');
  if ($q !== '') {
    // every word must appear in make or model: "honda civic" finds Honda Civic
    foreach (preg_split('/\s+/', $q) as $word) {
      $where[] = "CONCAT(c.make, ' ', c.model) LIKE ?";
      $types .= 's';
      $values[] = '%' . $word . '%';
    }
  }

  $make = str_param('make');
  if ($make !== '') {
    $where[] = 'c.make = ?';
    $types .= 's';
    $values[] = $make;
  }

  $minPrice = int_param('minPrice');
  if ($minPrice !== null) {
    $where[] = 'c.price >= ?';
    $types .= 'd';
    $values[] = $minPrice;
  }

  $maxPrice = int_param('maxPrice');
  if ($maxPrice !== null) {
    $where[] = 'c.price <= ?';
    $types .= 'd';
    $values[] = $maxPrice;
  }

  foreach (array('body_type', 'transmission') as $column) {
    $value = str_param($column);
    if ($value !== '') {
      $where[] = "c.$column = ?"; // $column comes from the fixed list above, never from the user
      $types .= 's';
      $values[] = $value;
    }
  }

  $sellerId = int_param('seller_id');
  if ($sellerId !== null) {
    $where[] = 'c.seller_id = ?';
    $types .= 'i';
    $values[] = $sellerId;
  }

  $status = str_param('status', $sellerId !== null ? 'all' : '');
  if ($status === '') {
    $where[] = "c.status <> 'sold'";
  } elseif ($status !== 'all') {
    if (!in_array($status, array('available', 'reserved', 'sold'), true)) {
      fail('status must be available, reserved, sold or all.');
    }
    $where[] = 'c.status = ?';
    $types .= 's';
    $values[] = $status;
  }

  // Only these sort orders are allowed, so nothing typed by the user goes into ORDER BY
  $sorts = array(
    'newest'      => 'c.created_at DESC, c.listing_id DESC',
    'price_asc'   => 'c.price ASC, c.listing_id',
    'price_desc'  => 'c.price DESC, c.listing_id',
    'year_desc'   => 'c.year DESC, c.price ASC',
    'year_asc'    => 'c.year ASC, c.price ASC',
    'mileage_asc' => 'c.mileage_km ASC, c.listing_id'
  );
  $sort = str_param('sort', 'newest');
  if (!isset($sorts[$sort])) {
    fail('Unknown sort. Use one of: ' . implode(', ', array_keys($sorts)) . '.');
  }

  $whereSql = count($where) ? ' WHERE ' . implode(' AND ', $where) : '';

  $count = db_select_one('SELECT COUNT(*) AS n FROM car_listings c' . $whereSql, $types, $values);
  $total = (int) $count['n'];

  $perPage = int_param('perPage', 6);
  $perPage = max(1, min(100, $perPage));
  $totalPages = max(1, (int) ceil($total / $perPage));
  $page = max(1, min($totalPages, int_param('page', 1)));
  $offset = ($page - 1) * $perPage;

  // LIMIT values are whole numbers we worked out ourselves, so they are safe to put in the SQL
  $rows = db_select(
    CAR_SELECT . $whereSql . ' ORDER BY ' . $sorts[$sort] . " LIMIT $offset, $perPage",
    $types,
    $values
  );

  $cars = array();
  foreach ($rows as $row) $cars[] = car_row($row);

  // Every make that has a car for sale - handy for a "Make" dropdown
  $makes = array();
  foreach (db_select("SELECT DISTINCT make FROM car_listings WHERE status <> 'sold' ORDER BY make") as $m) {
    $makes[] = $m['make'];
  }

  reply(array(
    'cars' => $cars,
    'total' => $total,
    'page' => $page,
    'perPage' => $perPage,
    'totalPages' => $totalPages,
    'makes' => $makes
  ));
}

switch (request_method()) {
  case 'GET':
    // OWNER: Lai (read-only version written by Lynn - see the notes above)
    $id = int_param('id');
    if ($id !== null) {
      get_one_listing($id);
    }
    get_listing_page();

  case 'POST':
    // OWNER: Lynn
    // Create. Sends car fields + seller_id -> { listing_id: 16 }
    not_built_yet('listings.php POST');

  case 'PUT':
    // OWNER: Lynn
    // Edit, owner only. Sends listing_id + car fields + seller_id -> { ok: true }
    not_built_yet('listings.php PUT');

  case 'DELETE':
    // OWNER: Lynn
    // Delete, owner only. Sends listing_id + seller_id -> { ok: true }
    not_built_yet('listings.php DELETE');

  default:
    fail('Method not allowed. Use GET, POST, PUT, DELETE.', 405);
}
