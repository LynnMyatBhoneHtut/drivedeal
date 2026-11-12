# database/

| File | What it does |
|---|---|
| `car_marketplace_group.sql` | Creates the 6 tables (drops them first, so re-importing is safe) |
| `car_marketplace_sample_data.sql` | 5 users, 15 cars, 30 photos, likes, inquiries |

## Set up your local database

1. In phpMyAdmin, create an empty database called **`car_marketplace`**.
2. Select it, then **Import** `car_marketplace_group.sql`, then `car_marketplace_sample_data.sql` (in that order).
3. Copy `api/db.example.php` to `api/db.php` and put in your MySQL user and password.
4. Run `npm run api` + `npm run dev`, then open http://localhost:8000/api/listings.php — you should see JSON with 14 cars.

To reset the data any time, import both files again.

## Sample logins

Every sample user's password is **`password123`**.

| user_id | Name | Email | Notes |
|---|---|---|---|
| 1 | Mei Ling Tan | meiling@example.com | admin |
| 2 | Ahmad Faizal | ahmad@example.com | the fake-login user; sells 3 cars, has 4 inquiries, liked 4 cars |
| 3 | Priya Raj | priya@example.com | |
| 4 | Jason Wong | jason@example.com | |
| 5 | Sarah Lim | sarah@example.com | |

Sample photos are in `uploads/sample/` and are committed to Git (photos uploaded later through the app are not).

## Tables

| Table | Key columns |
|---|---|
| `users` | user_id, full_name, email (unique), password_hash, phone, role (user/admin) |
| `car_listings` | listing_id, seller_id → users, make, model, year, price, mileage_km, transmission, fuel_type, body_type, colour, location, description, status (available/reserved/sold) |
| `car_images` | image_id, listing_id → car_listings, image_path, sort_order (0 = cover photo) |
| `listing_likes` | user_id + listing_id (together the key, so a user can like a car only once) |
| `inquiries` | inquiry_id, listing_id → car_listings, buyer_id → users, message |
| `contact_messages` | message_id, name, email, message (About page form) |

Deleting a user or a car also deletes their photos, likes and inquiries (`ON DELETE CASCADE`).

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

`sort` can be `newest` (default), `price_asc`, `price_desc`, `year_desc`, `year_asc`, `mileage_asc`.
Full details are in the comments at the top of each `api/*.php` file.
