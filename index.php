<?php
// error reporting - فقط در محیط توسعه
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/php_errors.log');

// شروع session با تنظیمات امنیتی
session_start([
    'cookie_httponly' => true,
    'cookie_secure' => true,
    'cookie_samesite' => 'Strict',
    'use_strict_mode' => true,
    'use_only_cookies' => true,
]);

// Autoloader
spl_autoload_register(function ($class) {
    $prefix = '';
    $base_dir = __DIR__ . '/';
    
    $class = str_replace('\\', '/', $class);
    $file = $base_dir . $class . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load environment variables
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env');
    foreach ($lines as $line) {
        $line = trim($line);
        if (!empty($line) && $line[0] !== '#') {
            putenv($line);
        }
    }
}

// Simple router
$request_uri = $_SERVER['REQUEST_URI'];
$request_uri = strtok($request_uri, '?');
$request_uri = trim($request_uri, '/');

if (empty($request_uri)) {
    $request_uri = 'home';
}

// Routing table
$routes = [
    'home' => ['controller' => 'DashboardController', 'method' => 'index'],
    'login' => ['controller' => 'AuthController', 'method' => 'showLogin'],
    'login/submit' => ['controller' => 'AuthController', 'method' => 'login'],
    'register' => ['controller' => 'AuthController', 'method' => 'showRegister'],
    'register/submit' => ['controller' => 'AuthController', 'method' => 'register'],
    'logout' => ['controller' => 'AuthController', 'method' => 'logout'],
    'dashboard' => ['controller' => 'DashboardController', 'method' => 'index'],
    '2fa/verify' => ['controller' => 'AuthController', 'method' => 'verify2FA'],
    '2fa/setup' => ['controller' => 'AuthController', 'method' => 'show2FASetup'],
];

// Route request
if (isset($routes[$request_uri])) {
    $controller = 'Controllers\\' . $routes[$request_uri]['controller'];
    $method = $routes[$request_uri]['method'];
    
    if (class_exists($controller)) {
        $obj = new $controller();
        if (method_exists($obj, $method)) {
            $obj->$method();
            exit;
        }
    }
}

// 404 Not Found
http_response_code(404);
echo "<h1>404 - Page Not Found</h1>";
