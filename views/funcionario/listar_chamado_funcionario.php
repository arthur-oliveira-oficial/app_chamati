<?php
session_start();
require_once __DIR__ . '/../../database/conexaodb.php';

// Verifica se usuário está logado e NÃO é Tecnico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] === 'Tecnico') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();

// Adicione após o bloco de verificação de sessão, no início do arquivo
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

// Se for uma requisição AJAX para buscar detalhes do chamado
if (isset($_GET['action']) && $_GET['action'] === 'visualizar' && isset($_GET['id'])) {
    try {
        $stmt = $db->prepare("
            SELECT 
                c.*,
                f.nome as filial_nome,
                s.nome as setor_nome,
                ua.nome as nome_solicitante,
                tr.nome as nome_tecnico
            FROM chamados c
            LEFT JOIN filiais f ON c.filial_id = f.id
            LEFT JOIN setores s ON c.setor_id = s.id
            LEFT JOIN usuarios ua ON c.usuario_abertura_id = ua.id
            LEFT JOIN usuarios tr ON c.tecnico_responsavel_id = tr.id
            WHERE c.id = :id
        ");
        
        $stmt->bindParam(':id', $_GET['id'], PDO::PARAM_INT);
        $stmt->execute();
        
        $chamado = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        if ($chamado) {
            echo json_encode(['success' => true, 'chamado' => $chamado]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chamado não encontrado']);
        }
        exit();
    } catch (Exception $e) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'Erro ao buscar chamado: ' . $e->getMessage()]);
        exit();
    }
}

// Buscar apenas os chamados do usuário logado
$where_conditions = [];
$params = [];

// Por padrão, excluir chamados fechados a menos que seja especificado no filtro
if (!isset($_GET['status']) || $_GET['status'] === '') {
    $where_conditions[] = "c.status != 'Fechado'";
} else {
    $where_conditions[] = "c.status = :status";
    $params[':status'] = $_GET['status'];
}

// Adicionar condição para mostrar apenas chamados do usuário
$where_conditions[] = "c.usuario_abertura_id = :usuario_id";
$params[':usuario_id'] = $_SESSION['usuario_id'];

// Adicionar outros filtros
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

if (isset($_GET['data_inicio']) && $_GET['data_inicio'] !== '') {
    $where_conditions[] = "DATE(c.data_abertura) >= :data_inicio";
    $params[':data_inicio'] = $_GET['data_inicio'];
}

if (isset($_GET['data_fim']) && $_GET['data_fim'] !== '') {
    $where_conditions[] = "DATE(c.data_abertura) <= :data_fim";
    $params[':data_fim'] = $_GET['data_fim'];
}

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

    if (!empty($where_conditions)) {
        $query .= " WHERE " . implode(" AND ", $where_conditions);
    }

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
    error_log("Erro no banco de dados: " . $e->getMessage());
    $erro = "Ocorreu um erro ao carregar os dados. Por favor, tente novamente mais tarde.";
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Chamados - CHAMATI</title>
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
                    <i class="bi bi-ticket-detailed me-2"></i>Meus Chamados
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
                                           value="<?php echo isset($_GET['numero_os']) ? htmlspecialchars($_GET['numero_os']) : ''; ?>">
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
                                        <option value="<?php echo $filial['id']; ?>" 
                                                <?php echo isset($_GET['filial']) && $_GET['filial'] == $filial['id'] ? 'selected' : ''; ?>>
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
                                        <option value="<?php echo $setor['id']; ?>" 
                                                <?php echo isset($_GET['setor']) && $_GET['setor'] == $setor['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($setor['nome'] . ' (' . $setor['filial_nome'] . ')'); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label for="data_inicio" class="form-label">Data Início</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="data_inicio" 
                                       name="data_inicio"
                                       value="<?php echo isset($_GET['data_inicio']) ? htmlspecialchars($_GET['data_inicio']) : ''; ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="data_fim" class="form-label">Data Fim</label>
                                <input type="date" 
                                       class="form-control" 
                                       id="data_fim" 
                                       name="data_fim"
                                       value="<?php echo isset($_GET['data_fim']) ? htmlspecialchars($_GET['data_fim']) : ''; ?>">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Buscar
                                </button>
                                <a href="listar_chamado_funcionario.php" class="btn btn-secondary">
                                    <i class="bi bi-x-circle me-2"></i>Limpar Filtros
                                </a>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Lista de Chamados -->
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
                                        <p><i class="bi bi-clock"></i> <?php echo date('H:i', strtotime($chamado['data_abertura'])); ?></p>
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
                                            data-bs-target="#modalVisualizarChamado"
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

    <!-- Modal de Visualização -->
    <div class="modal fade" id="modalVisualizarChamado" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-dark">Detalhes do Chamado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="chamadoDetalhes"></div>
                    
                    <!-- Adicione esta seção para os arquivos -->
                    <div class="mt-4">
                        <h6 class="fw-bold text-dark">Arquivos Anexados</h6>
                        <div id="arquivosContainer" class="mt-3">
                            <div class="arquivos-list" id="arquivosList"></div>
                        </div>
                    </div>
                    
                    <!-- Seção do histórico existente -->
                    <div class="mt-4">
                        <h6 class="fw-bold text-dark">Histórico do Chamado</h6>
                        <div id="historicoContainer" class="mt-3">
                            <!-- O histórico será inserido aqui via JavaScript -->
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Função para formatar data e hora
        function formatarDataHora(dataString) {
            const data = new Date(dataString);
            return data.toLocaleString('pt-BR');
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
            
            fetch(`/app_chamati/views/funcionario/listar_chamado_funcionario.php?action=get_historico&chamado_id=${chamadoId}&_=${timestamp}`)
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(historico => {
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

        // Modifique o event listener existente do botão visualizar
        document.querySelectorAll('.visualizar-chamado').forEach(button => {
            button.addEventListener('click', function() {
                const chamadoData = JSON.parse(this.getAttribute('data-chamado'));
                
                // Renderizar detalhes do chamado (código existente)
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
                                    <p><strong>Data de Abertura:</strong> ${new Date(chamadoData.data_abertura).toLocaleString('pt-BR')}</p>
                                    <p><strong>Técnico Responsável:</strong> ${chamadoData.nome_tecnico || 'Não atribuído'}</p>
                                </div>
                            </div>
                            <div class="mt-3">
                                <p><strong>Descrição:</strong></p>
                                <p class="text-break">${chamadoData.descricao}</p>
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

                // Carregar o histórico do chamado
                carregarHistorico(chamadoData.id);
            });
        });
    });
    </script>
</body>
</html> 