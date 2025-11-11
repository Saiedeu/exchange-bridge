<?php
/**
 * ExchangeBridge - SEO Functions File
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */


if (!defined('ALLOW_ACCESS')) {
    die('Direct access not permitted');
}

/**
 * Calculate SEO score based on current settings
 */
function calculateSEOScore() {
    $score = 0;
    $maxScore = 100;
    
    // Meta title (20 points)
    $metaTitle = getSetting('seo_meta_title', '');
    if (!empty($metaTitle)) {
        $score += 15;
        $titleLength = strlen($metaTitle);
        if ($titleLength >= 30 && $titleLength <= 60) {
            $score += 5; // Bonus for optimal length
        }
    }
    
    // Meta description (20 points)
    $metaDescription = getSetting('seo_meta_description', '');
    if (!empty($metaDescription)) {
        $score += 15;
        $descLength = strlen($metaDescription);
        if ($descLength >= 150 && $descLength <= 160) {
            $score += 5; // Bonus for optimal length
        }
    }
    
    // Favicon (5 points)
    if (!empty(getSetting('site_favicon', ''))) {
        $score += 5;
    }
    
    // Logo (5 points)
    if (!empty(getSetting('site_logo', ''))) {
        $score += 5;
    }
    
    // Open Graph (10 points)
    if (getSetting('open_graph_enabled', '1') === '1') {
        $score += 8;
        if (!empty(getSetting('seo_og_image', '')) || !empty(getSetting('default_og_image', ''))) {
            $score += 2; // Bonus for OG image
        }
    }
    
    // Schema markup (15 points)
    if (getSetting('structured_data_enabled', '1') === '1') {
        $score += 10;
        if (getSetting('schema_organization', '1') === '1') {
            $score += 2;
        }
        if (getSetting('schema_website', '1') === '1') {
            $score += 2;
        }
        if (getSetting('schema_breadcrumbs', '1') === '1') {
            $score += 1;
        }
    }
    
    // Sitemap (10 points)
    if (getSetting('sitemap_enabled', '1') === '1') {
        $score += 8;
        // Check if sitemap actually exists
        if (file_exists(getRootPath() . '/sitemap.xml')) {
            $score += 2;
        }
    }
    
    // Robots.txt (5 points)
    if (getSetting('robots_txt_enabled', '1') === '1') {
        $score += 3;
        // Check if robots.txt actually exists
        if (file_exists(getRootPath() . '/robots.txt')) {
            $score += 2;
        }
    }
    
    // Analytics setup (5 points)
    if (!empty(getSetting('google_analytics_id', '')) || !empty(getSetting('google_tag_manager_id', ''))) {
        $score += 5;
    }
    
    // Site verification (5 points)
    if (!empty(getSetting('google_site_verification', '')) || 
        !empty(getSetting('bing_site_verification', '')) || 
        !empty(getSetting('yandex_site_verification', ''))) {
        $score += 5;
    }
    
    // Business info for schema (5 points)
    if (!empty(getSetting('contact_email', '')) && !empty(getSetting('address_city', ''))) {
        $score += 5;
    }
    
    return min($score, $maxScore);
}

/**
 * Generate comprehensive schema.org JSON-LD markup
 */
function generateSchemaMarkup() {
    $schemas = [];
    
    // Organization Schema
    if (getSetting('schema_organization', '1') === '1') {
        $organizationSchema = [
            "@context" => "https://schema.org",
            "@type" => getSetting('business_type', 'FinancialService'),
            "name" => getSetting('site_logo_text', 'Exchange Bridge'),
            "description" => getSetting('seo_meta_description', ''),
            "url" => getSetting('site_url', 'https://yourdomain.com'),
            "logo" => getSetting('site_logo', '') ? getSetting('site_url', '') . '/' . getSetting('site_logo', '') : '',
            "contactPoint" => [
                "@type" => "ContactPoint",
                "telephone" => getSetting('contact_phone', ''),
                "contactType" => "Customer Service",
                "email" => getSetting('contact_email', '')
            ],
            "address" => [
                "@type" => "PostalAddress",
                "streetAddress" => getSetting('address_street', ''),
                "addressLocality" => getSetting('address_city', ''),
                "addressRegion" => getSetting('address_state', ''),
                "postalCode" => getSetting('address_postal', ''),
                "addressCountry" => getSetting('address_country', '')
            ],
            "sameAs" => array_filter([
                getSetting('social_facebook', ''),
                getSetting('social_twitter', ''),
                getSetting('social_instagram', ''),
                getSetting('social_linkedin', '')
            ])
        ];
        
        // Remove empty fields
        $organizationSchema = array_filter($organizationSchema, function($value) {
            return !empty($value);
        });
        
        $schemas[] = $organizationSchema;
    }
    
    // Website Schema
    if (getSetting('schema_website', '1') === '1') {
        $websiteSchema = [
            "@context" => "https://schema.org",
            "@type" => "WebSite",
            "name" => getSetting('site_logo_text', 'Exchange Bridge'),
            "description" => getSetting('seo_meta_description', ''),
            "url" => getSetting('site_url', 'https://yourdomain.com'),
            "potentialAction" => [
                "@type" => "SearchAction",
                "target" => getSetting('site_url', 'https://yourdomain.com') . "/search?q={search_term_string}",
                "query-input" => "required name=search_term_string"
            ]
        ];
        
        $schemas[] = $websiteSchema;
    }
    
    return $schemas;
}

/**
 * Generate Open Graph meta tags
 */
function generateOpenGraphTags() {
    if (getSetting('open_graph_enabled', '1') !== '1') {
        return '';
    }
    
    $tags = '';
    $baseUrl = getSetting('site_url', 'https://yourdomain.com');
    
    // Basic OG tags
    $ogTitle = getSetting('seo_og_title', '') ?: getSetting('seo_meta_title', '');
    $ogDescription = getSetting('seo_og_description', '') ?: getSetting('seo_meta_description', '');
    $ogImage = getSetting('seo_og_image', '') ?: getSetting('default_og_image', '');
    
    if ($ogImage && !filter_var($ogImage, FILTER_VALIDATE_URL)) {
        $ogImage = $baseUrl . '/' . ltrim($ogImage, '/');
    }
    
    $tags .= '<meta property="og:title" content="' . htmlspecialchars($ogTitle) . '">' . "\n";
    $tags .= '<meta property="og:description" content="' . htmlspecialchars($ogDescription) . '">' . "\n";
    $tags .= '<meta property="og:type" content="website">' . "\n";
    $tags .= '<meta property="og:url" content="' . $baseUrl . $_SERVER['REQUEST_URI'] . '">' . "\n";
    $tags .= '<meta property="og:site_name" content="' . htmlspecialchars(getSetting('site_logo_text', '')) . '">' . "\n";
    
    if ($ogImage) {
        $tags .= '<meta property="og:image" content="' . htmlspecialchars($ogImage) . '">' . "\n";
        $tags .= '<meta property="og:image:width" content="1200">' . "\n";
        $tags .= '<meta property="og:image:height" content="630">' . "\n";
    }
    
    return $tags;
}

/**
 * Generate Twitter Card meta tags
 */
function generateTwitterCardTags() {
    if (getSetting('twitter_cards_enabled', '1') !== '1') {
        return '';
    }
    
    $tags = '';
    $twitterTitle = getSetting('seo_twitter_title', '') ?: getSetting('seo_meta_title', '');
    $twitterDescription = getSetting('seo_twitter_description', '') ?: getSetting('seo_meta_description', '');
    $twitterImage = getSetting('seo_og_image', '') ?: getSetting('default_og_image', '');
    
    $tags .= '<meta name="twitter:card" content="summary_large_image">' . "\n";
    $tags .= '<meta name="twitter:title" content="' . htmlspecialchars($twitterTitle) . '">' . "\n";
    $tags .= '<meta name="twitter:description" content="' . htmlspecialchars($twitterDescription) . '">' . "\n";
    
    if ($twitterImage) {
        $baseUrl = getSetting('site_url', 'https://yourdomain.com');
        if (!filter_var($twitterImage, FILTER_VALIDATE_URL)) {
            $twitterImage = $baseUrl . '/' . ltrim($twitterImage, '/');
        }
        $tags .= '<meta name="twitter:image" content="' . htmlspecialchars($twitterImage) . '">' . "\n";
    }
    
    return $tags;
}

/**
 * Generate canonical URL
 */
function generateCanonicalUrl() {
    $canonicalUrl = getSetting('seo_canonical_url', '');
    if (empty($canonicalUrl)) {
        $baseUrl = getSetting('site_url', 'https://yourdomain.com');
        $canonicalUrl = $baseUrl . $_SERVER['REQUEST_URI'];
    }
    
    return '<link rel="canonical" href="' . htmlspecialchars($canonicalUrl) . '">' . "\n";
}

/**
 * Generate favicon links
 */
function generateFaviconLinks() {
    $favicon = getSetting('site_favicon', '');
    if (empty($favicon)) {
        return '';
    }
    
    $baseUrl = getSetting('site_url', 'https://yourdomain.com');
    $faviconUrl = $baseUrl . '/' . ltrim($favicon, '/');
    
    $links = '';
    $links .= '<link rel="icon" type="image/x-icon" href="' . $faviconUrl . '">' . "\n";
    $links .= '<link rel="shortcut icon" type="image/x-icon" href="' . $faviconUrl . '">' . "\n";
    
    return $links;
}

/**
 * Generate breadcrumb schema
 */
function generateBreadcrumbSchema($breadcrumbs) {
    if (getSetting('schema_breadcrumbs', '1') !== '1' || empty($breadcrumbs)) {
        return '';
    }
    
    $baseUrl = getSetting('site_url', 'https://yourdomain.com');
    $breadcrumbList = [
        "@context" => "https://schema.org",
        "@type" => "BreadcrumbList",
        "itemListElement" => []
    ];
    
    foreach ($breadcrumbs as $position => $breadcrumb) {
        $breadcrumbList["itemListElement"][] = [
            "@type" => "ListItem",
            "position" => $position + 1,
            "name" => $breadcrumb['name'],
            "item" => $baseUrl . $breadcrumb['url']
        ];
    }
    
    return '<script type="application/ld+json">' . json_encode($breadcrumbList, JSON_UNESCAPED_SLASHES) . '</script>' . "\n";
}

/**
 * Generate all SEO meta tags for a page
 */
function generateSEOTags($pageTitle = '', $pageDescription = '', $pageImage = '') {
    $title = $pageTitle ?: getSetting('seo_meta_title', '');
    $description = $pageDescription ?: getSetting('seo_meta_description', '');
    $image = $pageImage ?: getSetting('default_og_image', '');
    
    $tags = '';
    
    // Basic meta tags
    $tags .= '<meta name="description" content="' . htmlspecialchars($description) . '">' . "\n";
    $tags .= '<meta name="keywords" content="' . htmlspecialchars(getSetting('seo_meta_keywords', '')) . '">' . "\n";
    $tags .= '<meta name="robots" content="' . htmlspecialchars(getSetting('seo_robots', 'index,follow')) . '">' . "\n";
    $tags .= '<meta name="author" content="' . htmlspecialchars(getSetting('site_author', '')) . '">' . "\n";
    
    // Language and region
    $tags .= '<meta name="language" content="' . htmlspecialchars(getSetting('site_language', 'en')) . '">' . "\n";
    
    // Verification meta tags
    if (!empty(getSetting('google_site_verification', ''))) {
        $tags .= '<meta name="google-site-verification" content="' . htmlspecialchars(getSetting('google_site_verification', '')) . '">' . "\n";
    }
    if (!empty(getSetting('bing_site_verification', ''))) {
        $tags .= '<meta name="msvalidate.01" content="' . htmlspecialchars(getSetting('bing_site_verification', '')) . '">' . "\n";
    }
    if (!empty(getSetting('yandex_site_verification', ''))) {
        $tags .= '<meta name="yandex-verification" content="' . htmlspecialchars(getSetting('yandex_site_verification', '')) . '">' . "\n";
    }
    
    // Generate canonical URL
    $tags .= generateCanonicalUrl();
    
    // Generate favicon links
    $tags .= generateFaviconLinks();
    
    // Generate Open Graph tags
    $tags .= generateOpenGraphTags();
    
    // Generate Twitter Card tags
    $tags .= generateTwitterCardTags();
    
    return $tags;
}

/**
 * Generate structured data scripts
 */
function generateStructuredDataScripts() {
    if (getSetting('structured_data_enabled', '1') !== '1') {
        return '';
    }
    
    $schemas = generateSchemaMarkup();
    $scripts = '';
    
    foreach ($schemas as $schema) {
        $scripts .= '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>' . "\n";
    }
    
    return $scripts;
}

/**
 * Generate analytics tracking codes
 */
function generateAnalyticsCode() {
    $code = '';
    
    // Google Analytics
    $gaId = getSetting('google_analytics_id', '');
    if (!empty($gaId)) {
        if (strpos($gaId, 'G-') === 0) {
            // Google Analytics 4
            $code .= "<!-- Google Analytics 4 -->\n";
            $code .= "<script async src=\"https://www.googletagmanager.com/gtag/js?id={$gaId}\"></script>\n";
            $code .= "<script>\n";
            $code .= "window.dataLayer = window.dataLayer || [];\n";
            $code .= "function gtag(){dataLayer.push(arguments);}\n";
            $code .= "gtag('js', new Date());\n";
            $code .= "gtag('config', '{$gaId}');\n";
            $code .= "</script>\n";
        } else {
            // Universal Analytics (legacy)
            $code .= "<!-- Universal Analytics -->\n";
            $code .= "<script>\n";
            $code .= "(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){\n";
            $code .= "(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),\n";
            $code .= "m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)\n";
            $code .= "})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');\n";
            $code .= "ga('create', '{$gaId}', 'auto');\n";
            $code .= "ga('send', 'pageview');\n";
            $code .= "</script>\n";
        }
    }
    
    // Google Tag Manager
    $gtmId = getSetting('google_tag_manager_id', '');
    if (!empty($gtmId)) {
        $code .= "<!-- Google Tag Manager -->\n";
        $code .= "<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\n";
        $code .= "new Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\n";
        $code .= "j=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n";
        $code .= "'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n";
        $code .= "})(window,document,'script','dataLayer','{$gtmId}');</script>\n";
    }
    
    // Facebook Pixel
    $fbPixelId = getSetting('facebook_pixel_id', '');
    if (!empty($fbPixelId)) {
        $code .= "<!-- Facebook Pixel -->\n";
        $code .= "<script>\n";
        $code .= "!function(f,b,e,v,n,t,s)\n";
        $code .= "{if(f.fbq)return;n=f.fbq=function(){n.callMethod?\n";
        $code .= "n.callMethod.apply(n,arguments):n.queue.push(arguments)};\n";
        $code .= "if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';\n";
        $code .= "n.queue=[];t=b.createElement(e);t.async=!0;\n";
        $code .= "t.src=v;s=b.getElementsByTagName(e)[0];\n";
        $code .= "s.parentNode.insertBefore(t,s)}(window, document,'script',\n";
        $code .= "'https://connect.facebook.net/en_US/fbevents.js');\n";
        $code .= "fbq('init', '{$fbPixelId}');\n";
        $code .= "fbq('track', 'PageView');\n";
        $code .= "</script>\n";
        $code .= "<noscript><img height=\"1\" width=\"1\" style=\"display:none\"\n";
        $code .= "src=\"https://www.facebook.com/tr?id={$fbPixelId}&ev=PageView&noscript=1\"\n";
        $code .= "/></noscript>\n";
    }
    
    return $code;
}

/**
 * Get root path for file operations
 */
function getRootPath() {
    // Multiple methods to determine the root path
    $possiblePaths = [
        $_SERVER['DOCUMENT_ROOT'],
        dirname(dirname(__FILE__)),
        realpath(dirname(__FILE__) . '/../'),
    ];
    
    foreach ($possiblePaths as $path) {
        if (is_dir($path) && is_writable($path)) {
            return $path;
        }
    }
    
    // Fallback
    return realpath(dirname(__FILE__) . '/../');
}
?>