<?php
/**
 * Client Class - Extended User Management for Clients
 * 
 * Handles client-specific functionality including profile management,
 * booking requests, favorites management, and event planning
 * 
 * @author Subham Shrestha
 * @version 1.0
 * @since 2025
 */

require_once 'User.php';
require_once '../config/database.php';

class Client extends User {
    
    // Client-specific properties
    private $client_id;
    private $organization_name;
    private $organization_type;
    private $preferred_genres;
    private $typical_event_types;
    private $typical_budget_range;
    private $event_frequency;
    private $total_events_organized;
    private $preferred_contact_method;
    
    /**
     * Constructor - Initialize client object
     */
    public function __construct($db = null) {
        parent::__construct($db);
        $this->initializeDefaults();
    }
    
    /**
     * Initialize default values for client properties
     */
    private function initializeDefaults() {
        $this->organization_type = 'individual';
        $this->event_frequency = 'occasionally';
        $this->total_events_organized = 0;
        $this->preferred_contact_method = 'email';
    }
    
    /**
     * Create client profile after user registration
     * 
     * @param int $user_id User ID from users table
     * @param array $profile_data Client profile data
     * @return bool Success status
     */
    public function createClientProfile($user_id, $profile_data) {
        try {
            $this->db->beginTransaction();
            
            // Create basic user profile first
            $basic_profile = [
                'bio' => $profile_data['bio'] ?? '',
                'location' => $profile_data['location'] ?? '',
                'phone' => $profile_data['phone'] ?? '',
                'address' => $profile_data['address'] ?? '',
                'social_media' => json_encode($profile_data['social_media'] ?? [])
            ];
            
            if (!$this->createUserProfile($user_id, $basic_profile)) {
                throw new Exception("Failed to create basic user profile");
            }
            
            // Prepare client-specific data (all optional for clients)
            $client_data = $this->prepareClientData($user_id, $profile_data);
            
            // Insert client profile (using user_profiles table with client-specific fields)
            $sql = "UPDATE user_profiles SET 
                        organization_name = :organization_name,
                        organization_type = :organization_type,
                        preferred_genres = :preferred_genres,
                        typical_event_types = :typical_event_types,
                        typical_budget_range = :typical_budget_range,
                        event_frequency = :event_frequency,
                        preferred_contact_method = :preferred_contact_method,
                        updated_at = NOW()
                    WHERE user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($client_data);
            
            if (!$result) {
                throw new Exception("Failed to create client profile");
            }
            
            // Log activity
            $this->logActivity($user_id, ACTIVITY_PROFILE_UPDATED, "Client profile created");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Update client profile
     * 
     * @param int $user_id User ID
     * @param array $profile_data Updated profile data
     * @return bool Success status
     */
    public function updateClientProfile($user_id, $profile_data) {
        try {
            $this->db->beginTransaction();
            
            // Update basic user profile fields
            $basic_fields = [];
            $basic_params = ['user_id' => $user_id];
            
            $basic_field_names = ['bio', 'location', 'phone', 'address'];
            
            foreach ($basic_field_names as $field) {
                if (isset($profile_data[$field])) {
                    $basic_fields[] = "$field = :$field";
                    $basic_params[$field] = $profile_data[$field];
                }
            }
            
            // Handle social_media JSON field
            if (isset($profile_data['social_media'])) {
                $basic_fields[] = "social_media = :social_media";
                $basic_params['social_media'] = is_array($profile_data['social_media']) ? 
                    json_encode($profile_data['social_media']) : $profile_data['social_media'];
            }
            
            // Update client-specific fields
            $client_fields = [];
            $client_params = ['user_id' => $user_id];
            
            $client_field_names = [
                'organization_name', 'organization_type', 'typical_budget_range',
                'event_frequency', 'preferred_contact_method'
            ];
            
            foreach ($client_field_names as $field) {
                if (isset($profile_data[$field])) {
                    $client_fields[] = "$field = :$field";
                    $client_params[$field] = $profile_data[$field];
                }
            }
            
            // Handle JSON fields for client
            if (isset($profile_data['preferred_genres'])) {
                $client_fields[] = "preferred_genres = :preferred_genres";
                $client_params['preferred_genres'] = is_array($profile_data['preferred_genres']) ? 
                    json_encode($profile_data['preferred_genres']) : $profile_data['preferred_genres'];
            }
            
            if (isset($profile_data['typical_event_types'])) {
                $client_fields[] = "typical_event_types = :typical_event_types";
                $client_params['typical_event_types'] = is_array($profile_data['typical_event_types']) ? 
                    json_encode($profile_data['typical_event_types']) : $profile_data['typical_event_types'];
            }
            
            // Combine all fields for single update
            $all_fields = array_merge($basic_fields, $client_fields);
            $all_params = array_merge($basic_params, $client_params);
            
            if (!empty($all_fields)) {
                $sql = "UPDATE user_profiles SET " . implode(', ', $all_fields) . 
                       ", updated_at = NOW() WHERE user_id = :user_id";
                
                $stmt = $this->db->prepare($sql);
                if (!$stmt->execute($all_params)) {
                    throw new Exception("Failed to update client profile");
                }
            }
            
            // Log activity
            $this->logActivity($user_id, ACTIVITY_PROFILE_UPDATED, "Client profile updated");
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get complete client profile with user data
     * 
     * @param int $user_id User ID
     * @return array|false Client profile data or false
     */
    public function getClientProfile($user_id) {
        try {
            $sql = "SELECT u.*, up.* 
                    FROM users u
                    LEFT JOIN user_profiles up ON u.user_id = up.user_id
                    WHERE u.user_id = :user_id AND u.user_type = 'client'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            
            $profile = $stmt->fetch();
            
            if ($profile) {
                // Decode JSON fields
                $json_fields = ['social_media', 'preferred_genres', 'typical_event_types'];
                foreach ($json_fields as $field) {
                    if (isset($profile[$field]) && !empty($profile[$field])) {
                        $profile[$field] = json_decode($profile[$field], true);
                    }
                }
                
                return $profile;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client dashboard statistics
     * 
     * @param int $user_id Client user ID
     * @return array Dashboard statistics
     */
    public function getDashboardStats($user_id) {
        try {
            $stats = [];
            
            // Get basic client info
            $profile = $this->getClientProfile($user_id);
            $stats['profile'] = $profile;
            
            // Total bookings made
            $sql = "SELECT COUNT(*) as total FROM bookings WHERE client_id = :user_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['total_bookings'] = $stmt->fetch()['total'];
            
            // Pending bookings
            $sql = "SELECT COUNT(*) as total FROM bookings 
                    WHERE client_id = :user_id AND booking_status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['pending_bookings'] = $stmt->fetch()['total'];
            
            // Confirmed bookings
            $sql = "SELECT COUNT(*) as total FROM bookings 
                    WHERE client_id = :user_id AND booking_status = 'confirmed'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['confirmed_bookings'] = $stmt->fetch()['total'];
            
            // Completed events
            $sql = "SELECT COUNT(*) as total FROM bookings 
                    WHERE client_id = :user_id AND booking_status = 'completed'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['completed_events'] = $stmt->fetch()['total'];
            
            // Total spent
            $sql = "SELECT COALESCE(SUM(p.amount), 0) as total_spent
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.booking_id
                    WHERE b.client_id = :user_id AND p.payment_status = 'completed'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['total_spent'] = $stmt->fetch()['total_spent'];
            
            // Pending payments
            $sql = "SELECT COALESCE(SUM(p.amount), 0) as pending_payments
                    FROM payments p
                    JOIN bookings b ON p.booking_id = b.booking_id
                    WHERE b.client_id = :user_id AND p.payment_status = 'pending'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['pending_payments'] = $stmt->fetch()['pending_payments'];
            
            // Recent bookings (last 5)
            $sql = "SELECT b.*, mp.stage_name, up.location as musician_location
                    FROM bookings b
                    JOIN musician_profiles mp ON b.musician_id = mp.user_id
                    JOIN user_profiles up ON b.musician_id = up.user_id
                    WHERE b.client_id = :user_id
                    ORDER BY b.created_at DESC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['recent_bookings'] = $stmt->fetchAll();
            
            // Upcoming events
            $sql = "SELECT b.*, mp.stage_name, up.location as musician_location
                    FROM bookings b
                    JOIN musician_profiles mp ON b.musician_id = mp.user_id
                    JOIN user_profiles up ON b.musician_id = up.user_id
                    WHERE b.client_id = :user_id AND b.event_date >= CURDATE() 
                    AND b.booking_status IN ('confirmed', 'pending')
                    ORDER BY b.event_date ASC
                    LIMIT 5";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['user_id' => $user_id]);
            $stats['upcoming_events'] = $stmt->fetchAll();
            
            // Favorite musicians count
            $stats['favorite_musicians'] = $this->getFavoriteMusicianCount($user_id);
            
            return $stats;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Add musician to favorites
     * 
     * @param int $client_id Client user ID
     * @param int $musician_id Musician user ID
     * @return bool Success status
     */
    public function addToFavorites($client_id, $musician_id) {
        try {
            // Check if already in favorites
            $sql = "SELECT COUNT(*) as count FROM client_favorites 
                    WHERE client_id = :client_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['client_id' => $client_id, 'musician_id' => $musician_id]);
            
            if ($stmt->fetch()['count'] > 0) {
                $this->setError("Musician is already in your favorites");
                return false;
            }
            
            // Add to favorites
            $sql = "INSERT INTO client_favorites (client_id, musician_id, created_at) 
                    VALUES (:client_id, :musician_id, NOW())";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['client_id' => $client_id, 'musician_id' => $musician_id]);
            
            if ($result) {
                $this->logActivity($client_id, 'favorite_added', "Added musician to favorites");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Remove musician from favorites
     * 
     * @param int $client_id Client user ID
     * @param int $musician_id Musician user ID
     * @return bool Success status
     */
    public function removeFromFavorites($client_id, $musician_id) {
        try {
            $sql = "DELETE FROM client_favorites 
                    WHERE client_id = :client_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['client_id' => $client_id, 'musician_id' => $musician_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logActivity($client_id, 'favorite_removed', "Removed musician from favorites");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get client's favorite musicians
     * 
     * @param int $client_id Client user ID
     * @param int $page Page number for pagination
     * @param int $per_page Items per page
     * @return array Favorite musicians with pagination
     */
    public function getFavoriteMusicians($client_id, $page = 1, $per_page = 12) {
        try {
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM client_favorites cf
                          JOIN musician_full_profiles mfp ON cf.musician_id = mfp.user_id
                          WHERE cf.client_id = :client_id AND mfp.account_status = 'active'";
            $count_stmt = $this->db->prepare($count_sql);
            $count_stmt->execute(['client_id' => $client_id]);
            $total_count = $count_stmt->fetch()['total'];
            
            // Get paginated results
            $offset = ($page - 1) * $per_page;
            
            $sql = "SELECT mfp.*, cf.created_at as favorited_at
                    FROM client_favorites cf
                    JOIN musician_full_profiles mfp ON cf.musician_id = mfp.user_id
                    WHERE cf.client_id = :client_id AND mfp.account_status = 'active'
                    ORDER BY cf.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':client_id', $client_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $per_page, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $favorites = $stmt->fetchAll();
            
            // Decode JSON fields
            foreach ($favorites as &$favorite) {
                $json_fields = ['genres', 'instruments', 'portfolio_images'];
                foreach ($json_fields as $field) {
                    if (isset($favorite[$field]) && !empty($favorite[$field])) {
                        $favorite[$field] = json_decode($favorite[$field], true);
                    }
                }
            }
            
            return [
                'favorites' => $favorites,
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
            return ['favorites' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get favorite musicians count
     * 
     * @param int $client_id Client user ID
     * @return int Number of favorite musicians
     */
    public function getFavoriteMusicianCount($client_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM client_favorites cf
                    JOIN users u ON cf.musician_id = u.user_id
                    WHERE cf.client_id = :client_id AND u.account_status = 'active'";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['client_id' => $client_id]);
            
            return $stmt->fetch()['count'];
            
        } catch (Exception $e) {
            return 0;
        }
    }
    
    /**
     * Check if musician is in client's favorites
     * 
     * @param int $client_id Client user ID
     * @param int $musician_id Musician user ID
     * @return bool Is favorite status
     */
    public function isFavoriteMusician($client_id, $musician_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM client_favorites 
                    WHERE client_id = :client_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['client_id' => $client_id, 'musician_id' => $musician_id]);
            
            return $stmt->fetch()['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get client's booking history with filtering
     * 
     * @param int $client_id Client user ID
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Booking history with pagination
     */
    public function getBookingHistory($client_id, $filters = [], $page = 1, $per_page = 10) {
        try {
            $where_conditions = ["b.client_id = :client_id"];
            $params = ['client_id' => $client_id];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $where_conditions[] = "b.booking_status = :status";
                $params['status'] = $filters['status'];
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
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total 
                          FROM bookings b
                          JOIN musician_profiles mp ON b.musician_id = mp.user_id
                          WHERE $where_clause";
            $count_stmt = $this->db->prepare($count_sql);
            $count_stmt->execute($params);
            $total_count = $count_stmt->fetch()['total'];
            
            // Get paginated results
            $offset = ($page - 1) * $per_page;
            
            $sql = "SELECT b.*, mp.stage_name, up.location as musician_location,
                           p.amount as payment_amount, p.payment_status
                    FROM bookings b
                    JOIN musician_profiles mp ON b.musician_id = mp.user_id
                    JOIN user_profiles up ON b.musician_id = up.user_id
                    LEFT JOIN payments p ON b.booking_id = p.booking_id
                    WHERE $where_clause
                    ORDER BY b.event_date DESC, b.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $per_page;
            $params['offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $bookings = $stmt->fetchAll();
            
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
     * Validate client profile data
     * 
     * @param array $data Profile data to validate
     * @return bool Validation result
     */
    public function validateClientProfile($data) {
        $errors = [];
        
        // Organization type validation
        if (!empty($data['organization_type'])) {
            $valid_types = ['individual', 'company', 'non-profit', 'government', 'educational'];
            if (!in_array($data['organization_type'], $valid_types)) {
                $errors[] = "Invalid organization type";
            }
        }
        
        // Budget range validation
        if (!empty($data['typical_budget_range'])) {
            $valid_ranges = ['under-5000', '5000-15000', '15000-30000', '30000-50000', 'above-50000'];
            if (!in_array($data['typical_budget_range'], $valid_ranges)) {
                $errors[] = "Invalid budget range";
            }
        }
        
        // Event frequency validation
        if (!empty($data['event_frequency'])) {
            $valid_frequencies = ['rarely', 'occasionally', 'monthly', 'weekly', 'daily'];
            if (!in_array($data['event_frequency'], $valid_frequencies)) {
                $errors[] = "Invalid event frequency";
            }
        }
        
        // Contact method validation
        if (!empty($data['preferred_contact_method'])) {
            $valid_methods = ['email', 'phone', 'sms', 'whatsapp'];
            if (!in_array($data['preferred_contact_method'], $valid_methods)) {
                $errors[] = "Invalid contact method";
            }
        }
        
        // Phone validation if provided
        if (!empty($data['phone'])) {
            if (!preg_match('/^[0-9+\-\s\(\)]{10,15}$/', $data['phone'])) {
                $errors[] = "Invalid phone number format";
            }
        }
        
        if (!empty($errors)) {
            $this->setError(implode(', ', $errors));
            return false;
        }
        
        return true;
    }
    
    /**
     * Prepare client data for database insertion
     * 
     * @param int $user_id User ID
     * @param array $profile_data Raw profile data
     * @return array Prepared data array
     */
    private function prepareClientData($user_id, $profile_data) {
        return [
            'user_id' => $user_id,
            'organization_name' => $profile_data['organization_name'] ?? null,
            'organization_type' => $profile_data['organization_type'] ?? $this->organization_type,
            'preferred_genres' => isset($profile_data['preferred_genres']) ? 
                (is_array($profile_data['preferred_genres']) ? json_encode($profile_data['preferred_genres']) : $profile_data['preferred_genres']) : null,
            'typical_event_types' => isset($profile_data['typical_event_types']) ? 
                (is_array($profile_data['typical_event_types']) ? json_encode($profile_data['typical_event_types']) : $profile_data['typical_event_types']) : null,
            'typical_budget_range' => $profile_data['typical_budget_range'] ?? null,
            'event_frequency' => $profile_data['event_frequency'] ?? $this->event_frequency,
            'preferred_contact_method' => $profile_data['preferred_contact_method'] ?? $this->preferred_contact_method
        ];
    }
    
    // Getters and Setters
    public function getClientId() { return $this->client_id; }
    public function getOrganizationName() { return $this->organization_name; }
    public function getOrganizationType() { return $this->organization_type; }
    public function getPreferredGenres() { return $this->preferred_genres; }
    public function getTypicalEventTypes() { return $this->typical_event_types; }
    public function getTypicalBudgetRange() { return $this->typical_budget_range; }
    public function getEventFrequency() { return $this->event_frequency; }
    public function getTotalEventsOrganized() { return $this->total_events_organized; }
    public function getPreferredContactMethod() { return $this->preferred_contact_method; }
    
    public function setOrganizationName($name) { $this->organization_name = $name; }
    public function setOrganizationType($type) { $this->organization_type = $type; }
    public function setPreferredGenres($genres) { $this->preferred_genres = $genres; }
    public function setTypicalEventTypes($types) { $this->typical_event_types = $types; }
    public function setTypicalBudgetRange($range) { $this->typical_budget_range = $range; }
    public function setEventFrequency($frequency) { $this->event_frequency = $frequency; }
    public function setPreferredContactMethod($method) { $this->preferred_contact_method = $method; }
}
?>