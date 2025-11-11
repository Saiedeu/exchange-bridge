<?php
/**
 * ExchangeBridge - Database Core File 
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

class Database {
    private $conn;
    private static $instance = null;
    
    private function __construct() {
        try {
            $this->conn = new PDO(
                "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
                DB_USER,
                DB_PASS,
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_PERSISTENT => false,
                    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
                ]
            );
            
            // Additional security settings
            $this->conn->exec("SET sql_mode = 'STRICT_TRANS_TABLES,NO_ZERO_DATE,NO_ZERO_IN_DATE'");
            
        } catch (PDOException $e) {
            error_log("Database connection error: " . $e->getMessage());
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Connection failed: " . $e->getMessage());
            } else {
                die("Database connection error. Please try again later.");
            }
        }
    }
    
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->conn;
    }
    
    // Secure query execution with improved validation
    public function query($sql, $params = []) {
        try {
            // Security check for suspicious SQL patterns (improved)
            $this->validateQuery($sql);
            
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Database query error: " . $e->getMessage() . " | SQL: " . $sql);
            
            if (defined('DEBUG_MODE') && DEBUG_MODE) {
                die("Query failed: " . $e->getMessage());
            }
            return false;
        }
    }
    
    private function validateQuery($sql) {
        // Check for more specific suspicious patterns that indicate SQL injection attempts
        $suspiciousPatterns = [
            // Check for UNION-based injection attempts
            '/union\s+select/i',
            // Check for comment-based injection attempts  
            '/--\s*\w/i',
            '/\/\*.*\*\//i',
            // Check for potential boolean-based injection with suspicious patterns
            '/(\s|^)(or|and)\s+[\'"]?\w*[\'"]?\s*=\s*[\'"]?\w*[\'"]?\s+(or|and)/i',
            // Check for potential stacked queries (semicolon followed by SQL keywords)
            '/;\s*(insert|update|delete|drop|create|alter|truncate)/i',
            // Check for potential hex/char injection
            '/(char|ascii|hex)\s*\(/i',
            // Check for potential system function calls
            '/(load_file|into\s+outfile|into\s+dumpfile)/i',
            // Check for potential SQL injection with 1=1 or similar
            '/(\s|^)(or|and)\s+\d+\s*=\s*\d+\s+(or|and)/i'
        ];
        
        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $sql)) {
                error_log("Suspicious SQL pattern detected: " . $sql);
                throw new PDOException("Invalid query detected");
            }
        }
    }
    
    // Get a single row
    public function getRow($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetch() : false;
    }
    
    // Get multiple rows
    public function getRows($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }
    
    // Get a single value
    public function getValue($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        $row = $stmt ? $stmt->fetch(PDO::FETCH_NUM) : false;
        return $row ? $row[0] : false;
    }
    
    // Insert data and return last insert ID
    public function insert($table, $data) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        
        if ($this->query($sql, array_values($data))) {
            return $this->conn->lastInsertId();
        }
        
        return false;
    }
    
    // Update data
    public function update($table, $data, $where, $whereParams = []) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $setParts = [];
        $params = [];
        
        foreach ($data as $column => $value) {
            $setParts[] = "$column = ?";
            $params[] = $value;
        }
        
        $params = array_merge($params, $whereParams);
        
        $sql = "UPDATE $table SET " . implode(', ', $setParts) . " WHERE $where";
        
        return $this->query($sql, $params) ? true : false;
    }
    
    // Delete data
    public function delete($table, $where, $params = []) {
        // Sanitize table name
        $table = preg_replace('/[^a-zA-Z0-9_]/', '', $table);
        
        $sql = "DELETE FROM $table WHERE $where";
        return $this->query($sql, $params) ? true : false;
    }
    
    // Begin transaction
    public function beginTransaction() {
        return $this->conn->beginTransaction();
    }
    
    // Commit transaction
    public function commit() {
        return $this->conn->commit();
    }
    
    // Rollback transaction
    public function rollback() {
        return $this->conn->rollBack();
    }
    
    // Check if in transaction
    public function inTransaction() {
        return $this->conn->inTransaction();
    }
}