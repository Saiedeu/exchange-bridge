<?php
/**
 * Exchange Bridge License Diagnostic Tool
 * Run this file to diagnose license verification issues
 */

// Start output buffering for clean display
ob_start();

// Define constants to prevent errors
if (!defined('EB_SCRIPT_RUNNING')) {
    define('EB_SCRIPT_RUNNING', true);
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Exchange Bridge License Diagnostic Tool</title>
    <link href="https://cdn.tailwindcss.com/3.4.0/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .gradient-bg { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); }
        .diagnostic-card { backdrop-filter: blur(10px); background: rgba(255, 255, 255, 0.95); border: 1px solid rgba(255, 255, 255, 0.2); }
        .status-pass { color: #10b981; }
        .status-fail { color: #ef4444; }
        .status-warning { color: #f59e0b; }
        .code-block { background: #1f2937; color: #e5e7eb; padding: 1rem; border-radius: 0.5rem; font-family: 'Courier New', monospace; }
    </style>
</head>
<body class="gradient-bg min-h-screen py-8">
    <div class="container mx-auto px-4 max-w-4xl">
        <div class="diagnostic-card rounded-lg shadow-2xl p-8 mb-8">
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-800 mb-2">
                    <i class="fas fa-stethoscope mr-3"></i>License Diagnostic Tool
                </h1>
                <p class="text-gray-600">Exchange Bridge License Verification System</p>
            </div>

            <?php
            // Initialize diagnostic results
            $diagnostics = [];
            $overallStatus = true;

            // Helper function to add diagnostic result
            function addDiagnostic($title, $status, $message, $solution = '') {
                global $diagnostics, $overallStatus;
                $diagnostics[] = [
                    'title' => $title,
                    'status' => $status,
                    'message' => $message,
                    'solution' => $solution
                ];
                if ($status === 'fail') {
                    $overallStatus = false;
                }
            }

            // Get current directory and determine paths
            $currentDir = __DIR__;
            $configDir = $currentDir . '/config';
            $includesDir = $currentDir . '/includes';

            // 1. Check file structure
            $requiredFiles = [
                'config/config.php' => $configDir . '/config.php',
                'config/license.php' => $configDir . '/license.php',
                'config/verification.php' => $configDir . '/verification.php',
                'config/install.lock' => $configDir . '/install.lock',
                'includes/license_check.php' => $includesDir . '/license_check.php',
                'includes/license_protection.php' => $includesDir . '/license_protection.php',
                'includes/security.php' => $includesDir . '/security.php',
                'includes/functions.php' => $includesDir . '/functions.php',
            ];

            $missingFiles = [];
            $existingFiles = [];

            foreach ($requiredFiles as $name => $path) {
                if (file_exists($path)) {
                    $existingFiles[] = $name;
                } else {
                    $missingFiles[] = $name;
                }
            }

            if (empty($missingFiles)) {
                addDiagnostic('File Structure', 'pass', 'All required files are present', '');
            } else {
                addDiagnostic('File Structure', 'fail', 'Missing files: ' . implode(', ', $missingFiles), 'Upload missing files from the original script package');
            }

            // 2. Check license configuration
            $licenseConfigPath = $configDir . '/license.php';
            if (file_exists($licenseConfigPath)) {
                include_once $licenseConfigPath;
                
                $licenseConstants = ['LICENSE_KEY', 'LICENSE_API_URL', 'LICENSE_API_KEY', 'LICENSE_SALT'];
                $missingConstants = [];
                
                foreach ($licenseConstants as $constant) {
                    if (!defined($constant)) {
                        $missingConstants[] = $constant;
                    }
                }
                
                if (empty($missingConstants)) {
                    addDiagnostic('License Configuration', 'pass', 'All license constants are defined', '');
                    
                    // Check license key format
                    if (defined('LICENSE_KEY')) {
                        $licenseKey = LICENSE_KEY;
                        if (strlen($licenseKey) >= 32 && preg_match('/^[A-Za-z0-9\-]+$/', $licenseKey)) {
                            addDiagnostic('License Key Format', 'pass', 'License key format is valid', '');
                        } else {
                            addDiagnostic('License Key Format', 'fail', 'License key format is invalid', 'Contact support for a valid license key');
                        }
                    }
                } else {
                    addDiagnostic('License Configuration', 'fail', 'Missing constants: ' . implode(', ', $missingConstants), 'Check config/license.php file and ensure all constants are defined');
                }
            } else {
                addDiagnostic('License Configuration', 'fail', 'License configuration file missing', 'Create config/license.php with proper license constants');
            }

            // 3. Check verification file
            $verificationPath = $configDir . '/verification.php';
            if (file_exists($verificationPath)) {
                $verification = include $verificationPath;
                if (is_array($verification)) {
                    $requiredKeys = ['license_key', 'domain', 'status', 'hash', 'last_check'];
                    $missingKeys = [];
                    
                    foreach ($requiredKeys as $key) {
                        if (!isset($verification[$key])) {
                            $missingKeys[] = $key;
                        }
                    }
                    
                    if (empty($missingKeys)) {
                        if ($verification['status'] === 'active') {
                            addDiagnostic('Verification File', 'pass', 'Verification file is valid and active', '');
                        } else {
                            addDiagnostic('Verification File', 'fail', 'License status is: ' . $verification['status'], 'Contact support to reactivate your license');
                        }
                        
                        // Check domain match
                        $currentDomain = strtolower(preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
                        if ($verification['domain'] === '*' || $verification['domain'] === $currentDomain) {
                            addDiagnostic('Domain Verification', 'pass', 'Domain is authorized', '');
                        } else {
                            addDiagnostic('Domain Verification', 'fail', 'Domain mismatch: Licensed for ' . $verification['domain'] . ', running on ' . $currentDomain, 'Update license for this domain or transfer to licensed domain');
                        }
                        
                        // Check last verification time
                        $lastCheck = $verification['last_check'] ?? 0;
                        $daysSinceCheck = floor((time() - $lastCheck) / 86400);
                        if ($daysSinceCheck <= 7) {
                            addDiagnostic('Last Check', 'pass', 'Last verified ' . $daysSinceCheck . ' days ago', '');
                        } else {
                            addDiagnostic('Last Check', 'warning', 'Last verified ' . $daysSinceCheck . ' days ago', 'Server verification needed soon');
                        }
                    } else {
                        addDiagnostic('Verification File', 'fail', 'Invalid verification data: missing ' . implode(', ', $missingKeys), 'Delete verification.php to force re-verification');
                    }
                } else {
                    addDiagnostic('Verification File', 'fail', 'Verification file is corrupted', 'Delete verification.php to force re-verification');
                }
            } else {
                addDiagnostic('Verification File', 'warning', 'No verification file found', 'Will attempt server verification on next access');
            }

            // 4. Check system failure marker
            $failureMarker = $configDir . '/.system_failure';
            if (file_exists($failureMarker)) {
                $failureData = @json_decode(file_get_contents($failureMarker), true);
                $reason = $failureData['reason'] ?? 'Unknown';
                addDiagnostic('System Status', 'fail', 'System failure detected: ' . $reason, 'Delete .system_failure file after resolving the issue');
            } else {
                addDiagnostic('System Status', 'pass', 'No system failure detected', '');
            }

            // 5. Check file permissions
            $permissionIssues = [];
            $checkPaths = [$configDir, $includesDir];
            
            foreach ($checkPaths as $path) {
                if (is_dir($path)) {
                    if (!is_readable($path)) {
                        $permissionIssues[] = $path . ' (not readable)';
                    }
                    if (!is_writable($path)) {
                        $permissionIssues[] = $path . ' (not writable)';
                    }
                }
            }
            
            if (empty($permissionIssues)) {
                addDiagnostic('File Permissions', 'pass', 'Directory permissions are correct', '');
            } else {
                addDiagnostic('File Permissions', 'fail', 'Permission issues: ' . implode(', ', $permissionIssues), 'Set proper directory permissions (755 for directories, 644 for files)');
            }

            // 6. Test server connectivity
            if (defined('LICENSE_API_URL')) {
                $apiUrl = LICENSE_API_URL;
                $testData = ['action' => 'ping'];
                
                $serverReachable = false;
                if (function_exists('curl_init')) {
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $apiUrl,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query($testData),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_TIMEOUT => 10,
                        CURLOPT_USERAGENT => 'License Diagnostic Tool'
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        $serverReachable = true;
                    }
                }
                
                if ($serverReachable) {
                    addDiagnostic('Server Connectivity', 'pass', 'License server is reachable', '');
                } else {
                    addDiagnostic('Server Connectivity', 'warning', 'Cannot reach license server', 'Check internet connection or firewall settings');
                }
            }

            // 7. Check for install.lock
            $installLock = $configDir . '/install.lock';
            if (file_exists($installLock)) {
                addDiagnostic('Installation Status', 'pass', 'Installation is complete', '');
            } else {
                addDiagnostic('Installation Status', 'fail', 'Installation not completed', 'Complete the installation process first');
            }

            // Display results
            ?>
            
            <div class="space-y-4">
                <?php foreach ($diagnostics as $diagnostic): ?>
                <div class="border rounded-lg p-4 <?php echo $diagnostic['status'] === 'pass' ? 'border-green-300 bg-green-50' : ($diagnostic['status'] === 'fail' ? 'border-red-300 bg-red-50' : 'border-yellow-300 bg-yellow-50'); ?>">
                    <div class="flex items-center justify-between">
                        <h3 class="font-semibold text-gray-800 flex items-center">
                            <i class="fas <?php echo $diagnostic['status'] === 'pass' ? 'fa-check-circle status-pass' : ($diagnostic['status'] === 'fail' ? 'fa-times-circle status-fail' : 'fa-exclamation-triangle status-warning'); ?> mr-2"></i>
                            <?php echo htmlspecialchars($diagnostic['title']); ?>
                        </h3>
                        <span class="text-sm font-medium <?php echo $diagnostic['status'] === 'pass' ? 'status-pass' : ($diagnostic['status'] === 'fail' ? 'status-fail' : 'status-warning'); ?>">
                            <?php echo strtoupper($diagnostic['status']); ?>
                        </span>
                    </div>
                    <p class="text-gray-700 mt-2"><?php echo htmlspecialchars($diagnostic['message']); ?></p>
                    <?php if (!empty($diagnostic['solution'])): ?>
                    <div class="mt-2 p-3 bg-blue-50 border border-blue-200 rounded">
                        <p class="text-blue-800 font-medium">Solution:</p>
                        <p class="text-blue-700"><?php echo htmlspecialchars($diagnostic['solution']); ?></p>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Overall Status -->
            <div class="mt-8 p-6 rounded-lg <?php echo $overallStatus ? 'bg-green-50 border border-green-300' : 'bg-red-50 border border-red-300'; ?>">
                <h2 class="text-xl font-bold <?php echo $overallStatus ? 'text-green-800' : 'text-red-800'; ?> mb-2">
                    <i class="fas <?php echo $overallStatus ? 'fa-shield-alt' : 'fa-exclamation-triangle'; ?> mr-2"></i>
                    Overall Status: <?php echo $overallStatus ? 'HEALTHY' : 'ISSUES DETECTED'; ?>
                </h2>
                <p class="<?php echo $overallStatus ? 'text-green-700' : 'text-red-700'; ?>">
                    <?php if ($overallStatus): ?>
                        Your license system appears to be configured correctly. If you're still experiencing issues, try deleting the .system_failure file (if it exists) and refresh your main page.
                    <?php else: ?>
                        Critical issues were detected that prevent license verification. Please resolve the issues marked as "FAIL" above.
                    <?php endif; ?>
                </p>
            </div>

            <!-- Quick Actions -->
            <div class="mt-8 p-6 bg-gray-50 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <button onclick="deleteSystemFailure()" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded transition-colors">
                        <i class="fas fa-trash mr-2"></i>Delete System Failure Marker
                    </button>
                    <button onclick="deleteVerification()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded transition-colors">
                        <i class="fas fa-refresh mr-2"></i>Force Re-verification
                    </button>
                    <button onclick="showSystemInfo()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded transition-colors">
                        <i class="fas fa-info-circle mr-2"></i>Show System Info
                    </button>
                    <button onclick="testLicenseKey()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded transition-colors">
                        <i class="fas fa-key mr-2"></i>Test License Key
                    </button>
                </div>
            </div>

            <!-- System Information -->
            <div id="systemInfo" class="mt-8 p-6 bg-gray-100 rounded-lg" style="display: none;">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">System Information</h3>
                <div class="code-block">
                    <strong>Domain:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?><br>
                    <strong>IP Address:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?><br>
                    <strong>PHP Version:</strong> <?php echo PHP_VERSION; ?><br>
                    <strong>Server Software:</strong> <?php echo htmlspecialchars($_SERVER['SERVER_SOFTWARE'] ?? 'Unknown'); ?><br>
                    <strong>Document Root:</strong> <?php echo htmlspecialchars($_SERVER['DOCUMENT_ROOT'] ?? 'Unknown'); ?><br>
                    <strong>Script Path:</strong> <?php echo htmlspecialchars(__FILE__); ?><br>
                    <strong>Config Directory:</strong> <?php echo htmlspecialchars($configDir); ?><br>
                    <strong>Config Dir Exists:</strong> <?php echo is_dir($configDir) ? 'Yes' : 'No'; ?><br>
                    <strong>Config Dir Writable:</strong> <?php echo is_writable($configDir) ? 'Yes' : 'No'; ?><br>
                    <?php if (defined('LICENSE_KEY')): ?>
                    <strong>License Key:</strong> <?php echo htmlspecialchars(substr(LICENSE_KEY, 0, 10) . '...'); ?><br>
                    <?php endif; ?>
                    <?php if (defined('LICENSE_API_URL')): ?>
                    <strong>API URL:</strong> <?php echo htmlspecialchars(LICENSE_API_URL); ?><br>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script>
        function deleteSystemFailure() {
            if (confirm('Are you sure you want to delete the system failure marker?')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_failure'
                }).then(response => response.text()).then(data => {
                    alert('System failure marker deleted');
                    location.reload();
                });
            }
        }

        function deleteVerification() {
            if (confirm('Are you sure you want to delete the verification file? This will force re-verification.')) {
                fetch('', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete_verification'
                }).then(response => response.text()).then(data => {
                    alert('Verification file deleted');
                    location.reload();
                });
            }
        }

        function showSystemInfo() {
            const info = document.getElementById('systemInfo');
            info.style.display = info.style.display === 'none' ? 'block' : 'none';
        }

        function testLicenseKey() {
            fetch('', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: 'action=test_license'
            }).then(response => response.text()).then(data => {
                alert(data);
            });
        }
    </script>
</body>
</html>

<?php
// Handle AJAX actions
if ($_POST['action'] ?? '') {
    $action = $_POST['action'];
    
    switch ($action) {
        case 'delete_failure':
            $failureFile = $configDir . '/.system_failure';
            if (file_exists($failureFile)) {
                unlink($failureFile);
                echo 'System failure marker deleted successfully';
            } else {
                echo 'No system failure marker found';
            }
            exit;
            
        case 'delete_verification':
            $verificationFile = $configDir . '/verification.php';
            if (file_exists($verificationFile)) {
                unlink($verificationFile);
                echo 'Verification file deleted successfully';
            } else {
                echo 'No verification file found';
            }
            exit;
            
        case 'test_license':
            if (defined('LICENSE_KEY') && defined('LICENSE_API_URL')) {
                try {
                    $apiUrl = LICENSE_API_URL;
                    $apiKey = defined('LICENSE_API_KEY') ? LICENSE_API_KEY : '';
                    $domain = strtolower(preg_replace('/^www\./i', '', $_SERVER['HTTP_HOST'] ?? 'localhost'));
                    
                    $postData = [
                        'action' => 'verify',
                        'license_key' => LICENSE_KEY,
                        'domain' => $domain,
                        'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
                        'api_key' => $apiKey,
                        'product' => 'exchange_bridge',
                        'version' => '3.0.0'
                    ];
                    
                    $ch = curl_init();
                    curl_setopt_array($ch, [
                        CURLOPT_URL => $apiUrl,
                        CURLOPT_POST => true,
                        CURLOPT_POSTFIELDS => http_build_query($postData),
                        CURLOPT_RETURNTRANSFER => true,
                        CURLOPT_SSL_VERIFYPEER => false,
                        CURLOPT_TIMEOUT => 30
                    ]);
                    
                    $response = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200) {
                        $result = json_decode($response, true);
                        if ($result) {
                            echo 'Server Response: ' . ($result['message'] ?? 'Unknown response');
                        } else {
                            echo 'Invalid response from server';
                        }
                    } else {
                        echo 'Server returned HTTP code: ' . $httpCode;
                    }
                } catch (Exception $e) {
                    echo 'Error testing license: ' . $e->getMessage();
                }
            } else {
                echo 'License configuration not found';
            }
            exit;
    }
}

ob_end_flush();
?>