<?php
/**
 * ExchangeBridge - Functions Core File 
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

// Prevent direct access
if (!defined('ALLOW_ACCESS')) {
    header("HTTP/1.1 403 Forbidden");
    exit("Direct access forbidden");
}

// Initialize security
require_once __DIR__ . '/security.php';
$security = Security::getInstance();


// Get a setting from the database
function getSetting($key, $default = '') {
    try {
        $db = Database::getInstance();
        $value = $db->getValue("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
        return $value !== false ? $value : $default;
    } catch (Exception $e) {
        error_log("getSetting error for '{$key}': " . $e->getMessage());
        return $default;
    }
}

// Update a setting in the database (Fixed Version)
function updateSetting($key, $value) {
    try {
        $db = Database::getInstance();
        
        // Log attempt
        error_log("updateSetting called: key={$key}, value=" . substr($value, 0, 100) . (strlen($value) > 100 ? '...' : ''));
        
        // Check if database connection exists
        if (!$db || !$db->getConnection()) {
            error_log("Database connection failed in updateSetting");
            return false;
        }
        
        // Check if setting exists
        $exists = $db->getValue("SELECT COUNT(*) FROM settings WHERE setting_key = ?", [$key]);
        error_log("Setting '{$key}' exists check: " . ($exists ? $exists : '0'));
        
        $result = false;
        
        if ($exists > 0) {
            // Update existing setting
            $result = $db->update('settings', [
                'setting_value' => $value,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'setting_key = ?', [$key]);
            error_log("Update result for '{$key}': " . ($result ? 'success' : 'failed'));
        } else {
            // Insert new setting
            $result = $db->insert('settings', [
                'setting_key' => $key,
                'setting_value' => $value,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            error_log("Insert result for '{$key}': " . ($result ? 'success' : 'failed'));
        }
        
        // Verify the update if successful
        if ($result) {
            $newValue = $db->getValue("SELECT setting_value FROM settings WHERE setting_key = ?", [$key]);
            error_log("Verification for '{$key}': stored value matches = " . ($newValue == $value ? 'yes' : 'no'));
            return true;
        }
        
        return false;
        
    } catch (Exception $e) {
        error_log("updateSetting error for '{$key}': " . $e->getMessage());
        return false;
    }
}

// Test database connection function
function testDatabaseConnection() {
    try {
        $db = Database::getInstance();
        $result = $db->getValue("SELECT 1");
        return $result !== false;
    } catch (Exception $e) {
        error_log("Database connection test failed: " . $e->getMessage());
        return false;
    }
}

// Test settings table function
function testSettingsTable() {
    try {
        $db = Database::getInstance();
        $result = $db->getValue("SELECT COUNT(*) FROM settings");
        return $result !== false;
    } catch (Exception $e) {
        error_log("Settings table test failed: " . $e->getMessage());
        return false;
    }
}

// Debug function to check current settings
function debugSettings() {
    try {
        $db = Database::getInstance();
        $settings = $db->getRows("SELECT * FROM settings ORDER BY setting_key");
        error_log("Current settings count: " . count($settings));
        return $settings;
    } catch (Exception $e) {
        error_log("Debug settings failed: " . $e->getMessage());
        return false;
    }
}

// Test single setting update
function testSettingUpdate() {
    $testKey = 'test_setting_' . time();
    $testValue = 'test_value_' . rand(1000, 9999);
    
    error_log("Testing setting update with key: {$testKey}, value: {$testValue}");
    
    $result = updateSetting($testKey, $testValue);
    if ($result) {
        // Try to retrieve the value
        $retrieved = getSetting($testKey);
        if ($retrieved === $testValue) {
            error_log("Test setting update: SUCCESS");
            // Clean up test setting
            try {
                $db = Database::getInstance();
                $db->delete('settings', 'setting_key = ?', [$testKey]);
            } catch (Exception $e) {
                error_log("Failed to clean up test setting: " . $e->getMessage());
            }
            return true;
        } else {
            error_log("Test setting update: FAILED - Retrieved value mismatch. Expected: {$testValue}, Got: {$retrieved}");
        }
    } else {
        error_log("Test setting update: FAILED - updateSetting returned false");
    }
    
    return false;
}

// Generate sequential exchange ID
function generateExchangeId() {
    $db = Database::getInstance();
    
    // Set timezone
    date_default_timezone_set(getSetting('site_timezone', 'Asia/Dhaka'));
    
    // Get current date in YYMMDD format
    $currentDate = date('ymd');
    
    try {
        // Begin transaction
        $db->getConnection()->beginTransaction();
        
        // Get the highest serial number for today
        $stmt = $db->getConnection()->prepare("
            SELECT MAX(CAST(SUBSTRING(reference_id, 10, 2) AS UNSIGNED)) as max_serial 
            FROM exchanges 
            WHERE SUBSTRING(reference_id, 4, 6) = :date_part 
            AND reference_id LIKE 'EB-%'
            AND DATE(created_at) = CURDATE()
        ");
        $stmt->bindParam(':date_part', $currentDate);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate next serial number
        $nextSerial = 1;
        if ($result && $result['max_serial'] !== null) {
            $nextSerial = intval($result['max_serial']) + 1;
        }
        
        // Format serial number with leading zeros
        $serialNumber = str_pad($nextSerial, 2, '0', STR_PAD_LEFT);
        
        // Generate the exchange ID
        $exchangeId = "EB-{$currentDate}{$serialNumber}";
        
        // Verify this ID doesn't already exist
        $checkStmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM exchanges WHERE reference_id = :exchange_id");
        $checkStmt->bindParam(':exchange_id', $exchangeId);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            // If somehow it exists, increment and try again
            $nextSerial++;
            $serialNumber = str_pad($nextSerial, 2, '0', STR_PAD_LEFT);
            $exchangeId = "EB-{$currentDate}{$serialNumber}";
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        
        return $exchangeId;
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->getConnection()->rollBack();
        
        // Fallback to time-based ID
        $timestamp = time();
        $random = rand(10, 99);
        return "EB-{$currentDate}{$random}";
    }
}

// Get all active currencies
function getAllCurrencies($activeOnly = true) {
    try {
        $db = Database::getInstance();
        $sql = "SELECT * FROM currencies";
        
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        
        $sql .= " ORDER BY name ASC";
        
        return $db->getRows($sql);
    } catch (Exception $e) {
        error_log("getAllCurrencies error: " . $e->getMessage());
        return [];
    }
}

// Get a currency by code
function getCurrencyByCode($code) {
    try {
        $db = Database::getInstance();
        return $db->getRow("SELECT * FROM currencies WHERE code = ?", [$code]);
    } catch (Exception $e) {
        error_log("getCurrencyByCode error for '{$code}': " . $e->getMessage());
        return false;
    }
}

// Get exchange rate between two currencies
function getExchangeRate($fromCurrency, $toCurrency) {
    try {
        $db = Database::getInstance();
        $rate = $db->getValue(
            "SELECT rate FROM exchange_rates WHERE from_currency = ? AND to_currency = ? AND status = 'active'",
            [$fromCurrency, $toCurrency]
        );
        
        return $rate !== false ? (float) $rate : 0;
    } catch (Exception $e) {
        error_log("getExchangeRate error for '{$fromCurrency}' to '{$toCurrency}': " . $e->getMessage());
        return 0;
    }
}

// Get all exchange rates
function getAllExchangeRates() {
    try {
        $db = Database::getInstance();
        return $db->getRows("SELECT * FROM exchange_rates WHERE status = 'active'");
    } catch (Exception $e) {
        error_log("getAllExchangeRates error: " . $e->getMessage());
        return [];
    }
}

// Get currency reserves
function getCurrencyReserves($currencyCode = null) {
    try {
        $db = Database::getInstance();
        
        if ($currencyCode) {
            return $db->getRow(
                "SELECT r.*, c.name, c.display_name, c.logo, c.background_class, c.icon_class 
                FROM reserves r 
                JOIN currencies c ON r.currency_code = c.code 
                WHERE r.currency_code = ?",
                [$currencyCode]
            );
        } else {
            return $db->getRows(
                "SELECT r.*, c.name, c.display_name, c.logo, c.background_class, c.icon_class 
                FROM reserves r 
                JOIN currencies c ON r.currency_code = c.code 
                ORDER BY c.name ASC"
            );
        }
    } catch (Exception $e) {
        error_log("getCurrencyReserves error: " . $e->getMessage());
        return $currencyCode ? false : [];
    }
}

// Get all active notices
function getActiveNotices($type = null) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM notices WHERE status = 'active'";
        $params = [];
        
        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }
        
        return $db->getRows($sql, $params);
    } catch (Exception $e) {
        error_log("getActiveNotices error: " . $e->getMessage());
        return [];
    }
}

// Get all recent exchanges
function getRecentExchanges($limit = 10) {
    try {
        $db = Database::getInstance();
        
        return $db->getRows(
            "SELECT e.*, 
                fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo, fc.background_class as from_bg_class, fc.icon_class as from_icon_class,
                tc.name as to_currency_name, tc.display_name as to_display_name, tc.logo as to_logo, tc.background_class as to_bg_class, tc.icon_class as to_icon_class
            FROM exchanges e
            JOIN currencies fc ON e.from_currency = fc.code
            JOIN currencies tc ON e.to_currency = tc.code
            ORDER BY e.created_at DESC
            LIMIT ?",
            [$limit]
        );
    } catch (Exception $e) {
        error_log("getRecentExchanges error: " . $e->getMessage());
        return [];
    }
}

// Get exchange by reference ID
function getExchangeByReferenceId($referenceId) {
    try {
        $db = Database::getInstance();
        
        return $db->getRow(
            "SELECT e.*, 
                fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo,
                tc.name as to_currency_name, tc.display_name as to_display_name, tc.logo as to_logo
            FROM exchanges e
            JOIN currencies fc ON e.from_currency = fc.code
            JOIN currencies tc ON e.to_currency = tc.code
            WHERE e.reference_id = ?",
            [$referenceId]
        );
    } catch (Exception $e) {
        error_log("getExchangeByReferenceId error for '{$referenceId}': " . $e->getMessage());
        return false;
    }
}

// Get all active testimonials
function getActiveTestimonials($limit = 5) {
    try {
        $db = Database::getInstance();
        
        return $db->getRows(
            "SELECT t.*, 
                fc.name as from_currency_name, fc.display_name as from_display_name, fc.logo as from_logo, fc.background_class as from_bg_class, fc.icon_class as from_icon_class,
                tc.name as to_currency_name, tc.display_name as to_display_name, tc.logo as to_logo, tc.background_class as to_bg_class, tc.icon_class as to_icon_class
            FROM testimonials t
            LEFT JOIN currencies fc ON t.from_currency = fc.code
            LEFT JOIN currencies tc ON t.to_currency = tc.code
            WHERE t.status = 'active'
            ORDER BY t.created_at DESC
            LIMIT ?",
            [$limit]
        );
    } catch (Exception $e) {
        error_log("getActiveTestimonials error: " . $e->getMessage());
        return [];
    }
}

// Get page by slug
function getPageBySlug($slug) {
    try {
        $db = Database::getInstance();
        return $db->getRow("SELECT * FROM pages WHERE slug = ? AND status = 'active'", [$slug]);
    } catch (Exception $e) {
        error_log("getPageBySlug error for '{$slug}': " . $e->getMessage());
        return false;
    }
}

// Create slug from string
function createSlug($string) {
    $string = strtolower($string);
    $string = preg_replace('/[^a-z0-9\-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

// Format time difference
function timeAgo($datetime) {
    try {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);
        
        if ($diff->y > 0) {
            return $diff->y . ' year' . ($diff->y > 1 ? 's' : '') . ' ago';
        } elseif ($diff->m > 0) {
            return $diff->m . ' month' . ($diff->m > 1 ? 's' : '') . ' ago';
        } elseif ($diff->d > 0) {
            return $diff->d . ' day' . ($diff->d > 1 ? 's' : '') . ' ago';
        } elseif ($diff->h > 0) {
            return $diff->h . ' hour' . ($diff->h > 1 ? 's' : '') . ' ago';
        } elseif ($diff->i > 0) {
            return $diff->i . ' minute' . ($diff->i > 1 ? 's' : '') . ' ago';
        } else {
            return 'just now';
        }
    } catch (Exception $e) {
        error_log("timeAgo error: " . $e->getMessage());
        return 'unknown';
    }
}

// Generate CSRF token
function generateCSRFToken() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
    
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

// Verify CSRF token
function verifyCSRFToken($token) {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }
    
    if (!isset($_SESSION['csrf_token']) || $_SESSION['csrf_token'] !== $token) {
        return false;
    }
    return true;
}

// Sanitize input
function sanitizeInput($input) {
    if (is_array($input)) {
        foreach ($input as $key => $value) {
            $input[$key] = sanitizeInput($value);
        }
    } else {
        $input = trim(htmlspecialchars($input, ENT_QUOTES, 'UTF-8'));
    }
    return $input;
}

// Validate email
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Adjust color brightness
function adjustBrightness($hexColor, $percent) {
    try {
        // Convert hex to RGB
        $hexColor = ltrim($hexColor, '#');
        if (strlen($hexColor) == 3) {
            $hexColor = $hexColor[0] . $hexColor[0] . $hexColor[1] . $hexColor[1] . $hexColor[2] . $hexColor[2];
        }
        
        $r = hexdec(substr($hexColor, 0, 2));
        $g = hexdec(substr($hexColor, 2, 2));
        $b = hexdec(substr($hexColor, 4, 2));
        
        // Adjust brightness
        $r = max(0, min(255, $r + $percent));
        $g = max(0, min(255, $g + $percent));
        $b = max(0, min(255, $b + $percent));
        
        // Convert back to hex
        return sprintf("#%02x%02x%02x", $r, $g, $b);
    } catch (Exception $e) {
        error_log("adjustBrightness error: " . $e->getMessage());
        return $hexColor;
    }
}

// Upload file
function uploadFile($file, $targetDir, $allowedTypes = ['jpg', 'jpeg', 'png', 'gif'], $maxSize = 5242880) {
    try {
        // Check if file was uploaded without errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return ['success' => false, 'message' => 'File upload error: ' . $file['error']];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['success' => false, 'message' => 'File size exceeds the maximum allowed size of ' . ($maxSize / 1024 / 1024) . 'MB'];
        }
        
        // Check file type
        $fileType = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($fileType, $allowedTypes)) {
            return ['success' => false, 'message' => 'Invalid file type. Allowed types: ' . implode(', ', $allowedTypes)];
        }
        
        // Create target directory if it doesn't exist
        if (!file_exists($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
        
        // Generate unique filename
        $filename = uniqid() . '.' . $fileType;
        $targetFile = $targetDir . '/' . $filename;
        
        // Move uploaded file
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            return ['success' => true, 'filename' => $filename, 'path' => $targetFile];
        } else {
            return ['success' => false, 'message' => 'Failed to move uploaded file'];
        }
    } catch (Exception $e) {
        error_log("uploadFile error: " . $e->getMessage());
        return ['success' => false, 'message' => 'File upload failed: ' . $e->getMessage()];
    }
}

// Get blog posts
function getBlogPosts($limit = 10, $offset = 0) {
    try {
        $db = Database::getInstance();
        
        return $db->getRows(
            "SELECT p.*, u.username as author_name
            FROM blog_posts p
            JOIN users u ON p.author_id = u.id
            WHERE p.status = 'published'
            ORDER BY p.created_at DESC
            LIMIT ? OFFSET ?",
            [$limit, $offset]
        );
    } catch (Exception $e) {
        error_log("getBlogPosts error: " . $e->getMessage());
        return [];
    }
}

// Get blog post by slug
function getBlogPostBySlug($slug) {
    try {
        $db = Database::getInstance();
        
        return $db->getRow(
            "SELECT p.*, u.username as author_name
            FROM blog_posts p
            JOIN users u ON p.author_id = u.id
            WHERE p.slug = ? AND p.status = 'published'",
            [$slug]
        );
    } catch (Exception $e) {
        error_log("getBlogPostBySlug error for '{$slug}': " . $e->getMessage());
        return false;
    }
}

// Update reserve amount (for auto-update from exchanges)
function updateReserveAmount($currencyCode, $amount, $operation = 'add') {
    try {
        $db = Database::getInstance();
        
        // Get current reserve
        $reserve = getCurrencyReserves($currencyCode);
        if (!$reserve || !$reserve['auto_update']) {
            return false;
        }
        
        // Calculate new amount
        if ($operation === 'add') {
            $newAmount = $reserve['amount'] + $amount;
        } elseif ($operation === 'subtract') {
            $newAmount = max(0, $reserve['amount'] - $amount);
        } else {
            $newAmount = $amount; // direct set
        }
        
        // Update reserve
        return $db->update('reserves', ['amount' => $newAmount], 'currency_code = ?', [$currencyCode]);
    } catch (Exception $e) {
        error_log("updateReserveAmount error for '{$currencyCode}': " . $e->getMessage());
        return false;
    }
}

// Check if reserve is low
function isReserveLow($currencyCode) {
    try {
        $reserve = getCurrencyReserves($currencyCode);
        if (!$reserve) {
            return false;
        }
        
        return $reserve['amount'] <= $reserve['min_amount'];
    } catch (Exception $e) {
        error_log("isReserveLow error for '{$currencyCode}': " . $e->getMessage());
        return false;
    }
}

// Get reserve status
function getReserveStatus($currencyCode) {
    try {
        $reserve = getCurrencyReserves($currencyCode);
        if (!$reserve) {
            return 'unknown';
        }
        
        if ($reserve['max_amount'] > 0) {
            $percentage = ($reserve['amount'] / $reserve['max_amount']) * 100;
            
            if ($reserve['min_amount'] > 0 && $reserve['amount'] <= $reserve['min_amount']) {
                return 'low';
            } elseif ($percentage >= 80) {
                return 'high';
            } else {
                return 'medium';
            }
        }
        
        return 'unknown';
    } catch (Exception $e) {
        error_log("getReserveStatus error for '{$currencyCode}': " . $e->getMessage());
        return 'unknown';
    }
}

// Get all media files
function getAllMedia($limit = null, $offset = 0) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT m.*, u.username as uploaded_by_name 
                FROM media m 
                LEFT JOIN users u ON m.uploaded_by = u.id 
                ORDER BY m.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT $limit OFFSET $offset";
        }
        
        return $db->getRows($sql);
    } catch (Exception $e) {
        error_log("getAllMedia error: " . $e->getMessage());
        return [];
    }
}

// Get media by ID
function getMediaById($id) {
    try {
        $db = Database::getInstance();
        return $db->getRow("SELECT * FROM media WHERE id = ?", [$id]);
    } catch (Exception $e) {
        error_log("getMediaById error: " . $e->getMessage());
        return false;
    }
}

// Delete media file
function deleteMedia($id) {
    try {
        $db = Database::getInstance();
        
        // Get file info
        $media = getMediaById($id);
        if (!$media) {
            return false;
        }
        
        // Delete physical file
        $fullPath = '../../' . $media['file_path'];
        if (file_exists($fullPath)) {
            unlink($fullPath);
        }
        
        // Delete from database
        return $db->delete('media', 'id = ?', [$id]);
    } catch (Exception $e) {
        error_log("deleteMedia error: " . $e->getMessage());
        return false;
    }
}

// Upload media file
function uploadMedia($file, $uploadedBy) {
    try {
        $uploadDir = '../../uploads/media/';
        
        // Create upload directory if it doesn't exist
        if (!file_exists($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }
        
        $originalName = $file['name'];
        $tempName = $file['tmp_name'];
        $fileSize = $file['size'];
        $mimeType = $file['type'];
        
        // Generate unique filename
        $extension = pathinfo($originalName, PATHINFO_EXTENSION);
        $filename = uniqid() . '_' . time() . '.' . $extension;
        $filePath = $uploadDir . $filename;
        
        // Determine file type
        $fileType = getMediaFileType($mimeType);
        
        if (move_uploaded_file($tempName, $filePath)) {
            // Save to database
            $db = Database::getInstance();
            $mediaId = $db->insert('media', [
                'filename' => $filename,
                'original_name' => $originalName,
                'file_path' => 'uploads/media/' . $filename,
                'file_size' => $fileSize,
                'mime_type' => $mimeType,
                'file_type' => $fileType,
                'uploaded_by' => $uploadedBy
            ]);
            
            return $mediaId ? getMediaById($mediaId) : false;
        }
        
        return false;
    } catch (Exception $e) {
        error_log("uploadMedia error: " . $e->getMessage());
        return false;
    }
}

// Get file type from MIME type
function getMediaFileType($mimeType) {
    if (strpos($mimeType, 'image/') === 0) return 'image';
    if (strpos($mimeType, 'video/') === 0) return 'video';
    if (strpos($mimeType, 'audio/') === 0) return 'audio';
    if ($mimeType === 'application/pdf') return 'pdf';
    if (in_array($mimeType, ['application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])) return 'document';
    return 'other';
}

// Format file size
function formatMediaFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

// Count total blog posts
function countBlogPosts() {
    try {
        $db = Database::getInstance();
        return $db->getValue("SELECT COUNT(*) FROM blog_posts WHERE status = 'published'");
    } catch (Exception $e) {
        error_log("countBlogPosts error: " . $e->getMessage());
        return 0;
    }
}

// Debug function
function debug($data) {
    if (defined('DEBUG_MODE') && DEBUG_MODE) {
        echo '<pre>';
        var_dump($data);
        echo '</pre>';
    }
}

// Log function for better debugging
function logMessage($message, $type = 'info') {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [{$type}] {$message}";
    error_log($logMessage);
}

// Format currency amount
function formatCurrency($amount, $currency = 'BDT', $decimals = 2) {
    try {
        $formatted = number_format($amount, $decimals);
        
        switch ($currency) {
            case 'BDT':
                return '৳' . $formatted;
            case 'USD':
                return '$' . $formatted;
            case 'EUR':
                return '€' . $formatted;
            default:
                return $formatted . ' ' . $currency;
        }
    } catch (Exception $e) {
        error_log("formatCurrency error: " . $e->getMessage());
        return $amount;
    }
}

// Check system requirements
function checkSystemRequirements() {
    $requirements = [
        'php_version' => version_compare(PHP_VERSION, '7.4.0', '>='),
        'pdo_extension' => extension_loaded('pdo'),
        'pdo_mysql' => extension_loaded('pdo_mysql'),
        'session_support' => function_exists('session_start'),
        'json_support' => function_exists('json_encode'),
        'curl_support' => function_exists('curl_init'),
        'gd_support' => extension_loaded('gd'),
        'mbstring_support' => extension_loaded('mbstring')
    ];
    
    return $requirements;
}

// Get system info for debugging
function getSystemInfo() {
    return [
        'php_version' => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
        'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown',
        'script_filename' => $_SERVER['SCRIPT_FILENAME'] ?? 'Unknown',
        'memory_limit' => ini_get('memory_limit'),
        'max_execution_time' => ini_get('max_execution_time'),
        'upload_max_filesize' => ini_get('upload_max_filesize'),
        'post_max_size' => ini_get('post_max_size'),
        'display_errors' => ini_get('display_errors'),
        'error_reporting' => error_reporting(),
        'timezone' => date_default_timezone_get()
    ];
}

// Cleanup old sessions and temporary data
function cleanupOldData() {
    try {
        $db = Database::getInstance();
        
        // Clean up old test settings (older than 1 hour)
        $db->delete('settings', "setting_key LIKE 'test_setting_%' AND created_at < DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        
        // Log cleanup
        logMessage('Old test data cleaned up', 'info');
        return true;
    } catch (Exception $e) {
        error_log("cleanupOldData error: " . $e->getMessage());
        return false;
    }
}

/**
 * Update page content by slug
 */
function updatePageBySlug($slug, $data) {
    try {
        $db = Database::getInstance();
        
        // Check if page exists
        $page = getPageBySlug($slug);
        
        if ($page) {
            // Update existing page
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $db->update('pages', $data, 'slug = ?', [$slug]);
        } else {
            // Create new page
            $data['slug'] = $slug;
            $data['status'] = 'active';
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            return $db->insert('pages', $data);
        }
    } catch (Exception $e) {
        error_log("updatePageBySlug error for '{$slug}': " . $e->getMessage());
        return false;
    }
}

/**
 * Get all pages for admin
 */
function getAllPages($activeOnly = false) {
    try {
        $db = Database::getInstance();
        
        $sql = "SELECT * FROM pages";
        if ($activeOnly) {
            $sql .= " WHERE status = 'active'";
        }
        $sql .= " ORDER BY title ASC";
        
        return $db->getRows($sql);
    } catch (Exception $e) {
        error_log("getAllPages error: " . $e->getMessage());
        return [];
    }
}

/**
 * Sanitize HTML content while preserving safe tags
 */
function sanitizeHTML($content) {
    try {
        // Allow specific HTML tags that are safe for content
        $allowed_tags = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><a><img><blockquote><table><thead><tbody><tr><th><td><div><span><hr>';
        
        // Strip tags not in allowed list
        $content = strip_tags($content, $allowed_tags);
        
        // Additional security: remove javascript and other dangerous attributes
        $content = preg_replace('/on\w+="[^"]*"/i', '', $content);
        $content = preg_replace('/javascript:/i', '', $content);
        
        return $content;
    } catch (Exception $e) {
        error_log("sanitizeHTML error: " . $e->getMessage());
        return strip_tags($content); // Fallback to basic strip_tags
    }
}

// Initialize error reporting if in debug mode
if (defined('DEBUG_MODE') && DEBUG_MODE) {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
    ini_set('log_errors', 1);

    // Helper function to handle button URLs
    function handleButtonUrl($url) {
        // Handle JavaScript URLs safely
        if (strpos($url, 'javascript:') === 0) {
            return 'javascript:void(0)';
        }
        return htmlspecialchars($url);
    }
}

// Periodic license check function
function periodicLicenseCheck() {
    static $lastCheck = 0;
    $checkInterval = 300; // 5 minutes

    if ((time() - $lastCheck) > $checkInterval) {
        $lastCheck = time();

        if (!performBackgroundLicenseCheck()) {
            logSecurityEvent('LICENSE_CHECK_FAILED', 'Background license check failed');
        }
    }
}

// Check if script is licensed
function isScriptLicensed() {
    try {
        return verifyScriptLicense();
    } catch (Exception $e) {
        return false;
    }
}

// Get license information
function getLicenseInfo() {
    $verificationFile = __DIR__ . '/config/verification.php';

    if (file_exists($verificationFile)) {
        return include $verificationFile;
    }

    return null;
}

// Perform periodic license check
function performPeriodicLicenseCheck() {
    static $lastCheck = 0;
    $checkInterval = 300; // 5 minutes

    if ((time() - $lastCheck) > $checkInterval) {
        $lastCheck = time();

        if (function_exists('performBackgroundLicenseCheck')) {
            return performBackgroundLicenseCheck();
        }
    }

    return true;
}

// Get license status
function getLicenseStatus() {
    $verificationFile = __DIR__ . '/../config/verification.php';

    if (file_exists($verificationFile)) {
        return include $verificationFile;
    }

    return null;
}

// Check if license is valid
function isLicenseValid() {
    try {
        return verifyExchangeBridgeLicense();
    } catch (Exception $e) {
        return false;
    }
}

// Log security events
function logSecurityEvent($eventType, $message) {
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[{$timestamp}] [SECURITY] [{$eventType}] {$message}";
    error_log($logMessage);
    
    // You can also save to database if needed
    try {
        $db = Database::getInstance();
        $db->insert('security_logs', [
            'event_type' => $eventType,
            'message' => $message,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
            'created_at' => date('Y-m-d H:i:s')
        ]);
    } catch (Exception $e) {
        // Silently continue if logging to database fails
    }
}

// Perform background license check
function performBackgroundLicenseCheck() {
    try {
        // This function should contain the actual license verification logic
        // For now, we'll just return true as a placeholder
        return true;
    } catch (Exception $e) {
        logMessage("Background license check failed: " . $e->getMessage(), 'error');
        return false;
    }
}

// Verify script license
function verifyScriptLicense() {
    try {
        // This function should contain the actual license verification logic
        // For now, we'll just return true as a placeholder
        return true;
    } catch (Exception $e) {
        logMessage("Script license verification failed: " . $e->getMessage(), 'error');
        return false;
    }
}

// Verify Exchange Bridge license
function verifyExchangeBridgeLicense() {
    try {
        // This function should contain the actual Exchange Bridge license verification logic
        // For now, we'll just return true as a placeholder
        return true;
    } catch (Exception $e) {
        logMessage("Exchange Bridge license verification failed: " . $e->getMessage(), 'error');
        return false;
    }
}

?>