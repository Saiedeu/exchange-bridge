<?php
/**
 * ExchangeBridge - Core Security Protection
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

class Security {
    
    private static $instance = null;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeSecureSession();
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Security();
        }
        return self::$instance;
    }
    
    /**
     * Initialize secure session configuration
     */
    private function initializeSecureSession() {
        // Configure secure session settings
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', 1);
        ini_set('session.entropy_length', 32);
        ini_set('session.hash_function', 'sha256');
        
        // Start session if not already started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerate session ID periodically
        $this->regenerateSessionId();
        
        // Check for session hijacking
        $this->validateSession();
    }
    
    /**
     * Regenerate session ID to prevent session fixation
     */
    private function regenerateSessionId() {
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif ($_SESSION['last_regeneration'] < (time() - 300)) { // 5 minutes
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }
    }
    
    /**
     * Validate session to prevent hijacking
     */
    private function validateSession() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $ipAddress = $this->getClientIp();
        
        if (!isset($_SESSION['user_agent'])) {
            $_SESSION['user_agent'] = hash('sha256', $userAgent);
            $_SESSION['ip_address'] = hash('sha256', $ipAddress);
        } else {
            // Check for session hijacking
            if ($_SESSION['user_agent'] !== hash('sha256', $userAgent) || 
                $_SESSION['ip_address'] !== hash('sha256', $ipAddress)) {
                $this->destroySession();
                $this->logSecurityEvent('SESSION_HIJACKING_ATTEMPT', 
                    "Suspicious session detected from IP: $ipAddress");
                header('Location: /admin/login.php?error=session_invalid');
                exit();
            }
        }
    }
    
    /**
     * Get client IP address safely
     */
    public function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 
                   'HTTP_X_CLUSTER_CLIENT_IP', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 
                   'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                $ip = $_SERVER[$key];
                if (strpos($ip, ',') !== false) {
                    $ip = explode(',', $ip)[0];
                }
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, 
                    FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
    }
    
    /**
     * Generate CSRF token
     */
    public function generateCSRFToken() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public function verifyCSRFToken($token) {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        
        if (!isset($_SESSION['csrf_token']) || 
            !hash_equals($_SESSION['csrf_token'], $token)) {
            $this->logSecurityEvent('CSRF_TOKEN_MISMATCH', 
                "Invalid CSRF token from IP: " . $this->getClientIp());
            return false;
        }
        return true;
    }
    
    /**
     * Sanitize input data
     */
    public function sanitizeInput($input, $type = 'string') {
        if (is_array($input)) {
            foreach ($input as $key => $value) {
                $input[$key] = $this->sanitizeInput($value, $type);
            }
            return $input;
        }
        
        // Remove null bytes
        $input = str_replace(chr(0), '', $input);
        
        switch ($type) {
            case 'email':
                return filter_var(trim($input), FILTER_SANITIZE_EMAIL);
            
            case 'url':
                return filter_var(trim($input), FILTER_SANITIZE_URL);
            
            case 'int':
                return filter_var($input, FILTER_SANITIZE_NUMBER_INT);
            
            case 'float':
                return filter_var($input, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
            
            case 'html':
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
            
            case 'sql':
                return addslashes(trim($input));
            
            case 'filename':
                return preg_replace('/[^a-zA-Z0-9._-]/', '', $input);
            
            case 'alphanum':
                return preg_replace('/[^a-zA-Z0-9]/', '', $input);
            
            default: // string
                return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
        }
    }
    
    /**
     * Validate input data
     */
    public function validateInput($input, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $input[$field] ?? null;
            
            // Required validation
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = ucfirst($field) . ' is required';
                continue;
            }
            
            if (empty($value)) continue;
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = ucfirst($field) . ' must be at least ' . $rule['min_length'] . ' characters';
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = ucfirst($field) . ' must not exceed ' . $rule['max_length'] . ' characters';
            }
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = 'Invalid email format';
                        }
                        break;
                    
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = 'Invalid URL format';
                        }
                        break;
                    
                    case 'int':
                        if (!filter_var($value, FILTER_VALIDATE_INT)) {
                            $errors[$field] = 'Must be a valid integer';
                        }
                        break;
                    
                    case 'float':
                        if (!filter_var($value, FILTER_VALIDATE_FLOAT)) {
                            $errors[$field] = 'Must be a valid number';
                        }
                        break;
                    
                    case 'phone':
                        if (!preg_match('/^[\+]?[0-9\-\(\)\s]+$/', $value)) {
                            $errors[$field] = 'Invalid phone number format';
                        }
                        break;
                    
                    case 'alphanumeric':
                        if (!preg_match('/^[a-zA-Z0-9]+$/', $value)) {
                            $errors[$field] = 'Must contain only letters and numbers';
                        }
                        break;
                }
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['pattern_message'] ?? 'Invalid format';
            }
            
            // Custom validation
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customResult = $rule['custom']($value);
                if ($customResult !== true) {
                    $errors[$field] = $customResult;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Rate limiting functionality
     */
    public function checkRateLimit($action, $limit = 5, $timeWindow = 300) {
        $ip = $this->getClientIp();
        $key = hash('sha256', $action . '_' . $ip);
        
        // Clean old entries
        $this->db->query("DELETE FROM rate_limits WHERE created_at < DATE_SUB(NOW(), INTERVAL ? SECOND)", [$timeWindow]);
        
        // Count current requests
        $count = $this->db->getValue("SELECT COUNT(*) FROM rate_limits WHERE rate_key = ? AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)", 
            [$key, $timeWindow]);
        
        if ($count >= $limit) {
            $this->logSecurityEvent('RATE_LIMIT_EXCEEDED', 
                "Rate limit exceeded for action: $action from IP: $ip");
            return false;
        }
        
        // Record this request
        $this->db->insert('rate_limits', [
            'rate_key' => $key,
            'ip_address' => $ip,
            'action' => $action,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        
        return true;
    }
    
    /**
     * SQL Injection protection for dynamic queries
     */
    public function sanitizeForSQL($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeForSQL'], $input);
        }
        
        // Remove common SQL injection patterns
        $patterns = [
            '/(\s|^)(union|select|insert|update|delete|drop|create|alter|exec|execute)(\s|$)/i',
            '/(\s|^)(or|and)(\s|$)(\d+(\s|$)=(\s|$)\d+|true|false)/i',
            '/(\'|\"|`|;|--|\/\*|\*\/)/i'
        ];
        
        foreach ($patterns as $pattern) {
            $input = preg_replace($pattern, '', $input);
        }
        
        return $input;
    }
    
    /**
     * XSS Protection
     */
    public function antiXSS($input) {
        if (is_array($input)) {
            return array_map([$this, 'antiXSS'], $input);
        }
        
        // Remove dangerous tags and attributes
        $input = preg_replace('/<script\b[^<]*(?:(?!<\/script>)<[^<]*)*<\/script>/mi', '', $input);
        $input = preg_replace('/<iframe\b[^<]*(?:(?!<\/iframe>)<[^<]*)*<\/iframe>/mi', '', $input);
        $input = preg_replace('/on\w+\s*=\s*["\']?[^"\']*["\']?/i', '', $input);
        $input = preg_replace('/javascript:/i', '', $input);
        $input = preg_replace('/vbscript:/i', '', $input);
        $input = preg_replace('/data:/i', '', $input);
        
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * File upload security
     */
    public function validateFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        $errors = [];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $errors[] = 'File upload error: ' . $file['error'];
            return $errors;
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            $errors[] = 'File size exceeds maximum allowed size';
        }
        
        // Check file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!empty($allowedTypes) && !in_array($fileType, $allowedTypes)) {
            $errors[] = 'Invalid file type';
        }
        
        // Check MIME type
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'pdf' => 'application/pdf'
        ];
        
        if (isset($allowedMimes[$fileType])) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            
            if ($mimeType !== $allowedMimes[$fileType]) {
                $errors[] = 'File type mismatch';
            }
        }
        
        // Check for malicious content
        $content = file_get_contents($file['tmp_name']);
        if (preg_match('/<\?php|<script|javascript:/i', $content)) {
            $errors[] = 'Malicious content detected';
        }
        
        return $errors;
    }
    
    /**
     * Log security events
     */
    public function logSecurityEvent($eventType, $description) {
        try {
            $this->db->insert('security_logs', [
                'event_type' => $eventType,
                'description' => $description,
                'ip_address' => $this->getClientIp(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'user_id' => $_SESSION['user_id'] ?? null,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (Exception $e) {
            error_log("Failed to log security event: " . $e->getMessage());
        }
    }
    
    /**
     * Destroy session securely
     */
    public function destroySession() {
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
    }
    
    /**
     * Check if user is banned
     */
    public function checkBanStatus() {
        $ip = $this->getClientIp();
        
        $banned = $this->db->getValue(
            "SELECT COUNT(*) FROM banned_ips WHERE ip_address = ? AND (expires_at IS NULL OR expires_at > NOW())",
            [$ip]
        );
        
        if ($banned > 0) {
            http_response_code(403);
            exit('Access denied: Your IP has been banned');
        }
    }
    
    /**
     * Password strength validation
     */
    public function validatePasswordStrength($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }
        
        if (!preg_match('/[^A-Za-z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }
        
        return $errors;
    }
    
    /**
     * Generate secure random token
     */
    public function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
}