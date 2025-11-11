<?php
/**
 * ExchangeBridge - License Error Template
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

// Send 403 Forbidden HTTP response
http_response_code(403);

// Optional: define a custom error message from exception
$errorMessage = 'Your license could not be verified or system integrity has been compromised.';
if (isset($e) && $e instanceof Exception) {
    $errorMessage = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Error - Exchange Bridge</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #e53e3e 0%, #c53030 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .error-container {
            background: white;
            padding: 40px;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            max-width: 600px;
            text-align: center;
            border: 1px solid #e1e5e9;
            animation: fadeIn 0.5s ease-out;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.1); }
        }
        .error-title {
            color: #2d3748;
            font-size: 28px;
            margin-bottom: 16px;
            font-weight: 600;
        }
        .error-message {
            color: #4a5568;
            font-size: 16px;
            line-height: 1.6;
            margin-bottom: 30px;
        }
        .error-details {
            background: #f7fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 30px;
            text-align: left;
        }
        .btn {
            display: inline-block;
            padding: 12px 24px;
            background: #e53e3e;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        .btn:hover {
            background: #c53030;
            transform: translateY(-1px);
        }
        .footer-info {
            margin-top: 30px;
            font-size: 14px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
        .error-code {
            font-family: 'Courier New', monospace;
            background: #e2e8f0;
            padding: 5px 10px;
            border-radius: 4px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <h1 class="error-title">System Access Denied</h1>
        <div class="error-message">
            <?php echo htmlspecialchars($errorMessage); ?>
        </div>
        
        <div class="error-details">
            <h3 style="margin-top: 0; color: #e53e3e;">⚠️ Possible Causes:</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>License key has been deactivated or expired</li>
                <li>License files have been deleted or modified</li>
                <li>Domain is not authorized for this license</li>
                <li>License server is temporarily unavailable</li>
                <li>System files have been tampered with</li>
                <li>Nulled or pirated version detected</li>
            </ul>
            
            <h3 style="color: #38a169;">✅ How to Resolve:</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Contact support with your license key</li>
                <li>Restore original files if modified</li>
                <li>Reinstall from official source</li>
                <li>Verify domain authorization</li>
            </ul>
        </div>
        
        <div>
            <?php if (file_exists(__DIR__ . '/../install/index.php')): ?>
                <a href="../install/index.php" class="btn">Reinstall System</a>
            <?php endif; ?>
            <?php if (file_exists(__DIR__ . '/../admin/login.php')): ?>
                <a href="../admin/login.php" class="btn">Admin Panel</a>
            <?php endif; ?>
        </div>
        
        <div class="footer-info">
            <p><strong>Error Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>
            <p><strong>Domain:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?></p>
            <p><strong>IP Address:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?></p>
            <p><strong>Error Code:</strong> <span class="error-code">EB-<?php echo strtoupper(substr(md5(time() . ($_SERVER['HTTP_HOST'] ?? '')), 0, 8)); ?></span></p>
            <p style="margin-top: 20px; font-size: 12px; color: #a0aec0;">
                Exchange Bridge v1.0.0 | © 2025 SidMan Solutions
            </p>
        </div>
    </div>

    <script>
    // Prevent common bypass attempts
    document.addEventListener('keydown', function(e) {
        if (e.keyCode === 123 || 
            (e.ctrlKey && e.shiftKey && e.keyCode === 73) ||
            (e.ctrlKey && e.keyCode === 85)) {
            e.preventDefault();
            return false;
        }
    });
    
    // Disable right-click
    document.addEventListener('contextmenu', function(e) {
        e.preventDefault();
        return false;
    });
    
    // Anti-debugging
    setInterval(function() {
        if (window.outerHeight - window.innerHeight > 160) {
            console.clear();
        }
    }, 1000);
    </script>
</body>
</html>
