<?php
/**
 * Exchange Bridge - Secure License Verification System
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

// Load secure configuration
require_once __DIR__ . '/secure_config.php';

/**
 * Secure License Verifier with Enhanced Cryptography
 */
class SecureLicenseVerifier {
    private $licenseKey;
    private $domain;
    private $ip;
    private $verificationFile;
    private $configDir;
    private $rateLimitFile;
    private $lastRequestTime = 0;
    private $requestCount = 0;
    
    public function __construct() {
        SecureConfig::init();
        
        $this->configDir = __DIR__ . '/../config';
        $this->verificationFile = $this->configDir . '/secure_verification.php';
        $this->rateLimitFile = $this->configDir . '/rate_limit.php';
        $this->domain = $this->getCurrentDomain();
        $this->ip = $this->getClientIP();
        $this->licenseKey = SecureConfig::get('LICENSE_KEY');
        
        $this->ensureSecureDirectories();
        $this->loadRateLimitData();
    }
    
    /**
     * Ensure secure directory structure
     */
    private function ensureSecureDirectories() {
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0700, true);
        }
        
        // Create comprehensive .htaccess protection
        $htaccessFile = $this->configDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = <<<EOD
# Deny all access to config directory
Order deny,allow
Deny from all

# Additional security headers
<Files "*">
    Header always set X-Robots-Tag "noindex, nofollow, nosnippet, noarchive"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-Frame-Options "DENY"
</Files>

# Block common attack patterns
RewriteEngine On
RewriteCond %{QUERY_STRING} (union|select|insert|drop|delete|update|create|alter) [NC]
RewriteRule .* - [F,L]
EOD;
            file_put_contents($htaccessFile, $htaccessContent);
        }
        
        // Create index.php to prevent directory listing
        $indexFile = $this->configDir . '/index.php';
        if (!file_exists($indexFile)) {
            file_put_contents($indexFile, "<?php http_response_code(403); exit('Access denied'); ?>");
        }
    }
    
    /**
     * Get current domain with enhanced validation
     */
    private function getCurrentDomain() {
        $domain = '';
        
        // Try multiple methods with validation
        $headers = ['HTTP_HOST', 'SERVER_NAME', 'HTTP_X_FORWARDED_HOST'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                $domain = $_SERVER[$header];
                break;
            }
        }
        
        // Sanitize and validate domain
        $domain = preg_replace('/^www\./i', '', $domain);
        $domain = preg_replace('/:\d+$/', '', $domain);
        $domain = strtolower(trim($domain));
        
        // Validate domain format
        if (!filter_var($domain, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME)) {
            throw new Exception('Invalid domain format detected');
        }
        
        return $domain;
    }
    
    /**
     * Get client IP with enhanced detection
     */
    private function getClientIP() {
        $headers = [
            'HTTP_CF_CONNECTING_IP',     // Cloudflare
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
     * Load rate limiting data
     */
    private function loadRateLimitData() {
        if (file_exists($this->rateLimitFile)) {
            $data = include $this->rateLimitFile;
            if (is_array($data)) {
                $this->lastRequestTime = $data['last_request'] ?? 0;
                $this->requestCount = $data['count'] ?? 0;
                
                // Reset count if hour has passed
                if (time() - $this->lastRequestTime > 3600) {
                    $this->requestCount = 0;
                }
            }
        }
    }
    
    /**
     * Check rate limiting
     */
    private function checkRateLimit() {
        $maxRequests = SecureConfig::get('API_RATE_LIMIT', 60);
        $currentTime = time();
        
        // Reset counter if hour has passed
        if ($currentTime - $this->lastRequestTime > 3600) {
            $this->requestCount = 0;
        }
        
        if ($this->requestCount >= $maxRequests) {
            throw new Exception('Rate limit exceeded. Please try again later.');
        }
        
        $this->requestCount++;
        $this->lastRequestTime = $currentTime;
        
        // Save rate limit data
        $data = [
            'last_request' => $currentTime,
            'count' => $this->requestCount,
            'ip' => $this->ip,
            'domain' => $this->domain
        ];
        
        $content = "<?php\n// Rate limit data - DO NOT EDIT\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($this->rateLimitFile, $content, LOCK_EX);
        chmod($this->rateLimitFile, 0600);
    }
    
    /**
     * Main license verification with enhanced security
     */
    public function verifyLicense() {
        // Check rate limiting first
        $this->checkRateLimit();
        
        if (empty($this->licenseKey)) {
            throw new Exception('License key not found. Please reinstall the script.');
        }
        
        // Check verification file
        if (!file_exists($this->verificationFile)) {
            return $this->checkWithServer(true);
        }
        
        // Load and validate verification data
        $verificationData = $this->loadVerificationData();
        
        // Verify file integrity with enhanced cryptography
        if (!$this->verifyFileIntegrity($verificationData)) {
            $this->logSecurityEvent('File integrity check failed', 'high');
            @unlink($this->verificationFile);
            throw new Exception('License verification file has been tampered with.');
        }
        
        // Check license status
        if (isset($verificationData['status']) && $verificationData['status'] !== 'active') {
            throw new Exception('Your license has been deactivated. Please contact support.');
        }
        
        // Enhanced domain validation
        if (!$this->validateDomain($verificationData['domain'])) {
            $this->logSecurityEvent('Domain validation failed', 'high');
            throw new Exception('This license is not valid for this domain: ' . $this->domain);
        }
        
        // Check expiration with buffer
        if (isset($verificationData['expires']) && $verificationData['expires'] > 0) {
            $bufferTime = 86400; // 1 day buffer
            if (time() > ($verificationData['expires'] - $bufferTime)) {
                if (time() > $verificationData['expires']) {
                    throw new Exception('License has expired on ' . date('Y-m-d', $verificationData['expires']));
                } else {
                    $this->logSecurityEvent('License expiring soon', 'medium');
                }
            }
        }
        
        // Check server verification interval
        $lastCheck = $verificationData['last_check'] ?? 0;
        $checkInterval = SecureConfig::get('LICENSE_CHECK_INTERVAL', 3600);
        
        if ((time() - $lastCheck) > $checkInterval) {
            return $this->checkWithServer(false);
        }
        
        return true;
    }
    
    /**
     * Load verification data with validation
     */
    private function loadVerificationData() {
        try {
            $data = include $this->verificationFile;
            
            if (!is_array($data)) {
                throw new Exception('Invalid verification file format');
            }
            
            $requiredFields = ['license_key', 'domain', 'hash', 'status', 'last_check', 'nonce'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }
            
            return $data;
        } catch (Exception $e) {
            $this->logSecurityEvent('Failed to load verification data: ' . $e->getMessage(), 'high');
            throw new Exception('Corrupted verification file. Please reinstall your license.');
        }
    }
    
    /**
     * Enhanced file integrity verification using SHA-256
     */
    private function verifyFileIntegrity($data) {
        if (!isset($data['license_key'], $data['domain'], $data['hash'], $data['nonce'])) {
            return false;
        }
        
        // Use dynamic salt with nonce
        $salt = SecureConfig::get('LICENSE_SALT');
        $nonce = $data['nonce'];
        
        // Create hash with multiple components for enhanced security
        $hashInput = $data['license_key'] . $data['domain'] . $salt . $nonce . $data['status'];
        $expectedHash = hash('sha256', $hashInput);
        
        return hash_equals($expectedHash, $data['hash']);
    }
    
    /**
     * Enhanced domain validation
     */
    private function validateDomain($licensedDomain) {
        if ($licensedDomain === '*') {
            return true;
        }
        
        // Exact match
        if ($licensedDomain === $this->domain) {
            return true;
        }
        
        // Check for subdomain wildcards (e.g., *.example.com)
        if (str_starts_with($licensedDomain, '*.')) {
            $baseDomain = substr($licensedDomain, 2);
            if (str_ends_with($this->domain, '.' . $baseDomain) || $this->domain === $baseDomain) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Check license with server using enhanced security
     */
    private function checkWithServer($isFirstTime = false) {
        $apiUrl = SecureConfig::get('LICENSE_API_URL');
        $apiKey = SecureConfig::getSecure('LICENSE_API_KEY');
        
        if (empty($apiKey)) {
            throw new Exception('License API key not configured');
        }
        
        // Create secure request with timestamp and signature
        $timestamp = time();
        $nonce = bin2hex(random_bytes(16));
        
        $postData = [
            'action' => 'verify',
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'ip' => $this->ip,
            'timestamp' => $timestamp,
            'nonce' => $nonce,
            'product' => 'exchange_bridge',
            'version' => '2.0.0'
        ];
        
        // Create request signature
        $signature = $this->createRequestSignature($postData, $apiKey);
        $postData['signature'] = $signature;
        $postData['api_key'] = $apiKey;
        
        try {
            $response = $this->makeSecureApiRequest($apiUrl, $postData);
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['status'])) {
                throw new Exception('Invalid response from license server');
            }
            
            if ($result['status'] === 'success') {
                // Verify response signature if provided
                if (isset($result['signature'])) {
                    if (!$this->verifyResponseSignature($result, $apiKey)) {
                        throw new Exception('Invalid server response signature');
                    }
                }
                
                // Check server-side license status
                if (isset($result['license_status']) && $result['license_status'] !== 'active') {
                    $this->markLicenseInactive();
                    throw new Exception('Your license has been deactivated on the server.');
                }
                
                // Update verification file with enhanced security
                $this->updateVerificationFile($result, $nonce);
                return true;
            } else {
                if (!$isFirstTime) {
                    $this->markLicenseInactive();
                }
                throw new Exception($result['message'] ?? 'License verification failed');
            }
        } catch (Exception $e) {
            if ($isFirstTime) {
                throw $e;
            }
            
            return $this->handleServerError();
        }
    }
    
    /**
     * Create request signature for API security
     */
    private function createRequestSignature($data, $apiKey) {
        ksort($data);
        $queryString = http_build_query($data);
        return hash_hmac('sha256', $queryString, $apiKey);
    }
    
    /**
     * Verify response signature
     */
    private function verifyResponseSignature($response, $apiKey) {
        if (!isset($response['signature'])) {
            return false;
        }
        
        $signature = $response['signature'];
        unset($response['signature']);
        
        ksort($response);
        $queryString = http_build_query($response);
        $expectedSignature = hash_hmac('sha256', $queryString, $apiKey);
        
        return hash_equals($expectedSignature, $signature);
    }
    
    /**
     * Make secure API request with SSL verification
     */
    private function makeSecureApiRequest($url, $data) {
        $sslVerifyPeer = SecureConfig::get('SSL_VERIFY_PEER', true);
        $sslVerifyHost = SecureConfig::get('SSL_VERIFY_HOST', true);
        
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => $sslVerifyPeer,
                CURLOPT_SSL_VERIFYHOST => $sslVerifyHost ? 2 : 0,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_CONNECTTIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => false,
                CURLOPT_MAXREDIRS => 0,
                CURLOPT_USERAGENT => 'Exchange Bridge Secure License Checker v2.0',
                CURLOPT_HTTPHEADER => [
                    'Accept: application/json',
                    'Content-Type: application/x-www-form-urlencoded',
                    'X-Request-ID: ' . bin2hex(random_bytes(8))
                ]
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error = curl_error($ch);
            curl_close($ch);
            
            if ($response === false || !empty($error)) {
                throw new Exception("cURL error: " . ($error ?: 'Unknown error'));
            }
            
            if ($httpCode !== 200) {
                throw new Exception("API returned HTTP code: $httpCode");
            }
            
            return $response;
        } else {
            throw new Exception('cURL extension is required for secure license verification');
        }
    }
    
    /**
     * Update verification file with enhanced security
     */
    private function updateVerificationFile($serverResponse, $nonce) {
        $salt = SecureConfig::get('LICENSE_SALT');
        $timestamp = time();
        
        $data = [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'status' => 'active',
            'nonce' => $nonce,
            'last_check' => $timestamp,
            'validation_type' => $serverResponse['validation_type'] ?? 'automatic',
            'server_status' => $serverResponse['license_status'] ?? 'active',
            'expires' => isset($serverResponse['expires']) ? strtotime($serverResponse['expires']) : null,
            'created_at' => $timestamp,
            'version' => '2.0.0',
            'checksum' => hash('sha256', $this->licenseKey . $this->domain . $timestamp)
        ];
        
        // Create enhanced hash
        $hashInput = $data['license_key'] . $data['domain'] . $salt . $nonce . $data['status'];
        $data['hash'] = hash('sha256', $hashInput);
        
        $content = "<?php\n// Secure License Verification Data v2.0.0 - DO NOT EDIT\n";
        $content .= "// Generated: " . date('Y-m-d H:i:s') . "\n";
        $content .= "// Integrity: " . hash('sha256', serialize($data)) . "\n";
        $content .= "return " . var_export($data, true) . ";\n";
        
        if (file_put_contents($this->verificationFile, $content, LOCK_EX) === false) {
            throw new Exception('Failed to update verification file');
        }
        
        chmod($this->verificationFile, 0600);
    }
    
    /**
     * Mark license as inactive with audit trail
     */
    private function markLicenseInactive($reason = 'License deactivated') {
        if (file_exists($this->verificationFile)) {
            $data = include $this->verificationFile;
            $data['status'] = 'inactive';
            $data['last_check'] = time();
            $data['deactivation_reason'] = $reason;
            $data['deactivated_at'] = time();
            
            $content = "<?php\n// Secure License Verification Data v2.0.0 - DO NOT EDIT\nreturn " . var_export($data, true) . ";\n";
            file_put_contents($this->verificationFile, $content, LOCK_EX);
        }
        
        $this->logSecurityEvent("License marked as inactive: $reason", 'high');
    }
    
    /**
     * Handle server connection errors with reduced grace period
     */
    private function handleServerError() {
        if (!file_exists($this->verificationFile)) {
            throw new Exception('Unable to verify license: Server unreachable and no local verification available');
        }
        
        $data = include $this->verificationFile;
        $lastCheck = $data['last_check'] ?? 0;
        $gracePeriod = SecureConfig::get('LICENSE_GRACE_PERIOD', 86400); // Reduced to 1 day
        
        if ((time() - $lastCheck) > $gracePeriod) {
            $this->markLicenseInactive('Grace period expired');
            throw new Exception('License verification failed: Unable to contact server for extended period');
        }
        
        $this->logSecurityEvent('Using grace period due to server connection error', 'medium');
        return true;
    }
    
    /**
     * Log security events
     */
    private function logSecurityEvent($message, $severity = 'info') {
        $logFile = $this->configDir . '/security.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $logEntry = "$timestamp [$severity] $message (IP: {$this->ip}, Domain: {$this->domain})\n";
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Keep log file manageable (max 5MB)
        if (file_exists($logFile) && filesize($logFile) > 5242880) {
            $lines = file($logFile);
            $lines = array_slice($lines, -1000);
            file_put_contents($logFile, implode('', $lines));
        }
    }
}

/**
 * Global secure license verification function
 */
function verifySecureLicense() {
    static $verified = false;
    
    if ($verified) {
        return true;
    }
    
    try {
        $verifier = new SecureLicenseVerifier();
        $verifier->verifyLicense();
        $verified = true;
        return true;
    } catch (Exception $e) {
        error_log('Secure License verification failed: ' . $e->getMessage());
        throw $e;
    }
}
