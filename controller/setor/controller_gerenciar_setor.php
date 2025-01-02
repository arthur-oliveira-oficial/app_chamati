<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class ControllerGerenciarSetor {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function buscarSetor($id) {
        try {
            $stmt = $this->conn->prepare("
                SELECT id, nome, filial_id
                FROM setores 
                WHERE id = :id
            ");
            
            $stmt->execute([':id' => $id]);
            $setor = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$setor) {
                throw new Exception('Setor não encontrado');
            }
            
            return [
                'success' => true,
                'setor' => $setor
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function editarSetor($dados) {
        try {
            // Validar dados
            if (empty($dados['id']) || empty($dados['nome']) || empty($dados['filial_id'])) {
                throw new Exception('Todos os campos são obrigatórios');
            }

            // Verificar se o setor existe
            $stmt = $this->conn->prepare("SELECT id FROM setores WHERE id = :id");
            $stmt->execute([':id' => $dados['id']]);
            if (!$stmt->fetch()) {
                throw new Exception('Setor não encontrado');
            }

            // Verificar se já existe um setor com mesmo nome na mesma filial
            $stmt = $this->conn->prepare("
                SELECT COUNT(*) FROM setores 
                WHERE nome = :nome 
                AND filial_id = :filial_id 
                AND id != :id
            ");
            $stmt->execute([
                ':nome' => $dados['nome'],
                ':filial_id' => $dados['filial_id'],
                ':id' => $dados['id']
            ]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Já existe um setor com este nome nesta filial');
            }

            // Atualizar setor
            $stmt = $this->conn->prepare("
                UPDATE setores 
                SET nome = :nome, 
                    filial_id = :filial_id 
                WHERE id = :id
            ");

            $resultado = $stmt->execute([
                ':id' => $dados['id'],
                ':nome' => $dados['nome'],
                ':filial_id' => $dados['filial_id']
            ]);

            if (!$resultado) {
                throw new Exception('Erro ao atualizar setor');
            }

            return [
                'success' => true,
                'message' => 'Setor atualizado com sucesso'
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function excluirSetor($id) {
        try {
            // Verificar se existem chamados vinculados
            $stmt = $this->conn->prepare("SELECT COUNT(*) FROM chamados WHERE setor_id = :id");
            $stmt->execute([':id' => $id]);
            if ($stmt->fetchColumn() > 0) {
                throw new Exception('Não é possível excluir o setor pois existem chamados vinculados');
            }

            // Excluir setor
            $stmt = $this->conn->prepare("DELETE FROM setores WHERE id = :id");
            $resultado = $stmt->execute([':id' => $id]);

            if (!$resultado) {
                throw new Exception('Erro ao excluir setor');
            }

            return [
                'success' => true,
                'message' => 'Setor excluído com sucesso'
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

$controller = new ControllerGerenciarSetor();

// Roteamento das ações
$action = $_GET['action'] ?? '';

header('Content-Type: application/json');

switch ($action) {
    case 'buscar':
        if (isset($_GET['id'])) {
            echo json_encode($controller->buscarSetor($_GET['id']));
        }
        break;
        
    case 'editar':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $dados = [
                'id' => filter_var($_POST['id'] ?? '', FILTER_VALIDATE_INT),
                'nome' => htmlspecialchars(trim($_POST['nome'] ?? ''), ENT_QUOTES, 'UTF-8'),
                'filial_id' => filter_var($_POST['filial_id'] ?? '', FILTER_VALIDATE_INT)
            ];
            echo json_encode($controller->editarSetor($dados));
        }
        break;
        
    case 'excluir':
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $data = json_decode(file_get_contents('php://input'), true);
            if (isset($data['id'])) {
                echo json_encode($controller->excluirSetor($data['id']));
            }
        }
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Ação inválida']);
}
?> 