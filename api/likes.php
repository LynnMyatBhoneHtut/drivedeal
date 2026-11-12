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
    not_built_yet('likes.php POST');

  case 'DELETE':
    // OWNER: Yati
    // Unlike. Sends user_id, listing_id -> { likes: 3, liked: false }
    not_built_yet('likes.php DELETE');

  default:
    fail('Method not allowed. Use GET, POST, DELETE.', 405);
}
