<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class SetorController {
    private function validarDados($dados) {
        if (empty($dados['nome'])) {
            throw new Exception('Nome do setor é obrigatório');
        }
        
        // Validar caracteres especiais
        if (!preg_match("/^[a-zA-Z0-9 ]*$/", $dados['nome'])) {
            throw new Exception('Nome do setor contém caracteres inválidos');
        }

        // Verificar se já existe setor com mesmo nome na mesma filial
        $db = Database::getInstance();
        $stmt = $db->prepare("SELECT COUNT(*) FROM setores WHERE nome = :nome AND filial_id = :filial_id");
        $stmt->bindParam(':nome', $dados['nome']);
        $stmt->bindParam(':filial_id', $dados['filial_id']);
        $stmt->execute();
        
        if ($stmt->fetchColumn() > 0) {
            throw new Exception('Já existe um setor com este nome nesta filial');
        }
    }

    public function criarSetor() {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            try {
                $db = Database::getInstance();
                
                // Validação
                $nome = filter_input(INPUT_POST, 'nome', FILTER_SANITIZE_STRING);
                $filial_id = filter_input(INPUT_POST, 'filial_id', FILTER_VALIDATE_INT);
                
                $this->validarDados([
                    'nome' => $nome,
                    'filial_id' => $filial_id
                ]);

                // Inserir setor
                $stmt = $db->prepare("INSERT INTO setores (nome, filial_id) VALUES (:nome, :filial_id)");
                $stmt->bindParam(':nome', $nome);
                $stmt->bindParam(':filial_id', $filial_id);
                
                if ($stmt->execute()) {
                    header('Location: /app_chamati/views/admin/admin_setor/novo_setor.php?success=1');
                    exit;
                } else {
                    throw new Exception('Erro ao cadastrar setor');
                }

            } catch (Exception $e) {
                header('Location: /app_chamati/views/admin/admin_setor/novo_setor.php?error=' . urlencode($e->getMessage()));
                exit;
            }
        } else {
            header('Location: /app_chamati/views/admin/admin_setor/novo_setor.php');
            exit;
        }
    }
}

// Instanciar e executar
$controller = new SetorController();
$controller->criarSetor();