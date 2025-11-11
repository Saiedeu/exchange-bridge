<?php
/**
 * Exchange Bridge - Secure Configuration System
 * 
 * @package     ExchangeBridge
 * @author      Security Enhanced Version
 * @version     2.0.0
 * @created     2025-09-01
 */

// Prevent direct access
if (!defined('EB_SCRIPT_RUNNING')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

/**
 * Secure Configuration Manager
 */
class SecureConfig {
    private static $config = [];
    private static $initialized = false;
    private static $encryptionKey = null;
    
    /**
     * Initialize secure configuration
     */
    public static function init() {
        if (self::$initialized) {
            return;
        }
        
        // Generate or load encryption key
        self::$encryptionKey = self::getOrCreateEncryptionKey();
        
        // Load environment variables if available
        self::loadEnvironmentConfig();
        
        // Load secure defaults
        self::loadSecureDefaults();
        
        self::$initialized = true;
    }
    
    /**
     * Get configuration value
     */
    public static function get($key, $default = null) {
        self::init();
        return self::$config[$key] ?? $default;
    }
    
    /**
     * Set configuration value (encrypted if sensitive)
     */
    public static function set($key, $value, $encrypt = false) {
        self::init();
        
        if ($encrypt && self::$encryptionKey) {
            $value = self::encrypt($value);
        }
        
        self::$config[$key] = $value;
    }
    
    /**
     * Get decrypted sensitive value
     */
    public static function getSecure($key, $default = null) {
        self::init();
        $value = self::$config[$key] ?? $default;
        
        if ($value && self::$encryptionKey) {
            $decrypted = self::decrypt($value);
            return $decrypted !== false ? $decrypted : $default;
        }
        
        return $value;
    }
    
    /**
     * Load environment configuration
     */
    private static function loadEnvironmentConfig() {
        // Try to load from .env file
        $envFile = __DIR__ . '/../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && !str_starts_with(trim($line), '#')) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value, '"\'');
                }
            }
        }
        
        // License system configuration
        self::$config['LICENSE_API_URL'] = $_ENV['LICENSE_API_URL'] ?? 'https://eb-admin.rf.gd/api.php';
        self::$config['LICENSE_API_KEY'] = $_ENV['LICENSE_API_KEY'] ?? null;
        self::$config['LICENSE_SALT'] = $_ENV['LICENSE_SALT'] ?? null;
        self::$config['LICENSE_CHECK_INTERVAL'] = (int)($_ENV['LICENSE_CHECK_INTERVAL'] ?? 3600);
        self::$config['LICENSE_GRACE_PERIOD'] = (int)($_ENV['LICENSE_GRACE_PERIOD'] ?? 86400); // Reduced to 1 day
        
        // Database configuration
        self::$config['DB_HOST'] = $_ENV['DB_HOST'] ?? 'localhost';
        self::$config['DB_USER'] = $_ENV['DB_USER'] ?? '';
        self::$config['DB_PASS'] = $_ENV['DB_PASS'] ?? '';
        self::$config['DB_NAME'] = $_ENV['DB_NAME'] ?? '';
        
        // Security configuration
        self::$config['CSRF_TOKEN_SECRET'] = $_ENV['CSRF_TOKEN_SECRET'] ?? null;
        self::$config['SESSION_ENCRYPTION_KEY'] = $_ENV['SESSION_ENCRYPTION_KEY'] ?? null;
    }
    
    /**
     * Load secure defaults
     */
    private static function loadSecureDefaults() {
        // Generate secure random values if not provided
        if (!self::$config['LICENSE_API_KEY']) {
            self::$config['LICENSE_API_KEY'] = self::generateSecureKey(32);
        }
        
        if (!self::$config['LICENSE_SALT']) {
            self::$config['LICENSE_SALT'] = self::generateSecureKey(64);
        }
        
        if (!self::$config['CSRF_TOKEN_SECRET']) {
            self::$config['CSRF_TOKEN_SECRET'] = self::generateSecureKey(32);
        }
        
        if (!self::$config['SESSION_ENCRYPTION_KEY']) {
            self::$config['SESSION_ENCRYPTION_KEY'] = self::generateSecureKey(32);
        }
        
        // Security settings
        self::$config['SSL_VERIFY_PEER'] = true;
        self::$config['SSL_VERIFY_HOST'] = true;
        self::$config['MAX_LOGIN_ATTEMPTS'] = 5;
        self::$config['LOGIN_LOCKOUT_TIME'] = 900; // 15 minutes
        self::$config['API_RATE_LIMIT'] = 60; // requests per hour
        self::$config['DEBUG_MODE'] = false;
    }
    
    /**
     * Generate secure random key
     */
    private static function generateSecureKey($length = 32) {
        if (function_exists('random_bytes')) {
            return bin2hex(random_bytes($length));
        } elseif (function_exists('openssl_random_pseudo_bytes')) {
            return bin2hex(openssl_random_pseudo_bytes($length));
        } else {
            // Fallback (less secure)
            return hash('sha256', uniqid(mt_rand(), true) . microtime(true));
        }
    }
    
    /**
     * Get or create encryption key
     */
    private static function getOrCreateEncryptionKey() {
        $keyFile = __DIR__ . '/../config/.encryption_key';
        
        // Create config directory if it doesn't exist
        $configDir = dirname($keyFile);
        if (!is_dir($configDir)) {
            mkdir($configDir, 0700, true);
        }
        
        if (file_exists($keyFile)) {
            $key = file_get_contents($keyFile);
            if (strlen($key) === 64) { // 32 bytes hex encoded
                return $key;
            }
        }
        
        // Generate new key
        $key = self::generateSecureKey(32);
        file_put_contents($keyFile, $key);
        chmod($keyFile, 0600);
        
        // Create .htaccess protection
        $htaccessFile = $configDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            file_put_contents($htaccessFile, "Order deny,allow\nDeny from all\n");
        }
        
        return $key;
    }
    
    /**
     * Encrypt sensitive data
     */
    private static function encrypt($data) {
        if (!self::$encryptionKey || !function_exists('openssl_encrypt')) {
            return $data;
        }
        
        $key = hex2bin(self::$encryptionKey);
        $iv = openssl_random_pseudo_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        
        return base64_encode($iv . $encrypted);
    }
    
    /**
     * Decrypt sensitive data
     */
    private static function decrypt($data) {
        if (!self::$encryptionKey || !function_exists('openssl_decrypt')) {
            return $data;
        }
        
        $data = base64_decode($data);
        if ($data === false || strlen($data) < 16) {
            return false;
        }
        
        $key = hex2bin(self::$encryptionKey);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    /**
     * Save configuration to secure file
     */
    public static function save() {
        $configFile = __DIR__ . '/../config/secure_config.php';
        $configDir = dirname($configFile);
        
        if (!is_dir($configDir)) {
            mkdir($configDir, 0700, true);
        }
        
        $sensitiveKeys = ['LICENSE_API_KEY', 'DB_PASS', 'CSRF_TOKEN_SECRET', 'SESSION_ENCRYPTION_KEY'];
        $configData = [];
        
        foreach (self::$config as $key => $value) {
            if (in_array($key, $sensitiveKeys)) {
                $configData[$key] = self::encrypt($value);
            } else {
                $configData[$key] = $value;
            }
        }
        
        $content = "<?php\n// Secure Configuration File - DO NOT EDIT MANUALLY\nreturn " . var_export($configData, true) . ";\n";
        file_put_contents($configFile, $content);
        chmod($configFile, 0600);
    }
    
    /**
     * Validate configuration integrity
     */
    public static function validateIntegrity() {
        $required = ['LICENSE_API_KEY', 'LICENSE_SALT', 'CSRF_TOKEN_SECRET'];
        
        foreach ($required as $key) {
            if (empty(self::get($key))) {
                throw new Exception("Missing required configuration: $key");
            }
        }
        
        return true;
    }
}
