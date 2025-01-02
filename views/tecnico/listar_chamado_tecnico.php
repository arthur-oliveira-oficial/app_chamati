<?php
session_start();
require_once __DIR__ . '/../../database/conexaodb.php';

error_reporting(E_ALL);
ini_set('display_errors', 1);

// Verifica se usuário está logado e é Tecnico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();

// Processa as requisições AJAX
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Define o header JSON apenas para requisições AJAX
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        $action = $_GET['action'] ?? '';
        
        switch ($action) {
            case 'aceitar':
                $input = json_decode(file_get_contents('php://input'), true);
                if (!isset($input['chamado_id'])) {
                    throw new Exception('ID do chamado não fornecido');
                }

                $db->beginTransaction();

                // Verifica se o chamado já não está atribuído
                $stmt = $db->prepare("
                    SELECT status, tecnico_responsavel_id 
                    FROM chamados 
                    WHERE id = :chamado_id
                ");
                $stmt->bindParam(':chamado_id', $input['chamado_id']);
                $stmt->execute();
                $chamado = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$chamado) {
                    throw new Exception('Chamado não encontrado');
                }

                if ($chamado['tecnico_responsavel_id']) {
                    throw new Exception('Este chamado já está atribuído a um técnico');
                }

                // Atualiza o chamado
                $stmt = $db->prepare("
                    UPDATE chamados 
                    SET tecnico_responsavel_id = :tecnico_id,
                        status = 'Em Progresso',
                        atualizado_em = NOW()
                    WHERE id = :chamado_id
                ");

                $stmt->bindParam(':tecnico_id', $_SESSION['usuario_id']);
                $stmt->bindParam(':chamado_id', $input['chamado_id']);
                $stmt->execute();

                // Registra no histórico
                $stmt = $db->prepare("
                    INSERT INTO historico_chamados 
                    (chamado_id, usuario_id, status_chamado, descricao)
                    VALUES 
                    (:chamado_id, :usuario_id, 'Em Progresso', 'Chamado aceito pelo técnico')
                ");

                $stmt->bindParam(':chamado_id', $input['chamado_id']);
                $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
                $stmt->execute();

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Chamado aceito com sucesso']);
                break;

            case 'transferir':
                if (!isset($_POST['chamado_id']) || !isset($_POST['tecnico_id']) || !isset($_POST['motivo'])) {
                    throw new Exception('Dados incompletos');
                }

                $db->beginTransaction();

                // Atualiza o chamado
                $stmt = $db->prepare("
                    UPDATE chamados 
                    SET tecnico_responsavel_id = :tecnico_id,
                        data_transferencia = NOW(),
                        atualizado_em = NOW()
                    WHERE id = :chamado_id
                ");

                $stmt->bindParam(':tecnico_id', $_POST['tecnico_id']);
                $stmt->bindParam(':chamado_id', $_POST['chamado_id']);
                $stmt->execute();

                // Registra no histórico
                $stmt = $db->prepare("
                    INSERT INTO historico_chamados 
                    (chamado_id, usuario_id, status_chamado, descricao)
                    VALUES 
                    (:chamado_id, :usuario_id, 'Em Progresso', :descricao)
                ");

                $descricao = "Chamado transferido. Motivo: " . $_POST['motivo'];
                $stmt->bindParam(':chamado_id', $_POST['chamado_id']);
                $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
                $stmt->bindParam(':descricao', $descricao);
                $stmt->execute();

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Chamado transferido com sucesso']);
                break;

            case 'fechar':
                if (!isset($_POST['chamado_id']) || !isset($_POST['solucao'])) {
                    throw new Exception('Dados incompletos');
                }

                $db->beginTransaction();

                // Atualiza o status do chamado
                $stmt = $db->prepare("
                    UPDATE chamados 
                    SET status = 'Fechado',
                        data_fechamento = NOW(),
                        atualizado_em = NOW()
                    WHERE id = :chamado_id
                ");

                $stmt->bindParam(':chamado_id', $_POST['chamado_id']);
                $stmt->execute();

                // Registra no histórico
                $stmt = $db->prepare("
                    INSERT INTO historico_chamados 
                    (chamado_id, usuario_id, status_chamado, descricao)
                    VALUES 
                    (:chamado_id, :usuario_id, 'Fechado', :descricao)
                ");

                $stmt->bindParam(':chamado_id', $_POST['chamado_id']);
                $stmt->bindParam(':usuario_id', $_SESSION['usuario_id']);
                $stmt->bindParam(':descricao', $_POST['solucao']);
                $stmt->execute();

                $db->commit();
                echo json_encode(['success' => true, 'message' => 'Chamado fechado com sucesso']);
                break;

            case 'get_tecnicos':
                $stmt = $db->prepare("
                    SELECT id, nome 
                    FROM usuarios 
                    WHERE tipo = 'Tecnico' 
                    AND status = 'Ativo' 
                    AND id != :usuario_atual_id
                    ORDER BY nome
                ");
                $stmt->bindParam(':usuario_atual_id', $_SESSION['usuario_id']);
                $stmt->execute();
                $tecnicos = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($tecnicos);
                break;

            default:
                throw new Exception('Ação inválida');
        }
    } catch (Exception $e) {
        if (isset($db)) {
            $db->rollBack();
        }
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// No início do arquivo, após session_start()
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['action']) && $_GET['action'] === 'get_historico') {
    header('Content-Type: application/json; charset=utf-8');
    
    try {
        if (!isset($_GET['chamado_id'])) {
            throw new Exception('ID do chamado não fornecido');
        }
        
        $stmt = $db->prepare("
            SELECT 
                h.status_chamado,
                h.descricao,
                h.data_acao,
                u.nome as usuario_nome
            FROM historico_chamados h
            JOIN usuarios u ON h.usuario_id = u.id
            WHERE h.chamado_id = :chamado_id
            ORDER BY h.data_acao DESC
        ");
        $stmt->bindParam(':chamado_id', $_GET['chamado_id']);
        $stmt->execute();
        $historico = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo json_encode($historico);
        exit;
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
        exit;
    }
}

// Buscar todos os chamados (para exibição na página)
$where_conditions = [];
$params = [];

// Por padrão, excluir chamados fechados a menos que seja especificado no filtro
if (!isset($_GET['status']) || $_GET['status'] === '') {
    $where_conditions[] = "c.status != 'Fechado'";
} else {
    $where_conditions[] = "c.status = :status";
    $params[':status'] = $_GET['status'];
}

if (isset($_GET['prioridade']) && $_GET['prioridade'] !== '') {
    $where_conditions[] = "c.tipo_prioridade = :prioridade";
    $params[':prioridade'] = $_GET['prioridade'];
}

if (isset($_GET['filial']) && $_GET['filial'] !== '') {
    $where_conditions[] = "c.filial_id = :filial";
    $params[':filial'] = $_GET['filial'];
}

if (isset($_GET['setor']) && $_GET['setor'] !== '') {
    $where_conditions[] = "c.setor_id = :setor";
    $params[':setor'] = $_GET['setor'];
}

// Adicionar filtros de data
if (isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '') {
    $where_conditions[] = "DATE(c.data_abertura) >= :data_inicio";
    $params[':data_inicio'] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && $_GET['data_fim'] !== '') {
    $where_conditions[] = "DATE(c.data_abertura) <= :data_fim";
    $params[':data_fim'] = $_GET['data_fim'];
}

// Adicionar no início do arquivo, junto com os outros filtros
if (isset($_GET['numero_os']) && $_GET['numero_os'] !== '') {
    $where_conditions[] = "c.numero_chamado LIKE :numero_os";
    $params[':numero_os'] = '%' . $_GET['numero_os'] . '%';
}

try {
    // Buscar filiais e setores para os filtros
    $stmt = $db->prepare("SELECT id, nome FROM filiais ORDER BY nome");
    $stmt->execute();
    $filiais = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $stmt = $db->prepare("
        SELECT s.id, s.nome, f.nome as filial_nome 
        FROM setores s
        JOIN filiais f ON s.filial_id = f.id 
        ORDER BY f.nome, s.nome
    ");
    $stmt->execute();
    $setores = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Construir a query dos chamados
    $query = "
        SELECT 
            c.id,
            c.numero_chamado,
            c.tipo_prioridade,
            c.status,
            c.data_abertura,
            c.tecnico_responsavel_id,
            f.nome as filial_nome,
            s.nome as setor_nome,
            ua.nome as nome_solicitante,
            tr.nome as nome_tecnico,
            c.descricao
        FROM chamados c
        LEFT JOIN filiais f ON c.filial_id = f.id
        LEFT JOIN setores s ON c.setor_id = s.id
        LEFT JOIN usuarios ua ON c.usuario_abertura_id = ua.id
        LEFT JOIN usuarios tr ON c.tecnico_responsavel_id = tr.id
    ";

    // Adicionar condições WHERE se existirem
    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }

    // Adicionar ordenação
    $query .= " ORDER BY 
        DATE(c.data_abertura) DESC,
        CASE 
            WHEN c.status = 'Aberto' THEN 1
            WHEN c.status = 'Em Progresso' THEN 2
            ELSE 3
        END,
        c.tipo_prioridade DESC,
        c.data_abertura DESC
    ";

    $stmt = $db->prepare($query);
    foreach ($params as $key => $value) {
        $stmt->bindValue($key, $value);
    }
    $stmt->execute();
    $chamados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    // Log do erro
    error_log("Erro no banco de dados: " . $e->getMessage());
    
    // Mensagem amigável para o usuário
    $erro = "Ocorreu um erro ao carregar os dados. Por favor, tente novamente mais tarde.";
    
    // Você pode decidir se quer mostrar o erro real em ambiente de desenvolvimento
    if ($_SERVER['SERVER_NAME'] === 'localhost') {
        $erro .= "<br>Erro: " . $e->getMessage();
    }
    
    die($erro);
} catch (Exception $e) {
    error_log("Erro geral: " . $e->getMessage());
    die("Ocorreu um erro inesperado. Por favor, tente novamente mais tarde.");
}

// Após debug, voltar para:
// error_reporting(E_ALL);
// ini_set('display_errors', 0);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de Chamados - CHAMATI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/listar_chamado_tecnico.css">
    <style>
        .arquivos-list {
            margin-top: 10px;
        }

        .arquivo-item {
            display: flex;
            align-items: center;
            padding: 8px;
            border: 1px solid #dee2e6;
            border-radius: 4px;
            margin-bottom: 8px;
            background-color: #f8f9fa;
        }

        .arquivo-item i {
            margin-right: 10px;
            font-size: 1.2em;
        }

        .arquivo-item a {
            color: #0056b3;
            text-decoration: none;
        }

        .arquivo-item a:hover {
            text-decoration: underline;
        }

        .preview-image {
            max-width: 100%;
            max-height: 200px;
            margin-top: 5px;
            border-radius: 4px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="chamado-container">
                <h2 class="text-center mb-4 fs-4 text-primary">
                    <i class="bi bi-ticket-detailed me-2"></i>Lista de Chamados
                </h2>

                <!-- Card de Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">Filtros de Busca</h5>
                        </div>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label for="numero_os" class="form-label">Número da OS</label>
                                <div class="input-group">
                                    <input type="text" 
                                           class="form-control" 
                                           id="numero_os" 
                                           name="numero_os" 
                                           placeholder="Buscar OS..."
                                           value="<?php echo isset($_GET['numero_os']) ? htmlspecialchars($_GET['numero_os']) : ''; ?>">
                                    <button class="btn btn-outline-secondary" type="submit">
                                        <i class="bi bi-search"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status">
                                    <option value="">Todos</option>
                                    <option value="Aberto" <?php echo isset($_GET['status']) && $_GET['status'] === 'Aberto' ? 'selected' : ''; ?>>Aberto</option>
                                    <option value="Em Progresso" <?php echo isset($_GET['status']) && $_GET['status'] === 'Em Progresso' ? 'selected' : ''; ?>>Em Progresso</option>
                                    <option value="Fechado" <?php echo isset($_GET['status']) && $_GET['status'] === 'Fechado' ? 'selected' : ''; ?>>Fechado</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="prioridade" class="form-label">Prioridade</label>
                                <select class="form-select" id="prioridade" name="prioridade">
                                    <option value="">Todas</option>
                                    <option value="Normal" <?php echo isset($_GET['prioridade']) && $_GET['prioridade'] === 'Normal' ? 'selected' : ''; ?>>Normal</option>
                                    <option value="Urgente" <?php echo isset($_GET['prioridade']) && $_GET['prioridade'] === 'Urgente' ? 'selected' : ''; ?>>Urgente</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="filial" class="form-label">Filial</label>
                                <select class="form-select" id="filial" name="filial">
                                    <option value="">Todas</option>
                                    <?php foreach ($filiais as $filial): ?>
                                        <option value="<?php echo $filial['id']; ?>" <?php echo isset($_GET['filial']) && $_GET['filial'] == $filial['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($filial['nome']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="setor" class="form-label">Setor</label>
                                <select class="form-select" id="setor" name="setor">
                                    <option value="">Todos</option>
                                    <?php foreach ($setores as $setor): ?>
                                        <option value="<?php echo $setor['id']; ?>" <?php echo isset($_GET['setor']) && $_GET['setor'] == $setor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($setor['nome'] . ' (' . $setor['filial_nome'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" class="form-control" id="data_inicio" name="data_inicio" 
                                    value="<?php echo isset($_GET['data_inicio']) ? $_GET['data_inicio'] : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" class="form-control" id="data_fim" name="data_fim"
                                    value="<?php echo isset($_GET['data_fim']) ? $_GET['data_fim'] : ''; ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-filter"></i> Filtrar
                                </button>
                                <a href="listar_chamado_tecnico.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle"></i> Limpar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Card da Lista de Chamados -->
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">Chamados</h5>
                        </div>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="chamados-grid">
                            <?php
                            $data_atual = null;
                            foreach ($chamados as $chamado):
                                $data_chamado = date('Y-m-d', strtotime($chamado['data_abertura']));
                                
                                if ($data_chamado !== $data_atual):
                            ?>
                                <h5 class="data-separator">
                                    <?php 
                                    $data_formatada = date('d/m/Y', strtotime($data_chamado));
                                    $dia_semana = date('w', strtotime($data_chamado));
                                    $dias = ['Domingo', 'Segunda-feira', 'Terça-feira', 'Quarta-feira', 'Quinta-feira', 'Sexta-feira', 'Sábado'];
                                    echo $dias[$dia_semana] . ' - ' . $data_formatada;
                                    ?>
                                </h5>
                            <?php 
                                $data_atual = $data_chamado;
                                endif; 
                            ?>
                            
                            <div class="chamado-card">
                                <div class="chamado-card__header">
                                    <span class="chamado-card__number">#<?php echo htmlspecialchars($chamado['numero_chamado']); ?></span>
                                    <div class="chamado-card__badges">
                                        <span class="badge bg-<?php 
                                            echo $chamado['status'] === 'Aberto' ? 'danger' : 
                                                ($chamado['status'] === 'Em Progresso' ? 'warning' : 'success'); 
                                        ?>">
                                            <?php echo htmlspecialchars($chamado['status']); ?>
                                        </span>
                                        <span class="badge bg-<?php echo $chamado['tipo_prioridade'] === 'Urgente' ? 'danger' : 'primary'; ?>">
                                            <?php echo htmlspecialchars($chamado['tipo_prioridade']); ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="chamado-card__body">
                                    <div class="chamado-card__info">
                                        <p><i class="bi bi-building"></i> <?php echo htmlspecialchars($chamado['filial_nome']); ?></p>
                                        <p><i class="bi bi-diagram-3"></i> <?php echo htmlspecialchars($chamado['setor_nome']); ?></p>
                                        <p><i class="bi bi-person"></i> <?php echo htmlspecialchars($chamado['nome_solicitante']); ?></p>
                                        <p>
                                            <i class="bi bi-clock"></i> 
                                            <?php echo date('H:i', strtotime($chamado['data_abertura'])); ?>
                                        </p>
                                    </div>
                                    
                                    <div class="chamado-card__tech">
                                        <i class="bi bi-person-gear"></i>
                                        <?php echo htmlspecialchars($chamado['nome_tecnico'] ?? 'Não atribuído'); ?>
                                    </div>
                                </div>
                                
                                <div class="chamado-card__footer">
                                    <button type="button" 
                                            class="btn btn-primary btn-sm visualizar-chamado"
                                            data-bs-toggle="modal" 
                                            data-bs-target="#chamadoModal" 
                                            data-chamado='<?php echo json_encode($chamado); ?>'>
                                        <i class="bi bi-eye"></i> Visualizar
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (empty($chamados)): ?>
                                <div class="no-chamados">
                                    <i class="bi bi-inbox"></i>
                                    <p>Nenhum chamado encontrado</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal do Chamado -->
    <div class="modal fade" id="chamadoModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">Detalhes do Chamado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="chamadoDetalhes"></div>
                    
                    <!-- Container do Histórico -->
                    <div class="mt-4">
                        <h6 class="fw-bold text-dark">Histórico do Chamado</h6>
                        <div id="historicoContainer" class="mt-3">
                            <!-- O histórico será inserido aqui via JavaScript -->
                        </div>
                    </div>

                    <!-- Container das Ações -->
                    <div class="mt-4">
                        <h6 class="fw-bold text-dark">Ações do Chamado</h6>
                        <div class="d-flex gap-2 mt-3" id="acoesContainer">
                            <!-- Botões serão inseridos dinamicamente aqui -->
                        </div>
                    </div>
                    
                    <!-- Formulário de Transferência -->
                    <div class="collapse mt-3" id="transferirForm">
                        <div class="card card-body">
                            <h6 class="fw-bold text-dark">Transferir Chamado</h6>
                            <form id="formTransferir">
                                <input type="hidden" id="chamadoIdTransferir" name="chamado_id">
                                <div class="mb-3">
                                    <label for="tecnicoId" class="form-label fw-bold text-dark">Técnico</label>
                                    <select class="form-select" id="tecnicoId" name="tecnico_id" required>
                                        <option value="">Selecione um técnico</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="motivo" class="form-label fw-bold text-dark">Motivo da Transferência</label>
                                    <textarea class="form-control" id="motivo" name="motivo" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Confirmar Transferência</button>
                            </form>
                        </div>
                    </div>
                    
                    <!-- Formulário de Fechamento -->
                    <div class="collapse mt-3" id="fecharForm">
                        <div class="card card-body">
                            <h6 class="fw-bold text-dark">Fechar Chamado</h6>
                            <form id="formFechar">
                                <input type="hidden" id="chamadoIdFechar" name="chamado_id">
                                <div class="mb-3">
                                    <label for="solucao" class="form-label fw-bold text-dark">Solução Aplicada</label>
                                    <textarea class="form-control" id="solucao" name="solucao" rows="3" required></textarea>
                                </div>
                                <button type="submit" class="btn btn-primary">Confirmar Fechamento</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('chamadoModal');
        let chamadoAtual = null;

        // Função para formatar data
        function formatarData(dataString) {
            const data = new Date(dataString);
            return data.toLocaleString('pt-BR');
        }

        // Função para formatar data
        function formatarDataHora(dataString) {
            const data = new Date(dataString);
            return data.toLocaleString('pt-BR');
        }

        // Função para renderizar os botões de ação baseado no status do chamado
        function renderizarBotoesAcao(chamado) {
            const acoesContainer = document.getElementById('acoesContainer');
            if (!acoesContainer) {
                console.error('Container de ações não encontrado');
                return;
            }
            
            acoesContainer.innerHTML = '';

            // Verifica se o chamado está aberto e sem técnico responsável
            if (chamado.status === 'Aberto' && !chamado.nome_tecnico) {
                const btnAceitar = document.createElement('button');
                btnAceitar.className = 'btn btn-success';
                btnAceitar.innerHTML = '<i class="bi bi-check-circle"></i> Aceitar Chamado';
                btnAceitar.onclick = () => aceitarChamado(chamado.id);
                acoesContainer.appendChild(btnAceitar);
            }

            // Botões para chamados em andamento
            if (chamado.status === 'Em Progresso' && chamado.nome_tecnico) {
                const btnTransferir = document.createElement('button');
                btnTransferir.className = 'btn btn-warning me-2';
                btnTransferir.setAttribute('data-bs-toggle', 'collapse');
                btnTransferir.setAttribute('data-bs-target', '#transferirForm');
                btnTransferir.innerHTML = '<i class="bi bi-arrow-left-right"></i> Transferir';
                btnTransferir.onclick = () => carregarTecnicos();
                acoesContainer.appendChild(btnTransferir);

                const btnFechar = document.createElement('button');
                btnFechar.className = 'btn btn-danger';
                btnFechar.setAttribute('data-bs-toggle', 'collapse');
                btnFechar.setAttribute('data-bs-target', '#fecharForm');
                btnFechar.innerHTML = '<i class="bi bi-x-circle"></i> Fechar Chamado';
                acoesContainer.appendChild(btnFechar);
            }
        }

        // Função para aceitar chamado
        function aceitarChamado(chamadoId) {
            if (!chamadoId) {
                console.error('ID do chamado não fornecido');
                return;
            }

            fetch('/app_chamati/views/tecnico/listar_chamado_tecnico.php?action=aceitar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    chamado_id: chamadoId
                })
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Erro no servidor');
                        }
                        return data;
                    });
                } else {
                    return response.text().then(text => {
                        console.error('Resposta não-JSON recebida:', text);
                        throw new Error('Resposta inválida do servidor');
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao aceitar chamado');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao aceitar chamado: ' + error.message);
            });
        }

        // Event listener para abrir o modal
        document.querySelectorAll('.visualizar-chamado').forEach(button => {
            button.addEventListener('click', function() {
                const chamadoData = JSON.parse(this.getAttribute('data-chamado'));
                chamadoAtual = chamadoData;
                
                console.log('Dados do chamado:', chamadoData); // Para debug

                // Atualizar IDs nos formulários
                document.getElementById('chamadoIdTransferir').value = chamadoData.id;
                document.getElementById('chamadoIdFechar').value = chamadoData.id;

                // Renderizar detalhes do chamado
                const detalhesHtml = `
                    <div class="card">
                        <div class="card-body">
                            <h6 class="card-subtitle mb-2 fw-bold text-dark">Chamado #${chamadoData.numero_chamado}</h6>
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Filial:</strong> ${chamadoData.filial_nome}</p>
                                    <p><strong>Setor:</strong> ${chamadoData.setor_nome}</p>
                                    <p><strong>Solicitante:</strong> ${chamadoData.nome_solicitante}</p>
                                    <p><strong>Prioridade:</strong> 
                                        <span class="badge bg-${chamadoData.tipo_prioridade === 'Urgente' ? 'danger' : 'primary'}">
                                            ${chamadoData.tipo_prioridade}
                                        </span>
                                    </p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Status:</strong> 
                                        <span class="badge bg-${
                                            chamadoData.status === 'Aberto' ? 'danger' : 
                                            chamadoData.status === 'Em Progresso' ? 'warning' : 'success'
                                        }">
                                            ${chamadoData.status}
                                        </span>
                                    </p>
                                    <p><strong>Data de Abertura:</strong> ${formatarData(chamadoData.data_abertura)}</p>
                                    <p><strong>Técnico Responsável:</strong> ${chamadoData.nome_tecnico || 'Não atribuído'}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p><strong>Descrição:</strong></p>
                                <p class="text-break">${chamadoData.descricao}</p>
                            </div>
                            <div class="mt-3" id="arquivosContainer">
                                <h6 class="fw-bold text-dark">Arquivos Anexados:</h6>
                                <div class="arquivos-list" id="arquivosList"></div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.getElementById('chamadoDetalhes').innerHTML = detalhesHtml;
               
                // Buscar e exibir os arquivos anexados
                fetch(`/app_chamati/controller/chamados/controller_abrir_chamado.php?action=getChamadoDetalhes&chamado_id=${chamadoData.id}`)
                    .then(response => response.json())
                    .then(response => {
                        if (response.success && response.data.arquivos) {
                            const arquivosList = document.getElementById('arquivosList');
                            arquivosList.innerHTML = '';
                            
                            if (response.data.arquivos.length > 0) {
                                response.data.arquivos.forEach(arquivo => {
                                    const extensao = arquivo.split('.').pop().toLowerCase();
                                    let icone = 'bi-file-earmark';
                                    let previewHtml = '';
                                    
                                    // Definir ícone baseado no tipo de arquivo
                                    if (['jpg', 'jpeg', 'png'].includes(extensao)) {
                                        icone = 'bi-file-earmark-image';
                                        previewHtml = `<img src="/app_chamati/${arquivo}" class="preview-image" alt="Preview">`;
                                    } else if (extensao === 'pdf') {
                                        icone = 'bi-file-earmark-pdf';
                                    } else if (['doc', 'docx'].includes(extensao)) {
                                        icone = 'bi-file-earmark-word';
                                    } else if (['xls', 'xlsx'].includes(extensao)) {
                                        icone = 'bi-file-earmark-excel';
                                    } else if (extensao === 'txt') {
                                        icone = 'bi-file-earmark-text';
                                    }

                                    const nomeArquivo = arquivo.split('/').pop();
                                    const html = `
                                        <div class="arquivo-item">
                                            <i class="bi ${icone}"></i>
                                            <a href="/app_chamati/${arquivo}" target="_blank">${nomeArquivo}</a>
                                            ${previewHtml}
                                        </div>
                                    `;
                                    arquivosList.innerHTML += html;
                                });
                                document.getElementById('arquivosContainer').style.display = 'block';
                            } else {
                                document.getElementById('arquivosContainer').style.display = 'none';
                            }
                        }
                    })
                    .catch(error => {
                        console.error('Erro ao carregar arquivos:', error);
                    });

                renderizarBotoesAcao(chamadoData);

                // Carrega os técnicos apenas se necessário
                if (chamadoData.status === 'Em Progresso' && chamadoData.nome_tecnico) {
                    carregarTecnicos();
                }

                // Carregar o histórico do chamado
                carregarHistorico(chamadoData.id);
            });
        });

        // Event listeners para os formulários
        document.getElementById('formTransferir').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('/app_chamati/views/tecnico/listar_chamado_tecnico.php?action=transferir', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Erro no servidor');
                        }
                        return data;
                    });
                } else {
                    return response.text().then(text => {
                        console.error('Resposta não-JSON recebida:', text);
                        throw new Error('Resposta inválida do servidor');
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao transferir chamado');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao transferir chamado: ' + error.message);
            });
        });

        document.getElementById('formFechar').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            
            fetch('/app_chamati/views/tecnico/listar_chamado_tecnico.php?action=fechar', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                const contentType = response.headers.get('content-type');
                if (contentType && contentType.includes('application/json')) {
                    return response.json().then(data => {
                        if (!response.ok) {
                            throw new Error(data.message || 'Erro no servidor');
                        }
                        return data;
                    });
                } else {
                    return response.text().then(text => {
                        console.error('Resposta não-JSON recebida:', text);
                        throw new Error('Resposta inválida do servidor');
                    });
                }
            })
            .then(data => {
                if (data.success) {
                    alert(data.message);
                    window.location.reload();
                } else {
                    throw new Error(data.message || 'Erro ao fechar chamado');
                }
            })
            .catch(error => {
                console.error('Erro:', error);
                alert('Erro ao fechar chamado: ' + error.message);
            });
        });

        function carregarTecnicos() {
            fetch('/app_chamati/views/tecnico/listar_chamado_tecnico.php?action=get_tecnicos', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(tecnicos => {
                const select = document.getElementById('tecnicoId');
                select.innerHTML = '<option value="">Selecione um técnico</option>';
                tecnicos.forEach(tecnico => {
                    select.innerHTML += `<option value="${tecnico.id}">${tecnico.nome}</option>`;
                });
            })
            .catch(error => {
                console.error('Erro ao carregar técnicos:', error);
                alert('Erro ao carregar lista de técnicos');
            });
        }

        // Função para carregar o histórico do chamado
        function carregarHistorico(chamadoId) {
            console.log('Carregando histórico para chamado:', chamadoId);

            if (!chamadoId) {
                console.error('ID do chamado não fornecido para carregamento do histórico');
                return;
            }

            const historicoContainer = document.getElementById('historicoContainer');
            if (!historicoContainer) {
                console.error('Container do histórico não encontrado no DOM');
                return;
            }

            // Mostrar loading
            historicoContainer.innerHTML = '<p class="text-muted">Carregando histórico...</p>';

            // Adicionar timestamp para evitar cache
            const timestamp = new Date().getTime();
            
            fetch(`/app_chamati/views/tecnico/listar_chamado_tecnico.php?action=get_historico&chamado_id=${chamadoId}&_=${timestamp}`)
                .then(response => {
                    console.log('Resposta recebida:', response);
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(historico => {
                    console.log('Histórico recebido:', historico);
                    
                    if (historico.error) {
                        throw new Error(historico.error);
                    }

                    if (!Array.isArray(historico)) {
                        throw new Error('Formato de resposta inválido');
                    }

                    if (historico.length === 0) {
                        historicoContainer.innerHTML = '<p class="text-muted">Nenhum registro encontrado no histórico.</p>';
                        return;
                    }

                    const historicoHtml = historico.map(registro => `
                        <div class="list-group-item">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1">${registro.usuario_nome || 'Usuário não identificado'}</h6>
                                <small class="text-muted">${formatarDataHora(registro.data_acao)}</small>
                            </div>
                            <p class="mb-1">Status: <span class="badge bg-${
                                registro.status_chamado === 'Aberto' ? 'danger' : 
                                registro.status_chamado === 'Em Progresso' ? 'warning' : 'success'
                            }">${registro.status_chamado}</span></p>
                            <p class="mb-0">${registro.descricao}</p>
                        </div>
                    `).join('');

                    historicoContainer.innerHTML = `
                        <div class="list-group">
                            ${historicoHtml}
                        </div>
                    `;
                })
                .catch(error => {
                    console.error('Erro ao carregar histórico:', error);
                    historicoContainer.innerHTML = `
                        <div class="alert alert-danger" role="alert">
                            <i class="bi bi-exclamation-circle"></i> 
                            Erro ao carregar o histórico: ${error.message}
                        </div>
                    `;
                });
        }
    });
    </script>
</body>
</html> 