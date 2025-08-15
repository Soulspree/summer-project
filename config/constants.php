<?php
/**
 * System Constants
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

// User Types
define('USER_TYPE_MUSICIAN', 'musician');
define('USER_TYPE_CLIENT', 'client');
define('USER_TYPE_ADMIN', 'admin');

// Account Status
define('ACCOUNT_STATUS_ACTIVE', 'active');
define('ACCOUNT_STATUS_INACTIVE', 'inactive');
define('ACCOUNT_STATUS_SUSPENDED', 'suspended');

// Booking Status
define('BOOKING_STATUS_PENDING', 'pending');
define('BOOKING_STATUS_ACCEPTED', 'accepted');
define('BOOKING_STATUS_REJECTED', 'rejected');
define('BOOKING_STATUS_CANCELLED', 'cancelled');
define('BOOKING_STATUS_COMPLETED', 'completed');

// Gig Status
define('GIG_STATUS_SCHEDULED', 'scheduled');
define('GIG_STATUS_COMPLETED', 'completed');
define('GIG_STATUS_CANCELLED', 'cancelled');
define('GIG_STATUS_IN_PROGRESS', 'in_progress');

// Payment Status
define('PAYMENT_STATUS_PENDING', 'pending');
define('PAYMENT_STATUS_COMPLETED', 'completed');
define('PAYMENT_STATUS_FAILED', 'failed');
define('PAYMENT_STATUS_REFUNDED', 'refunded');
define('PAYMENT_STATUS_CANCELLED', 'cancelled');

// Payment Types
define('PAYMENT_TYPE_DEPOSIT', 'deposit');
define('PAYMENT_TYPE_PARTIAL', 'partial');
define('PAYMENT_TYPE_FULL', 'full');
define('PAYMENT_TYPE_BONUS', 'bonus');
define('PAYMENT_TYPE_REFUND', 'refund');

// Payment Methods
define('PAYMENT_METHOD_CASH', 'cash');
define('PAYMENT_METHOD_BANK_TRANSFER', 'bank_transfer');
define('PAYMENT_METHOD_ONLINE', 'online');
define('PAYMENT_METHOD_CHECK', 'check');
define('PAYMENT_METHOD_OTHER', 'other');

// Experience Levels
define('EXPERIENCE_BEGINNER', 'beginner');
define('EXPERIENCE_INTERMEDIATE', 'intermediate');
define('EXPERIENCE_PROFESSIONAL', 'professional');
define('EXPERIENCE_EXPERT', 'expert');

// Availability Status
define('AVAILABILITY_AVAILABLE', 'available');
define('AVAILABILITY_BUSY', 'busy');
define('AVAILABILITY_UNAVAILABLE', 'unavailable');

// Event Types
define('EVENT_TYPE_WEDDING', 'wedding');
define('EVENT_TYPE_CORPORATE', 'corporate');
define('EVENT_TYPE_PRIVATE_PARTY', 'private_party');
define('EVENT_TYPE_CONCERT', 'concert');
define('EVENT_TYPE_RESTAURANT', 'restaurant');
define('EVENT_TYPE_BAR', 'bar');
define('EVENT_TYPE_FESTIVAL', 'festival');
define('EVENT_TYPE_OTHER', 'other');

// Availability Types
define('AVAILABILITY_TYPE_AVAILABLE', 'available');
define('AVAILABILITY_TYPE_BUSY', 'busy');
define('AVAILABILITY_TYPE_BLOCKED', 'blocked');

// Recurring Types
define('RECURRING_NONE', 'none');
define('RECURRING_DAILY', 'daily');
define('RECURRING_WEEKLY', 'weekly');
define('RECURRING_MONTHLY', 'monthly');

// Activity Log Types
define('ACTIVITY_LOGIN', 'login');
define('ACTIVITY_LOGOUT', 'logout');
define('ACTIVITY_BOOKING_REQUEST', 'booking_request');
define('ACTIVITY_BOOKING_CONFIRMED', 'booking_confirmed');
define('ACTIVITY_PAYMENT_MADE', 'payment_made');
define('ACTIVITY_PROFILE_UPDATED', 'profile_updated');
define('ACTIVITY_GIG_CREATED', 'gig_created');
define('ACTIVITY_REVIEW_POSTED', 'review_posted');

// Setting Types
define('SETTING_TYPE_STRING', 'string');
define('SETTING_TYPE_INTEGER', 'integer');
define('SETTING_TYPE_BOOLEAN', 'boolean');
define('SETTING_TYPE_JSON', 'json');

// Common Music Genres (for reference)
define('MUSIC_GENRES', [
    'rock', 'pop', 'jazz', 'classical', 'blues', 'country', 'folk', 
    'electronic', 'hip_hop', 'rap', 'r_n_b', 'soul', 'funk', 'reggae', 
    'punk', 'metal', 'alternative', 'indie', 'acoustic', 'instrumental',
    'nepali_folk', 'nepali_classical', 'nepali_modern', 'bollywood'
]);

// Common Instruments (for reference)
define('INSTRUMENTS', [
    'guitar', 'bass_guitar', 'electric_guitar', 'acoustic_guitar',
    'piano', 'keyboard', 'drums', 'violin', 'flute', 'saxophone',
    'trumpet', 'clarinet', 'cello', 'mandolin', 'harmonica',
    'vocals', 'tabla', 'sitar', 'harmonium', 'madal', 'sarangi'
]);

// File Upload Limits
define('PROFILE_IMAGE_MAX_SIZE', 5242880); // 5MB
define('DEMO_AUDIO_MAX_SIZE', 10485760); // 10MB
define('DOCUMENT_MAX_SIZE', 5242880); // 5MB

// Image Dimensions
define('PROFILE_IMAGE_MAX_WIDTH', 1200);
define('PROFILE_IMAGE_MAX_HEIGHT', 1200);
define('PORTFOLIO_IMAGE_MAX_WIDTH', 1920);
define('PORTFOLIO_IMAGE_MAX_HEIGHT', 1080);

// Audio Specifications
define('DEMO_AUDIO_MAX_DURATION', 300); // 5 minutes in seconds
define('ALLOWED_AUDIO_BITRATES', [128, 192, 256, 320]); // kbps

// Rating System
define('MIN_RATING', 1);
define('MAX_RATING', 5);
define('DEFAULT_RATING', 0);

// Search and Pagination
define('DEFAULT_SEARCH_LIMIT', 20);
define('MAX_SEARCH_RESULTS', 100);
define('SEARCH_MIN_CHARACTERS', 2);

// Geographic Limits
define('MAX_TRAVEL_RADIUS', 500); // kilometers
define('DEFAULT_TRAVEL_RADIUS', 50);

// Pricing Limits
define('MIN_HOURLY_RATE', 500); // NPR
define('MAX_HOURLY_RATE', 50000); // NPR
define('MIN_EVENT_RATE', 2000); // NPR
define('MAX_EVENT_RATE', 500000); // NPR

// Time Limits
define('BOOKING_CONFIRMATION_HOURS', 48);
define('REVIEW_DEADLINE_DAYS', 30);
define('GIG_REMINDER_HOURS', 24);

// Email Templates
define('EMAIL_TEMPLATES', [
    'booking_request' => 'booking-request.html',
    'booking_confirmation' => 'booking-confirmation.html',
    'booking_cancellation' => 'booking-cancellation.html',
    'payment_notification' => 'payment-notification.html',
    'password_reset' => 'password-reset.html',
    'welcome' => 'welcome.html'
]);

// Social Media Platforms
define('SOCIAL_PLATFORMS', [
    'facebook', 'instagram', 'twitter', 'youtube', 'spotify', 
    'soundcloud', 'bandcamp', 'linkedin', 'tiktok'
]);

// Common Error Messages
define('ERROR_MESSAGES', [
    'invalid_credentials' => 'Invalid email or password',
    'account_suspended' => 'Your account has been suspended',
    'email_exists' => 'Email address already exists',
    'username_exists' => 'Username already exists',
    'invalid_email' => 'Please enter a valid email address',
    'weak_password' => 'Password must be at least 8 characters long',
    'booking_not_found' => 'Booking not found',
    'gig_not_found' => 'Gig not found',
    'unauthorized' => 'You are not authorized to perform this action',
    'file_too_large' => 'File size exceeds maximum limit',
    'invalid_file_type' => 'Invalid file type',
    'database_error' => 'Database error occurred',
    'network_error' => 'Network error, please try again'
]);

// Success Messages
define('SUCCESS_MESSAGES', [
    'registration_success' => 'Registration successful! Please log in.',
    'login_success' => 'Welcome back!',
    'profile_updated' => 'Profile updated successfully',
    'booking_submitted' => 'Booking request submitted successfully',
    'booking_confirmed' => 'Booking confirmed successfully',
    'payment_recorded' => 'Payment recorded successfully',
    'gig_created' => 'Gig created successfully',
    'review_submitted' => 'Review submitted successfully',