<?php
/**
 * ExchangeBridge - Exchange ID Generation API
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

header('Content-Type: application/json');

// Allow CORS
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include your database configuration
require_once(dirname(__DIR__) . '/config/config.php');
require_once(dirname(__DIR__) . '/includes/functions.php');
require_once(dirname(__DIR__) . '/includes/db.php');

try {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || ($input['action'] !== 'generate_exchange_id' && $input['action'] !== 'generate_sequential_id')) {
        throw new Exception('Invalid request');
    }
    
    // Set timezone
    $timezone = getSetting('site_timezone', 'Asia/Dhaka');
    date_default_timezone_set($timezone);
    
    // Generate sequential exchange ID
    $exchangeId = generateSequentialExchangeId();
    
    // Return success response
    echo json_encode([
        'success' => true,
        'exchange_id' => $exchangeId,
        'timestamp' => time(),
        'timezone' => $timezone
    ]);
    
} catch (Exception $e) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'timestamp' => time()
    ]);
}

/**
 * Generate sequential exchange ID for the current date
 * Format: EB-YYMMDD01, EB-YYMMDD02, etc.
 */
function generateSequentialExchangeId() {
    $db = Database::getInstance();
    
    // Set timezone
    date_default_timezone_set(getSetting('site_timezone', 'Asia/Dhaka'));
    
    // Get current date in YYMMDD format
    $currentDate = date('ymd');
    
    try {
        // Begin transaction for thread safety
        $db->getConnection()->beginTransaction();
        
        // Get the highest serial number for today
        $stmt = $db->getConnection()->prepare("
            SELECT MAX(CAST(SUBSTRING(reference_id, 10, 2) AS UNSIGNED)) as max_serial 
            FROM exchanges 
            WHERE SUBSTRING(reference_id, 4, 6) = :date_part 
            AND reference_id LIKE 'EB-%'
            AND LENGTH(reference_id) = 11
            AND DATE(created_at) = CURDATE()
            FOR UPDATE
        ");
        $stmt->bindParam(':date_part', $currentDate);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Calculate next serial number
        $nextSerial = 1;
        if ($result && $result['max_serial'] !== null) {
            $nextSerial = intval($result['max_serial']) + 1;
        }
        
        // Ensure serial number doesn't exceed 99
        if ($nextSerial > 99) {
            $nextSerial = 99; // Maximum 99 exchanges per day
        }
        
        // Format serial number with leading zeros
        $serialNumber = str_pad($nextSerial, 2, '0', STR_PAD_LEFT);
        
        // Generate the exchange ID
        $exchangeId = "EB-{$currentDate}{$serialNumber}";
        
        // Double-check this ID doesn't already exist
        $checkStmt = $db->getConnection()->prepare("SELECT COUNT(*) FROM exchanges WHERE reference_id = :exchange_id");
        $checkStmt->bindParam(':exchange_id', $exchangeId);
        $checkStmt->execute();
        
        if ($checkStmt->fetchColumn() > 0) {
            // If somehow it exists, increment once more
            $nextSerial++;
            if ($nextSerial > 99) {
                throw new Exception('Daily exchange limit reached (99 exchanges per day)');
            }
            $serialNumber = str_pad($nextSerial, 2, '0', STR_PAD_LEFT);
            $exchangeId = "EB-{$currentDate}{$serialNumber}";
        }
        
        // Commit transaction
        $db->getConnection()->commit();
        
        return $exchangeId;
        
    } catch (Exception $e) {
        // Roll back transaction on error
        $db->getConnection()->rollBack();
        
        // Log the error
        error_log("Sequential ID generation error: " . $e->getMessage());
        
        // Fallback to time-based ID with date format
        $timestamp = time();
        $random = rand(10, 99);
        return "EB-{$currentDate}{$random}";
    }
}
?>