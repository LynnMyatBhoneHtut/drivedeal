<?php
/*
 * register.php - Create an account
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
    // Sends full_name, email, password, phone -> user object (no password). Store password_hash()
    not_built_yet('register.php POST');

  default:
    fail('Method not allowed. Use POST.', 405);
}
