<?php
/**
 * ExchangeBridge - Application Header
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

// Set UTF-8 header for Bengali/multilingual support
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

// Include SEO functions
require_once 'includes/seo-functions.php';

$siteName = getSetting('site_name', SITE_NAME);
$siteTagline = getSetting('site_tagline', 'Exchange Taka Globally');
$primaryColor = getSetting('primary_color', '#5D5CDE');
$secondaryColor = getSetting('secondary_color', '#4BB74B');
$headerColor = getSetting('header_color', '#1E3A8A');
$footerColor = getSetting('footer_color', '#1E3A8A');
$operatorStatus = getSetting('operator_status', 'online');
$workingHours = getSetting('working_hours', '9 am-11.50pm +6');
$contactPhone = getSetting('contact_phone', '+8801869838872');
$contactWhatsapp = getSetting('contact_whatsapp', '8801869838872');
$seoMetaTitle = getSetting('seo_meta_title', $siteName . ' - Fast Currency Exchange');
$seoMetaDescription = getSetting('seo_meta_description', $siteName . ' offers fast and secure currency exchange services globally.');
$seoMetaKeywords = getSetting('seo_meta_keywords', 'currency exchange, taka exchange, paypal, bkash, bitcoin');

// Get SEO settings
$siteLanguage = getSetting('site_language', 'en');
$siteRegion = getSetting('site_region', 'US');
$robotsMeta = getSetting('seo_robots', 'index,follow');
$siteAuthor = getSetting('site_author', '');

// Get logo settings
$logoType = getSetting('logo_type', 'text');
$siteLogo = getSetting('site_logo', '');
$siteLogoText = getSetting('site_logo_text', $siteName);
$logoSize = getSetting('logo_size', 'medium');
$logoPosition = getSetting('logo_position', 'center');
$logoMaxWidth = getSetting('logo_max_width', 200);
$logoMaxHeight = getSetting('logo_max_height', 60);

// Get the current page
$currentPage = basename($_SERVER['PHP_SELF']);

// Helper function to get logo size styles
function getLogoSizeStyles() {
    global $logoSize, $logoMaxWidth, $logoMaxHeight;
    
    switch($logoSize) {
        case 'small':
            return 'max-width: 120px; max-height: 40px;';
        case 'medium':
            return 'max-width: 200px; max-height: 60px;';
        case 'large':
            return 'max-width: 300px; max-height: 80px;';
        case 'custom':
            return "max-width: {$logoMaxWidth}px; max-height: {$logoMaxHeight}px;";
        default:
            return 'max-width: 200px; max-height: 60px;';
    }
}

// Helper function to get site URL
function getSiteUrl() {
    return defined('SITE_URL') ? SITE_URL : '/';
}

// Helper function to render logo based on type with proper positioning
function renderLogo($location = 'header') {
    global $logoType, $siteLogo, $siteLogoText, $siteTagline, $logoPosition;
    
    $logoSizeStyles = getLogoSizeStyles();
    $homeUrl = getSiteUrl();
    
    // Determine container alignment classes based on position
    $containerClasses = '';
    $textAlign = '';
    
    switch($logoPosition) {
        case 'left':
            $containerClasses = 'flex flex-col items-start justify-center';
            $textAlign = 'text-left';
            break;
        case 'right':
            $containerClasses = 'flex flex-col items-end justify-center';
            $textAlign = 'text-right';
            break;
        case 'center':
        default:
            $containerClasses = 'flex flex-col items-center justify-center';
            $textAlign = 'text-center';
            break;
    }
    
    // Add location-specific classes
    $locationClass = $location === 'footer' ? 'footer-logo' : 'header-logo';
    
    echo '<a href="' . $homeUrl . '" class="logo-main-container ' . $containerClasses . ' ' . $textAlign . ' logo-link no-underline ' . $locationClass . '">';
    
    switch($logoType) {
        case 'text':
            // Text logo only - tagline inside frame
            echo '<div class="logo-text-container">';
            echo '<div class="logo-frame">';
            echo '<div class="logo-text-premium">';
            
            // Dynamic text handling - split into words for multi-color effect
            $words = explode(' ', $siteLogoText);
            if (count($words) >= 2) {
                // First word in yellow, second word in coral, rest in yellow
                echo '<span class="exchange-text">' . htmlspecialchars($words[0]) . '</span>';
                echo '<span class="bridge-text">' . htmlspecialchars($words[1]) . '</span>';
                if (count($words) > 2) {
                    for ($i = 2; $i < count($words); $i++) {
                        echo '<span class="exchange-text"> ' . htmlspecialchars($words[$i]) . '</span>';
                    }
                }
            } else {
                // Single word - use gradient effect
                echo '<span class="single-word-text">' . htmlspecialchars($siteLogoText) . '</span>';
            }
            
            echo '</div>';
            // Tagline inside the frame for text logos
            echo '<div class="premium-tagline-inside">' . htmlspecialchars($siteTagline) . '</div>';
            echo '</div>';
            echo '</div>';
            break;
            
        case 'image':
            // Image logo only - tagline outside without frame
            if (!empty($siteLogo) && file_exists($siteLogo)) {
                echo '<div class="logo-image-container">';
                echo '<img src="' . htmlspecialchars($siteLogo) . '" alt="' . htmlspecialchars($siteLogoText) . '" class="logo-image mb-2" style="' . $logoSizeStyles . ' object-fit: contain;">';
                echo '</div>';
                // Tagline outside frame for image logos
                echo '<div class="premium-tagline-outside">' . htmlspecialchars($siteTagline) . '</div>';
            } else {
                // Fallback to text if no image
                echo '<div class="logo-text-container">';
                echo '<div class="logo-frame">';
                echo '<div class="logo-text-premium">';
                
                $words = explode(' ', $siteLogoText);
                if (count($words) >= 2) {
                    echo '<span class="exchange-text">' . htmlspecialchars($words[0]) . '</span>';
                    echo '<span class="bridge-text">' . htmlspecialchars($words[1]) . '</span>';
                    if (count($words) > 2) {
                        for ($i = 2; $i < count($words); $i++) {
                            echo '<span class="exchange-text"> ' . htmlspecialchars($words[$i]) . '</span>';
                        }
                    }
                } else {
                    echo '<span class="single-word-text">' . htmlspecialchars($siteLogoText) . '</span>';
                }
                
                echo '</div>';
                echo '<div class="premium-tagline-inside">' . htmlspecialchars($siteTagline) . '</div>';
                echo '</div>';
                echo '</div>';
            }
            break;
            
        case 'both':
            // Combined image + text logo - tagline inside frame
            echo '<div class="logo-combined-container">';
            
            if (!empty($siteLogo) && file_exists($siteLogo)) {
                // For both type, always use vertical layout but respect alignment
                echo '<div class="flex flex-col items-center space-y-2">';
                echo '<img src="' . htmlspecialchars($siteLogo) . '" alt="' . htmlspecialchars($siteLogoText) . '" class="logo-image" style="' . $logoSizeStyles . ' object-fit: contain;">';
                echo '<div class="logo-frame">';
                echo '<div class="logo-text-premium">';
                
                $words = explode(' ', $siteLogoText);
                if (count($words) >= 2) {
                    echo '<span class="exchange-text">' . htmlspecialchars($words[0]) . '</span>';
                    echo '<span class="bridge-text">' . htmlspecialchars($words[1]) . '</span>';
                    if (count($words) > 2) {
                        for ($i = 2; $i < count($words); $i++) {
                            echo '<span class="exchange-text"> ' . htmlspecialchars($words[$i]) . '</span>';
                        }
                    }
                } else {
                    echo '<span class="single-word-text">' . htmlspecialchars($siteLogoText) . '</span>';
                }
                
                echo '</div>';
                echo '<div class="premium-tagline-inside">' . htmlspecialchars($siteTagline) . '</div>';
                echo '</div>';
                echo '</div>';
            } else {
                // Fallback to text if no image
                echo '<div class="logo-frame">';
                echo '<div class="logo-text-premium">';
                
                $words = explode(' ', $siteLogoText);
                if (count($words) >= 2) {
                    echo '<span class="exchange-text">' . htmlspecialchars($words[0]) . '</span>';
                    echo '<span class="bridge-text">' . htmlspecialchars($words[1]) . '</span>';
                    if (count($words) > 2) {
                        for ($i = 2; $i < count($words); $i++) {
                            echo '<span class="exchange-text"> ' . htmlspecialchars($words[$i]) . '</span>';
                        }
                    }
                } else {
                    echo '<span class="single-word-text">' . htmlspecialchars($siteLogoText) . '</span>';
                }
                
                echo '</div>';
                echo '<div class="premium-tagline-inside">' . htmlspecialchars($siteTagline) . '</div>';
                echo '</div>';
            }
            
            echo '</div>';
            break;
            
        default:
            // Default fallback to text
            echo '<div class="logo-text-container">';
            echo '<div class="logo-frame">';
            echo '<div class="logo-text-premium">';
            
            $words = explode(' ', $siteLogoText);
            if (count($words) >= 2) {
                echo '<span class="exchange-text">' . htmlspecialchars($words[0]) . '</span>';
                echo '<span class="bridge-text">' . htmlspecialchars($words[1]) . '</span>';
                if (count($words) > 2) {
                    for ($i = 2; $i < count($words); $i++) {
                        echo '<span class="exchange-text"> ' . htmlspecialchars($words[$i]) . '</span>';
                    }
                }
            } else {
                echo '<span class="single-word-text">' . htmlspecialchars($siteLogoText) . '</span>';
            }
            
            echo '</div>';
            echo '<div class="premium-tagline-inside">' . htmlspecialchars($siteTagline) . '</div>';
            echo '</div>';
            echo '</div>';
    }
    
    echo '</a>';
}
?>
<!DOCTYPE html>
<html lang="<?php echo $siteLanguage; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($seoMetaTitle); ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($seoMetaDescription); ?>">
    <meta name="keywords" content="<?php echo htmlspecialchars($seoMetaKeywords); ?>">
    <meta name="robots" content="<?php echo htmlspecialchars($robotsMeta); ?>">
    <?php if (!empty($siteAuthor)): ?>
    <meta name="author" content="<?php echo htmlspecialchars($siteAuthor); ?>">
    <?php endif; ?>
    <meta name="language" content="<?php echo $siteLanguage; ?>">
    <meta name="geo.region" content="<?php echo $siteRegion; ?>">
    
    <!-- Advanced SEO Meta Tags -->
    <?php
    // Generate SEO meta tags
    echo generateFaviconLinks();
    echo generateCanonicalUrl();
    echo generateOpenGraphTags();
    echo generateTwitterCardTags();
    ?>
    
    <!-- Verification Meta Tags -->
    <?php
    $googleVerification = getSetting('google_site_verification', '');
    $bingVerification = getSetting('bing_site_verification', '');
    $yandexVerification = getSetting('yandex_site_verification', '');
    
    if (!empty($googleVerification)): ?>
    <meta name="google-site-verification" content="<?php echo htmlspecialchars($googleVerification); ?>">
    <?php endif; ?>
    
    <?php if (!empty($bingVerification)): ?>
    <meta name="msvalidate.01" content="<?php echo htmlspecialchars($bingVerification); ?>">
    <?php endif; ?>
    
    <?php if (!empty($yandexVerification)): ?>
    <meta name="yandex-verification" content="<?php echo htmlspecialchars($yandexVerification); ?>">
    <?php endif; ?>
    
    <!-- Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Google Analytics -->
    <?php
    // Check for both analytics code and analytics ID settings
    $googleAnalyticsCode = getSetting('google_analytics_code', '');
    $googleAnalyticsId = getSetting('google_analytics_id', '');
    
    // Priority: Use custom analytics code first, then analytics ID
    if (!empty($googleAnalyticsCode)):
        echo $googleAnalyticsCode;
    elseif (!empty($googleAnalyticsId)):
        if (strpos($googleAnalyticsId, 'G-') === 0 || strpos($googleAnalyticsId, 'GA_MEASUREMENT_ID') !== false): // GA4
    ?>
    <!-- Google tag (gtag.js) -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=<?php echo $googleAnalyticsId; ?>"></script>
    <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());
        gtag('config', '<?php echo $googleAnalyticsId; ?>');
    </script>
    <?php else: // Universal Analytics ?>
    <script>
        (function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
        (i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
        m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
        })(window,document,'script','https://www.google-analytics.com/analytics.js','ga');
        ga('create', '<?php echo $googleAnalyticsId; ?>', 'auto');
        ga('send', 'pageview');
    </script>
    <?php 
        endif;
    endif; 
    ?>
    
    <!-- Google Tag Manager -->
    <?php
    $googleTagManagerId = getSetting('google_tag_manager_id', '');
    if (!empty($googleTagManagerId)):
    ?>
    <script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':
    new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],
    j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=
    'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);
    })(window,document,'script','dataLayer','<?php echo $googleTagManagerId; ?>');</script>
    <?php endif; ?>
    
    <!-- Facebook Pixel -->
    <?php
    $facebookPixelId = getSetting('facebook_pixel_id', '');
    if (!empty($facebookPixelId)):
    ?>
    <script>
    !function(f,b,e,v,n,t,s)
    {if(f.fbq)return;n=f.fbq=function(){n.callMethod?
    n.callMethod.apply(n,arguments):n.queue.push(arguments)};
    if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
    n.queue=[];t=b.createElement(e);t.async=!0;
    t.src=v;s=b.getElementsByTagName(e)[0];
    s.parentNode.insertBefore(t,s)}(window, document,'script',
    'https://connect.facebook.net/en_US/fbevents.js');
    fbq('init', '<?php echo $facebookPixelId; ?>');
    fbq('track', 'PageView');
    </script>
    <noscript><img height="1" width="1" style="display:none"
    src="https://www.facebook.com/tr?id=<?php echo $facebookPixelId; ?>&ev=PageView&noscript=1"
    /></noscript>
    <?php endif; ?>
    
    <!-- Custom Header Scripts -->
    <?php
    $headerScripts = getSetting('header_scripts', '');
    if (!empty($headerScripts)):
        echo $headerScripts;
    endif;
    ?>
    
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    colors: {
                        primary: '<?php echo $primaryColor; ?>',
                        secondary: '<?php echo $secondaryColor; ?>',
                        danger: '#DC3545',
                        footerbg: '<?php echo $footerColor; ?>', // Deep blue for footer
                        copyrightbg: '#172554', // Darker blue for copyright
                        sitename: '#0F2440', // Site name background
                        gold: '#FFD700', // Premium gold color
                        amber: '#F59E0B', // Premium amber color
                    },
                    boxShadow: {
                        card: '0 4px 15px rgba(0, 0, 0, 0.1)',
                        'logo-glow': '0 0 20px rgba(255, 215, 0, 0.4)',
                        'premium': '0 8px 32px rgba(245, 158, 11, 0.3)',
                    },
                    animation: {
                        'logo-pulse': 'logo-pulse 2s ease-in-out infinite',
                        'text-shimmer': 'text-shimmer 2s ease-in-out infinite',
                        'gold-shimmer': 'gold-shimmer 3s ease-in-out infinite',
                    }
                }
            }
        }

        // Check for dark mode preference
        if (window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches) {
            document.documentElement.classList.add('dark');
        }

        // Listen for changes in color scheme preference
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', event => {
            if (event.matches) {
                document.documentElement.classList.add('dark');
            } else {
                document.documentElement.classList.remove('dark');
            }
        });
    </script>
    <style>
        /* Custom styles */
        .dark {
            --tw-bg-opacity: 1;
            background-color: rgba(24, 24, 24, var(--tw-bg-opacity));
            color: #f0f0f0;
        }

        /* Logo Link Styles */
        .logo-link {
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .logo-link:hover {
            text-decoration: none;
            transform: translateY(-2px);
        }

        /* Logo Main Container - Controls overall positioning */
        .logo-main-container {
            transition: all 0.3s ease;
            min-height: 80px;
            width: 100%;
        }

        /* Footer logo adjustments */
        .footer-logo {
            min-height: auto;
        }

        .footer-logo .logo-frame {
            padding: 15px 25px;
            margin-bottom: 10px;
        }

        .footer-logo .logo-text-premium {
            font-size: 2rem;
        }

        .footer-logo .premium-tagline-inside {
            font-size: 0.875rem;
        }

        .footer-logo .premium-tagline-outside {
            font-size: 0.875rem;
        }

        /* Logo Image Styles */
        .logo-image {
            transition: all 0.3s ease;
            filter: drop-shadow(0 4px 8px rgba(0, 0, 0, 0.1));
            margin: 0 auto;
        }

        .logo-image:hover {
            filter: drop-shadow(0 8px 16px rgba(0, 0, 0, 0.15));
            transform: scale(1.05);
        }

        /* Premium Logo Frame - New Design */
        .logo-frame {
            background: #1B263B; /* deep navy */
            padding: 25px 40px;
            border-radius: 14px;
            border: 4px solid #415A77; /* muted blue-grey */
            box-shadow: 0 12px 30px rgba(27, 38, 59, 0.4);
            display: inline-block;
            margin-bottom: 20px;
            transition: all 0.3s ease;
        }

        .logo-frame:hover {
            transform: translateY(-2px);
            box-shadow: 0 16px 40px rgba(27, 38, 59, 0.5);
        }

        /* Premium Logo Text */
        .logo-text-premium {
            font-family: 'Anton', sans-serif;
            font-size: 3rem;
            text-shadow: 3px 6px 16px rgba(0, 0, 0, 0.7);
            letter-spacing: 0.02em;
            line-height: 1;
            margin-bottom: 10px;
        }

        /* Exchange Text - Sunshine Yellow (First word) */
        .exchange-text {
            color: #FFDE59; /* sunshine yellow */
        }

        /* Bridge Text - Coral Red (Second word) */
        .bridge-text {
            color: #FF6F61; /* coral red */
        }

        /* Single Word Text - Gradient Effect */
        .single-word-text {
            background: linear-gradient(135deg, #FFDE59 0%, #FF6F61 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        /* Premium Tagline Inside Frame */
        .premium-tagline-inside {
            font-family: 'Inter', sans-serif;
            font-size: 0.875rem;
            font-weight: 600;
            color: #E2E8F0; /* Light blue-gray to complement frame */
            text-transform: uppercase;
            letter-spacing: 0.1em;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            opacity: 0.95;
            margin-top: 8px;
        }

        /* Premium Tagline Outside Frame (for image-only logos) */
        .premium-tagline-outside {
            font-family: 'Inter', sans-serif;
            font-size: 1rem;
            font-weight: 600;
            color: #E2E8F0; /* Light blue-gray to complement header */
            text-transform: uppercase;
            letter-spacing: 0.1em;
            margin-top: 0.5rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.3);
            opacity: 0.95;
        }

        /* Dark mode overrides */
        .dark .logo-frame {
            background: #1a2332; /* Slightly lighter for dark mode */
            border-color: #4a5568;
            box-shadow: 0 12px 30px rgba(26, 35, 50, 0.6);
        }

        .dark .premium-tagline-inside {
            color: #CBD5E0; /* Lighter for dark mode */
        }

        .dark .premium-tagline-outside {
            color: #CBD5E0; /* Lighter for dark mode */
        }

        /* Text Container */
        .logo-text-container {
            width: 100%;
        }

        /* Image Container */
        .logo-image-container {
            width: 100%;
        }

        /* Combined Logo Container */
        .logo-combined-container {
            width: 100%;
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .logo-main-container {
                min-height: 70px;
            }
            
            .logo-text-premium {
                font-size: 2.5rem;
                margin-bottom: 8px;
            }
            
            .logo-frame {
                padding: 20px 30px;
            }
            
            .premium-tagline-inside {
                font-size: 0.75rem;
            }
            
            .premium-tagline-outside {
                font-size: 0.875rem;
            }
        }

        @media (max-width: 480px) {
            .logo-text-premium {
                font-size: 2rem;
                margin-bottom: 6px;
            }
            
            .logo-frame {
                padding: 16px 20px;
            }
            
            .premium-tagline-inside {
                font-size: 0.7rem;
            }
            
            .premium-tagline-outside {
                font-size: 0.8rem;
            }
        }

        /* Old styles for backward compatibility - hidden */
        .site-name-badge {
            display: none;
        }

        .site-tagline {
            display: none;
        }

        /* Background Pattern */
        .bg-pattern {
            background-color: #f5f7fa;
            background-image: 
                linear-gradient(rgba(93, 92, 222, 0.07) 1px, transparent 1px),
                linear-gradient(to right, rgba(93, 92, 222, 0.07) 1px, #f5f7fa 1px);
            background-size: 50px 50px;
            position: relative;
        }

        .dark .bg-pattern {
            background-color: #181818;
            background-image: 
                linear-gradient(rgba(93, 92, 222, 0.07) 1px, transparent 1px),
                linear-gradient(to right, rgba(93, 92, 222, 0.07) 1px, #181818 1px);
        }
        
        /* Floating Currency Icons Background */
        .background-icons {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1;
            opacity: 0.07;
            overflow: hidden;
            pointer-events: none;
        }

        .floating-icon {
            position: absolute;
            font-size: 1.5rem;
            color: <?php echo $primaryColor; ?>;
            animation: float 15s linear infinite;
            pointer-events: none;
        }

        .dark .floating-icon {
            color: #7776E7;
        }

        @keyframes float {
            0% {
                transform: translateY(100vh) rotate(0deg);
                opacity: 0;
            }
            10% {
                opacity: 1;
            }
            90% {
                opacity: 1;
            }
            100% {
                transform: translateY(-100px) rotate(360deg);
                opacity: 0;
            }
        }

        .card {
            transition: all 0.3s ease;
        }
        
        .card:hover {
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
        }

        .currency-card {
            border-left: 4px solid <?php echo $primaryColor; ?>;
        }

        .exchange-btn {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, #4c4cbe 100%);
            transition: all 0.3s;
        }
        
        .exchange-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.2);
        }

        .payment-icon {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }

        .status-online {
            background-color: <?php echo $secondaryColor; ?>;
            color: white;
        }
        
        .status-offline {
            background-color: #DC3545;
            color: white;
        }
        
        .status-away {
            background-color: #FFC107;
            color: black;
        }
        
        .status-confirmed {
            background-color: <?php echo $secondaryColor; ?>;
            color: white;
        }
        
        .status-pending {
            background-color: #FFC107;
            color: black;
        }
        
        .status-cancelled {
            background-color: #DC3545;
            color: white;
        }
        
        .status-refunded {
            background-color: #6C757D;
            color: white;
        }

        .reserve-container {
            max-height: 400px;
            overflow-y: auto;
        }

        .testimonial-card {
            border-left: 4px solid #FFC107;
        }

        .star-rating {
            color: #FFC107;
        }

        /* Step indicator styles */
        .step-indicator {
            display: flex;
            justify-content: space-between;
            position: relative;
            margin-bottom: 24px;
        }
        
        .step-indicator::before {
            content: '';
            position: absolute;
            top: 15px;
            left: 0;
            right: 0;
            height: 2px;
            background-color: #e0e0e0;
            z-index: 1;
        }
        
        .step {
            position: relative;
            z-index: 2;
            display: flex;
            flex-direction: column;
            align-items: center;
        }
        
        .step-number {
            width: 30px;
            height: 30px;
            border-radius: 50%;
            background-color: white;
            border: 2px solid #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            margin-bottom: 8px;
            transition: all 0.3s;
        }
        
        .step-title {
            font-size: 14px;
            color: #777;
            transition: all 0.3s;
        }
        
        .step.active .step-number {
            background-color: <?php echo $primaryColor; ?>;
            border-color: <?php echo $primaryColor; ?>;
            color: white;
        }
        
        .step.active .step-title {
            color: <?php echo $primaryColor; ?>;
            font-weight: bold;
        }
        
        .step.completed .step-number {
            background-color: <?php echo $secondaryColor; ?>;
            border-color: <?php echo $secondaryColor; ?>;
            color: white;
        }
        
        .dark .step-number {
            background-color: #333;
            border-color: #555;
        }
        
        .dark .step-indicator::before {
            background-color: #555;
        }

        /* Toast notification */
        .toast {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 9999;
            max-width: 350px;
            overflow: hidden;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-20px);
            transition: all 0.3s ease-in-out;
        }
        
        .toast.show {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        /* Custom select styles */
        .custom-select {
            position: relative;
        }

        .custom-select.open .custom-options {
            display: block;
        }

        .custom-select.open .arrow i {
            transform: rotate(180deg);
        }

        .custom-options {
            max-height: 200px;
            overflow-y: auto;
        }

        .currency-icon-container {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 24px;
            height: 24px;
        }

        /* Animation for the notice bar */
        @keyframes scroll {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }

        .scrolling-text {
            white-space: nowrap;
            animation: scroll 25s linear infinite;
        }

        /* Scrollbar customization */
        ::-webkit-scrollbar {
            width: 8px;
            height: 8px;
        }

        ::-webkit-scrollbar-track {
            background: #f1f1f1;
        }

        .dark ::-webkit-scrollbar-track {
            background: #333;
        }

        ::-webkit-scrollbar-thumb {
            background: <?php echo $primaryColor; ?>;
            border-radius: 5px;
        }

        ::-webkit-scrollbar-thumb:hover {
            background: #4c4cbe;
        }

        /* Mobile menu and dropdown */
        #mobile-menu {
            position: fixed;
            top: 0;
            left: -100%;
            width: 80%;
            max-width: 300px;
            height: 100%;
            z-index: 100;
            background-color: white;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease-in-out;
            padding: 20px;
            overflow-y: auto;
        }
        
        .dark #mobile-menu {
            background-color: #2d3748;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
        }
        
        #mobile-menu.open {
            left: 0;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 99;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s ease-in-out;
        }
        
        .overlay.open {
            opacity: 1;
            visibility: visible;
        }

        /* Table row hover animation */
        tr.animate-row {
            transition: all 0.2s ease;
        }
        
        tr.animate-row:hover {
            background-color: rgba(93, 92, 222, 0.05);
            transform: translateX(2px);
        }
        
        .dark tr.animate-row:hover {
            background-color: rgba(93, 92, 222, 0.15);
        }

        /* Print styles */
        @media print {
            body {
                background: white;
                padding: 0;
                margin: 0;
            }
            
            .receipt-container {
                box-shadow: none;
                page-break-inside: avoid;
            }
            
            .action-buttons, 
            .no-print {
                display: none !important;
            }
        }

        /* Nav tabs styles for track transactions */
        .nav-tab {
            position: relative;
            transition: all 0.3s;
        }

        .nav-tab::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 3px;
            background-color: <?php echo $primaryColor; ?>;
            transition: all 0.3s;
        }

        .nav-tab.active::after, 
        .nav-tab:hover::after {
            width: 100%;
        }

        .nav-tab.active {
            color: <?php echo $primaryColor; ?>;
            font-weight: 600;
        }

        /* Table header styles */
        .table-header {
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, #4c4cbe 100%);
            color: white;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.75rem;
            padding: 0.75rem 1rem;
        }

        .dark .table-header {
            background: linear-gradient(135deg, #4c4cbe 0%, #3c3cae 100%);
        }

        /* Reserve header style */
        .reserve-header {
            background: linear-gradient(135deg, #2563EB 0%, #1D4ED8 100%);
            color: white;
        }

        /* Testimonial header style */
        .testimonial-header {
            background: linear-gradient(135deg, #F59E0B 0%, #D97706 100%);
            color: white;
        }

        /* Exchange rate header style */
        .exchange-rate-header {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }

        /* Track transaction header style */
        .track-header {
            background: linear-gradient(135deg, #6366F1 0%, #4F46E5 100%);
            color: white;
        }

        /* Section backgrounds */
        .section-bg {
            position: relative;
            overflow: hidden;
        }

        .section-bg::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-image: 
                radial-gradient(<?php echo $primaryColor; ?>05 1.5px, transparent 1.5px),
                radial-gradient(<?php echo $primaryColor; ?>05 1.5px, transparent 1.5px);
            background-size: 30px 30px;
            background-position: 0 0, 15px 15px;
            pointer-events: none;
            z-index: 0;
        }

        .dark .section-bg::before {
            background-image: 
                radial-gradient(rgba(93, 92, 222, 0.05) 1.5px, transparent 1.5px),
                radial-gradient(rgba(93, 92, 222, 0.05) 1.5px, transparent 1.5px);
        }

        .section-content {
            position: relative;
            z-index: 1;
        }

        .mobile-menu-toggle {
            display: flex;
            align-items: center;
            justify-content: center;
            width: 40px;
            height: 40px;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            cursor: pointer;
            color: white;
            font-size: 1.25rem;
        }

        /* Bold amount inputs */
        .amount-input {
            font-weight: 700 !important;
            font-size: 1.25rem !important;
        }

        /* Animated borders for containers */
        .animated-border {
            position: relative;
        }

        .animated-border::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            border: 2px solid transparent;
            border-radius: inherit;
            pointer-events: none;
            animation: borderPulse 2s infinite;
        }

        @keyframes borderPulse {
            0% {
                border-color: rgba(93, 92, 222, 0.2);
                box-shadow: 0 0 0 rgba(93, 92, 222, 0.2);
            }
            50% {
                border-color: rgba(93, 92, 222, 0.6);
                box-shadow: 0 0 15px rgba(93, 92, 222, 0.3);
            }
            100% {
                border-color: rgba(93, 92, 222, 0.2);
                box-shadow: 0 0 0 rgba(93, 92, 222, 0.2);
            }
        }

        /* Vertical exchange form layout */
        .exchange-form-vertical {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .exchange-form-section {
            background: rgba(45, 55, 72, 0.05);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid rgba(93, 92, 222, 0.1);
            transition: all 0.3s ease;
            text-align: center;
        }

        .exchange-form-section:hover {
            border-color: <?php echo $primaryColor; ?>;
            box-shadow: 0 0 20px rgba(93, 92, 222, 0.1);
        }

        /* Exchange direction button */
        .exchange-direction-button {
            width: 40px;
            height: 40px;
            background: linear-gradient(135deg, <?php echo $primaryColor; ?> 0%, #4c4cbe 100%);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.2rem;
            cursor: pointer;
            transition: all 0.3s ease;
            align-self: center;
            box-shadow: 0 4px 10px rgba(93, 92, 222, 0.3);
        }

        .exchange-direction-button:hover {
            transform: rotate(180deg);
            box-shadow: 0 8px 15px rgba(93, 92, 222, 0.4);
        }

        /* Exchange rate display */
        .exchange-rate-display {
            text-align: center;
            margin-top: 1rem;
            padding: 0.75rem;
            background-color: rgba(93, 92, 222, 0.1);
            border-radius: 0.5rem;
            font-weight: 600;
            color: <?php echo $primaryColor; ?>;
        }

        .dark .exchange-rate-display {
            background-color: rgba(93, 92, 222, 0.2);
            color: #a5a4ff;
        }
        
        <?php
        // Get custom CSS from settings
        $customCss = getSetting('custom_css', '');
        echo $customCss;
        ?>
    </style>
    
    <!-- Schema.org Structured Data -->
    <?php
    // Generate Schema markup
    if (getSetting('structured_data_enabled', '1') === '1') {
        $schemas = generateSchemaMarkup();
        foreach ($schemas as $schema) {
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
        }
    }
    ?>
</head>
<body class="min-h-screen bg-gray-50 dark:bg-gray-900 text-gray-800 dark:text-gray-200 bg-pattern">
    <!-- Google Tag Manager (noscript) -->
    <?php if (!empty($googleTagManagerId)): ?>
    <noscript><iframe src="https://www.googletagmanager.com/ns.html?id=<?php echo $googleTagManagerId; ?>"
    height="0" width="0" style="display:none;visibility:hidden"></iframe></noscript>
    <?php endif; ?>
    
    <!-- Floating background icons -->
    <div class="background-icons" id="backgroundIcons"></div>
    
    <?php if (getSetting('enable_notification_sound', 'yes') === 'yes'): ?>
    <!-- Notification Sound -->
    <audio id="notification-sound" preload="auto">
        <source src="assets/uploads/sounds/popup.wav" type="audio/wav">
    </audio>
    <?php endif; ?>
    
    <div id="app" class="flex flex-col min-h-screen">
        <!-- Toast Notification -->
        <div id="toast" class="toast bg-white dark:bg-gray-800 rounded-lg shadow-lg overflow-hidden">
            <div class="flex p-4">
                <div id="toast-icon" class="flex-shrink-0 flex items-center justify-center w-12 h-12 text-white rounded-full">
                    <i id="toast-icon-i" class="fas fa-check-circle fa-lg"></i>
                </div>
                <div class="ml-4 flex-grow">
                    <div id="toast-title" class="font-bold"></div>
                    <div id="toast-message" class="text-sm text-gray-600 dark:text-gray-300"></div>
                </div>
                <button onclick="hideToast()" class="flex-shrink-0 ml-2 text-gray-400 hover:text-gray-500">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div id="toast-progress" class="h-1 bg-primary" style="width: 100%;"></div>
        </div>

        <!-- Header -->
        <header class="text-white py-6 px-6 shadow-md" style="background: linear-gradient(135deg, #0e6cad 0%, #1a4a73 100%);">
            <div class="container mx-auto">
                <div class="flex flex-col lg:flex-row justify-between items-center">
                    <!-- Logo Section with proper positioning and hyperlink -->
                    <div class="mb-4 lg:mb-0 flex-shrink-0 w-full lg:w-auto">
                        <?php renderLogo('header'); ?>
                    </div>
                    
                    <!-- Contact Information Section -->
                    <div class="flex flex-col items-center lg:items-end space-y-3 text-sm">
                        <div class="flex flex-wrap items-center justify-center lg:justify-end gap-4">
                            <div class="flex items-center bg-white bg-opacity-10 rounded-lg px-3 py-2">
                                <span class="mr-2">Operator:</span>
                                <span class="status-<?php echo $operatorStatus; ?> px-2 py-1 rounded-full text-xs font-semibold">
                                    <?php echo ucfirst($operatorStatus); ?>
                                </span>
                            </div>
                            
                            <div class="flex items-center bg-white bg-opacity-10 rounded-lg px-3 py-2">
                                <i class="far fa-clock mr-2"></i> 
                                <span><?php echo $workingHours; ?></span>
                            </div>
                        </div>
                        
                        <div class="flex flex-wrap items-center justify-center lg:justify-end gap-3">
                            <a href="tel:<?php echo $contactPhone; ?>" 
                               class="flex items-center bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded-lg transition-colors duration-300 shadow-lg hover:shadow-xl">
                                <i class="fas fa-phone-alt mr-2"></i> 
                                <span class="font-medium"><?php echo $contactPhone; ?></span>
                            </a>
                            
                            <a href="https://wa.me/<?php echo $contactWhatsapp; ?>" 
                               class="flex items-center bg-green-500 hover:bg-green-600 text-white px-3 py-2 rounded-lg transition-all duration-300 shadow-lg hover:shadow-xl hover:scale-105">
                                <i class="fab fa-whatsapp mr-2"></i> 
                                <span class="font-medium">WhatsApp</span>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </header>

        <!-- Navigation with Toggle -->
        <nav class="bg-blue-900 text-white py-3" style="background: linear-gradient(135deg, #094a7a 0%, #0d3456 100%);">
            <div class="container mx-auto px-4">
                <div class="flex justify-between items-center">
                    <!-- Mobile Menu Toggle - Only visible on mobile -->
                    <button id="mobile-menu-btn" class="mobile-menu-toggle md:hidden">
                        <i class="fas fa-bars"></i>
                    </button>
                    
                    <!-- Center-aligned Navigation Links -->
                    <div class="flex-grow flex justify-center space-x-4">
                        <a href="index.php" class="px-3 py-1 <?php echo $currentPage === 'index.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?> rounded flex items-center text-sm md:text-base">
                            <i class="fas fa-exchange-alt mr-1"></i> Exchange
                        </a>
                        <a href="track.php" class="px-3 py-1 <?php echo $currentPage === 'track.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?> rounded flex items-center text-sm md:text-base" id="track-link">
                            <i class="fas fa-search mr-1"></i> Track
                        </a>
                        <a href="blog.php" class="px-3 py-1 <?php echo $currentPage === 'blog.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?> rounded flex items-center text-sm md:text-base">
                            <i class="fas fa-newspaper mr-1"></i> Blog
                        </a>
                        <!-- Additional navigation items for desktop -->
                        <a href="contact.php" class="px-3 py-1 <?php echo $currentPage === 'contact.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?> rounded hidden md:flex items-center text-sm md:text-base">
                            <i class="fas fa-envelope mr-1"></i> Contact
                        </a>
                        <a href="about.php" class="px-3 py-1 <?php echo $currentPage === 'about.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?> rounded hidden md:flex items-center text-sm md:text-base">
                            <i class="fas fa-info-circle mr-1"></i> About
                        </a>
                        <a href="faq.php" class="px-3 py-1 <?php echo $currentPage === 'faq.php' ? 'bg-blue-800' : 'hover:bg-blue-800'; ?> rounded hidden md:flex items-center text-sm md:text-base">
                            <i class="fas fa-question-circle mr-1"></i> FAQ
                        </a>
                    </div>
                    
                    <!-- Right-aligned Toggle Button -->
                    <button id="toggle-theme" class="bg-gray-200 dark:bg-gray-700 text-gray-800 dark:text-white px-3 py-1 rounded-md flex items-center text-sm">
                        <i class="fas fa-moon mr-1 dark:hidden"></i>
                        <i class="fas fa-sun mr-1 hidden dark:block"></i>
                        <span class="dark:hidden">Dark</span>
                        <span class="hidden dark:block">Light</span>
                    </button>
                </div>
            </div>
        </nav>

        <!-- Mobile Menu Overlay -->
        <div id="overlay" class="overlay"></div>
        
        <!-- Mobile Menu -->
        <div id="mobile-menu" class="z-50">
            <div class="flex justify-between items-center mb-6">
                <div class="logo-main-container flex flex-col items-start text-left footer-logo">
                    <div class="logo-frame" style="margin-bottom: 0;">
                        <div class="logo-text-premium">
                            <?php 
                            $words = explode(' ', $siteLogoText);
                            if (count($words) >= 2) {
                                echo '<span class="exchange-text">' . htmlspecialchars($words[0]) . '</span>';
                                echo '<span class="bridge-text">' . htmlspecialchars($words[1]) . '</span>';
                                if (count($words) > 2) {
                                    for ($i = 2; $i < count($words); $i++) {
                                        echo '<span class="exchange-text"> ' . htmlspecialchars($words[$i]) . '</span>';
                                    }
                                }
                            } else {
                                echo '<span class="single-word-text">' . htmlspecialchars($siteLogoText) . '</span>';
                            }
                            ?>
                        </div>
                        <div class="premium-tagline-inside"><?php echo htmlspecialchars($siteTagline); ?></div>
                    </div>
                </div>
                <button id="close-mobile-menu" class="text-gray-500 hover:text-gray-700 dark:text-gray-300 dark:hover:text-white">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
            
            <nav class="mb-6">
                <ul class="space-y-3">
                    <li>
                        <a href="index.php" class="block px-3 py-2 <?php echo $currentPage === 'index.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-exchange-alt mr-2"></i> Exchange
                        </a>
                    </li>
                    <li>
                        <a href="track.php" class="block px-3 py-2 <?php echo $currentPage === 'track.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center track-link-mobile">
                            <i class="fas fa-search mr-2"></i> Track
                        </a>
                    </li>
                    <li>
                        <a href="blog.php" class="block px-3 py-2 <?php echo $currentPage === 'blog.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-newspaper mr-2"></i> Blog
                        </a>
                    </li>
                    <li>
                        <a href="contact.php" class="block px-3 py-2 <?php echo $currentPage === 'contact.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-envelope mr-2"></i> Contact
                        </a>
                    </li>
                    <li>
                        <a href="about.php" class="block px-3 py-2 <?php echo $currentPage === 'about.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-info-circle mr-2"></i> About Us
                        </a>
                    </li>
                    <li>
                        <a href="faq.php" class="block px-3 py-2 <?php echo $currentPage === 'faq.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-question-circle mr-2"></i> FAQ
                        </a>
                    </li>
                    <li>
                        <a href="terms.php" class="block px-3 py-2 <?php echo $currentPage === 'terms.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-file-contract mr-2"></i> Terms & Conditions
                        </a>
                    </li>
                    <li>
                        <a href="privacy.php" class="block px-3 py-2 <?php echo $currentPage === 'privacy.php' ? 'bg-primary text-white' : 'hover:bg-gray-100 dark:hover:bg-gray-700'; ?> rounded flex items-center">
                            <i class="fas fa-shield-alt mr-2"></i> Privacy Policy
                        </a>
                    </li>
                </ul>
            </nav>
            
            <div class="border-t border-gray-200 dark:border-gray-700 pt-4">
                <div class="flex items-center mb-4">
                    <div class="w-8 h-8 bg-green-500 text-white rounded-full flex items-center justify-center mr-3">
                        <i class="fas fa-headset"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Support Hotline</div>
                        <div class="font-semibold"><a href="tel:<?php echo $contactPhone; ?>"><?php echo $contactPhone; ?></a></div>
                    </div>
                </div>
                
                <div class="flex items-center">
                    <div class="w-8 h-8 bg-blue-500 text-white rounded-full flex items-center justify-center mr-3">
                        <i class="far fa-clock"></i>
                    </div>
                    <div>
                        <div class="text-xs text-gray-500 dark:text-gray-400">Working Hours</div>
                        <div class="font-semibold"><?php echo $workingHours; ?></div>
                    </div>
                </div>
            </div>
        </div>

        <?php
        // Get scrolling notice
        $scrollingNotices = getActiveNotices('scrolling');
        if (!empty($scrollingNotices)): 
            $notice = $scrollingNotices[0]; // Take the first active notice
        ?>
        <!-- Notice Bar -->
        <div class="bg-yellow-50 dark:bg-yellow-900 overflow-hidden py-2 border-y border-yellow-200 dark:border-yellow-800">
            <div class="scrolling-text text-yellow-700 dark:text-yellow-300 text-sm">
                <?php echo $notice['content']; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <?php
        // Get popup notice
        $popupNotices = getActiveNotices('popup');
        if (!empty($popupNotices)): 
            $popup = $popupNotices[0]; // Take the first active popup notice
        ?>
        <!-- Popup Notice (hidden by default, shown with JavaScript) -->
        <div id="popup-notice" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50 hidden">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-md w-full mx-4 overflow-hidden">
                <div class="bg-primary text-white px-4 py-3 flex justify-between items-center">
                    <h3 class="font-bold"><?php echo $popup['title']; ?></h3>
                    <button id="close-popup" class="text-white hover:text-gray-200">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <div class="p-4">
                    <div class="mb-4 prose dark:prose-invert prose-sm max-w-none">
                        <?php echo $popup['content']; ?>
                    </div>
                    <div class="flex justify-end">
                        <button id="dismiss-popup" class="bg-primary hover:bg-primary-dark text-white px-4 py-2 rounded">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Breadcrumbs (if enabled) -->
        <?php if (getSetting('breadcrumbs_enabled', '1') === '1' && function_exists('generateBreadcrumbs')): ?>
        <nav class="bg-gray-100 dark:bg-gray-800 py-2 px-6">
            <div class="container mx-auto">
                <?php echo generateBreadcrumbs(); ?>
            </div>
        </nav>
        <?php endif; ?>