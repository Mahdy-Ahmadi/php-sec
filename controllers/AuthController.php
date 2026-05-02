<?php
namespace Controllers;

use Core\Controller;
use Core\Security;
use Classes\User;
use Classes\RateLimiter;
use Classes\TwoFactor;
use Classes\Logger;
use Models\UserModel;

class AuthController extends Controller {
    private $userModel;
    private $rateLimiter;
    private $logger;
    
    public function __construct() {
        parent::__construct();
        $this->userModel = new UserModel();
        $this->rateLimiter = RateLimiter::getInstance();
        $this->logger = Logger::getInstance();
    }
    
    /**
     * Show login page
     */
    public function showLogin() {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('login', [
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }
    
    /**
     * Handle login request
     */
    public function login() {
        // Rate limiting by IP
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->rateLimiter->isAllowed("login_$ip", 
            getenv('RATE_LIMIT_LOGIN'), 
            getenv('RATE_LIMIT_TIME') / 60)) {
            $this->jsonResponse(['error' => 'Too many attempts. Try again later.'], 429);
            return;
        }
        
        // Validate CSRF token
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->logger->warning("CSRF attack detected", ['ip' => $ip]);
            $this->jsonResponse(['error' => 'Invalid security token'], 403);
            return;
        }
        
        // Validate input
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        
        if (!Security::validateEmail($email)) {
            $this->jsonResponse(['error' => 'Invalid email format'], 400);
            return;
        }
        
        if (empty($password)) {
            $this->jsonResponse(['error' => 'Password is required'], 400);
            return;
        }
        
        // Check user credentials
        $user = $this->userModel->findByEmail($email);
        
        if (!$user || !password_verify($password, $user['password_hash'])) {
            $this->logger->warning("Failed login attempt", [
                'email' => $email,
                'ip' => $ip
            ]);
            $this->jsonResponse(['error' => 'Invalid credentials'], 401);
            return;
        }
        
        // Check if account is locked
        if ($user['is_locked'] == 1) {
            $this->jsonResponse(['error' => 'Account is locked. Contact support.'], 403);
            return;
        }
        
        // Check if email is verified
        if ($user['email_verified'] == 0) {
            $this->jsonResponse(['error' => 'Please verify your email first'], 403);
            return;
        }
        
        // Check for 2FA
        if ($user['two_factor_enabled'] == 1) {
            $_SESSION['2fa_user_id'] = $user['id'];
            $_SESSION['2fa_pending'] = true;
            $this->jsonResponse(['requires_2fa' => true]);
            return;
        }
        
        // Complete login
        $this->completeLogin($user);
        $this->jsonResponse(['success' => true, 'redirect' => '/dashboard']);
    }
    
    /**
     * Verify 2FA code
     */
    public function verify2FA() {
        if (!isset($_SESSION['2fa_pending']) || !$_SESSION['2fa_pending']) {
            $this->redirect('/login');
            return;
        }
        
        $code = $_POST['code'] ?? '';
        $userId = $_SESSION['2fa_user_id'];
        $user = $this->userModel->findById($userId);
        
        if (!TwoFactor::verifyTOTP($user['two_factor_secret'], $code)) {
            $this->jsonResponse(['error' => 'Invalid 2FA code'], 401);
            return;
        }
        
        unset($_SESSION['2fa_pending']);
        unset($_SESSION['2fa_user_id']);
        
        $this->completeLogin($user);
        $this->jsonResponse(['success' => true, 'redirect' => '/dashboard']);
    }
    
    /**
     * Complete the login process
     */
    private function completeLogin($user) {
        // Regenerate session ID to prevent session fixation
        session_regenerate_id(true);
        
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
        $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'] ?? '';
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Update last login
        $this->userModel->updateLastLogin($user['id']);
        
        $this->logger->info("User logged in", [
            'user_id' => $user['id'],
            'email' => $user['email']
        ]);
        
        // Clear rate limit on successful login
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $this->rateLimiter->clear("login_$ip");
    }
    
    /**
     * Handle logout
     */
    public function logout() {
        $this->logger->info("User logged out", [
            'user_id' => $_SESSION['user_id'] ?? 'unknown'
        ]);
        
        $_SESSION = [];
        
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }
        
        session_destroy();
        $this->redirect('/login');
    }
    
    /**
     * Show registration page
     */
    public function showRegister() {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
        
        $this->view('register', [
            'csrf_token' => Security::generateCSRFToken()
        ]);
    }
    
    /**
     * Handle registration
     */
    public function register() {
        // Rate limiting
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        if (!$this->rateLimiter->isAllowed("register_$ip", 
            getenv('RATE_LIMIT_REGISTER'), 
            getenv('RATE_LIMIT_TIME') / 60)) {
            $this->jsonResponse(['error' => 'Too many registration attempts'], 429);
            return;
        }
        
        // Validate CSRF
        if (!Security::validateCSRFToken($_POST['csrf_token'] ?? '')) {
            $this->jsonResponse(['error' => 'Invalid security token'], 403);
            return;
        }
        
        // Validate input
        $name = Security::sanitizeInput($_POST['name'] ?? '');
        $email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        $errors = [];
        
        if (strlen($name) < 3 || strlen($name) > 50) {
            $errors[] = 'Name must be between 3 and 50 characters';
        }
        
        if (!Security::validateEmail($email)) {
            $errors[] = 'Valid email is required';
        }
        
        if (!Security::validatePasswordStrength($password)) {
            $errors[] = 'Password must be at least 12 characters with uppercase, lowercase, number, and special character';
        }
        
        if ($password !== $confirmPassword) {
            $errors[] = 'Passwords do not match';
        }
        
        // Check if email exists
        if ($this->userModel->findByEmail($email)) {
            $errors[] = 'Email already registered';
        }
        
        if (!empty($errors)) {
            $this->jsonResponse(['errors' => $errors], 400);
            return;
        }
        
        // Create user
        $userId = $this->userModel->create([
            'name' => $name,
            'email' => $email,
            'password' => Security::generatePasswordHash($password)
        ]);
        
        if (!$userId) {
            $this->jsonResponse(['error' => 'Registration failed'], 500);
            return;
        }
        
        $this->logger->info("New user registered", [
            'user_id' => $userId,
            'email' => $email,
            'ip' => $ip
        ]);
        
        // Send verification email (implement email service)
        // $this->sendVerificationEmail($email, $userId);
        
        $this->jsonResponse(['success' => true, 'message' => 'Registration successful. Please verify your email.']);
    }
    
    /**
     * Show 2FA setup page
     */
    public function show2FASetup() {
        $this->requireAuth();
        
        $secret = TwoFactor::generateSecret();
        $_SESSION['2fa_temp_secret'] = $secret;
        
        $user = $this->userModel->findById($_SESSION['user_id']);
        $qrCodeUrl = TwoFactor::getQRCodeURL($user['email'], $secret, getenv('TWO_FACTOR_ISSUER'));
        
        $this->view('2fa_setup', [
            'secret' => $secret,
            'qr_code_url' => $qrCodeUrl,
            'backup_codes' => TwoFactor::generateBackupCodes()
        ]);
    }
}
