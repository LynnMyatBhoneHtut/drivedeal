<?php
/*
 * inquiries.php - Buyer messages to sellers
 *
 * Method Owner
 * GET    already live
 * POST   Lynn
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'GET':
    // OWNER: already live
    // Already live on Mercury - paste that code here first,
    // otherwise deploying this file would replace the live one with this stub.
    not_built_yet('inquiries.php GET');

  case 'POST':
    // OWNER: Lynn
    // Sends listing_id, buyer_id, message -> { inquiry_id: 7 }
    not_built_yet('inquiries.php POST');

  default:
    fail('Method not allowed. Use GET, POST.', 405);
}
