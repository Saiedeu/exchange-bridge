<?php
/**
 * ExchangeBridge - Pages Table
 *
 * package     ExchangeBridge
 * author      Saieed Rahman
 * copyright   SidMan Solution 2025
 * version     1.0.0
 */
 
require_once '../config/config.php';
require_once 'db.php';

$db = Database::getInstance();

// Create pages table if it doesn't exist
$sql = "CREATE TABLE IF NOT EXISTS pages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL UNIQUE,
    content LONGTEXT,
    meta_title VARCHAR(255),
    meta_description TEXT,
    meta_keywords TEXT,
    status ENUM('active', 'inactive') DEFAULT 'active',
    show_in_menu TINYINT(1) DEFAULT 0,
    menu_order INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_status (status),
    INDEX idx_menu (show_in_menu, menu_order)
)";

try {
    $db->query($sql);
    echo "Pages table created successfully!";
} catch (Exception $e) {
    echo "Error creating pages table: " . $e->getMessage();
}
?>