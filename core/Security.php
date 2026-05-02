<?php
namespace Core;

class Security {
    private static $instance = null;
    private $encryptionKey;
    private $cipher = 'aes-256-gcm';
    
    private function __construct() {
        $this->encryptionKey = $this->getOrCreateEncryptionKey();
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Prevent XSS attacks with multiple encoding
     */
    public static function sanitizeInput($data, $context = 'html') {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        
        $data = trim($data);
        
        switch ($context) {
            case 'html':
                return htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
            case 'sql':
                return addcslashes($data, "%_");
            case 'js':
                return json_encode($data, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
            default:
                return filter_var($data, FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW | FILTER_FLAG_STRIP_HIGH);
        }
    }
    
    /**
     * Generate cryptographically secure random token
     */
    public static function generateSecureToken($length = 64) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Validate and filter email
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) && 
               checkdnsrr(substr(strrchr($email, "@"), 1), "MX");
    }
    
    /**
     * Validate strong password
     * at least 12 chars, uppercase, lowercase, number, special char
     */
    public static function validatePasswordStrength($password) {
        if (strlen($password) < 12) return false;
        if (!preg_match('/[A-Z]/', $password)) return false;
        if (!preg_match('/[a-z]/', $password)) return false;
        if (!preg_match('/[0-9]/', $password)) return false;
        if (!preg_match('/[^A-Za-z0-9]/', $password)) return false;
        if (preg_match('/(.)\1{2,}/', $password)) return false; // No repeated chars
        return true;
    }
    
    /**
     * Encrypt sensitive data with AEAD (Authenticated Encryption)
     */
    public function encrypt($data) {
        $iv = random_bytes(openssl_cipher_iv_length($this->cipher));
        $tag = '';
        $encrypted = openssl_encrypt(
            $data,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16 // tag length
        );
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    /**
     * Decrypt data with integrity verification
     */
    public function decrypt($encryptedData) {
        $data = base64_decode($encryptedData);
        $ivLength = openssl_cipher_iv_length($this->cipher);
        $iv = substr($data, 0, $ivLength);
        $tag = substr($data, $ivLength, 16);
        $ciphertext = substr($data, $ivLength + 16);
        
        return openssl_decrypt(
            $ciphertext,
            $this->cipher,
            $this->encryptionKey,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
    }
    
    /**
     * Get or create encryption key for the application
     */
    private function getOrCreateEncryptionKey() {
        $keyFile = __DIR__ . '/../config/encryption.key';
        
        if (file_exists($keyFile)) {
            return base64_decode(file_get_contents($keyFile));
        }
        
        $key = random_bytes(32); // 256-bit key
        file_put_contents($keyFile, base64_encode($key));
        chmod($keyFile, 0600); // Only read/write for owner
        
        return $key;
    }
    
    /**
     * Validate UUID v4
     */
    public static function validateUUID($uuid) {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $uuid);
    }
    
    /**
     * Prevent timing attacks
     */
    public static function timingSafeCompare($known, $user) {
        return hash_equals($known, $user);
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateSecureToken(32);
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && 
               self::timingSafeCompare($_SESSION['csrf_token'], $token);
    }
}
