<?php
require_once __DIR__ . '/controller_login.php';

// Verifica se a sessão já está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    // Instancia o controller de login
    $controller = new LoginController();
    
    // Executa o logout
    $controller->logout();
    
} catch (Exception $e) {
    // Em caso de erro, registra o erro e redireciona
    error_log('Erro durante logout: ' . $e->getMessage());
    header('Location: /app_chamati/index.php?error=' . urlencode('Erro ao realizar logout'));
    exit();
}