<?php
/**
 * ExchangeBridge - System  Maintenaince Check File 
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

$currentScript = basename($_SERVER['PHP_SELF']);
$currentPath = $_SERVER['REQUEST_URI'];

// Skip maintenance check for these files/paths
$skipMaintenanceCheck = [
    'maintenance.php',
    'login.php',
    'admin',
    'ajax',
    'api',
    'cron',
    'install'
];

// Check if current request should skip maintenance check
$shouldSkip = false;
foreach ($skipMaintenanceCheck as $skip) {
    if (strpos($currentPath, $skip) !== false || $currentScript === $skip) {
        $shouldSkip = true;
        break;
    }
}

if (!$shouldSkip) {
    // Make sure functions are available before checking settings
    if (function_exists('getSetting')) {
        // Check if maintenance mode is enabled
        $maintenanceMode = getSetting('maintenance_mode', 'no');
        
        if ($maintenanceMode === 'yes') {
            // Allow admin users to bypass maintenance mode
            $canBypass = false;
            
            // Check if Auth class and functions are available
            if (class_exists('Auth') && method_exists('Auth', 'isLoggedIn')) {
                if (Auth::isLoggedIn()) {
                    $user = Auth::getUser();
                    if ($user && Auth::isAdmin()) {
                        $canBypass = true;
                    }
                }
            }
            
            // If user cannot bypass, redirect to maintenance page
            if (!$canBypass) {
                // Make sure SITE_URL is defined
                $siteUrl = defined('SITE_URL') ? SITE_URL : (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
                $maintenanceUrl = $siteUrl . '/maintenance.php';
                
                // Prevent infinite redirect loop
                if ($currentScript !== 'maintenance.php') {
                    header("Location: " . $maintenanceUrl);
                    exit;
                }
            }
        }
    }
}
?>