<?php
/**
 * Exchange Bridge - Secure Bootstrap System
 * 
 * @package     ExchangeBridge
 * @author      Security Enhanced Version
 * @version     2.0.0
 * @created     2025-09-01
 */

// Define that the script is running
if (!defined('EB_SCRIPT_RUNNING')) {
    define('EB_SCRIPT_RUNNING', true);
}

// Start secure session
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.cookie_secure', isset($_SERVER['HTTPS']));
    ini_set('session.use_strict_mode', 1);
    session_start();
}

// Load secure configuration
require_once __DIR__ . '/secure_config.php';

// Initialize secure configuration
SecureConfig::init();

// Verify license before proceeding
try {
    require_once __DIR__ . '/secure_license_verifier.php';
    verifySecureLicense();
} catch (Exception $e) {
    // Log the error
    error_log('License verification failed: ' . $e->getMessage());
    
    // Show user-friendly error page
    http_response_code(403);
    ?>
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>License Error - Exchange Bridge</title>
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 40px; background: #f5f5f5; }
            .container { max-width: 600px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .error-icon { font-size: 48px; color: #e74c3c; text-align: center; margin-bottom: 20px; }
            h1 { color: #2c3e50; text-align: center; margin-bottom: 20px; }
            .message { color: #7f8c8d; line-height: 1.6; margin-bottom: 30px; }
            .contact-info { background: #ecf0f1; padding: 20px; border-radius: 4px; }
            .contact-info h3 { margin-top: 0; color: #2c3e50; }
        </style>
    </head>
    <body>
        <div class="container">
            <div class="error-icon">🔒</div>
            <h1>License Verification Required</h1>
            <div class="message">
                <p>This Exchange Bridge installation requires a valid license to operate. The license verification process has encountered an issue:</p>
                <p><strong><?php echo htmlspecialchars($e->getMessage()); ?></strong></p>
                <p>Please ensure your license is properly configured and your server can connect to the license verification service.</p>
            </div>
            <div class="contact-info">
                <h3>Need Help?</h3>
                <p>If you believe this is an error, please contact support with the following information:</p>
                <ul>
                    <li>Domain: <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?></li>
                    <li>Time: <?php echo date('Y-m-d H:i:s'); ?></li>
                    <li>Error: License verification failed</li>
                </ul>
            </div>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// Load enhanced security middleware
require_once __DIR__ . '/security_middleware.php';

// Initialize security middleware
$securityMiddleware = new SecurityMiddleware();
$securityMiddleware->processRequest();

// Set secure error handling
set_error_handler(function($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) {
        return false;
    }
    
    // Log security-related errors
    if (strpos($message, 'security') !== false || strpos($message, 'license') !== false) {
        error_log("Security Error: $message in $file on line $line");
    }
    
    // Don't expose sensitive information in production
    if (!SecureConfig::get('DEBUG_MODE', false)) {
        return true; // Suppress error display
    }
    
    return false; // Use default error handler
});

// Set secure exception handler
set_exception_handler(function($exception) {
    error_log('Uncaught exception: ' . $exception->getMessage());
    
    if (!SecureConfig::get('DEBUG_MODE', false)) {
        http_response_code(500);
        echo 'An error occurred. Please try again later.';
    } else {
        echo 'Exception: ' . $exception->getMessage();
    }
});

// Validate configuration integrity
try {
    SecureConfig::validateIntegrity();
} catch (Exception $e) {
    error_log('Configuration validation failed: ' . $e->getMessage());
    die('Configuration error. Please check your setup.');
}
