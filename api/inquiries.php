<?php
/*
 * inquiries.php - Buyer messages to sellers
 *
 * Method Owner
 * GET    Lynn (read-only, done)
 * POST   Lynn
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cars_common.php';

switch (request_method()) {
  case 'GET':
    // Read-only (Lynn).
    //   ?seller_id=2  -> messages buyers sent about this seller's cars (My listings / InquiryList)
    //   ?buyer_id=4   -> messages this buyer has sent
    //   ?listing_id=3 -> messages about one car
    // Each item: { inquiry_id, listing_id, buyer_id, message, created_at,
    //              make, model, buyer_name, buyer_email, buyer_phone }  - newest first
    $filters = array('seller_id' => 'c.seller_id', 'buyer_id' => 'q.buyer_id', 'listing_id' => 'q.listing_id');
    $where = array();
    $types = '';
    $values = array();
    foreach ($filters as $param => $column) {
      $value = int_param($param);
      if ($value !== null) {
        $where[] = "$column = ?";
        $types .= 'i';
        $values[] = $value;
      }
    }
    if (!count($where)) fail('Send seller_id, buyer_id or listing_id.');

    $rows = db_select(
      'SELECT q.inquiry_id, q.listing_id, q.buyer_id, q.message, q.created_at,
              c.make, c.model, c.seller_id,
              b.full_name AS buyer_name, b.email AS buyer_email, b.phone AS buyer_phone
         FROM inquiries q
         JOIN car_listings c ON c.listing_id = q.listing_id
         JOIN users b ON b.user_id = q.buyer_id
        WHERE ' . implode(' AND ', $where) . '
        ORDER BY q.created_at DESC, q.inquiry_id DESC',
      $types, $values
    );
    foreach ($rows as $i => $r) {
      foreach (array('inquiry_id', 'listing_id', 'buyer_id', 'seller_id') as $k) $rows[$i][$k] = (int) $r[$k];
    }
    reply($rows);

  case 'POST':
    // OWNER: Lynn
    // Sends listing_id, buyer_id, message -> { inquiry_id: 7 }
    // message: 10 to 2000 characters. 404 car not found; 400 bad input, unknown buyer,
    // your own car, or a sold car.
    $input = json_input();
    $ids = array();
    foreach (array('listing_id', 'buyer_id') as $name) {
      $value = isset($input[$name]) ? $input[$name] : null;
      if (is_string($value) && preg_match('/^\s*\d+\s*$/', $value)) $value = (int) $value;
      if (is_float($value) && floor($value) == $value) $value = (int) $value;
      if (!is_int($value) || $value <= 0) {
        fail($name === 'buyer_id' ? 'Please log in to contact the seller.' : 'listing_id is required and must be a whole number.');
      }
      $ids[$name] = $value;
    }

    $message = isset($input['message']) && is_string($input['message']) ? trim($input['message']) : '';
    $length = function_exists('mb_strlen') ? mb_strlen($message, 'UTF-8') : strlen($message);
    if ($length < 10) fail('Your message must be at least 10 characters.');
    if ($length > 2000) fail('Your message must be 2000 characters or fewer.');

    $car = db_select_one('SELECT seller_id, status FROM car_listings WHERE listing_id = ?', 'i', array($ids['listing_id']));
    if ($car === null) fail('Car not found.', 404);
    if (db_select_one('SELECT user_id FROM users WHERE user_id = ?', 'i', array($ids['buyer_id'])) === null) {
      fail('Buyer not found - please log in again.');
    }
    if ((int) $car['seller_id'] === $ids['buyer_id']) fail("You can't send an enquiry about your own car.");
    if ($car['status'] === 'sold') fail('This car has already been sold.');

    $stmt = db()->prepare('INSERT INTO inquiries (listing_id, buyer_id, message) VALUES (?, ?, ?)');
    $stmt->bind_param('iis', $ids['listing_id'], $ids['buyer_id'], $message);
    $stmt->execute();
    $inquiryId = (int) $stmt->insert_id;
    $stmt->close();

    reply(array('inquiry_id' => $inquiryId));

  default:
    fail('Method not allowed. Use GET, POST.', 405);
}
