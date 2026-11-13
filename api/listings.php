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

/* ---------------------------------------------------------------
 * POST / PUT / DELETE - selling (Lynn)
 *
 *   POST   { seller_id, make, model, year, price, mileage_km, transmission, fuel_type,
 *            body_type, colour, location, description }            -> { "listing_id": 16 }
 *   PUT    { listing_id, seller_id, ...any of the fields above, status } -> { "ok": true }
 *          Fields you leave out keep their current value, so { listing_id, seller_id, status: 'sold' } works.
 *   DELETE { listing_id, seller_id }                               -> { "ok": true }
 *
 * Errors: 400 bad input, 403 not your car, 404 car not found.
 * Same rules as src/components/CarForm.vue:
 *   make, model    required, max 50          year        whole number 1980 to next year
 *   price          > 0 and <= 10,000,000     mileage_km  whole number 0 to 2,000,000
 *   transmission   Automatic | Manual        fuel_type   Petrol | Diesel | Hybrid | Electric
 *   body_type, colour  optional, max 30      location    optional, max 100
 *   description    optional, max 2000        status (PUT) available | reserved | sold
 * --------------------------------------------------------------- */

/** A whole number from JSON (5, "5" or 5.0), or null if it isn't one. */
function whole_number($value) {
  if (is_int($value)) return $value;
  if (is_float($value) && floor($value) == $value) return (int) $value;
  if (is_string($value) && preg_match('/^\s*-?\d+\s*$/', $value)) return (int) $value;
  return null;
}

/** Length of a text in characters (multibyte-safe when mbstring exists). */
function text_length($text) {
  return function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
}

/**
 * Check and clean the car fields. Returns array($clean, $errors):
 * $clean has trimmed text ('' for optional text becomes null), $errors is field => message.
 */
function validate_car($input) {
  $errors = array();
  $clean = array();

  // Text fields: name => array(label, max length, required)
  $textFields = array(
    'make'        => array('Make', 50, true),
    'model'       => array('Model', 50, true),
    'body_type'   => array('Body type', 30, false),
    'colour'      => array('Colour', 30, false),
    'location'    => array('Location', 100, false),
    'description' => array('Description', 2000, false)
  );
  foreach ($textFields as $name => $rule) {
    $value = isset($input[$name]) ? $input[$name] : '';
    if (is_int($value) || is_float($value)) $value = (string) $value;
    if (!is_string($value)) {
      $errors[$name] = $rule[0] . ' must be text.';
      continue;
    }
    $value = trim($value);
    if ($value === '') {
      if ($rule[2]) $errors[$name] = $rule[0] . ' is required.';
      $clean[$name] = $rule[2] ? '' : null;
    } elseif (text_length($value) > $rule[1]) {
      $errors[$name] = $rule[0] . ' must be ' . $rule[1] . ' characters or fewer.';
    } else {
      $clean[$name] = $value;
    }
  }

  $maxYear = (int) date('Y') + 1;
  $year = whole_number(isset($input['year']) ? $input['year'] : null);
  if ($year === null || $year < 1980 || $year > $maxYear) {
    $errors['year'] = 'Year must be a whole number from 1980 to ' . $maxYear . '.';
  } else {
    $clean['year'] = $year;
  }

  $price = isset($input['price']) ? $input['price'] : null;
  if (is_string($price)) $price = trim($price);
  if (!is_numeric($price) || (float) $price <= 0 || (float) $price > 10000000) {
    $errors['price'] = 'Price must be a number more than 0 and at most RM 10,000,000.';
  } else {
    $clean['price'] = round((float) $price, 2);
  }

  $mileage = whole_number(isset($input['mileage_km']) ? $input['mileage_km'] : null);
  if ($mileage === null || $mileage < 0 || $mileage > 2000000) {
    $errors['mileage_km'] = 'Mileage must be a whole number from 0 to 2,000,000 km.';
  } else {
    $clean['mileage_km'] = $mileage;
  }

  $choices = array(
    'transmission' => array('Transmission', array('Automatic', 'Manual')),
    'fuel_type'    => array('Fuel type', array('Petrol', 'Diesel', 'Hybrid', 'Electric'))
  );
  foreach ($choices as $name => $rule) {
    $value = isset($input[$name]) ? $input[$name] : '';
    if (!is_string($value) || !in_array(trim($value), $rule[1], true)) {
      $errors[$name] = $rule[0] . ' must be one of: ' . implode(', ', $rule[1]) . '.';
    } else {
      $clean[$name] = trim($value);
    }
  }

  return array($clean, $errors);
}

/** Stop with 400 and every field problem: { "error": "Make is required. ...", "fields": { "make": "..." } } */
function fail_fields($errors) {
  reply(array('error' => implode(' ', array_values($errors)), 'fields' => $errors), 400);
}

/** The listing_id / seller_id from the body as a whole number, or 400. */
function required_id($input, $name) {
  $id = whole_number(isset($input[$name]) ? $input[$name] : null);
  if ($id === null || $id <= 0) fail($name . ' is required and must be a whole number.');
  return $id;
}

/** Load the car and check this seller owns it: 404 if missing, 403 if someone else's. */
function owned_listing($listingId, $sellerId) {
  $car = db_select_one('SELECT * FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
  if ($car === null) fail('Car not found.', 404);
  if ((int) $car['seller_id'] !== $sellerId) fail('You can only edit your own listings.', 403);
  return $car;
}

/**
 * Delete an uploaded photo file from disk. Only files under uploads/, never uploads/sample/
 * (the shared sample photos), and never anything outside the uploads folder.
 */
function delete_upload_file($path) {
  $path = (string) $path;
  if (strpos($path, 'uploads/') !== 0 || strpos($path, 'uploads/sample/') === 0) return;
  if (strpos($path, '..') !== false || strpos($path, '\\') !== false) return;
  $uploadsDir = realpath(__DIR__ . '/../uploads');
  $file = realpath(__DIR__ . '/../' . $path);
  if ($uploadsDir === false || $file === false || !is_file($file)) return;
  if (dirname($file) !== $uploadsDir) return; // only files directly in uploads/
  @unlink($file);
}

function create_listing() {
  $input = json_input();
  $sellerId = required_id($input, 'seller_id');
  if (db_select_one('SELECT user_id FROM users WHERE user_id = ?', 'i', array($sellerId)) === null) {
    fail('Seller not found - please log in again.');
  }

  $result = validate_car($input);
  $car = $result[0];
  if (count($result[1])) fail_fields($result[1]);

  $stmt = db()->prepare(
    "INSERT INTO car_listings
       (seller_id, make, model, year, price, mileage_km, transmission, fuel_type,
        body_type, colour, location, description, status)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'available')"
  );
  $stmt->bind_param(
    'issidissssss',
    $sellerId, $car['make'], $car['model'], $car['year'], $car['price'], $car['mileage_km'],
    $car['transmission'], $car['fuel_type'], $car['body_type'], $car['colour'],
    $car['location'], $car['description']
  );
  $stmt->execute();
  $listingId = (int) $stmt->insert_id;
  $stmt->close();

  reply(array('listing_id' => $listingId));
}

function update_listing() {
  $input = json_input();
  $listingId = required_id($input, 'listing_id');
  $sellerId = required_id($input, 'seller_id');
  $existing = owned_listing($listingId, $sellerId);

  // Fields that were not sent keep their current value
  $merged = $existing;
  foreach ($input as $key => $value) $merged[$key] = $value;

  $result = validate_car($merged);
  $car = $result[0];
  $errors = $result[1];

  $status = is_string($merged['status']) ? trim($merged['status']) : '';
  if (!in_array($status, array('available', 'reserved', 'sold'), true)) {
    $errors['status'] = 'Status must be available, reserved or sold.';
  }
  if (count($errors)) fail_fields($errors);

  $stmt = db()->prepare(
    'UPDATE car_listings
        SET make = ?, model = ?, year = ?, price = ?, mileage_km = ?, transmission = ?,
            fuel_type = ?, body_type = ?, colour = ?, location = ?, description = ?, status = ?
      WHERE listing_id = ?'
  );
  $stmt->bind_param(
    'ssidisssssssi',
    $car['make'], $car['model'], $car['year'], $car['price'], $car['mileage_km'],
    $car['transmission'], $car['fuel_type'], $car['body_type'], $car['colour'],
    $car['location'], $car['description'], $status, $listingId
  );
  $stmt->execute();
  $stmt->close();

  // Yati - watchlist (D/HD): if the price changed, save it to price_history and alert
  // everyone watching this car. See api/price_alerts.php.
  require_once __DIR__ . '/price_alerts.php';
  record_price_change($listingId, $existing['price'], $car['price']);

  reply(array('ok' => true));
}

function delete_listing() {
  $input = json_input();
  $listingId = required_id($input, 'listing_id');
  $sellerId = required_id($input, 'seller_id');
  owned_listing($listingId, $sellerId);

  // Remove the photo files first; the car_images, likes and inquiries rows go with the car (ON DELETE CASCADE)
  foreach (car_images($listingId) as $image) delete_upload_file($image['image_path']);

  $stmt = db()->prepare('DELETE FROM car_listings WHERE listing_id = ?');
  $stmt->bind_param('i', $listingId);
  $stmt->execute();
  $stmt->close();

  reply(array('ok' => true));
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
    create_listing();

  case 'PUT':
    // OWNER: Lynn
    // Edit, owner only. Sends listing_id + car fields + seller_id -> { ok: true }
    update_listing();

  case 'DELETE':
    // OWNER: Lynn
    // Delete, owner only. Sends listing_id + seller_id -> { ok: true }
    delete_listing();

  default:
    fail('Method not allowed. Use GET, POST, PUT, DELETE.', 405);
}
