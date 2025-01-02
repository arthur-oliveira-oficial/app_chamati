<?php
require_once __DIR__ . '/../../database/conexaodb.php';

class ControllerAbrirChamado {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public function getSetores($filialId) {
        try {
            $stmt = $this->db->prepare("SELECT id, nome FROM setores WHERE filial_id = :filial_id ORDER BY nome");
            $stmt->bindParam(':filial_id', $filialId);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            throw new Exception("Erro ao buscar setores: " . $e->getMessage());
        }
    }

    public function abrirChamado($dados, $arquivos = null) {
        try {
            // Validar dados
            $this->validarDados($dados);

            // Gerar número do chamado
            $numeroChamado = $this->gerarNumeroChamado();

            // Processar arquivos se existirem
            $arquivosProcessados = [];
            if ($arquivos && !empty($arquivos['name'][0])) {
                $arquivosProcessados = $this->processarArquivos($arquivos, $numeroChamado);
            }

            // Iniciar transação
            $this->db->getConnection()->beginTransaction();

            // Inserir chamado
            $stmt = $this->db->prepare("
                INSERT INTO chamados (
                    numero_chamado, filial_id, setor_id, tipo_prioridade,
                    codigo_acesso_remoto, email_contato, telefone_contato,
                    descricao, usuario_abertura_id
                ) VALUES (
                    :numero_chamado, :filial_id, :setor_id, :tipo_prioridade,
                    :codigo_acesso_remoto, :email_contato, :telefone_contato,
                    :descricao, :usuario_abertura_id
                )
            ");

            $stmt->execute([
                ':numero_chamado' => $numeroChamado,
                ':filial_id' => $dados['filial_id'],
                ':setor_id' => $dados['setor_id'],
                ':tipo_prioridade' => $dados['tipo_prioridade'],
                ':codigo_acesso_remoto' => $dados['codigo_acesso_remoto'],
                ':email_contato' => $dados['email_contato'],
                ':telefone_contato' => $dados['telefone_contato'],
                ':descricao' => $dados['descricao'],
                ':usuario_abertura_id' => $_SESSION['usuario_id']
            ]);

            $chamadoId = $this->db->getConnection()->lastInsertId();

            // Registrar no histórico
            $stmt = $this->db->prepare("
                INSERT INTO historico_chamados (
                    chamado_id, usuario_id, status_chamado, descricao
                ) VALUES (
                    :chamado_id, :usuario_id, 'Aberto', 'Chamado aberto'
                )
            ");

            $stmt->execute([
                ':chamado_id' => $chamadoId,
                ':usuario_id' => $_SESSION['usuario_id']
            ]);

            // Adicionar caminhos dos arquivos ao banco de dados
            if (!empty($arquivosProcessados)) {
                foreach ($arquivosProcessados as $caminhoArquivo) {
                    $stmt = $this->db->prepare("
                        INSERT INTO arquivos_chamado (
                            chamado_id, caminho_arquivo
                        ) VALUES (
                            :chamado_id, :caminho_arquivo
                        )
                    ");
                    
                    $stmt->execute([
                        ':chamado_id' => $chamadoId,
                        ':caminho_arquivo' => $caminhoArquivo
                    ]);
                }
            }

            $this->db->getConnection()->commit();

            return [
                'success' => true,
                'message' => 'Chamado aberto com sucesso',
                'chamado_id' => $chamadoId
            ];

        } catch (Exception $e) {
            if ($this->db->getConnection()->inTransaction()) {
                $this->db->getConnection()->rollBack();
            }
            throw new Exception('Erro ao abrir chamado: ' . $e->getMessage());
        }
    }

    private function validarDados($dados) {
        $camposObrigatorios = ['filial_id', 'setor_id', 'tipo_prioridade', 'email_contato', 'descricao'];
        foreach ($camposObrigatorios as $campo) {
            if (empty($dados[$campo])) {
                throw new Exception("Campo {$campo} é obrigatório");
            }
        }

        if (!filter_var($dados['email_contato'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Email de contato inválido");
        }

        if (!in_array($dados['tipo_prioridade'], ['Normal', 'Urgente'])) {
            throw new Exception("Prioridade inválida");
        }
    }

    private function gerarNumeroChamado() {
        $data = date('Ymd');
        $stmt = $this->db->prepare("
            SELECT COUNT(*) + 1 as proximo
            FROM chamados 
            WHERE DATE(data_abertura) = CURDATE()
        ");
        $stmt->execute();
        $resultado = $stmt->fetch();
        $sequencial = str_pad($resultado['proximo'], 3, '0', STR_PAD_LEFT);
        
        return "OS_{$data}_{$sequencial}";
    }

    private function processarArquivos($arquivos, $numeroChamado) {
        $arquivosProcessados = [];
        
        // Criar diretório base para uploads
        $diretorioBase = __DIR__ . '/../../uploads/chamados/';
        if (!file_exists($diretorioBase)) {
            mkdir($diretorioBase, 0777, true);
        }

        // Criar diretório específico para o chamado
        $diretorioChamado = $diretorioBase . $numeroChamado . '/';
        if (!file_exists($diretorioChamado)) {
            mkdir($diretorioChamado, 0777, true);
        }

        // Extensões permitidas por tipo de arquivo
        $extensoesPermitidas = [
            'imagem' => ['jpg', 'jpeg', 'png'],
            'documento' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'txt']
        ];

        // Processar cada arquivo
        foreach ($arquivos['name'] as $index => $nome) {
            if ($arquivos['error'][$index] === UPLOAD_ERR_OK) {
                $extensao = strtolower(pathinfo($nome, PATHINFO_EXTENSION));
                $ehExtensaoPermitida = false;
                
                // Verificar se a extensão é permitida
                foreach ($extensoesPermitidas as $tipo => $extensoes) {
                    if (in_array($extensao, $extensoes)) {
                        $ehExtensaoPermitida = true;
                        break;
                    }
                }

                if (!$ehExtensaoPermitida) {
                    throw new Exception('Formato de arquivo não permitido: ' . $extensao);
                }

                // Gerar nome único para o arquivo
                $nomeArquivo = $numeroChamado . '_' . uniqid() . '.' . $extensao;
                $caminhoCompleto = $diretorioChamado . $nomeArquivo;

                // Mover o arquivo
                if (move_uploaded_file($arquivos['tmp_name'][$index], $caminhoCompleto)) {
                    $arquivosProcessados[] = 'uploads/chamados/' . $numeroChamado . '/' . $nomeArquivo;
                } else {
                    throw new Exception('Erro ao fazer upload do arquivo: ' . $nome);
                }
            }
        }

        return $arquivosProcessados;
    }

    public function getChamadoDetalhes($chamadoId) {
        try {
            // Buscar informações básicas do chamado
            $stmt = $this->db->prepare("
                SELECT 
                    c.*,
                    f.nome as filial_nome,
                    s.nome as setor_nome,
                    DATE_FORMAT(c.data_abertura, '%d/%m/%Y %H:%i') as data_abertura
                FROM chamados c
                JOIN filiais f ON c.filial_id = f.id
                JOIN setores s ON c.setor_id = s.id
                WHERE c.id = :chamado_id
            ");
            
            $stmt->execute([':chamado_id' => $chamadoId]);
            $chamado = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$chamado) {
                throw new Exception('Chamado não encontrado');
            }

            // Buscar arquivos anexados
            $stmt = $this->db->prepare("
                SELECT caminho_arquivo
                FROM arquivos_chamado
                WHERE chamado_id = :chamado_id
            ");
            $stmt->execute([':chamado_id' => $chamadoId]);
            $chamado['arquivos'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            return [
                'success' => true,
                'data' => $chamado
            ];
        } catch (Exception $e) {
            throw new Exception('Erro ao buscar detalhes do chamado: ' . $e->getMessage());
        }
    }
}

// Tratamento das requisições
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action'])) {
    $controller = new ControllerAbrirChamado();
    header('Content-Type: application/json');
    
    try {
        if ($_GET['action'] === 'getSetores' && isset($_GET['filial_id'])) {
            $setores = $controller->getSetores($_GET['filial_id']);
            echo json_encode($setores);
        } 
        else if ($_GET['action'] === 'getChamadoDetalhes' && isset($_GET['chamado_id'])) {
            $resultado = $controller->getChamadoDetalhes($_GET['chamado_id']);
            echo json_encode($resultado);
        }
    } catch (Exception $e) {
        http_response_code(500);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    session_start();
    
    if (!isset($_SESSION['usuario_id'])) {
        header('Location: /app_chamati/login.php?error=' . urlencode('Sessão expirada'));
        exit;
    }

    try {
        $controller = new ControllerAbrirChamado();
        $resultado = $controller->abrirChamado($_POST, $_FILES['arquivos'] ?? null);
        
        header('Location: /app_chamati/views/chamados/abrir_chamado.php?success=1&chamado_id=' . $resultado['chamado_id']);
    } catch (Exception $e) {
        header('Location: /app_chamati/views/chamados/abrir_chamado.php?error=' . urlencode($e->getMessage()));
    }
    exit;
}
