<?php
/*
 * login.php - Log in
 *
 * Method Owner
 * POST   Yati
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'POST':
    // OWNER: Yati
    // Sends email, password -> user object (no password). Check with password_verify()
    not_built_yet('login.php POST');

  default:
    fail('Method not allowed. Use POST.', 405);
}
