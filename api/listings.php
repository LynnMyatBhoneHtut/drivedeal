<?php
/*
 * listings.php - Car listings
 *
 * Method Owner
 * GET    Lai
 * POST   Lynn
 * PUT    Lynn
 * DELETE Lynn
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'GET':
    // OWNER: Lai
    // ?make=&minPrice=&maxPrice=&sort=price_asc&page=1 -> { cars: [...], total, page, perPage }
    //   ?id=1 -> one car + seller_name
    // Already live on Mercury - paste that code here first,
    // otherwise deploying this file would replace the live one with this stub.
    // Then add the search, sort and page params.
    not_built_yet('listings.php GET');

  case 'POST':
    // OWNER: Lynn
    // Create. Sends car fields + seller_id -> { listing_id: 16 }
    not_built_yet('listings.php POST');

  case 'PUT':
    // OWNER: Lynn
    // Edit, owner only. Sends listing_id + car fields + seller_id -> { ok: true }
    not_built_yet('listings.php PUT');

  case 'DELETE':
    // OWNER: Lynn
    // Delete, owner only. Sends listing_id + seller_id -> { ok: true }
    not_built_yet('listings.php DELETE');

  default:
    fail('Method not allowed. Use GET, POST, PUT, DELETE.', 405);
}
