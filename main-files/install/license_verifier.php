<?php
/**
 * ExchabgeBridge - License Verification Script
 * 
 * This script handles license validation with the EB license server
 * and ensures that software only runs with a valid license.
 * 
 * @package EB License System
 * @version 1.0.0
 * @author Saieed Rahman
 * @company SidMan Solution
 */

// Define API constants if not already defined
if (!defined('API_URL')) define('API_URL', 'https://eb-admin.rf.gd/api.php');
if (!defined('API_KEY')) define('API_KEY', 'd7x9HgT2pL5vZwK8qY3rS6mN4jF1aE0b');
if (!defined('LICENSE_CHECK_INTERVAL')) define('LICENSE_CHECK_INTERVAL', 3600); // 1 hour (minimum allowed)
if (!defined('LICENSE_VERIFICATION_FILE')) define('LICENSE_VERIFICATION_FILE', 'eb_verification.php');
if (!defined('LICENSE_GRACE_PERIOD')) define('LICENSE_GRACE_PERIOD', 604800); // 7 days
if (!defined('LICENSE_SALT')) define('LICENSE_SALT', 'eb_license_system_salt_key_2025');

class EBLicenseVerifier {
    private $licenseKey;
    private $domain;
    private $ip;
    private $verificationFile;
    private $debugMode;
    private $productName;
    private $apiUrl;
    private $apiKey;
    
    /**
     * Constructor
     * 
     * @param string $licenseKey The license key to verify (optional, can be loaded from file)
     * @param bool $debugMode Whether to enable detailed logging
     * @param string $productName Optional product name for multi-product setups
     * @param string $customApiUrl Optional custom API URL
     * @param string $customApiKey Optional custom API key
     */
    public function __construct($licenseKey = null, $debugMode = false, $productName = 'default', $customApiUrl = null, $customApiKey = null) {
        $this->domain = $this->getCurrentDomain();
        $this->ip = $this->getClientIP();
        $this->verificationFile = __DIR__ . '/' . LICENSE_VERIFICATION_FILE;
        $this->debugMode = $debugMode;
        $this->productName = $productName;
        $this->apiUrl = $customApiUrl ?: API_URL;
        $this->apiKey = $customApiKey ?: API_KEY;
        
        // Get license key from parameter or verification file
        if ($licenseKey) {
            $this->licenseKey = $licenseKey;
        } else {
            $this->licenseKey = $this->getLicenseKeyFromVerificationFile();
        }
        
        if ($this->debugMode) {
            $this->log("EBLicenseVerifier v2.0.0 initialized");
            $this->log("License Key: " . ($this->licenseKey ? substr($this->licenseKey, 0, 10) . '...' : 'Not set'));
            $this->log("Domain: " . $this->domain);
            $this->log("IP: " . $this->ip);
            $this->log("API URL: " . $this->apiUrl);
        }
    }
    
    /**
     * Get current domain
     * 
     * @return string
     */
    private function getCurrentDomain() {
        $domain = '';
        
        // Try different methods to get the domain
        if (isset($_SERVER['HTTP_HOST'])) {
            $domain = $_SERVER['HTTP_HOST'];
        } elseif (isset($_SERVER['SERVER_NAME'])) {
            $domain = $_SERVER['SERVER_NAME'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_HOST'])) {
            $domain = $_SERVER['HTTP_X_FORWARDED_HOST'];
        }
        
        // Remove www. prefix if present
        $domain = preg_replace('/^www\./i', '', $domain);
        
        // Remove port number if present
        $domain = preg_replace('/:\d+$/', '', $domain);
        
        return strtolower(trim($domain));
    }
    
    /**
     * Get client IP address
     * 
     * @return string
     */
    private function getClientIP() {
        $ipKeys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'HTTP_X_FORWARDED', 'HTTP_FORWARDED_FOR', 'HTTP_FORWARDED', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Get license key from verification file
     * 
     * @return string|null
     */
    private function getLicenseKeyFromVerificationFile() {
        if (!file_exists($this->verificationFile)) {
            if ($this->debugMode) $this->log("Verification file not found at: " . $this->verificationFile);
            return null;
        }
        
        try {
            $data = include $this->verificationFile;
            if (!is_array($data) || !isset($data['license_key'])) {
                if ($this->debugMode) $this->log("License key not found in verification file");
                return null;
            }
            
            return $data['license_key'];
        } catch (Exception $e) {
            if ($this->debugMode) $this->log("Error reading verification file: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Verify license with server
     * 
     * @return bool True if license is valid
     * @throws Exception If license validation fails
     */
    public function verifyLicense() {
        if (empty($this->licenseKey)) {
            throw new Exception("License key is missing or invalid");
        }
        
        if (empty($this->domain)) {
            throw new Exception("Domain name could not be determined");
        }
        
        // First check verification file hash if it exists
        if (file_exists($this->verificationFile)) {
            if (!$this->verifyFileIntegrity()) {
                if ($this->debugMode) $this->log("File integrity check failed - removing corrupted file");
                @unlink($this->verificationFile);
                throw new Exception("License verification failed. Verification file has been corrupted.");
            }
            
            // Check license status in verification file
            $data = include $this->verificationFile;
            if (isset($data['status']) && $data['status'] !== 'active') {
                if ($this->debugMode) $this->log("License is marked as inactive in verification file");
                throw new Exception("License is inactive or has been revoked");
            }
            
            // Check domain in verification file
            if (isset($data['domain']) && $data['domain'] !== '*' && $data['domain'] !== $this->domain) {
                if ($this->debugMode) $this->log("Domain mismatch: " . $data['domain'] . " vs " . $this->domain);
                throw new Exception("This license is not valid for this domain");
            }
            
            // Check expiry if set
            if (isset($data['expires']) && $data['expires'] > 0 && time() > $data['expires']) {
                if ($this->debugMode) $this->log("License has expired: " . date('Y-m-d H:i:s', $data['expires']));
                throw new Exception("License has expired");
            }
            
            // Check if we need to verify with server
            $lastCheck = isset($data['last_check']) ? $data['last_check'] : 0;
            $currentTime = time();
            
            // Always check with server at the configured interval
            if ($currentTime - $lastCheck > LICENSE_CHECK_INTERVAL) {
                if ($this->debugMode) $this->log("Time for server check: Last check was " . date('Y-m-d H:i:s', $lastCheck));
                return $this->checkWithServer(true);
            }
            
            if ($this->debugMode) $this->log("Using cached verification (last checked: " . date('Y-m-d H:i:s', $lastCheck) . ")");
            return true;
        } else {
            // No verification file, must check with server
            if ($this->debugMode) $this->log("No verification file found, checking with server");
            return $this->checkWithServer(false);
        }
    }
    
    /**
     * Verify the integrity of the verification file
     * 
     * @return bool
     */
    private function verifyFileIntegrity() {
        try {
            $data = include $this->verificationFile;
            
            if (!is_array($data) || !isset($data['license_key']) || !isset($data['hash']) || !isset($data['domain'])) {
                if ($this->debugMode) $this->log("Verification file missing required fields");
                return false;
            }
            
            // Generate expected hash
            $expectedHash = md5($data['license_key'] . $data['domain'] . LICENSE_SALT);
            
            $result = ($expectedHash === $data['hash']);
            
            if ($this->debugMode && !$result) {
                $this->log("Hash verification failed");
                $this->log("Expected: " . $expectedHash);
                $this->log("Found: " . $data['hash']);
            }
            
            return $result;
        } catch (Exception $e) {
            if ($this->debugMode) $this->log("Error verifying file integrity: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Check license with server
     * 
     * @param bool $hasVerificationFile Whether a verification file already exists
     * @return bool
     * @throws Exception
     */
    private function checkWithServer($hasVerificationFile = false) {
        try {
            $postData = [
                'action' => 'verify',
                'license_key' => $this->licenseKey,
                'domain' => $this->domain,
                'ip' => $this->ip,
                'api_key' => $this->apiKey,
                'product' => $this->productName
            ];
            
            if ($this->debugMode) {
                $this->log("Checking license with server: " . $this->apiUrl);
                $debugData = $postData;
                $debugData['api_key'] = substr($debugData['api_key'], 0, 8) . '...'; // Hide API key in logs
                $this->log("Request data: " . json_encode($debugData));
            }
            
            $response = $this->makeApiRequest($postData);
            
            if ($this->debugMode) {
                $this->log("Server response: " . $response);
            }
            
            // Parse response
            $responseData = json_decode($response, true);
            
            if (!$responseData || !isset($responseData['status'])) {
                throw new Exception("Invalid response from license server");
            }
            
            if ($responseData['status'] === 'success') {
                // Check if the license is active on the server
                if (isset($responseData['license_status']) && $responseData['license_status'] !== 'active') {
                    // License is inactive on the server - immediately mark as inactive locally
                    if ($hasVerificationFile) {
                        $this->markLicenseInactive($responseData['message'] ?? 'License deactivated on server');
                    }
                    
                    if ($this->debugMode) {
                        $this->log("Server reports license is inactive: " . ($responseData['message'] ?? 'License deactivated'));
                    }
                    
                    throw new Exception($responseData['message'] ?? "License has been deactivated");
                }
                
                // Create or update verification file
                $this->createVerificationFile($responseData);
                
                if ($this->debugMode) {
                    $this->log("License verified successfully with server");
                    $this->log("Validation type: " . ($responseData['validation_type'] ?? 'automatic'));
                }
                
                return true;
            } else {
                // License is invalid according to server
                if ($this->debugMode) {
                    $this->log("Server license validation failed: " . ($responseData['message'] ?? 'Unknown error'));
                }
                
                // If there's an existing verification file, mark it as invalid
                if ($hasVerificationFile) {
                    $this->markLicenseInactive($responseData['message'] ?? 'License validation failed');
                }
                
                throw new Exception($responseData['message'] ?? "License validation failed");
            }
        } catch (Exception $e) {
            if ($this->debugMode) {
                $this->log("Exception during server check: " . $e->getMessage());
            }
            
            // Grace period only applies for connection issues, not for invalid licenses
            $connectionErrors = [
                "Failed to connect to license server",
                "API returned HTTP code",
                "cURL error",
                "Connection timed out",
                "Could not resolve host"
            ];
            
            $isConnectionError = false;
            foreach ($connectionErrors as $errorType) {
                if (strpos($e->getMessage(), $errorType) !== false) {
                    $isConnectionError = true;
                    break;
                }
            }
            
            if ($isConnectionError) {
                // Only use grace period if we have a previously validated license
                if ($hasVerificationFile) {
                    $data = include $this->verificationFile;
                    
                    if (isset($data['last_check']) && isset($data['status']) && 
                        (time() - $data['last_check'] < LICENSE_GRACE_PERIOD) && 
                        $data['status'] === 'active') {
                        
                        if ($this->debugMode) {
                            $remainingGrace = LICENSE_GRACE_PERIOD - (time() - $data['last_check']);
                            $this->log("Server unreachable but using grace period (remaining: " . $remainingGrace . " seconds)");
                        }
                        return true;
                    }
                }
            }
            
            throw $e;
        }
    }
    
    /**
     * Make API request to license server
     * 
     * @param array $postData Data to send
     * @return string Response
     * @throws Exception
     */
    private function makeApiRequest($postData) {
        $response = false;
        $lastError = '';
        
        // Try cURL first
        if (function_exists('curl_version')) {
            try {
                $ch = curl_init($this->apiUrl);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postData));
                curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
                curl_setopt($ch, CURLOPT_USERAGENT, 'EB-License-Verifier/2.0.0');
                
                $response = curl_exec($ch);
                $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                $error = curl_error($ch);
                
                if ($this->debugMode) {
                    $this->log("cURL HTTP Code: " . $httpCode);
                    if (!empty($error)) {
                        $this->log("cURL Error: " . $error);
                    }
                }
                
                curl_close($ch);
                
                if ($response === false || !empty($error)) {
                    $lastError = "cURL error: " . ($error ?: 'Unknown error');
                    $response = false;
                } elseif ($httpCode !== 200) {
                    $lastError = "API returned HTTP code $httpCode";
                    $response = false;
                }
            } catch (Exception $e) {
                $lastError = "cURL exception: " . $e->getMessage();
                $response = false;
            }
        }
        
        // Fallback to file_get_contents if cURL failed
        if ($response === false && function_exists('file_get_contents')) {
            try {
                $context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => [
                            'Content-type: application/x-www-form-urlencoded',
                            'User-Agent: EB-License-Verifier/2.0.0'
                        ],
                        'content' => http_build_query($postData),
                        'timeout' => 30,
                        'ignore_errors' => true
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                
                $response = file_get_contents($this->apiUrl, false, $context);
                
                if ($this->debugMode) {
                    $this->log("Using file_get_contents fallback");
                }
                
                if ($response === false) {
                    $lastError = "file_get_contents failed";
                }
            } catch (Exception $e) {
                $lastError = "file_get_contents exception: " . $e->getMessage();
                $response = false;
            }
        }
        
        if ($response === false) {
            throw new Exception("Failed to connect to license server: " . $lastError);
        }
        
        return $response;
    }
    
    /**
     * Create or update verification file
     * 
     * @param array $responseData Response from server
     */
    private function createVerificationFile($responseData) {
        $data = [
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'status' => 'active',
            'hash' => md5($this->licenseKey . $this->domain . LICENSE_SALT),
            'last_check' => time(),
            'validation_type' => isset($responseData['validation_type']) ? $responseData['validation_type'] : 'automatic',
            'expires' => isset($responseData['expires']) && $responseData['expires'] ? strtotime($responseData['expires']) : 0,
            'server_status' => isset($responseData['license_status']) ? $responseData['license_status'] : 'active',
            'created_at' => time(),
            'version' => '2.0.0'
        ];
        
        $content = "<?php\n// EB License System Verification File v2.0.0\n// DO NOT MODIFY THIS FILE\nreturn " . var_export($data, true) . ";\n";
        
        if (file_put_contents($this->verificationFile, $content) === false) {
            if ($this->debugMode) $this->log("Failed to create verification file");
            throw new Exception("Failed to create verification file");
        }
        
        // Set file permissions (if possible)
        @chmod($this->verificationFile, 0644);
    }
    
    /**
     * Mark license as inactive in verification file
     * 
     * @param string $reason Reason for marking inactive
     */
    private function markLicenseInactive($reason = 'License deactivated') {
        if (file_exists($this->verificationFile)) {
            try {
                $data = include $this->verificationFile;
                $data['status'] = 'inactive';
                $data['last_check'] = time();
                $data['deactivation_reason'] = $reason;
                
                $content = "<?php\n// EB License System Verification File v2.0.0\n// DO NOT MODIFY THIS FILE\nreturn " . var_export($data, true) . ";\n";
                file_put_contents($this->verificationFile, $content);
            } catch (Exception $e) {
                if ($this->debugMode) $this->log("Failed to mark license as inactive: " . $e->getMessage());
            }
        }
    }
    
    /**
     * Activate the license for this domain
     * 
     * @return bool True if activation was successful
     * @throws Exception If activation fails
     */
    public function activateLicense() {
        if (empty($this->licenseKey)) {
            throw new Exception("License key is missing");
        }
        
        if (empty($this->domain)) {
            throw new Exception("Domain name could not be determined");
        }
        
        $postData = [
            'action' => 'activate',
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'ip' => $this->ip,
            'api_key' => $this->apiKey,
            'product' => $this->productName
        ];
        
        if ($this->debugMode) {
            $this->log("Activating license with server: " . $this->apiUrl);
            $debugData = $postData;
            $debugData['api_key'] = substr($debugData['api_key'], 0, 8) . '...';
            $this->log("Activation data: " . json_encode($debugData));
        }
        
        try {
            $response = $this->makeApiRequest($postData);
            
            if ($this->debugMode) {
                $this->log("Activation response: " . $response);
            }
            
            // Parse response
            $responseData = json_decode($response, true);
            
            if (!$responseData || !isset($responseData['status'])) {
                throw new Exception("Invalid response from license server");
            }
            
            if ($responseData['status'] === 'success') {
                // Create verification file
                $this->createVerificationFile($responseData);
                
                if ($this->debugMode) {
                    $this->log("License activated successfully");
                }
                
                // Create protection file for additional security
                $this->createProtectionFile();
                
                return true;
            } else {
                if ($this->debugMode) {
                    $this->log("License activation failed: " . ($responseData['message'] ?? 'Unknown error'));
                }
                
                throw new Exception($responseData['message'] ?? "License activation failed");
            }
        } catch (Exception $e) {
            if ($this->debugMode) {
                $this->log("Activation error: " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Deactivate the license for this domain
     * 
     * @return bool True if deactivation was successful
     */
    public function deactivateLicense() {
        if (empty($this->licenseKey)) {
            throw new Exception("License key is missing");
        }
        
        if (empty($this->domain)) {
            throw new Exception("Domain name could not be determined");
        }
        
        $postData = [
            'action' => 'deactivate',
            'license_key' => $this->licenseKey,
            'domain' => $this->domain,
            'ip' => $this->ip,
            'api_key' => $this->apiKey,
            'product' => $this->productName
        ];
        
        if ($this->debugMode) {
            $this->log("Deactivating license with server: " . $this->apiUrl);
        }
        
        // Try to contact the server, but don't fail if server is unreachable
        try {
            $response = $this->makeApiRequest($postData);
            
            if ($this->debugMode) {
                $this->log("Deactivation response: " . $response);
            }
        } catch (Exception $e) {
            if ($this->debugMode) {
                $this->log("Deactivation server request failed (continuing anyway): " . $e->getMessage());
            }
        }
        
        // Always clean up locally
        $this->cleanupLicenseFiles();
        
        return true;
    }
    
    /**
     * Get license status from server
     * 
     * @return array License status information
     * @throws Exception If status check fails
     */
    public function getLicenseStatus() {
        if (empty($this->licenseKey)) {
            throw new Exception("License key is missing");
        }
        
        $postData = [
            'action' => 'status',
            'license_key' => $this->licenseKey,
            'api_key' => $this->apiKey
        ];
        
        if ($this->debugMode) {
            $this->log("Getting license status from server");
        }
        
        try {
            $response = $this->makeApiRequest($postData);
            
            if ($this->debugMode) {
                $this->log("Status response: " . $response);
            }
            
            $responseData = json_decode($response, true);
            
            if (!$responseData || !isset($responseData['status'])) {
                throw new Exception("Invalid response from license server");
            }
            
            if ($responseData['status'] === 'success') {
                return $responseData;
            } else {
                throw new Exception($responseData['message'] ?? "Failed to get license status");
            }
        } catch (Exception $e) {
            if ($this->debugMode) {
                $this->log("Status check error: " . $e->getMessage());
            }
            throw $e;
        }
    }
    
    /**
     * Clean up license files
     */
    private function cleanupLicenseFiles() {
        // Remove verification file
        if (file_exists($this->verificationFile)) {
            @unlink($this->verificationFile);
        }
        
        // Remove protection file
        $protectionFile = __DIR__ . '/eb_license_check.php';
        if (file_exists($protectionFile)) {
            @unlink($protectionFile);
        }
        
        // Remove debug log if in debug mode
        if ($this->debugMode) {
            $debugFile = __DIR__ . '/eb_license_debug.log';
            if (file_exists($debugFile)) {
                @unlink($debugFile);
            }
        }
    }
    
    /**
     * Create a protection file with obfuscated check
     */
    private function createProtectionFile() {
        $protectionFile = __DIR__ . '/eb_license_check.php';
        $obfuscatedCode = $this->getObfuscatedProtectionCode();
        
        if (file_put_contents($protectionFile, $obfuscatedCode) === false) {
            if ($this->debugMode) $this->log("Failed to create protection file");
            return false;
        }
        
        @chmod($protectionFile, 0644);
        return true;
    }
    
    /**
     * Get obfuscated protection code
     */
    private function getObfuscatedProtectionCode() {
        $apiUrl = $this->apiUrl;
        $apiKey = $this->apiKey;
        $verificationFile = LICENSE_VERIFICATION_FILE;
        $checkInterval = LICENSE_CHECK_INTERVAL;
        $gracePeriod = LICENSE_GRACE_PERIOD;
        $salt = LICENSE_SALT;
        
        $code = <<<EOD
<?php
/**
 * EB License System Protection File v2.0.0
 * DO NOT MODIFY THIS FILE
 * 
 * This file provides runtime license verification
 * and ensures continuous license compliance.
 */

// Prevent direct access
if(!defined('EB_SCRIPT_RUNNING')) {
    http_response_code(403);
    die('Access denied. This file is part of the EB License System.');
}

/**
 * Verify license status and domain compliance
 */
function eb_verify_license_runtime() {
    \$v_file = __DIR__ . '/$verificationFile';
    
    // Check if verification file exists
    if(!file_exists(\$v_file)) {
        http_response_code(403);
        die('License verification failed: Verification file not found. Please reinstall or reactivate your license.');
    }
    
    // Load verification data
    try {
        \$data = include \$v_file;
        if(!is_array(\$data)) {
            throw new Exception('Invalid verification file format');
        }
    } catch (Exception \$e) {
        http_response_code(403);
        die('License verification failed: Corrupted verification file. Please reinstall your license.');
    }
    
    // Check required fields
    \$required_fields = ['license_key', 'domain', 'hash', 'status', 'last_check'];
    foreach(\$required_fields as \$field) {
        if(!isset(\$data[\$field])) {
            http_response_code(403);
            die('License verification failed: Missing verification data. Please reinstall your license.');
        }
    }
    
    // Check if license is marked as inactive
    if(\$data['status'] !== 'active') {
        \$reason = isset(\$data['deactivation_reason']) ? \$data['deactivation_reason'] : 'License has been deactivated';
        http_response_code(403);
        die('License Error: ' . \$reason . '. Please contact support.');
    }
    
    // Get current domain
    \$current_domain = '';
    if (isset(\$_SERVER['HTTP_HOST'])) {
        \$current_domain = \$_SERVER['HTTP_HOST'];
    } elseif (isset(\$_SERVER['SERVER_NAME'])) {
        \$current_domain = \$_SERVER['SERVER_NAME'];
    }
    
    // Clean domain
    \$current_domain = preg_replace('/^www\./i', '', \$current_domain);
    \$current_domain = preg_replace('/:\d+\$/', '', \$current_domain);
    \$current_domain = strtolower(trim(\$current_domain));
    
    // Check domain compliance
    if(\$data['domain'] !== '*' && \$data['domain'] !== \$current_domain) {
        http_response_code(403);
        die('License Error: Domain mismatch. This license is valid for "' . \$data['domain'] . '" but you are accessing from "' . \$current_domain . '".');
    }
    
    // Verify file integrity
    \$expected_hash = md5(\$data['license_key'] . \$data['domain'] . '$salt');
    if(\$expected_hash !== \$data['hash']) {
        http_response_code(403);
        die('License verification failed: File integrity check failed. Please reinstall your license.');
    }
    
    // Check expiry
    if(isset(\$data['expires']) && \$data['expires'] > 0 && time() > \$data['expires']) {
        http_response_code(403);
        die('License Error: Your license has expired on ' . date('Y-m-d', \$data['expires']) . '. Please renew your license.');
    }
    
    // Check if server verification is needed
    \$last_check = \$data['last_check'];
    \$current_time = time();
    \$check_interval = $checkInterval;
    
    if(\$current_time - \$last_check > \$check_interval) {
        // Time for server verification
        \$license_key = \$data['license_key'];
        \$client_ip = \$_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        
        \$post_data = [
            'action' => 'verify',
            'license_key' => \$license_key,
            'domain' => \$current_domain,
            'ip' => \$client_ip,
            'api_key' => '$apiKey',
            'product' => 'default'
        ];
        
        \$server_response = false;
        \$api_url = '$apiUrl';
        
        // Try cURL first
        if (function_exists('curl_version')) {
            try {
                \$ch = curl_init(\$api_url);
                curl_setopt(\$ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt(\$ch, CURLOPT_POST, true);
                curl_setopt(\$ch, CURLOPT_POSTFIELDS, http_build_query(\$post_data));
                curl_setopt(\$ch, CURLOPT_TIMEOUT, 15);
                curl_setopt(\$ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt(\$ch, CURLOPT_SSL_VERIFYPEER, false);
                curl_setopt(\$ch, CURLOPT_SSL_VERIFYHOST, false);
                curl_setopt(\$ch, CURLOPT_USERAGENT, 'EB-License-Protection/2.0.0');
                
                \$server_response = curl_exec(\$ch);
                \$http_code = curl_getinfo(\$ch, CURLINFO_HTTP_CODE);
                curl_close(\$ch);
                
                if(\$server_response === false || \$http_code !== 200) {
                    \$server_response = false;
                }
            } catch (Exception \$e) {
                \$server_response = false;
            }
        }
        
        // Fallback to file_get_contents
        if(\$server_response === false && function_exists('file_get_contents')) {
            try {
                \$context = stream_context_create([
                    'http' => [
                        'method' => 'POST',
                        'header' => 'Content-type: application/x-www-form-urlencoded',
                        'content' => http_build_query(\$post_data),
                        'timeout' => 15
                    ],
                    'ssl' => [
                        'verify_peer' => false,
                        'verify_peer_name' => false
                    ]
                ]);
                
                \$server_response = @file_get_contents(\$api_url, false, \$context);
            } catch (Exception \$e) {
                \$server_response = false;
            }
        }
        
        if(\$server_response !== false) {
            // Parse server response
            \$result = json_decode(\$server_response, true);
            
            if(isset(\$result['status']) && \$result['status'] === 'success') {
                // Check server license status
                if(isset(\$result['license_status']) && \$result['license_status'] !== 'active') {
                    // License is inactive on server - mark locally and fail
                    \$data['status'] = 'inactive';
                    \$data['last_check'] = \$current_time;
                    \$data['deactivation_reason'] = \$result['message'] ?? 'License deactivated on server';
                    
                    \$content = "<?php\\n// EB License System Verification File v2.0.0\\n// DO NOT MODIFY THIS FILE\\nreturn " . var_export(\$data, true) . ";\\n";
                    @file_put_contents(\$v_file, \$content);
                    
                    http_response_code(403);
                    die('License Error: ' . (\$result['message'] ?? 'Your license has been deactivated on the server') . '. Please contact support.');
                }
                
                // Update last check time
                \$data['last_check'] = \$current_time;
                \$data['server_status'] = \$result['license_status'] ?? 'active';
                
                \$content = "<?php\\n// EB License System Verification File v2.0.0\\n// DO NOT MODIFY THIS FILE\\nreturn " . var_export(\$data, true) . ";\\n";
                @file_put_contents(\$v_file, \$content);
                
            } else {
                // Server validation failed
                if(isset(\$result['license_status']) && \$result['license_status'] !== 'active') {
                    // License is explicitly inactive - no grace period
                    \$data['status'] = 'inactive';
                    \$data['last_check'] = \$current_time;
                    \$data['deactivation_reason'] = \$result['message'] ?? 'License validation failed';
                    
                    \$content = "<?php\\n// EB License System Verification File v2.0.0\\n// DO NOT MODIFY THIS FILE\\nreturn " . var_export(\$data, true) . ";\\n";
                    @file_put_contents(\$v_file, \$content);
                    
                    http_response_code(403);
                    die('License Error: ' . (\$result['message'] ?? 'Your license is no longer valid') . '. Please contact support.');
                }
                
                // Check grace period for other errors
                \$grace_period = $gracePeriod;
                if(\$current_time - \$last_check > \$grace_period) {
                    http_response_code(403);
                    die('License Error: License verification failed and grace period expired. Please check your internet connection or contact support.');
                }
            }
        } else {
            // Server unreachable - check grace period
            \$grace_period = $gracePeriod;
            if(\$current_time - \$last_check > \$grace_period) {
                http_response_code(403);
                die('License Error: Unable to contact license server for verification and grace period has expired. Please check your internet connection or contact support.');
            }
        }
    }
    
    return true;
}

// Execute runtime verification
try {
    eb_verify_license_runtime();
} catch (Exception \$e) {
    http_response_code(403);
    die('License Error: ' . \$e->getMessage());
}

// License verification successful
return true;
EOD;
        
        return $code;
    }
    
    /**
     * Log debug messages
     */
    private function log($message) {
        if (!$this->debugMode) return;
        
        $logFile = __DIR__ . '/eb_license_debug.log';
        $timestamp = date('[Y-m-d H:i:s]');
        $logEntry = $timestamp . ' [EBLicenseVerifier] ' . $message . PHP_EOL;
        
        @file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        
        // Keep log file size reasonable (max 1MB)
        if (file_exists($logFile) && filesize($logFile) > 1048576) {
            $lines = file($logFile);
            $lines = array_slice($lines, -500); // Keep last 500 lines
            file_put_contents($logFile, implode('', $lines));
        }
    }
    
    /**
     * Get verification file path
     * 
     * @return string
     */
    public function getVerificationFilePath() {
        return $this->verificationFile;
    }
    
    /**
     * Check if license is locally verified
     * 
     * @return bool
     */
    public function isLocallyVerified() {
        if (!file_exists($this->verificationFile)) {
            return false;
        }
        
        try {
            $data = include $this->verificationFile;
            return is_array($data) && 
                   isset($data['status']) && 
                   $data['status'] === 'active' && 
                   $this->verifyFileIntegrity();
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Get local license information
     * 
     * @return array|null
     */
    public function getLocalLicenseInfo() {
        if (!file_exists($this->verificationFile)) {
            return null;
        }
        
        try {
            $data = include $this->verificationFile;
            if (!is_array($data)) {
                return null;
            }
            
            return [
                'license_key' => $data['license_key'] ?? null,
                'domain' => $data['domain'] ?? null,
                'status' => $data['status'] ?? null,
                'validation_type' => $data['validation_type'] ?? null,
                'last_check' => $data['last_check'] ?? null,
                'expires' => $data['expires'] ?? null,
                'created_at' => $data['created_at'] ?? null,
                'version' => $data['version'] ?? null
            ];
        } catch (Exception $e) {
            return null;
        }
    }
}

/**
 * Easy-to-use verification function for inclusion in PHP scripts
 * 
 * @param string $licenseKey Optional license key
 * @param bool $debugMode Enable debug logging
 * @return bool True if license is valid
 */
function eb_verify_script_license($licenseKey = null, $debugMode = false) {
    try {
        // Define that the script is running (for protection file)
        if (!defined('EB_SCRIPT_RUNNING')) {
            define('EB_SCRIPT_RUNNING', true);
        }
        
        $verifier = new EBLicenseVerifier($licenseKey, $debugMode);
        return $verifier->verifyLicense();
    } catch (Exception $e) {
        if ($debugMode) {
            error_log('EB License Error: ' . $e->getMessage());
        }
        die('License Error: ' . $e->getMessage() . '<br><br>Please contact support if this problem persists.');
    }
}

/**
 * Quick license activation function
 * 
 * @param string $licenseKey License key to activate
 * @param bool $debugMode Enable debug logging
 * @return bool True if activation successful
 */
function eb_activate_license($licenseKey, $debugMode = false) {
    try {
        $verifier = new EBLicenseVerifier($licenseKey, $debugMode);
        return $verifier->activateLicense();
    } catch (Exception $e) {
        if ($debugMode) {
            error_log('EB License Activation Error: ' . $e->getMessage());
        }
        throw $e;
    }
}

/**
 * Quick license deactivation function
 * 
 * @param string $licenseKey License key to deactivate
 * @param bool $debugMode Enable debug logging
 * @return bool True if deactivation successful
 */
function eb_deactivate_license($licenseKey = null, $debugMode = false) {
    try {
        $verifier = new EBLicenseVerifier($licenseKey, $debugMode);
        return $verifier->deactivateLicense();
    } catch (Exception $e) {
        if ($debugMode) {
            error_log('EB License Deactivation Error: ' . $e->getMessage());
        }
        throw $e;
    }
}
?>