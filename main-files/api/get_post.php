<?php
/**
 * ExchangeBridge - Get Blog Post API
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

// Include configuration files
require_once '../../includes/config.php';
require_once '../../includes/db.php';
require_once '../../includes/functions.php';
require_once '../../includes/auth.php';

// Set content type to JSON
header('Content-Type: application/json');

// Check if user is logged in
if (!Auth::isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit;
}

$db = Database::getInstance();

if (isset($_GET['id'])) {
    try {
        $post_id = (int)$_GET['id'];
        
        // Get blog post
        $post = $db->getRow("SELECT * FROM blog_posts WHERE id = ?", [$post_id]);
        
        if ($post) {
            echo json_encode([
                'success' => true,
                'post' => [
                    'id' => $post['id'],
                    'title' => $post['title'],
                    'slug' => $post['slug'],
                    'excerpt' => $post['excerpt'],
                    'content' => $post['content'],
                    'featured_image' => $post['featured_image'],
                    'status' => $post['status'],
                    'meta_title' => $post['meta_title'],
                    'meta_description' => $post['meta_description'],
                    'meta_keywords' => $post['meta_keywords'],
                    'created_at' => $post['created_at'],
                    'updated_at' => $post['updated_at']
                ]
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Blog post not found']);
        }

    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Post ID is required']);
}
?>