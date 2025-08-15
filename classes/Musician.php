<?php
/**
 * Musician Management Class
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/User.php';
require_once __DIR__ . '/Database.php';

/**
 * Musician Class - Extends User for musician-specific functionality
 */
class Musician extends User {
    
    private $musicianProfileDb;
    private $gigDb;
    private $availabilityDb;
    
    public function __construct() {
        parent::__construct();
        $this->musicianProfileDb = new Database('musician_profiles', 'musician_profile_id');
        $this->gigDb = new Database('gigs', 'gig_id');
        $this->availabilityDb = new Database('musician_availability', 'availability_id');
    }

    /**
     * Create musician profile after user registration
     * @param int $user_id
     * @param array $profileData
     * @return array
     */
    public function createMusicianProfile($user_id, $profileData = []) {
        try {
            $this->beginTransaction();
            
            // Validate user is a musician
            $user = $this->find($user_id);
            if (!$user || $user['user_type'] !== USER_TYPE_MUSICIAN) {
                throw new Exception('Invalid user or user is not a musician');
            }
            
            // Check if profile already exists
            $existingProfile = $this->musicianProfileDb->read(['user_id' => $user_id]);
            if (!empty($existingProfile)) {
                throw new Exception('Musician profile already exists');
            }
            
            // Sanitize and validate profile data
            $profileData = $this->sanitizeData($profileData);
            $validation = $this->validateMusicianProfileData($profileData);
            
            if (!$validation['valid']) {
                throw new Exception(implode(', ', $validation['errors']));
            }
            
            // Prepare musician profile data
            $musicianData = [
                'user_id' => $user_id,
                'stage_name' => $profileData['stage_name'] ?? null,
                'genres' => json_encode($profileData['genres'] ?? []),
                'instruments' => json_encode($profileData['instruments'] ?? []),
                'experience_level' => $profileData['experience_level'] ?? DEFAULT_VALUES['experience_level'],
                'years_of_experience' => intval($profileData['years_of_experience'] ?? 0),
                'base_price_per_hour' => floatval($profileData['base_price_per_hour'] ?? 0.00),
                'base_price_per_event' => floatval($profileData['base_price_per_event'] ?? 0.00),
                'pricing_negotiable' => $profileData['pricing_negotiable'] ?? DEFAULT_VALUES['pricing_negotiable'],
                'travel_radius' => intval($profileData['travel_radius'] ?? DEFAULT_VALUES['travel_radius']),
                'equipment_provided' => $profileData['equipment_provided'] ?? DEFAULT_VALUES['equipment_provided'],
                'specialties' => $profileData['specialties'] ?? null,
                'performance_setup' => $profileData['performance_setup'] ?? null,
                'availability_status' => DEFAULT_VALUES['availability_status'],
                'rating' => DEFAULT_VALUES['rating'],
                'total_ratings' => 0,
                'total_bookings' => 0,
                'created_at' => date('Y-m-d H:i:s')
            ];
            
            $profile_id = $this->musicianProfileDb->create($musicianData);
            
            if ($profile_id) {
                // Update profile completion percentage
                $this->updateProfileCompletion($user_id);
                
                $this->commit();
                
                logActivity(ACTIVITY_PROFILE_UPDATED, 'Musician profile created', $user_id);
                
                return [
                    'success' => true,
                    'message' => 'Musician profile created successfully',
                    'profile_id' => $profile_id
                ];
            }
            
            throw new Exception('Failed to create musician profile');
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Create musician profile error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Update musician profile
     * @param int $user_id
     * @param array $profileData
     * @return array
     */
    public function updateMusicianProfile($user_id, $profileData) {
        try {
            // Get existing profile
            $existingProfile = $this->getMusicianProfile($user_id);
            if (!$existingProfile) {
                return [
                    'success' => false,
                    'message' => 'Musician profile not found'
                ];
            }
            
            // Sanitize and validate data
            $profileData = $this->sanitizeData($profileData);
            $validation = $this->validateMusicianProfileData($profileData, true); // true for update
            
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => implode(', ', $validation['errors'])
                ];
            }
            
            $this->beginTransaction();
            
            // Prepare update data
            $updateData = [];
            $allowedFields = [
                'stage_name', 'experience_level', 'years_of_experience',
                'base_price_per_hour', 'base_price_per_event', 'pricing_negotiable',
                'travel_radius', 'equipment_provided', 'specialties', 'performance_setup',
                'availability_status'
            ];
            
            foreach ($allowedFields as $field) {
                if (isset($profileData[$field])) {
                    $updateData[$field] = $profileData[$field];
                }
            }
            
            // Handle JSON fields
            if (isset($profileData['genres'])) {
                $updateData['genres'] = json_encode($profileData['genres']);
            }
            
            if (isset($profileData['instruments'])) {
                $updateData['instruments'] = json_encode($profileData['instruments']);
            }
            
            if (isset($profileData['demo_tracks'])) {
                $updateData['demo_tracks'] = json_encode($profileData['demo_tracks']);
            }
            
            if (isset($profileData['portfolio_images'])) {
                $updateData['portfolio_images'] = json_encode($profileData['portfolio_images']);
            }
            
            // Update the profile
            $success = $this->musicianProfileDb->update($existingProfile['musician_profile_id'], $updateData);
            
            if ($success) {
                // Update profile completion percentage
                $this->updateProfileCompletion($user_id);
                
                $this->commit();
                
                logActivity(ACTIVITY_PROFILE_UPDATED, 'Musician profile updated', $user_id);
                
                return [
                    'success' => true,
                    'message' => 'Musician profile updated successfully'
                ];
            }
            
            throw new Exception('Failed to update musician profile');
            
        } catch (Exception $e) {
            $this->rollback();
            error_log("Update musician profile error: " . $e->getMessage());
            return [
                'success' => false,
                'message' => 'Failed to update profile. Please try again.'
            ];
        }
    }

    /**
     * Get complete musician profile with user data
     * @param int $user_id
     * @return array|false
     */
    public function getMusicianProfile($user_id) {
        try {
            $sql = "
                SELECT u.*, up.*, mp.*,
                       (SELECT COUNT(*) FROM gigs WHERE musician_id = u.user_id AND status = 'completed') as completed_gigs,
                       (SELECT COUNT(*) FROM bookings WHERE musician_id = u.user_id AND booking_status = 'pending') as pending_bookings
                FROM users u
                LEFT JOIN user_profiles up ON u.user_id = up.user_id
                LEFT JOIN musician_profiles mp ON u.user_id = mp.user_id
                WHERE u.user_id = ? AND u.user_type = 'musician'
            ";
            
            $stmt = $this->query($sql, [$user_id]);
            $profile = $stmt ? $stmt->fetch() : false;
            
            if ($profile) {
                // Decode JSON fields
                $profile['genres'] = json_decode($profile['genres'] ?? '[]', true);
                $profile['instruments'] = json_decode($profile['instruments'] ?? '[]', true);
                $profile['demo_tracks'] = json_decode($profile['demo_tracks'] ?? '[]', true);
                $profile['portfolio_images'] = json_decode($profile['portfolio_images'] ?? '[]', true);
                $profile['social_media'] = json_decode($profile['social_media'] ?? '{}', true);
            }
            
            return $profile;
            
        } catch (Exception $e) {
            error_log("Get musician profile error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get public musician profile for client viewing
     * @param int $user_id
     * @return array|false
     */
    public function getPublicMusicianProfile($user_id) {
        $profile = $this->getMusicianProfile($user_id);
        
        if ($profile) {
            // Remove sensitive information
            $publicFields = [
                'user_id', 'username', 'first_name', 'last_name', 'profile_picture',
                'bio', 'location', 'city', 'stage_name', 'genres', 'instruments',
                'experience_level', 'years_of_experience', 'base_price_per_hour',
                'base_price_per_event', 'pricing_negotiable', 'travel_radius',
                'equipment_provided', 'specialties', 'availability_status',
                'rating', 'total_ratings', 'total_bookings', 'is_featured',
                'is_verified', 'demo_tracks', 'portfolio_images', 'completed_gigs'
            ];
            
            $publicProfile = [];
            foreach ($publicFields as $field) {
                if (isset($profile[$field])) {
                    $publicProfile[$field] = $profile[$field];
                }
            }
            
            return $publicProfile;
        }
        
        return false;
    }

    /**
     * Search musicians with filters
     * @param array $filters
     * @param int $page
     * @param int $limit
     * @return array
     */
    public function searchMusicians($filters = [], $page = 1, $limit = 12) {
        try {
            $conditions = [];
            $params = [];
            
            $sql = "
                SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture,
                       up.bio, up.location, up.city,
                       mp.stage_name, mp.genres, mp.instruments, mp.experience_level,
                       mp.base_price_per_hour, mp.base_price_per_event, mp.pricing_negotiable,
                       mp.availability_status, mp.rating, mp.total_ratings, mp.total_bookings,
                       mp.is_featured, mp.is_verified
                FROM users u
                JOIN user_profiles up ON u.user_id = up.user_id
                JOIN musician_profiles mp ON u.user_id = mp.user_id
                WHERE u.user_type = 'musician' AND u.account_status = 'active'
            ";
            
            // Apply filters
            if (!empty($filters['genre'])) {
                $sql .= " AND JSON_CONTAINS(mp.genres, ?)";
                $params[] = json_encode($filters['genre']);
            }
            
            if (!empty($filters['location'])) {
                $sql .= " AND (up.city LIKE ? OR up.location LIKE ?)";
                $searchLocation = '%' . $this->escapeLike($filters['location']) . '%';
                $params[] = $searchLocation;
                $params[] = $searchLocation;
            }
            
            if (!empty($filters['experience_level'])) {
                $sql .= " AND mp.experience_level = ?";
                $params[] = $filters['experience_level'];
            }
            
            if (!empty($filters['min_price']) && !empty($filters['max_price'])) {
                $sql .= " AND ((mp.base_price_per_hour BETWEEN ? AND ?) OR (mp.base_price_per_event BETWEEN ? AND ?))";
                $params[] = $filters['min_price'];
                $params[] = $filters['max_price'];
                $params[] = $filters['min_price'];
                $params[] = $filters['max_price'];
            }
            
            if (!empty($filters['availability'])) {
                $sql .= " AND mp.availability_status = ?";
                $params[] = $filters['availability'];
            }
            
            if (!empty($filters['verified'])) {
                $sql .= " AND mp.is_verified = 1";
            }
            
            if (!empty($filters['search'])) {
                $sql .= " AND (u.first_name LIKE ? OR u.last_name LIKE ? OR mp.stage_name LIKE ? OR up.bio LIKE ?)";
                $searchTerm = '%' . $this->escapeLike($filters['search']) . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
            
            // Order by
            $orderBy = " ORDER BY mp.is_featured DESC, mp.rating DESC, mp.total_bookings DESC";
            
            if (!empty($filters['sort'])) {
                switch ($filters['sort']) {
                    case 'price_low':
                        $orderBy = " ORDER BY mp.base_price_per_hour ASC";
                        break;
                    case 'price_high':
                        $orderBy = " ORDER BY mp.base_price_per_hour DESC";
                        break;
                    case 'rating':
                        $orderBy = " ORDER BY mp.rating DESC";
                        break;
                    case 'newest':
                        $orderBy = " ORDER BY u.created_at DESC";
                        break;
                }
            }
            
            $sql .= $orderBy;
            
            // Add pagination
            $offset = ($page - 1) * $limit;
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
            
            $stmt = $this->query($sql, $params);
            $musicians = $stmt ? $stmt->fetchAll() : [];
            
            // Decode JSON fields
            foreach ($musicians as &$musician) {
                $musician['genres'] = json_decode($musician['genres'] ?? '[]', true);
                $musician['instruments'] = json_decode($musician['instruments'] ?? '[]', true);
            }
            
            // Get total count for pagination
            $countSql = str_replace(
                "SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture,
                       up.bio, up.location, up.city,
                       mp.stage_name, mp.genres, mp.instruments, mp.experience_level,
                       mp.base_price_per_hour, mp.base_price_per_event, mp.pricing_negotiable,
                       mp.availability_status, mp.rating, mp.total_ratings, mp.total_bookings,
                       mp.is_featured, mp.is_verified",
                "SELECT COUNT(*) as total",
                $sql
            );
            $countSql = preg_replace('/\s+ORDER BY.*?\s+LIMIT.*/', '', $countSql);
            $countParams = array_slice($params, 0, -2); // Remove limit and offset params
            
            $countStmt = $this->query($countSql, $countParams);
            $totalCount = $countStmt ? $countStmt->fetch()['total'] : 0;
            
            return [
                'musicians' => $musicians,
                'total' => $totalCount,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => ceil($totalCount / $limit)
            ];
            
        } catch (Exception $e) {
            error_log("Search musicians error: " . $e->getMessage());
            return [
                'musicians' => [],
                'total' => 0,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => 0
            ];
        }
    }

    /**
     * Get musician dashboard statistics
     * @param int $user_id
     * @return array
     */
    public function getDashboardStats($user_id) {
        try {
            $stats = [];
            
            // Total gigs
            $stats['total_gigs'] = $this->gigDb->count(['musician_id' => $user_id]);
            
            // Completed gigs
            $stats['completed_gigs'] = $this->gigDb->count([
                'musician_id' => $user_id,
                'status' => 'completed'
            ]);
            
            // Pending bookings
            $bookingDb = new Database('bookings', 'booking_id');
            $stats['pending_bookings'] = $bookingDb->count([
                'musician_id' => $user_id,
                'booking_status' => 'pending'
            ]);
            
            // Upcoming gigs (next 30 days)
            $sql = "SELECT COUNT(*) as count FROM gigs 
                   WHERE musician_id = ? AND event_date >= CURDATE() 
                   AND event_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                   AND status IN ('scheduled', 'in_progress')";
            $stmt = $this->query($sql, [$user_id]);
            $stats['upcoming_gigs'] = $stmt ? $stmt->fetch()['count'] : 0;
            
            // Total earnings (completed payments)
            $sql = "SELECT COALESCE(SUM(amount), 0) as total_earnings 
                   FROM payments 
                   WHERE payee_id = ? AND payment_status = 'completed'";
            $stmt = $this->query($sql, [$user_id]);
            $stats['total_earnings'] = $stmt ? $stmt->fetch()['total_earnings'] : 0;
            
            // This month's earnings
            $sql = "SELECT COALESCE(SUM(amount), 0) as monthly_earnings 
                   FROM payments 
                   WHERE payee_id = ? AND payment_status = 'completed'
                   AND MONTH(payment_date) = MONTH(CURDATE()) 
                   AND YEAR(payment_date) = YEAR(CURDATE())";
            $stmt = $this->query($sql, [$user_id]);
            $stats['monthly_earnings'] = $stmt ? $stmt->fetch()['monthly_earnings'] : 0;
            
            // Profile completion percentage
            $profile = $this->getMusicianProfile($user_id);
            $stats['profile_completion'] = $profile['profile_completion_percentage'] ?? 0;
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get dashboard stats error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get featured musicians
     * @param int $limit
     * @return array
     */
    public function getFeaturedMusicians($limit = 6) {
        try {
            $sql = "
                SELECT u.user_id, u.username, u.first_name, u.last_name, u.profile_picture,
                       up.bio, up.city,
                       mp.stage_name, mp.genres, mp.rating, mp.total_ratings, mp.total_bookings
                FROM users u
                JOIN user_profiles up ON u.user_id = up.user_id
                JOIN musician_profiles mp ON u.user_id = mp.user_id
                WHERE u.user_type = 'musician' AND u.account_status = 'active' 
                AND mp.is_featured = 1
                ORDER BY mp.rating DESC, mp.total_bookings DESC
                LIMIT ?
            ";
            
            $stmt = $this->query($sql, [$limit]);
            $musicians = $stmt ? $stmt->fetchAll() : [];
            
            // Decode genres
            foreach ($musicians as &$musician) {
                $musician['genres'] = json_decode($musician['genres'] ?? '[]', true);
            }
            
            return $musicians;
            
        } catch (Exception $e) {
            error_log("Get featured musicians error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Update availability status
     * @param int $user_id
     * @param string $status
     * @return bool
     */
    public function updateAvailabilityStatus($user_id, $status) {
        try {
            $validStatuses = ['available', 'busy', 'unavailable'];
            if (!in_array($status, $validStatuses)) {
                return false;
            }
            
            $profile = $this->musicianProfileDb->read(['user_id' => $user_id]);
            if (empty($profile)) {
                return false;
            }
            
            $result = $this->musicianProfileDb->update($profile[0]['musician_profile_id'], [
                'availability_status' => $status
            ]);
            
            if ($result) {
                logActivity(ACTIVITY_PROFILE_UPDATED, "Availability status updated to: $status", $user_id);
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Update availability status error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get musician statistics
     * @return array
     */
    public function getMusicianStatistics() {
        try {
            $stats = [];
            
            // Total musicians
            $stats['total_musicians'] = $this->count(['user_type' => USER_TYPE_MUSICIAN]);
            
            // Active musicians
            $stats['active_musicians'] = $this->musicianProfileDb->count(['availability_status' => 'available']);
            
            // Featured musicians
            $stats['featured_musicians'] = $this->musicianProfileDb->count(['is_featured' => 1]);
            
            // Verified musicians
            $stats['verified_musicians'] = $this->musicianProfileDb->count(['is_verified' => 1]);
            
            // Average rating
            $sql = "SELECT AVG(rating) as avg_rating FROM musician_profiles WHERE rating > 0";
            $stmt = $this->query($sql);
            $result = $stmt->fetch();
            $stats['average_rating'] = round($result['avg_rating'] ?? 0, 2);
            
            return $stats;
            
        } catch (Exception $e) {
            error_log("Get musician statistics error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Validate musician profile data
     * @param array $data
     * @param bool $isUpdate
     * @return array
     */
    private function validateMusicianProfileData($data, $isUpdate = false) {
        $errors = [];
        
        // Validate experience level
        if (isset($data['experience_level'])) {
            $validLevels = ['beginner', 'intermediate', 'professional', 'expert'];
            if (!in_array($data['experience_level'], $validLevels)) {
                $errors[] = 'Invalid experience level';
            }
        }
        
        // Validate years of experience
        if (isset($data['years_of_experience'])) {
            if (!is_numeric($data['years_of_experience']) || $data['years_of_experience'] < 0) {
                $errors[] = 'Years of experience must be a valid number';
            }
        }
        
        // Validate pricing
        if (isset($data['base_price_per_hour'])) {
            if (!is_numeric($data['base_price_per_hour']) || $data['base_price_per_hour'] < 0) {
                $errors[] = 'Price per hour must be a valid amount';
            }
        }
        
        if (isset($data['base_price_per_event'])) {
            if (!is_numeric($data['base_price_per_event']) || $data['base_price_per_event'] < 0) {
                $errors[] = 'Price per event must be a valid amount';
            }
        }
        
        // Validate travel radius
        if (isset($data['travel_radius'])) {
            if (!is_numeric($data['travel_radius']) || $data['travel_radius'] < 0) {
                $errors[] = 'Travel radius must be a valid number';
            }
        }
        
        // Validate genres (must be array)
        if (isset($data['genres'])) {
            if (!is_array($data['genres'])) {
                $errors[] = 'Genres must be an array';
            }
        }
        
        // Validate instruments (must be array)
        if (isset($data['instruments'])) {
            if (!is_array($data['instruments'])) {
                $errors[] = 'Instruments must be an array';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Update profile completion percentage
     * @param int $user_id
     */
    private function updateProfileCompletion($user_id) {
        try {
            $profile = $this->getMusicianProfile($user_id);
            if (!$profile) return;
            
            $completionPoints = 0;
            $totalPoints = 100;
            
            // Basic info (40 points)
            if (!empty($profile['first_name'])) $completionPoints += 10;
            if (!empty($profile['last_name'])) $completionPoints += 10;
            if (!empty($profile['bio'])) $completionPoints += 10;
            if (!empty($profile['location'])) $completionPoints += 10;
            
            // Musician specific (40 points)
            if (!empty($profile['genres']) && count($profile['genres']) > 0) $completionPoints += 10;
            if (!empty($profile['instruments']) && count($profile['instruments']) > 0) $completionPoints += 10;
            if (!empty($profile['base_price_per_hour']) || !empty($profile['base_price_per_event'])) $completionPoints += 10;
            if (!empty($profile['specialties'])) $completionPoints += 10;
            
            // Additional info (20 points)
            if (!empty($profile['profile_picture']) && $profile['profile_picture'] !== 'default-avatar.png') $completionPoints += 10;
            if (!empty($profile['phone'])) $completionPoints += 10;
            
            $percentage = min($completionPoints, $totalPoints);
            
            // Update user profile
            $userProfileDb = new Database('user_profiles', 'profile_id');
            $userProfile = $userProfileDb->read(['user_id' => $user_id]);
            if (!empty($userProfile)) {
                $userProfileDb->update($userProfile[0]['profile_id'], [
                    'profile_completion_percentage' => $percentage
                ]);
            }
            
        } catch (Exception $e) {
            error_log("Update profile completion error: " . $e->getMessage());
        }
    }

    /**
     * Escape LIKE wildcards in search terms
     * @param string $string
     * @return string
     */
    private function escapeLike($string) {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }
}

?> 
