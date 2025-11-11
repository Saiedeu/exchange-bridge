<?php
/**
 * ExchangeBridge - Exchange Process API
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */



// Start session
session_start();

// Define access constant
define('ALLOW_ACCESS', true);

// Include configuration files
require_once '../config/config.php';
require_once '../includes/db.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

// Check if this is a request to generate ID only
$input = json_decode(file_get_contents('php://input'), true);
if ($input && isset($input['action']) && $input['action'] === 'generate_id') {
    // Function to generate sequential exchange ID
    function generateSequentialExchangeId() {
        try {
            $db = Database::getInstance();
            
            // Get current date
            $currentDate = date('Y-m-d');
            $todayPrefix = date('ymd'); // YYMMDD format
            
            // Get the highest sequence number for today
            $lastId = $db->getValue(
                "SELECT MAX(CAST(SUBSTRING(reference_id, -2) AS UNSIGNED)) as max_seq 
                 FROM exchanges 
                 WHERE DATE(created_at) = ? 
                 AND reference_id LIKE ?",
                [$currentDate, "EB-{$todayPrefix}%"]
            );
            
            // Calculate next sequence number
            $nextSequence = ($lastId !== false && $lastId !== null) ? intval($lastId) + 1 : 1;
            $sequenceStr = str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
            
            // Generate the ID
            $exchangeId = "EB-{$todayPrefix}{$sequenceStr}";
            
            return $exchangeId;
            
        } catch (Exception $e) {
            error_log("Error generating sequential exchange ID: " . $e->getMessage());
            
            // Fallback to timestamp-based ID with proper format
            $timestamp = date('ymd');
            $randomSeq = str_pad(rand(1, 99), 2, '0', STR_PAD_LEFT);
            return "EB-{$timestamp}{$randomSeq}";
        }
    }
    
    try {
        $exchangeId = generateSequentialExchangeId();
        echo json_encode([
            'success' => true,
            'exchange_id' => $exchangeId,
            'timestamp' => time(),
            'date' => date('Y-m-d H:i:s')
        ]);
        exit;
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
        exit;
    }
}

// Get form data
$referenceId = isset($_POST['reference_id']) ? sanitizeInput($_POST['reference_id']) : '';
$customerName = isset($_POST['customer_name']) ? sanitizeInput($_POST['customer_name']) : '';
$customerEmail = isset($_POST['customer_email']) ? sanitizeInput($_POST['customer_email']) : '';
$customerPhone = isset($_POST['customer_phone']) ? sanitizeInput($_POST['customer_phone']) : '';
$paymentAddress = isset($_POST['payment_address']) ? sanitizeInput($_POST['payment_address']) : '';
$fromCurrency = isset($_POST['from_currency']) ? sanitizeInput($_POST['from_currency']) : '';
$toCurrency = isset($_POST['to_currency']) ? sanitizeInput($_POST['to_currency']) : '';
$sendAmount = isset($_POST['send_amount']) ? floatval($_POST['send_amount']) : 0;
$receiveAmount = isset($_POST['receive_amount']) ? floatval($_POST['receive_amount']) : 0;

// Validate form data
$errors = [];

if (empty($referenceId)) {
    $errors[] = 'Reference ID is required';
}

if (empty($customerName)) {
    $errors[] = 'Customer name is required';
}

if (empty($customerEmail) || !isValidEmail($customerEmail)) {
    $errors[] = 'Valid email address is required';
}

if (empty($customerPhone)) {
    $errors[] = 'Phone number is required';
}

if (empty($paymentAddress)) {
    $errors[] = 'Payment address is required';
}

if (empty($fromCurrency)) {
    $errors[] = 'From currency is required';
}

if (empty($toCurrency)) {
    $errors[] = 'To currency is required';
}

if ($sendAmount <= 0) {
    $errors[] = 'Send amount must be greater than zero';
}

if ($receiveAmount <= 0) {
    $errors[] = 'Receive amount must be greater than zero';
}

// If there are validation errors, return them
if (!empty($errors)) {
    echo json_encode(['success' => false, 'message' => implode(', ', $errors)]);
    exit;
}

// Get exchange rate
$exchangeRate = getExchangeRate($fromCurrency, $toCurrency);

// If exchange rate is not found, return error
if ($exchangeRate === 0) {
    echo json_encode(['success' => false, 'message' => 'Exchange rate not found for this currency pair']);
    exit;
}

// Check if currencies exist
$db = Database::getInstance();
$fromCurrencyExists = $db->getValue("SELECT COUNT(*) FROM currencies WHERE code = ? AND status = 'active'", [$fromCurrency]);
$toCurrencyExists = $db->getValue("SELECT COUNT(*) FROM currencies WHERE code = ? AND status = 'active'", [$toCurrency]);

if (!$fromCurrencyExists || !$toCurrencyExists) {
    echo json_encode(['success' => false, 'message' => 'One or both currencies are not active or do not exist']);
    exit;
}

// Check if reference ID already exists
$referenceExists = $db->getValue("SELECT COUNT(*) FROM exchanges WHERE reference_id = ?", [$referenceId]);

if ($referenceExists) {
    echo json_encode(['success' => false, 'message' => 'Reference ID already exists']);
    exit;
}

// Insert exchange data
$exchangeId = $db->insert('exchanges', [
    'reference_id' => $referenceId,
    'customer_name' => $customerName,
    'customer_email' => $customerEmail,
    'customer_phone' => $customerPhone,
    'payment_address' => $paymentAddress,
    'from_currency' => $fromCurrency,
    'to_currency' => $toCurrency,
    'send_amount' => $sendAmount,
    'receive_amount' => $receiveAmount,
    'exchange_rate' => $exchangeRate,
    'status' => 'pending'
]);

if ($exchangeId) {
    // Return success response
    echo json_encode(['success' => true, 'message' => 'Exchange created successfully', 'exchange_id' => $exchangeId]);
} else {
    // Return error response
    echo json_encode(['success' => false, 'message' => 'Failed to create exchange']);
}