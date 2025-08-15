-- Database Schema for Musician Booking System

-- Users table stores authentication and basic account information
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(100) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    user_type ENUM('musician','client','admin') NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    account_status ENUM('active','inactive','suspended') DEFAULT 'active',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- User profiles extend user information for both clients and musicians
CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    bio TEXT,
    location VARCHAR(255),
    city VARCHAR(100),
    country VARCHAR(100),
    phone VARCHAR(20),
    address VARCHAR(255),
    social_media JSON,
    organization_name VARCHAR(255),
    organization_type VARCHAR(100),
    preferred_genres JSON,
    typical_event_types JSON,
    typical_budget_range VARCHAR(50),
    event_frequency VARCHAR(50),
    preferred_contact_method VARCHAR(50),
    profile_completion_percentage INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Musician specific profile information
CREATE TABLE IF NOT EXISTS musician_profiles (
    musician_profile_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    stage_name VARCHAR(255),
    genres JSON,
    instruments JSON,
    experience_level VARCHAR(50),
    years_of_experience INT,
    base_price_per_hour DECIMAL(10,2) DEFAULT 0,
    base_price_per_event DECIMAL(10,2) DEFAULT 0,
    pricing_negotiable TINYINT(1) DEFAULT 1,
    travel_radius INT DEFAULT 0,
    equipment_provided TINYINT(1) DEFAULT 0,
    availability_status VARCHAR(50) DEFAULT 'available',
    rating DECIMAL(3,2) DEFAULT 0,
    total_ratings INT DEFAULT 0,
    total_bookings INT DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Gig records for musicians
CREATE TABLE IF NOT EXISTS gigs (
    gig_id INT AUTO_INCREMENT PRIMARY KEY,
    musician_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    venue_name VARCHAR(255) NOT NULL,
    venue_address VARCHAR(255),
    venue_contact VARCHAR(100),
    gig_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    gig_type VARCHAR(50),
    gig_status VARCHAR(50) DEFAULT 'scheduled',
    agreed_amount DECIMAL(10,2),
    payment_terms VARCHAR(100),
    equipment_required TEXT,
    special_requirements TEXT,
    audience_size INT,
    performance_notes TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (musician_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Booking requests between clients and musicians
CREATE TABLE IF NOT EXISTS bookings (
    booking_id INT AUTO_INCREMENT PRIMARY KEY,
    client_id INT NOT NULL,
    musician_id INT NOT NULL,
    gig_id INT DEFAULT NULL,
    event_title VARCHAR(255) NOT NULL,
    event_date DATE NOT NULL,
    start_time TIME NOT NULL,
    end_time TIME,
    venue_name VARCHAR(255) NOT NULL,
    venue_address VARCHAR(255),
    event_type VARCHAR(50),
    audience_size INT,
    music_genres_requested JSON,
    special_requests TEXT,
    equipment_provided TINYINT(1) DEFAULT 0,
    total_amount DECIMAL(10,2),
    booking_status VARCHAR(50) DEFAULT 'pending',
    payment_status VARCHAR(50) DEFAULT 'unpaid',
    payment_terms VARCHAR(100),
    contract_terms TEXT,
    cancellation_reason TEXT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (musician_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (gig_id) REFERENCES gigs(gig_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payments associated with bookings
CREATE TABLE IF NOT EXISTS payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_type VARCHAR(50) NOT NULL,
    payment_method VARCHAR(50) NOT NULL,
    payment_status VARCHAR(50) DEFAULT 'pending',
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    transaction_id VARCHAR(100),
    reference_number VARCHAR(100),
    notes TEXT,
    received_by INT,
    verified_by INT,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(booking_id) ON DELETE CASCADE,
    FOREIGN KEY (received_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (verified_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Failed login attempts for security monitoring
CREATE TABLE IF NOT EXISTS login_attempts (
    attempt_id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) NOT NULL,
    ip_address VARCHAR(45),
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity log for auditing user actions
CREATE TABLE IF NOT EXISTS activity_logs (
    log_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    activity_type VARCHAR(100),
    description TEXT,
    ip_address VARCHAR(45),
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
