<?php
/*
 * likes.php - Likes / votes
 *
 * Method Owner
 * GET    already live
 * POST   Yati
 * DELETE Yati
 *
 * Each person only edits the case(s) they own, so nobody touches the same lines.
 * Contract: see the API contract table in the group task plan.
 */

require_once __DIR__ . '/helpers.php';

switch (request_method()) {
  case 'GET':
    // OWNER: already live
    // Already live on Mercury - paste that code here first,
    // otherwise deploying this file would replace the live one with this stub.
    not_built_yet('likes.php GET');

  case 'POST':
    // OWNER: Yati
    // Like. Sends user_id, listing_id -> { likes: 4, liked: true }
    not_built_yet('likes.php POST');

  case 'DELETE':
    // OWNER: Yati
    // Unlike. Sends user_id, listing_id -> { likes: 3, liked: false }
    not_built_yet('likes.php DELETE');

  default:
    fail('Method not allowed. Use GET, POST, DELETE.', 405);
}
