<?php
/**
 * Exchange Bridge Installation Wizard with Integrated License system
 * 
 * @author Saieed Rahman
 * @copyright SidMan Solution 2025
 * @version 1.0.0
 * @description Complete installation wizard with license verification and database import
*/

session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants for the installation
define('EB_SCRIPT_RUNNING', true);
define('INSTALLATION_VERSION', '1.0.0');
define('MINIMUM_PHP_VERSION', '7.4.0');
define('MINIMUM_MYSQL_VERSION', '5.6.0');

// Security: Regenerate session ID
if (!isset($_SESSION['install_session_started'])) {
    session_regenerate_id(true);
    $_SESSION['install_session_started'] = true;
    $_SESSION['install_start_time'] = time();
}

// Installation timeout protection (45 minutes)
if (isset($_SESSION['install_start_time']) && (time() - $_SESSION['install_start_time']) > 2700) {
    session_destroy();
    header('Location: index.php');
    exit('Installation session expired. Please restart.');
}

// Define installation steps
$steps = [
    1 => ['name' => 'Welcome', 'icon' => 'fas fa-home'],
    2 => ['name' => 'Requirements', 'icon' => 'fas fa-server'],
    3 => ['name' => 'License', 'icon' => 'fas fa-key'],
    4 => ['name' => 'Database', 'icon' => 'fas fa-database'],
    5 => ['name' => 'Configuration', 'icon' => 'fas fa-cog'],
    6 => ['name' => 'Complete', 'icon' => 'fas fa-check']
];

// Get current step
$currentStep = isset($_GET['step']) ? (int)$_GET['step'] : 1;
if ($currentStep < 1 || $currentStep > count($steps)) {
    $currentStep = 1;
}

// Check if already installed
$configPath = __DIR__ . '/../config/config.php';
$installLockPath = __DIR__ . '/../config/install.lock';

if ((file_exists($configPath) || file_exists($installLockPath)) && $currentStep < 6) {
    $installed = true;
} else {
    $installed = false;
}

// CSRF Token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Initialize variables
$error = '';
$success = '';
$warnings = [];

// Include the license verifier class
require_once __DIR__ . '/license_verifier.php';

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $error = "Security token mismatch. Please refresh the page and try again.";
    } else {
        switch($currentStep) {
            case 3: // License verification
                $licenseKey = trim($_POST['license_key'] ?? '');
                
                if (empty($licenseKey)) {
                    $error = "License key is required";
                    break;
                }
                
                // Validate license key format
                if (!preg_match('/^EB-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/', $licenseKey)) {
                    $error = "Invalid license key format. Expected format: EB-XXXXX-XXXXX-XXXXX-XXXXX";
                    break;
                }
                
                // Validate license with server
                $licenseResponse = validateLicenseWithServer($licenseKey);
                if (!$licenseResponse || (isset($licenseResponse['status']) && $licenseResponse['status'] === 'error')) {
                    $errorMessage = isset($licenseResponse['message']) ? $licenseResponse['message'] : 'License validation failed. Please check your license key and try again.';
                    $error = $errorMessage;
                    break;
                }
                
                // Store license info
                $_SESSION['license_key'] = $licenseKey;
                $_SESSION['license_data'] = $licenseResponse;
                
                header('Location: index.php?step=4');
                exit;
                break;
                
            case 4: // Database configuration
                $dbHost = trim($_POST['db_host'] ?? 'localhost');
                $dbUser = trim($_POST['db_user'] ?? '');
                $dbPass = $_POST['db_pass'] ?? '';
                $dbName = trim($_POST['db_name'] ?? '');
                $dbPrefix = trim($_POST['db_prefix'] ?? 'eb_');
                $timezone = $_POST['timezone'] ?? 'Asia/Dhaka';
                
                // Validate inputs
                if (empty($dbUser) || empty($dbName)) {
                    $error = "Database host, username, and name are required";
                    break;
                }
                
                // Test database connection
                $dbTestResult = testDatabaseConnection($dbHost, $dbUser, $dbPass, $dbName, $dbPrefix, $timezone);
                if ($dbTestResult['success']) {
                    $_SESSION['db_config'] = $dbTestResult['config'];
                    header('Location: index.php?step=5');
                    exit;
                } else {
                    $error = $dbTestResult['error'];
                }
                break;
                
            case 5: // Site configuration
                $siteName = trim($_POST['site_name'] ?? 'Exchange Bridge');
                $siteUrl = trim($_POST['site_url'] ?? '');
                $adminUser = trim($_POST['admin_user'] ?? 'admin');
                $adminEmail = trim($_POST['admin_email'] ?? '');
                $adminPass = $_POST['admin_pass'] ?? '';
                $adminPassConfirm = $_POST['admin_pass_confirm'] ?? '';
                
                // Validate inputs
                $siteValidation = validateSiteConfiguration($siteName, $siteUrl, $adminUser, $adminEmail, $adminPass, $adminPassConfirm);
                if (!$siteValidation['success']) {
                    $error = $siteValidation['error'];
                    break;
                }
                
                $_SESSION['site_config'] = $siteValidation['config'];
                
                // Complete installation
                $installResult = completeInstallation();
                if ($installResult['success']) {
                    // Clear sensitive session data but keep success info for display
                    $_SESSION['installation_success'] = true;
                    $_SESSION['new_user_created'] = $installResult['new_user_info'] ?? null;
                    unset($_SESSION['db_config'], $_SESSION['site_config'], $_SESSION['license_key']);
                    header('Location: index.php?step=6');
                    exit;
                } else {
                    $error = $installResult['error'];
                }
                break;
        }
    }
}

/**
 * Validate license with server
 */
function validateLicenseWithServer($licenseKey) {
    $domain = $_SERVER['HTTP_HOST'];
    $ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['SERVER_ADDR'] ?? 'unknown';
    
    // Remove www. prefix
    $domain = preg_replace('/^www\./i', '', $domain);
    
    try {
        $verifier = new EBLicenseVerifier($licenseKey, true); // Enable debug mode
        
        // Try to activate the license
        $result = $verifier->activateLicense();
        
        if ($result) {
            return [
                'status' => 'success',
                'message' => 'License activated successfully',
                'license_key' => $licenseKey,
                'domain' => $domain
            ];
        } else {
            return [
                'status' => 'error',
                'message' => 'License activation failed'
            ];
        }
    } catch (Exception $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

/**
 * Test database connection
 */
function testDatabaseConnection($host, $user, $pass, $name, $prefix, $timezone) {
    try {
        // Validate prefix
        if (!empty($prefix) && !preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $prefix)) {
            return ['success' => false, 'error' => 'Table prefix must start with a letter and contain only letters, numbers, and underscores'];
        }
        
        // Ensure prefix ends with underscore
        if (!empty($prefix) && substr($prefix, -1) !== '_') {
            $prefix .= '_';
        }
        
        // Validate timezone
        if (!in_array($timezone, timezone_identifiers_list())) {
            return ['success' => false, 'error' => 'Invalid timezone selected'];
        }
        
        // Test connection
        $conn = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ]);
        
        // Check MySQL version
        $version = $conn->query('SELECT VERSION()')->fetchColumn();
        if (version_compare($version, MINIMUM_MYSQL_VERSION, '<')) {
            return ['success' => false, 'error' => "MySQL version " . MINIMUM_MYSQL_VERSION . " or higher is required. Your version: $version"];
        }
        
        return [
            'success' => true,
            'config' => [
                'host' => $host,
                'user' => $user,
                'pass' => $pass,
                'name' => $name,
                'prefix' => $prefix,
                'timezone' => $timezone,
                'mysql_version' => $version
            ]
        ];
        
    } catch(PDOException $e) {
        $error = "Database connection failed: " . $e->getMessage();
        
        // Add helpful hints
        if (strpos($e->getMessage(), 'Access denied') !== false) {
            $error .= " Please check your database username and password.";
        } elseif (strpos($e->getMessage(), 'Unknown database') !== false) {
            $error .= " Please make sure the database exists.";
        } elseif (strpos($e->getMessage(), 'Connection refused') !== false) {
            $error .= " Please check your database host address.";
        }
        
        return ['success' => false, 'error' => $error];
    }
}

/**
 * Validate site configuration
 */
function validateSiteConfiguration($siteName, $siteUrl, $adminUser, $adminEmail, $adminPass, $adminPassConfirm) {
    if (empty($siteName) || empty($adminUser) || empty($adminEmail) || empty($adminPass)) {
        return ['success' => false, 'error' => 'All fields are required'];
    }
    
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'error' => 'Please enter a valid email address'];
    }
    
    if (strlen($adminPass) < 8) {
        return ['success' => false, 'error' => 'Password must be at least 8 characters long'];
    }
    
    if ($adminPass !== $adminPassConfirm) {
        return ['success' => false, 'error' => 'Passwords do not match'];
    }
    
    if (!preg_match('/^[a-zA-Z0-9_]+$/', $adminUser)) {
        return ['success' => false, 'error' => 'Username can only contain letters, numbers, and underscores'];
    }
    
    // Auto-detect site URL if not provided
    if (empty($siteUrl)) {
        $protocol = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') ? 'https' : 'http';
        $siteUrl = $protocol . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/install');
    } elseif (!filter_var($siteUrl, FILTER_VALIDATE_URL)) {
        return ['success' => false, 'error' => 'Please enter a valid site URL'];
    }
    
    return [
        'success' => true,
        'config' => [
            'site_name' => $siteName,
            'site_url' => $siteUrl,
            'admin_user' => $adminUser,
            'admin_email' => $adminEmail,
            'admin_pass' => $adminPass
        ]
    ];
}

/**
 * Complete installation
 */
function completeInstallation() {
    if (!isset($_SESSION['db_config']) || !isset($_SESSION['site_config']) || !isset($_SESSION['license_key'])) {
        return ['success' => false, 'error' => 'Missing configuration data'];
    }
    
    $db = $_SESSION['db_config'];
    $site = $_SESSION['site_config'];
    $licenseKey = $_SESSION['license_key'];
    $domain = $_SERVER['HTTP_HOST'];
    $domain = preg_replace('/^www\./i', '', $domain);
    
    try {
        // Create database connection
        $conn = new PDO(
            "mysql:host={$db['host']};dbname={$db['name']};charset=utf8mb4", 
            $db['user'], 
            $db['pass'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
            ]
        );
        
        // Import database
        $importResult = importDatabase($conn, $db['prefix']);
        if (!$importResult['success']) {
            return $importResult;
        }
        
        // Create NEW admin user (don't touch default one)
        $adminResult = createNewAdminUser($conn, $db, $site);
        if (!$adminResult['success']) {
            return $adminResult;
        }
        
        // Update settings
        $settingsResult = updateSiteSettings($conn, $db, $site, $licenseKey, $domain);
        if (!$settingsResult['success']) {
            return $settingsResult;
        }
        
        // Create config files
        $configResult = createConfigurationFiles($db, $site, $licenseKey, $domain);
        if (!$configResult['success']) {
            return $configResult;
        }
        
        return [
            'success' => true, 
            'message' => 'Installation completed successfully',
            'new_user_info' => $adminResult['user_info'] ?? null
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Installation error: ' . $e->getMessage()];
    }
}

/**
 * Create NEW admin user - COMPLETELY REWRITTEN FOR RELIABILITY
 */
function createNewAdminUser($conn, $db, $site) {
    try {
        $usersTable = $db['prefix'] . 'users';
        
        // Debug: Check if table exists
        $stmt = $conn->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$usersTable]);
        if (!$stmt->fetch()) {
            return ['success' => false, 'error' => "Users table '{$usersTable}' does not exist. Database import may have failed."];
        }
        
        // Check if username already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `{$usersTable}` WHERE username = ?");
        $stmt->execute([$site['admin_user']]);
        $userExists = $stmt->fetchColumn();
        
        if ($userExists > 0) {
            return ['success' => false, 'error' => 'Username "' . $site['admin_user'] . '" already exists. Please choose a different username.'];
        }
        
        // Check if email already exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `{$usersTable}` WHERE email = ?");
        $stmt->execute([$site['admin_email']]);
        $emailExists = $stmt->fetchColumn();
        
        if ($emailExists > 0) {
            return ['success' => false, 'error' => 'Email "' . $site['admin_email'] . '" already exists. Please choose a different email.'];
        }
        
        // Hash the password
        $hashedPass = password_hash($site['admin_pass'], PASSWORD_DEFAULT);
        
        // Use simple INSERT that matches the exact table structure from db.sql
        $sql = "INSERT INTO `{$usersTable}` (username, email, password, role, status, created_at, updated_at) VALUES (?, ?, ?, 'admin', 'active', NOW(), NOW())";
        
        $stmt = $conn->prepare($sql);
        $result = $stmt->execute([
            $site['admin_user'],
            $site['admin_email'],
            $hashedPass
        ]);
        
        if (!$result) {
            return ['success' => false, 'error' => 'Failed to create new admin user. SQL execution failed.'];
        }
        
        $newUserId = $conn->lastInsertId();
        
        if (!$newUserId) {
            return ['success' => false, 'error' => 'Failed to get new user ID after insertion.'];
        }
        
        // Verify the new user was created successfully
        $stmt = $conn->prepare("SELECT id, username, email, role, status FROM `{$usersTable}` WHERE id = ?");
        $stmt->execute([$newUserId]);
        $newUser = $stmt->fetch();
        
        if (!$newUser) {
            return ['success' => false, 'error' => 'New admin user verification failed - user not found after creation.'];
        }
        
        // Verify password hash works
        $stmt = $conn->prepare("SELECT password FROM `{$usersTable}` WHERE id = ?");
        $stmt->execute([$newUserId]);
        $storedPassword = $stmt->fetchColumn();
        
        if (!password_verify($site['admin_pass'], $storedPassword)) {
            return ['success' => false, 'error' => 'Password verification failed for new admin user.'];
        }
        
        // Double-check that default admin user still exists
        $stmt = $conn->prepare("SELECT COUNT(*) FROM `{$usersTable}` WHERE username = 'admin'");
        $stmt->execute();
        $defaultExists = $stmt->fetchColumn();
        
        return [
            'success' => true, 
            'message' => 'New admin user created successfully. Default admin user preserved.',
            'user_info' => [
                'id' => $newUser['id'],
                'username' => $newUser['username'],
                'email' => $newUser['email'],
                'role' => $newUser['role'],
                'status' => $newUser['status'],
                'table' => $usersTable,
                'default_user_preserved' => $defaultExists > 0
            ]
        ];
        
    } catch (PDOException $e) {
        return ['success' => false, 'error' => 'Database error while creating user: ' . $e->getMessage()];
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Unexpected error while creating user: ' . $e->getMessage()];
    }
}

/**
 * Import database from SQL file
 */
function importDatabase($conn, $prefix) {
    $sqlPaths = [
        __DIR__ . '/db.sql',
        __DIR__ . '/database.sql',
        __DIR__ . '/../db.sql',
        __DIR__ . '/../database.sql'
    ];
    
    $sqlFile = null;
    foreach ($sqlPaths as $path) {
        if (file_exists($path) && is_readable($path)) {
            $sqlFile = $path;
            break;
        }
    }
    
    if (!$sqlFile) {
        return ['success' => false, 'error' => 'Database SQL file not found. Please ensure db.sql exists in the install directory.'];
    }
    
    try {
        $sqlContent = file_get_contents($sqlFile);
        if ($sqlContent === false) {
            return ['success' => false, 'error' => 'Failed to read SQL file'];
        }
        
        // Remove BOM
        $sqlContent = preg_replace('/^\xEF\xBB\xBF/', '', $sqlContent);
        
        // Replace table prefixes if needed
        if (!empty($prefix) && $prefix !== '') {
            $tableNames = [
                'banned_ips', 'blog_posts', 'currencies', 'currency_rates', 'exchanges',
                'exchange_rates', 'floating_buttons', 'media', 'notices', 
                'pages', 'rate_limits', 'reserves', 'security_logs', 'settings', 
                'testimonials', 'users'
            ];
            
            foreach ($tableNames as $tableName) {
                $sqlContent = str_replace("`{$tableName}`", "`{$prefix}{$tableName}`", $sqlContent);
            }
        }
        
        // Parse SQL statements properly
        $statements = parseSqlStatements($sqlContent);
        
        $successCount = 0;
        $totalCount = count($statements);
        
        foreach ($statements as $statement) {
            $statement = trim($statement);
            
            if (empty($statement) || strpos($statement, '--') === 0 || strpos($statement, '#') === 0) {
                continue;
            }
            
            try {
                $conn->exec($statement);
                $successCount++;
            } catch (PDOException $e) {
                // Allow certain errors that are safe to ignore
                $errorMessage = $e->getMessage();
                if (strpos($errorMessage, 'already exists') === false && 
                    strpos($errorMessage, 'Duplicate entry') === false &&
                    strpos($errorMessage, 'Duplicate key name') === false) {
                    return ['success' => false, 'error' => 'SQL execution error: ' . $e->getMessage() . ' in statement: ' . substr($statement, 0, 100) . '...'];
                }
            }
        }
        
        return ['success' => true, 'message' => "Successfully executed {$successCount} of {$totalCount} SQL statements"];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Database import failed: ' . $e->getMessage()];
    }
}

/**
 * Parse SQL statements properly handling semicolons in quoted strings
 */
function parseSqlStatements($sqlContent) {
    $statements = [];
    $currentStatement = '';
    $inQuotes = false;
    $quoteChar = '';
    $inComment = false;
    $length = strlen($sqlContent);
    
    for ($i = 0; $i < $length; $i++) {
        $char = $sqlContent[$i];
        $nextChar = ($i + 1 < $length) ? $sqlContent[$i + 1] : '';
        
        // Handle single line comments
        if (!$inQuotes && ($char === '-' && $nextChar === '-')) {
            $inComment = true;
            $currentStatement .= $char;
            continue;
        }
        
        // Handle end of line comment
        if ($inComment && ($char === "\n" || $char === "\r")) {
            $inComment = false;
            $currentStatement .= $char;
            continue;
        }
        
        // Skip characters in comments
        if ($inComment) {
            $currentStatement .= $char;
            continue;
        }
        
        // Handle multi-line comments
        if (!$inQuotes && $char === '/' && $nextChar === '*') {
            // Skip multi-line comment
            $i += 2;
            while ($i < $length - 1) {
                if ($sqlContent[$i] === '*' && $sqlContent[$i + 1] === '/') {
                    $i += 2;
                    break;
                }
                $i++;
            }
            continue;
        }
        
        // Handle quotes
        if (($char === '"' || $char === "'") && !$inQuotes) {
            $inQuotes = true;
            $quoteChar = $char;
            $currentStatement .= $char;
            continue;
        }
        
        if ($inQuotes && $char === $quoteChar) {
            // Check for escaped quotes
            if ($i > 0 && $sqlContent[$i - 1] === '\\') {
                $currentStatement .= $char;
                continue;
            }
            // Check for doubled quotes (MySQL escape)
            if ($nextChar === $quoteChar) {
                $currentStatement .= $char . $nextChar;
                $i++; // Skip the next quote
                continue;
            }
            $inQuotes = false;
            $quoteChar = '';
            $currentStatement .= $char;
            continue;
        }
        
        // Handle semicolon
        if (!$inQuotes && $char === ';') {
            $currentStatement = trim($currentStatement);
            if (!empty($currentStatement)) {
                $statements[] = $currentStatement;
            }
            $currentStatement = '';
            continue;
        }
        
        $currentStatement .= $char;
    }
    
    // Add the last statement if it doesn't end with semicolon
    $currentStatement = trim($currentStatement);
    if (!empty($currentStatement)) {
        $statements[] = $currentStatement;
    }
    
    return $statements;
}

/**
 * Update site settings
 */
function updateSiteSettings($conn, $db, $site, $licenseKey, $domain) {
    try {
        $settingsTable = $db['prefix'] . 'settings';
        
        $siteSettings = [
            'site_name' => $site['site_name'],
            'site_url' => $site['site_url'],
            'timezone' => $db['timezone'],
            'installation_date' => date('Y-m-d H:i:s'),
            'installation_version' => INSTALLATION_VERSION,
            'license_key' => $licenseKey,
            'license_domain' => $domain,
            'database_version' => $db['mysql_version'],
            'config_version' => INSTALLATION_VERSION,
            'admin_email' => $site['admin_email']
        ];
        
        foreach ($siteSettings as $key => $value) {
            $stmt = $conn->prepare("UPDATE `{$settingsTable}` SET setting_value = ?, updated_at = NOW() WHERE setting_key = ?");
            $stmt->execute([$value, $key]);
            
            if ($stmt->rowCount() === 0) {
                $stmt = $conn->prepare("INSERT INTO `{$settingsTable}` (setting_key, setting_value, created_at, updated_at) VALUES (?, ?, NOW(), NOW())");
                $stmt->execute([$key, $value]);
            }
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Settings update failed: ' . $e->getMessage()];
    }
}

/**
 * Create configuration files
 */
function createConfigurationFiles($db, $site, $licenseKey, $domain) {
    try {
        $configDir = __DIR__ . '/../config';
        
        // Create config directory
        if (!is_dir($configDir)) {
            if (!mkdir($configDir, 0755, true)) {
                return ['success' => false, 'error' => 'Failed to create config directory'];
            }
        }
        
        // Create main config file
        $configResult = createMainConfigFile($configDir, $db, $site, $licenseKey, $domain);
        if (!$configResult['success']) {
            return $configResult;
        }
        
        // Create license verification file
        $licenseResult = createLicenseFiles($configDir, $licenseKey, $domain);
        if (!$licenseResult['success']) {
            return $licenseResult;
        }
        
        // Create security files
        createSecurityFiles($configDir);
        
        // Create installation lock
        $lockResult = createInstallationLock($configDir);
        if (!$lockResult['success']) {
            return $lockResult;
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Config creation failed: ' . $e->getMessage()];
    }
}

/**
 * Create main configuration file - UPDATED to include DB_TABLE_PREFIX
 */
function createMainConfigFile($configDir, $db, $site, $licenseKey, $domain) {
    try {
        // Generate config content exactly like config 2.php (working version) but include table prefix
        $configContent = "<?php

/**
 * Exchange Bridge - Main Configuration File
 * 
 * @package     ExchangeBridge
 * @author      Saieed Rahman
 * @copyright   SidMan Solutions 2025
 * @version     " . INSTALLATION_VERSION . "
 * @created     " . date('Y-m-d H:i:s') . "
 */

// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', '" . addslashes($db['host']) . "'); // Replace with your actual host
if (!defined('DB_USER')) define('DB_USER', '" . addslashes($db['user']) . "'); // Replace with your actual user
if (!defined('DB_PASS')) define('DB_PASS', '" . addslashes($db['pass']) . "'); // Replace with your actual password
if (!defined('DB_NAME')) define('DB_NAME', '" . addslashes($db['name']) . "'); // Replace with your actual database name";

        // Add table prefix only if user provided one and it's not empty
        if (!empty($db['prefix']) && $db['prefix'] !== '' && $db['prefix'] !== '_') {
            $configContent .= "\nif (!defined('DB_TABLE_PREFIX')) define('DB_TABLE_PREFIX', '" . addslashes($db['prefix']) . "');";
        }

        $configContent .= "

// Application constants
if (!defined('SITE_URL')) define('SITE_URL', '" . addslashes($site['site_url']) . "'); // Replace with your actual domain
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');
if (!defined('ASSETS_URL')) define('ASSETS_URL', SITE_URL . '/assets');

// Default values
if (!defined('SITE_NAME')) define('SITE_NAME', '" . addslashes($site['site_name']) . "');
if (!defined('SITE_TAGLINE')) define('SITE_TAGLINE', 'Exchange Taka Globally');
if (!defined('DEFAULT_META_TITLE')) define('DEFAULT_META_TITLE', '" . addslashes($site['site_name']) . " - Fast Currency Exchange');
if (!defined('DEFAULT_META_DESCRIPTION')) define('DEFAULT_META_DESCRIPTION', '" . addslashes($site['site_name']) . " offers fast and secure currency exchange services globally.');

// Text logo
if (!defined('TXT_LOGO')) define('TXT_LOGO', 'Exchange<span class=\"text-yellow-300\">Bridge</span>');

// Define allowed file types for uploads
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Security token
if (!defined('CSRF_TOKEN_SECRET')) define('CSRF_TOKEN_SECRET', 'ExchangeBridge2023SecureToken');

// Debug mode (set to false in production)
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Set default timezone
date_default_timezone_set('" . addslashes($db['timezone']) . "');

// Access control
if (!defined('ALLOW_ACCESS')) define('ALLOW_ACCESS', true);
";
        
        $configPath = $configDir . '/config.php';
        if (!file_put_contents($configPath, $configContent, LOCK_EX)) {
            return ['success' => false, 'error' => 'Failed to write main config file'];
        }
        
        @chmod($configPath, 0644);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Config file creation error: ' . $e->getMessage()];
    }
}

/**
 * Create license verification files
 */
function createLicenseFiles($configDir, $licenseKey, $domain) {
    try {
        // License configuration file
        $licenseConfig = "<?php
/**
 * License Configuration
 */
if (!defined('ALLOW_ACCESS')) {
    exit('Direct access forbidden');
}

if (!defined('LICENSE_KEY')) define('LICENSE_KEY', '$licenseKey');
if (!defined('LICENSE_DOMAIN')) define('LICENSE_DOMAIN', '$domain');
if (!defined('LICENSE_API_URL')) define('LICENSE_API_URL', 'https://eb-admin.rf.gd/api.php');
if (!defined('LICENSE_API_KEY')) define('LICENSE_API_KEY', 'd7x9HgT2pL5vZwK8qY3rS6mN4jF1aE0b');
if (!defined('LICENSE_CHECK_INTERVAL')) define('LICENSE_CHECK_INTERVAL', 3600);
if (!defined('LICENSE_GRACE_PERIOD')) define('LICENSE_GRACE_PERIOD', 604800);
if (!defined('LICENSE_SALT')) define('LICENSE_SALT', 'eb_license_system_salt_key_2025');
";
        
        if (!file_put_contents($configDir . '/license.php', $licenseConfig, LOCK_EX)) {
            return ['success' => false, 'error' => 'Failed to create license config'];
        }
        
        // Initial verification file
        $verificationData = [
            'license_key' => $licenseKey,
            'domain' => $domain,
            'status' => 'active',
            'hash' => hash('sha256', $licenseKey . $domain . 'eb_license_system_salt_key_2025'),
            'last_check' => time(),
            'validation_type' => 'installation',
            'created_at' => time(),
            'version' => INSTALLATION_VERSION
        ];
        
        $verificationContent = "<?php\n// License verification data - DO NOT MODIFY\nif (!defined('ALLOW_ACCESS')) { exit('Access Denied'); }\nreturn " . var_export($verificationData, true) . ";\n";
        
        if (!file_put_contents($configDir . '/verification.php', $verificationContent, LOCK_EX)) {
            return ['success' => false, 'error' => 'Failed to create verification file'];
        }
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'License files creation error: ' . $e->getMessage()];
    }
}

/**
 * Create security files
 */
function createSecurityFiles($configDir) {
    // .htaccess for config directory
    $htaccessContent = "Order deny,allow\nDeny from all\n<Files \"*.php\">\nOrder deny,allow\nDeny from all\n</Files>";
    @file_put_contents($configDir . '/.htaccess', $htaccessContent);
    
    // index.php for directory protection
    $indexContent = "<?php\nhttp_response_code(403);\nexit('Access Denied');\n";
    @file_put_contents($configDir . '/index.php', $indexContent);
}

/**
 * Create installation lock
 */
function createInstallationLock($configDir) {
    try {
        $lockData = [
            'installed' => true,
            'timestamp' => time(),
            'version' => INSTALLATION_VERSION,
            'installation_id' => bin2hex(random_bytes(16)),
            'php_version' => PHP_VERSION,
            'installer_ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        
        $lockContent = "<?php\n// Installation lock file - DO NOT DELETE\nif (!defined('ALLOW_ACCESS')) { exit('Access Denied'); }\nreturn " . var_export($lockData, true) . ";\n";
        
        if (!file_put_contents($configDir . '/install.lock', $lockContent, LOCK_EX)) {
            return ['success' => false, 'error' => 'Failed to create installation lock'];
        }
        
        @chmod($configDir . '/install.lock', 0644);
        
        return ['success' => true];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => 'Lock file creation error: ' . $e->getMessage()];
    }
}

/**
 * Check system requirements
 */
function checkRequirements() {
    $requirements = [
        'PHP Version' => [
            'required' => MINIMUM_PHP_VERSION . '+',
            'current' => PHP_VERSION,
            'status' => version_compare(PHP_VERSION, MINIMUM_PHP_VERSION, '>='),
            'critical' => true
        ],
        'PDO Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('pdo') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo'),
            'critical' => true
        ],
        'PDO MySQL' => [
            'required' => 'Enabled',
            'current' => extension_loaded('pdo_mysql') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('pdo_mysql'),
            'critical' => true
        ],
        'cURL Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('curl') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('curl'),
            'critical' => true
        ],
        'JSON Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('json') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('json'),
            'critical' => true
        ],
        'GD Extension' => [
            'required' => 'Enabled',
            'current' => extension_loaded('gd') ? 'Enabled' : 'Disabled',
            'status' => extension_loaded('gd'),
            'critical' => false
        ],
        'Config Directory' => [
            'required' => 'Writable',
            'current' => checkDirectoryWritable(__DIR__ . '/../config'),
            'status' => isDirectoryWritable(__DIR__ . '/../config'),
            'critical' => true
        ],
        'Assets Directory' => [
            'required' => 'Writable',
            'current' => checkDirectoryWritable(__DIR__ . '/../assets'),
            'status' => isDirectoryWritable(__DIR__ . '/../assets'),
            'critical' => true
        ],
        'SQL File' => [
            'required' => 'Available',
            'current' => checkSqlFile(),
            'status' => checkSqlFile() === 'Found',
            'critical' => true
        ]
    ];
    
    $allPassed = true;
    $criticalFailed = false;
    
    foreach ($requirements as $req) {
        if (!$req['status']) {
            $allPassed = false;
            if ($req['critical']) {
                $criticalFailed = true;
            }
        }
    }
    
    return [
        'requirements' => $requirements,
        'passed' => $allPassed,
        'critical_failed' => $criticalFailed
    ];
}

function checkSqlFile() {
    $paths = [
        __DIR__ . '/db.sql',
        __DIR__ . '/database.sql',
        __DIR__ . '/../db.sql',
        __DIR__ . '/../database.sql'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path) && is_readable($path)) {
            return 'Found';
        }
    }
    
    return 'Not Found';
}

function checkDirectoryWritable($dir) {
    if (!is_dir($dir)) {
        if (@mkdir($dir, 0755, true)) {
            return 'Writable (Created)';
        } else {
            return 'Not Writable';
        }
    }
    
    return is_writable($dir) ? 'Writable' : 'Not Writable';
}

function isDirectoryWritable($dir) {
    if (!is_dir($dir)) {
        return @mkdir($dir, 0755, true);
    }
    return is_writable($dir);
}

/**
 * Get timezones
 */
function getTimezones() {
    $timezones = [];
    $regions = [
        'Asia' => DateTimeZone::ASIA,
        'Europe' => DateTimeZone::EUROPE,
        'America' => DateTimeZone::AMERICA,
        'Africa' => DateTimeZone::AFRICA,
        'Australia' => DateTimeZone::AUSTRALIA,
        'Pacific' => DateTimeZone::PACIFIC,
    ];
    
    foreach ($regions as $name => $mask) {
        $timezones[$name] = DateTimeZone::listIdentifiers($mask);
    }
    
    return $timezones;
}

$pageTitle = "Exchange Bridge Installation v" . INSTALLATION_VERSION . " - " . $steps[$currentStep]['name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #6366f1;
            --primary-dark: #4f46e5;
            --primary-light: #8b5cf6;
            --secondary: #f59e0b;
            --success: #10b981;
            --error: #ef4444;
            --warning: #f59e0b;
            --info: #3b82f6;
            --text-primary: #0f172a;
            --text-secondary: #475569;
            --text-muted: #94a3b8;
            --bg-primary: #ffffff;
            --bg-secondary: #f8fafc;
            --bg-tertiary: #f1f5f9;
            --border: #e2e8f0;
            --border-light: #f1f5f9;
            --shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --shadow-xl: 0 25px 50px -12px rgba(0, 0, 0, 0.25);
            --border-radius: 12px;
            --border-radius-lg: 16px;
            --transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Poppins', 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            color: var(--text-primary);
            background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
            line-height: 1.6;
            min-height: 100vh;
            padding: 20px;
            font-weight: 400;
        }
        
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--bg-primary);
            border-radius: var(--border-radius-lg);
            box-shadow: var(--shadow-xl);
            overflow: hidden;
            backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .header {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 50%, var(--primary-light) 100%);
            padding: 50px 40px;
            color: white;
            text-align: center;
            position: relative;
            overflow: hidden;
        }
        
        .header::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: 
                radial-gradient(circle at 20% 50%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(255,255,255,0.1) 0%, transparent 50%),
                radial-gradient(circle at 40% 80%, rgba(255,255,255,0.1) 0%, transparent 50%);
            pointer-events: none;
        }
        
        .header h1 {
            font-size: 42px;
            font-weight: 900;
            margin-bottom: 12px;
            position: relative;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        
        .header p {
            font-size: 20px;
            opacity: 0.95;
            font-weight: 500;
            position: relative;
            letter-spacing: 0.5px;
        }
        
        .version-badge {
            position: absolute;
            top: 25px;
            right: 25px;
            background: rgba(255,255,255,0.25);
            backdrop-filter: blur(10px);
            padding: 8px 16px;
            border-radius: 25px;
            font-size: 14px;
            font-weight: 700;
            border: 1px solid rgba(255,255,255,0.3);
        }
        
        .progress-container {
            background: var(--bg-secondary);
            padding: 40px;
            border-bottom: 1px solid var(--border);
        }
        
        .progress-bar {
            position: relative;
            height: 8px;
            background: var(--border-light);
            border-radius: 4px;
            margin-bottom: 40px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, var(--primary) 0%, var(--primary-light) 50%, var(--secondary) 100%);
            border-radius: 4px;
            transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
            width: <?= (($currentStep - 1) / (count($steps) - 1)) * 100 ?>%;
            position: relative;
            box-shadow: 0 2px 4px rgba(99, 102, 241, 0.3);
        }
        
        .progress-fill::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(90deg, transparent 0%, rgba(255,255,255,0.4) 50%, transparent 100%);
            animation: shimmer 2.5s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .steps {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .step {
            display: flex;
            flex-direction: column;
            align-items: center;
            flex: 1;
            min-width: 140px;
            position: relative;
            z-index: 2;
        }
        
        .step-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 12px;
            transition: var(--transition);
            position: relative;
            border: 3px solid transparent;
            font-weight: 600;
        }
        
        .step.completed .step-icon {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            transform: scale(1.1);
            border-color: rgba(16, 185, 129, 0.3);
            box-shadow: 0 4px 20px rgba(16, 185, 129, 0.4);
        }
        
        .step.active .step-icon {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            color: white;
            transform: scale(1.15);
            border-color: rgba(99, 102, 241, 0.3);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.5);
            animation: pulse 2s ease-in-out infinite;
        }
        
        .step:not(.active):not(.completed) .step-icon {
            background: var(--border-light);
            color: var(--text-muted);
            border-color: var(--border);
        }
        
        @keyframes pulse {
            0%, 100% { transform: scale(1.15); }
            50% { transform: scale(1.2); }
        }
        
        .step-label {
            font-size: 15px;
            font-weight: 600;
            text-align: center;
            color: var(--text-secondary);
            transition: var(--transition);
            letter-spacing: 0.3px;
        }
        
        .step.active .step-label,
        .step.completed .step-label {
            color: var(--text-primary);
            font-weight: 700;
        }
        
        .content {
            padding: 50px 40px;
        }
        
        .step-content h2 {
            font-size: 36px;
            font-weight: 800;
            margin-bottom: 16px;
            color: var(--text-primary);
            letter-spacing: -0.5px;
        }
        
        .step-content .subtitle {
            font-size: 18px;
            color: var(--text-secondary);
            margin-bottom: 40px;
            font-weight: 500;
        }
        
        .welcome-text {
            text-align: center;
            margin-bottom: 50px;
        }
        
        .welcome-text h2 {
            font-size: 42px;
            margin-bottom: 20px;
            color: var(--text-primary);
            font-weight: 900;
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }
        
        .welcome-text p {
            font-size: 20px;
            color: var(--text-secondary);
            margin-bottom: 16px;
            font-weight: 500;
        }
        
        .feature-list {
            list-style: none;
            margin: 30px 0;
            padding: 0;
        }
        
        .feature-list li {
            display: flex;
            align-items: center;
            padding: 16px 0;
            font-size: 17px;
            color: var(--text-secondary);
            font-weight: 500;
            transition: var(--transition);
        }
        
        .feature-list li:hover {
            color: var(--text-primary);
            transform: translateX(5px);
        }
        
        .feature-list li i {
            color: var(--success);
            margin-right: 16px;
            font-size: 20px;
            width: 24px;
            font-weight: 600;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 25px;
            margin-bottom: 35px;
        }
        
        .form-group {
            margin-bottom: 25px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 10px;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 15px;
            letter-spacing: 0.3px;
        }
        
        .form-group .label-description {
            font-size: 13px;
            color: var(--text-muted);
            font-weight: 500;
            margin-top: 4px;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 16px 20px;
            border: 2px solid var(--border);
            border-radius: var(--border-radius);
            font-size: 16px;
            transition: var(--transition);
            background: var(--bg-primary);
            font-weight: 500;
            font-family: inherit;
        }
        
        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--primary);
            box-shadow: 0 0 0 4px rgba(99, 102, 241, 0.1);
            transform: translateY(-1px);
        }
        
        .form-group .input-with-icon {
            position: relative;
        }
        
        .form-group .input-icon {
            position: absolute;
            right: 20px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            cursor: pointer;
            transition: var(--transition);
            font-size: 18px;
        }
        
        .form-group .input-icon:hover {
            color: var(--text-secondary);
            transform: translateY(-50%) scale(1.1);
        }
        
        .form-group .help-text {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 8px;
            line-height: 1.5;
            font-weight: 500;
        }
        
        .requirements-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            background: var(--bg-primary);
            border-radius: var(--border-radius);
            overflow: hidden;
            border: 1px solid var(--border);
            margin-bottom: 35px;
            box-shadow: var(--shadow);
        }
        
        .requirements-table th {
            background: linear-gradient(135deg, var(--bg-tertiary) 0%, var(--bg-secondary) 100%);
            padding: 20px;
            text-align: left;
            font-weight: 700;
            color: var(--text-primary);
            font-size: 15px;
            border-bottom: 2px solid var(--border);
            letter-spacing: 0.3px;
        }
        
        .requirements-table td {
            padding: 18px 20px;
            border-bottom: 1px solid var(--border-light);
            color: var(--text-secondary);
            font-size: 15px;
            font-weight: 500;
        }
        
        .requirements-table tr:last-child td {
            border-bottom: none;
        }
        
        .requirements-table tr:hover {
            background: var(--bg-secondary);
        }
        
        .status-icon {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            font-weight: 600;
        }
        
        .status-success {
            color: var(--success);
        }
        
        .status-error {
            color: var(--error);
        }
        
        .status-warning {
            color: var(--warning);
        }
        
        .alert {
            padding: 20px 25px;
            border-radius: var(--border-radius);
            margin-bottom: 30px;
            border-left: 5px solid;
            font-size: 15px;
            line-height: 1.6;
            font-weight: 500;
            box-shadow: var(--shadow);
        }
        
        .alert-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(16, 185, 129, 0.05) 100%);
            border-color: var(--success);
            color: #047857;
        }
        
        .alert-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1) 0%, rgba(239, 68, 68, 0.05) 100%);
            border-color: var(--error);
            color: #dc2626;
        }
        
        .alert-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1) 0%, rgba(245, 158, 11, 0.05) 100%);
            border-color: var(--warning);
            color: #d97706;
        }
        
        .alert-info {
            background: linear-gradient(135deg, rgba(59, 130, 246, 0.1) 0%, rgba(59, 130, 246, 0.05) 100%);
            border-color: var(--info);
            color: #2563eb;
        }
        
        .alert i {
            margin-right: 12px;
            font-size: 18px;
        }
        
        .button {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            padding: 16px 32px;
            border: none;
            border-radius: var(--border-radius);
            font-size: 16px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            font-family: inherit;
            letter-spacing: 0.5px;
            box-shadow: var(--shadow);
        }
        
        .button:before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255,255,255,0.3), transparent);
            transition: left 0.6s;
        }
        
        .button:hover:before {
            left: 100%;
        }
        
        .button-primary {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-dark) 100%);
            color: white;
        }
        
        .button-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(99, 102, 241, 0.4);
        }
        
        .button-secondary {
            background: linear-gradient(135deg, var(--secondary) 0%, #e97f0d 100%);
            color: white;
        }
        
        .button-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.4);
        }
        
        .button-success {
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
        }
        
        .button-success:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(16, 185, 129, 0.4);
        }
        
        .button:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
            box-shadow: var(--shadow) !important;
        }
        
        .button-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 50px;
            gap: 20px;
        }
        
        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%;
            border-top-color: white;
            animation: spin 1s ease-in-out infinite;
        }
        
        @keyframes spin {
            to { transform: rotate(360deg); }
        }
        
        .form-section {
            background: linear-gradient(135deg, var(--bg-secondary) 0%, var(--bg-primary) 100%);
            border: 2px solid var(--border);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: var(--shadow);
            transition: var(--transition);
        }
        
        .form-section:hover {
            border-color: var(--primary);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.1);
        }
        
        .form-section h3 {
            margin-bottom: 20px;
            color: var(--text-primary);
            font-size: 22px;
            font-weight: 800;
            letter-spacing: -0.3px;
        }
        
        .success-animation {
            text-align: center;
            margin: 50px 0;
        }
        
        .success-icon {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--success) 0%, #059669 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 25px;
            font-size: 40px;
            animation: successPulse 2.5s ease-in-out infinite;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4);
        }
        
        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
        
        .premium-badge {
            background: linear-gradient(135deg, #ffd700 0%, #ffed4e 100%);
            color: #000;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-left: 10px;
            box-shadow: 0 2px 8px rgba(255, 215, 0, 0.3);
        }
        
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .container {
                margin: 0;
            }
            
            .header {
                padding: 40px 25px;
            }
            
            .header h1 {
                font-size: 32px;
            }
            
            .header p {
                font-size: 18px;
            }
            
            .content {
                padding: 40px 25px;
            }
            
            .progress-container {
                padding: 30px 25px;
            }
            
            .steps {
                gap: 8px;
            }
            
            .step {
                min-width: 100px;
            }
            
            .step-icon {
                width: 50px;
                height: 50px;
                font-size: 18px;
            }
            
            .step-label {
                font-size: 13px;
            }
            
            .form-grid {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .button-container {
                flex-direction: column;
                gap: 15px;
            }
            
            .button {
                width: 100%;
                padding: 18px 32px;
            }
            
            .requirements-table {
                font-size: 13px;
            }
            
            .requirements-table th,
            .requirements-table td {
                padding: 15px 12px;
            }
            
            .welcome-text h2 {
                font-size: 32px;
            }
            
            .step-content h2 {
                font-size: 28px;
            }
        }
        
        /* Custom scrollbar */
        ::-webkit-scrollbar {
            width: 8px;
        }
        
        ::-webkit-scrollbar-track {
            background: var(--bg-secondary);
        }
        
        ::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, var(--primary) 0%, var(--primary-light) 100%);
            border-radius: 4px;
        }
        
        ::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary) 100%);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <div class="version-badge">v<?= INSTALLATION_VERSION ?><span class="premium-badge">PRO</span></div>
            <h1><i class="fas fa-exchange-alt"></i> Exchange Bridge</h1>
            <p>Premium Installation Wizard & License Management System</p>
        </div>
        
        <div class="progress-container">
            <div class="progress-bar">
                <div class="progress-fill"></div>
            </div>
            
            <div class="steps">
                <?php foreach ($steps as $stepNum => $stepData): ?>
                    <div class="step <?= $currentStep == $stepNum ? 'active' : '' ?> <?= $currentStep > $stepNum ? 'completed' : '' ?>">
                        <div class="step-icon">
                            <?php if ($currentStep > $stepNum): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <i class="<?= $stepData['icon'] ?>"></i>
                            <?php endif; ?>
                        </div>
                        <div class="step-label"><?= $stepData['name'] ?></div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <div class="content">
            <?php if ($installed && $currentStep < 6): ?>
                <div class="alert alert-info">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Already Installed:</strong> Exchange Bridge is already installed. If you want to reinstall, please delete the config directory first.
                </div>
                <div class="alert alert-warning">
                    <i class="fas fa-info-circle"></i> 
                    <strong>Default Login:</strong> Try logging in with:<br>
                    <strong>Username:</strong> admin<br>
                    <strong>Password:</strong> secret
                </div>
                <div class="button-container">
                    <a href="../index.php" class="button button-primary">
                        <i class="fas fa-home"></i> Go to Homepage
                    </a>
                    <a href="../admin/login.php" class="button button-secondary">
                        <i class="fas fa-user-shield"></i> Admin Panel
                    </a>
                </div>
            <?php else: ?>
                <?php if (!empty($error)): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-triangle"></i> <strong>Error:</strong> <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i> <strong>Success:</strong> <?= htmlspecialchars($success) ?>
                    </div>
                <?php endif; ?>
                
                <div class="step-content">
                    <?php if ($currentStep == 1): ?>
                        <!-- Step 1: Welcome -->
                        <div class="welcome-text">
                            <h2><i class="fas fa-rocket"></i> Welcome to Exchange Bridge Pro</h2>
                            <p>Enterprise-grade currency exchange platform with advanced features</p>
                            <div class="alert alert-success" style="margin-top: 25px;">
                                <i class="fas fa-crown"></i> 
                                <strong>Premium Edition:</strong> You're installing the professional version with full features and lifetime updates.
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-star"></i> Premium Features</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-shield-alt"></i> Advanced license verification & protection system</li>
                                <li><i class="fas fa-database"></i> Automated database setup with optimized structure</li>
                                <li><i class="fas fa-cogs"></i> Smart configuration management with auto-detection</li>
                                <li><i class="fas fa-lock"></i> Multi-layer security with CSRF protection</li>
                                <li><i class="fas fa-mobile-alt"></i> Fully responsive design with mobile optimization</li>
                                <li><i class="fas fa-chart-line"></i> Real-time exchange rates with multiple providers</li>
                                <li><i class="fas fa-palette"></i> Advanced theme customization system</li>
                                <li><i class="fas fa-headset"></i> Priority technical support & lifetime updates</li>
                            </ul>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-clipboard-check"></i> System Requirements</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-server"></i> PHP <?= MINIMUM_PHP_VERSION ?>+ with essential extensions</li>
                                <li><i class="fas fa-database"></i> MySQL <?= MINIMUM_MYSQL_VERSION ?>+ or MariaDB equivalent</li>
                                <li><i class="fas fa-key"></i> Valid Exchange Bridge Pro license key</li>
                                <li><i class="fas fa-wifi"></i> Stable internet connection for license verification</li>
                                <li><i class="fas fa-folder-open"></i> Writable directories for configuration files</li>
                            </ul>
                        </div>
                        
                        <div class="button-container">
                            <div></div>
                            <a href="index.php?step=2" class="button button-primary">
                                <i class="fas fa-arrow-right"></i> Begin Installation
                            </a>
                        </div>
                        
                    <?php elseif ($currentStep == 2): ?>
                        <!-- Step 2: System Requirements -->
                        <h2><i class="fas fa-server"></i> System Requirements Verification</h2>
                        <p class="subtitle">Checking server compatibility and system requirements</p>
                        
                        <?php 
                        $reqCheck = checkRequirements();
                        $requirements = $reqCheck['requirements'];
                        $allPassed = $reqCheck['passed'];
                        $criticalFailed = $reqCheck['critical_failed'];
                        ?>
                        
                        <table class="requirements-table">
                            <thead>
                                <tr>
                                    <th><i class="fas fa-list"></i> Component</th>
                                    <th><i class="fas fa-exclamation"></i> Required</th>
                                    <th><i class="fas fa-info"></i> Current Status</th>
                                    <th><i class="fas fa-check-circle"></i> Result</th>
                                    <th><i class="fas fa-flag"></i> Critical</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach($requirements as $name => $req): ?>
                                    <tr>
                                        <td><strong><?= htmlspecialchars($name) ?></strong></td>
                                        <td><?= htmlspecialchars($req['required']) ?></td>
                                        <td><?= htmlspecialchars($req['current']) ?></td>
                                        <td>
                                            <span class="status-icon <?= $req['status'] ? 'status-success' : 'status-error' ?>">
                                                <?php if($req['status']): ?>
                                                    <i class="fas fa-check-circle"></i> <strong>Passed</strong>
                                                <?php else: ?>
                                                    <i class="fas fa-times-circle"></i> <strong>Failed</strong>
                                                <?php endif; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if($req['critical']): ?>
                                                <span class="status-error"><i class="fas fa-exclamation-triangle"></i> <strong>Yes</strong></span>
                                            <?php else: ?>
                                                <span class="status-success"><i class="fas fa-info-circle"></i> Optional</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        
                        <?php if (!$allPassed): ?>
                            <?php if ($criticalFailed): ?>
                                <div class="alert alert-error">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Critical Requirements Failed!</strong> Please resolve the critical issues before continuing with the installation.
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    <strong>Optional Requirements:</strong> Some optional features may not be available, but installation can proceed.
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> 
                                <strong>Perfect!</strong> Your server exceeds all requirements for Exchange Bridge Pro.
                            </div>
                        <?php endif; ?>
                        
                        <div class="button-container">
                            <a href="index.php?step=1" class="button button-secondary">
                                <i class="fas fa-arrow-left"></i> Previous
                            </a>
                            <?php if(!$criticalFailed): ?>
                                <a href="index.php?step=3" class="button button-primary">
                                    <i class="fas fa-arrow-right"></i> Continue
                                </a>
                            <?php else: ?>
                                <button class="button button-primary" disabled>
                                    <i class="fas fa-times"></i> Fix Critical Issues
                                </button>
                            <?php endif; ?>
                        </div>
                        
                    <?php elseif ($currentStep == 3): ?>
                        <!-- Step 3: License Verification -->
                        <h2><i class="fas fa-key"></i> License Verification</h2>
                        <p class="subtitle">Activate your Exchange Bridge Pro license</p>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-info-circle"></i> License Benefits</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-shield-alt"></i> Ensures authentic software with security updates</li>
                                <li><i class="fas fa-cloud-download-alt"></i> Access to automatic updates and new features</li>
                                <li><i class="fas fa-globe"></i> Domain-specific activation for security</li>
                                <li><i class="fas fa-headset"></i> Priority technical support and documentation</li>
                                <li><i class="fas fa-sync-alt"></i> Continuous license monitoring and validation</li>
                            </ul>
                        </div>
                        
                        <form method="post" action="index.php?step=3" id="licenseForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <div class="form-section">
                                <h3><i class="fas fa-key"></i> Enter License Key</h3>
                                <div class="form-group">
                                    <label for="license_key">
                                        <strong>License Key</strong>
                                        <span class="label-description">Enter your 25-character Exchange Bridge Pro license</span>
                                    </label>
                                    <input type="text" 
                                           id="license_key" 
                                           name="license_key" 
                                           placeholder="EB-XXXXX-XXXXX-XXXXX-XXXXX" 
                                           pattern="^EB-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$"
                                           required
                                           maxlength="29"
                                           value="<?= htmlspecialchars($_POST['license_key'] ?? '') ?>">
                                    <div class="help-text">
                                        <strong>Format:</strong> EB-XXXXX-XXXXX-XXXXX-XXXXX (25 characters plus dashes)
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Activation Details:</strong><br>
                                <strong>Domain:</strong> <?= htmlspecialchars($_SERVER['HTTP_HOST']) ?><br>
                                <strong>Version:</strong> Exchange Bridge Pro v<?= INSTALLATION_VERSION ?><br>
                                <strong>PHP Version:</strong> <?= PHP_VERSION ?><br>
                                This license will be permanently activated for the domain listed above.
                            </div>
                            
                            <div class="button-container">
                                <a href="index.php?step=2" class="button button-secondary">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </a>
                                <button type="submit" class="button button-primary" id="verifyBtn">
                                    <i class="fas fa-shield-alt"></i> Activate License
                                </button>
                            </div>
                        </form>
                        
                    <?php elseif ($currentStep == 4): ?>
                        <!-- Step 4: Database Configuration -->
                        <h2><i class="fas fa-database"></i> Database Configuration</h2>
                        <p class="subtitle">Configure MySQL database connection and settings</p>
                        
                        <form method="post" action="index.php?step=4" id="databaseForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <div class="form-section">
                                <h3><i class="fas fa-server"></i> Database Connection Details</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="db_host">
                                            <strong>Database Host</strong>
                                            <span class="label-description">Server hostname (usually localhost)</span>
                                        </label>
                                        <input type="text" 
                                               id="db_host" 
                                               name="db_host" 
                                               value="<?= htmlspecialchars($_POST['db_host'] ?? 'localhost') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="db_name">
                                            <strong>Database Name</strong>
                                            <span class="label-description">Database created in hosting panel</span>
                                        </label>
                                        <input type="text" 
                                               id="db_name" 
                                               name="db_name" 
                                               value="<?= htmlspecialchars($_POST['db_name'] ?? '') ?>" 
                                               required>
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="db_user">
                                            <strong>Database Username</strong>
                                            <span class="label-description">MySQL username from hosting panel</span>
                                        </label>
                                        <input type="text" 
                                               id="db_user" 
                                               name="db_user" 
                                               value="<?= htmlspecialchars($_POST['db_user'] ?? '') ?>" 
                                               required>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="db_pass">
                                            <strong>Database Password</strong>
                                            <span class="label-description">Leave empty if no password required</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <input type="password" 
                                                   id="db_pass" 
                                                   name="db_pass" 
                                                   value="<?= htmlspecialchars($_POST['db_pass'] ?? '') ?>">
                                            <i class="fas fa-eye input-icon" onclick="togglePassword('db_pass')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3><i class="fas fa-cogs"></i> Advanced Configuration</h3>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="db_prefix">
                                            <strong>Table Prefix</strong>
                                            <span class="label-description">Recommended: eb_ (prevents table conflicts)</span>
                                        </label>
                                        <input type="text" 
                                               id="db_prefix" 
                                               name="db_prefix" 
                                               value="<?= htmlspecialchars($_POST['db_prefix'] ?? '') ?>" 
                                               pattern="^[a-zA-Z][a-zA-Z0-9_]*$"
                                               placeholder="eb_">
                                        <div class="help-text">
                                            Use 'eb_' prefix to avoid conflicts. Letters, numbers, underscores only.
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="timezone">
                                            <strong>Server Timezone</strong>
                                            <span class="label-description">Select your geographic timezone</span>
                                        </label>
                                        <select id="timezone" name="timezone" required>
                                            <?php 
                                            $timezones = getTimezones();
                                            $selectedTimezone = $_POST['timezone'] ?? 'Asia/Dhaka';
                                            ?>
                                            <?php foreach($timezones as $region => $tzList): ?>
                                                <optgroup label="<?= htmlspecialchars($region) ?>">
                                                    <?php foreach($tzList as $tz): ?>
                                                        <option value="<?= htmlspecialchars($tz) ?>" 
                                                                <?= $tz === $selectedTimezone ? 'selected' : '' ?>>
                                                            <?= htmlspecialchars(str_replace('_', ' ', $tz)) ?>
                                                        </option>
                                                    <?php endforeach; ?>
                                                </optgroup>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-success">
                                <i class="fas fa-database"></i> 
                                <strong>Automatic Setup:</strong> The installer will import the complete database structure, tables, and initial data automatically.
                            </div>
                            
                            <div class="button-container">
                                <a href="index.php?step=3" class="button button-secondary">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </a>
                                <button type="submit" class="button button-primary" id="testDbBtn">
                                    <i class="fas fa-plug"></i> Test Connection & Continue
                                </button>
                            </div>
                        </form>
                        
                    <?php elseif ($currentStep == 5): ?>
                        <!-- Step 5: Site Configuration -->
                        <h2><i class="fas fa-cog"></i> Site Configuration</h2>
                        <p class="subtitle">Configure site settings and create administrator account</p>
                        
                        <form method="post" action="index.php?step=5" id="configForm">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token']) ?>">
                            
                            <div class="form-section">
                                <h3><i class="fas fa-globe"></i> Site Information</h3>
                                <div class="form-group">
                                    <label for="site_name">
                                        <strong>Site Name</strong>
                                        <span class="label-description">Name of your exchange platform</span>
                                    </label>
                                    <input type="text" 
                                           id="site_name" 
                                           name="site_name" 
                                           value="<?= htmlspecialchars($_POST['site_name'] ?? 'Exchange Bridge') ?>" 
                                           required
                                           maxlength="100">
                                </div>
                                
                                <div class="form-group">
                                    <label for="site_url">
                                        <strong>Site URL (Optional)</strong>
                                        <span class="label-description">Leave empty for automatic detection</span>
                                    </label>
                                    <input type="url" 
                                           id="site_url" 
                                           name="site_url" 
                                           value="<?= htmlspecialchars($_POST['site_url'] ?? '') ?>" 
                                           placeholder="https://yourdomain.com">
                                    <div class="help-text">
                                        <strong>Auto-detected:</strong> <?= (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'] . rtrim(dirname($_SERVER['PHP_SELF']), '/install') ?>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <h3><i class="fas fa-user-shield"></i> Create NEW Administrator Account</h3>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <strong>Note:</strong> This will create a NEW admin user. The default user (admin/admin123) will remain untouched.
                                </div>
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="admin_user">
                                            <strong>Username</strong>
                                            <span class="label-description">Choose a unique username (different from 'admin')</span>
                                        </label>
                                        <input type="text" 
                                               id="admin_user" 
                                               name="admin_user" 
                                               value="<?= htmlspecialchars($_POST['admin_user'] ?? 'admin') ?>" 
                                               pattern="^[a-zA-Z0-9_]+$"
                                               required
                                               maxlength="50">
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="admin_email">
                                            <strong>Email Address</strong>
                                            <span class="label-description">For notifications and password recovery</span>
                                        </label>
                                        <input type="email" 
                                               id="admin_email" 
                                               name="admin_email" 
                                               value="<?= htmlspecialchars($_POST['admin_email'] ?? '') ?>" 
                                               required
                                               maxlength="255">
                                    </div>
                                </div>
                                
                                <div class="form-grid">
                                    <div class="form-group">
                                        <label for="admin_pass">
                                            <strong>Password</strong>
                                            <span class="label-description">Minimum 8 characters, use strong password</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <input type="password" 
                                                   id="admin_pass" 
                                                   name="admin_pass" 
                                                   minlength="8"
                                                   required>
                                            <i class="fas fa-eye input-icon" onclick="togglePassword('admin_pass')"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="form-group">
                                        <label for="admin_pass_confirm">
                                            <strong>Confirm Password</strong>
                                            <span class="label-description">Re-enter your password for verification</span>
                                        </label>
                                        <div class="input-with-icon">
                                            <input type="password" 
                                                   id="admin_pass_confirm" 
                                                   name="admin_pass_confirm" 
                                                   minlength="8"
                                                   required>
                                            <i class="fas fa-eye input-icon" onclick="togglePassword('admin_pass_confirm')"></i>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <strong>Important:</strong> Store your administrator credentials securely. You'll need them to access the admin panel.
                            </div>
                            
                            <div class="button-container">
                                <a href="index.php?step=4" class="button button-secondary">
                                    <i class="fas fa-arrow-left"></i> Previous
                                </a>
                                <button type="submit" class="button button-success" id="installBtn">
                                    <i class="fas fa-rocket"></i> Complete Installation
                                </button>
                            </div>
                        </form>
                        
                    <?php elseif ($currentStep == 6): ?>
                        <!-- Step 6: Installation Complete -->
                        <div class="success-animation">
                            <div class="success-icon">
                                <i class="fas fa-check"></i>
                            </div>
                            <h2>🎉 Installation Successfully Completed!</h2>
                            <p>Exchange Bridge Pro v<?= INSTALLATION_VERSION ?> is now ready!</p>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-tasks"></i> Installation Summary</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-check"></i> ✅ <strong>License activated and verified successfully</strong></li>
                                <li><i class="fas fa-check"></i> ✅ <strong>Database imported with default user preserved</strong></li>
                                <li><i class="fas fa-check"></i> ✅ <strong>NEW admin user created successfully</strong></li>
                                <li><i class="fas fa-check"></i> ✅ <strong>Configuration files generated with security</strong></li>
                                <li><i class="fas fa-check"></i> ✅ <strong>License protection system activated</strong></li>
                                <li><i class="fas fa-check"></i> ✅ <strong>Security measures and CSRF protection enabled</strong></li>
                            </ul>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-user-shield"></i> Available Login Options</h3>
                            <div class="alert alert-success">
                                <i class="fas fa-info-circle"></i> 
                                <strong>Default User (Preserved):</strong><br>
                                <strong>Username:</strong> admin<br>
                                <strong>Password:</strong> admin123 (from SQL file)
                            </div>
                            <?php if (isset($_SESSION['new_user_created']) && $_SESSION['new_user_created']): ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-user-plus"></i> 
                                    <strong>Your New Admin User Created Successfully:</strong><br>
                                    Username: <strong><?= htmlspecialchars($_SESSION['new_user_created']['username'] ?? 'N/A') ?></strong><br>
                                    Role: <strong><?= htmlspecialchars($_SESSION['new_user_created']['role'] ?? 'N/A') ?></strong><br>
                                    Status: <strong><?= htmlspecialchars($_SESSION['new_user_created']['status'] ?? 'N/A') ?></strong>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="form-section">
                            <h3><i class="fas fa-clipboard-list"></i> Next Steps</h3>
                            <ul class="feature-list">
                                <li><i class="fas fa-trash-alt"></i> <strong>Delete 'install' directory immediately</strong> for security</li>
                                <li><i class="fas fa-user-cog"></i> <strong>Login to admin panel</strong> with either account</li>
                                <li><i class="fas fa-exchange-alt"></i> <strong>Set up exchange rates</strong> and supported currencies</li>
                                <li><i class="fas fa-palette"></i> <strong>Customize appearance</strong> and branding elements</li>
                                <li><i class="fas fa-envelope"></i> <strong>Configure email settings</strong> for notifications</li>
                                <li><i class="fas fa-shield-alt"></i> <strong>Review security settings</strong> and user management</li>
                            </ul>
                        </div>
                        
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle"></i> 
                            <strong>Security Alert:</strong> Please delete the 'install' directory immediately to prevent unauthorized reinstallation attempts.
                        </div>
                        
                        <div class="button-container">
                            <a href="../index.php" class="button button-secondary">
                                <i class="fas fa-home"></i> View Website
                            </a>
                            <a href="../admin/login.php" class="button button-primary">
                                <i class="fas fa-sign-in-alt"></i> Admin Login
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script>
        // Enhanced JavaScript for premium installation wizard
        document.addEventListener('DOMContentLoaded', function() {
            // Password visibility toggle with enhanced animation
            window.togglePassword = function(inputId) {
                const input = document.getElementById(inputId);
                const icon = input.parentElement.querySelector('.input-icon');
                
                if (input.type === 'password') {
                    input.type = 'text';
                    icon.className = 'fas fa-eye-slash input-icon';
                } else {
                    input.type = 'password';
                    icon.className = 'fas fa-eye input-icon';
                }
            };
            
            // Enhanced form submission handlers with improved loading states
            const forms = ['licenseForm', 'databaseForm', 'configForm'];
            
            forms.forEach(formId => {
                const form = document.getElementById(formId);
                if (form) {
                    form.addEventListener('submit', function(e) {
                        const button = form.querySelector('button[type="submit"]');
                        if (button) {
                            const originalText = button.innerHTML;
                            button.innerHTML = '<span class="loading-spinner"></span> Processing...';
                            button.disabled = true;
                            button.style.transform = 'none';
                            button.style.boxShadow = 'var(--shadow)';
                            
                            // Re-enable after 30 seconds as failsafe
                            setTimeout(() => {
                                button.innerHTML = originalText;
                                button.disabled = false;
                                button.style.transform = '';
                                button.style.boxShadow = '';
                            }, 30000);
                        }
                    });
                }
            });
            
            // Enhanced license key formatting with validation
            const licenseInput = document.getElementById('license_key');
            if (licenseInput) {
                licenseInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/[^A-Z0-9]/g, '').toUpperCase();
                    
                    // Ensure starts with EB
                    if (value.length <= 2) {
                        if (!value.startsWith('EB')) {
                            value = 'EB' + value.replace('EB', '');
                        }
                    } else {
                        if (!value.startsWith('EB')) {
                            value = 'EB' + value.substring(0, 23);
                        }
                        
                        // Format with dashes
                        const formatted = value.replace(/^(EB)(.{0,5})(.{0,5})(.{0,5})(.{0,5}).*/, function(match, p1, p2, p3, p4, p5) {
                            let result = p1;
                            if (p2) result += '-' + p2;
                            if (p3) result += '-' + p3;
                            if (p4) result += '-' + p4;
                            if (p5) result += '-' + p5;
                            return result;
                        });
                        
                        value = formatted.substring(0, 29);
                    }
                    
                    e.target.value = value;
                    
                    // Real-time validation feedback
                    const isValid = /^EB-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}-[A-Z0-9]{5}$/.test(value);
                    if (value.length === 29) {
                        e.target.style.borderColor = isValid ? 'var(--success)' : 'var(--error)';
                    } else {
                        e.target.style.borderColor = 'var(--border)';
                    }
                });
            }
            
            // Enhanced password confirmation validation
            const passwordInput = document.getElementById('admin_pass');
            const confirmInput = document.getElementById('admin_pass_confirm');
            
            if (passwordInput && confirmInput) {
                function checkPasswordMatch() {
                    if (confirmInput.value && passwordInput.value !== confirmInput.value) {
                        confirmInput.setCustomValidity('Passwords do not match');
                        confirmInput.style.borderColor = 'var(--error)';
                    } else {
                        confirmInput.setCustomValidity('');
                        confirmInput.style.borderColor = confirmInput.value ? 'var(--success)' : 'var(--border)';
                    }
                }
                
                // Password strength indicator
                function checkPasswordStrength() {
                    const password = passwordInput.value;
                    const strength = calculatePasswordStrength(password);
                    
                    if (password.length >= 8) {
                        passwordInput.style.borderColor = strength >= 3 ? 'var(--success)' : 'var(--warning)';
                    } else {
                        passwordInput.style.borderColor = 'var(--border)';
                    }
                }
                
                function calculatePasswordStrength(password) {
                    let strength = 0;
                    if (password.length >= 8) strength++;
                    if (/[a-z]/.test(password)) strength++;
                    if (/[A-Z]/.test(password)) strength++;
                    if (/[0-9]/.test(password)) strength++;
                    if (/[^A-Za-z0-9]/.test(password)) strength++;
                    return strength;
                }
                
                passwordInput.addEventListener('input', checkPasswordStrength);
                passwordInput.addEventListener('input', checkPasswordMatch);
                confirmInput.addEventListener('input', checkPasswordMatch);
            }
        });
    </script>
</body>
</html>