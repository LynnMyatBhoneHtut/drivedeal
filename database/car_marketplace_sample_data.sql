-- =============================================================
-- DriveDeal - sample data. Import AFTER car_marketplace_group.sql.
--
-- Every sample user's password is:  password123
--   user_id 2 = Ahmad Faizal (ahmad@example.com) - the fake-login user in the group plan;
--               he sells 3 cars and has inquiries, so My listings has data to show.
--   user_id 1 = Mei Ling Tan is the admin.
-- Photos live in uploads/sample/ (committed to Git so everyone sees the same cars).
-- =============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE contact_messages;
TRUNCATE TABLE inquiries;
TRUNCATE TABLE listing_likes;
TRUNCATE TABLE car_images;
TRUNCATE TABLE car_listings;
TRUNCATE TABLE users;
SET FOREIGN_KEY_CHECKS = 1;

INSERT INTO users (user_id, full_name, email, password_hash, phone, role, created_at) VALUES
  (1, 'Mei Ling Tan', 'meiling@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '012-345 6789', 'admin', NOW() - INTERVAL 59 DAY),
  (2, 'Ahmad Faizal', 'ahmad@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '013-222 4455', 'user', NOW() - INTERVAL 58 DAY),
  (3, 'Priya Raj', 'priya@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '016-777 1020', 'user', NOW() - INTERVAL 57 DAY),
  (4, 'Jason Wong', 'jason@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '017-888 3141', 'user', NOW() - INTERVAL 56 DAY),
  (5, 'Sarah Lim', 'sarah@example.com', '$2y$10$YEqhyp8RAzk4ns/OdyRCq.gPOJvlGxabuTrmlD6yQVLq2H4wgPMTK', '019-555 2718', 'user', NOW() - INTERVAL 55 DAY);

INSERT INTO car_listings (listing_id, seller_id, make, model, year, price, mileage_km, transmission, fuel_type, body_type, colour, location, description, status, created_at) VALUES
  (1, 1, 'Toyota', 'Corolla Cross', 2022, 105000.00, 35000, 'Automatic', 'Petrol', 'SUV', 'White', 'Subang Jaya, Selangor', 'Full Toyota service record, one owner, still under warranty. Reverse camera and 360 sensors.', 'available', NOW() - INTERVAL 2 DAY),
  (2, 3, 'Honda', 'Civic', 2021, 89000.00, 48000, 'Automatic', 'Petrol', 'Sedan', 'Black', 'Petaling Jaya, Selangor', '1.5 TC-P turbo. Non-smoker, no accident, new tyres fitted last month.', 'available', NOW() - INTERVAL 5 DAY),
  (3, 2, 'Perodua', 'Myvi', 2023, 48000.00, 18000, 'Automatic', 'Petrol', 'Hatchback', 'Red', 'Shah Alam, Selangor', '1.5 AV with Advanced Safety Assist. Low mileage, like new.', 'available', NOW() - INTERVAL 1 DAY),
  (4, 4, 'Proton', 'X50', 2022, 82000.00, 30000, 'Automatic', 'Petrol', 'SUV', 'Grey', 'Kuala Lumpur', '1.5 TGDi Flagship. Panoramic roof, ADAS, full Proton service history.', 'available', NOW() - INTERVAL 8 DAY),
  (5, 5, 'Perodua', 'Axia', 2020, 26500.00, 62000, 'Automatic', 'Petrol', 'Hatchback', 'Silver', 'Klang, Selangor', 'Very fuel efficient city car. Ideal first car for students.', 'available', NOW() - INTERVAL 12 DAY),
  (6, 2, 'Proton', 'Saga', 2019, 22000.00, 78000, 'Manual', 'Petrol', 'Sedan', 'Blue', 'Ipoh, Perak', 'Manual 1.3 Standard. Cheap to maintain, road tax until next year.', 'available', NOW() - INTERVAL 20 DAY),
  (7, 1, 'Honda', 'City', 2020, 62000.00, 55000, 'Automatic', 'Petrol', 'Sedan', 'White', 'Cheras, Kuala Lumpur', '1.5 V spec. Spacious boot, Honda Sensing, serviced at Honda.', 'available', NOW() - INTERVAL 4 DAY),
  (8, 3, 'Toyota', 'Vios', 2018, 48500.00, 90000, 'Automatic', 'Petrol', 'Sedan', 'Silver', 'Johor Bahru, Johor', 'Reliable daily car. Recently serviced, aircond cold.', 'available', NOW() - INTERVAL 15 DAY),
  (9, 4, 'Mazda', 'CX-5', 2021, 118000.00, 41000, 'Automatic', 'Petrol', 'SUV', 'Red', 'George Town, Penang', '2.5 High spec in Soul Red. Bose sound system, leather seats.', 'available', NOW() - INTERVAL 6 DAY),
  (10, 5, 'Toyota', 'Hilux', 2019, 86000.00, 98000, 'Manual', 'Diesel', 'Pickup', 'White', 'Seremban, Negeri Sembilan', '2.4 4x4 with canopy. Strong engine, good for work and outstation trips.', 'available', NOW() - INTERVAL 18 DAY),
  (11, 2, 'Honda', 'HR-V', 2023, 128000.00, 12000, 'Automatic', 'Hybrid', 'SUV', 'Blue', 'Puchong, Selangor', 'e:HEV RS. Hybrid, very low mileage, full warranty. Deposit received.', 'reserved', NOW() - INTERVAL 3 DAY),
  (12, 1, 'Proton', 'X70', 2021, 95000.00, 46000, 'Automatic', 'Petrol', 'SUV', 'Black', 'Cyberjaya, Selangor', '1.8 TGDi Premium. 7-speed DCT, powered tailgate.', 'available', NOW() - INTERVAL 10 DAY),
  (13, 3, 'Perodua', 'Alza', 2022, 68000.00, 28000, 'Automatic', 'Petrol', 'MPV', 'Grey', 'Bangi, Selangor', '1.5 AV, 7 seats. Perfect family car with Perodua Smart Drive Assist.', 'available', NOW() - INTERVAL 7 DAY),
  (14, 4, 'BMW', '330e', 2020, 168000.00, 52000, 'Automatic', 'Hybrid', 'Sedan', 'White', 'Mont Kiara, Kuala Lumpur', 'M Sport plug-in hybrid. Full BMW service record, extended warranty.', 'available', NOW() - INTERVAL 9 DAY),
  (15, 5, 'Nissan', 'Almera', 2021, 64000.00, 39000, 'Automatic', 'Petrol', 'Sedan', 'Orange', 'Kajang, Selangor', '1.0 Turbo VLT. Sold - kept here so the sold status can be tested.', 'sold', NOW() - INTERVAL 25 DAY);

INSERT INTO car_images (listing_id, image_path, sort_order) VALUES
  (1, 'uploads/sample/listing-1-1.jpg', 0),
  (1, 'uploads/sample/listing-1-2.jpg', 1),
  (2, 'uploads/sample/listing-2-1.jpg', 0),
  (2, 'uploads/sample/listing-2-2.jpg', 1),
  (3, 'uploads/sample/listing-3-1.jpg', 0),
  (3, 'uploads/sample/listing-3-2.jpg', 1),
  (4, 'uploads/sample/listing-4-1.jpg', 0),
  (4, 'uploads/sample/listing-4-2.jpg', 1),
  (5, 'uploads/sample/listing-5-1.jpg', 0),
  (5, 'uploads/sample/listing-5-2.jpg', 1),
  (6, 'uploads/sample/listing-6-1.jpg', 0),
  (6, 'uploads/sample/listing-6-2.jpg', 1),
  (7, 'uploads/sample/listing-7-1.jpg', 0),
  (7, 'uploads/sample/listing-7-2.jpg', 1),
  (8, 'uploads/sample/listing-8-1.jpg', 0),
  (8, 'uploads/sample/listing-8-2.jpg', 1),
  (9, 'uploads/sample/listing-9-1.jpg', 0),
  (9, 'uploads/sample/listing-9-2.jpg', 1),
  (10, 'uploads/sample/listing-10-1.jpg', 0),
  (10, 'uploads/sample/listing-10-2.jpg', 1),
  (11, 'uploads/sample/listing-11-1.jpg', 0),
  (11, 'uploads/sample/listing-11-2.jpg', 1),
  (12, 'uploads/sample/listing-12-1.jpg', 0),
  (12, 'uploads/sample/listing-12-2.jpg', 1),
  (13, 'uploads/sample/listing-13-1.jpg', 0),
  (13, 'uploads/sample/listing-13-2.jpg', 1),
  (14, 'uploads/sample/listing-14-1.jpg', 0),
  (14, 'uploads/sample/listing-14-2.jpg', 1),
  (15, 'uploads/sample/listing-15-1.jpg', 0),
  (15, 'uploads/sample/listing-15-2.jpg', 1);

INSERT INTO listing_likes (user_id, listing_id) VALUES
  (2, 1),
  (2, 4),
  (2, 9),
  (2, 14),
  (3, 1),
  (3, 3),
  (4, 1),
  (4, 3),
  (4, 11),
  (5, 1),
  (5, 9),
  (5, 3),
  (1, 3),
  (1, 11),
  (3, 9),
  (5, 14);

INSERT INTO inquiries (listing_id, buyer_id, message, created_at) VALUES
  (3, 4, 'Hi, is the Myvi still available? Can I view it this weekend?', NOW() - INTERVAL 1 DAY),
  (3, 5, 'Would you accept RM 45,000? I can pay in cash.', NOW() - INTERVAL 0 DAY),
  (6, 3, 'Hello, is the price negotiable? And has the timing belt been changed?', NOW() - INTERVAL 4 DAY),
  (11, 1, 'Hi Ahmad, if the deposit falls through please let me know. Very interested.', NOW() - INTERVAL 2 DAY),
  (1, 2, 'Hi, how many keys come with the car? Any scratches?', NOW() - INTERVAL 1 DAY),
  (9, 2, 'Is the CX-5 still under warranty?', NOW() - INTERVAL 3 DAY),
  (4, 5, 'Can I bring a mechanic to inspect the X50?', NOW() - INTERVAL 2 DAY);

INSERT INTO contact_messages (name, email, message) VALUES
  ('Daniel Lee', 'daniel@example.com', 'Hi, do you charge sellers a fee to list a car on DriveDeal?');
