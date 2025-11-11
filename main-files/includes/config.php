<?php

/**
 * Exchange Bridge - Main Configuration File
 * 
 * @package     ExchangeBridge
 * @author      Saieed Rahman
 * @copyright   SidMan Solutions 2025
 * @version     1.0.0
 * @created     2025-07-22 07:06:26
 */

// Database configuration
if (!defined('DB_HOST')) define('DB_HOST', 'sql300.infinityfree.com'); // Replace with your actual host
if (!defined('DB_USER')) define('DB_USER', 'if0_39024958'); // Replace with your actual user
if (!defined('DB_PASS')) define('DB_PASS', 'SaidurRahman10'); // Replace with your actual password
if (!defined('DB_NAME')) define('DB_NAME', 'if0_39024958_Test'); // Replace with your actual database name

// Application constants
if (!defined('SITE_URL')) define('SITE_URL', 'http://saieed-rahman.rf.gd/V3'); // Replace with your actual domain
if (!defined('ADMIN_URL')) define('ADMIN_URL', SITE_URL . '/admin');
if (!defined('ASSETS_URL')) define('ASSETS_URL', SITE_URL . '/assets');

// Default values
if (!defined('SITE_NAME')) define('SITE_NAME', 'Exchange Bridge');
if (!defined('SITE_TAGLINE')) define('SITE_TAGLINE', 'Exchange Taka Globally');
if (!defined('DEFAULT_META_TITLE')) define('DEFAULT_META_TITLE', 'Exchange Bridge - Fast Currency Exchange');
if (!defined('DEFAULT_META_DESCRIPTION')) define('DEFAULT_META_DESCRIPTION', 'Exchange Bridge offers fast and secure currency exchange services globally.');

// Text logo
if (!defined('TXT_LOGO')) define('TXT_LOGO', 'Exchange<span class="text-yellow-300">Bridge</span>');

// Define allowed file types for uploads
if (!defined('ALLOWED_IMAGE_TYPES')) define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);
if (!defined('MAX_UPLOAD_SIZE')) define('MAX_UPLOAD_SIZE', 5 * 1024 * 1024); // 5MB

// Security token
if (!defined('CSRF_TOKEN_SECRET')) define('CSRF_TOKEN_SECRET', 'ExchangeBridge2023SecureToken');

// Debug mode (set to false in production)
if (!defined('DEBUG_MODE')) define('DEBUG_MODE', false);

// Set default timezone
date_default_timezone_set('Asia/Dhaka');

// Access control
if (!defined('ALLOW_ACCESS')) define('ALLOW_ACCESS', true);
