<?php
/*
 * Shared car query pieces, used by listings.php and likes.php so a car
 * always has the same fields everywhere. Shared file: tell the group before changing.
 *
 * Every car object the API returns looks like:
 * {
 *   "listing_id": 3, "seller_id": 2, "make": "Perodua", "model": "Myvi", "year": 2023,
 *   "price": 48000, "mileage_km": 18000, "transmission": "Automatic", "fuel_type": "Petrol",
 *   "body_type": "Hatchback", "colour": "Red", "location": "Shah Alam, Selangor",
 *   "description": "...", "status": "available", "created_at": "2026-11-10 14:00:00",
 *   "image_path": "uploads/sample/listing-3-1.jpg",   // cover photo, or null
 *   "likes": 4, "seller_name": "Ahmad Faizal"
 * }
 */

// Columns for a car, plus its cover photo, like count and seller name
define('CAR_SELECT', "
  SELECT c.listing_id, c.seller_id, c.make, c.model, c.year, c.price, c.mileage_km,
         c.transmission, c.fuel_type, c.body_type, c.colour, c.location, c.description,
         c.status, c.created_at, c.updated_at,
         u.full_name AS seller_name,
         (SELECT i.image_path FROM car_images i
           WHERE i.listing_id = c.listing_id
           ORDER BY i.sort_order, i.image_id LIMIT 1) AS image_path,
         (SELECT COUNT(*) FROM listing_likes l WHERE l.listing_id = c.listing_id) AS likes
    FROM car_listings c
    JOIN users u ON u.user_id = c.seller_id
");

/** MySQL returns every value as text - turn the number columns back into numbers. */
function car_row($row) {
  foreach (array('listing_id', 'seller_id', 'year', 'mileage_km', 'likes') as $k) {
    if (isset($row[$k])) $row[$k] = (int) $row[$k];
  }
  if (isset($row['price'])) $row['price'] = (float) $row['price'];
  return $row;
}

/** All photos of one car, cover first. */
function car_images($listingId) {
  $rows = db_select(
    'SELECT image_id, listing_id, image_path, sort_order
       FROM car_images WHERE listing_id = ? ORDER BY sort_order, image_id',
    'i', array($listingId)
  );
  foreach ($rows as $i => $r) {
    $rows[$i]['image_id'] = (int) $r['image_id'];
    $rows[$i]['listing_id'] = (int) $r['listing_id'];
    $rows[$i]['sort_order'] = (int) $r['sort_order'];
  }
  return $rows;
}

/** Has this user liked this car? (false for guests) */
function user_liked($userId, $listingId) {
  if (!$userId) return false;
  $row = db_select_one(
    'SELECT 1 AS yes FROM listing_likes WHERE user_id = ? AND listing_id = ?',
    'ii', array($userId, $listingId)
  );
  return $row !== null;
}
