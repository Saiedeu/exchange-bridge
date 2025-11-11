<?php
/**
 * ExchangeBridge - Application Footer
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

$siteName = getSetting('site_name', SITE_NAME);
$footerColor = getSetting('footer_color', '#1E3A8A');
$contactPhone = getSetting('contact_phone', '+8801869838872');
$contactWhatsapp = getSetting('contact_whatsapp', '8801869838872');
$contactEmail = getSetting('contact_email', 'support@exchangebridge.com');
$contactAddress = getSetting('contact_address', 'Dhaka, Bangladesh');
$workingHours = getSetting('working_hours', '9 am-11.50pm +6');

// Get floating buttons from database (exclude system buttons)
$db = Database::getInstance();
$floatingButtons = $db->getRows("SELECT * FROM floating_buttons WHERE status = 'active' AND button_type = 'custom' ORDER BY order_index ASC, created_at DESC");

// Helper function to get display name for site logo/name
function getFooterDisplaySiteName() {
    // Check logo type setting
    $logoType = getSetting('logo_type', 'text');
    
    if ($logoType === 'text' || $logoType === 'both') {
        // Use logo text if available, otherwise fall back to site name
        $logoText = getSetting('site_logo_text', '');
        if (!empty($logoText)) {
            return $logoText;
        }
    }
    
    // Fall back to site name
    return getSetting('site_name', SITE_NAME);
}
?>

<!-- Footer -->
<footer class="bg-footerbg text-white py-6 mt-8" style="background-color: <?php echo $footerColor; ?>;">
    <div class="container mx-auto px-4">
        <div class="flex flex-col md:flex-row justify-between items-center mb-6">
            <div class="mb-4 md:mb-0">
                <div class="site-name-badge text-xl font-bold mb-2">
                    <?php echo htmlspecialchars(getFooterDisplaySiteName()); ?>
                    <div class="site-tagline"><?php echo htmlspecialchars(getSetting('site_tagline', 'Exchange Taka Globally')); ?></div>
                </div>
                <div class="flex space-x-3 justify-center md:justify-start">
                    <a href="<?php echo getSetting('social_facebook', '#'); ?>" class="text-white hover:text-yellow-300 transition-colors">
                        <i class="fab fa-facebook-f"></i>
                    </a>
                    <a href="<?php echo getSetting('social_twitter', '#'); ?>" class="text-white hover:text-yellow-300 transition-colors">
                        <i class="fab fa-twitter"></i>
                    </a>
                    <a href="<?php echo getSetting('social_telegram', '#'); ?>" class="text-white hover:text-yellow-300 transition-colors">
                        <i class="fab fa-telegram"></i>
                    </a>
                    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contactWhatsapp); ?>" class="text-white hover:text-yellow-300 transition-colors">
                        <i class="fab fa-whatsapp"></i>
                    </a>
                </div>
            </div>
            
            <div class="grid grid-cols-2 gap-x-8 gap-y-2 text-center md:text-left text-sm mb-4 md:mb-0">
                <a href="about.php" class="text-white hover:text-yellow-300 transition-colors">About Us</a>
                <a href="contact.php" class="text-white hover:text-yellow-300 transition-colors">Contact Us</a>
                <a href="privacy.php" class="text-white hover:text-yellow-300 transition-colors">Privacy Policy</a>
                <a href="faq.php" class="text-white hover:text-yellow-300 transition-colors">FAQ</a>
            </div>
            
            <div class="text-center md:text-right text-sm">
                <div class="text-white mb-1">Working Time: <?php echo $workingHours; ?></div>
                <div class="text-white mb-1">
                    <i class="fas fa-phone-alt mr-1"></i> <a href="tel:<?php echo $contactPhone; ?>"><?php echo $contactPhone; ?></a>
                </div>
                <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $contactWhatsapp); ?>" id="footer-whatsapp-btn" class="bg-green-500 text-white inline-block px-3 py-1 rounded-lg cursor-pointer hover:bg-green-600 transition-colors">
                    <i class="fab fa-whatsapp mr-1"></i> Message us
                </a>
            </div>
        </div>
        
        <div class="bg-copyrightbg py-3 text-center text-sm text-white -mx-4 mt-4">
            <?php echo getSetting('footer_copyright', '© ' . date('Y') . ' ' . $siteName . '. All rights reserved.'); ?>
        </div>
    </div>
</footer>

<!-- Floating Buttons System -->
<div id="floating-buttons-container">
    <!-- System WhatsApp Button -->
    <?php if (getSetting('enable_whatsapp_button', 'yes') === 'yes'): ?>
    <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', getSetting('whatsapp_number', $contactWhatsapp)); ?>?text=<?php echo urlencode(getSetting('whatsapp_message', 'Hello ' . $siteName . ' Operator')); ?>" 
       id="system-whatsapp-btn" 
       class="floating-btn system-btn whatsapp-system" 
       title="Chat on WhatsApp"
       data-system-button="whatsapp">
        <i class="fab fa-whatsapp"></i>
    </a>
    <?php endif; ?>

    <!-- Custom Floating Buttons from Database -->
    <?php if (!empty($floatingButtons)): ?>
        <?php 
        $leftSideButtons = [];
        $rightSideButtons = [];
        $bottomLeftButtons = [];
        $bottomRightButtons = [];
        
        // Categorize buttons by position
        foreach ($floatingButtons as $button) {
            if ($button['position'] === 'left') {
                $leftSideButtons[] = $button;
            } elseif ($button['position'] === 'right') {
                $rightSideButtons[] = $button;
            } elseif ($button['position'] === 'bottom-left') {
                $bottomLeftButtons[] = $button;
            } elseif ($button['position'] === 'bottom-right') {
                $bottomRightButtons[] = $button;
            }
        }
        ?>

        <!-- Left Side Buttons -->
        <?php if (!empty($leftSideButtons)): ?>
        <div class="floating-buttons-left">
            <?php foreach ($leftSideButtons as $index => $button): ?>
            <?php 
            $buttonUrl = handleButtonUrl($button['url']);
            $onClickHandler = getButtonClickHandler($button['url']);
            ?>
            <a href="<?php echo $buttonUrl; ?>" 
               target="<?php echo $button['target']; ?>"
               <?php echo $onClickHandler; ?>
               class="floating-btn custom-btn <?php echo !$button['show_on_mobile'] ? 'desktop-only' : ''; ?> <?php echo !$button['show_on_desktop'] ? 'mobile-only' : ''; ?>"
               style="background-color: <?php echo $button['color']; ?>; bottom: <?php echo 140 + ($index * 70); ?>px;"
               title="<?php echo htmlspecialchars($button['title']); ?>">
                
                <?php if (!empty($button['custom_icon']) && file_exists($button['custom_icon'])): ?>
                    <img src="<?php echo SITE_URL . '/' . $button['custom_icon']; ?>" 
                         alt="<?php echo htmlspecialchars($button['title']); ?>" 
                         class="custom-icon">
                <?php else: ?>
                    <i class="<?php echo $button['icon']; ?>"></i>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Right Side Buttons -->
        <?php if (!empty($rightSideButtons)): ?>
        <div class="floating-buttons-right">
            <?php foreach ($rightSideButtons as $index => $button): ?>
            <?php 
            $buttonUrl = handleButtonUrl($button['url']);
            $onClickHandler = getButtonClickHandler($button['url']);
            ?>
            <a href="<?php echo $buttonUrl; ?>" 
               target="<?php echo $button['target']; ?>"
               <?php echo $onClickHandler; ?>
               class="floating-btn custom-btn <?php echo !$button['show_on_mobile'] ? 'desktop-only' : ''; ?> <?php echo !$button['show_on_desktop'] ? 'mobile-only' : ''; ?>"
               style="background-color: <?php echo $button['color']; ?>; bottom: <?php echo 140 + ($index * 70); ?>px;"
               title="<?php echo htmlspecialchars($button['title']); ?>">
                
                <?php if (!empty($button['custom_icon']) && file_exists($button['custom_icon'])): ?>
                    <img src="<?php echo SITE_URL . '/' . $button['custom_icon']; ?>" 
                         alt="<?php echo htmlspecialchars($button['title']); ?>" 
                         class="custom-icon">
                <?php else: ?>
                    <i class="<?php echo $button['icon']; ?>"></i>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Bottom Left Buttons -->
        <?php if (!empty($bottomLeftButtons)): ?>
        <div class="floating-buttons-bottom-left">
            <?php foreach ($bottomLeftButtons as $index => $button): ?>
            <?php 
            $buttonUrl = handleButtonUrl($button['url']);
            $onClickHandler = getButtonClickHandler($button['url']);
            ?>
            <a href="<?php echo $buttonUrl; ?>" 
               target="<?php echo $button['target']; ?>"
               <?php echo $onClickHandler; ?>
               class="floating-btn custom-btn <?php echo !$button['show_on_mobile'] ? 'desktop-only' : ''; ?> <?php echo !$button['show_on_desktop'] ? 'mobile-only' : ''; ?>"
               style="background-color: <?php echo $button['color']; ?>; left: <?php echo 90 + ($index * 70); ?>px;"
               title="<?php echo htmlspecialchars($button['title']); ?>">
                
                <?php if (!empty($button['custom_icon']) && file_exists($button['custom_icon'])): ?>
                    <img src="<?php echo SITE_URL . '/' . $button['custom_icon']; ?>" 
                         alt="<?php echo htmlspecialchars($button['title']); ?>" 
                         class="custom-icon">
                <?php else: ?>
                    <i class="<?php echo $button['icon']; ?>"></i>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Bottom Right Buttons -->
        <?php if (!empty($bottomRightButtons)): ?>
        <div class="floating-buttons-bottom-right">
            <?php foreach ($bottomRightButtons as $index => $button): ?>
            <?php 
            $buttonUrl = handleButtonUrl($button['url']);
            $onClickHandler = getButtonClickHandler($button['url']);
            ?>
            <a href="<?php echo $buttonUrl; ?>" 
               target="<?php echo $button['target']; ?>"
               <?php echo $onClickHandler; ?>
               class="floating-btn custom-btn <?php echo !$button['show_on_mobile'] ? 'desktop-only' : ''; ?> <?php echo !$button['show_on_desktop'] ? 'mobile-only' : ''; ?>"
               style="background-color: <?php echo $button['color']; ?>; right: <?php echo 90 + ($index * 70); ?>px;"
               title="<?php echo htmlspecialchars($button['title']); ?>">
                
                <?php if (!empty($button['custom_icon']) && file_exists($button['custom_icon'])): ?>
                    <img src="<?php echo SITE_URL . '/' . $button['custom_icon']; ?>" 
                         alt="<?php echo htmlspecialchars($button['title']); ?>" 
                         class="custom-icon">
                <?php else: ?>
                    <i class="<?php echo $button['icon']; ?>"></i>
                <?php endif; ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Floating Buttons CSS -->
<style>
#floating-buttons-container {
    position: fixed;
    z-index: 9999;
    pointer-events: none;
}

.floating-btn {
    position: fixed;
    width: 56px;
    height: 56px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    text-decoration: none;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
    pointer-events: auto;
    cursor: pointer;
    z-index: 10000;
}

.floating-btn:hover {
    transform: scale(1.1);
    box-shadow: 0 6px 20px rgba(0, 0, 0, 0.4);
    text-decoration: none;
    color: white;
}

.floating-btn i {
    font-size: 24px;
}

.custom-icon {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    object-fit: cover;
}

/* System WhatsApp Button - Made Bigger */
.whatsapp-system {
    bottom: 20px;
    left: 20px;
    background-color: #25D366 !important;
    width: 70px;
    height: 70px;
}

.whatsapp-system i {
    font-size: 32px;
}

/* Left Side Buttons */
.floating-buttons-left .floating-btn {
    left: 20px;
}

/* Right Side Buttons */
.floating-buttons-right .floating-btn {
    right: 20px;
}

/* Bottom Left Buttons */
.floating-buttons-bottom-left .floating-btn {
    bottom: 20px;
}

/* Bottom Right Buttons */
.floating-buttons-bottom-right .floating-btn {
    bottom: 20px;
}

/* Device-specific visibility */
@media (max-width: 768px) {
    .desktop-only {
        display: none !important;
    }
    
    .floating-btn {
        width: 50px;
        height: 50px;
    }
    
    .floating-btn i {
        font-size: 20px;
    }
    
    .custom-icon {
        width: 28px;
        height: 28px;
    }
    
    /* WhatsApp button smaller on mobile but still bigger than others */
    .whatsapp-system {
        width: 60px;
        height: 60px;
    }
    
    .whatsapp-system i {
        font-size: 28px;
    }
}

@media (min-width: 769px) {
    .mobile-only {
        display: none !important;
    }
}

/* Animation for new buttons */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.floating-btn {
    animation: fadeInUp 0.5s ease-out;
}

/* Pulse animation for attention */
.floating-btn.pulse {
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0% {
        transform: scale(1);
    }
    50% {
        transform: scale(1.05);
    }
    100% {
        transform: scale(1);
    }
}

/* Tooltip styles */
.floating-btn::before {
    content: attr(title);
    position: absolute;
    background: rgba(0, 0, 0, 0.8);
    color: white;
    padding: 8px 12px;
    border-radius: 4px;
    font-size: 12px;
    white-space: nowrap;
    opacity: 0;
    visibility: hidden;
    transition: all 0.3s ease;
    z-index: 10001;
}

.whatsapp-system::before,
.floating-buttons-left .floating-btn::before {
    right: -10px;
    top: 50%;
    transform: translateY(-50%) translateX(100%);
}

.floating-buttons-right .floating-btn::before {
    left: -10px;
    top: 50%;
    transform: translateY(-50%) translateX(-100%);
}

.floating-buttons-bottom-left .floating-btn::before,
.floating-buttons-bottom-right .floating-btn::before {
    bottom: 70px;
    left: 50%;
    transform: translateX(-50%);
}

.floating-btn:hover::before {
    opacity: 1;
    visibility: visible;
}
</style>

<!-- JavaScript for Tawk.to Integration -->
<script type="text/javascript">
// System Tawk.to (from Settings) - Keep the system program active
<?php if (getSetting('enable_tawkto', 'yes') === 'yes' && !empty(getSetting('tawkto_widget_code', ''))): ?>
var Tawk_API=Tawk_API||{}, Tawk_LoadStart=new Date();
(function(){
var s1=document.createElement("script"),s0=document.getElementsByTagName("script")[0];
s1.async=true;
s1.src='https://embed.tawk.to/<?php echo getSetting('tawkto_widget_code', ''); ?>';
s1.charset='UTF-8';
s1.setAttribute('crossorigin','*');
s0.parentNode.insertBefore(s1,s0);
})();

// Function to open system Tawk.to chat (from settings)
function openSystemTawktoChat() {
    if (typeof Tawk_API !== 'undefined' && Tawk_API.toggle) {
        Tawk_API.toggle();
    } else if (typeof window.$_Tawk !== 'undefined' && window.$_Tawk.toggle) {
        window.$_Tawk.toggle();
    } else {
        console.log('System Tawk.to not loaded yet');
        setTimeout(openSystemTawktoChat, 1000);
    }
}
<?php endif; ?>

// Function to open custom Tawk.to chat with specific widget ID
function openCustomTawktoChat(widgetId) {
    // Check if we need to load a different widget
    var currentScript = document.querySelector('script[src*="embed.tawk.to"]');
    var expectedSrc = 'https://embed.tawk.to/' + widgetId;
    
    if (currentScript && currentScript.src === expectedSrc) {
        // Same widget is already loaded, just toggle
        if (typeof Tawk_API !== 'undefined' && Tawk_API.toggle) {
            Tawk_API.toggle();
        }
    } else {
        // Need to load different widget
        // Remove existing Tawk.to if present
        if (currentScript) {
            currentScript.remove();
        }
        
        // Load new widget
        var script = document.createElement("script");
        script.async = true;
        script.src = expectedSrc;
        script.charset = 'UTF-8';
        script.setAttribute('crossorigin', '*');
        
        script.onload = function() {
            // Wait a bit for Tawk.to to initialize, then open
            setTimeout(function() {
                if (typeof Tawk_API !== 'undefined' && Tawk_API.toggle) {
                    Tawk_API.toggle();
                } else if (typeof window.$_Tawk !== 'undefined' && window.$_Tawk.toggle) {
                    window.$_Tawk.toggle();
                }
            }, 1500);
        };
        
        document.head.appendChild(script);
    }
}
</script>

<!-- Toast script -->
<script>
    // Show toast notification
    function showToast(title, message, type = 'success') {
        const toast = document.getElementById('toast');
        const toastTitle = document.getElementById('toast-title');
        const toastMessage = document.getElementById('toast-message');
        const toastIcon = document.getElementById('toast-icon');
        const toastIconI = document.getElementById('toast-icon-i');
        const toastProgress = document.getElementById('toast-progress');
        
        toastTitle.textContent = title;
        toastMessage.textContent = message;
        
        // Set icon and colors based on type
        if (type === 'success') {
            toastIcon.className = 'flex-shrink-0 flex items-center justify-center w-12 h-12 text-white rounded-full bg-green-500';
            toastIconI.className = 'fas fa-check-circle fa-lg';
            toastProgress.className = 'h-1 bg-green-500';
        } else if (type === 'error') {
            toastIcon.className = 'flex-shrink-0 flex items-center justify-center w-12 h-12 text-white rounded-full bg-red-500';
            toastIconI.className = 'fas fa-exclamation-circle fa-lg';
            toastProgress.className = 'h-1 bg-red-500';
        } else if (type === 'warning') {
            toastIcon.className = 'flex-shrink-0 flex items-center justify-center w-12 h-12 text-white rounded-full bg-yellow-500';
            toastIconI.className = 'fas fa-exclamation-triangle fa-lg';
            toastProgress.className = 'h-1 bg-yellow-500';
        } else if (type === 'info') {
            toastIcon.className = 'flex-shrink-0 flex items-center justify-center w-12 h-12 text-white rounded-full bg-blue-500';
            toastIconI.className = 'fas fa-info-circle fa-lg';
            toastProgress.className = 'h-1 bg-blue-500';
        }
        
        // Show toast
        toast.classList.add('show');
        
        // Animate progress bar
        let width = 100;
        const interval = setInterval(() => {
            width -= 1;
            toastProgress.style.width = width + '%';
            
            if (width <= 0) {
                clearInterval(interval);
                hideToast();
            }
        }, 30);
    }
    
    // Hide toast notification
    function hideToast() {
        const toast = document.getElementById('toast');
        toast.classList.remove('show');
    }

    // Mobile menu functionality
    document.getElementById('mobile-menu-btn').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.add('open');
        document.getElementById('overlay').classList.add('open');
    });
    
    document.getElementById('close-mobile-menu').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.remove('open');
        document.getElementById('overlay').classList.remove('open');
    });
    
    document.getElementById('overlay').addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.remove('open');
        document.getElementById('overlay').classList.remove('open');
    });
    
    // Toggle dark mode
    document.getElementById('toggle-theme').addEventListener('click', function() {
        document.documentElement.classList.toggle('dark');
    });
    
    // Create floating background icons
    function createFloatingIcon() {
        const icons = [
            '$', '€', '£', '¥', '₹', '₽', '₩', '₪', '₡', '₦',
            '₨', '₱', '₫', '₴', '₵', '₸', '₹', '₺', '₼', '₾', '৳',
            '💰', '💳', '💎', '🏦', '📱', '💸', '💵', '💶', '💷',
            '🪙', '💲', '💯', '📊', '📈', '💹', '🏧', '🔔', '⚡',
            '💻', '💴', '🧧', '💱',
            'BTC', 'Bkash', 'VISA', 'PAYPAL', 'ETH', 'USD', 'EUR', 'GBP',
            'TAKA', 'DOLLAR', 'IBBL', 'BANK ASIA', 'CITYBANK', 'DBBL',
            'NAGAD', 'UPAY', 'REDOT PAY', 'BYBIT', 'BANGLADESH BANK',
            'IFIC', 'CELLFIN', 'MOBILE Banking', 'BRACK', 'PRIME BANK',
            'JAMUNA BANK', 'EXIM', 'BKASH', 'PAYONEER', 'WISE',
            'DUTCH-BANGLA', 'UCB'
        ];
        
        const icon = document.createElement('div');
        icon.className = 'floating-icon';
        
        // Random icon
        icon.textContent = icons[Math.floor(Math.random() * icons.length)];
        
        // Random horizontal position
        icon.style.left = Math.random() * 100 + 'vw';
        
        // Random animation duration
        icon.style.animationDuration = (10 + Math.random() * 15) + 's';
        
        // Random delay
        icon.style.animationDelay = Math.random() * 5 + 's';
        
        document.getElementById('backgroundIcons').appendChild(icon);
        
        // Remove after animation
        setTimeout(() => {
            if (icon.parentNode) {
                icon.parentNode.removeChild(icon);
            }
        }, 25000);
    }

    // Start creating floating icons
    setInterval(createFloatingIcon, 1000);
    
    // Create initial floating icons
    for (let i = 0; i < 10; i++) {
        setTimeout(() => createFloatingIcon(), i * 200);
    }
    
    <?php if (getSetting('enable_popup_notice', 'yes') === 'yes'): ?>
    // Popup notice
    document.addEventListener('DOMContentLoaded', function() {
        const popupNotice = document.getElementById('popup-notice');
        if (popupNotice) {
            // Check if user has already seen the notice
            const noticeId = '<?php echo isset($popup['id']) ? $popup['id'] : '0'; ?>';
            const seenNotices = localStorage.getItem('seenNotices') ? JSON.parse(localStorage.getItem('seenNotices')) : [];
            
            if (!seenNotices.includes(noticeId)) {
                setTimeout(() => {
                    popupNotice.classList.remove('hidden');
                    
                    <?php if (getSetting('enable_notification_sound', 'yes') === 'yes'): ?>
                    // Play notification sound
                    const notificationSound = document.getElementById('notification-sound');
                    if (notificationSound) {
                        notificationSound.play().catch(e => console.log('Sound playback prevented:', e));
                    }
                    <?php endif; ?>
                }, 1000);
            }
            
            // Close button
            document.getElementById('close-popup').addEventListener('click', function() {
                popupNotice.classList.add('hidden');
                
                // Save in local storage
                if (!seenNotices.includes(noticeId)) {
                    seenNotices.push(noticeId);
                    localStorage.setItem('seenNotices', JSON.stringify(seenNotices));
                }
            });
            
            // Dismiss button
            document.getElementById('dismiss-popup').addEventListener('click', function() {
                popupNotice.classList.add('hidden');
                
                // Save in local storage
                if (!seenNotices.includes(noticeId)) {
                    seenNotices.push(noticeId);
                    localStorage.setItem('seenNotices', JSON.stringify(seenNotices));
                }
            });
        }
    });
    <?php endif; ?>
</script>

</div><!-- End #app -->
</body>
</html>

<?php
// Helper function to handle button URLs
function handleButtonUrl($url) {
    // Check if it's a Tawk.to widget ID (format: xxxxxxxxx/xxxxxxx)
    if (preg_match('/^[a-f0-9]+\/[a-z0-9]+$/i', $url)) {
        return '#'; // Return # for Tawk.to IDs, will be handled by onclick
    }
    // Handle JavaScript URLs safely
    if (strpos($url, 'javascript:') === 0) {
        return '#';
    }
    return htmlspecialchars($url);
}

// Helper function to get onclick handler for buttons
function getButtonClickHandler($url) {
    // Check if it's a Tawk.to widget ID
    if (preg_match('/^[a-f0-9]+\/[a-z0-9]+$/i', $url)) {
        return 'onclick="openCustomTawktoChat(\'' . htmlspecialchars($url) . '\'); return false;"';
    }
    // Handle JavaScript URLs
    if (strpos($url, 'javascript:') === 0) {
        return 'onclick="' . htmlspecialchars(substr($url, 11)) . '; return false;"';
    }
    return '';
}
?>