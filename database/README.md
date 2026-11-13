# database/

| File | What it does |
|---|---|
| `drivedeal.sql` | **The whole database in one file.** Drops and creates all 12 tables, then fills them with sample data |

## Set up your local database

1. In phpMyAdmin, create an empty database called **`car_marketplace`**.
2. Select it, then **Import** `drivedeal.sql`. That's the only file.
3. Copy `api/db.example.php` to `api/db.php` and put in your MySQL user and password.
4. Run `npm run api` + `npm run dev`, then open http://localhost:8000/api/listings.php — you should see JSON with 14 cars.

To reset the data any time, import `drivedeal.sql` again (it drops every table first, so re-importing is safe).

On Mercury: select your own database (`s<id>_db`) in phpMyAdmin and import the same file — no `CREATE DATABASE` needed.

## Sample logins

Every sample user's password is **`password123`**.

| user_id | Name | Email | Notes |
|---|---|---|---|
| 1 | Mei Ling Tan | meiling@example.com | admin; sells the Honda City and Corolla Cross |
| 2 | Ahmad Faizal | ahmad@example.com | the fake-login user; sells 3 cars, has inquiries and 2 offers on his Myvi, is negotiating for the Honda City |
| 3 | Priya Raj | priya@example.com | negotiating for Ahmad's Myvi (test drive requested) |
| 4 | Jason Wong | jason@example.com | made an offer on the Myvi; watches the Corolla Cross |
| 5 | Sarah Lim | sarah@example.com | watches 3 cars and has price-drop alerts |

Sample photos are in `uploads/sample/` and are committed to Git (photos uploaded later through the app are not).

## Tables

**Core marketplace (group)**

| Table | Key columns |
|---|---|
| `users` | user_id, full_name, email (unique), password_hash, phone, role (user/admin) |
| `car_listings` | listing_id, seller_id → users, make, model, year, price, mileage_km, transmission, fuel_type, body_type, colour, location, description, status (available/reserved/sold) |
| `car_images` | image_id, listing_id → car_listings, image_path, sort_order (0 = cover photo) |
| `listing_likes` | user_id + listing_id (together the key, so a user can like a car only once) |
| `inquiries` | inquiry_id, listing_id → car_listings, buyer_id → users, message |
| `contact_messages` | message_id, name, email, message (About page form) |

**Price-Drop Alert & Watchlist (Yati)**

| Table | Key columns |
|---|---|
| `watchlist` | user_id + listing_id, price_when_added, target_price |
| `price_history` | history_id, listing_id → car_listings, old_price, new_price, changed_at |
| `notifications` | notification_id, user_id → users, listing_id, history_id → price_history, type (price_drop/target_reached), message, is_read |

**Make-an-Offer & Negotiation with Test Drive Request (Lynn)**

| Table | Key columns |
|---|---|
| `offers` | offer_id, listing_id → car_listings, buyer_id → users, status (open/accepted/rejected/withdrawn/closed), agreed_price |
| `offer_events` | event_id, offer_id → offers, actor (buyer/seller/system), action (offer/counter/accept/reject/withdraw/close), amount, message |
| `test_drives` | test_drive_id, offer_id → offers, preferred_date, preferred_time, note, status (requested/confirmed/declined/cancelled) |

Deleting a user or a car also deletes everything that belongs to it — photos, likes, inquiries, watchlist rows, alerts, offers and test drives (`ON DELETE CASCADE`).

## Changing the database

Everyone's pages depend on these table names, so tell the group first. Then edit `drivedeal.sql` (the table in its Part 1–3, its sample rows in Part 4), re-import it, and commit it — please don't add separate `.sql` files, so there is always just one file to import.

## Read-only API (already working)

| Call | Returns |
|---|---|
| `listings.php?q=&make=&minPrice=&maxPrice=&sort=&page=&perPage=` | `{ cars, total, page, perPage, totalPages, makes }` — sold cars hidden |
| `listings.php?id=3&user_id=2` | one car + `seller_name`, `images`, `likes`, `liked` |
| `listings.php?seller_id=2` | that seller's cars, all statuses (My listings) |
| `images.php?listing_id=3` | that car's photos |
| `likes.php?listing_id=3&user_id=2` | `{ likes, liked }` |
| `likes.php?user_id=2` | `{ cars }` the user liked (Liked cars page) |
| `inquiries.php?seller_id=2` (or `buyer_id`, `listing_id`) | messages with car and buyer details |
| `users.php?id=2` | user without password |
| `offers.php?buyer_id=2` (or `seller_id`, `listing_id&user_id`) | negotiations with history, test drive and summary |

`sort` can be `newest` (default), `price_asc`, `price_desc`, `year_desc`, `year_asc`, `mileage_asc`.
Full details are in the comments at the top of each `api/*.php` file.
