<?php
/*
 * likes.php - Likes / votes
 *
 * Method Owner
 * GET    Lynn (read-only, done)
 * POST   Yati
 * DELETE Yati
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cars_common.php';

switch (request_method()) {
  case 'GET':
    // Read-only (Lynn).
    //   ?listing_id=3&user_id=2 -> { "likes": 4, "liked": true }   (user_id optional)
    //   ?user_id=2              -> { "cars": [...] }  every car this user liked, newest like first (Liked cars page)
    $userId = int_param('user_id', 0);
    $listingId = int_param('listing_id');

    if ($listingId !== null) {
      $count = db_select_one('SELECT COUNT(*) AS n FROM listing_likes WHERE listing_id = ?', 'i', array($listingId));
      reply(array('likes' => (int) $count['n'], 'liked' => user_liked($userId, $listingId)));
    }

    if (!$userId) fail('Send listing_id, user_id, or both.');
    $rows = db_select(
      CAR_SELECT . ' JOIN listing_likes mine ON mine.listing_id = c.listing_id AND mine.user_id = ?
                     ORDER BY mine.liked_at DESC, c.listing_id DESC',
      'i', array($userId)
    );
    $cars = array();
    foreach ($rows as $row) {
      $car = car_row($row);
      $car['liked'] = true;
      $cars[] = $car;
    }
    reply(array('cars' => $cars));

  case 'POST':
    // OWNER: Yati
    // Like. Sends user_id, listing_id -> { likes: 4, liked: true }
    // Liking twice is harmless: the (user_id, listing_id) key keeps one row, INSERT IGNORE skips the copy.
    $ids = like_ids();
    $stmt = db()->prepare('INSERT IGNORE INTO listing_likes (user_id, listing_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $ids[0], $ids[1]);
    $stmt->execute();
    $stmt->close();
    reply(like_state($ids[0], $ids[1]));

  case 'DELETE':
    // OWNER: Yati
    // Unlike. Sends user_id, listing_id -> { likes: 3, liked: false }
    $ids = like_ids();
    $stmt = db()->prepare('DELETE FROM listing_likes WHERE user_id = ? AND listing_id = ?');
    $stmt->bind_param('ii', $ids[0], $ids[1]);
    $stmt->execute();
    $stmt->close();
    reply(like_state($ids[0], $ids[1]));

  default:
    fail('Method not allowed. Use GET, POST, DELETE.', 405);
}

// ---- Helpers for POST / DELETE. OWNER: Yati ----

/** user_id and listing_id from the JSON body, after checking both exist. */
function like_ids() {
  $input = json_input();
  $userId = isset($input['user_id']) && is_numeric($input['user_id']) ? (int) $input['user_id'] : 0;
  $listingId = isset($input['listing_id']) && is_numeric($input['listing_id']) ? (int) $input['listing_id'] : 0;
  if ($userId <= 0) fail('Please log in to like cars.', 401);
  if ($listingId <= 0) fail('listing_id is required and must be a whole number.');

  if (db_select_one('SELECT 1 AS yes FROM users WHERE user_id = ?', 'i', array($userId)) === null) {
    fail('Your account was not found. Please log in again.', 401);
  }
  if (db_select_one('SELECT 1 AS yes FROM car_listings WHERE listing_id = ?', 'i', array($listingId)) === null) {
    fail('Car not found.', 404);
  }
  return array($userId, $listingId);
}

/** { likes, liked } after the change, so the button shows the real count. */
function like_state($userId, $listingId) {
  $count = db_select_one('SELECT COUNT(*) AS n FROM listing_likes WHERE listing_id = ?', 'i', array($listingId));
  return array('likes' => (int) $count['n'], 'liked' => user_liked($userId, $listingId));
}
