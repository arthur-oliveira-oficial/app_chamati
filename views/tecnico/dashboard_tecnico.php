<?php
session_start();
require_once __DIR__ . '/../../database/conexaodb.php';

// Verifica se usuário está logado e é Tecnico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();

// Buscar estatísticas de chamados
$stmt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'Aberto' THEN 1 END) as abertos,
        COUNT(CASE WHEN status = 'Em Progresso' THEN 1 END) as em_andamento,
        COUNT(CASE WHEN data_transferencia IS NOT NULL THEN 1 END) as transferidos,
        COUNT(CASE WHEN status = 'Fechado' THEN 1 END) as fechados
    FROM chamados
");
$stmt->execute();
$estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar chamados por técnico
$stmt = $db->prepare("
    SELECT 
        u.nome as tecnico,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN usuarios u ON c.tecnico_responsavel_id = u.id
    WHERE u.tipo = 'Tecnico'
    GROUP BY u.id, u.nome
    ORDER BY total_chamados DESC
");
$stmt->execute();
$chamadosPorTecnico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chamados por filial (top 5)
$stmt = $db->prepare("
    SELECT 
        f.nome as filial,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN filiais f ON c.filial_id = f.id
    GROUP BY f.id, f.nome
    ORDER BY total_chamados DESC
    LIMIT 5
");
$stmt->execute();
$chamadosPorFilial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chamados por setor (top 5)
$stmt = $db->prepare("
    SELECT 
        s.nome as setor,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN setores s ON c.setor_id = s.id
    GROUP BY s.id, s.nome
    ORDER BY total_chamados DESC
    LIMIT 5
");
$stmt->execute();
$chamadosPorSetor = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chamados por usuário (top 5)
$stmt = $db->prepare("
    SELECT 
        u.nome as usuario,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN usuarios u ON c.usuario_abertura_id = u.id
    WHERE u.tipo = 'Funcionario'
    GROUP BY u.id, u.nome
    ORDER BY total_chamados DESC
    LIMIT 5
");
$stmt->execute();
$chamadosPorUsuario = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Técnico - CHAMATI</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/dashboard_tecnico.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <main class="dashboard">
        <div class="dashboard__header">
            <div class="container-fluid">
                <h1 class="dashboard__title">Dashboard</h1>
                <p class="dashboard__subtitle">Bem-vindo, <?php echo htmlspecialchars($_SESSION['usuario_nome']); ?></p>
            </div>
        </div>

        <div class="dashboard__content">
            <div class="container-fluid">
                <!-- Cards de Estatísticas -->
                <div class="row stats-grid">
                    <div class="col-6 col-md-3">
                        <div class="stats-card stats-card--primary">
                            <div class="stats-card__icon">
                                <i class="bi bi-ticket"></i>
                            </div>
                            <div class="stats-card__content">
                                <h3 class="stats-card__number"><?php echo $estatisticas['abertos']; ?></h3>
                                <p class="stats-card__label">Chamados Abertos</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="stats-card stats-card--warning">
                            <div class="stats-card__icon">
                                <i class="bi bi-clock-history"></i>
                            </div>
                            <div class="stats-card__content">
                                <h3 class="stats-card__number"><?php echo $estatisticas['em_andamento']; ?></h3>
                                <p class="stats-card__label">Em Andamento</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="stats-card stats-card--info">
                            <div class="stats-card__icon">
                                <i class="bi bi-arrow-repeat"></i>
                            </div>
                            <div class="stats-card__content">
                                <h3 class="stats-card__number"><?php echo $estatisticas['transferidos']; ?></h3>
                                <p class="stats-card__label">Transferidos</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-6 col-md-3">
                        <div class="stats-card stats-card--success">
                            <div class="stats-card__icon">
                                <i class="bi bi-check-circle"></i>
                            </div>
                            <div class="stats-card__content">
                                <h3 class="stats-card__number"><?php echo $estatisticas['fechados']; ?></h3>
                                <p class="stats-card__label">Fechados</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Gráficos -->
                <div class="row charts-grid">
                    <div class="col-12 col-lg-6">
                        <div class="chart-card">
                            <div class="chart-card__header">
                                <h4 class="chart-card__title">Chamados por Técnico</h4>
                            </div>
                            <div class="chart-card__body">
                                <canvas id="chamadosPorTecnico"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="chart-card">
                            <div class="chart-card__header">
                                <h4 class="chart-card__title">Top 5 - Chamados por Filial</h4>
                            </div>
                            <div class="chart-card__body">
                                <canvas id="chamadosPorFilial"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="chart-card">
                            <div class="chart-card__header">
                                <h4 class="chart-card__title">Top 5 - Chamados por Setor</h4>
                            </div>
                            <div class="chart-card__body">
                                <canvas id="chamadosPorSetor"></canvas>
                            </div>
                        </div>
                    </div>

                    <div class="col-12 col-lg-6">
                        <div class="chart-card">
                            <div class="chart-card__header">
                                <h4 class="chart-card__title">Top 5 - Chamados por Usuário</h4>
                            </div>
                            <div class="chart-card__body">
                                <canvas id="chamadosPorUsuario"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dados para os gráficos
        const chamadosPorTecnico = <?php echo json_encode($chamadosPorTecnico); ?>;
        const chamadosPorFilial = <?php echo json_encode($chamadosPorFilial); ?>;
        const chamadosPorSetor = <?php echo json_encode($chamadosPorSetor); ?>;
        const chamadosPorUsuario = <?php echo json_encode($chamadosPorUsuario); ?>;

        // Configuração dos gráficos
        const configGraficos = {
            type: 'bar',
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1
                        }
                    }
                }
            }
        };

        // Gráfico de Chamados por Técnico
        new Chart(document.getElementById('chamadosPorTecnico'), {
            ...configGraficos,
            data: {
                labels: chamadosPorTecnico.map(item => item.tecnico),
                datasets: [{
                    data: chamadosPorTecnico.map(item => item.total_chamados),
                    backgroundColor: '#3498db'
                }]
            }
        });

        // Gráfico de Chamados por Filial
        new Chart(document.getElementById('chamadosPorFilial'), {
            ...configGraficos,
            data: {
                labels: chamadosPorFilial.map(item => item.filial),
                datasets: [{
                    data: chamadosPorFilial.map(item => item.total_chamados),
                    backgroundColor: '#2ecc71'
                }]
            }
        });

        // Gráfico de Chamados por Setor
        new Chart(document.getElementById('chamadosPorSetor'), {
            ...configGraficos,
            data: {
                labels: chamadosPorSetor.map(item => item.setor),
                datasets: [{
                    data: chamadosPorSetor.map(item => item.total_chamados),
                    backgroundColor: '#e74c3c'
                }]
            }
        });

        // Gráfico de Chamados por Usuário
        new Chart(document.getElementById('chamadosPorUsuario'), {
            ...configGraficos,
            data: {
                labels: chamadosPorUsuario.map(item => item.usuario),
                datasets: [{
                    data: chamadosPorUsuario.map(item => item.total_chamados),
                    backgroundColor: '#9b59b6'
                }]
            }
        });
    </script>
</body>
</html>
