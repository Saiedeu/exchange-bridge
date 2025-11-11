<?php
/**
 * Exchange Bridge - Secure Application Core
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

// Load secure bootstrap
require_once __DIR__ . '/secure_bootstrap.php';

/**
 * Secure Application Manager
 */
class SecureApp {
    private static $instance = null;
    private $rootPath;
    private $configDir;
    private $securityLevel = 'HIGH';
    private $verificationData = null;
    
    // Security patterns to detect tampering
    private $tamperingPatterns = [
        '/nulled|cracked|pirated/i',
        '/remove.*?license|bypass.*?license/i',
        '/free.*?download|warez/i',
        '/\$license.*?=.*?true/i',
        '/function\s+crack_|function\s+bypass_/i',
        '/define.*?license.*?bypass/i',
        '/license.*?check.*?false/i'
    ];
    
    private function __construct() {
        $this->rootPath = $this->findRootPath();
        $this->configDir = $this->rootPath . '/config';
        $this->initializeSecurity();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Find application root path
     */
    private function findRootPath() {
        $scriptPath = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
        $currentDir = dirname($scriptPath);
        
        // Look for install.lock file up to 5 levels up
        for ($i = 0; $i < 5; $i++) {
            if (file_exists($currentDir . '/config/install.lock')) {
                return $currentDir;
            }
            $currentDir = dirname($currentDir);
        }
        
        return dirname(dirname(__FILE__));
    }
    
    /**
     * Initialize security systems
     */
    private function initializeSecurity() {
        $this->createSecureDirectories();
        $this->initializeSecurityLog();
        $this->checkSystemIntegrity();
        $this->validateCriticalFiles();
    }
    
    /**
     * Create secure directories with proper permissions
     */
    private function createSecureDirectories() {
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0700, true);
        }
        
        // Enhanced .htaccess protection
        $htaccessFile = $this->configDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = <<<EOD
# Exchange Bridge Security Protection
Order deny,allow
Deny from all

# Block common attack patterns
<Files "*">
    Header always set X-Robots-Tag "noindex, nofollow, nosnippet, noarchive"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
</Files>

# Additional security rules
RewriteEngine On
RewriteCond %{QUERY_STRING} (union|select|insert|drop|delete|update|create|alter) [NC]
RewriteRule .* - [F,L]

# Block direct PHP execution
<Files "*.php">
    Deny from all
</Files>
EOD;
            @file_put_contents($htaccessFile, $htaccessContent);
        }
        
        // Create index.php protection
        $indexFile = $this->configDir . '/index.php';
        if (!file_exists($indexFile)) {
            @file_put_contents($indexFile, '<?php http_response_code(403); exit(\'Access Denied\'); ?>');
        }
    }
    
    /**
     * Initialize security logging
     */
    private function initializeSecurityLog() {
        $logFile = $this->configDir . '/security.log';
        if (!file_exists($logFile)) {
            @file_put_contents($logFile, "# Exchange Bridge Security Log\n");
            @chmod($logFile, 0640);
        }
        
        $this->logSecurityEvent('ACCESS', 'System access from ' . $this->getClientIP());
    }
    
    /**
     * Check system integrity for tampering
     */
    private function checkSystemIntegrity() {
        $suspiciousFiles = [];
        $criticalFiles = [
            $this->rootPath . '/index.php',
            $this->rootPath . '/includes/functions.php',
            $this->rootPath . '/includes/auth.php',
            $this->rootPath . '/config/config.php'
        ];
        
        foreach ($criticalFiles as $file) {
            if (file_exists($file)) {
                $content = @file_get_contents($file);
                if ($content) {
                    foreach ($this->tamperingPatterns as $pattern) {
                        if (preg_match($pattern, $content)) {
                            $suspiciousFiles[] = basename($file);
                            break;
                        }
                    }
                }
            }
        }
        
        if (!empty($suspiciousFiles)) {
            $this->logSecurityEvent('TAMPERING', 'Suspicious content detected in: ' . implode(', ', $suspiciousFiles));
            $this->handleSecurityViolation('Code tampering detected in system files');
        }
    }
    
    /**
     * Validate critical system files exist
     */
    private function validateCriticalFiles() {
        $criticalFiles = [
            '/config/install.lock' => 'Installation lock file',
            '/config/license.php' => 'License configuration',
            '/includes/db.php' => 'Database connection',
            '/includes/functions.php' => 'Core functions',
            '/includes/security.php' => 'Security system'
        ];
        
        $missingFiles = [];
        foreach ($criticalFiles as $file => $description) {
            if (!file_exists($this->rootPath . $file)) {
                $missingFiles[] = $description;
            }
        }
        
        if (!empty($missingFiles)) {
            $this->logSecurityEvent('INTEGRITY', 'Missing critical files: ' . implode(', ', $missingFiles));
            $this->handleSecurityViolation('Critical system files are missing');
        }
    }
    
    /**
     * Main license verification method
     */
    public function verifyLicense() {
        try {
            // Check installation status
            if (!$this->verifyInstallation()) {
                throw new Exception('Installation verification failed');
            }
            
            // Load license configuration
            if (!$this->loadLicenseConfig()) {
                throw new Exception('License configuration invalid');
            }
            
            // Validate license key format
            if (!$this->validateLicenseKeyFormat()) {
                throw new Exception('Invalid license key format');
            }
            
            // Check local verification first
            if (!$this->checkLocalVerification()) {
                // Perform server verification
                if (!$this->performServerVerification()) {
                    throw new Exception('License verification failed');
                }
            }
            
            // Validate domain authorization
            if (!$this->validateDomainAuthorization()) {
                throw new Exception('Domain not authorized for this license');
            }
            
            // Final security validation
            if (!$this->performSecurityValidation()) {
                throw new Exception('Security validation failed');
            }
            
            $this->logSecurityEvent('LICENSE_OK', 'License verification successful');
            return true;
            
        } catch (Exception $e) {
            $this->logSecurityEvent('LICENSE_FAIL', $e->getMessage());
            $this->handleSecurityViolation($e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify installation status
     */
    private function verifyInstallation() {
        $lockFile = $this->configDir . '/install.lock';
        if (!file_exists($lockFile)) {
            return false;
        }
        
        try {
            $lockData = include $lockFile;
            if (!is_array($lockData) || !isset($lockData['installed']) || !$lockData['installed']) {
                return false;
            }
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Load license configuration
     */
    private function loadLicenseConfig() {
        $licenseFile = $this->configDir . '/license.php';
        if (!file_exists($licenseFile)) {
            return false;
        }
        
        try {
            include_once $licenseFile;
            return defined('LICENSE_KEY') && !empty(LICENSE_KEY);
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Validate license key format
     */
    private function validateLicenseKeyFormat() {
        if (!defined('LICENSE_KEY')) {
            return false;
        }
        
        $licenseKey = LICENSE_KEY;
        $validFormats = [
            '/^EB-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/',
            '/^[A-Z0-9]{32}$/',
            '/^[A-Z0-9\-]{20,50}$/'
        ];
        
        foreach ($validFormats as $format) {
            if (preg_match($format, $licenseKey)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check local verification cache
     */
    private function checkLocalVerification() {
        $verificationFile = $this->configDir . '/secure_verification.php';
        if (!file_exists($verificationFile)) {
            return false;
        }
        
        try {
            $this->verificationData = include $verificationFile;
            if (!is_array($this->verificationData)) {
                return false;
            }
            
            // Verify file integrity
            if (!$this->verifyVerificationFileIntegrity($this->verificationData)) {
                $this->logSecurityEvent('INTEGRITY_FAIL', 'Verification file integrity check failed');
                return false;
            }
            
            // Check status
            if (!isset($this->verificationData['status']) || $this->verificationData['status'] !== 'active') {
                return false;
            }
            
            // Check if verification is still valid
            $lastCheck = $this->verificationData['last_check'] ?? 0;
            $checkInterval = defined('LICENSE_CHECK_INTERVAL') ? LICENSE_CHECK_INTERVAL : 3600;
            
            if ((time() - $lastCheck) > $checkInterval) {
                return false;
            }
            
            return true;
            
        } catch (Exception $e) {
            $this->logSecurityEvent('VERIFICATION_ERROR', 'Local verification error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Verify verification file integrity using enhanced cryptography
     */
    private function verifyVerificationFileIntegrity($data) {
        if (!isset($data['license_key'], $data['domain'], $data['hash'], $data['nonce'])) {
            return false;
        }
        
        $salt = defined('LICENSE_SALT') ? LICENSE_SALT : SecureConfig::get('LICENSE_SALT', '');
        if (empty($salt)) {
            return false;
        }
        
        $nonce = $data['nonce'];
        $hashInput = $data['license_key'] . $data['domain'] . $salt . $nonce . $data['status'];
        $expectedHash = hash('sha256', $hashInput);
        
        return hash_equals($expectedHash, $data['hash']);
    }
    
    /**
     * Perform server verification using secure license verifier
     */
    private function performServerVerification() {
        try {
            $verifier = new SecureLicenseVerifier();
            return $verifier->verifyLicense();
        } catch (Exception $e) {
            $this->logSecurityEvent('SERVER_VERIFICATION_FAIL', $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate domain authorization
     */
    private function validateDomainAuthorization() {
        $currentDomain = $this->getCurrentDomain();
        
        if ($this->verificationData && isset($this->verificationData['domain'])) {
            $licensedDomain = $this->verificationData['domain'];
            
            // Wildcard license
            if ($licensedDomain === '*') {
                return true;
            }
            
            // Exact match
            if ($licensedDomain === $currentDomain) {
                return true;
            }
            
            // Subdomain wildcard (e.g., *.example.com)
            if (str_starts_with($licensedDomain, '*.')) {
                $baseDomain = substr($licensedDomain, 2);
                if (str_ends_with($currentDomain, '.' . $baseDomain) || $currentDomain === $baseDomain) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Perform additional security validation
     */
    private function performSecurityValidation() {
        // Check for suspicious activity
        $clientIP = $this->getClientIP();
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Log access for monitoring
        $this->logSecurityEvent('ACCESS_VALIDATION', "IP: $clientIP, UA: " . substr($userAgent, 0, 100));
        
        // Additional security checks can be added here
        return true;
    }
    
    /**
     * Get current domain
     */
    private function getCurrentDomain() {
        $domain = $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $domain = preg_replace('/^www\./i', '', $domain);
        $domain = preg_replace('/:\d+$/', '', $domain);
        return strtolower(trim($domain));
    }
    
    /**
     * Get client IP address
     */
    private function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',
            'HTTP_CLIENT_IP',
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_FORWARDED',
            'HTTP_X_CLUSTER_CLIENT_IP',
            'HTTP_FORWARDED_FOR',
            'HTTP_FORWARDED',
            'REMOTE_ADDR'
        ];
        
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $ips = explode(',', $_SERVER[$header]);
                foreach ($ips as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($type, $message) {
        $logFile = $this->configDir . '/security.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $ip = $this->getClientIP();
        $domain = $this->getCurrentDomain();
        
        $logEntry = "$timestamp [$type] $message (IP: $ip, Domain: $domain)\n";
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Keep log file manageable
        if (file_exists($logFile) && filesize($logFile) > 5242880) { // 5MB
            $lines = file($logFile);
            $lines = array_slice($lines, -1000);
            file_put_contents($logFile, implode('', $lines));
        }
    }
    
    /**
     * Handle security violations
     */
    private function handleSecurityViolation($message) {
        // Log the violation
        error_log("Exchange Bridge Security Violation: $message");
        
        // In production, you might want to:
        // - Send email alerts
        // - Block IP addresses
        // - Trigger additional security measures
        
        // For now, we'll throw an exception to stop execution
        throw new Exception($message);
    }
    
    /**
     * Get application root path
     */
    public function getRootPath() {
        return $this->rootPath;
    }
    
    /**
     * Get config directory path
     */
    public function getConfigDir() {
        return $this->configDir;
    }
}

// Initialize secure application
$secureApp = SecureApp::getInstance();

// Verify license on every request
try {
    $secureApp->verifyLicense();
} catch (Exception $e) {
    // License verification failed - application will not continue
    error_log('Exchange Bridge License Error: ' . $e->getMessage());
    
    // Show user-friendly error page
    http_response_code(403);
    include __DIR__ . '/../templates/license_error.php';
    exit;
}
