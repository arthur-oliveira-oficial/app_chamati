<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class ControllerGerenciarUsuario {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function buscarUsuario($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, nome, email, tipo
                FROM usuarios 
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $id]);
            $usuario = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$usuario) {
                throw new Exception('Usuário não encontrado');
            }
            
            return [
                'success' => true,
                'usuario' => $usuario
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function editarUsuario($dados) {
        try {
            $this->validarDados($dados);
            
            // Verificar se email já existe para outro usuário
            if ($this->emailExisteOutroUsuario($dados['email'], $dados['id'])) {
                throw new Exception('Este e-mail já está em uso por outro usuário');
            }

            $this->conn->beginTransaction();

            // Preparar query base
            $sql = "UPDATE usuarios SET 
                    nome = :nome,
                    email = :email,
                    tipo = :tipo";
            
            $params = [
                ':id' => $dados['id'],
                ':nome' => $dados['nome'],
                ':email' => $dados['email'],
                ':tipo' => $dados['tipo']
            ];

            // Adicionar senha à query se fornecida
            if (!empty($dados['senha'])) {
                $sql .= ", senha_hash = :senha_hash";
                $params[':senha_hash'] = password_hash($dados['senha'], PASSWORD_DEFAULT);
            }

            $sql .= " WHERE id = :id";

            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);

            $this->conn->commit();
            
            // Registrar log
            $this->registrarLog('Usuário atualizado', $dados['id']);

            return [
                'success' => true,
                'message' => 'Usuário atualizado com sucesso'
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function excluirUsuario($id) {
        try {
            // Verificar se o usuário existe
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $id]);
            
            if ($stmt->fetchColumn() == 0) {
                throw new Exception('Usuário não encontrado');
            }

            $this->conn->beginTransaction();

            // Excluir usuário
            $stmt = $this->conn->prepare("DELETE FROM usuarios WHERE id = :id");
            $stmt->execute([':id' => $id]);

            $this->conn->commit();
            
            // Registrar log
            $this->registrarLog('Usuário excluído', $id);

            return [
                'success' => true,
                'message' => 'Usuário excluído com sucesso'
            ];
        } catch (Exception $e) {
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validarDados($dados) {
        if (empty($dados['nome'])) {
            throw new Exception('Nome é obrigatório');
        }
        if (empty($dados['email'])) {
            throw new Exception('E-mail é obrigatório');
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

        // Validar senha se fornecida
        if (!empty($dados['senha']) && strlen($dados['senha']) < 6) {
            throw new Exception('A senha deve ter no mínimo 6 caracteres');
        }

        // Validar tipo de usuário
        if (!in_array($dados['tipo'], ['Tecnico', 'Funcionario'])) {
            throw new Exception('Tipo de usuário inválido');
        }
    }

    private function emailExisteOutroUsuario($email, $id) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM usuarios 
            WHERE email = :email AND id != :id
        ");
        $stmt->execute([
            ':email' => $email,
            ':id' => $id
        ]);
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

// Processar requisições
session_start();

// Verificar autenticação e autorização
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso não autorizado']);
    exit();
}

$controller = new ControllerGerenciarUsuario();

// Roteamento das ações
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'buscar':
        if (isset($_GET['id'])) {
            echo json_encode($controller->buscarUsuario($_GET['id']));
        }
        break;
        
    case 'editar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'id' => filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT),
                'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'email' => filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL),
                'senha' => $_POST['senha'] ?? '',
                'tipo' => $_POST['tipo'] ?? ''
            ];
            echo json_encode($controller->editarUsuario($dados));
        }
        break;
        
    case 'excluir':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['id'])) {
                echo json_encode($controller->excluirUsuario($data['id']));
            }
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
?>
