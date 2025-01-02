<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class LoginController {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function login($email, $senha) {
        try {
            // Validação dos campos
            $this->validarCampos($email, $senha);

            // Buscar usuário
            $stmt = $this->db->prepare("
                SELECT id, nome, email, senha_hash, tipo, status 
                FROM usuarios 
                WHERE email = :email
                LIMIT 1
            ");
            
            $stmt->bindParam(':email', $email);
            $stmt->execute();
            
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }

            if ($usuario['status'] !== 'Ativo') {
                throw new Exception('Usuário inativo. Contate o administrador.');
            }

            if (!password_verify($senha, $usuario['senha_hash'])) {
                throw new Exception('Senha incorreta');
            }

            // Iniciar sessão
            $this->iniciarSessao($usuario);
            
            return [
                'success' => true,
                'message' => 'Login realizado com sucesso',
                'redirect' => $this->getRedirectUrl($usuario['tipo'])
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validarCampos($email, $senha) {
        if (empty($email) || empty($senha)) {
            throw new Exception('Email e senha são obrigatórios');
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception('Email inválido');
        }

        if (strlen($senha) < 6) {
            throw new Exception('Senha deve ter no mínimo 6 caracteres');
        }
    }

    private function iniciarSessao($usuario) {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        // Regenerar ID da sessão para prevenir fixação de sessão
        session_regenerate_id(true);
        
        $_SESSION['usuario_id'] = $usuario['id'];
        $_SESSION['usuario_nome'] = $usuario['nome'];
        $_SESSION['usuario_email'] = $usuario['email'];
        $_SESSION['usuario_tipo'] = $usuario['tipo'];
        $_SESSION['ultimo_acesso'] = time();
        
        // Configurações de segurança da sessão
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_secure', 1);
    }

    private function getRedirectUrl($tipo) {
        switch ($tipo) {
            case 'Tecnico':
                return '/app_chamati/views/tecnico/dashboard_tecnico.php';
            case 'Funcionario':
                return '/app_chamati/views/funcionario/dashboard_funcionario.php';
            default:
                return '/app_chamati/index.php';
        }
    }

    public function logout() {
        session_start();
        
        // Destruir todas as variáveis da sessão
        $_SESSION = array();
        
        // Destruir o cookie da sessão
        if (isset($_COOKIE[session_name()])) {
            setcookie(session_name(), '', time()-42000, '/');
        }
        
        // Destruir a sessão
        session_destroy();
        
        header('Location: /app_chamati/index.php?logout=1');
        exit();
    }
}

// Processar requisições
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $controller = new LoginController();
    
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $senha = $_POST['senha'] ?? '';
    
    $resultado = $controller->login($email, $senha);
    
    if ($resultado['success']) {
        header('Location: ' . $resultado['redirect']);
        exit();
    } else {
        header('Location: /app_chamati/index.php?error=' . urlencode($resultado['message']));
        exit();
    }
}
