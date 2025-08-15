<?php
/**
 * Gig Class - Gig Management System for Musicians
 * 
 * Handles musician gig records including creation, updating, scheduling,
 * status tracking, venue management, and performance analytics
 * 
 * @author Subham Shrestha
 * @version 1.0
 * @since 2025
 */

require_once __DIR__ . '/../config/database.php';
// Ensure the base Database class is available for legacy usages
require_once __DIR__ . '/Database.php'

class Gig {
    
    private $db;
    private $error;
    
    // Gig properties
    private $gig_id;
    private $musician_id;
    private $title;
    private $venue_name;
    private $venue_address;
    private $venue_contact;
    private $gig_date;
    private $start_time;
    private $end_time;
    private $gig_type;
    private $gig_status;
    private $agreed_amount;
    private $payment_terms;
    private $equipment_required;
    private $special_requirements;
    private $audience_size;
    private $performance_notes;
    
    // Constants for gig types
    const GIG_TYPE_WEDDING = 'wedding';
    const GIG_TYPE_PARTY = 'party';
    const GIG_TYPE_CORPORATE = 'corporate';
    const GIG_TYPE_CONCERT = 'concert';
    const GIG_TYPE_FESTIVAL = 'festival';
    const GIG_TYPE_BAR_CLUB = 'bar_club';
    const GIG_TYPE_RESTAURANT = 'restaurant';
    const GIG_TYPE_PRIVATE = 'private_event';
    const GIG_TYPE_CHARITY = 'charity';
    const GIG_TYPE_OTHER = 'other';
    
    // Constants for gig status
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_POSTPONED = 'postponed';
    
    /**
     * Constructor - Initialize gig object
     */
    public function __construct($db = null) {
        if ($db === null) {
             $this->db = getDB();
        } else {
            $this->db = $db;
        }
    }
    
    /**
     * Create new gig
     * 
     * @param int $musician_id Musician user ID
     * @param array $gig_data Gig information
     * @return int|false Gig ID on success, false on failure
     */
    public function createGig($musician_id, $gig_data) {
        try {
            $this->db->beginTransaction();
            
            // Validate required fields
            $required_fields = ['title', 'venue_name', 'gig_date', 'start_time'];
            if (!$this->validateRequiredFields($gig_data, $required_fields)) {
                throw new Exception("Missing required gig fields");
            }
            
            // Validate gig data
            if (!$this->validateGigData($gig_data)) {
                throw new Exception($this->getError());
            }
            
            // Check for scheduling conflicts
            if (!$this->checkSchedulingConflicts($musician_id, $gig_data['gig_date'], $gig_data['start_time'], $gig_data['end_time'])) {
                throw new Exception("Scheduling conflict detected. You have another gig at this time.");
            }
            
            // Prepare gig data
            $data = $this->prepareGigData($musician_id, $gig_data);
            
            // Insert gig
            $sql = "INSERT INTO gigs (
                        musician_id, title, venue_name, venue_address, venue_contact,
                        gig_date, start_time, end_time, gig_type, gig_status,
                        agreed_amount, payment_terms, equipment_required, 
                        special_requirements, audience_size, performance_notes,
                        created_at
                    ) VALUES (
                        :musician_id, :title, :venue_name, :venue_address, :venue_contact,
                        :gig_date, :start_time, :end_time, :gig_type, :gig_status,
                        :agreed_amount, :payment_terms, :equipment_required,
                        :special_requirements, :audience_size, :performance_notes,
                        NOW()
                    )";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($data);
            
            if (!$result) {
                throw new Exception("Failed to create gig");
            }
            
            $gig_id = $this->db->lastInsertId();
            
            // Log activity
            $this->logActivity($musician_id, ACTIVITY_GIG_CREATED, "New gig created: " . $gig_data['title']);
            
            $this->db->commit();
            return $gig_id;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Update gig information
     * 
     * @param int $gig_id Gig ID
     * @param int $musician_id Musician user ID (for authorization)
     * @param array $gig_data Updated gig data
     * @return bool Success status
     */
    public function updateGig($gig_id, $musician_id, $gig_data) {
        try {
            $this->db->beginTransaction();
            
            // Verify gig ownership
            if (!$this->verifyGigOwnership($gig_id, $musician_id)) {
                throw new Exception("Unauthorized: You can only update your own gigs");
            }
            
            // Validate gig data
            if (!$this->validateGigData($gig_data)) {
                throw new Exception($this->getError());
            }
            
            // Check for scheduling conflicts (excluding current gig)
            if (isset($gig_data['gig_date']) || isset($gig_data['start_time']) || isset($gig_data['end_time'])) {
                $current_gig = $this->getGigById($gig_id);
                $check_date = $gig_data['gig_date'] ?? $current_gig['gig_date'];
                $check_start = $gig_data['start_time'] ?? $current_gig['start_time'];
                $check_end = $gig_data['end_time'] ?? $current_gig['end_time'];
                
                if (!$this->checkSchedulingConflicts($musician_id, $check_date, $check_start, $check_end, $gig_id)) {
                    throw new Exception("Scheduling conflict detected with another gig");
                }
            }
            
            // Build update query
            $update_fields = [];
            $params = ['gig_id' => $gig_id];
            
            $updatable_fields = [
                'title', 'venue_name', 'venue_address', 'venue_contact',
                'gig_date', 'start_time', 'end_time', 'gig_type', 'gig_status',
                'agreed_amount', 'payment_terms', 'equipment_required',
                'special_requirements', 'audience_size', 'performance_notes'
            ];
            
            foreach ($updatable_fields as $field) {
                if (isset($gig_data[$field])) {
                    $update_fields[] = "$field = :$field";
                    $params[$field] = $gig_data[$field];
                }
            }
            
            if (empty($update_fields)) {
                throw new Exception("No data to update");
            }
            
            $sql = "UPDATE gigs SET " . implode(', ', $update_fields) . 
                   ", updated_at = NOW() WHERE gig_id = :gig_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new Exception("Failed to update gig");
            }
            
            // Log activity
            $this->logActivity($musician_id, ACTIVITY_PROFILE_UPDATED, "Gig updated: " . ($gig_data['title'] ?? 'ID ' . $gig_id));
            
            $this->db->commit();
            return true;
            
        } catch (Exception $e) {
            $this->db->rollback();
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete gig
     * 
     * @param int $gig_id Gig ID
     * @param int $musician_id Musician user ID (for authorization)
     * @return bool Success status
     */
    public function deleteGig($gig_id, $musician_id) {
        try {
            // Verify gig ownership
            if (!$this->verifyGigOwnership($gig_id, $musician_id)) {
                throw new Exception("Unauthorized: You can only delete your own gigs");
            }
            
            // Check if gig can be deleted (not completed or in progress)
            $gig = $this->getGigById($gig_id);
            if (in_array($gig['gig_status'], [self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED])) {
                throw new Exception("Cannot delete gigs that are in progress or completed");
            }
            
            $sql = "DELETE FROM gigs WHERE gig_id = :gig_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute(['gig_id' => $gig_id, 'musician_id' => $musician_id]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logActivity($musician_id, 'gig_deleted', "Gig deleted: " . $gig['title']);
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get gig by ID
     * 
     * @param int $gig_id Gig ID
     * @return array|false Gig data or false
     */
    public function getGigById($gig_id) {
        try {
            $sql = "SELECT * FROM gigs WHERE gig_id = :gig_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['gig_id' => $gig_id]);
            
            return $stmt->fetch();
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get musician's gigs with filtering and pagination
     * 
     * @param int $musician_id Musician user ID
     * @param array $filters Filter criteria
     * @param int $page Page number
     * @param int $per_page Items per page
     * @return array Gigs with pagination info
     */
    public function getMusicianGigs($musician_id, $filters = [], $page = 1, $per_page = 10) {
        try {
            $where_conditions = ["musician_id = :musician_id"];
            $params = ['musician_id' => $musician_id];
            
            // Apply filters
            if (!empty($filters['status'])) {
                $where_conditions[] = "gig_status = :status";
                $params['status'] = $filters['status'];
            }
            
            if (!empty($filters['type'])) {
                $where_conditions[] = "gig_type = :type";
                $params['type'] = $filters['type'];
            }
            
            if (!empty($filters['date_from'])) {
                $where_conditions[] = "gig_date >= :date_from";
                $params['date_from'] = $filters['date_from'];
            }
            
            if (!empty($filters['date_to'])) {
                $where_conditions[] = "gig_date <= :date_to";
                $params['date_to'] = $filters['date_to'];
            }
            
            if (!empty($filters['venue'])) {
                $where_conditions[] = "venue_name LIKE :venue";
                $params['venue'] = '%' . $filters['venue'] . '%';
            }
            
            if (!empty($filters['search'])) {
                $where_conditions[] = "(title LIKE :search OR venue_name LIKE :search OR venue_address LIKE :search)";
                $params['search'] = '%' . $filters['search'] . '%';
            }
            
            $where_clause = implode(' AND ', $where_conditions);
            
            // Get total count
            $count_sql = "SELECT COUNT(*) as total FROM gigs WHERE $where_clause";
            $count_stmt = $this->db->prepare($count_sql);
            $count_stmt->execute($params);
            $total_count = $count_stmt->fetch()['total'];
            
            // Get paginated results
            $offset = ($page - 1) * $per_page;
            $order_by = $this->buildGigOrderBy($filters['sort_by'] ?? 'date_desc');
            
            $sql = "SELECT * FROM gigs 
                    WHERE $where_clause 
                    $order_by 
                    LIMIT :limit OFFSET :offset";
            
            $params['limit'] = $per_page;
            $params['offset'] = $offset;
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $gigs = $stmt->fetchAll();
            
            return [
                'gigs' => $gigs,
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
            return ['gigs' => [], 'pagination' => []];
        }
    }
    
    /**
     * Get upcoming gigs for musician
     * 
     * @param int $musician_id Musician user ID
     * @param int $limit Number of gigs to return
     * @return array Upcoming gigs
     */
    public function getUpcomingGigs($musician_id, $limit = 5) {
        try {
           $sql = "SELECT * FROM gigs
                    WHERE musician_id = :musician_id
                    AND gig_date >= CURDATE()
                    AND gig_status IN ('scheduled', 'confirmed')
                    ORDER BY gig_date ASC, start_time ASC 
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':musician_id', $musician_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->fetchAll();
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Get upcoming gigs across all musicians for public display
     *
     * @param int $limit Number of gigs to return
     * @return array Upcoming gigs with musician names
     */
    public function getPublicUpcomingGigs($limit = 5) {
        try {
            $sql = "SELECT g.*, COALESCE(mp.stage_name, CONCAT(u.first_name, ' ', u.last_name)) AS musician_name
                    FROM gigs g
                    JOIN users u ON g.musician_id = u.user_id
                    LEFT JOIN musician_profiles mp ON g.musician_id = mp.user_id
                    WHERE g.gig_date >= CURDATE()
                      AND g.gig_status IN ('scheduled', 'confirmed')
                    ORDER BY g.gig_date ASC, g.start_time ASC
                    LIMIT :limit";

            $stmt = $this->db->prepare($sql);
            $stmt->bindValue(':limit', (int)$limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();

        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Get gig statistics for musician
     * 
     * @param int $musician_id Musician user ID
     * @param string $period Time period ('month', 'year', 'all')
     * @return array Gig statistics
     */
    public function getGigStatistics($musician_id, $period = 'year') {
        try {
            $stats = [];
            
            // Build date condition
            $date_condition = $this->buildDateCondition($period);
            
            // Total gigs
            $sql = "SELECT COUNT(*) as total FROM gigs 
                    WHERE musician_id = :musician_id $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['total_gigs'] = $stmt->fetch()['total'];
            
            // Gigs by status
            $sql = "SELECT gig_status, COUNT(*) as count FROM gigs 
                    WHERE musician_id = :musician_id $date_condition
                    GROUP BY gig_status";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            
            $status_counts = [];
            while ($row = $stmt->fetch()) {
                $status_counts[$row['gig_status']] = $row['count'];
            }
            $stats['by_status'] = $status_counts;
            
            // Gigs by type
            $sql = "SELECT gig_type, COUNT(*) as count FROM gigs 
                    WHERE musician_id = :musician_id $date_condition
                    GROUP BY gig_type
                    ORDER BY count DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            
            $type_counts = [];
            while ($row = $stmt->fetch()) {
                $type_counts[$row['gig_type']] = $row['count'];
            }
            $stats['by_type'] = $type_counts;
            
            // Total earnings
            $sql = "SELECT COALESCE(SUM(agreed_amount), 0) as total_earnings FROM gigs 
                    WHERE musician_id = :musician_id AND gig_status = 'completed' $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['total_earnings'] = $stmt->fetch()['total_earnings'];
            
            // Average gig amount
            $sql = "SELECT COALESCE(AVG(agreed_amount), 0) as avg_amount FROM gigs 
                    WHERE musician_id = :musician_id AND agreed_amount > 0 $date_condition";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['musician_id' => $musician_id]);
            $stats['average_amount'] = $stmt->fetch()['avg_amount'];
            
            // Monthly breakdown for the year
            if ($period === 'year') {
                $sql = "SELECT MONTH(gig_date) as month, COUNT(*) as count, 
                               COALESCE(SUM(agreed_amount), 0) as earnings
                        FROM gigs 
                        WHERE musician_id = :musician_id AND YEAR(gig_date) = YEAR(CURDATE())
                        GROUP BY MONTH(gig_date)
                        ORDER BY month";
                $stmt = $this->db->prepare($sql);
                $stmt->execute(['musician_id' => $musician_id]);
                
                $monthly_data = [];
                while ($row = $stmt->fetch()) {
                    $monthly_data[$row['month']] = [
                        'count' => $row['count'],
                        'earnings' => $row['earnings']
                    ];
                }
                $stats['monthly_breakdown'] = $monthly_data;
            }
            
            return $stats;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Update gig status
     * 
     * @param int $gig_id Gig ID
     * @param string $status New status
     * @param int $musician_id Musician user ID (for authorization)
     * @return bool Success status
     */
    public function updateGigStatus($gig_id, $status, $musician_id) {
        try {
            // Verify gig ownership
            if (!$this->verifyGigOwnership($gig_id, $musician_id)) {
                throw new Exception("Unauthorized: You can only update your own gigs");
            }
            
            // Validate status
            $valid_statuses = [
                self::STATUS_SCHEDULED, self::STATUS_CONFIRMED, self::STATUS_IN_PROGRESS,
                self::STATUS_COMPLETED, self::STATUS_CANCELLED, self::STATUS_POSTPONED
            ];
            
            if (!in_array($status, $valid_statuses)) {
                throw new Exception("Invalid gig status");
            }
            
            $sql = "UPDATE gigs SET gig_status = :status, updated_at = NOW() 
                    WHERE gig_id = :gig_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                'status' => $status,
                'gig_id' => $gig_id,
                'musician_id' => $musician_id
            ]);
            
            if ($result && $stmt->rowCount() > 0) {
                $this->logActivity($musician_id, 'gig_status_updated', "Gig status updated to: $status");
                return true;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return false;
        }
    }
    
    /**
     * Get gigs calendar data for musician
     * 
     * @param int $musician_id Musician user ID
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @return array Calendar events data
     */
    public function getCalendarGigs($musician_id, $start_date, $end_date) {
        try {
            $sql = "SELECT gig_id, title, venue_name, gig_date, start_time, end_time, 
                           gig_type, gig_status, agreed_amount
                    FROM gigs 
                    WHERE musician_id = :musician_id 
                    AND gig_date BETWEEN :start_date AND :end_date
                    ORDER BY gig_date, start_time";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'musician_id' => $musician_id,
                'start_date' => $start_date,
                'end_date' => $end_date
            ]);
            
            $events = [];
            while ($row = $stmt->fetch()) {
                $events[] = [
                    'id' => $row['gig_id'],
                    'title' => $row['title'],
                    'start' => $row['gig_date'] . 'T' . $row['start_time'],
                    'end' => $row['gig_date'] . 'T' . ($row['end_time'] ?? $row['start_time']),
                    'venue' => $row['venue_name'],
                    'type' => $row['gig_type'],
                    'status' => $row['gig_status'],
                    'amount' => $row['agreed_amount'],
                    'className' => $this->getStatusClassName($row['gig_status'])
                ];
            }
            
            return $events;
            
        } catch (Exception $e) {
            $this->setError($e->getMessage());
            return [];
        }
    }
    
    /**
     * Check for scheduling conflicts
     * 
     * @param int $musician_id Musician user ID
     * @param string $date Gig date
     * @param string $start_time Start time
     * @param string $end_time End time
     * @param int $exclude_gig_id Gig ID to exclude from conflict check
     * @return bool True if no conflicts
     */
    private function checkSchedulingConflicts($musician_id, $date, $start_time, $end_time = null, $exclude_gig_id = null) {
        try {
            $sql = "SELECT COUNT(*) as conflicts FROM gigs 
                    WHERE musician_id = :musician_id 
                    AND gig_date = :date 
                    AND gig_status NOT IN ('cancelled', 'completed')";
            
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
            
            if ($exclude_gig_id) {
                $sql .= " AND gig_id != :exclude_gig_id";
                $params['exclude_gig_id'] = $exclude_gig_id;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetch()['conflicts'] == 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Verify gig ownership
     * 
     * @param int $gig_id Gig ID
     * @param int $musician_id Musician user ID
     * @return bool Ownership status
     */
    private function verifyGigOwnership($gig_id, $musician_id) {
        try {
            $sql = "SELECT COUNT(*) as count FROM gigs 
                    WHERE gig_id = :gig_id AND musician_id = :musician_id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['gig_id' => $gig_id, 'musician_id' => $musician_id]);
            
            return $stmt->fetch()['count'] > 0;
            
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate gig data
     * 
     * @param array $data Gig data to validate
     * @return bool Validation result
     */
    private function validateGigData($data) {
        $errors = [];
        
        // Title validation
        if (isset($data['title']) && (empty($data['title']) || strlen($data['title']) > 200)) {
            $errors[] = "Title is required and must be less than 200 characters";
        }
        
        // Date validation
        if (isset($data['gig_date'])) {
            if (empty($data['gig_date']) || !$this->isValidDate($data['gig_date'])) {
                $errors[] = "Valid gig date is required";
            } elseif (strtotime($data['gig_date']) < strtotime('today')) {
                $errors[] = "Gig date cannot be in the past";
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
        
        // Gig type validation
        if (isset($data['gig_type'])) {
            $valid_types = [
                self::GIG_TYPE_WEDDING, self::GIG_TYPE_PARTY, self::GIG_TYPE_CORPORATE,
                self::GIG_TYPE_CONCERT, self::GIG_TYPE_FESTIVAL, self::GIG_TYPE_BAR_CLUB,
                self::GIG_TYPE_RESTAURANT, self::GIG_TYPE_PRIVATE, self::GIG_TYPE_CHARITY,
                self::GIG_TYPE_OTHER
            ];
            if (!in_array($data['gig_type'], $valid_types)) {
                $errors[] = "Invalid gig type";
            }
        }
        
        // Amount validation
        if (isset($data['agreed_amount']) && !empty($data['agreed_amount'])) {
            if (!is_numeric($data['agreed_amount']) || $data['agreed_amount'] < 0) {
                $errors[] = "Agreed amount must be a valid positive number";
            }
        }
        
        // Audience size validation
        if (isset($data['audience_size']) && !empty($data['audience_size'])) {
            if (!is_numeric($data['audience_size']) || $data['audience_size'] < 0) {
                $errors[] = "Audience size must be a valid positive number";
            }
        }
        
        // Venue name validation
        if (isset($data['venue_name']) && (empty($data['venue_name']) || strlen($data['venue_name']) > 200)) {
            $errors[] = "Venue name is required and must be less than 200 characters";
        }
        
        if (!empty($errors)) {
            $this->setError(implode(', ', $errors));
            return false;
        }
        
        return true;
    }
    
    /**
     * Prepare gig data for database insertion
     * 
     * @param int $musician_id Musician user ID
     * @param array $gig_data Raw gig data
     * @return array Prepared data array
     */
    private function prepareGigData($musician_id, $gig_data) {
        return [
            'musician_id' => $musician_id,
            'title' => $gig_data['title'],
            'venue_name' => $gig_data['venue_name'],
            'venue_address' => $gig_data['venue_address'] ?? null,
            'venue_contact' => $gig_data['venue_contact'] ?? null,
            'gig_date' => $gig_data['gig_date'],
            'start_time' => $gig_data['start_time'],
            'end_time' => $gig_data['end_time'] ?? null,
            'gig_type' => $gig_data['gig_type'] ?? self::GIG_TYPE_OTHER,
            'gig_status' => $gig_data['gig_status'] ?? self::STATUS_SCHEDULED,
            'agreed_amount' => $gig_data['agreed_amount'] ?? null,
            'payment_terms' => $gig_data['payment_terms'] ?? null,
            'equipment_required' => $gig_data['equipment_required'] ?? null,
            'special_requirements' => $gig_data['special_requirements'] ?? null,
            'audience_size' => $gig_data['audience_size'] ?? null,
            'performance_notes' => $gig_data['performance_notes'] ?? null
        ];
    }
    
    /**
     * Build ORDER BY clause for gig queries
     * 
     * @param string $sort_by Sort criteria
     * @return string ORDER BY clause
     */
    private function buildGigOrderBy($sort_by) {
        switch ($sort_by) {
            case 'date_asc':
                return "ORDER BY gig_date ASC, start_time ASC";
            case 'date_desc':
                return "ORDER BY gig_date DESC, start_time DESC";
            case 'title_asc':
                return "ORDER BY title ASC";
            case 'title_desc':
                return "ORDER BY title DESC";
            case 'venue_asc':
                return "ORDER BY venue_name ASC";
            case 'venue_desc':
                return "ORDER BY venue_name DESC";
            case 'amount_asc':
                return "ORDER BY agreed_amount ASC";
            case 'amount_desc':
                return "ORDER BY agreed_amount DESC";
            case 'status_asc':
                return "ORDER BY gig_status ASC";
            case 'status_desc':
                return "ORDER BY gig_status DESC";
            case 'created_desc':
                return "ORDER BY created_at DESC";
            case 'created_asc':
                return "ORDER BY created_at ASC";
            default:
                return "ORDER BY gig_date DESC, start_time DESC";
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
                return "AND gig_date >= DATE_SUB(CURDATE(), INTERVAL 1 MONTH)";
            case 'year':
                return "AND gig_date >= DATE_SUB(CURDATE(), INTERVAL 1 YEAR)";
            case 'week':
                return "AND gig_date >= DATE_SUB(CURDATE(), INTERVAL 1 WEEK)";
            case 'all':
            default:
                return "";
        }
    }
    
    /**
     * Get CSS class name for gig status
     * 
     * @param string $status Gig status
     * @return string CSS class name
     */
    private function getStatusClassName($status) {
        switch ($status) {
            case self::STATUS_CONFIRMED:
                return 'gig-confirmed';
            case self::STATUS_IN_PROGRESS:
                return 'gig-in-progress';
            case self::STATUS_COMPLETED:
                return 'gig-completed';
            case self::STATUS_CANCELLED:
                return 'gig-cancelled';
            case self::STATUS_POSTPONED:
                return 'gig-postponed';
            case self::STATUS_SCHEDULED:
            default:
                return 'gig-scheduled';
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
     * Get gig types array
     * 
     * @return array Gig types with labels
     */
    public static function getGigTypes() {
        return [
            self::GIG_TYPE_WEDDING => 'Wedding',
            self::GIG_TYPE_PARTY => 'Private Party',
            self::GIG_TYPE_CORPORATE => 'Corporate Event',
            self::GIG_TYPE_CONCERT => 'Concert',
            self::GIG_TYPE_FESTIVAL => 'Festival',
            self::GIG_TYPE_BAR_CLUB => 'Bar/Club',
            self::GIG_TYPE_RESTAURANT => 'Restaurant',
            self::GIG_TYPE_PRIVATE => 'Private Event',
            self::GIG_TYPE_CHARITY => 'Charity Event',
            self::GIG_TYPE_OTHER => 'Other'
        ];
    }
    
    /**
     * Get gig statuses array
     * 
     * @return array Gig statuses with labels
     */
    public static function getGigStatuses() {
        return [
            self::STATUS_SCHEDULED => 'Scheduled',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_POSTPONED => 'Postponed'
        ];
    }
    
    /**
     * Get payment term options 
     * @return array Payment term options
     */
    public static function getPaymentTermOptions() {
        return [
            'full_advance' => 'Full Payment in Advance',
            '50_50' => '50% Advance, 50% After Event',
            'after_event' => 'Payment After Event',
            'installments' => 'Multiple Installments',
            'negotiable' => 'Negotiable'
        ];
    }
    
    // Getters and Setters
    public function getGigId() { return $this->gig_id; }
    public function getMusicianId() { return $this->musician_id; }
    public function getTitle() { return $this->title; }
    public function getVenueName() { return $this->venue_name; }
    public function getVenueAddress() { return $this->venue_address; }
    public function getVenueContact() { return $this->venue_contact; }
    public function getGigDate() { return $this->gig_date; }
    public function getStartTime() { return $this->start_time; }
    public function getEndTime() { return $this->end_time; }
    public function getGigType() { return $this->gig_type; }
    public function getGigStatus() { return $this->gig_status; }
    public function getAgreedAmount() { return $this->agreed_amount; }
    public function getPaymentTerms() { return $this->payment_terms; }
    public function getEquipmentRequired() { return $this->equipment_required; }
    public function getSpecialRequirements() { return $this->special_requirements; }
    public function getAudienceSize() { return $this->audience_size; }
    public function getPerformanceNotes() { return $this->performance_notes; }
    public function getError() { return $this->error; }
    
    public function setGigId($gig_id) { $this->gig_id = $gig_id; }
    public function setMusicianId($musician_id) { $this->musician_id = $musician_id; }
    public function setTitle($title) { $this->title = $title; }
    public function setVenueName($venue_name) { $this->venue_name = $venue_name; }
    public function setVenueAddress($venue_address) { $this->venue_address = $venue_address; }
    public function setVenueContact($venue_contact) { $this->venue_contact = $venue_contact; }
    public function setGigDate($gig_date) { $this->gig_date = $gig_date; }
    public function setStartTime($start_time) { $this->start_time = $start_time; }
    public function setEndTime($end_time) { $this->end_time = $end_time; }
    public function setGigType($gig_type) { $this->gig_type = $gig_type; }
    public function setGigStatus($gig_status) { $this->gig_status = $gig_status; }
    public function setAgreedAmount($agreed_amount) { $this->agreed_amount = $agreed_amount; }
    public function setPaymentTerms($payment_terms) { $this->payment_terms = $payment_terms; }
    public function setEquipmentRequired($equipment_required) { $this->equipment_required = $equipment_required; }
    public function setSpecialRequirements($special_requirements) { $this->special_requirements = $special_requirements; }
    public function setAudienceSize($audience_size) { $this->audience_size = $audience_size; }
    public function setPerformanceNotes($performance_notes) { $this->performance_notes = $performance_notes; }
    public function setError($error) { $this->error = $error; }
}
?> 
