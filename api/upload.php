<?php
/*
 * upload.php - Car photo upload (form-data, not JSON: use $_POST and $_FILES)
 *
 * Method Owner
 * POST   Lynn
 * DELETE Lynn
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 *
 *   POST   (multipart form-data) image, listing_id, seller_id
 *          -> { "image_id": 31, "image_path": "uploads/<file>" }
 *          JPG / PNG / WEBP only, max 2 MB, max 8 photos per car. The first photo is the cover.
 *          Vue: const form = new FormData(); form.append('image', file);
 *               form.append('listing_id', 16); form.append('seller_id', user.user_id)
 *               await apiUpload('upload.php', form)
 *   DELETE (JSON) { image_id, seller_id } -> { "ok": true }   remove one photo (Edit listing)
 *
 * Errors: 400 bad input, 403 not your car, 404 car / photo not found.
 */

require_once __DIR__ . '/helpers.php';

define('MAX_PHOTO_BYTES', 2 * 1024 * 1024);
define('MAX_PHOTOS_PER_CAR', 8);

/** A whole number (5, "5" or 5.0), or null if it isn't one. */
function upload_whole_number($value) {
  if (is_int($value)) return $value;
  if (is_float($value) && floor($value) == $value) return (int) $value;
  if (is_string($value) && preg_match('/^\s*\d+\s*$/', $value)) return (int) $value;
  return null;
}

/** A required positive id from $source, or 400. */
function upload_required_id($source, $name) {
  $id = upload_whole_number(isset($source[$name]) ? $source[$name] : null);
  if ($id === null || $id <= 0) fail($name . ' is required and must be a whole number.');
  return $id;
}

/**
 * Delete an uploaded photo file from disk. Only files directly in uploads/, never
 * uploads/sample/ (the shared sample photos), and never anything outside the uploads folder.
 * (Same rule as delete_upload_file() in listings.php.)
 */
function upload_delete_file($path) {
  $path = (string) $path;
  if (strpos($path, 'uploads/') !== 0 || strpos($path, 'uploads/sample/') === 0) return;
  if (strpos($path, '..') !== false || strpos($path, '\\') !== false) return;
  $uploadsDir = realpath(__DIR__ . '/../uploads');
  $file = realpath(__DIR__ . '/../' . $path);
  if ($uploadsDir === false || $file === false || !is_file($file)) return;
  if (dirname($file) !== $uploadsDir) return;
  @unlink($file);
}

/** Clear message for a PHP upload error code. */
function upload_error_message($code) {
  if ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE) {
    return 'The photo is too large. The maximum size is 2 MB.';
  }
  if ($code === UPLOAD_ERR_PARTIAL) return 'The photo only partly uploaded. Please try again.';
  if ($code === UPLOAD_ERR_NO_FILE) return 'Choose a photo to upload.';
  return 'The server could not receive the photo (upload error ' . $code . ').';
}

function upload_photo() {
  // A file bigger than PHP's post_max_size empties $_POST and $_FILES completely
  if (!count($_POST) && !count($_FILES) && isset($_SERVER['CONTENT_LENGTH']) && (int) $_SERVER['CONTENT_LENGTH'] > 0) {
    fail('The photo is too large. The maximum size is 2 MB.');
  }

  $listingId = upload_required_id($_POST, 'listing_id');
  $sellerId = upload_required_id($_POST, 'seller_id');

  $car = db_select_one('SELECT seller_id FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
  if ($car === null) fail('Car not found.', 404);
  if ((int) $car['seller_id'] !== $sellerId) fail('You can only edit your own listings.', 403);

  if (!isset($_FILES['image']) || is_array($_FILES['image']['error'])) {
    fail('Choose one photo to upload (form field "image").');
  }
  $file = $_FILES['image'];
  if ($file['error'] !== UPLOAD_ERR_OK) {
    $code = (int) $file['error'];
    fail(upload_error_message($code), ($code === UPLOAD_ERR_INI_SIZE || $code === UPLOAD_ERR_FORM_SIZE || $code === UPLOAD_ERR_PARTIAL || $code === UPLOAD_ERR_NO_FILE) ? 400 : 500);
  }
  if ($file['size'] > MAX_PHOTO_BYTES) fail('The photo is too large. The maximum size is 2 MB.');
  if (!is_uploaded_file($file['tmp_name'])) fail('Invalid upload.');

  // Detect the real type from the file contents (never trust the file name)
  $info = @getimagesize($file['tmp_name']);
  $types = array(IMAGETYPE_JPEG => 'jpg', IMAGETYPE_PNG => 'png');
  if (defined('IMAGETYPE_WEBP')) $types[IMAGETYPE_WEBP] = 'webp';
  $extension = null;
  if ($info !== false && isset($types[$info[2]])) {
    $extension = $types[$info[2]];
  } elseif ($info !== false && isset($info['mime']) && $info['mime'] === 'image/webp') {
    $extension = 'webp';
  }
  if ($extension === null) fail('Only JPG, PNG or WEBP photos can be uploaded.');

  $count = db_select_one(
    'SELECT COUNT(*) AS n, MAX(sort_order) AS top FROM car_images WHERE listing_id = ?',
    'i', array($listingId)
  );
  if ((int) $count['n'] >= MAX_PHOTOS_PER_CAR) {
    fail('A car can have at most ' . MAX_PHOTOS_PER_CAR . ' photos. Remove one first.');
  }
  $sortOrder = $count['top'] === null ? 0 : (int) $count['top'] + 1;

  $dir = __DIR__ . '/../uploads/';
  if (!is_dir($dir)) @mkdir($dir, 0755, true);
  $name = 'car' . $listingId . '_' . str_replace('.', '', uniqid('', true)) . mt_rand(100, 999) . '.' . $extension;
  if (!move_uploaded_file($file['tmp_name'], $dir . $name)) {
    fail('The server could not save the photo.', 500);
  }
  $path = 'uploads/' . $name;

  try {
    $stmt = db()->prepare('INSERT INTO car_images (listing_id, image_path, sort_order) VALUES (?, ?, ?)');
    $stmt->bind_param('isi', $listingId, $path, $sortOrder);
    $stmt->execute();
    $imageId = (int) $stmt->insert_id;
    $stmt->close();
  } catch (Exception $e) {
    upload_delete_file($path); // don't leave a file with no database row
    throw $e;
  }

  reply(array('image_id' => $imageId, 'image_path' => $path));
}

function delete_photo() {
  $input = json_input();
  $imageId = upload_required_id($input, 'image_id');
  $sellerId = upload_required_id($input, 'seller_id');

  $image = db_select_one(
    'SELECT i.image_id, i.image_path, c.seller_id
       FROM car_images i JOIN car_listings c ON c.listing_id = i.listing_id
      WHERE i.image_id = ?',
    'i', array($imageId)
  );
  if ($image === null) fail('Photo not found.', 404);
  if ((int) $image['seller_id'] !== $sellerId) fail('You can only edit your own listings.', 403);

  upload_delete_file($image['image_path']);

  $stmt = db()->prepare('DELETE FROM car_images WHERE image_id = ?');
  $stmt->bind_param('i', $imageId);
  $stmt->execute();
  $stmt->close();

  reply(array('ok' => true));
}

switch (request_method()) {
  case 'POST':
    // OWNER: Lynn
    // Sends image + listing_id + seller_id -> { image_id, image_path: 'uploads/...' }
    upload_photo();

  case 'DELETE':
    // OWNER: Lynn
    // Sends image_id + seller_id -> { ok: true }
    delete_photo();

  default:
    fail('Method not allowed. Use POST or DELETE.', 405);
}
