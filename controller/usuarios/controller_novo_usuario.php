<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class ControllerNovoUsuario {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function cadastrarUsuario($dados) {
        try {
            // Validar dados
            $this->validarDados($dados);
            
            // Verificar se já existe um usuário com o mesmo email
            if ($this->emailExiste($dados['email'])) {
                throw new Exception('Já existe um usuário com este e-mail.');
            }

            // Iniciar transação
            $this->conn->beginTransaction();

            // Hash da senha
            $senha_hash = password_hash($dados['senha'], PASSWORD_DEFAULT);

            // Inserir usuário
            $stmt = $this->conn->prepare("
                INSERT INTO usuarios (
                    nome, 
                    email, 
                    senha_hash, 
                    tipo
                ) VALUES (
                    :nome, 
                    :email, 
                    :senha_hash, 
                    :tipo
                )
            ");

            $stmt->execute([
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':senha_hash' => $senha_hash,
                ':tipo' => $dados['tipo']
            ]);

            $usuarioId = $this->conn->lastInsertId();

            // Commit da transação
            $this->conn->commit();

            // Registrar log
            $this->registrarLog('Novo usuário cadastrado', $usuarioId);

            return [
                'success' => true,
                'message' => 'Usuário cadastrado com sucesso!',
                'usuario_id' => $usuarioId
            ];

        } catch (Exception $e) {
            // Rollback em caso de erro
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            throw new Exception('Erro ao cadastrar usuário: ' . $e->getMessage());
        }
    }

    private function validarDados($dados) {
        if (empty($dados['nome'])) {
            throw new Exception('Nome é obrigatório');
        }
        if (empty($dados['email'])) {
            throw new Exception('E-mail é obrigatório');
        }
        if (empty($dados['senha'])) {
            throw new Exception('Senha é obrigatória');
        }
        if (empty($dados['tipo'])) {
            throw new Exception('Tipo de usuário é obrigatório');
        }

        // Validar formato do email
        if (!filter_var($dados['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception('E-mail inválido');
        }

        // Validar caracteres permitidos no nome
        if (!preg_match('/^[A-Za-zÀ-ÿ\s]+$/', $dados['nome'])) {
            throw new Exception('O nome contém caracteres inválidos');
        }

        // Validar comprimento da senha
        if (strlen($dados['senha']) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres');
        }

        // Validar tipo de usuário
        if (!in_array($dados['tipo'], ['Tecnico', 'Funcionario'])) {
            throw new Exception('Tipo de usuário inválido');
        }
    }

    private function emailExiste($email) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM usuarios WHERE email = :email
        ");
        $stmt->execute([':email' => $email]);
        return $stmt->fetchColumn() > 0;
    }

    private function registrarLog($acao, $usuarioId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_afetado, data_hora)
                VALUES (:usuario_id, :acao, 'usuarios', :registro_afetado, NOW())
            ");

            $stmt->execute([
                ':usuario_id' => $_SESSION['usuario_id'],
                ':acao' => $acao,
                ':registro_afetado' => $usuarioId
            ]);
        } catch (Exception $e) {
            error_log('Erro ao registrar log: ' . $e->getMessage());
        }
    }
}

// Processar requisição
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    // Verificar autenticação e autorização
    if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
        header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
        exit();
    }

    try {
        $controller = new ControllerNovoUsuario();
        
        // Sanitizar inputs
        $dados = [
            'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
            'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
            'senha' => $_POST['senha'] ?? '',
            'tipo' => $_POST['tipo'] ?? ''
        ];
        
        $resultado = $controller->cadastrarUsuario($dados);
        
        header('Location: /app_chamati/views/admin/admin_usuario/novo_usuario.php?success=1');
    } catch (Exception $e) {
        header('Location: /app_chamati/views/admin/admin_usuario/novo_usuario.php?error=' . urlencode($e->getMessage()));
    }
    exit();
}
?>