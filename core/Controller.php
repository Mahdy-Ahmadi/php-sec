<?php
namespace Core;

class Controller {
    protected function view($view, $data = []) {
        extract($data);
        $viewFile = __DIR__ . '/../views/' . $view . '.php';
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            die("View not found: $view");
        }
    }
    
    protected function redirect($url) {
        header("Location: $url");
        exit;
    }
    
    protected function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function isAuthenticated() {
        return isset($_SESSION['user_id']);
    }
    
    protected function requireAuth() {
        if (!$this->isAuthenticated()) {
            $this->redirect('/login');
        }
    }
    
    protected function requireGuest() {
        if ($this->isAuthenticated()) {
            $this->redirect('/dashboard');
        }
    }
}
