<?php
/*
 * login.php - Log in
 *
 * Method Owner
 * POST   Yati
 *
 * POST { email, password }
 *   200 -> { user_id, full_name, email, phone, role, created_at }   (never the password)
 *   400 -> { error, fields }   a field is empty
 *   401 -> { error }           wrong email or password (same message for both, so nobody
 *                              can use the form to find out which emails are registered)
 *
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/accounts_common.php';

switch (request_method()) {
  case 'POST':
    // OWNER: Yati
    $input = json_input();
    $email = strtolower(input_text($input, 'email'));
    $password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';

    $errors = array();
    if ($email === '') $errors['email'] = 'Email is required.';
    if ($password === '') $errors['password'] = 'Password is required.';
    if (count($errors)) fail_account_fields($errors);

    $row = db_select_one('SELECT user_id, password_hash FROM users WHERE email = ?', 's', array($email));
    if ($row === null || !password_verify($password, $row['password_hash'])) {
      fail('Email or password is incorrect.', 401);
    }

    reply(public_user((int) $row['user_id']));

  default:
    fail('Method not allowed. Use POST.', 405);
}
