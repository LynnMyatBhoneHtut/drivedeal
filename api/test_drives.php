<?php
/*
 * test_drives.php - Test Drive Request, part of the negotiation (Lynn's individual feature)
 * Rules: see offers_common.php. Table: database/drivedeal.sql (Part 3).
 *
 * POST  { offer_id, buyer_id, preferred_date: 'YYYY-MM-DD', preferred_time: 'HH:MM', note? }
 *       -> { offer: {...} }   the negotiation, with its new test_drive
 *   Only the buyer, only once unlocked (within 10% of the seller's price, or deal agreed),
 *   one active request at a time, from tomorrow up to 30 days ahead, on an hourly slot 09:00-17:00.
 *
 * PUT   { test_drive_id, user_id, action }                         -> { offer: {...} }
 *   action: 'confirm' | 'decline' (seller, while requested) | 'cancel' (buyer, requested or confirmed)
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/offers_common.php';

switch (request_method()) {
  case 'POST':
    $input = json_input();
    $offerId = offer_required_id($input, 'offer_id', 'offer_id is required and must be a whole number.');
    $buyerId = offer_required_id($input, 'buyer_id', 'Please log in to request a test drive.');
    $offer = offer_for_user($offerId, $buyerId);
    if ($offer['role'] !== 'buyer') fail('Only the buyer can request a test drive.', 403);

    if ($offer['status'] !== 'open' && $offer['status'] !== 'accepted') fail('This negotiation has ended.');
    if ($offer['car_status'] === 'sold') fail('This car has been sold.');
    if (!$offer['test_drive_unlocked']) {
      fail('You can request a test drive once your offer is within ' . TEST_DRIVE_RANGE . '% of the seller\'s price.');
    }
    $active = $offer['test_drive'];
    if ($active !== null && ($active['status'] === 'requested' || $active['status'] === 'confirmed')) {
      fail('You already have a test drive ' . $active['status'] . ' for this car.');
    }

    // Date: a real date, from tomorrow up to 30 days ahead
    $date = isset($input['preferred_date']) && is_string($input['preferred_date']) ? trim($input['preferred_date']) : '';
    if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date, $m) || !checkdate((int) $m[2], (int) $m[3], (int) $m[1])) {
      fail('Choose a date for the test drive.');
    }
    $first = date('Y-m-d', strtotime('+1 day'));
    $last = date('Y-m-d', strtotime('+' . TEST_DRIVE_MAX_DAYS . ' days'));
    if ($date < $first || $date > $last) {
      fail('Choose a date from tomorrow up to ' . TEST_DRIVE_MAX_DAYS . ' days ahead.');
    }

    $time = isset($input['preferred_time']) && is_string($input['preferred_time']) ? trim($input['preferred_time']) : '';
    if (!in_array($time, test_drive_slots(), true)) fail('Choose a time between 09:00 and 17:00.');

    $note = offer_text($input, 'note', TEST_DRIVE_NOTE_MAX, 'Your note');

    $stmt = db()->prepare('INSERT INTO test_drives (offer_id, preferred_date, preferred_time, note) VALUES (?, ?, ?, ?)');
    $stmt->bind_param('isss', $offerId, $date, $time, $note);
    $stmt->execute();
    $stmt->close();

    reply(array('offer' => load_offer($offerId)), 201);

  case 'PUT':
    $input = json_input();
    $driveId = offer_required_id($input, 'test_drive_id', 'test_drive_id is required and must be a whole number.');
    $userId = offer_required_id($input, 'user_id', 'Please log in to manage test drives.');
    $action = isset($input['action']) && is_string($input['action']) ? $input['action'] : '';

    $drive = db_select_one('SELECT offer_id, status FROM test_drives WHERE test_drive_id = ?', 'i', array($driveId));
    if ($drive === null) fail('Test drive not found.', 404);
    $offer = offer_for_user((int) $drive['offer_id'], $userId);

    if ($action === 'confirm' || $action === 'decline') {
      if ($offer['role'] !== 'seller') fail('Only the seller can ' . $action . ' a test drive.', 403);
      if ($drive['status'] !== 'requested') fail('This test drive has already been answered.');
      $newStatus = $action === 'confirm' ? 'confirmed' : 'declined';
    } elseif ($action === 'cancel') {
      if ($offer['role'] !== 'buyer') fail('Only the buyer can cancel a test drive.', 403);
      if ($drive['status'] !== 'requested' && $drive['status'] !== 'confirmed') fail('This test drive is not active.');
      $newStatus = 'cancelled';
    } else {
      fail("action must be 'confirm', 'decline' or 'cancel'.");
    }

    $stmt = db()->prepare('UPDATE test_drives SET status = ? WHERE test_drive_id = ?');
    $stmt->bind_param('si', $newStatus, $driveId);
    $stmt->execute();
    $stmt->close();

    reply(array('offer' => load_offer((int) $drive['offer_id'])));

  default:
    fail('Method not allowed. Use POST, PUT.', 405);
}
