<?php
/*
 * Shared account helpers for register.php, login.php and users.php. OWNER: Yati.
 * The same rules are checked in the Vue forms (RegisterForm / ProfileView), but the
 * server checks them again because anyone can call the API directly.
 *
 * Written for older PHP too (no ?? or typed params), like helpers.php.
 */

/** Columns that are safe to send back - never password_hash. */
define('USER_PUBLIC_COLUMNS', 'user_id, full_name, email, phone, role, created_at');

/** One user without the password hash, or null. */
function public_user($userId) {
  $user = db_select_one(
    'SELECT ' . USER_PUBLIC_COLUMNS . ' FROM users WHERE user_id = ?',
    'i', array($userId)
  );
  if ($user !== null) $user['user_id'] = (int) $user['user_id'];
  return $user;
}

/** A trimmed string from the JSON body ('' if missing or not text). */
function input_text($input, $name) {
  if (!isset($input[$name]) || !is_scalar($input[$name])) return '';
  return trim((string) $input[$name]);
}

/** Send {"error": "...", "fields": {...}} so the form can mark each bad field. */
function fail_account_fields($errors, $status = 400) {
  reply(array('error' => implode(' ', array_values($errors)), 'fields' => $errors), $status);
}

/** Rules for the name / email / phone fields. Returns field => message for each problem. */
function validate_profile_fields($fullName, $email, $phone) {
  $errors = array();

  if ($fullName === '') {
    $errors['full_name'] = 'Full name is required.';
  } elseif (mb_strlen($fullName) < 2 || mb_strlen($fullName) > 100) {
    $errors['full_name'] = 'Full name must be 2 to 100 characters.';
  } elseif (!preg_match("/^[\\p{L} .'-]+$/u", $fullName)) {
    $errors['full_name'] = 'Full name can only contain letters, spaces, apostrophes, dots and hyphens.';
  }

  if ($email === '') {
    $errors['email'] = 'Email is required.';
  } elseif (strlen($email) > 150 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors['email'] = 'Enter a valid email address.';
  }

  // Optional. Malaysian style numbers like 012-345 6789 or +60123456789
  if ($phone !== '' && !preg_match('/^\+?[0-9][0-9 -]{6,18}$/', $phone)) {
    $errors['phone'] = 'Enter a valid phone number (digits, spaces, + and - only).';
  }

  return $errors;
}

/** Password rule: 8-72 characters with at least one letter and one number. '' if fine. */
function password_problem($password) {
  if ($password === '') return 'Password is required.';
  if (strlen($password) < 8 || strlen($password) > 72) {
    return 'Password must be 8 to 72 characters.';
  }
  if (!preg_match('/[A-Za-z]/', $password) || !preg_match('/[0-9]/', $password)) {
    return 'Password must include at least one letter and one number.';
  }
  return '';
}

/** true if another account already uses this email. */
function email_taken($email, $exceptUserId = 0) {
  $row = db_select_one(
    'SELECT user_id FROM users WHERE email = ? AND user_id <> ?',
    'si', array($email, $exceptUserId)
  );
  return $row !== null;
}
