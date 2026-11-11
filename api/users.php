<?php
/*
 * users.php - Users
 *
 * Method Owner
 * GET    Lynn (read-only, done)
 * PUT    Yati
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'GET':
    // Read-only (Lynn). ?id=2 -> { user_id, full_name, email, phone, role, created_at }
    // (never the password hash)
    $id = int_param('id');
    if ($id === null) fail('id is required, e.g. users.php?id=2');
    $user = db_select_one(
      'SELECT user_id, full_name, email, phone, role, created_at FROM users WHERE user_id = ?',
      'i', array($id)
    );
    if ($user === null) fail('User not found.', 404);
    $user['user_id'] = (int) $user['user_id'];
    reply($user);

  case 'PUT':
    // OWNER: Yati
    // Update profile -> user object (no password)
    not_built_yet('users.php PUT');

  default:
    fail('Method not allowed. Use GET, PUT.', 405);
}
