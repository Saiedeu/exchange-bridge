<?php
/**
 * ExchangeBridge - Exchange Wizard Form
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

session_start();

// Define access constant
define('ALLOW_ACCESS', true);

// Include required files
require_once 'config/config.php';
require_once 'config/license.php';
require_once 'includes/app.php';
require_once 'includes/db.php';
require_once 'includes/functions.php';
require_once 'includes/security.php';

// Initialize security class
$security = Security::getInstance();

// Check for banned IPs
$security->checkBanStatus();

// Individual IP-based rate limiting with higher margin for main exchange page
$clientIp = $security->getClientIp();
if (!$security->checkRateLimit("exchange_access_{$clientIp}", 100, 3600)) {
    http_response_code(429);
    exit('Too many requests from your IP. Please try again in an hour.');
}

// Set correct timezone to fix time issues
date_default_timezone_set(getSetting('site_timezone', 'Asia/Dhaka'));

// Get all active currencies with enhanced error handling and proper logo paths
try {
    $db = Database::getInstance();
    $currencies = $db->getRows(
        "SELECT id, code, name, display_name, logo, icon_class, background_class, 
         payment_address, address_label, address_type, status 
         FROM currencies 
         WHERE status = ? 
         ORDER BY name ASC",
        ['active']
    );
    
    if (empty($currencies)) {
        error_log("Warning: No active currencies found");
        $currencies = [];
    }
} catch (Exception $e) {
    error_log("Error fetching currencies: " . $e->getMessage());
    $currencies = [];
}

// Get currency pairs with valid exchange rates
try {
    $db = Database::getInstance();
    $exchangeRates = $db->getRows(
        "SELECT er.rate, er.from_currency, er.to_currency, 
         fc.name as from_currency_name, fc.display_name as from_display_name,
         tc.name as to_currency_name, tc.display_name as to_display_name
         FROM exchange_rates er
         JOIN currencies fc ON er.from_currency = fc.code
         JOIN currencies tc ON er.to_currency = tc.code
         WHERE er.status = ? AND fc.status = ? AND tc.status = ?",
        ['active', 'active', 'active']
    );
    
    if (empty($exchangeRates)) {
        error_log("Warning: No active exchange rates found");
        $exchangeRates = [];
    }
} catch (Exception $e) {
    error_log("Error fetching exchange rates: " . $e->getMessage());
    $exchangeRates = [];
}

// Create exchange rates array for JavaScript
$exchangeRatesJS = [];
if (!empty($exchangeRates)) {
    foreach ($exchangeRates as $rate) {
        $key = $rate['from_currency'] . '-' . $rate['to_currency'];
        $exchangeRatesJS[$key] = (float)$rate['rate'];
    }
}

// Get operator contact info with defaults
$operatorContact = [
    'whatsapp' => getSetting('contact_whatsapp', '8801869838872'),
    'phone' => getSetting('contact_phone', '+8801869838872'),
    'email' => getSetting('contact_email', 'support@exchangebridge.com')
];

// Get site settings with defaults
$siteName = getSetting('site_name', 'Exchange Bridge');
$siteTagline = getSetting('site_tagline', 'Exchange Taka Globally');
$contactEmail = getSetting('contact_email', 'info@exchangebridge.com');
$contactPhone = getSetting('contact_phone', '+8801869838872');

// Get all wizard settings from admin panel
$wizardSettings = [
    'wizard_title' => getSetting('wizard_title', 'Fast Exchange in Minutes'),
    'wizard_subtitle' => getSetting('wizard_subtitle', 'Minimum Exchange $5 Dollar'),
    'wizard_heading' => getSetting('wizard_heading', 'Start Exchange'),
    'wizard_footer_text' => getSetting('wizard_footer_text', 'When ordering, give your mobile phone number and when you buy dollars from us, you must send money from your bKash/rocket/cash number. If you send money from other number, your order will be canceled and the rest of the money will be refunded.'),
    'send_section_label' => getSetting('send_section_label', 'SEND'),
    'receive_section_label' => getSetting('receive_section_label', 'RECEIVE'),
    'currency_select_label' => getSetting('currency_select_label', 'Select Exchange Currency'),
    'amount_input_label' => getSetting('amount_input_label', 'Enter Exchange Amount'),
    'receive_amount_label' => getSetting('receive_amount_label', 'You\'ll Get this Amount'),
    'continue_button_text' => getSetting('continue_button_text', 'Continue to Next Step'),
    'contact_step_title' => getSetting('contact_step_title', 'Provide Your Contact Details'),
    'name_field_label' => getSetting('name_field_label', 'Full Name'),
    'email_field_label' => getSetting('email_field_label', 'Email Address'),
    'phone_field_label' => getSetting('phone_field_label', 'Phone Number'),
    'address_field_label' => getSetting('address_field_label', 'Payment Address'),
    'address_help_text' => getSetting('address_help_text', 'This is the address where you want to receive your funds.'),
    'back_button_text' => getSetting('back_button_text', 'Back'),
    'continue_step2_text' => getSetting('continue_step2_text', 'Continue'),
    'confirmation_title' => getSetting('confirmation_title', 'Confirm Your Exchange'),
    'reference_id_title' => getSetting('reference_id_title', 'Exchange Reference ID'),
    'reference_id_message' => getSetting('reference_id_message', 'আপনার এক্সচেঞ্জ স্ট্যাটাস ট্র্যাক করার জন্য এই "Reference ID" সংরক্ষণ করে রাখুন।'),
    'exchange_details_title' => getSetting('exchange_details_title', 'Exchange Details'),
    'payment_details_title' => getSetting('payment_details_title', 'Payment Details'),
    'payment_instruction' => getSetting('payment_instruction', 'আপনার লেনদেন শুরু করতে এই অ্যাকাউন্টে {amount} BDT/USD সেন্ড করুন:'),
    'after_payment_message' => getSetting('after_payment_message', 'পেমেন্ট পাঠানোর পর আপনার "Reference ID" নিয়ে WhatsApp-এ আমাদের অপারেটরের সাথে যোগাযোগ করুন।'),
    'next_steps_title' => getSetting('next_steps_title', 'Next Steps'),
    'whatsapp_contact_message' => getSetting('whatsapp_contact_message', 'আপনার এক্সচেঞ্জ অর্ডার সম্পন্ন করতে হোয়াটসঅ্যাপে আমাদের অপারেটরের সাথে যোগাযোগ করুন:'),
    'whatsapp_button_text' => getSetting('whatsapp_button_text', 'Contact Operator'),
    'final_instruction' => getSetting('final_instruction', 'যোগাযোগ করার সময় আপনার রেফারেন্স আইডি দিন এবং লেনদেন সম্পন্ন করতে অপারেটরের নির্দেশনা অনুসরণ করুন।'),
    'view_receipt_text' => getSetting('view_receipt_text', 'View Receipt'),
    'complete_button_text' => getSetting('complete_button_text', 'Complete Exchange'),
    'next_todo_text' => getSetting('next_todo_text', 'Next To Do'),
    'send_label_text' => getSetting('send_label_text', 'You Send:'),
    'receive_label_text' => getSetting('receive_label_text', 'You Receive:'),
    'rate_label_text' => getSetting('rate_label_text', 'Exchange Rate:'),
    'datetime_label_text' => getSetting('datetime_label_text', 'Date and Time:'),
    'status_label_text' => getSetting('status_label_text', 'Status:'),
    'pending_status' => getSetting('pending_status', 'Pending'),
    'min_amount_error' => getSetting('min_amount_error', 'দয়া করে সর্বনিম্ন ৫ ডলার পরিমাণ লিখুন'),
    'invalid_email_error' => getSetting('invalid_email_error', 'দয়া করে সঠিক ই-মেইল ঠিকানা দিন'),
    'required_fields_error' => getSetting('required_fields_error', 'দয়া করে সব প্রয়োজনীয় ঘর পূরণ করুন'),
    'rate_unavailable_error' => getSetting('rate_unavailable_error', 'এই কারেন্সির জন্য এক্সচেঞ্জ রেট পাওয়া যাচ্ছে না'),
    'amount_required_error' => getSetting('amount_required_error', 'দয়া করে এক্সচেঞ্জ এমাউন্ট প্রবেশ করুন'),
    'exchange_success_message' => getSetting('exchange_success_message', 'আপনার এক্সচেঞ্জ অর্ডার সফলভাবে জমা হয়েছে!'),
    'copy_success_message' => getSetting('copy_success_message', 'অ্যাকাউন্ট নাম্বার ক্লিপবোর্ডে কপি হয়েছে'),
    'wizard_font_family' => getSetting('wizard_font_family', 'hind_siliguri'),
    'wizard_primary_color' => getSetting('wizard_primary_color', '#5dffde'),
    'wizard_progress_bar_color' => getSetting('wizard_progress_bar_color', '#285FB7'),
    'wizard_border_radius' => getSetting('wizard_border_radius', 'medium'),
    'enable_animations' => getSetting('enable_animations', 'yes'),
    'minimum_exchange_amount' => (float)getSetting('minimum_exchange_amount', '5')
];

// Enhanced currency data processing with proper logo path handling
$processedCurrencies = [];
if (!empty($currencies)) {
    foreach ($currencies as $currency) {
        $processedCurrency = [
            'id' => $currency['id'] ?? 0,
            'code' => $currency['code'] ?? '',
            'name' => $currency['name'] ?? 'Unknown Currency',
            'display_name' => $currency['display_name'] ?? ($currency['name'] ?? 'Unknown'),
            'logo' => '',
            'logo_exists' => false,
            'background_class' => $currency['background_class'] ?? 'bg-blue-500 text-white',
            'icon_class' => $currency['icon_class'] ?? 'fas fa-money-bill-wave',
            'payment_address' => $currency['payment_address'] ?? '',
            'address_label' => $currency['address_label'] ?? 'Payment Address',
            'address_type' => $currency['address_type'] ?? 'address',
            'status' => $currency['status'] ?? 'active'
        ];
        
        // Handle logo path with proper validation
        if (!empty($currency['logo'])) {
            // Define both relative and absolute paths for logo
            $logoRelativePath = 'assets/uploads/currencies/' . $currency['logo'];
            $logoAbsolutePath = $_SERVER['DOCUMENT_ROOT'] . '/' . $logoRelativePath;
            
            // Check if file exists on server
            if (file_exists($logoAbsolutePath)) {
                $processedCurrency['logo'] = $logoRelativePath;
                $processedCurrency['logo_exists'] = true;
            } else {
                // Try with ASSETS_URL constant if defined
                if (defined('ASSETS_URL')) {
                    $alternativePath = ASSETS_URL . '/uploads/currencies/' . $currency['logo'];
                    $processedCurrency['logo'] = $alternativePath;
                    $processedCurrency['logo_exists'] = true; // Assume it exists if using ASSETS_URL
                }
            }
        }
        
        $processedCurrencies[] = $processedCurrency;
    }
}

$currencies = $processedCurrencies;

// Current server time for accurate time synchronization
$currentServerTime = date('Y-m-d H:i:s');
$currentTimestamp = time();

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
        $nextSequence = ($lastId !== false) ? intval($lastId) + 1 : 1;
        $sequenceStr = str_pad($nextSequence, 2, '0', STR_PAD_LEFT);
        
        // Generate the ID
        $exchangeId = "EB-{$todayPrefix}{$sequenceStr}";
        
        return $exchangeId;
        
    } catch (Exception $e) {
        error_log("Error generating sequential exchange ID: " . $e->getMessage());
        
        // Fallback to timestamp-based ID
        $timestamp = date('ymdHis');
        return "EB-{$timestamp}";
    }
}
?>

<!-- Hidden inputs for time synchronization to fix time issues -->
<input type="hidden" id="server-current-time" value="<?php echo $currentServerTime; ?>">
<input type="hidden" id="server-timestamp" value="<?php echo $currentTimestamp; ?>">

<!-- Enhanced Toast Notification System -->
<div id="toast-container" class="fixed top-4 left-1/2 transform -translate-x-1/2 z-50">
    <!-- Success Toast -->
    <div id="success-toast" class="hidden bg-green-100 border border-green-400 text-green-700 px-6 py-4 rounded-lg shadow-lg mb-2 max-w-md w-full mx-4">
        <div class="flex items-center">
            <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
            <div class="flex-1">
                <div class="font-bold text-lg">Success!</div>
                <div id="success-toast-message" class="text-sm font-semibold"></div>
            </div>
            <button onclick="hideToast('success-toast')" class="ml-4 text-green-700 hover:text-green-900 font-bold">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Error Toast -->
    <div id="error-toast" class="hidden bg-red-100 border border-red-400 text-red-700 px-6 py-4 rounded-lg shadow-lg mb-2 max-w-md w-full mx-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-circle text-red-500 mr-3 text-xl"></i>
            <div class="flex-1">
                <div class="font-bold text-lg">Error!</div>
                <div id="error-toast-message" class="text-sm font-semibold"></div>
            </div>
            <button onclick="hideToast('error-toast')" class="ml-4 text-red-700 hover:text-red-900 font-bold">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
    
    <!-- Warning Toast -->
    <div id="warning-toast" class="hidden bg-yellow-100 border border-yellow-400 text-yellow-700 px-6 py-4 rounded-lg shadow-lg mb-2 max-w-md w-full mx-4">
        <div class="flex items-center">
            <i class="fas fa-exclamation-triangle text-yellow-500 mr-3 text-xl"></i>
            <div class="flex-1">
                <div class="font-bold text-lg">Warning!</div>
                <div id="warning-toast-message" class="text-sm font-semibold"></div>
            </div>
            <button onclick="hideToast('warning-toast')" class="ml-4 text-yellow-700 hover:text-yellow-900 font-bold">
                <i class="fas fa-times"></i>
            </button>
        </div>
    </div>
</div>

<!-- Enhanced Exchange Wizard -->
<div id="exchange-wizard" class="mb-8">
    <!-- Step 1: Exchange Form -->
    <div id="step-1-exchange" class="exchange-step bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg animated-border">
        <div class="bg-gray-50 dark:bg-gray-700 p-4 border-b border-gray-200 dark:border-gray-600">
            <div class="step-indicator">
                <div class="step active">
                    <div class="step-number">1</div>
                    <div class="step-title"><strong>Exchange Details</strong></div>
                </div>
                <div class="step">
                    <div class="step-number">2</div>
                    <div class="step-title"><strong>Contact Information</strong></div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-title"><strong>Confirmation</strong></div>
                </div>
            </div>
            
            <h2 class="text-xl font-bold text-center"><?php echo htmlspecialchars($wizardSettings['wizard_title']); ?></h2>
        </div>
        
        <div class="p-4 section-content">
            <div class="text-center mb-4 text-red-500 font-bold">
                <p><?php echo htmlspecialchars($wizardSettings['wizard_subtitle']); ?></p>
            </div>
            
            <h3 class="text-2xl font-bold text-center mb-6"><?php echo htmlspecialchars($wizardSettings['wizard_heading']); ?></h3>
            
            <?php if (empty($currencies)): ?>
            <div class="text-center py-8">
                <div class="bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded mb-4">
                    <strong>Warning:</strong> No currencies are currently available for exchange.
                </div>
            </div>
            <?php else: ?>
            
            <div class="exchange-form-container max-w-md mx-auto">
                <form id="exchange-form" class="exchange-form-vertical">
                    <!-- CSRF Protection -->
                    <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                    
                    <!-- SEND Section -->
                    <div class="exchange-form-section">
                        <div class="text-center mb-3">
                            <span class="inline-block px-3 py-1 bg-blue-100 dark:bg-blue-900 text-blue-600 dark:text-blue-300 rounded-full font-bold">
                                <i class="fas fa-arrow-up text-blue-500 mr-1"></i> <?php echo htmlspecialchars($wizardSettings['send_section_label']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['currency_select_label']); ?></label>
                            <div class="custom-select-wrapper w-full" id="send-currency-wrapper">
                                <div class="custom-select border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                    <div class="custom-select__trigger p-3 flex justify-between items-center cursor-pointer">
                                        <div class="custom-select__selected-display flex items-center">
                                            <?php 
                                            $defaultCurrency = $currencies[0];
                                            ?>
                                            <div class="payment-icon <?php echo htmlspecialchars($defaultCurrency['background_class']); ?> w-7 h-7 mr-3 rounded-full flex items-center justify-center">
                                                <?php if ($defaultCurrency['logo_exists'] && !empty($defaultCurrency['logo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($defaultCurrency['logo']); ?>" alt="<?php echo htmlspecialchars($defaultCurrency['name']); ?>" class="w-6 h-6 object-contain rounded-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <i class="<?php echo htmlspecialchars($defaultCurrency['icon_class']); ?> text-sm" style="display: none;"></i>
                                                <?php else: ?>
                                                    <i class="<?php echo htmlspecialchars($defaultCurrency['icon_class']); ?> text-sm"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span class="custom-select__selected-value text-base font-bold" data-value="<?php echo htmlspecialchars($defaultCurrency['code']); ?>"><?php echo htmlspecialchars($defaultCurrency['name']); ?></span>
                                        </div>
                                        <div class="arrow transition-transform duration-200">
                                            <i class="fas fa-chevron-down text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="custom-options absolute z-20 hidden bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-b w-full mt-[-1px] currency-dropdown">
                                        <?php foreach ($currencies as $currency): ?>
                                            <div class="custom-option currency-option p-3 flex items-center hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-all duration-150" data-value="<?php echo htmlspecialchars($currency['code']); ?>">
                                                <div class="payment-icon <?php echo htmlspecialchars($currency['background_class']); ?> w-8 h-8 mr-3 flex-shrink-0 rounded-full flex items-center justify-center">
                                                    <?php if ($currency['logo_exists'] && !empty($currency['logo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($currency['logo']); ?>" alt="<?php echo htmlspecialchars($currency['name']); ?>" class="w-7 h-7 object-contain rounded-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <i class="<?php echo htmlspecialchars($currency['icon_class']); ?> text-sm" style="display: none;"></i>
                                                    <?php else: ?>
                                                        <i class="<?php echo htmlspecialchars($currency['icon_class']); ?> text-sm"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="currency-name text-base font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($currency['name']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['amount_input_label']); ?></label>
                            <input type="number" id="send-amount" name="sendAmount" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-3 amount-input text-center text-lg font-semibold" placeholder="0.00" min="<?php echo $wizardSettings['minimum_exchange_amount']; ?>" step="0.01" required>
                            <small class="block text-center text-red-500 dark:text-red-400 mt-1 font-semibold">সর্বনিম্ন পরিমাণ: <?php echo $wizardSettings['minimum_exchange_amount']; ?> ডলার</small>
                        </div>
                    </div>
                    
                    <!-- Exchange Direction Button -->
                    <div class="exchange-direction-container">
                        <div class="exchange-direction-button" id="swap-currencies">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                    </div>
                    
                    <!-- RECEIVE Section -->
                    <div class="exchange-form-section">
                        <div class="text-center mb-3">
                            <span class="inline-block px-3 py-1 bg-green-100 dark:bg-green-900 text-green-600 dark:text-green-300 rounded-full font-bold">
                                <i class="fas fa-arrow-down text-green-500 mr-1"></i> <?php echo htmlspecialchars($wizardSettings['receive_section_label']); ?>
                            </span>
                        </div>
                        
                        <div class="mb-3">
                            <label class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['currency_select_label']); ?></label>
                            <div class="custom-select-wrapper w-full" id="receive-currency-wrapper">
                                <div class="custom-select border border-gray-300 dark:border-gray-600 rounded bg-white dark:bg-gray-700">
                                    <div class="custom-select__trigger p-3 flex justify-between items-center cursor-pointer">
                                        <div class="custom-select__selected-display flex items-center">
                                            <?php 
                                            $secondCurrency = count($currencies) > 1 ? $currencies[1] : $currencies[0];
                                            ?>
                                            <div class="payment-icon <?php echo htmlspecialchars($secondCurrency['background_class']); ?> w-7 h-7 mr-3 rounded-full flex items-center justify-center">
                                                <?php if ($secondCurrency['logo_exists'] && !empty($secondCurrency['logo'])): ?>
                                                    <img src="<?php echo htmlspecialchars($secondCurrency['logo']); ?>" alt="<?php echo htmlspecialchars($secondCurrency['name']); ?>" class="w-6 h-6 object-contain rounded-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                    <i class="<?php echo htmlspecialchars($secondCurrency['icon_class']); ?> text-sm" style="display: none;"></i>
                                                <?php else: ?>
                                                    <i class="<?php echo htmlspecialchars($secondCurrency['icon_class']); ?> text-sm"></i>
                                                <?php endif; ?>
                                            </div>
                                            <span class="custom-select__selected-value text-base font-bold" data-value="<?php echo htmlspecialchars($secondCurrency['code']); ?>"><?php echo htmlspecialchars($secondCurrency['name']); ?></span>
                                        </div>
                                        <div class="arrow transition-transform duration-200">
                                            <i class="fas fa-chevron-down text-sm"></i>
                                        </div>
                                    </div>
                                    <div class="custom-options absolute z-20 hidden bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-b w-full mt-[-1px] currency-dropdown">
                                        <?php foreach ($currencies as $currency): ?>
                                            <div class="custom-option currency-option p-3 flex items-center hover:bg-gray-100 dark:hover:bg-gray-600 cursor-pointer transition-all duration-150" data-value="<?php echo htmlspecialchars($currency['code']); ?>">
                                                <div class="payment-icon <?php echo htmlspecialchars($currency['background_class']); ?> w-8 h-8 mr-3 flex-shrink-0 rounded-full flex items-center justify-center">
                                                    <?php if ($currency['logo_exists'] && !empty($currency['logo'])): ?>
                                                        <img src="<?php echo htmlspecialchars($currency['logo']); ?>" alt="<?php echo htmlspecialchars($currency['name']); ?>" class="w-7 h-7 object-contain rounded-full" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                                        <i class="<?php echo htmlspecialchars($currency['icon_class']); ?> text-sm" style="display: none;"></i>
                                                    <?php else: ?>
                                                        <i class="<?php echo htmlspecialchars($currency['icon_class']); ?> text-sm"></i>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="currency-name text-base font-bold text-gray-800 dark:text-gray-200"><?php echo htmlspecialchars($currency['name']); ?></span>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <label class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['receive_amount_label']); ?></label>
                            <input type="number" id="receive-amount" name="receiveAmount" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-3 amount-input text-center text-lg font-semibold" placeholder="0.00" readonly>
                        </div>
                    </div>
                    
                    <!-- Exchange Rate Display -->
                    <div id="exchange-rate-display" class="exchange-rate-display font-semibold">
                        <i class="fas fa-chart-line mr-2"></i> Exchange Rate: Loading...
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="button" id="continue-to-step-2" class="exchange-btn px-6 py-3 rounded-full font-bold text-white shadow-md hover:shadow-lg">
                            <i class="fas fa-exchange-alt mr-2"></i> <?php echo htmlspecialchars($wizardSettings['continue_button_text']); ?>
                        </button>
                    </div>
                </form>
            </div>
            
            <?php endif; ?>
        </div>
        
        <div class="p-4 bg-gray-50 dark:bg-gray-700 border-t border-gray-200 dark:border-gray-600">
            <div class="max-w-2xl mx-auto">
                <p class="text-sm text-gray-600 dark:text-gray-400 text-center font-medium">
                    <?php echo htmlspecialchars($wizardSettings['wizard_footer_text']); ?>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Step 2: Contact Information -->
    <div id="step-2-contact" class="exchange-step bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg animated-border" style="display: none;">
        <div class="bg-gray-50 dark:bg-gray-700 p-4 border-b border-gray-200 dark:border-gray-600">
            <div class="step-indicator">
                <div class="step completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-title"><strong>Exchange Details</strong></div>
                </div>
                <div class="step active">
                    <div class="step-number">2</div>
                    <div class="step-title"><strong>Contact Information</strong></div>
                </div>
                <div class="step">
                    <div class="step-number">3</div>
                    <div class="step-title"><strong>Confirmation</strong></div>
                </div>
            </div>
            
            <h2 class="text-xl font-bold text-center"><?php echo htmlspecialchars($wizardSettings['contact_step_title']); ?></h2>
        </div>
        
        <div class="p-6 section-content">
            <form id="contact-form" class="max-w-lg mx-auto">
                <!-- CSRF Protection -->
                <input type="hidden" name="csrf_token" value="<?php echo $security->generateCSRFToken(); ?>">
                
                <div class="mb-4">
                    <label for="name" class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['name_field_label']); ?></label>
                    <input type="text" id="name" name="name" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-lg text-center font-semibold" required maxlength="100" pattern="[a-zA-Z\s\.\-']+" title="Name can only contain letters, spaces, dots, hyphens and apostrophes">
                </div>
                
                <div class="mb-4">
                    <label for="email" class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['email_field_label']); ?></label>
                    <input type="email" id="email" name="email" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-lg text-center font-semibold" required maxlength="255">
                </div>
                
                <div class="mb-4">
                    <label for="phone" class="block text-sm font-bold mb-1 text-center"><?php echo htmlspecialchars($wizardSettings['phone_field_label']); ?></label>
                    <input type="tel" id="phone" name="phone" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-lg text-center font-semibold" required pattern="[0-9+\-\s]+" maxlength="20" title="Phone number can only contain numbers, plus, minus and spaces">
                </div>
                
                <div class="mb-4">
                    <label for="payment-address" class="block text-sm font-bold mb-1 text-center">
                        <span id="payment-address-label"><?php echo htmlspecialchars($wizardSettings['address_field_label']); ?></span>
                    </label>
                    <input type="text" id="payment-address" name="paymentAddress" class="w-full border border-gray-300 dark:border-gray-600 dark:bg-gray-700 rounded p-2 text-lg text-center font-semibold" required maxlength="255">
                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1 text-center font-medium">
                        <?php echo htmlspecialchars($wizardSettings['address_help_text']); ?>
                    </p>
                </div>
                
                <div class="flex justify-between mt-6">
                    <button type="button" id="back-to-step-1" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-bold">
                        <i class="fas fa-arrow-left mr-2"></i> <?php echo htmlspecialchars($wizardSettings['back_button_text']); ?>
                    </button>
                    <button type="button" id="continue-to-step-3" class="exchange-btn px-6 py-2 rounded-full font-bold text-white shadow-md hover:shadow-lg">
                        <?php echo htmlspecialchars($wizardSettings['continue_step2_text']); ?> <i class="fas fa-arrow-right ml-2"></i>
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Step 3: Confirmation -->
    <div id="step-3-confirmation" class="exchange-step bg-white dark:bg-gray-800 rounded-lg shadow-card overflow-hidden mb-6 section-bg animated-border" style="display: none;">
        <div class="bg-gray-50 dark:bg-gray-700 p-4 border-b border-gray-200 dark:border-gray-600">
            <div class="step-indicator">
                <div class="step completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-title"><strong>Exchange Details</strong></div>
                </div>
                <div class="step completed">
                    <div class="step-number"><i class="fas fa-check"></i></div>
                    <div class="step-title"><strong>Contact Information</strong></div>
                </div>
                <div class="step active">
                    <div class="step-number">3</div>
                    <div class="step-title"><strong>Confirmation</strong></div>
                </div>
            </div>
            
            <h2 class="text-xl font-bold text-center"><?php echo htmlspecialchars($wizardSettings['confirmation_title']); ?></h2>
        </div>
        
        <div class="p-6 section-content">
            <div class="max-w-lg mx-auto">
                <!-- Reference ID Section -->
                <div class="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg mb-6">
                    <div class="text-center mb-4">
                        <div class="text-lg font-bold text-blue-700 dark:text-blue-300"><?php echo htmlspecialchars($wizardSettings['reference_id_title']); ?></div>
                        <div id="reference-id" class="text-2xl font-bold text-blue-800 dark:text-blue-200">EB-25053101</div>
                    </div>
                    
                    <div class="text-sm text-blue-700 dark:text-blue-300 text-center font-medium">
                        <?php echo htmlspecialchars($wizardSettings['reference_id_message']); ?>
                    </div>
                </div>
                
                <!-- Exchange Details Section -->
                <div class="mb-6">
                    <h3 class="text-lg font-bold mb-3 text-center"><?php echo htmlspecialchars($wizardSettings['exchange_details_title']); ?></h3>
                    <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                        <div class="text-gray-600 dark:text-gray-400 font-semibold"><?php echo htmlspecialchars($wizardSettings['send_label_text']); ?></div>
                        <div id="confirm-send-amount" class="font-bold text-right">500.00 BDT</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                        <div class="text-gray-600 dark:text-gray-400 font-semibold"><?php echo htmlspecialchars($wizardSettings['receive_label_text']); ?></div>
                        <div id="confirm-receive-amount" class="font-bold text-right">5.00 USD</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                        <div class="text-gray-600 dark:text-gray-400 font-semibold"><?php echo htmlspecialchars($wizardSettings['rate_label_text']); ?></div>
                        <div id="confirm-rate" class="font-bold text-right">1 USD = 100 BDT</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4 border-b border-gray-200 dark:border-gray-700 pb-3 mb-3">
                        <div class="text-gray-600 dark:text-gray-400 font-semibold"><?php echo htmlspecialchars($wizardSettings['datetime_label_text']); ?></div>
                        <div id="confirm-datetime" class="font-bold text-right">--/--/---- --:--:--</div>
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-gray-600 dark:text-gray-400 font-semibold"><?php echo htmlspecialchars($wizardSettings['status_label_text']); ?></div>
                        <div class="font-bold text-right">
                            <span class="status-pending px-2 py-0.5 rounded text-xs font-bold">
                                <?php echo htmlspecialchars($wizardSettings['pending_status']); ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <!-- Complete Button -->
                <div class="text-center mb-6">
                    <button type="button" id="complete-exchange" class="exchange-btn px-8 py-3 rounded-full font-bold text-white shadow-md hover:shadow-lg text-lg">
                        <?php echo htmlspecialchars($wizardSettings['complete_button_text']); ?> <i class="fas fa-check ml-2"></i>
                    </button>
                </div>
                
                <!-- Success Section (Initially Hidden) -->
                <div id="success-section" class="hidden">
                    <!-- Premium Next To Do Section -->
                    <div class="text-center mb-6">
                        <h1 class="premium-next-todo text-2xl font-bold text-white px-6 py-3 rounded-xl shadow-lg">
                            <i class="fas fa-star mr-2"></i><?php echo htmlspecialchars($wizardSettings['next_todo_text']); ?><i class="fas fa-star ml-2"></i>
                        </h1>
                    </div>
                    
                    <!-- Payment Details Section -->
                    <div id="receiving-account-container" class="mb-6">
                        <h3 class="text-lg font-bold mb-3 text-center"><?php echo htmlspecialchars($wizardSettings['payment_details_title']); ?></h3>
                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                            <p class="text-yellow-700 dark:text-yellow-300 mb-2 text-center font-semibold">
                                <?php 
                                $paymentText = str_replace('{amount}', '<span id="confirmation-amount" class="font-bold">500.00 BDT</span>', $wizardSettings['payment_instruction']);
                                echo $paymentText;
                                ?>
                            </p>
                            <div class="flex items-center bg-white dark:bg-gray-800 p-2 rounded mb-2">
                                <span id="account-text" class="flex-grow font-mono text-center font-bold"><a href="tel:01712345678">01712345678</a></span>
                                <button id="copy-account" type="button" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded ml-2 font-bold">
                                    <i class="fas fa-copy"></i> Copy
                                </button>
                            </div>
                            <p class="text-sm text-yellow-600 dark:text-yellow-400 text-center font-medium">
                                <?php echo htmlspecialchars($wizardSettings['after_payment_message']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Next Steps Section -->
                    <div class="mb-6">
                        <h3 class="text-lg font-bold mb-3 text-center"><?php echo htmlspecialchars($wizardSettings['next_steps_title']); ?></h3>
                        <div class="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                            <p class="text-yellow-700 dark:text-yellow-300 mb-3 text-center font-semibold">
                                <?php echo htmlspecialchars($wizardSettings['whatsapp_contact_message']); ?>
                            </p>
                            <a href="#" id="whatsapp-link" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex items-center justify-center font-bold mb-3">
                                <i class="fab fa-whatsapp text-xl mr-2"></i>
                                <?php echo htmlspecialchars($wizardSettings['whatsapp_button_text']); ?>: <?php echo htmlspecialchars($operatorContact['whatsapp']); ?>
                            </a>
                            <p class="text-xs text-yellow-600 dark:text-yellow-400 mt-2 text-center font-medium">
                                <?php echo htmlspecialchars($wizardSettings['final_instruction']); ?>
                            </p>
                        </div>
                    </div>
                    
                    <!-- Post-Submit Action Buttons -->
                    <div class="flex justify-between items-center space-x-2">
                        <button type="button" id="start-new-exchange" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded transition-colors font-bold flex-1">
                            <i class="fas fa-plus mr-2"></i> Start New Exchange
                        </button>
                        
                        <button type="button" id="view-receipt-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded transition-colors font-bold flex-1">
                            <i class="fas fa-receipt mr-2"></i> <?php echo htmlspecialchars($wizardSettings['view_receipt_text']); ?>
                        </button>
                        
                        <a href="write-review.php" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-colors font-bold text-center flex-1">
                            <i class="fas fa-star mr-2"></i> Write Review
                        </a>
                    </div>
                </div>
                
                <!-- Pre-Submit Navigation Buttons -->
                <div id="pre-submit-buttons" class="flex justify-between">
                    <button type="button" id="back-to-step-2" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded hover:bg-gray-100 dark:hover:bg-gray-700 transition-colors font-bold">
                        <i class="fas fa-arrow-left mr-2"></i> <?php echo htmlspecialchars($wizardSettings['back_button_text']); ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Enhanced Receipt Modal with exact template from attached file -->
<div id="receipt-modal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 hidden">
    <div class="bg-white dark:bg-gray-800 max-w-6xl w-full mx-4 max-h-full overflow-y-auto rounded-lg">
        <div class="receipt-container" id="receipt-content">
            <!-- Receipt content will be dynamically generated here matching the exact template -->
        </div>
        <div class="p-4 border-t border-gray-200 dark:border-gray-600 text-center">
            <button id="download-receipt-pdf" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded mr-2 font-bold">
                <i class="fas fa-download mr-2"></i>Download PDF
            </button>
            <button id="print-receipt" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded mr-2 font-bold">
                <i class="fas fa-print mr-2"></i>Print
            </button>
            <button id="close-receipt-modal" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded font-bold">
                <i class="fas fa-times mr-2"></i>Close
            </button>
        </div>
    </div>
</div>

<style>
/* Enhanced font loading */
<?php
$fontFamily = $wizardSettings['wizard_font_family'];
$fontUrl = '';
switch($fontFamily) {
    case 'poppins':
        $fontUrl = '@import url("https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800;900&display=swap");';
        $fontStack = '"Poppins", sans-serif';
        break;
    case 'roboto':
        $fontUrl = '@import url("https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&display=swap");';
        $fontStack = '"Roboto", sans-serif';
        break;
    case 'hind_siliguri':
    default:
        $fontUrl = '@import url("https://fonts.googleapis.com/css2?family=Hind+Siliguri:wght@300;400;500;600;700;800&display=swap");';
        $fontStack = '"Hind Siliguri", sans-serif';
        break;
}
echo $fontUrl;
?>

/* CSS Variables */
:root {
    --wizard-primary-color: <?php echo $wizardSettings['wizard_primary_color']; ?>;
    --wizard-progress-bar-color: <?php echo $wizardSettings['wizard_progress_bar_color']; ?>;
    --wizard-font-family: <?php echo $fontStack; ?>;
    <?php
    $borderRadius = $wizardSettings['wizard_border_radius'];
    switch($borderRadius) {
        case 'none':
            echo '--wizard-border-radius: 0;';
            break;
        case 'small':
            echo '--wizard-border-radius: 4px;';
            break;
        case 'large':
            echo '--wizard-border-radius: 12px;';
            break;
        case 'medium':
        default:
            echo '--wizard-border-radius: 8px;';
            break;
    }
    ?>
}

/* Apply font family */
#exchange-wizard {
    font-family: var(--wizard-font-family);
}

/* Premium Next To Do Section with gradient background */
.premium-next-todo {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 50%, #f093fb 100%);
    background-size: 200% 200%;
    animation: gradientShift 3s ease infinite;
    border: 2px solid rgba(255, 255, 255, 0.3);
    box-shadow: 0 8px 32px rgba(0, 0, 0, 0.3);
}

@keyframes gradientShift {
    0% { background-position: 0% 50%; }
    50% { background-position: 100% 50%; }
    100% { background-position: 0% 50%; }
}

/* Exchange Step Display Control */
.exchange-step {
    transition: all 0.3s ease-in-out;
    border-radius: var(--wizard-border-radius);
}

/* Custom Select Styling */
.custom-select {
    position: relative;
    border-radius: var(--wizard-border-radius);
}

.custom-select.open {
    z-index: 50;
}

.custom-select.open .custom-options {
    display: block !important;
    animation: dropdownSlideDown 0.3s ease-out;
    z-index: 51;
}

.custom-select.open .arrow {
    transform: rotate(180deg);
}

.custom-select__trigger {
    user-select: none;
    transition: all 0.2s ease;
    border-radius: var(--wizard-border-radius);
}

.custom-select__trigger:hover {
    background-color: #f9fafb;
    border-color: var(--wizard-primary-color);
}

.dark .custom-select__trigger:hover {
    background-color: #374151;
    border-color: var(--wizard-primary-color);
}

/* Currency Dropdown */
.currency-dropdown {
    max-height: 420px;
    overflow-y: auto;
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
    border-radius: 0 0 var(--wizard-border-radius) var(--wizard-border-radius);
    scrollbar-width: thin;
    scrollbar-color: #CBD5E0 #F7FAFC;
    background-color: #FEFEFE !important;
    z-index: 51;
}

.dark .currency-dropdown {
    background-color: #1F2937 !important;
    scrollbar-color: #6B7280 #374151;
}

/* Currency Option Styling */
.currency-option {
    min-height: 60px;
    border-bottom: 1px solid #F3F4F6;
    transition: all 0.15s ease-in-out;
    transform: translateX(0);
    background-color: #FEFEFE;
}

.dark .currency-option {
    border-bottom-color: #4B5563;
    background-color: #1F2937;
}

.currency-option:hover {
    background-color: #F0F7FF !important;
    transform: translateX(4px);
    border-left: 3px solid var(--wizard-primary-color);
}

.dark .currency-option:hover {
    background-color: #2563EB !important;
    border-left-color: var(--wizard-primary-color);
}

/* Currency Names - Always Bold */
.currency-name,
.custom-select__selected-value {
    font-weight: 700 !important;
    font-size: 1.1rem !important;
}

/* Payment Icons */
.payment-icon {
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    overflow: hidden;
    border: 2px solid rgba(255, 255, 255, 0.2);
    transition: all 0.15s ease;
}

.currency-option:hover .payment-icon {
    transform: scale(1.1);
    border-color: rgba(255, 255, 255, 0.4);
}

/* Dropdown Animation */
@keyframes dropdownSlideDown {
    from {
        opacity: 0;
        transform: translateY(-10px) scale(0.95);
    }
    to {
        opacity: 1;
        transform: translateY(0) scale(1);
    }
}

/* Exchange Form Styling */
.exchange-form-container {
    position: relative;
}

.exchange-form-vertical {
    position: relative;
    display: flex;
    flex-direction: column;
    gap: 1rem;
}

.exchange-form-section {
    position: relative;
    z-index: 1;
    background: white;
    padding: 1rem;
    border-radius: var(--wizard-border-radius);
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
}

.exchange-form-section:has(.custom-select.open) {
    z-index: 45;
}

.exchange-form-section:hover {
    border-color: var(--wizard-primary-color);
    box-shadow: 0 2px 8px rgba(93, 92, 222, 0.1);
}

.dark .exchange-form-section {
    background: #1f2937;
    border-color: #374151;
}

.dark .exchange-form-section:hover {
    border-color: var(--wizard-primary-color);
    box-shadow: 0 2px 8px rgba(139, 138, 229, 0.1);
}

/* Exchange Direction Container */
.exchange-direction-container {
    position: relative;
    height: 20px;
    display: flex;
    justify-content: center;
    align-items: center;
    z-index: 5;
}

.exchange-direction-button {
    position: absolute;
    width: 44px;
    height: 44px;
    background-color: #fff;
    border: 2px solid #e5e7eb;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
    transition: all 0.3s ease;
    top: 50%;
    transform: translateY(-50%);
}

.dark .exchange-direction-button {
    background-color: #1f2937;
    border-color: #4b5563;
    color: #fff;
}

.exchange-direction-button:hover {
    background-color: #f3f4f6;
    transform: translateY(-50%) scale(1.15);
    box-shadow: 0 6px 16px rgba(0, 0, 0, 0.15);
}

.dark .exchange-direction-button:hover {
    background-color: #374151;
}

.exchange-direction-button i {
    font-size: 18px;
    color: var(--wizard-primary-color);
}

/* Exchange Rate Display */
.exchange-rate-display {
    text-align: center;
    padding: 0.75rem;
    background-color: #f9fafb;
    border-radius: var(--wizard-border-radius);
    margin-bottom: 1rem;
    font-size: 0.875rem;
    color: #4b5563;
    border: 1px solid #e5e7eb;
    font-weight: 600;
}

.dark .exchange-rate-display {
    background-color: #374151;
    color: #9ca3af;
    border-color: #4b5563;
}

/* Step Indicator */
.step-indicator {
    display: flex;
    justify-content: space-between;
    margin-bottom: 1rem;
}

.step {
    display: flex;
    flex-direction: column;
    align-items: center;
    flex: 1;
    position: relative;
}

.step:not(:last-child):after {
    content: "";
    position: absolute;
    width: calc(100% - 30px);
    height: 2px;
    background-color: #e5e7eb;
    top: 12px;
    left: calc(50% + 15px);
}

.dark .step:not(:last-child):after {
    background-color: #4b5563;
}

.step-number {
    width: 24px;
    height: 24px;
    border-radius: 50%;
    background-color: #e5e7eb;
    color: #6b7280;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    margin-bottom: 4px;
    z-index: 1;
}

.dark .step-number {
    background-color: #4b5563;
    color: #e5e7eb;
}

.step-title {
    font-size: 12px;
    color: #6b7280;
    font-weight: 600;
}

.dark .step-title {
    color: #9ca3af;
}

.step.active .step-number {
    background-color: var(--wizard-progress-bar-color);
    color: white;
}

.step.active .step-title {
    color: var(--wizard-progress-bar-color);
    font-weight: 700;
}

.step.completed .step-number {
    background-color: #10b981;
    color: white;
}

.step.completed .step-title {
    color: #10b981;
    font-weight: 700;
}

.dark .step.completed .step-title {
    color: #34d399;
}

.step.completed:not(:last-child):after {
    background-color: #10b981;
}

.step.active:not(:last-child):after {
    background-color: var(--wizard-progress-bar-color);
}

/* Status Badges */
.status-pending {
    background-color: #fef3c7;
    color: #92400e;
    font-weight: 700;
}

.dark .status-pending {
    background-color: #78350f;
    color: #fef3c7;
}

/* Animated Border */
.animated-border {
    position: relative;
    border-radius: var(--wizard-border-radius);
    overflow: hidden;
}

<?php if ($wizardSettings['enable_animations'] === 'yes'): ?>
.animated-border::before {
    content: '';
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(
        90deg,
        rgba(93, 92, 222, 0.3),
        rgba(93, 92, 222, 0.1),
        rgba(93, 92, 222, 0.3)
    );
    background-size: 200% 100%;
    animation: shimmer 3s infinite;
    pointer-events: none;
}

@keyframes shimmer {
    0% { background-position: 100% 0; }
    100% { background-position: -100% 0; }
}
<?php endif; ?>

/* Button Styling */
.exchange-btn {
    background: linear-gradient(135deg, var(--wizard-primary-color) 0%, #4A4BC9 100%);
    transition: all 0.3s ease;
    border-radius: var(--wizard-border-radius);
    font-weight: 700 !important;
}

.exchange-btn:hover {
    background: linear-gradient(135deg, #4A4BC9 0%, #3A3BB0 100%);
    transform: translateY(-1px);
}

/* Input validation styles */
.input-error {
    border-color: #ef4444 !important;
    box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.1) !important;
}

.input-success {
    border-color: #10b981 !important;
    box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
}

/* Toast Animation */
.toast-show {
    animation: slideInFromTop 0.5s ease-out;
}

.toast-hide {
    animation: slideOutToTop 0.5s ease-in;
}

@keyframes slideInFromTop {
    from {
        opacity: 0;
        transform: translate(-50%, -100%);
    }
    to {
        opacity: 1;
        transform: translate(-50%, 0);
    }
}

@keyframes slideOutToTop {
    from {
        opacity: 1;
        transform: translate(-50%, 0);
    }
    to {
        opacity: 0;
        transform: translate(-50%, -100%);
    }
}

/* Receipt Styling - Exact match from attached file */
.receipt-container {
    width: 19cm;
    height: 13cm;
    background-color: #F8FAFF;
    box-shadow: 0 4px 25px rgba(0, 0, 0, 0.15);
    border-radius: 10px;
    overflow: hidden;
    position: relative;
    display: flex;
    flex-direction: column;
    margin: auto;
    font-family: 'Montserrat', sans-serif;
}

.receipt-watermark {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%) rotate(-30deg);
    font-family: 'Playfair Display', serif;
    font-size: 140px;
    font-weight: 800;
    color: rgba(198, 164, 76, 0.04);
    white-space: nowrap;
    z-index: 1;
    pointer-events: none;
}

.receipt-header {
    padding: 15px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #0F2440;
    color: white;
}

.receipt-company-info {
    display: flex;
    align-items: center;
}

.receipt-logo {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    background: rgba(255, 255, 255, 0.1);
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.receipt-logo i {
    color: #C6A44C;
    font-size: 20px;
}

.receipt-company-text {
    display: flex;
    flex-direction: column;
}

.receipt-company-name {
    font-family: 'Playfair Display', serif;
    font-size: 20px;
    font-weight: 700;
    color: white;
    letter-spacing: 0.5px;
}

.receipt-tagline {
    font-size: 11px;
    color: rgba(255, 255, 255, 0.7);
    font-weight: 400;
}

.receipt-transaction-details {
    text-align: left;
    padding: 10px 15px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 8px;
    position: relative;
    overflow: hidden;
    border-left: 3px solid #C6A44C;
}

.receipt-transaction-id {
    font-weight: 700;
    color: white;
    font-size: 14px;
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 5px;
}

.receipt-transaction-id i {
    color: #C6A44C;
}

.receipt-date-time {
    color: rgba(255, 255, 255, 0.8);
    font-size: 11px;
}

.receipt-date-time div {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 2px;
}

.receipt-date-time i {
    color: #C6A44C;
}

.receipt-main-content {
    flex: 1;
    padding: 15px 25px;
    display: flex;
    gap: 25px;
    position: relative;
    background: #EBF0F9;
}

.receipt-section {
    flex: 1;
    background: #1D3A5D;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 6px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(255, 255, 255, 0.05);
    display: flex;
    flex-direction: column;
    color: white;
}

.receipt-section-title {
    font-size: 14px;
    font-weight: 700;
    color: white;
    margin-bottom: 12px;
    padding-bottom: 8px;
    border-bottom: 1px solid rgba(255, 255, 255, 0.1);
    display: flex;
    align-items: center;
    gap: 8px;
}

.receipt-section-title i {
    color: #C6A44C;
}

.receipt-detail-row {
    display: flex;
    align-items: center;
    margin-bottom: 10px;
}

.receipt-detail-row:last-child {
    margin-bottom: 0;
}

.receipt-detail-row i {
    color: #C6A44C;
    font-size: 12px;
    width: 16px;
    margin-right: 8px;
}

.receipt-detail-label {
    color: rgba(255, 255, 255, 0.7);
    font-weight: 600;
    margin-right: 8px;
    width: 60px;
    font-size: 12px;
}

.receipt-detail-value {
    font-weight: 500;
    color: white;
    flex: 1;
    font-size: 12px;
}

.receipt-detail-row.highlight {
    background: rgba(198, 164, 76, 0.1);
    padding: 6px 8px;
    border-radius: 6px;
    margin-left: -8px;
    margin-right: -8px;
    border-left: 3px solid #C6A44C;
}

.receipt-detail-row.highlight .receipt-detail-value {
    color: #C6A44C;
    font-weight: 700;
    font-size: 13px;
}

.receipt-footer {
    margin-top: auto;
}

.receipt-footer-content {
    padding: 12px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: #0F2440;
    color: white;
}

.receipt-footer-section {
    display: flex;
    align-items: center;
    color: rgba(255, 255, 255, 0.9);
    font-size: 11px;
}

.receipt-footer-section i {
    width: 28px;
    height: 28px;
    background: rgba(255, 255, 255, 0.1);
    color: #C6A44C;
    border-radius: 50%;
    display: flex;
    justify-content: center;
    align-items: center;
    margin-right: 10px;
    font-size: 12px;
    border: 1px solid rgba(255, 255, 255, 0.2);
}

.receipt-footer-title {
    font-size: 8px;
    color: #C6A44C;
    margin-bottom: 2px;
    text-transform: uppercase;
    letter-spacing: 1px;
    font-weight: 600;
}

.receipt-footer-value {
    font-size: 11px;
    font-weight: 500;
}

/* Mobile Responsiveness */
@media (max-width: 640px) {
    .currency-dropdown {
        max-height: 360px;
    }
    
    .currency-option {
        min-height: 56px;
        padding: 0.75rem;
    }
    
    .currency-name {
        font-size: 1rem !important;
    }
    
    .payment-icon {
        width: 2rem;
        height: 2rem;
    }
    
    .custom-select__selected-display .payment-icon {
        width: 1.75rem;
        height: 1.75rem;
    }
    
    .receipt-container {
        width: 100%;
        height: auto;
        max-width: 95vw;
    }
    
    .premium-next-todo {
        font-size: 1.5rem;
        padding: 1rem;
    }
}

/* Focus States for Accessibility */
.custom-select__trigger:focus {
    outline: 2px solid var(--wizard-primary-color);
    outline-offset: 2px;
}

.currency-option:focus {
    outline: 2px solid var(--wizard-primary-color);
    outline-offset: -2px;
    background-color: #EBF4FF !important;
}

.dark .currency-option:focus {
    background-color: #3B82F6 !important;
}

/* Error handling styles */
.error-message {
    background-color: #FEE2E2;
    border: 1px solid #FECACA;
    color: #991B1B;
    padding: 0.75rem;
    border-radius: var(--wizard-border-radius);
    margin: 1rem 0;
    font-weight: 600;
}

.dark .error-message {
    background-color: #7F1D1D;
    border-color: #991B1B;
    color: #FEE2E2;
}

/* Loading state styles */
.loading {
    opacity: 0.7;
    pointer-events: none;
    cursor: not-allowed;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    width: 20px;
    height: 20px;
    border: 2px solid #ccc;
    border-top: 2px solid var(--wizard-primary-color);
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: translate(-50%, -50%) rotate(0deg); }
    100% { transform: translate(-50%, -50%) rotate(360deg); }
}
</style>

<script>
// Enhanced JavaScript with all security and functionality improvements
(function() {
    'use strict';
    
    // Initialize exchange rates from server
    let exchangeRates = {};
    try {
        exchangeRates = <?php echo json_encode($exchangeRatesJS); ?> || {};
        console.log('Exchange rates loaded:', Object.keys(exchangeRates).length, 'pairs');
    } catch (error) {
        console.error('Error loading exchange rates:', error);
        exchangeRates = {};
    }

    // Initialize currencies data
    let currencies = [];
    try {
        currencies = <?php echo json_encode($currencies); ?> || [];
        console.log('Currencies loaded:', currencies.length, 'items');
    } catch (error) {
        console.error('Error loading currencies:', error);
        currencies = [];
    }

    // Create currency info object
    const currencyInfo = {};
    if (Array.isArray(currencies)) {
        currencies.forEach(currency => {
            if (currency && currency.code) {
                currencyInfo[currency.code] = {
                    name: currency.name || 'Unknown Currency',
                    displayName: currency.display_name || currency.name || currency.code,
                    paymentAddress: currency.payment_address || '',
                    addressLabel: currency.address_label || 'Payment Address',
                    addressType: currency.address_type || 'address',
                    logo: currency.logo || '',
                    logoExists: currency.logo_exists || false,
                    backgroundClass: currency.background_class || 'bg-blue-500 text-white',
                    iconClass: currency.icon_class || 'fas fa-money-bill-wave'
                };
            }
        });
    }

    // Get wizard settings from server
    let wizardSettings = {};
    try {
        wizardSettings = <?php echo json_encode($wizardSettings); ?> || {};
        console.log('Wizard settings loaded');
    } catch (error) {
        console.error('Error loading wizard settings:', error);
        wizardSettings = {};
    }

    // Get site info
    let siteInfo = {};
    try {
        siteInfo = {
            name: '<?php echo htmlspecialchars($siteName, ENT_QUOTES, 'UTF-8'); ?>',
            tagline: '<?php echo htmlspecialchars($siteTagline, ENT_QUOTES, 'UTF-8'); ?>',
            email: '<?php echo htmlspecialchars($contactEmail, ENT_QUOTES, 'UTF-8'); ?>',
            phone: '<?php echo htmlspecialchars($contactPhone, ENT_QUOTES, 'UTF-8'); ?>',
            whatsapp: '<?php echo htmlspecialchars($operatorContact['whatsapp'], ENT_QUOTES, 'UTF-8'); ?>'
        };
    } catch (error) {
        console.error('Error loading site info:', error);
        siteInfo = {
            name: 'Exchange Bridge',
            tagline: 'Exchange Taka Globally',
            email: 'info@exchangebridge.com',
            phone: '+8801869838872',
            whatsapp: '8801869838872'
        };
    }

    // Get minimum exchange amount from settings
    const minimumExchangeAmount = <?php echo $wizardSettings['minimum_exchange_amount']; ?> || 5;

    // Enhanced Toast Notification System
    function showToast(type, message) {
        console.log(`Toast: ${type} - ${message}`);
        
        try {
            const toastId = `${type}-toast`;
            const messageId = `${type}-toast-message`;
            
            const toast = document.getElementById(toastId);
            const messageElement = document.getElementById(messageId);
            
            if (!toast || !messageElement) {
                console.error(`Toast elements not found: ${toastId}`);
                return;
            }
            
            // Hide any existing toasts
            hideAllToasts();
            
            // Set message and show toast
            messageElement.textContent = message;
            toast.classList.remove('hidden');
            toast.classList.add('toast-show');
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                hideToast(toastId);
            }, 5000);
            
        } catch (error) {
            console.error('Error showing toast:', error);
        }
    }

    function hideToast(toastId) {
        try {
            const toast = document.getElementById(toastId);
            if (toast) {
                toast.classList.add('toast-hide');
                toast.classList.remove('toast-show');
                
                setTimeout(() => {
                    toast.classList.add('hidden');
                    toast.classList.remove('toast-hide');
                }, 500);
            }
        } catch (error) {
            console.error('Error hiding toast:', error);
        }
    }

    function hideAllToasts() {
        try {
            ['success-toast', 'error-toast', 'warning-toast'].forEach(toastId => {
                const toast = document.getElementById(toastId);
                if (toast && !toast.classList.contains('hidden')) {
                    hideToast(toastId);
                }
            });
        } catch (error) {
            console.error('Error hiding all toasts:', error);
        }
    }

    // Enhanced time functions to fix time issues
    function getAccurateServerTime() {
        try {
            const serverTimeInput = document.getElementById('server-current-time');
            const serverTimestampInput = document.getElementById('server-timestamp');
            
            if (serverTimeInput && serverTimestampInput) {
                const serverTime = serverTimeInput.value;
                const serverTimestamp = parseInt(serverTimestampInput.value) * 1000;
                const currentTimestamp = Date.now();
                const timeDifference = currentTimestamp - serverTimestamp;
                
                const accurateTime = new Date(new Date(serverTime).getTime() + timeDifference);
                
                const year = accurateTime.getFullYear();
                const month = (accurateTime.getMonth() + 1).toString().padStart(2, '0');
                const day = accurateTime.getDate().toString().padStart(2, '0');
                const hours = accurateTime.getHours().toString().padStart(2, '0');
                const minutes = accurateTime.getMinutes().toString().padStart(2, '0');
                const seconds = accurateTime.getSeconds().toString().padStart(2, '0');
                
                return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
            }
        } catch (error) {
            console.error('Error getting accurate server time:', error);
        }
        
        // Fallback to current time
        const now = new Date();
        const year = now.getFullYear();
        const month = (now.getMonth() + 1).toString().padStart(2, '0');
        const day = now.getDate().toString().padStart(2, '0');
        const hours = now.getHours().toString().padStart(2, '0');
        const minutes = now.getMinutes().toString().padStart(2, '0');
        const seconds = now.getSeconds().toString().padStart(2, '0');
        
        return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
    }

    function getFormattedDateTime() {
        try {
            const accurateTime = getAccurateServerTime();
            const date = new Date(accurateTime);
            
            if (isNaN(date.getTime())) {
                throw new Error('Invalid date');
            }
            
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            const seconds = date.getSeconds().toString().padStart(2, '0');
            
            return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        } catch (error) {
            console.error('Error formatting date time:', error);
            
            const now = new Date();
            const day = now.getDate().toString().padStart(2, '0');
            const month = (now.getMonth() + 1).toString().padStart(2, '0');
            const year = now.getFullYear();
            const hours = now.getHours().toString().padStart(2, '0');
            const minutes = now.getMinutes().toString().padStart(2, '0');
            const seconds = now.getSeconds().toString().padStart(2, '0');
            
            return `${day}-${month}-${year} ${hours}:${minutes}:${seconds}`;
        }
    }

    // Enhanced sequential exchange ID generation function using PHP backend
    async function generateSequentialExchangeId() {
        try {
            // Use PHP function directly for sequential ID generation
            const response = await fetch('api/exchange.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'generate_id'
                })
            });
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
            const data = await response.json();
            if (data.success && data.exchange_id) {
                return data.exchange_id;
            } else {
                throw new Error(data.message || 'Failed to generate sequential ID');
            }
        } catch (error) {
            console.error('Error generating sequential exchange ID:', error);
            
            // Enhanced fallback with sequential numbering based on current time
            try {
                const accurateTime = getAccurateServerTime();
                const date = new Date(accurateTime);
                
                let year, month, day;
                
                if (isNaN(date.getTime())) {
                    const now = new Date();
                    year = now.getFullYear().toString().slice(-2);
                    month = (now.getMonth() + 1).toString().padStart(2, '0');
                    day = now.getDate().toString().padStart(2, '0');
                } else {
                    year = date.getFullYear().toString().slice(-2);
                    month = (date.getMonth() + 1).toString().padStart(2, '0');
                    day = date.getDate().toString().padStart(2, '0');
                }
                
                // Generate a sequential number based on time (ensuring uniqueness)
                const hours = date.getHours().toString().padStart(2, '0');
                const minutes = date.getMinutes().toString().padStart(2, '0');
                const timeBasedSequence = parseInt(hours + minutes) % 100;
                const sequentialStr = timeBasedSequence.toString().padStart(2, '0');
                
                return `EB-${year}${month}${day}${sequentialStr}`;
            } catch (fallbackError) {
                console.error('Error in fallback ID generation:', fallbackError);
                
                // Last resort - use timestamp
                const timestamp = Date.now().toString().slice(-8);
                return `EB-${timestamp}`;
            }
        }
    }

    // Enhanced document ready function
    function initialize() {
        try {
            console.log('Initializing enhanced exchange form...');
            
            if (!currencies || currencies.length === 0) {
                console.warn('No currencies available');
                showToast('warning', 'No currencies available for exchange');
                return;
            }
            
            initializeCustomSelect();
            setupEventListeners();
            updateExchangeRate();
            updatePaymentAddressLabel();
            
            console.log('Enhanced exchange form initialization complete');
        } catch (error) {
            console.error('Error during initialization:', error);
            showToast('error', 'Failed to initialize form');
        }
    }

    // Enhanced custom select initialization
    function initializeCustomSelect() {
        console.log('Initializing custom select dropdowns...');
        
        try {
            document.querySelectorAll('.custom-select__trigger').forEach(trigger => {
                trigger.addEventListener('click', function(e) {
                    try {
                        e.stopPropagation();
                        const select = this.closest('.custom-select');
                        if (!select) return;
                        
                        closeAllSelects(select);
                        select.classList.toggle('open');
                        
                        if (select.classList.contains('open')) {
                            const firstOption = select.querySelector('.custom-option');
                            if (firstOption) {
                                firstOption.focus();
                            }
                        }
                    } catch (error) {
                        console.error('Error handling select trigger click:', error);
                    }
                });
                
                trigger.addEventListener('keydown', function(e) {
                    try {
                        if (e.key === 'Enter' || e.key === ' ') {
                            e.preventDefault();
                            this.click();
                        }
                    } catch (error) {
                        console.error('Error handling trigger keydown:', error);
                    }
                });
            });
            
            document.querySelectorAll('.custom-option').forEach((option, index) => {
                option.addEventListener('click', function(e) {
                    try {
                        e.stopPropagation();
                        const value = this.getAttribute('data-value');
                        if (!value) return;
                        
                        const select = this.closest('.custom-select');
                        if (!select) return;
                        
                        const trigger = select.querySelector('.custom-select__trigger');
                        if (!trigger) return;
                        
                        const selectedDisplay = trigger.querySelector('.custom-select__selected-display');
                        if (!selectedDisplay) return;
                        
                        this.style.transform = 'scale(0.95)';
                        setTimeout(() => {
                            this.style.transform = '';
                        }, 150);
                        
                        selectedDisplay.innerHTML = this.innerHTML;
                        selectedDisplay.setAttribute('data-value', value);
                        
                        select.classList.remove('open');
                        
                        if (select.closest('#receive-currency-wrapper')) {
                            updatePaymentAddressLabel();
                        }
                        
                        updateExchangeRate();
                    } catch (error) {
                        console.error('Error handling option selection:', error);
                    }
                });
                
                option.setAttribute('tabindex', '0');
                option.addEventListener('keydown', function(e) {
                    try {
                        const select = this.closest('.custom-select');
                        if (!select) return;
                        
                        const options = Array.from(select.querySelectorAll('.custom-option'));
                        const currentIndex = options.indexOf(this);
                        
                        switch (e.key) {
                            case 'Enter':
                            case ' ':
                                e.preventDefault();
                                this.click();
                                break;
                            case 'ArrowDown':
                                e.preventDefault();
                                const nextIndex = Math.min(currentIndex + 1, options.length - 1);
                                if (options[nextIndex]) options[nextIndex].focus();
                                break;
                            case 'ArrowUp':
                                e.preventDefault();
                                const prevIndex = Math.max(currentIndex - 1, 0);
                                if (options[prevIndex]) options[prevIndex].focus();
                                break;
                            case 'Escape':
                                select.classList.remove('open');
                                const trigger = select.querySelector('.custom-select__trigger');
                                if (trigger) trigger.focus();
                                break;
                        }
                    } catch (error) {
                        console.error('Error handling option keydown:', error);
                    }
                });
            });
            
            document.addEventListener('click', function() {
                closeAllSelects();
            });
            
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    closeAllSelects();
                }
            });
        } catch (error) {
            console.error('Error initializing custom select:', error);
        }
    }

    function closeAllSelects(exceptSelect) {
        try {
            document.querySelectorAll('.custom-select').forEach(select => {
                if (select !== exceptSelect) {
                    select.classList.remove('open');
                }
            });
        } catch (error) {
            console.error('Error closing selects:', error);
        }
    }

    // Enhanced event listeners setup
    function setupEventListeners() {
        console.log('Setting up event listeners...');
        
        try {
            const sendAmountInput = document.getElementById('send-amount');
            if (sendAmountInput) {
                let timeoutId;
                sendAmountInput.addEventListener('input', function() {
                    clearTimeout(timeoutId);
                    timeoutId = setTimeout(updateExchangeRate, 300);
                    
                    // Visual feedback for validation
                    const value = parseFloat(this.value) || 0;
                    if (this.value && value >= minimumExchangeAmount) {
                        this.classList.remove('input-error');
                        this.classList.add('input-success');
                    } else if (this.value) {
                        this.classList.remove('input-success');
                        this.classList.add('input-error');
                    } else {
                        this.classList.remove('input-error', 'input-success');
                    }
                });
                
                // Prevent negative values
                sendAmountInput.addEventListener('keydown', function(e) {
                    if (e.key === '-' || e.key === 'e' || e.key === 'E') {
                        e.preventDefault();
                    }
                });
            }
            
            const swapButton = document.getElementById('swap-currencies');
            if (swapButton) {
                swapButton.addEventListener('click', swapCurrencies);
            }
            
            // Step navigation
            const buttons = [
                { id: 'continue-to-step-2', action: goToStep2 },
                { id: 'back-to-step-1', action: goToStep1 },
                { id: 'continue-to-step-3', action: goToStep3 },
                { id: 'back-to-step-2', action: goToStep2 },
                { id: 'view-receipt-btn', action: viewReceipt },
                { id: 'complete-exchange', action: completeExchange },
                { id: 'start-new-exchange', action: startNewExchange },
                { id: 'copy-account', action: copyAccountToClipboard },
                { id: 'close-receipt-modal', action: hideReceiptModal },
                { id: 'download-receipt-pdf', action: downloadReceiptAsPDF },
                { id: 'print-receipt', action: printReceiptContent }
            ];
            
            buttons.forEach(({ id, action }) => {
                const element = document.getElementById(id);
                if (element && action) {
                    element.addEventListener('click', action);
                }
            });
            
            console.log('Event listeners setup complete');
        } catch (error) {
            console.error('Error setting up event listeners:', error);
        }
    }

    function showStep(stepNumber) {
        console.log(`Showing step ${stepNumber}...`);
        
        try {
            const allSteps = document.querySelectorAll('.exchange-step');
            allSteps.forEach(step => {
                step.style.display = 'none';
            });
            
            let targetStepId;
            switch(stepNumber) {
                case 1:
                    targetStepId = 'step-1-exchange';
                    break;
                case 2:
                    targetStepId = 'step-2-contact';
                    break;
                case 3:
                    targetStepId = 'step-3-confirmation';
                    break;
                default:
                    console.error('Invalid step number:', stepNumber);
                    return;
            }
            
            const targetStep = document.getElementById(targetStepId);
            if (targetStep) {
                targetStep.style.display = 'block';
                targetStep.style.opacity = '1';
                targetStep.style.transform = 'translateY(0)';
                console.log(`Successfully showed step ${stepNumber}`);
                
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            } else {
                console.error(`Step element not found: ${targetStepId}`);
            }
        } catch (error) {
            console.error('Error showing step:', error);
        }
    }

    function getSelectedCurrency(wrapperId) {
        try {
            const wrapper = document.getElementById(wrapperId);
            if (!wrapper) {
                console.error(`Wrapper not found: ${wrapperId}`);
                return '';
            }
            
            const selectedValue = wrapper.querySelector('.custom-select__selected-display');
            if (!selectedValue) {
                console.error(`Selected display not found in wrapper: ${wrapperId}`);
                return '';
            }
            
            const value = selectedValue.getAttribute('data-value');
            return value || '';
        } catch (error) {
            console.error('Error getting selected currency:', error);
            return '';
        }
    }

    function updateExchangeRate() {
        console.log('Updating exchange rate...');
        
        try {
            const sendAmountInput = document.getElementById('send-amount');
            if (!sendAmountInput) {
                console.error('Send amount input not found');
                return;
            }
            
            const sendAmount = parseFloat(sendAmountInput.value) || 0;
            const fromCurrency = getSelectedCurrency('send-currency-wrapper');
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            
            console.log('Exchange calculation:', { sendAmount, fromCurrency, toCurrency });
            
            if (fromCurrency === toCurrency && fromCurrency && toCurrency) {
                console.log('Same currencies detected, finding alternative...');
                const otherCurrency = currencies.find(c => c.code !== fromCurrency);
                if (otherCurrency) {
                    const receiveCurrencyWrapper = document.getElementById('receive-currency-wrapper');
                    if (receiveCurrencyWrapper) {
                        const options = receiveCurrencyWrapper.querySelectorAll('.custom-option');
                        
                        for (let option of options) {
                            if (option.getAttribute('data-value') === otherCurrency.code) {
                                const triggerDisplay = receiveCurrencyWrapper.querySelector('.custom-select__selected-display');
                                if (triggerDisplay) {
                                    triggerDisplay.innerHTML = option.innerHTML;
                                    triggerDisplay.setAttribute('data-value', otherCurrency.code);
                                    updatePaymentAddressLabel();
                                    console.log('Auto-selected different currency:', otherCurrency.code);
                                }
                                break;
                            }
                        }
                    }
                }
            }
            
            const updatedToCurrency = getSelectedCurrency('receive-currency-wrapper');
            
            const rateKey = `${fromCurrency}-${updatedToCurrency}`;
            let exchangeRate = exchangeRates[rateKey];
            
            console.log('Looking for rate:', rateKey, 'Found:', exchangeRate);
            
            if (exchangeRate === undefined) {
                const inverseRateKey = `${updatedToCurrency}-${fromCurrency}`;
                const inverseRate = exchangeRates[inverseRateKey];
                
                console.log('Trying inverse rate:', inverseRateKey, 'Found:', inverseRate);
                
                if (inverseRate !== undefined && inverseRate !== 0) {
                    exchangeRate = 1 / inverseRate;
                    console.log('Using inverse rate:', exchangeRate);
                } else {
                    exchangeRate = 0;
                    console.log('No exchange rate found');
                }
            }
            
            let receiveAmount = 0;
            if (exchangeRate > 0) {
                receiveAmount = sendAmount * exchangeRate;
            }
            
            console.log('Calculated receive amount:', receiveAmount);
            
            const receiveInput = document.getElementById('receive-amount');
            if (receiveInput) {
                receiveInput.value = receiveAmount.toFixed(2);
            }
            
            updateExchangeRateDisplay(fromCurrency, updatedToCurrency, exchangeRate);
        } catch (error) {
            console.error('Error updating exchange rate:', error);
        }
    }

    function updateExchangeRateDisplay(fromCurrency, toCurrency, rate) {
        try {
            const displayElement = document.getElementById('exchange-rate-display');
            if (!displayElement) return;
            
            if (!fromCurrency || !toCurrency || rate === 0 || rate === undefined) {
                const errorMessage = wizardSettings.rate_unavailable_error || 'Exchange rate not available for this currency pair';
                displayElement.innerHTML = `<i class="fas fa-exclamation-triangle mr-2 text-yellow-500"></i> ${errorMessage}`;
                return;
            }
            
            const fromName = currencyInfo[fromCurrency]?.displayName || fromCurrency;
            const toName = currencyInfo[toCurrency]?.displayName || toCurrency;
            const rateLabel = wizardSettings.rate_label_text || 'Exchange Rate:';
            
            displayElement.innerHTML = `<i class="fas fa-chart-line mr-2"></i> ${rateLabel} 1 ${fromName} = ${rate.toFixed(4)} ${toName}`;
        } catch (error) {
            console.error('Error updating exchange rate display:', error);
        }
    }

    function swapCurrencies() {
        console.log('Swapping currencies...');
        
        try {
            const sendWrapper = document.getElementById('send-currency-wrapper');
            const receiveWrapper = document.getElementById('receive-currency-wrapper');
            
            if (!sendWrapper || !receiveWrapper) {
                console.error('Currency wrappers not found');
                return;
            }
            
            const sendDisplay = sendWrapper.querySelector('.custom-select__selected-display');
            const receiveDisplay = receiveWrapper.querySelector('.custom-select__selected-display');
            
            if (!sendDisplay || !receiveDisplay) {
                console.error('Currency displays not found');
                return;
            }
            
            const sendHTML = sendDisplay.innerHTML;
            const sendValue = sendDisplay.getAttribute('data-value');
            const receiveHTML = receiveDisplay.innerHTML;
            const receiveValue = receiveDisplay.getAttribute('data-value');
            
            sendDisplay.innerHTML = receiveHTML;
            sendDisplay.setAttribute('data-value', receiveValue);
            receiveDisplay.innerHTML = sendHTML;
            receiveDisplay.setAttribute('data-value', sendValue);
            
            updatePaymentAddressLabel();
            updateExchangeRate();
            
            console.log('Currency swap complete');
        } catch (error) {
            console.error('Error swapping currencies:', error);
        }
    }

    function updatePaymentAddressLabel() {
        try {
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            const addressLabel = document.getElementById('payment-address-label');
            
            if (toCurrency && currencyInfo[toCurrency]) {
                if (addressLabel) {
                    addressLabel.textContent = currencyInfo[toCurrency].addressLabel || wizardSettings.address_field_label || 'Payment Address';
                }
            } else {
                if (addressLabel) {
                    addressLabel.textContent = wizardSettings.address_field_label || 'Payment Address';
                }
            }
        } catch (error) {
            console.error('Error updating payment address label:', error);
        }
    }

    // Enhanced step navigation with improved validation
    function goToStep2() {
        console.log('Attempting to go to step 2...');
        
        try {
            const sendAmountInput = document.getElementById('send-amount');
            if (!sendAmountInput) {
                console.error('Send amount input not found');
                showToast('error', 'Form error: Amount input not found');
                return;
            }
            
            const sendAmountValue = sendAmountInput.value;
            const sendAmount = parseFloat(sendAmountValue) || 0;
            
            console.log('Validation check:', { 
                value: sendAmountValue, 
                parsed: sendAmount, 
                minimum: minimumExchangeAmount,
                isEmpty: !sendAmountValue || sendAmountValue.trim() === ''
            });
            
            // Enhanced validation - check if amount is entered first
            if (!sendAmountValue || sendAmountValue.trim() === '' || sendAmountValue === '0') {
                console.log('Amount input validation failed - no amount or zero entered');
                const errorMessage = wizardSettings.amount_required_error || 'দয়া করে এক্সচেঞ্জ এমাউন্ট প্রবেশ করুন';
                showToast('error', errorMessage);
                
                // Visual feedback
                sendAmountInput.classList.add('input-error');
                sendAmountInput.focus();
                
                // Remove highlight after 3 seconds
                setTimeout(() => {
                    sendAmountInput.classList.remove('input-error');
                }, 3000);
                
                return;
            }
            
            // Check minimum amount
            if (sendAmount < minimumExchangeAmount) {
                console.log('Amount validation failed - below minimum:', sendAmount, '<', minimumExchangeAmount);
                const errorMessage = wizardSettings.min_amount_error || `দয়া করে সর্বনিম্ন ${minimumExchangeAmount} ডলার পরিমাণ লিখুন`;
                showToast('error', errorMessage);
                
                // Visual feedback
                sendAmountInput.classList.add('input-error');
                sendAmountInput.focus();
                
                // Remove highlight after 3 seconds
                setTimeout(() => {
                    sendAmountInput.classList.remove('input-error');
                }, 3000);
                
                return;
            }
            
            const fromCurrency = getSelectedCurrency('send-currency-wrapper');
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            
            console.log('Currency validation:', { fromCurrency, toCurrency });
            
            if (!fromCurrency || !toCurrency) {
                console.log('Currency selection validation failed');
                showToast('error', 'Please select both currencies');
                return;
            }
            
            const rateKey = `${fromCurrency}-${toCurrency}`;
            const inverseRateKey = `${toCurrency}-${fromCurrency}`;
            
            console.log('Checking exchange rates:', { rateKey, inverseRateKey });
            
            if (!exchangeRates[rateKey] && !exchangeRates[inverseRateKey]) {
                console.log('Exchange rate validation failed');
                const errorMessage = wizardSettings.rate_unavailable_error || 'Exchange rate is not available for this currency pair';
                showToast('error', errorMessage);
                return;
            }
            
            console.log('All validations passed, proceeding to step 2');
            
            // Visual feedback for successful validation
            sendAmountInput.classList.remove('input-error');
            sendAmountInput.classList.add('input-success');
            
            showStep(2);
            
        } catch (error) {
            console.error('Error in goToStep2:', error);
            showToast('error', 'An error occurred while processing your request');
        }
    }

    function goToStep1() {
        console.log('Going back to step 1');
        showStep(1);
    }

    async function goToStep3() {
        console.log('Attempting to go to step 3...');
        
        try {
            const name = document.getElementById('name');
            const email = document.getElementById('email');
            const phone = document.getElementById('phone');
            const paymentAddress = document.getElementById('payment-address');
            
            if (!name || !email || !phone || !paymentAddress) {
                console.error('Contact form elements not found');
                showToast('error', 'Form error: Contact fields not found');
                return;
            }
            
            const formData = {
                name: name.value.trim(),
                email: email.value.trim(),
                phone: phone.value.trim(),
                paymentAddress: paymentAddress.value.trim()
            };
            
            console.log('Contact form validation:', formData);
            
            // Enhanced validation with visual feedback
            let hasErrors = false;
            
            // Name validation
            if (!formData.name) {
                name.classList.add('input-error');
                hasErrors = true;
            } else {
                name.classList.remove('input-error');
                name.classList.add('input-success');
            }
            
            // Email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!formData.email) {
                email.classList.add('input-error');
                hasErrors = true;
            } else if (!emailRegex.test(formData.email)) {
                email.classList.add('input-error');
                hasErrors = true;
                const errorMessage = wizardSettings.invalid_email_error || 'দয়া করে সঠিক ই-মেইল ঠিকানা দিন';
                showToast('error', errorMessage);
                return;
            } else {
                email.classList.remove('input-error');
                email.classList.add('input-success');
            }
            
            // Phone validation
            if (!formData.phone) {
                phone.classList.add('input-error');
                hasErrors = true;
            } else {
                phone.classList.remove('input-error');
                phone.classList.add('input-success');
            }
            
            // Payment address validation
            if (!formData.paymentAddress) {
                paymentAddress.classList.add('input-error');
                hasErrors = true;
            } else {
                paymentAddress.classList.remove('input-error');
                paymentAddress.classList.add('input-success');
            }
            
            if (hasErrors) {
                const errorMessage = wizardSettings.required_fields_error || 'দয়া করে সব প্রয়োজনীয় ঘর পূরণ করুন';
                showToast('error', errorMessage);
                return;
            }
            
            // Generate sequential reference ID
            const referenceId = await generateSequentialExchangeId();
            const referenceElement = document.getElementById('reference-id');
            if (referenceElement) {
                referenceElement.textContent = referenceId;
            }
            
            updateConfirmationDetails();
            showStep(3);
            
        } catch (error) {
            console.error('Error in goToStep3:', error);
            showToast('error', 'An error occurred while processing your request');
        }
    }

    function goToStep2() {
        console.log('Going back to step 2');
        showStep(2);
    }

    function updateConfirmationDetails() {
        console.log('Updating confirmation details...');
        
        try {
            const fromCurrency = getSelectedCurrency('send-currency-wrapper');
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            
            const fromName = currencyInfo[fromCurrency]?.displayName || fromCurrency;
            const toName = currencyInfo[toCurrency]?.displayName || toCurrency;
            
            const sendAmountInput = document.getElementById('send-amount');
            const receiveAmountInput = document.getElementById('receive-amount');
            
            if (!sendAmountInput || !receiveAmountInput) {
                console.error('Amount inputs not found');
                return;
            }
            
            const sendAmount = parseFloat(sendAmountInput.value) || 0;
            const receiveAmount = parseFloat(receiveAmountInput.value) || 0;
            
            const confirmSendAmount = document.getElementById('confirm-send-amount');
            const confirmReceiveAmount = document.getElementById('confirm-receive-amount');
            const confirmationAmount = document.getElementById('confirmation-amount');
            
            if (confirmSendAmount) {
                confirmSendAmount.textContent = `${sendAmount.toFixed(2)} ${fromName}`;
            }
            if (confirmReceiveAmount) {
                confirmReceiveAmount.textContent = `${receiveAmount.toFixed(2)} ${toName}`;
            }
            if (confirmationAmount) {
                confirmationAmount.textContent = `${sendAmount.toFixed(2)} ${fromName}`;
            }
            
            const confirmDateTime = document.getElementById('confirm-datetime');
            if (confirmDateTime) {
                const formattedDateTime = getFormattedDateTime();
                confirmDateTime.textContent = formattedDateTime;
            }
            
            const confirmRate = document.getElementById('confirm-rate');
            if (confirmRate) {
                const rateKey = `${fromCurrency}-${toCurrency}`;
                let rate = exchangeRates[rateKey];
                
                if (rate === undefined) {
                    const inverseRateKey = `${toCurrency}-${fromCurrency}`;
                    const inverseRate = exchangeRates[inverseRateKey];
                    
                    if (inverseRate !== undefined && inverseRate !== 0) {
                        rate = 1 / inverseRate;
                        confirmRate.textContent = `1 ${fromName} = ${rate.toFixed(4)} ${toName}`;
                    } else {
                        confirmRate.textContent = 'Rate not available';
                    }
                } else {
                    confirmRate.textContent = `1 ${fromName} = ${rate.toFixed(4)} ${toName}`;
                }
            }
            
            const accountText = document.getElementById('account-text');
            if (accountText) {
                let paymentAccount = '';
                
                if (currencyInfo[fromCurrency] && currencyInfo[fromCurrency].paymentAddress) {
                    paymentAccount = currencyInfo[fromCurrency].paymentAddress;
                } else {
                    paymentAccount = '<?php echo htmlspecialchars(getSetting('default_payment_address', '01712345678'), ENT_QUOTES, 'UTF-8'); ?>';
                }
                
                if (/^\d+$/.test(paymentAccount)) {
                    accountText.innerHTML = `<a href="tel:${paymentAccount}">${paymentAccount}</a>`;
                } else {
                    accountText.textContent = paymentAccount;
                }
            }
            
            const whatsappLink = document.getElementById('whatsapp-link');
            if (whatsappLink) {
                const whatsappMessage = createWhatsAppMessage();
                const whatsappNumber = siteInfo.whatsapp.replace(/[^0-9]/g, '');
                whatsappLink.href = `https://wa.me/${whatsappNumber}?text=${whatsappMessage}`;
            }
        } catch (error) {
            console.error('Error updating confirmation details:', error);
        }
    }

    function createWhatsAppMessage() {
        try {
            const referenceElement = document.getElementById('reference-id');
            const nameElement = document.getElementById('name');
            const emailElement = document.getElementById('email');
            const phoneElement = document.getElementById('phone');
            const paymentAddressElement = document.getElementById('payment-address');
            const sendAmountElement = document.getElementById('send-amount');
            const receiveAmountElement = document.getElementById('receive-amount');
            
            const referenceId = referenceElement ? referenceElement.textContent : 'N/A';
            const name = nameElement ? nameElement.value || 'Customer' : 'Customer';
            const email = emailElement ? emailElement.value || 'N/A' : 'N/A';
            const phone = phoneElement ? phoneElement.value || 'N/A' : 'N/A';
            const paymentAddress = paymentAddressElement ? paymentAddressElement.value || 'N/A' : 'N/A';
            const sendAmount = sendAmountElement ? sendAmountElement.value || '0' : '0';
            const receiveAmount = receiveAmountElement ? receiveAmountElement.value || '0' : '0';
            
            const fromCurrency = getSelectedCurrency('send-currency-wrapper');
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            
            const fromName = currencyInfo[fromCurrency]?.name || fromCurrency;
            const toName = currencyInfo[toCurrency]?.name || toCurrency;
            
            const message = `Hello ${siteInfo.name} Operator,
My Transaction ID: ${referenceId}
Name: ${name}
E-mail: ${email}
Phone: ${phone}
Receiving Address: ${paymentAddress}
${fromName} ${sendAmount} ${fromCurrency} to ${toName} ${receiveAmount} ${toCurrency}
Time: ${getAccurateServerTime()}`;

            return encodeURIComponent(message);
        } catch (error) {
            console.error('Error creating WhatsApp message:', error);
            return encodeURIComponent('Hello, I need help with my exchange transaction.');
        }
    }

    // Enhanced complete exchange function
    function completeExchange() {
        console.log('Completing exchange...');
        
        try {
            const completeBtn = document.getElementById('complete-exchange');
            if (!completeBtn) {
                console.error('Complete button not found');
                return;
            }
            
            const originalBtnText = completeBtn.innerHTML;
            completeBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i> Processing...';
            completeBtn.disabled = true;
            
            const formData = new FormData();
            
            const referenceElement = document.getElementById('reference-id');
            const nameElement = document.getElementById('name');
            const emailElement = document.getElementById('email');
            const phoneElement = document.getElementById('phone');
            const paymentAddressElement = document.getElementById('payment-address');
            const sendAmountElement = document.getElementById('send-amount');
            const receiveAmountElement = document.getElementById('receive-amount');
            
            formData.append('reference_id', referenceElement ? referenceElement.textContent : '');
            formData.append('customer_name', nameElement ? nameElement.value : '');
            formData.append('customer_email', emailElement ? emailElement.value : '');
            formData.append('customer_phone', phoneElement ? phoneElement.value : '');
            formData.append('payment_address', paymentAddressElement ? paymentAddressElement.value : '');
            formData.append('from_currency', getSelectedCurrency('send-currency-wrapper'));
            formData.append('to_currency', getSelectedCurrency('receive-currency-wrapper'));
            formData.append('send_amount', sendAmountElement ? sendAmountElement.value : '0');
            formData.append('receive_amount', receiveAmountElement ? receiveAmountElement.value : '0');
            formData.append('exchange_datetime', getAccurateServerTime());
            
            const fromCurrency = getSelectedCurrency('send-currency-wrapper');
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            const rateKey = `${fromCurrency}-${toCurrency}`;
            let rate = exchangeRates[rateKey];
            
            if (rate === undefined) {
                const inverseRateKey = `${toCurrency}-${fromCurrency}`;
                const inverseRate = exchangeRates[inverseRateKey];
                
                if (inverseRate !== undefined && inverseRate !== 0) {
                    rate = 1 / inverseRate;
                } else {
                    rate = 0;
                }
            }
            
            formData.append('exchange_rate', rate);
            
            // Add CSRF token for security
            const csrfTokenInput = document.querySelector('input[name="csrf_token"]');
            if (csrfTokenInput) {
                formData.append('csrf_token', csrfTokenInput.value);
            }
            
            fetch('api/exchange.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                }
                return response.json();
            })
            .then(data => {
                if (data.success) {
                    showToast('success', wizardSettings.exchange_success_message || 'আপনার এক্সচেঞ্জ অর্ডার সফলভাবে জমা হয়েছে!');
                    
                    // Hide pre-submit buttons and show success section
                    const preSubmitButtons = document.getElementById('pre-submit-buttons');
                    const successSection = document.getElementById('success-section');
                    
                    if (preSubmitButtons) {
                        preSubmitButtons.style.display = 'none';
                    }
                    if (successSection) {
                        successSection.classList.remove('hidden');
                    }
                } else {
                    const errorMessage = data.message || 'Failed to submit exchange';
                    showToast('error', errorMessage);
                }
                
                completeBtn.innerHTML = originalBtnText;
                completeBtn.disabled = false;
            })
            .catch(error => {
                console.error('Error submitting exchange:', error);
                showToast('error', 'An error occurred while processing your request');
                completeBtn.innerHTML = originalBtnText;
                completeBtn.disabled = false;
            });
        } catch (error) {
            console.error('Error in completeExchange:', error);
            showToast('error', 'An error occurred while processing your request');
        }
    }

    // Start new exchange function
    function startNewExchange() {
        try {
            // Reset form
            const sendAmountInput = document.getElementById('send-amount');
            const receiveAmountInput = document.getElementById('receive-amount');
            const nameInput = document.getElementById('name');
            const emailInput = document.getElementById('email');
            const phoneInput = document.getElementById('phone');
            const paymentAddressInput = document.getElementById('payment-address');
            
            if (sendAmountInput) {
                sendAmountInput.value = '';
                sendAmountInput.classList.remove('input-error', 'input-success');
            }
            if (receiveAmountInput) receiveAmountInput.value = '';
            if (nameInput) {
                nameInput.value = '';
                nameInput.classList.remove('input-error', 'input-success');
            }
            if (emailInput) {
                emailInput.value = '';
                emailInput.classList.remove('input-error', 'input-success');
            }
            if (phoneInput) {
                phoneInput.value = '';
                phoneInput.classList.remove('input-error', 'input-success');
            }
            if (paymentAddressInput) {
                paymentAddressInput.value = '';
                paymentAddressInput.classList.remove('input-error', 'input-success');
            }
            
            // Go to step 1
            showStep(1);
            
            // Reset exchange rate display
            updateExchangeRate();
            
            console.log('Started new exchange');
        } catch (error) {
            console.error('Error starting new exchange:', error);
        }
    }

    // Receipt functions
    function viewReceipt() {
        try {
            generateReceiptModal();
        } catch (error) {
            console.error('Error viewing receipt:', error);
            showToast('error', 'Failed to generate receipt');
        }
    }

    function generateReceiptModal() {
        try {
            // Get all the form data
            const referenceElement = document.getElementById('reference-id');
            const nameElement = document.getElementById('name');
            const emailElement = document.getElementById('email');
            const phoneElement = document.getElementById('phone');
            const paymentAddressElement = document.getElementById('payment-address');
            const sendAmountElement = document.getElementById('send-amount');
            const receiveAmountElement = document.getElementById('receive-amount');
            
            const referenceId = referenceElement ? referenceElement.textContent : 'N/A';
            const name = nameElement ? nameElement.value : 'N/A';
            const email = emailElement ? emailElement.value : 'N/A';
            const phone = phoneElement ? phoneElement.value : 'N/A';
            const paymentAddress = paymentAddressElement ? paymentAddressElement.value : 'N/A';
            
            const fromCurrency = getSelectedCurrency('send-currency-wrapper');
            const toCurrency = getSelectedCurrency('receive-currency-wrapper');
            
            const fromName = currencyInfo[fromCurrency]?.name || fromCurrency;
            const toName = currencyInfo[toCurrency]?.name || toCurrency;
            
            const sendAmount = parseFloat(sendAmountElement ? sendAmountElement.value : 0);
            const receiveAmount = parseFloat(receiveAmountElement ? receiveAmountElement.value : 0);
            
            // Get exchange rate
            const rateKey = `${fromCurrency}-${toCurrency}`;
            let rate = exchangeRates[rateKey];
            
            if (rate === undefined) {
                const inverseRateKey = `${toCurrency}-${fromCurrency}`;
                const inverseRate = exchangeRates[inverseRateKey];
                
                if (inverseRate !== undefined && inverseRate !== 0) {
                    rate = 1 / inverseRate;
                } else {
                    rate = 0;
                }
            }
            
            const formattedDateTime = getFormattedDateTime();
            const dateTimeParts = formattedDateTime.split(' ');
            const datePart = dateTimeParts[0] || '';
            const timePart = dateTimeParts[1] || '';
            
            // Create receipt HTML matching the exact template from attached file
            const receiptHTML = `
                <div class="receipt-watermark">EXCHANGE</div>
                
                <div class="receipt-header">
                    <div class="receipt-company-info">
                        <div class="receipt-logo">
                            <i class="fas fa-exchange-alt"></i>
                        </div>
                        <div class="receipt-company-text">
                            <div class="receipt-company-name">${siteInfo.name}</div>
                            <div class="receipt-tagline">${siteInfo.tagline}</div>
                        </div>
                    </div>
                    
                    <div class="receipt-transaction-details">
                        <div class="receipt-transaction-id">
                            <i class="fas fa-fingerprint"></i>
                            <span>${referenceId}</span>
                        </div>
                        <div class="receipt-date-time">
                            <div>
                                <i class="far fa-calendar-alt"></i>
                                <span>${datePart}</span>
                            </div>
                            <div>
                                <i class="far fa-clock"></i>
                                <span>${timePart}</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="receipt-main-content">
                    <div class="receipt-section">
                        <div class="receipt-section-title">
                            <i class="fas fa-user-circle"></i> Customer Details
                        </div>
                        <div class="receipt-detail-row">
                            <i class="fas fa-user"></i>
                            <span class="receipt-detail-label">Name:</span>
                            <span class="receipt-detail-value">${name}</span>
                        </div>
                        ${email && email !== 'N/A' ? `
                        <div class="receipt-detail-row">
                            <i class="fas fa-envelope"></i>
                            <span class="receipt-detail-label">Email:</span>
                            <span class="receipt-detail-value">${email}</span>
                        </div>
                        ` : ''}
                        ${phone && phone !== 'N/A' ? `
                        <div class="receipt-detail-row">
                            <i class="fas fa-phone"></i>
                            <span class="receipt-detail-label">Phone:</span>
                            <span class="receipt-detail-value"><a href="tel:${phone}">${phone}</a></span>
                        </div>
                        ` : ''}
                        <div class="receipt-detail-row">
                            <i class="fas fa-map-marker-alt"></i>
                            <span class="receipt-detail-label">Address:</span>
                            <span class="receipt-detail-value">${paymentAddress}</span>
                        </div>
                    </div>
                    
                    <div class="receipt-section">
                        <div class="receipt-section-title">
                            <i class="fas fa-money-bill-wave"></i> Exchange Details
                        </div>
                        <div class="receipt-detail-row">
                            <i class="${currencyInfo[fromCurrency]?.iconClass || 'fas fa-money-bill-wave'}"></i>
                            <span class="receipt-detail-label">From:</span>
                            <span class="receipt-detail-value">${fromName} ${fromCurrency}</span>
                        </div>
                        <div class="receipt-detail-row">
                            <i class="${currencyInfo[toCurrency]?.iconClass || 'fas fa-money-bill-wave'}"></i>
                            <span class="receipt-detail-label">To:</span>
                            <span class="receipt-detail-value">${toName} ${toCurrency}</span>
                        </div>
                        <div class="receipt-detail-row">
                            <i class="fas fa-exchange-alt"></i>
                            <span class="receipt-detail-label">Rate:</span>
                            <span class="receipt-detail-value">${rate.toFixed(4)} ${toCurrency}/${fromCurrency}</span>
                        </div>
                        <div class="receipt-detail-row highlight">
                            <i class="fas fa-dollar-sign"></i>
                            <span class="receipt-detail-label">Sent:</span>
                            <span class="receipt-detail-value">${sendAmount.toFixed(2)} ${fromCurrency}</span>
                        </div>
                        <div class="receipt-detail-row highlight">
                            <i class="fas fa-hand-holding-usd"></i>
                            <span class="receipt-detail-label">Received:</span>
                            <span class="receipt-detail-value">${receiveAmount.toFixed(2)} ${toCurrency}</span>
                        </div>
                    </div>
                </div>
                
                <div class="receipt-footer">
                    <div class="receipt-footer-content">
                        <div class="receipt-footer-section">
                            <i class="fas fa-globe"></i>
                            <div>
                                <div class="receipt-footer-title">Website</div>
                                <div class="receipt-footer-value">${window.location.hostname}</div>
                            </div>
                        </div>
                        <div class="receipt-footer-section">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <div class="receipt-footer-title">Email Address</div>
                                <div class="receipt-footer-value">${siteInfo.email}</div>
                            </div>
                        </div>
                        <div class="receipt-footer-section">
                            <i class="fas fa-phone-alt"></i>
                            <div>
                                <div class="receipt-footer-title">Contact Number</div>
                                <div class="receipt-footer-value"><a href="tel:${siteInfo.phone}">${siteInfo.phone}</a></div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            const receiptContent = document.getElementById('receipt-content');
            if (receiptContent) {
                receiptContent.innerHTML = receiptHTML;
            }
            
            const receiptModal = document.getElementById('receipt-modal');
            if (receiptModal) {
                receiptModal.classList.remove('hidden');
            }
        } catch (error) {
            console.error('Error generating receipt modal:', error);
            showToast('error', 'Failed to generate receipt content');
        }
    }

    function hideReceiptModal() {
        try {
            const receiptModal = document.getElementById('receipt-modal');
            if (receiptModal) {
                receiptModal.classList.add('hidden');
            }
        } catch (error) {
            console.error('Error hiding receipt modal:', error);
        }
    }

    function downloadReceiptAsPDF() {
        try {
            const element = document.getElementById('receipt-content');
            if (!element) {
                showToast('error', 'Receipt content not found');
                return;
            }
            
            const referenceElement = document.getElementById('reference-id');
            const referenceId = referenceElement ? referenceElement.textContent : 'receipt';
            
            const opt = {
                margin: 0,
                filename: `${siteInfo.name}_Receipt_${referenceId}.pdf`,
                image: { type: 'jpeg', quality: 0.98 },
                html2canvas: { scale: 2, useCORS: true, logging: false },
                jsPDF: { unit: 'cm', format: [19, 13], orientation: 'landscape' }
            };
            
            if (typeof html2pdf !== 'undefined') {
                html2pdf().set(opt).from(element).save();
            } else {
                showToast('error', 'PDF library not loaded');
            }
        } catch (error) {
            console.error('Error downloading PDF:', error);
            showToast('error', 'Failed to generate PDF');
        }
    }

    function printReceiptContent() {
        try {
            const receiptContent = document.getElementById('receipt-content');
            if (!receiptContent) {
                showToast('error', 'Receipt content not found');
                return;
            }
            
            const referenceElement = document.getElementById('reference-id');
            const referenceId = referenceElement ? referenceElement.textContent : 'receipt';
            
            const printWindow = window.open('', '_blank');
            if (!printWindow) {
                showToast('error', 'Failed to open print window');
                return;
            }
            
            const styles = document.querySelector('style');
            const stylesContent = styles ? styles.innerHTML : '';
            
            printWindow.document.write(`
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Receipt - ${referenceId}</title>
                    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
                    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700;800&family=Playfair+Display:wght@400;500;600;700;800&display=swap" rel="stylesheet">
                    <style>
                        ${stylesContent}
                        body { margin: 0; padding: 20px; background: white; }
                        .receipt-container { box-shadow: none; }
                    </style>
                </head>
                <body>
                    <div class="receipt-container">
                        ${receiptContent.innerHTML}
                    </div>
                </body>
                </html>
            `);
            printWindow.document.close();
            printWindow.print();
        } catch (error) {
            console.error('Error printing receipt:', error);
            showToast('error', 'Failed to print receipt');
        }
    }

    function copyAccountToClipboard() {
        try {
            const accountTextElement = document.getElementById('account-text');
            if (!accountTextElement) {
                showToast('error', 'Account information not found');
                return;
            }
            
            const accountText = accountTextElement.textContent;
            
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(accountText)
                    .then(() => {
                        const successMessage = wizardSettings.copy_success_message || 'অ্যাকাউন্ট নাম্বার ক্লিপবোর্ডে কপি হয়েছে';
                        showToast('success', successMessage);
                    })
                    .catch(err => {
                        console.error('Clipboard API failed:', err);
                        fallbackCopyToClipboard(accountText);
                    });
            } else {
                fallbackCopyToClipboard(accountText);
            }
        } catch (error) {
            console.error('Error copying to clipboard:', error);
            showToast('error', 'Failed to copy account details');
        }
    }

    function fallbackCopyToClipboard(text) {
        try {
            const textarea = document.createElement('textarea');
            textarea.value = text;
            textarea.style.position = 'fixed';
            textarea.style.left = '-999999px';
            textarea.style.top = '-999999px';
            document.body.appendChild(textarea);
            textarea.focus();
            textarea.select();
            
            const successful = document.execCommand('copy');
            document.body.removeChild(textarea);
            
            if (successful) {
                const successMessage = wizardSettings.copy_success_message || 'অ্যাকাউন্ট নাম্বার ক্লিপবোর্ডে কপি হয়েছে';
                showToast('success', successMessage);
            } else {
                showToast('error', 'Failed to copy account details');
            }
        } catch (err) {
            console.error('Fallback copy failed:', err);
            showToast('error', 'Failed to copy account details');
        }
    }

    // Expose global functions
    window.showStep = showStep;
    window.showToast = showToast;
    window.hideToast = hideToast;

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initialize);
    } else {
        initialize();
    }

})();
</script>

<!-- Load HTML2PDF library for receipt generation -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>