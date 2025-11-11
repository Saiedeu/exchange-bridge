<?php 

/**
 * ExchangeBridge - 403: Forbidden Error Page
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

// Include configuration files - FIXED PATHS
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/verification.php';
require_once __DIR__ . '/../config/license.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Set 403 header
header("HTTP/1.0 403 Forbidden");

// Include header - FIXED PATH
include __DIR__ . '/../templates/header.php';
?>

<!-- Main Content -->
<main class="flex-grow container mx-auto p-4 md:p-6 flex items-center justify-center">
    <div class="text-center max-w-lg">
        <div class="text-primary text-9xl font-bold mb-4">403</div>
        <h1 class="text-3xl font-bold mb-4">Access Forbidden</h1>
        <p class="text-gray-600 dark:text-gray-400 mb-8">
            You don't have permission to access this page. If you believe this is an error, please contact the site administrator.
        </p>
        <div class="flex justify-center space-x-4">
            <a href="/" class="exchange-btn px-6 py-3 rounded-full font-semibold text-white shadow-md hover:shadow-lg">
                <i class="fas fa-home mr-2"></i> Go to Homepage
            </a>
            <a href="/contact" class="bg-gray-500 hover:bg-gray-600 text-white px-6 py-3 rounded-full font-semibold shadow-md hover:shadow-lg">
                <i class="fas fa-envelope mr-2"></i> Contact Support
            </a>
        </div>
        <div class="mt-6">
            <p class="text-gray-600 dark:text-gray-400">
                Need help? <a href="/contact" class="text-primary hover:underline">Contact us</a> or reach out via 
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('contact_whatsapp', '8801869838872')); ?>" class="text-green-600 dark:text-green-400 hover:underline">WhatsApp</a>
            </p>
        </div>
    </div>
</main>

<?php include __DIR__ . '/../templates/footer.php'; ?>