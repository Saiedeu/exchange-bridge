<?php
/**
 * ExchangeBridge - License Protection System
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */


// Prevent direct access
if (!defined('EB_SCRIPT_RUNNING')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

/**
 * Exchange Bridge License Verifier
 * Integrated with existing database and functions
 */
class ExchangeBridgeLicenseVerifier {
    private $licenseKey;
    private $domain;
    private $ip;
    private $verificationFile;
    private $configDir;
    private $db;
    
    public function __construct() {
        $this->configDir = __DIR__ . '/../config';
        $this->verificationFile = $this->configDir . '/verification.php';
        $this->domain = $this->getCurrentDomain();
        $this->ip = $this->getClientIP();
        $this->licenseKey = defined('LICENSE_KEY') ? LICENSE_KEY : null;
        
        // Create config directory if it doesn't exist
        if (!is_dir($this->configDir)) {
            mkdir($this->configDir, 0755, true);
        }
        
        // Create .htaccess protection
        $this->createConfigProtection();
    }
    
    /**
     * Create .htaccess protection for config directory
     */
    private function createConfigProtection() {
        $htaccessFile = $this->configDir . '/.htaccess';
        if (!file_exists($htaccessFile)) {
            $htaccessContent = "Order deny,allow\nDeny from all\n";
            file_put_contents($htaccessFile, $htaccessContent);
        }
    }
    
    /**
     * Get current domain without www and port
     */
    private function getCurrentDomain() {
        $domain = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $domain = preg_replace('/^www\./i', '', $domain);
        $domain = preg_replace('/:\d+$/', '', $domain);
        return strtolower(trim($domain));
    }
    
    /**
     * Get client IP
     */
    private function getClientIP() {
        $headers = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        foreach ($headers as $header) {
            if (!empty($_SERVER[$header])) {
                return $_SERVER[$header];
            }
        }
        return 'unknown';
    }
    
    /**
     * Main license verification method
     */
    public function verifyLicense() {
        // Check if license key is defined
        if (empty($this->licenseKey)) {
            throw new Exception('License key not found. Please reinstall the script.');
        }
        
        // Check verification file
        if (!file_exists($this->verificationFile)) {
            // No verification file, check with server
            return $this->checkWithServer(true);
        }
        
        // Load verification data
        $verificationData = include $this->verificationFile;
        
        // Verify file integrity
        if (!$this->verifyFileIntegrity($verificationData)) {
            throw new Exception('License verification file has been tampered with.');
        }
        
        // Check if license is marked as inactive
        if (isset($verificationData['status']) && $verificationData['status'] !== 'active') {
            throw new Exception('Your license has been deactivated. Please contact support.');
        }
        
        // Check domain
        if ($verificationData['domain'] !== '*' && $verificationData['domain'] !== $this->domain) {
            throw new Exception('This license is not valid for this domain: ' . $this->domain);
        }
        
        // Check if we need to verify with server
        $lastCheck = $verificationData['last_check'] ?? 0;
        $checkInterval = defined('LICENSE_CHECK_INTERVAL') ? LICENSE_CHECK_INTERVAL : 1800;
        
        if ((time() - $lastCheck) > $checkInterval) {
            return $this->checkWithServer(false);
        }
        
        return true;
    }
    
    /**
     * Verify file integrity using hash
     */
    private function verifyFileIntegrity($data) {
        if (!isset($data['license_key'], $data['domain'], $data['hash'])) {
            return false;
        }
        
        $salt = defined('LICENSE_SALT') ? LICENSE_SALT : 'eb_license_system_salt_key_2023';
        $expectedHash = hash('sha256', $data['license_key'] . $data['domain'] . $salt);
        
        return hash_equals($expectedHash, $data['hash']);
    }
    
    /**
     * Check license with server
     */
    private function checkWithServer($isFirstTime = false) {
        $apiUrl = defined('LICENSE_API_URL') ? LICENSE_API_URL : 'https://eb-admin.rf.gd/api.php';
        $apiKey = defined('LICENSE_API_KEY') ? LICENSE_API_KEY : 'd7x9HgT2pL5vZwK8qY3rS6mN4jF1aE0b';
        
        $postData = [
            'action' => 'verify',
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'ip' => $this->ip,
            'api_key' => $apiKey,
            'product' => 'exchange_bridge',
            'version' => '3.0.0'
        ];
        
        try {
            $response = $this->makeApiRequest($apiUrl, $postData);
            $result = json_decode($response, true);
            
            if (!$result || !isset($result['status'])) {
                throw new Exception('Invalid response from license server');
            }
            
            if ($result['status'] === 'success') {
                // Check server-side license status
                if (isset($result['license_status']) && $result['license_status'] !== 'active') {
                    $this->markLicenseInactive();
                    throw new Exception('Your license has been deactivated on the server.');
                }
                
                // Update verification file
                $this->updateVerificationFile($result);
                return true;
            } else {
                // License verification failed on server
                if (!$isFirstTime) {
                    $this->markLicenseInactive();
                }
                throw new Exception($result['message'] ?? 'License verification failed');
            }
        } catch (Exception $e) {
            // Server connection failed
            if ($isFirstTime) {
                throw $e;
            }
            
            // Use grace period for existing installations
            return $this->handleServerError();
        }
    }
    
    /**
     * Make API request using cURL or file_get_contents
     */
    private function makeApiRequest($url, $data) {
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => http_build_query($data),
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_USERAGENT => 'Exchange Bridge License Checker v3.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode !== 200) {
                throw new Exception("API returned HTTP code: $httpCode");
            }
            
            return $response;
        } else {
            // Fallback to file_get_contents
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-type: application/x-www-form-urlencoded',
                    'content' => http_build_query($data),
                    'timeout' => 30
                ]
            ]);
            
            $response = file_get_contents($url, false, $context);
            if ($response === false) {
                throw new Exception('Failed to connect to license server');
            }
            
            return $response;
        }
    }
    
    /**
     * Update verification file with server response
     */
    private function updateVerificationFile($serverResponse) {
        $salt = defined('LICENSE_SALT') ? LICENSE_SALT : 'eb_license_system_salt_key_2023';
        
        $data = [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'status' => 'active',
            'hash' => hash('sha256', $this->licenseKey . $this->domain . $salt),
            'last_check' => time(),
            'validation_type' => $serverResponse['validation_type'] ?? 'automatic',
            'server_status' => $serverResponse['license_status'] ?? 'active',
            'expires' => isset($serverResponse['expires']) ? strtotime($serverResponse['expires']) : null
        ];
        
        $content = "<?php\n// License verification data - DO NOT EDIT\nreturn " . var_export($data, true) . ";\n";
        file_put_contents($this->verificationFile, $content, LOCK_EX);
        chmod($this->verificationFile, 0640);
    }
    
    /**
     * Mark license as inactive
     */
    private function markLicenseInactive() {
        if (file_exists($this->verificationFile)) {
            $data = include $this->verificationFile;
            $data['status'] = 'inactive';
            $data['last_check'] = time();
            
            $content = "<?php\n// License verification data - DO NOT EDIT\nreturn " . var_export($data, true) . ";\n";
            file_put_contents($this->verificationFile, $content, LOCK_EX);
        }
    }
    
    /**
     * Handle server connection errors with grace period
     */
    private function handleServerError() {
        if (!file_exists($this->verificationFile)) {
            throw new Exception('Unable to verify license: Server unreachable and no local verification available');
        }
        
        $data = include $this->verificationFile;
        $lastCheck = $data['last_check'] ?? 0;
        $gracePeriod = defined('LICENSE_GRACE_PERIOD') ? LICENSE_GRACE_PERIOD : 604800; // 7 days
        
        if ((time() - $lastCheck) > $gracePeriod) {
            $this->markLicenseInactive();
            throw new Exception('License verification failed: Unable to contact server for extended period');
        }
        
        return true; // Allow to continue within grace period
    }
}

/**
 * Global license verification function
 */
function verifyExchangeBridgeLicense() {
    static $verified = false;
    
    if ($verified) {
        return true;
    }
    
    try {
        $verifier = new ExchangeBridgeLicenseVerifier();
        $verifier->verifyLicense();
        $verified = true;
        return true;
    } catch (Exception $e) {
        // Log the error if possible
        if (function_exists('error_log')) {
            error_log('Exchange Bridge License Error: ' . $e->getMessage());
        }
        throw $e;
    }
}

/**
 * Background license check (to be called in your application)
 */
function performBackgroundLicenseCheck() {
    static $lastBackgroundCheck = 0;
    $backgroundInterval = 300; // 5 minutes
    
    if ((time() - $lastBackgroundCheck) > $backgroundInterval) {
        $lastBackgroundCheck = time();
        
        try {
            $verifier = new ExchangeBridgeLicenseVerifier();
            return $verifier->verifyLicense();
        } catch (Exception $e) {
            // Silent fail for background checks, but log if possible
            if (function_exists('error_log')) {
                error_log('Background license check failed: ' . $e->getMessage());
            }
            return false;
        }
    }
    
    return true;
}