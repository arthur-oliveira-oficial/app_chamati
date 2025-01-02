<?php
session_start();
require_once __DIR__ . '/../../database/conexaodb.php';

// Verifica se usuário está logado e é Funcionario
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Funcionario') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();
$usuarioId = $_SESSION['usuario_id'];

// Buscar estatísticas de chamados do usuário
$stmt = $db->prepare("
    SELECT 
        COUNT(CASE WHEN status = 'Aberto' THEN 1 END) as abertos,
        COUNT(CASE WHEN status = 'Em Progresso' THEN 1 END) as em_andamento,
        COUNT(CASE WHEN data_transferencia IS NOT NULL THEN 1 END) as transferidos,
        COUNT(CASE WHEN status = 'Fechado' THEN 1 END) as fechados
    FROM chamados
    WHERE usuario_abertura_id = :usuario_id
");
$stmt->execute([':usuario_id' => $usuarioId]);
$estatisticas = $stmt->fetch(PDO::FETCH_ASSOC);

// Buscar chamados por técnico que atendeu o usuário
$stmt = $db->prepare("
    SELECT 
        u.nome as tecnico,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN usuarios u ON c.tecnico_responsavel_id = u.id
    WHERE c.usuario_abertura_id = :usuario_id
    AND u.tipo = 'Tecnico'
    GROUP BY u.id, u.nome
    ORDER BY total_chamados DESC
");
$stmt->execute([':usuario_id' => $usuarioId]);
$chamadosPorTecnico = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chamados por filial do usuário
$stmt = $db->prepare("
    SELECT 
        f.nome as filial,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN filiais f ON c.filial_id = f.id
    WHERE c.usuario_abertura_id = :usuario_id
    GROUP BY f.id, f.nome
    ORDER BY total_chamados DESC
");
$stmt->execute([':usuario_id' => $usuarioId]);
$chamadosPorFilial = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar chamados por setor do usuário
$stmt = $db->prepare("
    SELECT 
        s.nome as setor,
        COUNT(*) as total_chamados
    FROM chamados c
    JOIN setores s ON c.setor_id = s.id
    WHERE c.usuario_abertura_id = :usuario_id
    GROUP BY s.id, s.nome
    ORDER BY total_chamados DESC
");
$stmt->execute([':usuario_id' => $usuarioId]);
$chamadosPorSetor = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar histórico de chamados do usuário por mês
$stmt = $db->prepare("
    SELECT 
        DATE_FORMAT(data_abertura, '%Y-%m') as mes,
        COUNT(*) as total_chamados
    FROM chamados
    WHERE usuario_abertura_id = :usuario_id
    GROUP BY DATE_FORMAT(data_abertura, '%Y-%m')
    ORDER BY mes DESC
    LIMIT 12
");
$stmt->execute([':usuario_id' => $usuarioId]);
$historicoMensal = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Funcionário - CHAMATI</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/dashboard_funcionario.css">
</head>
<body>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>

    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="row mb-4">
                <div class="col-12">
                    <h2 class="page-title">Meu Dashboard</h2>
                </div>
            </div>

            <!-- Cards de Estatísticas -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card bg-primary text-white">
                        <div class="card-body">
                            <h5>Abertos</h5>
                            <h2><?php echo $estatisticas['abertos']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-warning text-white">
                        <div class="card-body">
                            <h5>Em Andamento</h5>
                            <h2><?php echo $estatisticas['em_andamento']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-info text-white">
                        <div class="card-body">
                            <h5>Transferidos</h5>
                            <h2><?php echo $estatisticas['transferidos']; ?></h2>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card bg-success text-white">
                        <div class="card-body">
                            <h5>Fechados</h5>
                            <h2><?php echo $estatisticas['fechados']; ?></h2>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Gráficos -->
            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Chamados por Técnico</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chamadosPorTecnico"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Chamados por Filial</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chamadosPorFilial"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Chamados por Setor</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="chamadosPorSetor"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5>Histórico Mensal de Chamados</h5>
                        </div>
                        <div class="card-body">
                            <canvas id="historicoMensal"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dados para os gráficos
        const chamadosPorTecnico = <?php echo json_encode($chamadosPorTecnico); ?>;
        const chamadosPorFilial = <?php echo json_encode($chamadosPorFilial); ?>;
        const chamadosPorSetor = <?php echo json_encode($chamadosPorSetor); ?>;
        const historicoMensal = <?php echo json_encode($historicoMensal); ?>;

        // Configuração dos gráficos de barra
        const configBarras = {
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

        // Configuração do gráfico de linha
        const configLinha = {
            type: 'line',
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
            ...configBarras,
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
            ...configBarras,
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
            ...configBarras,
            data: {
                labels: chamadosPorSetor.map(item => item.setor),
                datasets: [{
                    data: chamadosPorSetor.map(item => item.total_chamados),
                    backgroundColor: '#e74c3c'
                }]
            }
        });

        // Gráfico de Histórico Mensal
        new Chart(document.getElementById('historicoMensal'), {
            ...configLinha,
            data: {
                labels: historicoMensal.map(item => {
                    const [ano, mes] = item.mes.split('-');
                    return `${mes}/${ano}`;
                }),
                datasets: [{
                    data: historicoMensal.map(item => item.total_chamados),
                    borderColor: '#9b59b6',
                    tension: 0.1
                }]
            }
        });
    </script>
</body>
</html>
