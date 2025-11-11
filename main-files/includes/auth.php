<?php
/**
 * ExchangeBridge - User Authentication Core File 
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

class Auth {
    // Verify user login
    public static function login($username, $password) {
        $db = Database::getInstance();
        
        // Get user by username or email
        $user = $db->getRow(
            "SELECT * FROM users WHERE (username = ? OR email = ?) AND status = 'active'",
            [$username, $username]
        );
        
        if (!$user) {
            return false;
        }
        
        // Verify password
        if (password_verify($password, $user['password'])) {
            // Update last login time
            $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
            
            // Set session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['logged_in'] = true;
            
            return true;
        }
        
        return false;
    }
    
    // Logout user
    public static function logout() {
        // Unset all session variables
        $_SESSION = [];
        
        // Destroy the session
        session_destroy();
        
        // Regenerate session ID
        session_start();
        session_regenerate_id(true);
        
        return true;
    }
    
    // Check if user is logged in
    public static function isLoggedIn() {
        return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
    }
    
    // Get current user
    public static function getUser() {
        if (!self::isLoggedIn()) {
            return null;
        }
        
        $db = Database::getInstance();
        return $db->getRow("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
    }
    
    // Check if user has admin role
    public static function isAdmin() {
        return self::isLoggedIn() && $_SESSION['role'] === 'admin';
    }
    
    // Check if user has manager role
    public static function isManager() {
        return self::isLoggedIn() && ($_SESSION['role'] === 'admin' || $_SESSION['role'] === 'manager');
    }
    
    // Change user password
    public static function changePassword($userId, $currentPassword, $newPassword) {
        $db = Database::getInstance();
        
        // Get user
        $user = $db->getRow("SELECT * FROM users WHERE id = ?", [$userId]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Verify current password
        if (!password_verify($currentPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $result = $db->update('users', ['password' => $hashedPassword], 'id = ?', [$userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Password updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update password'];
        }
    }
    
    // Reset password
    public static function resetPassword($email) {
        $db = Database::getInstance();
        
        // Get user by email
        $user = $db->getRow("SELECT * FROM users WHERE email = ? AND status = 'active'", [$email]);
        
        if (!$user) {
            return ['success' => false, 'message' => 'User not found'];
        }
        
        // Generate a random password
        $newPassword = bin2hex(random_bytes(4));
        
        // Hash new password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        // Update password
        $result = $db->update('users', ['password' => $hashedPassword], 'id = ?', [$user['id']]);
        
        if ($result) {
            return [
                'success' => true,
                'message' => 'Password reset successfully',
                'password' => $newPassword
            ];
        } else {
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }
    
    // Create user
    public static function createUser($username, $email, $password, $role = 'editor') {
        $db = Database::getInstance();
        
        // Check if username already exists
        $existingUsername = $db->getValue("SELECT COUNT(*) FROM users WHERE username = ?", [$username]);
        if ($existingUsername > 0) {
            return ['success' => false, 'message' => 'Username already exists'];
        }
        
        // Check if email already exists
        $existingEmail = $db->getValue("SELECT COUNT(*) FROM users WHERE email = ?", [$email]);
        if ($existingEmail > 0) {
            return ['success' => false, 'message' => 'Email already exists'];
        }
        
        // Hash password
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        
        // Insert user
        $userId = $db->insert('users', [
            'username' => $username,
            'email' => $email,
            'password' => $hashedPassword,
            'role' => $role,
            'status' => 'active'
        ]);
        
        if ($userId) {
            return ['success' => true, 'message' => 'User created successfully', 'user_id' => $userId];
        } else {
            return ['success' => false, 'message' => 'Failed to create user'];
        }
    }
    
    // Update user
    public static function updateUser($userId, $data) {
        $db = Database::getInstance();
        
        // Check if username already exists
        if (isset($data['username'])) {
            $existingUsername = $db->getRow(
                "SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?",
                [$data['username'], $userId]
            );
            if ($existingUsername && $existingUsername['count'] > 0) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
        }
        
        // Check if email already exists
        if (isset($data['email'])) {
            $existingEmail = $db->getRow(
                "SELECT COUNT(*) as count FROM users WHERE email = ? AND id != ?",
                [$data['email'], $userId]
            );
            if ($existingEmail && $existingEmail['count'] > 0) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
        }
        
        // Update user
        $result = $db->update('users', $data, 'id = ?', [$userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'User updated successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to update user'];
        }
    }
    
    // Delete user
    public static function deleteUser($userId) {
        $db = Database::getInstance();
        
        // Don't allow deleting the last admin
        $adminCount = $db->getValue("SELECT COUNT(*) FROM users WHERE role = 'admin'");
        if ($adminCount <= 1) {
            $isAdmin = $db->getValue("SELECT COUNT(*) FROM users WHERE id = ? AND role = 'admin'", [$userId]);
            if ($isAdmin > 0) {
                return ['success' => false, 'message' => 'Cannot delete the last admin user'];
            }
        }
        
        // Delete user
        $result = $db->delete('users', 'id = ?', [$userId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'User deleted successfully'];
        } else {
            return ['success' => false, 'message' => 'Failed to delete user'];
        }
    }
}