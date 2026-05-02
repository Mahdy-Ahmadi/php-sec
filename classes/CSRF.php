<?php
namespace Classes;

use Core\Security;

class CSRF {
    public static function generate() {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = Security::generateSecureToken(32);
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verify($token) {
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function getField() {
        return '<input type="hidden" name="csrf_token" value="' . self::generate() . '">';
    }
}
