<?php
session_start();
require_once __DIR__ . '/../../controller/relatorio/controller_relatorio_chamado_tecnico.php';

// Verifica se usuário está logado e é Tecnico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$controller = new ControllerRelatorioChamadoTecnico();

// Processar exportação
if (isset($_POST['exportar'])) {
    $filtros = [
        'data_inicio' => $_POST['data_inicio'] ?? null,
        'data_fim' => $_POST['data_fim'] ?? null,
        'status' => $_POST['status'] ?? null
    ];
    
    $chamados = $controller->getChamados($filtros);
    
    if ($_POST['formato'] === 'excel') {
        $controller->exportarExcel($chamados);
        exit;
    } elseif ($_POST['formato'] === 'pdf') {
        $controller->exportarPDF($chamados);
        exit;
    }
}

// Buscar chamados com filtros
$filtros = [
    'data_inicio' => $_GET['data_inicio'] ?? null,
    'data_fim' => $_GET['data_fim'] ?? null,
    'status' => $_GET['status'] ?? null
];

// Data atual para usar como valor padrão
$data_atual = date('Y-m-d');

try {
    $chamados = $controller->getChamados($filtros);
} catch (Exception $e) {
    $erro = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Relatório de Chamados - CHAMATI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/relatorio_chamado_tecnico.css">
</head>
<body class="bg-light">
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="relatorio-container">
                <h2 class="text-center mb-4 fs-4 text-primary">
                    <i class="bi bi-file-earmark-text me-2"></i>Relatório de Chamados
                </h2>

                <!-- Filtros -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Filtros</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Data Início</label>
                                <input type="date" 
                                       name="data_inicio" 
                                       class="form-control" 
                                       value="<?php echo $_GET['data_inicio'] ?? $data_atual; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Data Fim</label>
                                <input type="date" 
                                       name="data_fim" 
                                       class="form-control" 
                                       value="<?php echo $_GET['data_fim'] ?? $data_atual; ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Status</label>
                                <select name="status" class="form-select">
                                    <option value="">Todos</option>
                                    <option value="Aberto" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Aberto') ? 'selected' : ''; ?>>Aberto</option>
                                    <option value="Em Progresso" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Em Progresso') ? 'selected' : ''; ?>>Em Progresso</option>
                                    <option value="Fechado" <?php echo (isset($_GET['status']) && $_GET['status'] === 'Fechado') ? 'selected' : ''; ?>>Fechado</option>
                                </select>
                            </div>
                            <div class="col-md-3 d-flex align-items-end">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-search me-2"></i>Filtrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Exportação -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Exportar Relatório</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" class="row g-3">
                            <input type="hidden" name="data_inicio" value="<?php echo $_GET['data_inicio'] ?? ''; ?>">
                            <input type="hidden" name="data_fim" value="<?php echo $_GET['data_fim'] ?? ''; ?>">
                            <input type="hidden" name="status" value="<?php echo $_GET['status'] ?? ''; ?>">
                            
                            <div class="col-md-6">
                                <select name="formato" class="form-select" required>
                                    <option value="">Selecione o formato</option>
                                    <option value="excel">Excel</option>
                                    <option value="pdf">PDF</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <button type="submit" name="exportar" class="btn btn-success">
                                    <i class="bi bi-download me-2"></i>Exportar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Tabela de Resultados -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Resultados</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th scope="col">Número OS</th>
                                        <th scope="col">Filial</th>
                                        <th scope="col">Setor</th>
                                        <th scope="col">Solicitante</th>
                                        <th scope="col">Técnico</th>
                                        <th scope="col">Prioridade</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Data Abertura</th>
                                        <th scope="col">Data Fechamento</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (!empty($chamados)): ?>
                                        <?php foreach ($chamados as $chamado): ?>
                                            <tr class="<?php echo ($chamado['status'] === 'Aberto' && empty($chamado['nome_tecnico'])) ? 'table-warning' : ''; ?>">
                                                <td><?php echo htmlspecialchars($chamado['numero_chamado']); ?></td>
                                                <td><?php echo htmlspecialchars($chamado['filial_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($chamado['setor_nome']); ?></td>
                                                <td><?php echo htmlspecialchars($chamado['nome_solicitante']); ?></td>
                                                <td><?php echo htmlspecialchars($chamado['nome_tecnico'] ?? 'Não atribuído'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $chamado['tipo_prioridade'] === 'Urgente' ? 'danger' : 'primary'; ?>">
                                                        <?php echo htmlspecialchars($chamado['tipo_prioridade']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $chamado['status'] === 'Aberto' ? 'danger' : 
                                                            ($chamado['status'] === 'Em Progresso' ? 'warning' : 'success'); 
                                                    ?>">
                                                        <?php echo htmlspecialchars($chamado['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo date('d/m/Y H:i', strtotime($chamado['data_abertura'])); ?></td>
                                                <td><?php echo $chamado['data_fechamento'] ? date('d/m/Y H:i', strtotime($chamado['data_fechamento'])) : '-'; ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <tr>
                                            <td colspan="9" class="text-center">Nenhum chamado encontrado</td>
                                        </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
