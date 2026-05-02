<?php
namespace Classes;

class Logger {
    private static $instance = null;
    private $logPath;
    private $logLevels = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
        'critical' => 4
    ];
    
    private function __construct() {
        $this->logPath = __DIR__ . '/../logs/security.log';
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function info($message, $context = []) {
        $this->log('info', $message, $context);
    }
    
    public function warning($message, $context = []) {
        $this->log('warning', $message, $context);
    }
    
    public function error($message, $context = []) {
        $this->log('error', $message, $context);
    }
    
    public function critical($message, $context = []) {
        $this->log('critical', $message, $context);
    }
    
    private function log($level, $message, $context) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => strtoupper($level),
            'message' => $message,
            'context' => $context,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'CLI',
            'user_id' => $_SESSION['user_id'] ?? null,
            'uri' => $_SERVER['REQUEST_URI'] ?? null
        ];
        
        $logLine = json_encode($logEntry) . PHP_EOL;
        file_put_contents($this->logPath, $logLine, FILE_APPEND);
    }
}
