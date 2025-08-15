<?php
/**
 * Database Connection Configuration
 * Musician Booking System
 */

// Security check
if (!defined('SYSTEM_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Database Connection Class
 * Handles MySQL database connections with error handling and security features
 */
class DatabaseConnection {
    private static $instance = null;
    private $connection;
    private $host;
    private $database;
    private $username;
    private $password;
    private $charset;

    /**
     * Private constructor for singleton pattern
     */
    private function __construct() {
        $this->host = DB_HOST;
        $this->database = DB_NAME;
        $this->username = DB_USER;
        $this->password = DB_PASS;
        $this->charset = DB_CHARSET;

        try {
            $dsn = "mysql:host={$this->host};dbname={$this->database};charset={$this->charset}";
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::ATTR_PERSISTENT => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {$this->charset} COLLATE {$this->charset}_unicode_ci"
            ];

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            
            // Log successful connection in development
            if (ENVIRONMENT === 'development') {
                error_log("Database connection established successfully");
            }
            
        } catch (PDOException $e) {
            $this->handleConnectionError($e);
        }
    }

    /**
     * Get singleton instance of database connection
     * @return DatabaseConnection
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get PDO connection object
     * @return PDO
     */
    public function getConnection() {
        return $this->connection;
    }

    /**
     * Handle database connection errors
     * @param PDOException $e
     */
    private function handleConnectionError($e) {
        $error_message = "Database connection failed: " . $e->getMessage();
        
        // Log the error
        error_log($error_message);
        
        // Display user-friendly message based on environment
        if (ENVIRONMENT === 'development') {
            die("Database Error: " . $e->getMessage());
        } else {
            die("Database connection error. Please try again later.");
        }
    }

    /**
     * Test database connection
     * @return bool
     */
    public function testConnection() {
        try {
            $stmt = $this->connection->query("SELECT 1");
            return $stmt !== false;
        } catch (PDOException $e) {
            error_log("Database connection test failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get database server information
     * @return array
     */
    public function getServerInfo() {
        try {
            return [
                'server_version' => $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION),
                'client_version' => $this->connection->getAttribute(PDO::ATTR_CLIENT_VERSION),
                'connection_status' => $this->connection->getAttribute(PDO::ATTR_CONNECTION_STATUS),
                'server_info' => $this->connection->getAttribute(PDO::ATTR_SERVER_INFO)
            ];
        } catch (PDOException $e) {
            error_log("Failed to get server info: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Close database connection
     */
    public function closeConnection() {
        $this->connection = null;
    }

    /**
     * Prevent cloning of singleton
     */
    private function __clone() {}

    /**
     * Prevent unserialization of singleton
     */
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Quick function to get database connection
 * @return PDO
 */
function getDB() {
    return DatabaseConnection::getInstance()->getConnection();
}

/**
 * Quick function to test database connection
 * @return bool
 */
function testDB() {
    return DatabaseConnection::getInstance()->testConnection();
}

?>

