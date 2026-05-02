<?php
namespace Classes;

use Core\Security;

class TwoFactor {
    /**
     * Generate TOTP secret key (RFC 6238 compliant)
     */
    public static function generateSecret() {
        $secret = '';
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        
        for ($i = 0; $i < 32; $i++) {
            $secret .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        
        return $secret;
    }
    
    /**
     * Generate TOTP code based on secret and current time
     */
    public static function generateTOTP($secret, $timeWindow = 30) {
        $secret = self::base32Decode($secret);
        $time = floor(time() / $timeWindow);
        $timeBytes = pack('N*', 0) . pack('N*', $time);
        
        $hash = hash_hmac('sha1', $timeBytes, $secret, true);
        $offset = ord($hash[19]) & 0xf;
        
        $code = (
            ((ord($hash[$offset]) & 0x7f) << 24) |
            ((ord($hash[$offset + 1]) & 0xff) << 16) |
            ((ord($hash[$offset + 2]) & 0xff) << 8) |
            (ord($hash[$offset + 3]) & 0xff)
        ) % 1000000;
        
        return str_pad($code, 6, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify TOTP code
     */
    public static function verifyTOTP($secret, $code, $window = 1) {
        for ($i = -$window; $i <= $window; $i++) {
            $timeWindow = 30;
            $time = floor(time() / $timeWindow) + $i;
            $timeBytes = pack('N*', 0) . pack('N*', $time);
            
            $hash = hash_hmac('sha1', $timeBytes, self::base32Decode($secret), true);
            $offset = ord($hash[19]) & 0xf;
            
            $generatedCode = (
                ((ord($hash[$offset]) & 0x7f) << 24) |
                ((ord($hash[$offset + 1]) & 0xff) << 16) |
                ((ord($hash[$offset + 2]) & 0xff) << 8) |
                (ord($hash[$offset + 3]) & 0xff)
            ) % 1000000;
            
            $generatedCode = str_pad($generatedCode, 6, '0', STR_PAD_LEFT);
            
            if (Security::timingSafeCompare($generatedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Generate QR code URL for Google Authenticator
     */
    public static function getQRCodeURL($name, $secret, $issuer = 'PHPSecure') {
        $name = rawurlencode($name);
        $issuer = rawurlencode($issuer);
        $secret = rawurlencode($secret);
        
        return "otpauth://totp/{$issuer}:{$name}?secret={$secret}&issuer={$issuer}";
    }
    
    /**
     * Generate backup codes (8 codes, 10 characters each)
     */
    public static function generateBackupCodes($count = 8, $length = 10) {
        $codes = [];
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        for ($i = 0; $i < $count; $i++) {
            $code = '';
            for ($j = 0; $j < $length; $j++) {
                $code .= $characters[random_int(0, strlen($characters) - 1)];
            }
            $codes[] = $code;
        }
        
        return $codes;
    }
    
    private static function base32Decode($base32) {
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32 = strtoupper($base32);
        $padding = strlen($base32) % 8;
        
        if ($padding !== 0) {
            $base32 .= str_repeat('=', 8 - $padding);
        }
        
        $result = '';
        $buffer = 0;
        $bits = 0;
        
        for ($i = 0; $i < strlen($base32); $i++) {
            $char = $base32[$i];
            if ($char === '=') break;
            
            $val = strpos($base32Chars, $char);
            if ($val === false) continue;
            
            $buffer = ($buffer << 5) | $val;
            $bits += 5;
            
            if ($bits >= 8) {
                $bits -= 8;
                $result .= chr(($buffer >> $bits) & 0xFF);
            }
        }
        
        return $result;
    }
}
