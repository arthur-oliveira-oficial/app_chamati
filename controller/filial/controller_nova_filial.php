<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class ControllerNovaFilial {
    private $db;
    private $conn;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->conn = $this->db->getConnection();
    }

    public function cadastrarFilial($nome, $endereco) {
        try {
            // Validar dados
            $this->validarDados($nome, $endereco);
            
            // Verificar se já existe uma filial com o mesmo nome
            if ($this->filialExiste($nome)) {
                throw new Exception('Já existe uma filial com este nome.');
            }

            // Iniciar transação
            $this->conn->beginTransaction();

            // Inserir filial
            $stmt = $this->conn->prepare("
                INSERT INTO filiais (nome, endereco) 
                VALUES (:nome, :endereco)
            ");

            $stmt->execute([
                ':nome' => $nome,
                ':endereco' => $endereco
            ]);

            $filialId = $this->conn->lastInsertId();

            // Commit da transação
            $this->conn->commit();

            // Registrar log
            $this->registrarLog('Nova filial cadastrada', $filialId);

            return [
                'success' => true,
                'message' => 'Filial cadastrada com sucesso!',
                'filial_id' => $filialId
            ];

        } catch (Exception $e) {
            // Rollback em caso de erro
            if ($this->conn->inTransaction()) {
                $this->conn->rollBack();
            }
            
            throw new Exception('Erro ao cadastrar filial: ' . $e->getMessage());
        }
    }

    private function validarDados($nome, $endereco) {
        if (empty($nome) || empty($endereco)) {
            throw new Exception('Todos os campos são obrigatórios.');
        }

        if (strlen($nome) < 3 || strlen($nome) > 100) {
            throw new Exception('O nome da filial deve ter entre 3 e 100 caracteres.');
        }

        if (strlen($endereco) < 5 || strlen($endereco) > 255) {
            throw new Exception('O endereço deve ter entre 5 e 255 caracteres.');
        }

        // Validar caracteres permitidos no nome
        if (!preg_match('/^[A-Za-zÀ-ÿ0-9\s\-]+$/', $nome)) {
            throw new Exception('O nome da filial contém caracteres inválidos.');
        }
    }

    private function filialExiste($nome) {
        $stmt = $this->conn->prepare("
            SELECT COUNT(*) FROM filiais WHERE LOWER(nome) = LOWER(:nome)
        ");
        $stmt->execute([':nome' => $nome]);
        return $stmt->fetchColumn() > 0;
    }

    private function registrarLog($acao, $filialId) {
        try {
            $stmt = $this->conn->prepare("
                INSERT INTO logs (usuario_id, acao, tabela_afetada, registro_afetado, data_hora)
                VALUES (:usuario_id, :acao, 'filiais', :registro_afetado, NOW())
            ");

            $stmt->execute([
                ':usuario_id' => $_SESSION['usuario_id'],
                ':acao' => $acao,
                ':registro_afetado' => $filialId
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
        $controller = new ControllerNovaFilial();
        
        // Sanitizar inputs usando htmlspecialchars em vez de FILTER_SANITIZE_STRING
        $nome = htmlspecialchars(trim($_POST['nome_filial'] ?? ''), ENT_QUOTES, 'UTF-8');
        $endereco = htmlspecialchars(trim($_POST['endereco'] ?? ''), ENT_QUOTES, 'UTF-8');
        
        $resultado = $controller->cadastrarFilial($nome, $endereco);
        
        header('Location: /app_chamati/views/admin/admin_filial/nova_filial.php?success=1');
    } catch (Exception $e) {
        header('Location: /app_chamati/views/admin/admin_filial/nova_filial.php?error=' . urlencode($e->getMessage()));
    }
    exit();
}
?>