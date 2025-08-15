 
<?php
/**
 * Base Database Class with CRUD Operations
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

require_once __DIR__ . '/../config/database.php';

/**
 * Base Database Class
 * Provides common CRUD operations and database utilities
 */
class Database {
    protected $db;
    protected $table;
    protected $primaryKey = 'id';
    
    /**
     * Constructor
     * @param string $table Table name
     * @param string $primaryKey Primary key column name
     */
    public function __construct($table = '', $primaryKey = 'id') {
        $this->db = getDB();
        $this->table = $table;
        $this->primaryKey = $primaryKey;
    }

    /**
     * Create a new record
     * @param array $data Associative array of column => value
     * @return int|false Last insert ID or false on failure
     */
    public function create($data) {
        try {
            $columns = implode(', ', array_keys($data));
            $placeholders = ':' . implode(', :', array_keys($data));
            
            $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
            $stmt = $this->db->prepare($sql);
            
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            if ($stmt->execute()) {
                return $this->db->lastInsertId();
            }
            return false;
            
        } catch (PDOException $e) {
            $this->logError("Create operation failed", $e, $data);
            return false;
        }
    }

    /**
     * Read records with optional conditions
     * @param array $conditions WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @param int $limit LIMIT clause
     * @param int $offset OFFSET clause
     * @return array|false
     */
    public function read($conditions = [], $orderBy = '', $limit = null, $offset = 0) {
        try {
            $sql = "SELECT * FROM {$this->table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $column => $value) {
                    if (is_array($value)) {
                        // Handle IN clause
                        $placeholders = implode(',', array_fill(0, count($value), '?'));
                        $whereClause[] = "{$column} IN ({$placeholders})";
                        $params = array_merge($params, $value);
                    } else {
                        $whereClause[] = "{$column} = ?";
                        $params[] = $value;
                    }
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            
            if ($limit) {
                $sql .= " LIMIT {$limit}";
                if ($offset > 0) {
                    $sql .= " OFFSET {$offset}";
                }
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->logError("Read operation failed", $e, $conditions);
            return false;
        }
    }

    /**
     * Read a single record by ID
     * @param mixed $id Primary key value
     * @return array|false
     */
    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$id]);
            
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            $this->logError("Find operation failed", $e, ['id' => $id]);
            return false;
        }
    }

    /**
     * Update a record
     * @param mixed $id Primary key value
     * @param array $data Associative array of column => value
     * @return bool
     */
    public function update($id, $data) {
        try {
            $setClause = [];
            foreach (array_keys($data) as $column) {
                $setClause[] = "{$column} = :{$column}";
            }
            
            $sql = "UPDATE {$this->table} SET " . implode(', ', $setClause) . 
                   " WHERE {$this->primaryKey} = :id";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind data parameters
            foreach ($data as $key => $value) {
                $stmt->bindValue(":{$key}", $value);
            }
            
            // Bind ID parameter
            $stmt->bindValue(':id', $id);
            
            return $stmt->execute();
            
        } catch (PDOException $e) {
            $this->logError("Update operation failed", $e, ['id' => $id, 'data' => $data]);
            return false;
        }
    }

    /**
     * Delete a record
     * @param mixed $id Primary key value
     * @return bool
     */
    public function delete($id) {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            
            return $stmt->execute([$id]);
            
        } catch (PDOException $e) {
            $this->logError("Delete operation failed", $e, ['id' => $id]);
            return false;
        }
    }

    /**
     * Count total records with optional conditions
     * @param array $conditions WHERE conditions
     * @return int|false
     */
    public function count($conditions = []) {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}";
            $params = [];
            
            if (!empty($conditions)) {
                $whereClause = [];
                foreach ($conditions as $column => $value) {
                    $whereClause[] = "{$column} = ?";
                    $params[] = $value;
                }
                $sql .= " WHERE " . implode(' AND ', $whereClause);
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch();
            return $result ? (int)$result['total'] : 0;
            
        } catch (PDOException $e) {
            $this->logError("Count operation failed", $e, $conditions);
            return false;
        }
    }

    /**
     * Execute a custom query
     * @param string $sql SQL query
     * @param array $params Parameters for prepared statement
     * @return PDOStatement|false
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute($params)) {
                return $stmt;
            }
            return false;
            
        } catch (PDOException $e) {
            $this->logError("Custom query failed", $e, ['sql' => $sql, 'params' => $params]);
            return false;
        }
    }

    /**
     * Get records with pagination
     * @param int $page Page number (1-based)
     * @param int $perPage Records per page
     * @param array $conditions WHERE conditions
     * @param string $orderBy ORDER BY clause
     * @return array
     */
    public function paginate($page = 1, $perPage = 20, $conditions = [], $orderBy = '') {
        $page = max(1, (int)$page);
        $perPage = max(1, (int)$perPage);
        $offset = ($page - 1) * $perPage;
        
        $totalRecords = $this->count($conditions);
        $totalPages = ceil($totalRecords / $perPage);
        
        $records = $this->read($conditions, $orderBy, $perPage, $offset);
        
        return [
            'data' => $records,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total_records' => $totalRecords,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
    }

    /**
     * Check if a record exists
     * @param mixed $id Primary key value
     * @return bool
     */
    public function exists($id) {
        $count = $this->count([$this->primaryKey => $id]);
        return $count > 0;
    }

    /**
     * Begin database transaction
     * @return bool
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }

    /**
     * Commit database transaction
     * @return bool
     */
    public function commit() {
        return $this->db->commit();
    }

    /**
     * Rollback database transaction
     * @return bool
     */
    public function rollback() {
        return $this->db->rollback();
    }

    /**
     * Get the last insert ID
     * @return string
     */
    public function getLastInsertId() {
        return $this->db->lastInsertId();
    }

    /**
     * Escape string for SQL LIKE operations
     * @param string $string
     * @return string
     */
    public function escapeLike($string) {
        return str_replace(['%', '_'], ['\%', '\_'], $string);
    }

    /**
     * Search records with LIKE operator
     * @param array $searchFields Fields to search in
     * @param string $searchTerm Search term
     * @param string $orderBy ORDER BY clause
     * @param int $limit LIMIT clause
     * @return array|false
     */
    public function search($searchFields, $searchTerm, $orderBy = '', $limit = null) {
        try {
            $searchTerm = '%' . $this->escapeLike($searchTerm) . '%';
            
            $whereClause = [];
            $params = [];
            
            foreach ($searchFields as $field) {
                $whereClause[] = "{$field} LIKE ?";
                $params[] = $searchTerm;
            }
            
            $sql = "SELECT * FROM {$this->table} WHERE " . implode(' OR ', $whereClause);
            
            if ($orderBy) {
                $sql .= " ORDER BY {$orderBy}";
            }
            
            if ($limit) {
                $sql .= " LIMIT {$limit}";
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            $this->logError("Search operation failed", $e, [
                'fields' => $searchFields, 
                'term' => $searchTerm
            ]);
            return false;
        }
    }

    /**
     * Log database errors
     * @param string $message Error message
     * @param PDOException $exception PDO exception
     * @param array $context Additional context
     */
    protected function logError($message, $exception, $context = []) {
        $logMessage = sprintf(
            "%s - Table: %s - Error: %s - Context: %s",
            $message,
            $this->table,
            $exception->getMessage(),
            json_encode($context)
        );
        
        error_log($logMessage);
        
        // In development, also log the stack trace
        if (ENVIRONMENT === 'development') {
            error_log("Stack trace: " . $exception->getTraceAsString());
        }
    }

    /**
     * Sanitize data for database operations
     * @param array $data
     * @return array
     */
    protected function sanitizeData($data) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } elseif (is_array($value)) {
                $sanitized[$key] = json_encode($value);
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Validate required fields
     * @param array $data Data to validate
     * @param array $required Required field names
     * @return array Array of missing fields
     */
    protected function validateRequired($data, $required) {
        $missing = [];
        
        foreach ($required as $field) {
            if (!isset($data[$field]) || 
                (is_string($data[$field]) && trim($data[$field]) === '') ||
                $data[$field] === null) {
                $missing[] = $field;
            }
        }
        
        return $missing;
    }
}

?>