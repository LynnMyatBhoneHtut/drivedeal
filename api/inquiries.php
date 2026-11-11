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
    not_built_yet('inquiries.php POST');

  default:
    fail('Method not allowed. Use GET, POST.', 405);
}
