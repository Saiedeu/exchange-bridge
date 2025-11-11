<?php
/**
 * ExchangeBridge - Popup Notice System
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */

if (!defined('ALLOW_ACCESS')) {
    exit('Direct access not allowed');
}

function renderPopupNotices() {
    try {
        $db = Database::getInstance();
        $notices = $db->getRows("SELECT * FROM notices WHERE type = 'popup' AND status = 'active' ORDER BY created_at DESC");
        
        if (empty($notices)) {
            return '';
        }
        
        // Fix image paths function
        $fixImagePath = function($imagePath) {
            if (empty($imagePath)) return '';
            
            // Clean path
            $imagePath = str_replace(['../'], '', $imagePath);
            
            // Ensure proper format
            if (strpos($imagePath, 'assets/uploads/notices/') === false) {
                if (strpos($imagePath, 'assets/uploads/') === false) {
                    $imagePath = 'assets/uploads/notices/' . $imagePath;
                }
            }
            
            return SITE_URL . '/' . $imagePath;
        };
        
        $noticesJson = [];
        foreach ($notices as $notice) {
            $imagePath = '';
            if (!empty($notice['image_path'])) {
                $imagePath = $fixImagePath($notice['image_path']);
            }
            
            $noticesJson[] = [
                'id' => $notice['id'],
                'title' => htmlspecialchars($notice['title']),
                'content' => $notice['content'], // Already processed by TinyMCE
                'image_path' => $imagePath
            ];
        }
        
        return json_encode($noticesJson);
        
    } catch (Exception $e) {
        error_log('Error in renderPopupNotices: ' . $e->getMessage());
        return '[]';
    }
}
?>

<style>
/* Popup Notice Styles */
.popup-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.6);
    z-index: 9999;
    display: flex;
    justify-content: center;
    align-items: center;
    backdrop-filter: blur(4px);
    animation: fadeIn 0.3s ease-out;
}

.popup-content {
    background: white;
    border-radius: 12px;
    padding: 2rem;
    max-width: 90%;
    max-height: 80vh;
    overflow-y: auto;
    box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3);
    position: relative;
    animation: slideIn 0.3s ease-out;
    font-family: 'Poppins', Arial, sans-serif;
    line-height: 1.6;
}

.popup-close {
    position: absolute;
    top: 1rem;
    right: 1rem;
    background: #f8f9fa;
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    cursor: pointer;
    font-size: 18px;
    color: #6c757d;
    transition: all 0.2s ease;
    z-index: 10000;
}

.popup-close:hover {
    background: #e9ecef;
    color: #495057;
    transform: scale(1.1);
}

.popup-title {
    font-size: 1.5rem;
    font-weight: 600;
    margin-bottom: 1rem;
    color: #333;
    padding-right: 3rem;
}

.popup-media {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 1rem 0;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
}

.popup-body {
    color: #555;
    font-size: 14px;
}

.popup-body img {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 10px 0;
}

.popup-body video {
    max-width: 100%;
    height: auto;
    border-radius: 8px;
    margin: 10px 0;
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideIn {
    from { 
        opacity: 0;
        transform: scale(0.8) translateY(-20px);
    }
    to { 
        opacity: 1;
        transform: scale(1) translateY(0);
    }
}

@keyframes fadeOut {
    from { opacity: 1; }
    to { opacity: 0; }
}

/* Dark mode support */
@media (prefers-color-scheme: dark) {
    .popup-content {
        background-color: #2d3748;
        color: #f7fafc;
    }
    
    .popup-title {
        color: #f7fafc;
    }
    
    .popup-body {
        color: #e2e8f0;
    }
    
    .popup-close {
        background: #4a5568;
        color: #e2e8f0;
    }
    
    .popup-close:hover {
        background: #2d3748;
        color: #f7fafc;
    }
}

/* Mobile responsive */
@media (max-width: 768px) {
    .popup-content {
        margin: 1rem;
        max-width: calc(100% - 2rem);
        padding: 1.5rem;
    }
    
    .popup-title {
        font-size: 1.25rem;
    }
}
</style>

<script>
// Popup notice system
(function() {
    const notices = <?php echo renderPopupNotices(); ?>;
    let currentNoticeIndex = 0;
    let shownNotices = [];

    // Load shown notices from localStorage
    function loadShownNotices() {
        try {
            const stored = localStorage.getItem('shownPopupNotices');
            return stored ? JSON.parse(stored) : [];
        } catch (e) {
            return [];
        }
    }

    // Save shown notices to localStorage
    function saveShownNotices() {
        localStorage.setItem('shownPopupNotices', JSON.stringify(shownNotices));
    }

    // Show popup notice
    function showNotice(notice) {
        const overlay = document.createElement('div');
        overlay.className = 'popup-overlay';
        overlay.onclick = (e) => {
            if (e.target === overlay) {
                closePopup(overlay);
            }
        };

        const content = document.createElement('div');
        content.className = 'popup-content';

        const closeBtn = document.createElement('button');
        closeBtn.className = 'popup-close';
        closeBtn.innerHTML = '×';
        closeBtn.onclick = () => closePopup(overlay);

        const title = document.createElement('div');
        title.className = 'popup-title';
        title.textContent = notice.title || 'Notice';

        const body = document.createElement('div');
        body.className = 'popup-body';
        body.innerHTML = notice.content || '';

        // Add media if exists
        if (notice.image_path) {
            const extension = notice.image_path.split('.').pop().toLowerCase();
            if (['mp4', 'webm', 'ogg'].includes(extension)) {
                const video = document.createElement('video');
                video.className = 'popup-media';
                video.controls = true;
                video.src = notice.image_path;
                video.onerror = () => {
                    console.error('Failed to load video:', notice.image_path);
                    video.style.display = 'none';
                };
                body.insertBefore(video, body.firstChild);
            } else {
                const img = document.createElement('img');
                img.className = 'popup-media';
                img.src = notice.image_path;
                img.alt = 'Notice Image';
                img.onerror = () => {
                    console.error('Failed to load image:', notice.image_path);
                    img.style.display = 'none';
                };
                body.insertBefore(img, body.firstChild);
            }
        }

        content.appendChild(closeBtn);
        content.appendChild(title);
        content.appendChild(body);
        overlay.appendChild(content);
        document.body.appendChild(overlay);

        // Mark as shown
        shownNotices.push(notice.id);
        saveShownNotices();
    }

    // Close popup
    function closePopup(overlay) {
        overlay.style.animation = 'fadeOut 0.3s ease-out';
        setTimeout(() => {
            if (overlay.parentNode) {
                overlay.parentNode.removeChild(overlay);
            }
            showNextNotice();
        }, 300);
    }

    // Show next notice
    function showNextNotice() {
        if (currentNoticeIndex < notices.length) {
            const notice = notices[currentNoticeIndex];
            currentNoticeIndex++;
            
            if (!shownNotices.includes(notice.id)) {
                setTimeout(() => showNotice(notice), 500);
            } else {
                showNextNotice();
            }
        }
    }

    // Initialize
    if (notices.length > 0) {
        shownNotices = loadShownNotices();
        
        // Clear daily
        const lastClear = localStorage.getItem('lastPopupClear');
        const today = new Date().toDateString();
        if (lastClear !== today) {
            shownNotices = [];
            saveShownNotices();
            localStorage.setItem('lastPopupClear', today);
        }

        // Start showing notices after page load
        document.addEventListener('DOMContentLoaded', function() {
            setTimeout(showNextNotice, 2000);
        });
    }
})();
</script>