<?php
/**
 * Booking Class - Complete Booking System Backend
 * 
 * Handles booking requests, confirmations, status management, 
 * availability checking, notifications, and booking workflows
 * 
 * @author Subham Shrestha
 * @version 1.0
 * @since 2025
 */

require_once __DIR__ . '/../config/database.php';

class Booking {
    
    private $db;
    private $error;
    
    // Booking properties
    private $booking_id;
    private $client_id;
    private $musician_id;
    private $event_title;
    private $event_date;
    private $start_time;
    private $end_time;
    private $venue_name;
    private $venue_address;
    private $event_type;
    private $audience_size;
    private $music_genres_requested;
    private $special_requests;
    private $equipment_provided;
    private $total_amount;
    private $booking_status;
    private $payment_terms;
    private $contract_terms;
    
    // Constants for booking status
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_COMPLETED = 'completed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_RESCHEDULED = 'rescheduled';
    
    // Constants for event types
    const EVENT_WEDDING = 'wedding';
    const EVENT_PARTY = 'party';
    const EVENT_CORPORATE = 'corporate';
    const EVENT_CONCERT = 'concert';
    const EVENT_FESTIVAL = 'festival';
    const EVENT_BAR_CLUB = 'bar_club';
    const EVENT_RESTAURANT = 'restaurant';
    const EVENT_PRIVATE = 'private_event';
    const EVENT_CHARITY = 'charity';
    const EVENT_OTHER = 'other';
    
    /**
     * Constructor - Initialize booking object
     */
    public function __construct($db = null) {
        if ($db === null) {
            $this->db = getDB();
        } else {
            $this->db = $db;
        }
    }
    
    /**
     * Create new booking request
     * 
     * @param int $client_id Client user ID
     * @param int $musician_id Musician user ID
     * @param array $booking_data Booking information
     * @return int|false Booking ID on success, false on failure
     */
    public function createBookingRequest($client_id, $musician_id, $booking_data) {
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $required_fields = ['event_title', 'event_date', 'start_time', 'venue_name', 'event_type'];
            if (!$this->validateRequiredFields($booking_data, $required_fields)) {
                throw new Exception("Missing required booking fields");
            }
            
            // Validate booking data
            if (!$this->validateBookingData($booking_data)) {
                throw new Exception($this->getError());
            }
            
            // Check musician availability
            if (!$this->checkMusicianAvailability($musician_id, $booking_data['event_date'], 
                                                 $booking_data['start_time'], $booking_data['end_time'])) {
                throw new Exception("Musician is not available at the requested time");
            }
            
            // Check if musician account is active
            if (!$this->isMusicianActive($musician_id)) {
                throw new Exception("Selected musician account is not active");
            }
            
            // Prepare booking data
            $data = $this->prepareBookingData($client_id, $musician_id, $booking_data);
            
            // Insert booking
            $sql = "INSERT INTO bookings (
                        client_id, musician_id, event_title, event_date, start_time, end_time,
                        venue_name, venue_address, event_type, audience_size, 
                        music_genres_requested, special_requests, equipment_provided,
                        total_amount, booking_status, payment_terms, contract_terms,
                        created_at
                    ) VALUES (
                        :client_id, :musician_id, :event_title, :event_date, :start_time, :end_time,
                        :venue_name, :venue_address, :event_type, :audience_size,
                        :music_genres_requested, :special_requests, :equipment_provided,
                        :total_amount, :booking_status, :payment_terms, :contract_terms,
                        NOW()
                    )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!$result) {
                throw new Exception("Failed to create booking request");
            }
            
            $booking_id = $this->db->lastInsertId();
            
            // Log activities
            $this->logActivity($client_id, ACTIVITY_BOOKING_REQUEST, "Booking request sent for: " . $booking_data['event_title']);
            $this->logActivity($musician_id, 'booking_received', "New booking request received from client");
            
            $this->db->commit();
            return $booking_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Update booking status (musician action)
     * 
     * @param int $booking_id Booking ID
     * @param string $status New status
     * @param int $musician_id Musician user ID (for authorization)
     * @param array $additional_data Additional data for the update
     * @return bool Success status
     */
    public function updateBookingStatus($booking_id, $status, $musician_id, $additional_data = []) {
        try {
            $this->db->beginTransaction();
            
            // Verify booking ownership
            if (!$this->verifyMusicianBooking($booking_id, $musician_id)) {
                throw new Exception("Unauthorized: You can only update your own bookings");
            }
            
            // Get current booking
            $current_booking = $this->getBookingById($booking_id);
            if (!$current_booking) {
                throw new Exception("Booking not found");
            }
            
            // Validate status transition
            if (!$this->isValidStatusTransition($current_booking['booking_status'], $status)) {
                throw new Exception("Invalid status transition");
            }
            
            // Validate status
            $valid_statuses = [
                self::STATUS_PENDING, self::STATUS_CONFIRMED, self::STATUS_REJECTED,
                self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_IN_PROGRESS,
                self::STATUS_RESCHEDULED
            ];
            
            if (!in_array($status, $valid_statuses)) {
                throw new Exception("Invalid booking status");
            }
            
            // Prepare update data
            $update_fields = ['booking_status = :status'];
            $params = [
                'booking_id' => $booking_id,
                'status' => $status
            ];
            
            // Handle additional data based on status
            if ($status === self::STATUS_CONFIRMED) {
                if (isset($additional_data['total_amount'])) {
                    $update_fields[] = 'total_amount = :total_amount';
                    $params['total_amount'] = $additional_data['total_amount'];
                }
                if (isset($additional_data['payment_terms'])) {
                    $update_fields[] = 'payment_terms = :payment_terms';
                    $params['payment_terms'] = $additional_data['payment_terms'];
                }
                if (isset($additional_data['contract_terms'])) {
                    $update_fields[] = 'contract_terms = :contract_terms';
                    $params['contract_terms'] = $additional_data['contract_terms'];
                }
                
                // Create corresponding gig when booking is confirmed
                $this->createGigFromBooking($current_booking, $additional_data);
            }
            
            if ($status === self::STATUS_REJECTED && isset($additional_data['rejection_reason'])) {
                $update_fields[] = 'rejection_reason = :rejection_reason';
                $params['rejection_reason'] = $additional_data['rejection_reason'];
            }
            
            // Update booking
            $sql = "UPDATE bookings SET " . implode(', ', $update_fields) . 
                   ", updated_at = NOW() WHERE booking_id = :booking_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new Exception("Failed to update booking status");
            }
            
            // Log activities
            $this->logActivity($musician_id, 'booking_status_updated', "Booking status updated to: $status");
            
            if ($status === self::STATUS_CONFIRMED) {
                $this->logActivity($current_booking['client_id'], ACTIVITY_BOOKING_CONFIRMED, "Your booking has been confirmed");
            }
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Cancel booking (client action)
     * 
     * @param int $booking_id Booking ID
     * @param int $client_id Client user ID (for authorization)
     * @param string $cancellation_reason Reason for cancellation
     * @return bool Success status
     */
    public function cancelBooking($booking_id, $client_id, $cancellation_reason = '') {
        try {
            $this->db->beginTransaction();
            
            // Verify booking ownership
            if (!$this->verifyClientBooking($booking_id, $client_id)) {
                throw new Exception("Unauthorized: You can only cancel your own bookings");
            }
            
            // Get current booking
            $booking = $this->getBookingById($booking_id);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Check if booking can be cancelled
            if (!in_array($booking['booking_status'], [self::STATUS_PENDING, self::STATUS_CONFIRMED])) {
                throw new Exception("Booking cannot be cancelled in current status");
            }
            
            // Update booking status
            $sql = "UPDATE bookings 
                    SET booking_status = :status, cancellation_reason = :reason, updated_at = NOW() 
                    WHERE booking_id = :booking_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'status' => self::STATUS_CANCELLED,
                'reason' => $cancellation_reason,
                'booking_id' => $booking_id
            ]);
            
            if (!$result) {
                throw new Exception("Failed to cancel booking");
            }
            
            // Cancel corresponding gig if exists
            if ($booking['booking_status'] === self::STATUS_CONFIRMED) {
                $this->cancelCorrespondingGig($booking_id);
            }
            
            // Log activities
            $this->logActivity($client_id, 'booking_cancelled', "Booking cancelled: " . $booking['event_title']);
            $this->logActivity($booking['musician_id'], 'booking_cancelled_by_client', "Client cancelled booking");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get booking by ID
     * 
     * @param int $booking_id Booking ID
     * @return array|false Booking data or false
     */
    public function getBookingById($booking_id) {
        try {
            $sql = "SELECT b.*, 
                           c.username as client_username, c.email as client_email,
                           cp.bio as client_bio, cp.location as client_location, cp.phone as client_phone,
                           m.username as musician_username, m.email as musician_email,
                           mp.stage_name, mp.rating as musician_rating,
                           mup.location as musician_location, mup.phone as musician_phone
                    FROM bookings b
                    LEFT JOIN users c ON b.client_id = c.user_id
                    LEFT JOIN user_profiles cp ON b.client_id = cp.user_id
                    LEFT JOIN users m ON b.musician_id = m.user_id
                    LEFT JOIN musician_profiles mp ON b.musician_id = mp.user_id
                    LEFT JOIN user_profiles mup ON b.musician_id = mup.user_id
                    WHERE b.booking_id = :booking_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['booking_id' => $booking_id]);
            
            $booking = $stmt->fetch();
            
            if ($booking && !empty($booking['music_genres_requested'])) {
                $booking['music_genres_requested'] = json_decode($booking['music_genres_requested'], true);
            }
            
            return $booking;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get musician's bookings with filtering
     * 
     * @param int $musician_id Musician user ID
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Bookings with pagination info
     */
    public function getMusicianBookings($musician_id, $filters = [], $page = 1, $per_page = 10) {
        try {
            $where_conditions = ["b.musician_id = :musician_id"];
            $params = ['musician_id' => $musician_id];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $where_conditions[] = "b.booking_status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['event_type'])) {
                $where_conditions[] = "b.event_type = :event_type";
                $params['event_type'] = $filters['event_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "b.event_date >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "b.event_date <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['search'])) {
                $where_conditions[] = "(b.event_title LIKE :search OR b.venue_name LIKE :search OR c.username LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM bookings b 
                          LEFT JOIN users c ON b.client_id = c.user_id 
                          WHERE $where_clause";
            $count_stmt = $this->db->prepare($count_sql);
            $count_stmt->execute($params);
            $total_count = $count_stmt->fetch()['total'];
            
            // Get paginated results
            $offset = ($page - 1) * $per_page;
            $order_by = $this->buildBookingOrderBy($filters['sort_by'] ?? 'date_desc');
            
            $sql = "SELECT b.*, c.username as client_username, cp.location as client_location
                    FROM bookings b
                    LEFT JOIN users c ON b.client_id = c.user_id
                    LEFT JOIN user_profiles cp ON b.client_id = cp.user_id
                    WHERE $where_clause 
                    $order_by 
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $per_page;
            $params['offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($bookings as &$booking) {
                if (!empty($booking['music_genres_requested'])) {
                    $booking['music_genres_requested'] = json_decode($booking['music_genres_requested'], true);
                }
            }
            
            return [
                'bookings' => $bookings,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_count,
                    'total_pages' => ceil($total_count / $per_page),
                    'has_next' => $page < ceil($total_count / $per_page),
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return ['bookings' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get client's bookings with filtering
     * 
     * @param int $client_id Client user ID
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Bookings with pagination info
     */
    public function getClientBookings($client_id, $filters = [], $page = 1, $per_page = 10) {
        try {
            $where_conditions = ["b.client_id = :client_id"];
            $params = ['client_id' => $client_id];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $where_conditions[] = "b.booking_status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['event_type'])) {
                $where_conditions[] = "b.event_type = :event_type";
                $params['event_type'] = $filters['event_type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "b.event_date >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "b.event_date <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['musician'])) {
                $where_conditions[] = "mp.stage_name LIKE :musician";
                $params['musician'] = '%' . $filters['musician'] . '%';
            }
            
            if (!empty($filters['search'])) {
                $where_conditions[] = "(b.event_title LIKE :search OR b.venue_name LIKE :search OR mp.stage_name LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM bookings b 
                          LEFT JOIN musician_profiles mp ON b.musician_id = mp.user_id 
                          WHERE $where_clause";
            $count_stmt = $this->db->prepare($count_sql);
            $count_stmt->execute($params);
            $total_count = $count_stmt->fetch()['total'];
            
            // Get paginated results
            $offset = ($page - 1) * $per_page;
            $order_by = $this->buildBookingOrderBy($filters['sort_by'] ?? 'date_desc');
            
            $sql = "SELECT b.*, mp.stage_name, mp.rating as musician_rating, 
                           mup.location as musician_location
                    FROM bookings b
                    LEFT JOIN musician_profiles mp ON b.musician_id = mp.user_id
                    LEFT JOIN user_profiles mup ON b.musician_id = mup.user_id
                    WHERE $where_clause 
                    $order_by 
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $per_page;
            $params['offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($bookings as &$booking) {
                if (!empty($booking['music_genres_requested'])) {
                    $booking['music_genres_requested'] = json_decode($booking['music_genres_requested'], true);
                }
            }
            
            return [
                'bookings' => $bookings,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $per_page,
                    'total_items' => $total_count,
                    'total_pages' => ceil($total_count / $per_page),
                    'has_next' => $page < ceil($total_count / $per_page),
                    'has_prev' => $page > 1
                ]
            ];
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return ['bookings' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get booking statistics for musician
     * 
     * @param int $musician_id Musician user ID
     * @param string $period Time period ('month', 'year', 'all')
     * @return array Booking statistics
     */
    public function getMusicianBookingStats($musician_id, $period = 'year') {
        try {
            $stats = [];
            
            // Build date condition
            $date_condition = $this->buildDateCondition($period);
            
            // Total bookings
            $sql = "SELECT COUNT(*) as total FROM bookings 
                    WHERE musician_id = :musician_id $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['total_bookings'] = $stmt->fetch()['total'];
            
            // Bookings by status
            $sql = "SELECT booking_status, COUNT(*) as count FROM bookings 
                    WHERE musician_id = :musician_id $date_condition
                    GROUP BY booking_status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            
            $status_counts = [];
            while ($row = $stmt->fetch()) {
                $status_counts[$row['booking_status']] = $row['count'];
            }
            $stats['by_status'] = $status_counts;
            
            // Total earnings from completed bookings
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_earnings FROM bookings 
                    WHERE musician_id = :musician_id AND booking_status = 'completed' $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['total_earnings'] = $stmt->fetch()['total_earnings'];
            
            // Pending earnings
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as pending_earnings FROM bookings 
                    WHERE musician_id = :musician_id AND booking_status = 'confirmed' $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['pending_earnings'] = $stmt->fetch()['pending_earnings'];
            
            // Average booking amount
            $sql = "SELECT COALESCE(AVG(total_amount), 0) as avg_amount FROM bookings 
                    WHERE musician_id = :musician_id AND total_amount > 0 $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['average_amount'] = $stmt->fetch()['avg_amount'];
            
            // Bookings by event type
            $sql = "SELECT event_type, COUNT(*) as count FROM bookings 
                    WHERE musician_id = :musician_id $date_condition
                    GROUP BY event_type
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            
            $type_counts = [];
            while ($row = $stmt->fetch()) {
                $type_counts[$row['event_type']] = $row['count'];
            }
            $stats['by_event_type'] = $type_counts;
            
            // Response rate (confirmed + rejected / total requests)
            $sql = "SELECT 
                        COUNT(*) as total_requests,
                        SUM(CASE WHEN booking_status IN ('confirmed', 'rejected') THEN 1 ELSE 0 END) as responded
                    FROM bookings 
                    WHERE musician_id = :musician_id $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $response_data = $stmt->fetch();
            
            if ($response_data['total_requests'] > 0) {
                $stats['response_rate'] = round(($response_data['responded'] / $response_data['total_requests']) * 100, 2);
            } else {
                $stats['response_rate'] = 0;
            }
            
            // Confirmation rate (confirmed / total requests)
            if ($response_data['total_requests'] > 0) {
                $confirmed_count = $status_counts['confirmed'] ?? 0;
                $stats['confirmation_rate'] = round(($confirmed_count / $response_data['total_requests']) * 100, 2);
            } else {
                $stats['confirmation_rate'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Get client booking statistics
     * 
     * @param int $client_id Client user ID
     * @param string $period Time period
     * @return array Booking statistics
     */
    public function getClientBookingStats($client_id, $period = 'year') {
        try {
            $stats = [];
            
            // Build date condition
            $date_condition = $this->buildDateCondition($period);
            
            // Total bookings made
            $sql = "SELECT COUNT(*) as total FROM bookings 
                    WHERE client_id = :client_id $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['client_id' => $client_id]);
            $stats['total_bookings'] = $stmt->fetch()['total'];
            
            // Bookings by status
            $sql = "SELECT booking_status, COUNT(*) as count FROM bookings 
                    WHERE client_id = :client_id $date_condition
                    GROUP BY booking_status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['client_id' => $client_id]);
            
            $status_counts = [];
            while ($row = $stmt->fetch()) {
                $status_counts[$row['booking_status']] = $row['count'];
            }
            $stats['by_status'] = $status_counts;
            
            // Total amount spent
            $sql = "SELECT COALESCE(SUM(total_amount), 0) as total_spent FROM bookings 
                    WHERE client_id = :client_id AND booking_status IN ('completed', 'confirmed') $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['client_id' => $client_id]);
            $stats['total_spent'] = $stmt->fetch()['total_spent'];
            
            // Success rate (confirmed + completed / total requests)
            if ($stats['total_bookings'] > 0) {
                $successful = ($status_counts['confirmed'] ?? 0) + ($status_counts['completed'] ?? 0);
                $stats['success_rate'] = round(($successful / $stats['total_bookings']) * 100, 2);
            } else {
                $stats['success_rate'] = 0;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Check musician availability for booking
     * 
     * @param int $musician_id Musician user ID
     * @param string $date Event date
     * @param string $start_time Start time
     * @param string $end_time End time
     * @return bool Availability status
     */
    public function checkMusicianAvailability($musician_id, $date, $start_time, $end_time = null) {
        try {
            // Check musician's availability status
            $sql = "SELECT availability_status FROM musician_profiles WHERE user_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $musician = $stmt->fetch();
            
            if (!$musician || $musician['availability_status'] === 'unavailable') {
                return false;
            }
            
            // Check for conflicting bookings
            $sql = "SELECT COUNT(*) as conflicts FROM bookings 
                    WHERE musician_id = :musician_id 
                    AND event_date = :date 
                    AND booking_status IN ('confirmed', 'in_progress')";
            
            $params = [
                'musician_id' => $musician_id,
                'date' => $date
            ];
            
            if ($end_time) {
                $sql .= " AND ((start_time <= :start_time AND end_time > :start_time) 
                          OR (start_time < :end_time AND end_time >= :end_time)
                          OR (start_time >= :start_time AND end_time <= :end_time))";
                $params['start_time'] = $start_time;
                $params['end_time'] = $end_time;
            } else {
                $sql .= " AND start_time = :start_time";
                $params['start_time'] = $start_time;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $conflicts = $stmt->fetch()['conflicts'];
            
            // Also check for conflicting gigs
            $sql = "SELECT COUNT(*) as conflicts FROM gigs 
                    WHERE musician_id = :musician_id 
                    AND gig_date = :date 
                    AND gig_status IN ('confirmed', 'in_progress')";
            
            if ($end_time) {
                $sql .= " AND ((start_time <= :start_time AND end_time > :start_time) 
                          OR (start_time < :end_time AND end_time >= :end_time)
                          OR (start_time >= :start_time AND end_time <= :end_time))";
            } else {
                $sql .= " AND start_time = :start_time";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $gig_conflicts = $stmt->fetch()['conflicts'];
            
            return ($conflicts + $gig_conflicts) == 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Reschedule booking
     * 
     * @param int $booking_id Booking ID
     * @param array $new_schedule New date and time
     * @param int $requester_id User ID of person making request
     * @param string $reason Reason for rescheduling
     * @return bool Success status
     */
    public function rescheduleBooking($booking_id, $new_schedule, $requester_id, $reason = '') {
        try {
            $this->db->beginTransaction();
            
            // Get current booking
            $booking = $this->getBookingById($booking_id);
            if (!$booking) {
                throw new Exception("Booking not found");
            }
            
            // Check authorization
            if ($requester_id != $booking['client_id'] && $requester_id != $booking['musician_id']) {
                throw new Exception("Unauthorized to reschedule this booking");
            }
            
            // Validate new schedule
            if (!$this->validateBookingData($new_schedule)) {
                throw new Exception($this->getError());
            }
            
            // Check musician availability for new time
            if (!$this->checkMusicianAvailability($booking['musician_id'], 
                                                 $new_schedule['event_date'], 
                                                 $new_schedule['start_time'], 
                                                 $new_schedule['end_time'])) {
                throw new Exception("Musician is not available at the new requested time");
            }
            
            // Update booking with new schedule
            $sql = "UPDATE bookings 
                    SET event_date = :event_date, start_time = :start_time, end_time = :end_time,
                        booking_status = :status, reschedule_reason = :reason, updated_at = NOW()
                    WHERE booking_id = :booking_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'event_date' => $new_schedule['event_date'],
                'start_time' => $new_schedule['start_time'],
                'end_time' => $new_schedule['end_time'] ?? null,
                'status' => self::STATUS_RESCHEDULED,
                'reason' => $reason,
                'booking_id' => $booking_id
            ]);
            
            if (!$result) {
                throw new Exception("Failed to reschedule booking");
            }
            
            // Update corresponding gig if exists
            $this->updateCorrespondingGig($booking_id, $new_schedule);
            
            // Log activities
            $this->logActivity($requester_id, 'booking_rescheduled', "Booking rescheduled: " . $booking['event_title']);
            $other_user = ($requester_id == $booking['client_id']) ? $booking['musician_id'] : $booking['client_id'];
            $this->logActivity($other_user, 'booking_rescheduled_by_other', "Booking has been rescheduled");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Create gig from confirmed booking
     * 
     * @param array $booking Booking data
     * @param array $additional_data Additional gig data
     * @return bool Success status
     */
    private function createGigFromBooking($booking, $additional_data = []) {
        try {
            $gig_data = [
                'title' => $booking['event_title'],
                'venue_name' => $booking['venue_name'],
                'venue_address' => $booking['venue_address'],
                'gig_date' => $booking['event_date'],
                'start_time' => $booking['start_time'],
                'end_time' => $booking['end_time'],
                'gig_type' => $booking['event_type'],
                'gig_status' => 'confirmed',
                'agreed_amount' => $booking['total_amount'],
                'audience_size' => $booking['audience_size'],
                'special_requirements' => $booking['special_requests'],
                'equipment_required' => $booking['equipment_provided'] ? 'provided_by_venue' : 'musician_brings_own',
                'performance_notes' => 'Generated from booking #' . $booking['booking_id']
            ];
            
            // Add any additional data
            $gig_data = array_merge($gig_data, $additional_data);
            
            // Create gig
            require_once 'Gig.php';
            $gig = new Gig($this->db);
            $gig_id = $gig->createGig($booking['musician_id'], $gig_data);
            
            if ($gig_id) {
                // Link booking to gig
                $sql = "UPDATE bookings SET gig_id = :gig_id WHERE booking_id = :booking_id";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['gig_id' => $gig_id, 'booking_id' => $booking['booking_id']]);
                
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Cancel corresponding gig when booking is cancelled
     * 
     * @param int $booking_id Booking ID
     * @return bool Success status
     */
    private function cancelCorrespondingGig($booking_id) {
        try {
            // Get gig ID from booking
            $sql = "SELECT gig_id, musician_id FROM bookings WHERE booking_id = :booking_id AND gig_id IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['booking_id' => $booking_id]);
            $booking = $stmt->fetch();
            
            if ($booking && $booking['gig_id']) {
                require_once 'Gig.php';
                $gig = new Gig($this->db);
                return $gig->updateGigStatus($booking['gig_id'], 'cancelled', $booking['musician_id']);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Update corresponding gig when booking is rescheduled
     * 
     * @param int $booking_id Booking ID
     * @param array $new_schedule New schedule data
     * @return bool Success status
     */
    private function updateCorrespondingGig($booking_id, $new_schedule) {
        try {
            // Get gig ID from booking
            $sql = "SELECT gig_id, musician_id FROM bookings WHERE booking_id = :booking_id AND gig_id IS NOT NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['booking_id' => $booking_id]);
            $booking = $stmt->fetch();
            
            if ($booking && $booking['gig_id']) {
                require_once 'Gig.php';
                $gig = new Gig($this->db);
                
                $gig_update = [
                    'gig_date' => $new_schedule['event_date'],
                    'start_time' => $new_schedule['start_time'],
                    'end_time' => $new_schedule['end_time'] ?? null
                ];
                
                return $gig->updateGig($booking['gig_id'], $booking['musician_id'], $gig_update);
            }
            
            return true;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate booking data
     * 
     * @param array $data Booking data to validate
     * @return bool Validation result
     */
    private function validateBookingData($data) {
        $errors = [];
        
        // Event title validation
        if (isset($data['event_title']) && (empty($data['event_title']) || strlen($data['event_title']) > 200)) {
            $errors[] = "Event title is required and must be less than 200 characters";
        }
        
        // Date validation
        if (isset($data['event_date'])) {
            if (empty($data['event_date']) || !$this->isValidDate($data['event_date'])) {
                $errors[] = "Valid event date is required";
            } elseif (strtotime($data['event_date']) < strtotime('today')) {
                $errors[] = "Event date cannot be in the past";
            }
        }
        
        // Time validation
        if (isset($data['start_time']) && !$this->isValidTime($data['start_time'])) {
            $errors[] = "Valid start time is required";
        }
        
        if (isset($data['end_time']) && !empty($data['end_time']) && !$this->isValidTime($data['end_time'])) {
            $errors[] = "End time must be valid time format";
        }
        
        // Time logic validation
        if (isset($data['start_time']) && isset($data['end_time']) && 
            !empty($data['end_time']) && $data['end_time'] <= $data['start_time']) {
            $errors[] = "End time must be after start time";
        }
        
        // Venue validation
        if (isset($data['venue_name']) && (empty($data['venue_name']) || strlen($data['venue_name']) > 200)) {
            $errors[] = "Venue name is required and must be less than 200 characters";
        }
        
        // Event type validation
        if (isset($data['event_type'])) {
            $valid_types = [
                self::EVENT_WEDDING, self::EVENT_PARTY, self::EVENT_CORPORATE,
                self::EVENT_CONCERT, self::EVENT_FESTIVAL, self::EVENT_BAR_CLUB,
                self::EVENT_RESTAURANT, self::EVENT_PRIVATE, self::EVENT_CHARITY,
                self::EVENT_OTHER
            ];
            if (!in_array($data['event_type'], $valid_types)) {
                $errors[] = "Invalid event type";
            }
        }
        
        // Amount validation
        if (isset($data['total_amount']) && !empty($data['total_amount'])) {
            if (!is_numeric($data['total_amount']) || $data['total_amount'] < 0) {
                $errors[] = "Total amount must be a valid positive number";
            }
        }
        
        // Audience size validation
        if (isset($data['audience_size']) && !empty($data['audience_size'])) {
            if (!is_numeric($data['audience_size']) || $data['audience_size'] < 0) {
                $errors[] = "Audience size must be a valid positive number";
            }
        }
        
        if (!empty($errors)) {
            $this->setError(implode(', ', $errors));
            return false;
        }
        
        return true;
    }
    
    /**
     * Check if status transition is valid
     * 
     * @param string $current_status Current status
     * @param string $new_status New status
     * @return bool Valid transition status
     */
    private function isValidStatusTransition($current_status, $new_status) {
        $valid_transitions = [
            self::STATUS_PENDING => [self::STATUS_CONFIRMED, self::STATUS_REJECTED, self::STATUS_CANCELLED],
            self::STATUS_CONFIRMED => [self::STATUS_IN_PROGRESS, self::STATUS_CANCELLED, self::STATUS_RESCHEDULED],
            self::STATUS_IN_PROGRESS => [self::STATUS_COMPLETED],
            self::STATUS_RESCHEDULED => [self::STATUS_CONFIRMED, self::STATUS_CANCELLED],
            self::STATUS_REJECTED => [], // Final state
            self::STATUS_CANCELLED => [], // Final state
            self::STATUS_COMPLETED => [] // Final state
        ];
        
        return isset($valid_transitions[$current_status]) && 
               in_array($new_status, $valid_transitions[$current_status]);
    }
    
    /**
     * Check if musician account is active
     * 
     * @param int $musician_id Musician user ID
     * @return bool Active status
     */
    private function isMusicianActive($musician_id) {
        try {
            $sql = "SELECT account_status FROM users WHERE user_id = :user_id AND user_type = 'musician'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $musician_id]);
            $user = $stmt->fetch();
            
            return $user && $user['account_status'] === 'active';
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verify musician booking ownership
     * 
     * @param int $booking_id Booking ID
     * @param int $musician_id Musician user ID
     * @return bool Ownership status
     */
    private function verifyMusicianBooking($booking_id, $musician_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM bookings 
                    WHERE booking_id = :booking_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['booking_id' => $booking_id, 'musician_id' => $musician_id]);
            
            return $stmt->fetch()['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verify client booking ownership
     * 
     * @param int $booking_id Booking ID
     * @param int $client_id Client user ID
     * @return bool Ownership status
     */
    private function verifyClientBooking($booking_id, $client_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM bookings 
                    WHERE booking_id = :booking_id AND client_id = :client_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['booking_id' => $booking_id, 'client_id' => $client_id]);
            
            return $stmt->fetch()['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Prepare booking data for database insertion
     * 
     * @param int $client_id Client user ID
     * @param int $musician_id Musician user ID
     * @param array $booking_data Raw booking data
     * @return array Prepared data array
     */
    private function prepareBookingData($client_id, $musician_id, $booking_data) {
        return [
            'client_id' => $client_id,
            'musician_id' => $musician_id,
            'event_title' => $booking_data['event_title'],
            'event_date' => $booking_data['event_date'],
            'start_time' => $booking_data['start_time'],
            'end_time' => $booking_data['end_time'] ?? null,
            'venue_name' => $booking_data['venue_name'],
            'venue_address' => $booking_data['venue_address'] ?? null,
            'event_type' => $booking_data['event_type'] ?? self::EVENT_OTHER,
            'audience_size' => $booking_data['audience_size'] ?? null,
            'music_genres_requested' => isset($booking_data['music_genres_requested']) ? 
                (is_array($booking_data['music_genres_requested']) ? json_encode($booking_data['music_genres_requested']) : $booking_data['music_genres_requested']) : null,
            'special_requests' => $booking_data['special_requests'] ?? null,
            'equipment_provided' => $booking_data['equipment_provided'] ?? 0,
            'total_amount' => $booking_data['total_amount'] ?? null,
            'booking_status' => self::STATUS_PENDING,
            'payment_terms' => $booking_data['payment_terms'] ?? null,
            'contract_terms' => $booking_data['contract_terms'] ?? null
        ];
    }
    
    /**
     * Build ORDER BY clause for booking queries
     * 
     * @param string $sort_by Sort criteria
     * @return string ORDER BY clause
     */
    private function buildBookingOrderBy($sort_by) {
        switch ($sort_by) {
            case 'date_asc':
                return "ORDER BY b.event_date ASC, b.start_time ASC";
            case 'date_desc':
                return "ORDER BY b.event_date DESC, b.start_time DESC";
            case 'title_asc':
                return "ORDER BY b.event_title ASC";
            case 'title_desc':
                return "ORDER BY b.event_title DESC";
            case 'status_asc':
                return "ORDER BY b.booking_status ASC";
            case 'status_desc':
                return "ORDER BY b.booking_status DESC";
            case 'amount_asc':
                return "ORDER BY b.total_amount ASC";
            case 'amount_desc':
                return "ORDER BY b.total_amount DESC";
            case 'created_desc':
                return "ORDER BY b.created_at DESC";
            case 'created_asc':
                return "ORDER BY b.created_at ASC";
            default:
                return "ORDER BY b.event_date DESC, b.created_at DESC";
        }
    }
    
    /**
     * Build date condition for statistics queries
     * 
     * @param string $period Time period
     * @return string Date condition SQL
     */
    private function buildDateCondition($period) {
        switch ($period) {
            case 'month':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
            case 'year':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
            case 'week':
                return "AND created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
            case 'all':
            default:
                return "";
        }
    }
    
    /**
     * Validate date format
     * 
     * @param string $date Date string
     * @return bool Valid date status
     */
    private function isValidDate($date) {
        $d = DateTime::createFromFormat('Y-m-d', $date);
        return $d && $d->format('Y-m-d') === $date;
    }
    
    /**
     * Validate time format
     * 
     * @param string $time Time string
     * @return bool Valid time status
     */
    private function isValidTime($time) {
        $t = DateTime::createFromFormat('H:i:s', $time);
        if (!$t) {
            $t = DateTime::createFromFormat('H:i', $time);
        }
        return $t !== false;
    }
    
    /**
     * Validate required fields
     * 
     * @param array $data Data array
     * @param array $required_fields Required field names
     * @return bool Validation result
     */
    private function validateRequiredFields($data, $required_fields) {
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty(trim($data[$field]))) {
                return false;
            }
        }
        return true;
    }
    
    /**
     * Log activity
     * 
     * @param int $user_id User ID
     * @param string $activity_type Activity type
     * @param string $description Activity description
     */
    private function logActivity($user_id, $activity_type, $description) {
        try {
            $sql = "INSERT INTO activity_logs (user_id, activity_type, description, ip_address, created_at) 
                    VALUES (:user_id, :activity_type, :description, :ip_address, NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'user_id' => $user_id,
                'activity_type' => $activity_type,
                'description' => $description,
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
        } catch (Exception $e) {
            // Silently fail for logging
        }
    }
    
    /**
     * Get booking event types array
     * 
     * @return array Event types with labels
     */
    public static function getEventTypes() {
        return [
            self::EVENT_WEDDING => 'Wedding',
            self::EVENT_PARTY => 'Private Party',
            self::EVENT_CORPORATE => 'Corporate Event',
            self::EVENT_CONCERT => 'Concert',
            self::EVENT_FESTIVAL => 'Festival',
            self::EVENT_BAR_CLUB => 'Bar/Club',
            self::EVENT_RESTAURANT => 'Restaurant',
            self::EVENT_PRIVATE => 'Private Event',
            self::EVENT_CHARITY => 'Charity Event',
            self::EVENT_OTHER => 'Other'
        ];
    }
    
    /**
     * Get booking statuses array
     * 
     * @return array Booking statuses with labels
     */
    public static function getBookingStatuses() {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_REJECTED => 'Rejected',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_RESCHEDULED => 'Rescheduled'
        ];
    }
    
    /**
     * Get status color for UI display
     * 
     * @param string $status Booking status
     * @return string CSS color class
     */
    public static function getStatusColor($status) {
        switch ($status) {
            case self::STATUS_PENDING:
                return 'warning';
            case self::STATUS_CONFIRMED:
                return 'success';
            case self::STATUS_REJECTED:
                return 'danger';
            case self::STATUS_CANCELLED:
                return 'secondary';
            case self::STATUS_COMPLETED:
                return 'primary';
            case self::STATUS_IN_PROGRESS:
                return 'info';
            case self::STATUS_RESCHEDULED:
                return 'warning';
            default:
                return 'secondary';
        }
    }
    
    /**
     * Get upcoming bookings for musician (next 7 days)
     * 
     * @param int $musician_id Musician user ID
     * @param int $limit Number of bookings to return
     * @return array Upcoming bookings
     */
    public function getUpcomingBookings($musician_id, $limit = 5) {
        try {
            $sql = "SELECT b.*, c.username as client_username, cp.location as client_location
                    FROM bookings b
                    LEFT JOIN users c ON b.client_id = c.user_id
                    LEFT JOIN user_profiles cp ON b.client_id = cp.user_id
                    WHERE b.musician_id = :musician_id 
                    AND b.event_date >= CURDATE() 
                    AND b.event_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                    AND b.booking_status IN ('confirmed', 'in_progress')
                    ORDER BY b.event_date ASC, b.start_time ASC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':musician_id', $musician_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $bookings = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($bookings as &$booking) {
                if (!empty($booking['music_genres_requested'])) {
                    $booking['music_genres_requested'] = json_decode($booking['music_genres_requested'], true);
                }
            }
            
            return $bookings;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Get calendar bookings for musician
     * 
     * @param int $musician_id Musician user ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Calendar events data
     */
    public function getCalendarBookings($musician_id, $start_date, $end_date) {
        try {
            $sql = "SELECT booking_id, event_title, venue_name, event_date, start_time, end_time, 
                           event_type, booking_status, total_amount, client_id
                    FROM bookings 
                    WHERE musician_id = :musician_id 
                    AND event_date BETWEEN :start_date AND :end_date
                    AND booking_status NOT IN ('rejected', 'cancelled')
                    ORDER BY event_date, start_time";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'musician_id' => $musician_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
            
            $events = [];
            while ($row = $stmt->fetch()) {
                $events[] = [
                    'id' => 'booking-' . $row['booking_id'],
                    'title' => $row['event_title'],
                    'start' => $row['event_date'] . 'T' . $row['start_time'],
                    'end' => $row['event_date'] . 'T' . ($row['end_time'] ?? $row['start_time']),
                    'venue' => $row['venue_name'],
                    'type' => $row['event_type'],
                    'status' => $row['booking_status'],
                    'amount' => $row['total_amount'],
                    'booking_id' => $row['booking_id'],
                    'className' => $this->getBookingStatusClassName($row['booking_status'])
                ];
            }
            
            return $events;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Get CSS class name for booking status
     * 
     * @param string $status Booking status
     * @return string CSS class name
     */
    private function getBookingStatusClassName($status) {
        switch ($status) {
            case self::STATUS_CONFIRMED:
                return 'booking-confirmed';
            case self::STATUS_IN_PROGRESS:
                return 'booking-in-progress';
            case self::STATUS_COMPLETED:
                return 'booking-completed';
            case self::STATUS_CANCELLED:
                return 'booking-cancelled';
            case self::STATUS_REJECTED:
                return 'booking-rejected';
            case self::STATUS_RESCHEDULED:
                return 'booking-rescheduled';
            case self::STATUS_PENDING:
            default:
                return 'booking-pending';
        }
    }
    
    // Getters and Setters
    public function getBookingId() { return $this->booking_id; }
    public function getClientId() { return $this->client_id; }
    public function getMusicianId() { return $this->musician_id; }
    public function getEventTitle() { return $this->event_title; }
    public function getEventDate() { return $this->event_date; }
    public function getStartTime() { return $this->start_time; }
    public function getEndTime() { return $this->end_time; }
    public function getVenueName() { return $this->venue_name; }
    public function getVenueAddress() { return $this->venue_address; }
    public function getEventType() { return $this->event_type; }
    public function getAudienceSize() { return $this->audience_size; }
    public function getMusicGenresRequested() { return $this->music_genres_requested; }
    public function getSpecialRequests() { return $this->special_requests; }
    public function getEquipmentProvided() { return $this->equipment_provided; }
    public function getTotalAmount() { return $this->total_amount; }
    public function getBookingStatus() { return $this->booking_status; }
    public function getPaymentTerms() { return $this->payment_terms; }
    public function getContractTerms() { return $this->contract_terms; }
    public function getError() { return $this->error; }
    
    public function setBookingId($booking_id) { $this->booking_id = $booking_id; }
    public function setClientId($client_id) { $this->client_id = $client_id; }
    public function setMusicianId($musician_id) { $this->musician_id = $musician_id; }
    public function setEventTitle($event_title) { $this->event_title = $event_title; }
    public function setEventDate($event_date) { $this->event_date = $event_date; }
    public function setStartTime($start_time) { $this->start_time = $start_time; }
    public function setEndTime($end_time) { $this->end_time = $end_time; }
    public function setVenueName($venue_name) { $this->venue_name = $venue_name; }
    public function setVenueAddress($venue_address) { $this->venue_address = $venue_address; }
    public function setEventType($event_type) { $this->event_type = $event_type; }
    public function setAudienceSize($audience_size) { $this->audience_size = $audience_size; }
    public function setMusicGenresRequested($music_genres_requested) { $this->music_genres_requested = $music_genres_requested; }
    public function setSpecialRequests($special_requests) { $this->special_requests = $special_requests; }
    public function setEquipmentProvided($equipment_provided) { $this->equipment_provided = $equipment_provided; }
    public function setTotalAmount($total_amount) { $this->total_amount = $total_amount; }
    public function setBookingStatus($booking_status) { $this->booking_status = $booking_status; }
    public function setPaymentTerms($payment_terms) { $this->payment_terms = $payment_terms; }
    public function setContractTerms($contract_terms) { $this->contract_terms = $contract_terms; }
    public function setError($error) { $this->error = $error; }
}
?> 
