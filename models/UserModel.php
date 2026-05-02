<?php
namespace Models;

use Core\Model;
use Core\Security;

class UserModel extends Model {
    protected $table = 'users';
    
    public function findByEmail($email) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        return $stmt->fetch();
    }
    
    public function create($data) {
        $uuid = Security::generateSecureToken(16);
        $uuid = substr($uuid, 0, 8) . '-' . substr($uuid, 8, 4) . '-4' . substr($uuid, 12, 3) 
              . '-8' . substr($uuid, 15, 3) . '-' . substr($uuid, 18, 12);
        
        $stmt = $this->db->prepare("
            INSERT INTO users (uuid, name, email, password_hash) 
            VALUES (:uuid, :name, :email, :password)
        ");
        
        $stmt->execute([
            'uuid' => $uuid,
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password']
        ]);
        
        return $this->db->lastInsertId();
    }
    
    public function updateLastLogin($userId) {
        $stmt = $this->db->prepare("
            UPDATE users SET 
                last_login_at = NOW(), 
                last_login_ip = :ip,
                failed_login_attempts = 0
            WHERE id = :id
        ");
        
        $stmt->execute([
            'id' => $userId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? null
        ]);
    }
    
    public function update2FASecret($userId, $secret) {
        $stmt = $this->db->prepare("
            UPDATE users SET 
                two_factor_secret = :secret,
                two_factor_enabled = 1
            WHERE id = :id
        ");
        
        return $stmt->execute([
            'id' => $userId,
            'secret' => $secret
        ]);
    }
}
