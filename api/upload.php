<?php
/*
 * upload.php - Car photo upload (form-data, not JSON: use $_POST and $_FILES)
 *
 * Method Owner
 * POST   Lynn
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'POST':
    // OWNER: Lynn
    // Sends image + listing_id -> { image_path: 'uploads/...' }. Save the file in ../uploads/
    not_built_yet('upload.php POST');

  default:
    fail('Method not allowed. Use POST.', 405);
}
