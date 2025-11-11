<?php
/**
 * ExchangeBridge - Transaction Track API
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

// Get reference ID from request
$referenceId = isset($_GET['ref']) ? sanitizeInput($_GET['ref']) : '';

// If reference ID is empty, return error
if (empty($referenceId)) {
    echo json_encode(['success' => false, 'message' => 'Reference ID is required']);
    exit;
}

// Get exchange details by reference ID
$exchange = getExchangeByReferenceId($referenceId);

// If exchange not found, return error
if (!$exchange) {
    echo json_encode(['success' => false, 'message' => 'Exchange not found']);
    exit;
}

// Format exchange data for response
$response = [
    'success' => true,
    'exchange' => [
        'reference_id' => $exchange['reference_id'],
        'created_at' => $exchange['created_at'],
        'updated_at' => $exchange['updated_at'],
        'status' => $exchange['status'],
        'from_currency' => [
            'code' => $exchange['from_currency'],
            'name' => $exchange['from_currency_name'],
            'display_name' => $exchange['from_display_name'] ?: $exchange['from_currency']
        ],
        'to_currency' => [
            'code' => $exchange['to_currency'],
            'name' => $exchange['to_currency_name'],
            'display_name' => $exchange['to_display_name'] ?: $exchange['to_currency']
        ],
        'send_amount' => $exchange['send_amount'],
        'receive_amount' => $exchange['receive_amount'],
        'exchange_rate' => $exchange['exchange_rate']
    ]
];

// Return success response
echo json_encode($response);