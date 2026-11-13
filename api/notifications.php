<?php
/*
 * notifications.php - In-app price-drop alerts for the bell in the navbar. OWNER: Yati (D/HD feature).
 *
 * GET ?user_id=5                 -> { "unread": 2, "latest_id": 14, "notifications": [ newest 20 ] }
 * GET ?user_id=5&after_id=14     -> only alerts newer than 14 (what the bell asks for every few seconds,
 *                                   so a check with nothing new sends back almost nothing)
 * PUT { user_id, notification_id }  mark one as read   -> { "unread": 1 }
 * PUT { user_id, all: true }        mark all as read   -> { "unread": 0 }
 *
 * Alerts are created by price_alerts.php when a seller lowers a price, never by the browser.
 */

require_once __DIR__ . '/helpers.php';
require_once __DIR__ . '/price_alerts.php';

require_watchlist_tables();

switch (request_method()) {
  case 'GET':
    $userId = positive_id(int_param('user_id', 0));
    if (!$userId) fail('Please log in to see your alerts.', 401);
    $afterId = positive_id(int_param('after_id', 0));

    $rows = db_select(
      'SELECT n.notification_id, n.listing_id, n.type, n.message, n.old_price, n.new_price,
              n.is_read, n.created_at, c.make, c.model,
              (SELECT i.image_path FROM car_images i WHERE i.listing_id = n.listing_id
                ORDER BY i.sort_order, i.image_id LIMIT 1) AS image_path
         FROM notifications n
         JOIN car_listings c ON c.listing_id = n.listing_id
        WHERE n.user_id = ? AND n.notification_id > ?
        ORDER BY n.notification_id DESC
        LIMIT 20',
      'ii', array($userId, $afterId)
    );
    foreach ($rows as $i => $r) {
      $rows[$i]['notification_id'] = (int) $r['notification_id'];
      $rows[$i]['listing_id'] = (int) $r['listing_id'];
      $rows[$i]['old_price'] = (float) $r['old_price'];
      $rows[$i]['new_price'] = (float) $r['new_price'];
      $rows[$i]['is_read'] = (bool) $r['is_read'];
    }

    reply(array(
      'unread' => unread_count($userId),
      'latest_id' => latest_id($userId),
      'notifications' => $rows
    ));

  case 'PUT':
    $input = json_input();
    $userId = positive_id(isset($input['user_id']) ? $input['user_id'] : 0);
    if (!$userId) fail('Please log in to manage your alerts.', 401);

    if (!empty($input['all'])) {
      $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
      $stmt->bind_param('i', $userId);
    } else {
      $notificationId = positive_id(isset($input['notification_id']) ? $input['notification_id'] : 0);
      if (!$notificationId) fail('notification_id is required (or send "all": true).');
      // user_id in the WHERE: nobody can mark another user's alert as read
      $stmt = db()->prepare('UPDATE notifications SET is_read = 1 WHERE notification_id = ? AND user_id = ?');
      $stmt->bind_param('ii', $notificationId, $userId);
    }
    $stmt->execute();
    $stmt->close();
    reply(array('unread' => unread_count($userId)));

  default:
    fail('Method not allowed. Use GET or PUT.', 405);
}

function unread_count($userId) {
  $row = db_select_one('SELECT COUNT(*) AS n FROM notifications WHERE user_id = ? AND is_read = 0', 'i', array($userId));
  return (int) $row['n'];
}

function latest_id($userId) {
  $row = db_select_one('SELECT MAX(notification_id) AS id FROM notifications WHERE user_id = ?', 'i', array($userId));
  return (int) $row['id'];
}
