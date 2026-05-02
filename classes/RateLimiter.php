<?php
namespace Classes;

use Core\Security;

class RateLimiter {
    private static $instance = null;
    private $limits = [];
    private $storagePath;
    
    private function __construct() {
        $this->storagePath = __DIR__ . '/../data/rate_limits/';
        if (!is_dir($this->storagePath)) {
            mkdir($this->storagePath, 0755, true);
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Check if request is allowed based on rate limit
     */
    public function isAllowed($key, $maxAttempts = 5, $decayMinutes = 5) {
        $this->cleanup($key);
        
        $currentAttempts = $this->getAttempts($key);
        
        if ($currentAttempts >= $maxAttempts) {
            $this->logRateLimitExceeded($key, $currentAttempts);
            return false;
        }
        
        $this->incrementAttempts($key, $decayMinutes);
        return true;
    }
    
    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts($key, $maxAttempts = 5) {
        $currentAttempts = $this->getAttempts($key);
        return max(0, $maxAttempts - $currentAttempts);
    }
    
    /**
     * Get time until reset (in seconds)
     */
    public function getResetTime($key) {
        $file = $this->storagePath . md5($key) . '.json';
        if (!file_exists($file)) return 0;
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data) return 0;
        
        $expiresAt = $data['expires_at'];
        return max(0, $expiresAt - time());
    }
    
    /**
     * Clear rate limit for a key
     */
    public function clear($key) {
        $file = $this->storagePath . md5($key) . '.json';
        if (file_exists($file)) {
            unlink($file);
        }
    }
    
    private function getAttempts($key) {
        $file = $this->storagePath . md5($key) . '.json';
        if (!file_exists($file)) return 0;
        
        $data = json_decode(file_get_contents($file), true);
        if (!$data || $data['expires_at'] < time()) {
            return 0;
        }
        
        return $data['attempts'];
    }
    
    private function incrementAttempts($key, $decayMinutes) {
        $file = $this->storagePath . md5($key) . '.json';
        $current = $this->getAttempts($key);
        
        $data = [
            'attempts' => $current + 1,
            'expires_at' => time() + ($decayMinutes * 60)
        ];
        
        file_put_contents($file, json_encode($data));
    }
    
    private function cleanup($key) {
        $file = $this->storagePath . md5($key) . '.json';
        if (file_exists($file)) {
            $data = json_decode(file_get_contents($file), true);
            if ($data && $data['expires_at'] < time()) {
                unlink($file);
            }
        }
    }
    
    private function logRateLimitExceeded($key, $attempts) {
        $logger = Logger::getInstance();
        $logger->warning("Rate limit exceeded", [
            'key' => $key,
            'attempts' => $attempts,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]);
    }
}
