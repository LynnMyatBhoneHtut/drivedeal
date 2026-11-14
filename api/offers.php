<?php
/*
 * offers.php - Make-an-Offer & Negotiation System (Lynn's individual feature)
 * Rules: see offers_common.php. Tables: database/drivedeal.sql (Part 3).
 *
 * GET
 *   ?buyer_id=3                    -> { offers: [...] }  negotiations this buyer started (My offers)
 *   ?seller_id=2                   -> { offers: [...] }  negotiations on this seller's cars (My listings)
 *   ?listing_id=3&user_id=3        -> { offers: [...] }  on one car: all of them for its seller,
 *                                                         otherwise only this user's own
 *   ?offer_id=1&user_id=3          -> { offer: {...} }   one negotiation (buyer or seller only)
 *   Each negotiation: offer_id, listing_id, buyer_id, seller_id, status, agreed_price,
 *   make, model, year, asking_price, car_status, image_path, buyer_name/email/phone,
 *   seller_name (+ seller_email/phone once a test drive is confirmed or the deal is agreed),
 *   events: [{ event_id, actor, action, amount, message, created_at }]  oldest first,
 *   test_drive: { test_drive_id, preferred_date, preferred_time, note, status } | null,
 *   summary: { buyer_amount, seller_amount, gap_percent, within_range, turn },
 *   test_drive_unlocked: true/false
 *
 * POST  { listing_id, buyer_id, amount, message? }                  -> { offer: {...} }
 *   Start a negotiation with a first offer.
 *
 * PUT   { offer_id, user_id, action, amount?, message? }            -> { offer: {...} }
 *   action: 'counter' (needs amount) | 'accept' | 'reject' (seller) | 'withdraw' (buyer)
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/offers_common.php';

switch (request_method()) {
  case 'GET':
    $offerId = int_param('offer_id');
    $userId = int_param('user_id');
    if ($offerId !== null) {
      if ($userId === null) fail('Send user_id with offer_id.');
      $offer = offer_for_user($offerId, $userId);
      unset($offer['role']);
      reply(array('offer' => $offer));
    }

    $buyerId = int_param('buyer_id');
    if ($buyerId !== null) reply(array('offers' => load_offers('o.buyer_id = ?', 'i', array($buyerId))));

    $sellerId = int_param('seller_id');
    if ($sellerId !== null) reply(array('offers' => load_offers('c.seller_id = ?', 'i', array($sellerId))));

    $listingId = int_param('listing_id');
    if ($listingId !== null) {
      if ($userId === null) reply(array('offers' => array()));
      $car = db_select_one('SELECT seller_id FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
      if ($car === null) fail('Car not found.', 404);
      if ((int) $car['seller_id'] === $userId) {
        reply(array('offers' => load_offers('o.listing_id = ?', 'i', array($listingId))));
      }
      reply(array('offers' => load_offers('o.listing_id = ? AND o.buyer_id = ?', 'ii', array($listingId, $userId))));
    }

    fail('Send buyer_id, seller_id, listing_id or offer_id.');

  case 'POST':
    $input = json_input();
    $listingId = offer_required_id($input, 'listing_id', 'listing_id is required and must be a whole number.');
    $buyerId = offer_required_id($input, 'buyer_id', 'Please log in to make an offer.');
    $amount = offer_amount($input);
    $message = offer_text($input, 'message', OFFER_MESSAGE_MAX, 'Your message');

    $car = db_select_one('SELECT seller_id, price, status FROM car_listings WHERE listing_id = ?', 'i', array($listingId));
    if ($car === null) fail('Car not found.', 404);
    if (db_select_one('SELECT user_id FROM users WHERE user_id = ?', 'i', array($buyerId)) === null) {
      fail('Buyer not found - please log in again.');
    }
    if ((int) $car['seller_id'] === $buyerId) fail("You can't make an offer on your own car.");
    if ($car['status'] !== 'available') fail('This car is no longer available for offers.');

    $asking = (float) $car['price'];
    $minimum = ceil($asking * OFFER_MIN_RATIO);
    if ($amount === null) fail('Enter your offer as a whole amount in RM.');
    if ($amount < $minimum) fail('Your offer must be at least ' . offer_rm($minimum) . ' (half the asking price).');
    if ($amount > $asking) fail('Your offer cannot be more than the asking price of ' . offer_rm($asking) . '.');

    $existing = db_select_one(
      "SELECT offer_id FROM offers WHERE listing_id = ? AND buyer_id = ? AND status IN ('open', 'accepted')",
      'ii', array($listingId, $buyerId)
    );
    if ($existing !== null) fail('You already have an offer on this car - reply in that negotiation instead.');

    $db = db();
    $db->autocommit(false);
    $stmt = $db->prepare('INSERT INTO offers (listing_id, buyer_id) VALUES (?, ?)');
    $stmt->bind_param('ii', $listingId, $buyerId);
    $stmt->execute();
    $offerId = (int) $stmt->insert_id;
    $stmt->close();
    add_offer_event($offerId, 'buyer', 'offer', $amount, $message);
    $db->commit();
    $db->autocommit(true);

    reply(array('offer' => load_offer($offerId)), 201);

  case 'PUT':
    $input = json_input();
    $offerId = offer_required_id($input, 'offer_id', 'offer_id is required and must be a whole number.');
    $userId = offer_required_id($input, 'user_id', 'Please log in to reply to this offer.');
    $action = isset($input['action']) && is_string($input['action']) ? $input['action'] : '';
    $message = offer_text($input, 'message', OFFER_MESSAGE_MAX, 'Your message');

    $offer = offer_for_user($offerId, $userId);
    $role = $offer['role'];
    $summary = $offer['summary'];

    if ($offer['status'] !== 'open') fail('This negotiation has ended.');

    // ---- End it (any time while open) ----
    if ($action === 'reject' || $action === 'withdraw') {
      if ($action === 'reject' && $role !== 'seller') fail('Only the seller can reject an offer.', 403);
      if ($action === 'withdraw' && $role !== 'buyer') fail('Only the buyer can withdraw an offer.', 403);
      $status = $action === 'reject' ? 'rejected' : 'withdrawn';

      $db = db();
      $db->autocommit(false);
      $stmt = $db->prepare('UPDATE offers SET status = ? WHERE offer_id = ?');
      $stmt->bind_param('si', $status, $offerId);
      $stmt->execute();
      $stmt->close();
      add_offer_event($offerId, $role, $action, null, $message);
      cancel_active_test_drives($offerId);
      $db->commit();
      $db->autocommit(true);

      reply(array('offer' => load_offer($offerId)));
    }

    if ($action !== 'counter' && $action !== 'accept') {
      fail("action must be 'counter', 'accept', 'reject' or 'withdraw'.");
    }

    // ---- Price moves: only on your turn, only while the car is available ----
    if ($offer['car_status'] !== 'available') {
      fail('This car is no longer available, so the price can no longer change.');
    }
    if ($summary['turn'] !== $role) {
      fail($role === 'buyer'
        ? 'Please wait for the seller to reply to your offer.'
        : 'Please wait for the buyer to reply to your counter-offer.');
    }

    $buyerPrice = $summary['buyer_amount'];
    $sellerPrice = $summary['seller_amount'];

    if ($action === 'counter') {
      $amount = offer_amount($input);
      if ($amount === null) fail('Enter your counter-offer as a whole amount in RM.');
      if ($role === 'buyer') {
        if ($amount <= $buyerPrice) fail('Your new offer must be higher than your last offer of ' . offer_rm($buyerPrice) . '.');
        if ($amount >= $sellerPrice) fail('The seller is asking ' . offer_rm($sellerPrice) . ' - accept their price instead of offering the same or more.');
      } else {
        if ($amount >= $sellerPrice) fail('Your counter-offer must be lower than your last price of ' . offer_rm($sellerPrice) . '.');
        if ($amount <= $buyerPrice) fail('The buyer offered ' . offer_rm($buyerPrice) . ' - accept their offer instead of asking the same or less.');
      }
      add_offer_event($offerId, $role, 'counter', $amount, $message);
      reply(array('offer' => load_offer($offerId)));
    }

    // ---- Accept: take the other side's latest price; the deal is agreed ----
    $agreed = $role === 'buyer' ? $sellerPrice : $buyerPrice;

    $db = db();
    $db->autocommit(false);
    $stmt = $db->prepare("UPDATE offers SET status = 'accepted', agreed_price = ? WHERE offer_id = ? AND status = 'open'");
    $stmt->bind_param('di', $agreed, $offerId);
    $stmt->execute();
    $stmt->close();
    add_offer_event($offerId, $role, 'accept', $agreed, $message);

    $listingId = $offer['listing_id'];
    $stmt = $db->prepare("UPDATE car_listings SET status = 'reserved' WHERE listing_id = ? AND status = 'available'");
    $stmt->bind_param('i', $listingId);
    $stmt->execute();
    $reserved = $stmt->affected_rows;
    $stmt->close();
    if ($reserved !== 1) {
      $db->rollback();
      $db->autocommit(true);
      fail('This car is no longer available.');
    }

    close_other_offers($listingId, $offerId);
    $db->commit();
    $db->autocommit(true);

    reply(array('offer' => load_offer($offerId)));

  default:
    fail('Method not allowed. Use GET, POST, PUT.', 405);
}
