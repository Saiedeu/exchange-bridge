<?php
/**
 * ExchangeBridge - Blog Function Core File 
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

// Function to process blog content for frontend display
function processBlogContent($content) {
    // Ensure all image paths are absolute URLs
    $pattern = '/<img([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
    
    $content = preg_replace_callback($pattern, function($matches) {
        $beforeSrc = $matches[1];
        $srcValue = $matches[2];
        $afterSrc = $matches[3];
        
        // If the src already contains the site URL, keep it as is
        if (strpos($srcValue, SITE_URL) !== false) {
            return $matches[0];
        }
        
        // If it's a relative path starting with assets/, make it absolute
        if (strpos($srcValue, 'assets/') === 0) {
            $srcValue = SITE_URL . '/' . $srcValue;
        }
        // If it's a path starting with /assets/, make it absolute
        elseif (strpos($srcValue, '/assets/') === 0) {
            $srcValue = SITE_URL . $srcValue;
        }
        // If it starts with ../ (relative path), resolve it
        elseif (strpos($srcValue, '../') === 0) {
            // Remove ../ and make it absolute
            $cleanPath = str_replace('../', '', $srcValue);
            $srcValue = SITE_URL . '/' . $cleanPath;
        }
        // If it's just a filename, assume it's in the blog uploads directory
        elseif (preg_match('/^[^\/]*\.(jpg|jpeg|png|gif|webp|mp4|webm|ogg)$/i', $srcValue)) {
            $srcValue = SITE_URL . '/assets/uploads/blog/' . $srcValue;
        }
        
        return '<img' . $beforeSrc . 'src="' . $srcValue . '"' . $afterSrc . ' loading="lazy">';
    }, $content);
    
    // Also process video tags
    $videoPattern = '/<video([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
    $content = preg_replace_callback($videoPattern, function($matches) {
        $beforeSrc = $matches[1];
        $srcValue = $matches[2];
        $afterSrc = $matches[3];
        
        if (strpos($srcValue, SITE_URL) === false) {
            if (strpos($srcValue, 'assets/') === 0) {
                $srcValue = SITE_URL . '/' . $srcValue;
            } elseif (strpos($srcValue, '/assets/') === 0) {
                $srcValue = SITE_URL . $srcValue;
            } elseif (strpos($srcValue, '../') === 0) {
                $cleanPath = str_replace('../', '', $srcValue);
                $srcValue = SITE_URL . '/' . $cleanPath;
            } elseif (preg_match('/^[^\/]*\.(mp4|webm|ogg)$/i', $srcValue)) {
                $srcValue = SITE_URL . '/assets/uploads/blog/' . $srcValue;
            }
        }
        
        return '<video' . $beforeSrc . 'src="' . $srcValue . '"' . $afterSrc . '>';
    }, $content);
    
    // Process source tags within video elements
    $sourcePattern = '/<source([^>]*?)src=["\']([^"\']*?)["\']([^>]*?)>/i';
    $content = preg_replace_callback($sourcePattern, function($matches) {
        $beforeSrc = $matches[1];
        $srcValue = $matches[2];
        $afterSrc = $matches[3];
        
        if (strpos($srcValue, SITE_URL) === false) {
            if (strpos($srcValue, 'assets/') === 0) {
                $srcValue = SITE_URL . '/' . $srcValue;
            } elseif (strpos($srcValue, '/assets/') === 0) {
                $srcValue = SITE_URL . $srcValue;
            } elseif (strpos($srcValue, '../') === 0) {
                $cleanPath = str_replace('../', '', $srcValue);
                $srcValue = SITE_URL . '/' . $cleanPath;
            } elseif (preg_match('/^[^\/]*\.(mp4|webm|ogg)$/i', $srcValue)) {
                $srcValue = SITE_URL . '/assets/uploads/blog/' . $srcValue;
            }
        }
        
        return '<source' . $beforeSrc . 'src="' . $srcValue . '"' . $afterSrc . '>';
    }, $content);
    
    return $content;
}

// Function to get featured image URL
function getBlogFeaturedImageUrl($imageName) {
    if (empty($imageName)) {
        return '';
    }
    
    // If it's already a full URL, return as is
    if (strpos($imageName, 'http') === 0) {
        return $imageName;
    }
    
    return SITE_URL . '/assets/uploads/blog/' . $imageName;
}
?>