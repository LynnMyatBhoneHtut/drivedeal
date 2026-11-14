<?php
/*
 * Shared account helpers for register.php, login.php and users.php. OWNER: Yati.
 * The same rules are checked in the Vue forms (RegisterForm / ProfileView), but the
 * server checks them again because anyone can call the API directly.
 *
 * Written for older PHP too (no ?? or typed params), like helpers.php.
 */

/*
 * password_hash() / password_verify() only exist from PHP 5.5. Mercury runs an older PHP,
 * so these stand-ins are used there. They make the same bcrypt "$2y$10$..." hashes with
 * crypt(), so passwords made on a newer PHP (laptops, the sample data) still work and the
 * other way round. On PHP 5.5+ the real functions exist and these are skipped.
 */
if (!defined('PASSWORD_DEFAULT')) {
  define('PASSWORD_BCRYPT', 1);
  define('PASSWORD_DEFAULT', PASSWORD_BCRYPT);
}

if (!function_exists('password_hash')) {
  function password_hash($password, $algo, $options = array()) {
    $cost = isset($options['cost']) ? (int) $options['cost'] : 10;
    $bytes = function_exists('openssl_random_pseudo_bytes') ? openssl_random_pseudo_bytes(16) : '';
    if (strlen($bytes) < 16) {
      $bytes = '';
      for ($i = 0; $i < 16; $i++) $bytes .= chr(mt_rand(0, 255));
    }
    // bcrypt wants a 22-character salt from ./A-Za-z0-9
    $salt = substr(strtr(base64_encode($bytes), '+', '.'), 0, 22);
    $hash = crypt($password, sprintf('$2y$%02d$', $cost) . $salt);
    return (is_string($hash) && strlen($hash) === 60) ? $hash : false;
  }
}

if (!function_exists('password_verify')) {
  function password_verify($password, $hash) {
    $check = crypt($password, $hash);
    if (!is_string($check) || strlen($check) !== strlen($hash) || strlen($hash) < 13) return false;
    // Compare every character so the time taken doesn't give the hash away
    $diff = 0;
    for ($i = 0; $i < strlen($hash); $i++) $diff |= ord($check[$i]) ^ ord($hash[$i]);
    return $diff === 0;
  }
}

/**
 * Length of a text in characters. Mercury's PHP has no mbstring, so without mb_strlen()
 * count UTF-8 characters with a regular expression instead of bytes ("Zoë" = 3).
 */
function account_text_length($text) {
  if (function_exists('mb_strlen')) return mb_strlen($text, 'UTF-8');
  $count = preg_match_all('/./us', $text, $unused);
  return $count === false ? strlen($text) : $count;
}

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
  } elseif (account_text_length($fullName) < 2 || account_text_length($fullName) > 100) {
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
