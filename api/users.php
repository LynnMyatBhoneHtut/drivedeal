<?php
/*
 * users.php - Users
 *
 * Method Owner
 * GET    already live
 * PUT    Yati
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
    not_built_yet('users.php GET');

  case 'PUT':
    // OWNER: Yati
    // Update profile -> user object (no password)
    not_built_yet('users.php PUT');

  default:
    fail('Method not allowed. Use GET, PUT.', 405);
}
