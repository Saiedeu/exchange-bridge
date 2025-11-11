<?php
/**
 * ExchangeBridge - Get Exchange Rate API
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

// Get all active exchange rates
$db = Database::getInstance();
$exchangeRates = $db->getRows(
    "SELECT er.from_currency, er.to_currency, er.rate 
     FROM exchange_rates er
     JOIN currencies fc ON er.from_currency = fc.code
     JOIN currencies tc ON er.to_currency = tc.code
     WHERE er.status = 'active' AND fc.status = 'active' AND tc.status = 'active'"
);

// Get all active currencies
$currencies = $db->getRows("SELECT code, display_name FROM currencies WHERE status = 'active'");

// Format exchange rates for JSON response
$ratesArray = [];
foreach ($exchangeRates as $rate) {
    $rateKey = $rate['from_currency'] . '_' . $rate['to_currency'];
    $ratesArray[$rateKey] = (float) $rate['rate'];
}

// Format currencies for JSON response
$currenciesArray = [];
foreach ($currencies as $currency) {
    $currenciesArray[$currency['code']] = !empty($currency['display_name']) ? $currency['display_name'] : $currency['code'];
}

// Return JSON response
echo json_encode([
    'success' => true,
    'rates' => $ratesArray,
    'currencies' => $currenciesArray
]);