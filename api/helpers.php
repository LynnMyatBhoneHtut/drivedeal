<?php
/*
 * Shared helpers for every endpoint. Shared file: don't change it without telling the group.
 *
 *   require_once __DIR__ . '/helpers.php';
 *
 *   request_method()   'GET' | 'POST' | 'PUT' | 'DELETE'
 *   json_input()       the JSON body the Vue app sent, as an array
 *   db()               the mysqli connection (from db.php)
 *   reply($data)       send JSON and stop
 *   fail('msg', 400)   send {"error": "msg"} with a 4xx/5xx code and stop
 *
 * Written for older PHP too (no ?? or typed params), in case Mercury runs PHP 7.
 */

header('Content-Type: application/json');

// Make mysqli throw errors instead of failing quietly
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Any uncaught error (bad SQL etc.) still comes back as JSON, so the Vue app can show it
set_exception_handler(function ($e) {
  http_response_code(500);
  echo json_encode(array('error' => 'Server error: ' . $e->getMessage()));
});

/** Send JSON and stop. */
function reply($data, $status = 200) {
  http_response_code($status);
  echo json_encode($data);
  exit;
}

/** Send {"error": "..."} and stop. Use 400 bad input, 401 not logged in, 403 not yours, 404 not found. */
function fail($message, $status = 400) {
  reply(array('error' => $message), $status);
}

/** Placeholder answer for parts nobody has built yet. */
function not_built_yet($what) {
  fail($what . ' is not built yet', 501);
}

/**
 * The HTTP method. src/services/api.js sends PUT and DELETE as POST with an
 * X-HTTP-Method-Override header (some servers block real PUT/DELETE), so this
 * turns them back into 'PUT' / 'DELETE'.
 */
function request_method() {
  $method = $_SERVER['REQUEST_METHOD'];
  if ($method === 'POST' && isset($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'])) {
    $override = strtoupper($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE']);
    if ($override === 'PUT' || $override === 'DELETE') {
      return $override;
    }
  }
  return $method;
}

/** The JSON body as an array (empty array if there is none). For upload.php use $_POST / $_FILES instead. */
function json_input() {
  $data = json_decode(file_get_contents('php://input'), true);
  return is_array($data) ? $data : array();
}

/** The database connection, opened once per request. */
function db() {
  static $conn = null;
  if ($conn === null) {
    if (!file_exists(__DIR__ . '/db.php')) {
      fail('api/db.php is missing - copy api/db.example.php to api/db.php and add your password', 500);
    }
    require __DIR__ . '/db.php';
    $conn = new mysqli($DB_HOST, $DB_USER, $DB_PASS, $DB_NAME);
    $conn->set_charset('utf8mb4');
  }
  return $conn;
}

/** A whole-number query parameter (?page=2), or $default if missing / not a number. */
function int_param($name, $default = null) {
  if (!isset($_GET[$name]) || $_GET[$name] === '' || !is_numeric($_GET[$name])) return $default;
  return (int) $_GET[$name];
}

/** A text query parameter (?make=Toyota), trimmed, or $default if missing. */
function str_param($name, $default = '') {
  if (!isset($_GET[$name]) || is_array($_GET[$name])) return $default;
  return trim((string) $_GET[$name]);
}

/**
 * Run a SELECT with ? placeholders and return all rows as arrays.
 *   db_select('SELECT * FROM users WHERE user_id = ?', 'i', array($id))
 * $types: one letter per value - i = whole number, d = decimal, s = text.
 * Works with or without mysqlnd (older Mercury PHP has no get_result()).
 */
function db_select($sql, $types = '', $values = array()) {
  $stmt = db()->prepare($sql);
  if ($types !== '') {
    $args = array($types);
    foreach ($values as $i => $v) {
      $args[] = &$values[$i]; // bind_param needs references
    }
    call_user_func_array(array($stmt, 'bind_param'), $args);
  }
  $stmt->execute();

  $rows = array();
  if (method_exists($stmt, 'get_result')) {
    $result = $stmt->get_result();
    while ($row = $result->fetch_assoc()) $rows[] = $row;
  } else {
    // Fallback without mysqlnd: bind every column to a variable
    $meta = $stmt->result_metadata();
    $row = array();
    $refs = array();
    while ($field = $meta->fetch_field()) {
      $row[$field->name] = null;
      $refs[] = &$row[$field->name];
    }
    call_user_func_array(array($stmt, 'bind_result'), $refs);
    while ($stmt->fetch()) {
      $copy = array();
      foreach ($row as $k => $v) $copy[$k] = $v;
      $rows[] = $copy;
    }
  }
  $stmt->close();
  return $rows;
}

/** First row of db_select(), or null. */
function db_select_one($sql, $types = '', $values = array()) {
  $rows = db_select($sql, $types, $values);
  return count($rows) ? $rows[0] : null;
}
