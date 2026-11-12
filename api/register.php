<?php
/*
 * register.php - Create an account
 *
 * Method Owner
 * POST   Yati
 *
 * POST { full_name, email, password, phone? }
 *   201 -> { user_id, full_name, email, phone, role, created_at }   (never the password)
 *   400 -> { error, fields: { full_name?, email?, password?, phone? } }
 *   409 -> { error, fields: { email } }   email already registered
 *
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/accounts_common.php';

switch (request_method()) {
  case 'POST':
    // OWNER: Yati
    $input = json_input();
    $fullName = input_text($input, 'full_name');
    $email = strtolower(input_text($input, 'email'));
    $phone = input_text($input, 'phone');
    // Passwords are not trimmed: spaces are allowed characters
    $password = isset($input['password']) && is_string($input['password']) ? $input['password'] : '';

    $errors = validate_profile_fields($fullName, $email, $phone);
    $problem = password_problem($password);
    if ($problem !== '') $errors['password'] = $problem;
    if (count($errors)) fail_account_fields($errors);

    if (email_taken($email)) {
      fail_account_fields(array('email' => 'An account with this email already exists. Try logging in.'), 409);
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $phoneOrNull = $phone === '' ? null : $phone;
    $stmt = db()->prepare(
      'INSERT INTO users (full_name, email, password_hash, phone, role) VALUES (?, ?, ?, ?, \'user\')'
    );
    $stmt->bind_param('ssss', $fullName, $email, $hash, $phoneOrNull);
    $stmt->execute();
    $newId = $stmt->insert_id;
    $stmt->close();

    reply(public_user($newId), 201);

  default:
    fail('Method not allowed. Use POST.', 405);
}
