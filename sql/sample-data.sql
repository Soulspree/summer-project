-- Sample data for Musician Booking System

-- Insert musician and client users
INSERT INTO users (username, email, password_hash, user_type, first_name, last_name, phone, account_status, created_at)
VALUES
('musician1', 'musician@example.com', '$2y$12$EhwHd3u.Si06SUWaoLaY.O0M8N1uB9dz2Wo1fxg7NzTr.6sUzxdZG', 'musician', 'Music', 'Player', '9800000000', 'active', NOW()),
('client1', 'client@example.com', '$2y$12$EhwHd3u.Si06SUWaoLaY.O0M8N1uB9dz2Wo1fxg7NzTr.6sUzxdZG', 'client', 'Client', 'One', '9800000001', 'active', NOW());

-- User profiles
INSERT INTO user_profiles (user_id, bio, location, city, country, profile_completion_percentage, created_at)
VALUES
(1, 'Experienced guitarist', 'Kathmandu', 'Kathmandu', 'Nepal', 80, NOW()),
(2, 'Event organizer', 'Pokhara', 'Pokhara', 'Nepal', 60, NOW());

-- Musician profile for the musician user
INSERT INTO musician_profiles (user_id, stage_name, genres, instruments, experience_level, base_price_per_hour, base_price_per_event, pricing_negotiable, travel_radius, equipment_provided, availability_status, rating, total_ratings, total_bookings, created_at)
VALUES
(1, 'Guitar Hero', '["rock"]', '["guitar"]', 'professional', 1000.00, 5000.00, 1, 50, 1, 'available', 0, 0, 0, NOW());

-- Sample booking linking the client and musician
INSERT INTO bookings (client_id, musician_id, event_title, event_date, start_time, end_time, venue_name, venue_address, event_type, audience_size, music_genres_requested, special_requests, equipment_provided, total_amount, booking_status, payment_terms, contract_terms, payment_status, created_at)
VALUES
(2, 1, 'Wedding Reception', '2025-06-01', '18:00:00', '20:00:00', 'Grand Hall', '123 Street, Kathmandu', 'wedding', 100, '["rock"]', 'Play favorite song', 1, 5000.00, 'pending', '50_50', 'Standard contract', 'unpaid', NOW());

-- Example payment for the booking
INSERT INTO payments (booking_id, amount, payment_type, payment_method, payment_status, payment_date, reference_number, created_at)
VALUES
(1, 2500.00, 'advance', 'cash', 'paid', NOW(), 'PAY-20250101-ABC123', NOW());
