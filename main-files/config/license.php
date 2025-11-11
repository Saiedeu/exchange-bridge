<?php
/**
 * Exchange Bridge - License Configuration
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
require_once __DIR__ . '/../includes/secure_config.php';

// Initialize secure config
SecureConfig::init();

// License system constants - these will be loaded from secure config
if (!defined('LICENSE_KEY')) {
    define('LICENSE_KEY', SecureConfig::get('LICENSE_KEY', ''));
}

if (!defined('LICENSE_API_URL')) {
    define('LICENSE_API_URL', SecureConfig::get('LICENSE_API_URL', 'https://eb-admin.rf.gd/api.php'));
}

if (!defined('LICENSE_API_KEY')) {
    define('LICENSE_API_KEY', SecureConfig::getSecure('LICENSE_API_KEY', ''));
}

if (!defined('LICENSE_SALT')) {
    define('LICENSE_SALT', SecureConfig::get('LICENSE_SALT', ''));
}

if (!defined('LICENSE_CHECK_INTERVAL')) {
    define('LICENSE_CHECK_INTERVAL', SecureConfig::get('LICENSE_CHECK_INTERVAL', 3600));
}

if (!defined('LICENSE_GRACE_PERIOD')) {
    define('LICENSE_GRACE_PERIOD', SecureConfig::get('LICENSE_GRACE_PERIOD', 86400));
}

// Validate required license configuration
$requiredConfig = ['LICENSE_KEY', 'LICENSE_API_KEY', 'LICENSE_SALT'];
foreach ($requiredConfig as $config) {
    if (empty(constant($config))) {
        error_log("Missing required license configuration: $config");
        throw new Exception("License configuration incomplete. Please check your .env file.");
    }
}
