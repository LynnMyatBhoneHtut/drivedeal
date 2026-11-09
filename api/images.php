<?php
/*
 * images.php - Car images (read-only)
 *
 * Method Owner
 * GET    already live
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
    not_built_yet('images.php GET');

  default:
    fail('Method not allowed. Use GET.', 405);
}
