<?php
/*
 * images.php - Car images (read-only)
 *
 * Method Owner
 * GET    Lynn (read-only, done)
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/cars_common.php';

switch (request_method()) {
  case 'GET':
    // Read-only (Lynn). ?listing_id=3 -> [ { image_id, listing_id, image_path, sort_order }, ... ]
    $listingId = int_param('listing_id');
    if ($listingId === null) fail('listing_id is required, e.g. images.php?listing_id=3');
    reply(car_images($listingId));

  default:
    fail('Method not allowed. Use GET.', 405);
}
