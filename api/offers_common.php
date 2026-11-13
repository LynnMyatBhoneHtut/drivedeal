<?php
/*
 * offers_common.php - shared pieces for offers.php and test_drives.php
 * Make-an-Offer & Negotiation System with Test Drive Request (Lynn's individual feature).
 *
 * The negotiation rules live here, and the same rules are in src/services/offerRules.js
 * (which has unit tests in tests/offerRules.test.js), so the page can explain a problem
 * before sending, and the server still refuses anything that breaks the rules.
 *
 * Rules
 *   - A buyer has one negotiation per car. The first offer is at least 50% of the asking
 *     price and at most the asking price, in whole ringgit.
 *   - Buyer and seller take turns. On your turn you can counter-offer or accept the other
 *     side's latest price. A buyer can only go up, a seller can only come down, and a
 *     counter must stay between the two latest prices (equal means: accept instead).
 *   - The seller can reject and the buyer can withdraw at any time while it is open.
 *   - Test drive: unlocked when the buyer's latest offer is within 10% of the seller's
 *     latest price (their counter-offer, or the asking price), or once the deal is agreed.
 *   - Accepting agrees the deal: the car becomes 'reserved' and every other open
 *     negotiation on that car is closed.
 *
 * Old-PHP friendly like helpers.php: no ??, no typed params, array() syntax.
 */

define('OFFER_MIN_RATIO', 0.5);       // first offer must be at least 50% of the asking price
define('TEST_DRIVE_RANGE', 10);       // percent
define('TEST_DRIVE_MAX_DAYS', 30);    // book up to 30 days ahead
define('OFFER_MESSAGE_MAX', 500);
define('TEST_DRIVE_NOTE_MAX', 300);

/** Hourly slots a test drive can start at. */
function test_drive_slots() {
  return array('09:00', '10:00', '11:00', '12:00', '13:00', '14:00', '15:00', '16:00', '17:00');
}

/** A positive whole number from the JSON body, or stop with $message. */
function offer_required_id($input, $name, $message) {
  $value = isset($input[$name]) ? $input[$name] : null;
  if (is_string($value) && preg_match('/^\s*\d+\s*$/', $value)) $value = (int) $value;
  if (is_float($value) && floor($value) == $value) $value = (int) $value;
  if (!is_int($value) || $value <= 0) fail($message);
  return $value;
}

/** A whole-ringgit amount from the JSON body, or null if it is missing / not a whole number. */
function offer_amount($input) {
  if (!isset($input['amount'])) return null;
  $value = $input['amount'];
  if (is_string($value)) $value = trim($value);
  if (is_string($value) && preg_match('/^\d+(\.0+)?$/', $value)) $value = (float) $value;
  if (is_int($value)) $value = (float) $value;
  if (!is_float($value) || floor($value) != $value || $value <= 0 || $value > 10000000) return null;
  return $value;
}

/** Optional text from the JSON body, trimmed; stops if it is longer than $max characters. */
function offer_text($input, $name, $max, $label) {
  $text = isset($input[$name]) && is_string($input[$name]) ? trim($input[$name]) : '';
  $length = function_exists('mb_strlen') ? mb_strlen($text, 'UTF-8') : strlen($text);
  if ($length > $max) fail($label . ' must be ' . $max . ' characters or fewer.');
  return $text === '' ? null : $text;
}

/** "RM 46,000" */
function offer_rm($amount) {
  return 'RM ' . number_format((float) $amount, 0);
}

/**
 * Where the negotiation stands, from its events (oldest first) and the asking price.
 *   buyer_amount   the buyer's latest price
 *   seller_amount  the seller's latest counter-offer, or the asking price
 *   gap_percent    how far apart they are, as a % of the seller's price
 *   within_range   gap_percent <= 10
 *   turn           'buyer' | 'seller' | null (whose move it is while open)
 */
function offer_summary($events, $askingPrice, $status) {
  $buyer = null;
  $seller = null;
  $lastMover = null;
  foreach ($events as $e) {
    if ($e['action'] === 'offer' || $e['action'] === 'counter') {
      if ($e['actor'] === 'buyer') $buyer = (float) $e['amount'];
      if ($e['actor'] === 'seller') $seller = (float) $e['amount'];
      $lastMover = $e['actor'];
    }
  }
  if ($seller === null) $seller = (float) $askingPrice;

  $gap = null;
  if ($buyer !== null && $seller > 0) {
    $gap = round(max(0, $seller - $buyer) / $seller * 100, 1);
  }

  $turn = null;
  if ($status === 'open' && $lastMover !== null) {
    $turn = $lastMover === 'buyer' ? 'seller' : 'buyer';
  }

  return array(
    'buyer_amount' => $buyer,
    'seller_amount' => $seller,
    'gap_percent' => $gap,
    'within_range' => $gap !== null && $gap <= TEST_DRIVE_RANGE,
    'turn' => $turn
  );
}

/** Build an "IN (?, ?, ?)" list for an array of ids. */
function offer_in_list($ids) {
  return '(' . implode(', ', array_fill(0, count($ids), '?')) . ')';
}

/**
 * Load negotiations with their car, people, events, latest test drive and summary.
 * $where is a SQL condition on o (offers) / c (car_listings), e.g. 'o.buyer_id = ?'.
 */
function load_offers($where, $types, $values) {
  $rows = db_select(
    'SELECT o.offer_id, o.listing_id, o.buyer_id, o.status, o.agreed_price, o.created_at, o.updated_at,
            c.seller_id, c.make, c.model, c.year, c.price AS asking_price, c.status AS car_status,
            (SELECT i.image_path FROM car_images i WHERE i.listing_id = c.listing_id
              ORDER BY i.sort_order, i.image_id LIMIT 1) AS image_path,
            b.full_name AS buyer_name, b.email AS buyer_email, b.phone AS buyer_phone,
            s.full_name AS seller_name, s.email AS seller_email, s.phone AS seller_phone
       FROM offers o
       JOIN car_listings c ON c.listing_id = o.listing_id
       JOIN users b ON b.user_id = o.buyer_id
       JOIN users s ON s.user_id = c.seller_id
      WHERE ' . $where . '
      ORDER BY (o.status = \'open\') DESC, o.updated_at DESC, o.offer_id DESC',
    $types, $values
  );
  if (!count($rows)) return array();

  $ids = array();
  foreach ($rows as $r) $ids[] = (int) $r['offer_id'];
  $idTypes = str_repeat('i', count($ids));

  $eventsByOffer = array();
  $events = db_select(
    'SELECT event_id, offer_id, actor, action, amount, message, created_at
       FROM offer_events WHERE offer_id IN ' . offer_in_list($ids) . '
      ORDER BY created_at, event_id',
    $idTypes, $ids
  );
  foreach ($events as $e) {
    $e['event_id'] = (int) $e['event_id'];
    $e['offer_id'] = (int) $e['offer_id'];
    $e['amount'] = $e['amount'] === null ? null : (float) $e['amount'];
    $eventsByOffer[$e['offer_id']][] = $e;
  }

  $driveByOffer = array();
  $drives = db_select(
    'SELECT test_drive_id, offer_id, preferred_date, preferred_time, note, status, created_at, updated_at
       FROM test_drives WHERE offer_id IN ' . offer_in_list($ids) . '
      ORDER BY test_drive_id',
    $idTypes, $ids
  );
  foreach ($drives as $d) {
    $d['test_drive_id'] = (int) $d['test_drive_id'];
    $d['offer_id'] = (int) $d['offer_id'];
    $driveByOffer[$d['offer_id']] = $d; // the latest one wins
  }

  $result = array();
  foreach ($rows as $r) {
    $id = (int) $r['offer_id'];
    foreach (array('offer_id', 'listing_id', 'buyer_id', 'seller_id', 'year') as $k) $r[$k] = (int) $r[$k];
    $r['asking_price'] = (float) $r['asking_price'];
    $r['agreed_price'] = $r['agreed_price'] === null ? null : (float) $r['agreed_price'];
    $r['events'] = isset($eventsByOffer[$id]) ? $eventsByOffer[$id] : array();
    $r['test_drive'] = isset($driveByOffer[$id]) ? $driveByOffer[$id] : null;
    $r['summary'] = offer_summary($r['events'], $r['asking_price'], $r['status']);
    $r['test_drive_unlocked'] = $r['status'] === 'accepted'
      || ($r['status'] === 'open' && $r['summary']['within_range']);

    // The seller's phone and email are shared once a test drive is confirmed or the deal is agreed
    $shareSeller = $r['status'] === 'accepted'
      || ($r['test_drive'] !== null && $r['test_drive']['status'] === 'confirmed');
    if (!$shareSeller) {
      $r['seller_email'] = null;
      $r['seller_phone'] = null;
    }
    $result[] = $r;
  }
  return $result;
}

/** One negotiation, or null. */
function load_offer($offerId) {
  $rows = load_offers('o.offer_id = ?', 'i', array($offerId));
  return count($rows) ? $rows[0] : null;
}

/** One negotiation the user takes part in, with their role; stops with 404 / 403 otherwise. */
function offer_for_user($offerId, $userId) {
  $offer = load_offer($offerId);
  if ($offer === null) fail('Offer not found.', 404);
  if ($userId === $offer['buyer_id']) {
    $offer['role'] = 'buyer';
  } elseif ($userId === $offer['seller_id']) {
    $offer['role'] = 'seller';
  } else {
    fail('You can only act on your own offers.', 403);
  }
  return $offer;
}

/** Add a history row and bump the negotiation's updated_at. */
function add_offer_event($offerId, $actor, $action, $amount, $message) {
  $stmt = db()->prepare(
    'INSERT INTO offer_events (offer_id, actor, action, amount, message) VALUES (?, ?, ?, ?, ?)'
  );
  $stmt->bind_param('issds', $offerId, $actor, $action, $amount, $message);
  $stmt->execute();
  $stmt->close();

  $stmt = db()->prepare('UPDATE offers SET updated_at = NOW() WHERE offer_id = ?');
  $stmt->bind_param('i', $offerId);
  $stmt->execute();
  $stmt->close();
}

/** Cancel any requested / confirmed test drive of a negotiation that has ended. */
function cancel_active_test_drives($offerId) {
  $stmt = db()->prepare(
    "UPDATE test_drives SET status = 'cancelled'
      WHERE offer_id = ? AND status IN ('requested', 'confirmed')"
  );
  $stmt->bind_param('i', $offerId);
  $stmt->execute();
  $stmt->close();
}

/** A car's deal is agreed: close every other open negotiation on it. */
function close_other_offers($listingId, $keepOfferId) {
  $others = db_select(
    "SELECT offer_id FROM offers WHERE listing_id = ? AND offer_id <> ? AND status = 'open'",
    'ii', array($listingId, $keepOfferId)
  );
  foreach ($others as $o) {
    $id = (int) $o['offer_id'];
    $stmt = db()->prepare("UPDATE offers SET status = 'closed' WHERE offer_id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $stmt->close();
    add_offer_event($id, 'system', 'close', null, 'This car has been reserved for another buyer.');
    cancel_active_test_drives($id);
  }
}
