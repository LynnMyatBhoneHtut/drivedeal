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
