<?php declare(strict_types=1);
/**
 * Database Manager for LookingGlass
 * 
 * Handles database connections and configuration management
 */

class DatabaseManager {
    private static $instance = null;
    private $pdo;
    private $config_cache = [];
    
    private function __construct() {
        if (!file_exists(__DIR__ . '/db_config.php')) {
            throw new Exception('Database configuration file not found');
        }
        
        // Always include to ensure variables are available in this scope
        require __DIR__ . '/db_config.php';

        // Validate required variables from db_config.php
        if (!isset($db_host, $db_name, $db_user, $db_pass) || $db_host === '' || $db_name === '' || $db_user === '') {
            throw new Exception('Database configuration variables are missing or empty in db_config.php');
        }

        try {
            $this->pdo = new PDO(
                "mysql:host=$db_host;dbname=$db_name;charset=utf8mb4",
                $db_user,
                $db_pass,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false
                ]
            );
        } catch (PDOException $e) {
            throw new Exception('Database connection failed: ' . $e->getMessage());
        }
    }
    
    public static function getInstance(): DatabaseManager {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection(): PDO {
        return $this->pdo;
    }
    
    /**
     * Get configuration value from database
     */
    public function getConfig(string $key, $default = null) {
        // Check cache first
        if (isset($this->config_cache[$key])) {
            return $this->config_cache[$key];
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT config_value, config_type FROM lg_config WHERE config_key = ?");
            $stmt->execute([$key]);
            $result = $stmt->fetch();
            
            if (!$result) {
                $this->config_cache[$key] = $default;
                return $default;
            }
            
            $value = $this->convertConfigValue($result['config_value'], $result['config_type']);
            $this->config_cache[$key] = $value;
            
            return $value;
        } catch (Exception $e) {
            return $default;
        }
    }
    
    /**
     * Set configuration value in database
     */
    public function setConfig(string $key, $value, string $type = 'string'): bool {
        try {
            $config_value = $this->prepareConfigValue($value, $type);
            
            $stmt = $this->pdo->prepare(
                "INSERT INTO lg_config (config_key, config_value, config_type) 
                 VALUES (?, ?, ?) 
                 ON DUPLICATE KEY UPDATE config_value = VALUES(config_value), config_type = VALUES(config_type)"
            );
            
            $result = $stmt->execute([$key, $config_value, $type]);
            
            // Update cache
            $this->config_cache[$key] = $value;
            
            return $result;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get all configuration values
     */
    public function getAllConfig(): array {
        try {
            $stmt = $this->pdo->query("SELECT config_key, config_value, config_type FROM lg_config");
            $configs = [];
            
            while ($row = $stmt->fetch()) {
                $configs[$row['config_key']] = $this->convertConfigValue(
                    $row['config_value'], 
                    $row['config_type']
                );
            }
            
            $this->config_cache = $configs;
            return $configs;
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Get theme configuration
     */
    public function getTheme(string $theme_name): ?array {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM lg_themes WHERE theme_name = ? AND is_active = 1");
            $stmt->execute([$theme_name]);
            return $stmt->fetch() ?: null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Get all active themes
     */
    public function getAllThemes(): array {
        try {
            $stmt = $this->pdo->query("SELECT * FROM lg_themes WHERE is_active = 1 ORDER BY theme_name");
            return $stmt->fetchAll();
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Authenticate admin user
     */
    public function authenticateAdmin(string $username, string $password): ?array {
        try {
            $stmt = $this->pdo->prepare(
                "SELECT id, username, password, email FROM lg_admins 
                 WHERE username = ? AND is_active = 1"
            );
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Update last login
                $update_stmt = $this->pdo->prepare(
                    "UPDATE lg_admins SET last_login = CURRENT_TIMESTAMP WHERE id = ?"
                );
                $update_stmt->execute([$user['id']]);
                
                unset($user['password']);
                return $user;
            }
            
            return null;
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Convert database value to proper PHP type
     */
    private function convertConfigValue($value, string $type) {
        switch ($type) {
            case 'boolean':
                return (bool) $value;
            case 'integer':
                return (int) $value;
            case 'json':
                return json_decode($value, true) ?: [];
            default:
                return $value;
        }
    }
    
    /**
     * Prepare value for database storage
     */
    private function prepareConfigValue($value, string $type): string {
        switch ($type) {
            case 'boolean':
                return $value ? '1' : '0';
            case 'integer':
                return (string) (int) $value;
            case 'json':
                return json_encode($value);
            default:
                return (string) $value;
        }
    }
}

/**
 * Helper function to get configuration values
 */
function getDbConfig(string $key, $default = null) {
    try {
        return DatabaseManager::getInstance()->getConfig($key, $default);
    } catch (Exception $e) {
        return $default;
    }
}

/**
 * Helper function to set configuration values
 */
function setDbConfig(string $key, $value, string $type = 'string'): bool {
    try {
        return DatabaseManager::getInstance()->setConfig($key, $value, $type);
    } catch (Exception $e) {
        return false;
    }
}