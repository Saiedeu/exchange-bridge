<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>License Error - Exchange Bridge</title>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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
        }
        .error-icon {
            font-size: 64px;
            margin-bottom: 20px;
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
            background: #4299e1;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            margin: 0 10px;
        }
        .btn:hover {
            background: #3182ce;
            transform: translateY(-1px);
        }
        .btn-danger {
            background: #e53e3e;
        }
        .btn-danger:hover {
            background: #c53030;
        }
        .footer-info {
            margin-top: 30px;
            font-size: 14px;
            color: #718096;
            border-top: 1px solid #e2e8f0;
            padding-top: 20px;
        }
    </style>
</head>
<body>
    <div class="error-container">
        <div class="error-icon">🔒</div>
        <h1 class="error-title">License Verification Failed</h1>
        <div class="error-message">
            <?php echo htmlspecialchars($e->getMessage() ?? 'Your license could not be verified.'); ?>
        </div>
        
        <div class="error-details">
            <h3 style="margin-top: 0;">What does this mean?</h3>
            <ul style="margin: 10px 0; padding-left: 20px;">
                <li>Your license key may have been deactivated</li>
                <li>The license server is temporarily unavailable</li>
                <li>Your license may have expired</li>
                <li>The domain may not be authorized for this license</li>
                <li>License files may have been tampered with</li>
            </ul>
        </div>
        
        <div>
            <a href="install/index.php" class="btn">Reinstall Script</a>
            <a href="admin/login.php" class="btn">Admin Panel</a>
        </div>
        
        <div class="footer-info">
            <p><strong>Error Time:</strong> <?php echo date('Y-m-d H:i:s T'); ?></p>
            <p><strong>Domain:</strong> <?php echo htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'Unknown'); ?></p>
            <p><strong>IP:</strong> <?php echo htmlspecialchars($_SERVER['REMOTE_ADDR'] ?? 'Unknown'); ?></p>
        </div>
    </div>
</body>
</html>