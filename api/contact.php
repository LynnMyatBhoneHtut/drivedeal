<?php
/*
 * contact.php - About page contact form
 *
 * Method Owner
 * POST   Lai
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'POST':
    // OWNER: Lai
    // Sends name, email, message -> { ok: true }
    not_built_yet('contact.php POST');

  default:
    fail('Method not allowed. Use POST.', 405);
}
