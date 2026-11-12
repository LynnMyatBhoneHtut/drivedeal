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
    // Sends user_id, full_name, email, phone
    //   + optionally current_password and new_password to change the password.
    //   200 -> user object, 400 -> { error, fields }, 401 -> wrong current password,
    //   404 -> no such user, 409 -> email used by another account
    require_once __DIR__ . '/accounts_common.php';
    $input = json_input();
    $userId = isset($input['user_id']) && is_numeric($input['user_id']) ? (int) $input['user_id'] : 0;
    if ($userId <= 0) fail('Please log in to edit your profile.', 401);

    $stored = db_select_one('SELECT password_hash FROM users WHERE user_id = ?', 'i', array($userId));
    if ($stored === null) fail('User not found.', 404);

    $fullName = input_text($input, 'full_name');
    $email = strtolower(input_text($input, 'email'));
    $phone = input_text($input, 'phone');
    $errors = validate_profile_fields($fullName, $email, $phone);

    $newPassword = isset($input['new_password']) && is_string($input['new_password']) ? $input['new_password'] : '';
    $currentPassword = isset($input['current_password']) && is_string($input['current_password']) ? $input['current_password'] : '';
    if ($newPassword !== '') {
      $problem = password_problem($newPassword);
      if ($problem !== '') $errors['new_password'] = $problem;
      if ($currentPassword === '') $errors['current_password'] = 'Enter your current password to set a new one.';
    }
    if (count($errors)) fail_account_fields($errors);

    if ($newPassword !== '' && !password_verify($currentPassword, $stored['password_hash'])) {
      fail_account_fields(array('current_password' => 'Current password is incorrect.'), 401);
    }
    if (email_taken($email, $userId)) {
      fail_account_fields(array('email' => 'Another account already uses this email.'), 409);
    }

    $phoneOrNull = $phone === '' ? null : $phone;
    $stmt = db()->prepare('UPDATE users SET full_name = ?, email = ?, phone = ? WHERE user_id = ?');
    $stmt->bind_param('sssi', $fullName, $email, $phoneOrNull, $userId);
    $stmt->execute();
    $stmt->close();

    if ($newPassword !== '') {
      $hash = password_hash($newPassword, PASSWORD_DEFAULT);
      $stmt = db()->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
      $stmt->bind_param('si', $hash, $userId);
      $stmt->execute();
      $stmt->close();
    }

    reply(public_user($userId));

  default:
    fail('Method not allowed. Use GET, PUT.', 405);
}
