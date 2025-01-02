<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class ControllerGerenciarFilial {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function buscarFilial($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, nome, endereco
                FROM filiais 
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $id]);
            $filial = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$filial) {
                throw new Exception('Filial não encontrada');
            }
            
            return [
                'success' => true,
                'filial' => $filial
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function editarFilial($dados) {
        try {
            // Validar dados
            if (empty($dados['id']) || empty($dados['nome']) || empty($dados['endereco'])) {
                throw new Exception('Todos os campos são obrigatórios');
            }

            // Verificar se a filial existe
            $stmt = $this->conn->prepare("SELECT id FROM filiais WHERE id = :id");
            $stmt->execute([':id' => $dados['id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Filial não encontrada');
            }

            // Atualizar filial
            $stmt = $this->conn->prepare("
                UPDATE filiais 
                SET nome = :nome, 
                    endereco = :endereco 
                WHERE id = :id
            ");

            $resultado = $stmt->execute([
                ':id' => $dados['id'],
                ':nome' => $dados['nome'],
                ':endereco' => $dados['endereco']
            ]);

            if (!$resultado) {
                throw new Exception('Erro ao atualizar filial');
            }

            return [
                'success' => true,
                'message' => 'Filial atualizada com sucesso'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function excluirFilial($id) {
        try {
            // Verificar se existem setores vinculados
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM setores WHERE filial_id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Não é possível excluir a filial pois existem setores vinculados');
            }

            // Excluir filial
            $stmt = $this->conn->prepare("DELETE FROM filiais WHERE id = :id");
            $resultado = $stmt->execute([':id' => $id]);

            if (!$resultado) {
                throw new Exception('Erro ao excluir filial');
            }

            return [
                'success' => true,
                'message' => 'Filial excluída com sucesso'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
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

$controller = new ControllerGerenciarFilial();

// Roteamento das ações
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'buscar':
        if (isset($_GET['id'])) {
            echo json_encode($controller->buscarFilial($_GET['id']));
        }
        break;
        
    case 'editar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'id' => filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT),
                'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'endereco' => htmlspecialchars(trim($_POST['endereco'] ?? ''), ENT_QUOTES, 'UTF-8')
            ];
            echo json_encode($controller->editarFilial($dados));
        }
        break;
        
    case 'excluir':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['id'])) {
                echo json_encode($controller->excluirFilial($data['id']));
            }
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
?> 