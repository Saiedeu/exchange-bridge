<?php
/**
 * ExchangeBridge - Enhanced Security Middleware
 *
 * package     ExchangeBridge
 * author      Security Enhanced Version
 * copyright   SidMan Solution 2025
 * version     2.0.0
 */


// Prevent direct access
if (!defined('EB_SCRIPT_RUNNING')) {
    http_response_code(403);
    exit('Direct access forbidden');
}

// Load secure configuration
require_once __DIR__ . '/secure_config.php';
require_once __DIR__ . '/secure_license_verifier.php';

class SecurityMiddleware {
    private $security;
    
    public function __construct() {
        $this->security = Security::getInstance();
    }
    
    /**
     * Process all incoming requests
     */
    public function processRequest() {
        // Check for banned IPs
        $this->security->checkBanStatus();
        
        // Sanitize all input data
        $this->sanitizeGlobalInputs();
        
        // Check rate limiting for sensitive actions
        $this->checkRateLimiting();
        
        // Validate CSRF for POST requests
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->validateCSRF();
        }
        
        // Additional security headers
        $this->setSecurityHeaders();
    }
    
    private function sanitizeGlobalInputs() {
        // Sanitize GET parameters
        foreach ($_GET as $key => $value) {
            $_GET[$key] = $this->security->sanitizeInput($value);
        }
        
        // Sanitize POST parameters
        foreach ($_POST as $key => $value) {
            $_POST[$key] = $this->security->sanitizeInput($value);
        }
        
        // Sanitize COOKIE parameters
        foreach ($_COOKIE as $key => $value) {
            $_COOKIE[$key] = $this->security->sanitizeInput($value);
        }
    }
    
    private function checkRateLimiting() {
        $uri = $_SERVER['REQUEST_URI'];
        
        // Define rate limits for different endpoints
        $rateLimits = [
            '/admin/login.php' => ['limit' => 5, 'window' => 300],
            '/api/' => ['limit' => 60, 'window' => 60],
            '/contact.php' => ['limit' => 3, 'window' => 300]
        ];
        
        foreach ($rateLimits as $endpoint => $limits) {
            if (strpos($uri, $endpoint) !== false) {
                if (!$this->security->checkRateLimit($endpoint, $limits['limit'], $limits['window'])) {
                    http_response_code(429);
                    exit('Rate limit exceeded. Please try again later.');
                }
                break;
            }
        }
    }
    
    private function validateCSRF() {
        // Skip CSRF validation for certain paths
        $skipPaths = ['/api/public/'];
        $currentPath = $_SERVER['REQUEST_URI'];
        
        foreach ($skipPaths as $path) {
            if (strpos($currentPath, $path) !== false) {
                return;
            }
        }
        
        // Check CSRF token
        $token = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$this->security->verifyCSRFToken($token)) {
            http_response_code(403);
            exit('CSRF token validation failed');
        }
    }
    
    private function setSecurityHeaders() {
        // Prevent XSS attacks
        header('X-XSS-Protection: 1; mode=block');
        
        // Prevent content type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Strict transport security (if HTTPS)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; font-src 'self' https://fonts.gstatic.com; img-src 'self' data: https:; connect-src 'self'");
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Remove server information
        header_remove('X-Powered-By');
        header_remove('Server');
    }
}