<?php
session_start();
require_once __DIR__ . '/../../../database/conexaodb.php';

// Verifica se usuário está logado e é Tecnico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();

// Buscar todos os setores com nome da filial
$stmt = $db->prepare("
    SELECT s.id, s.nome as setor_nome, s.filial_id, f.nome as filial_nome 
    FROM setores s
    JOIN filiais f ON s.filial_id = f.id 
    ORDER BY f.nome, s.nome
");
$stmt->execute();
$setores = $stmt->fetchAll();

// Buscar filiais para o select do modal
$stmt = $db->prepare("SELECT id, nome FROM filiais ORDER BY nome");
$stmt->execute();
$filiais = $stmt->fetchAll();

// Verifica se há uma mensagem de retorno
$message = '';
$alert_type = '';
if (isset($_GET['success'])) {
    $message = 'Operação realizada com sucesso!';
    $alert_type = 'success';
} elseif (isset($_GET['error'])) {
    $message = $_GET['error'];
    $alert_type = 'danger';
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Gerenciamento de Setores - CHAMATI">
    <title>Gerenciar Setores - CHAMATI</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/gerenciar_setor.css">

    <style>
        /* Mobile-First Styles */
        .content-wrapper {
            position: relative;
            min-height: 100vh;
            padding: var(--spacing-xs);
            transition: padding-left 0.3s;
            z-index: 1;
        }

        .chamado-form-container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 20px;
        }

        /* Card Styles */
        .card {
            box-shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            border: none;
            border-radius: 10px;
        }

        .card-header {
            background-color: #fff;
            border-bottom: 1px solid rgba(0, 0, 0, 0.125);
            padding: 1rem;
        }

        .table-actions {
            display: flex;
            gap: 0.5rem;
            justify-content: flex-start;
        }

        .btn-sm {
            padding: 0.4rem;
            font-size: 0.875rem;
        }

        /* Ajustes para tabela responsiva em dispositivos móveis */
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 0.5rem 0;
                margin-left: var(--sidebar-width-mobile);
            }

            .container-fluid {
                padding: 0 0.5rem;
            }

            .chamado-form-container {
                margin: 1rem auto;
                padding: 10px;
            }

            .card {
                margin: 0 auto;
                border-radius: 0.5rem;
            }

            .table thead {
                display: none; /* Oculta o cabeçalho em dispositivos muito pequenos */
            }

            .table, .table tbody, .table tr, .table td {
                display: block;
                width: 100%;
            }

            .table tr {
                margin-bottom: 1rem;
                border: 1px solid #dee2e6;
                border-radius: 0.25rem;
                padding: 0.5rem;
            }

            .table td {
                text-align: left;
                padding: 0.5rem;
                position: relative;
                padding-left: 50%;
            }

            .table td:before {
                content: attr(data-label);
                position: absolute;
                left: 0.5rem;
                width: 45%;
                font-weight: bold;
            }
        }

        /* Ajustes para modal em dispositivos móveis */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-content {
                border-radius: 0.5rem;
            }
        }

        /* Ajustes para telas médias */
        @media (min-width: 577px) and (max-width: 991px) {
            .content-wrapper {
                margin-left: var(--sidebar-width);
                padding: var(--spacing-sm);
            }
            
            .card {
                margin: 0 auto;
                max-width: 100%;
            }
        }

        /* Ajustes para telas grandes */
        @media (min-width: 992px) {
            .content-wrapper {
                margin-left: var(--sidebar-width);
                padding: var(--spacing-md);
            }
            
            .chamado-form-container {
                max-width: 1200px;
                padding: var(--spacing-md);
            }
        }

        /* Variáveis CSS */
        :root {
            --primary-color: #0056b3;
            --text-color: #333333;
            --bg-color: #f8f9fa;
            --card-bg: #ffffff;
            --border-color: rgba(0, 0, 0, 0.125);
            --spacing-xs: 0.5rem;
            --spacing-sm: 1rem;
            --spacing-md: 1.5rem;
            --spacing-lg: 2rem;
            --border-radius: 10px;
            --shadow: 0 0 15px rgba(0, 0, 0, 0.1);
            --sidebar-width: 250px;
            --sidebar-width-mobile: 60px;
        }
    </style>
</head>
<body class="bg-light">
    <?php include_once __DIR__ . '/../../../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container-fluid">
            <div class="chamado-form-container">
                <h2 class="text-center mb-4 fs-4 text-primary">
                    <i class="bi bi-diagram-3 me-2"></i>Gerenciar Setores
                </h2>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0 d-none d-sm-block">Lista de Setores</h5>
                            <a href="/app_chamati/views/admin/admin_setor/novo_setor.php" class="btn btn-success">
                                <i class="bi bi-plus-circle"></i> <span class="d-none d-sm-inline">Novo Setor</span>
                            </a>
                        </div>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead>
                                    <tr>
                                        <th>Setor</th>
                                        <th>Filial</th>
                                        <th>Ações</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($setores as $setor): ?>
                                        <tr>
                                            <td data-label="Setor"><?php echo htmlspecialchars($setor['setor_nome']); ?></td>
                                            <td data-label="Filial"><?php echo htmlspecialchars($setor['filial_nome']); ?></td>
                                            <td data-label="Ações">
                                                <div class="table-actions">
                                                    <button class="btn btn-sm btn-primary" onclick="editarSetor(<?php echo $setor['id']; ?>)">
                                                        <i class="bi bi-pencil"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="confirmarExclusao(<?php echo $setor['id']; ?>)">
                                                        <i class="bi bi-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal de Edição -->
    <div class="modal fade" id="editarSetorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Editar Setor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="formEditarSetor">
                        <input type="hidden" id="editId" name="id">
                        <div class="mb-3">
                            <label for="editNome" class="form-label">Nome do Setor</label>
                            <input type="text" class="form-control" id="editNome" name="nome" required>
                        </div>
                        <div class="mb-3">
                            <label for="editFilial" class="form-label">Filial</label>
                            <select class="form-select" id="editFilial" name="filial_id" required>
                                <option value="">Selecione uma filial</option>
                                <?php foreach ($filiais as $filial): ?>
                                    <option value="<?php echo $filial['id']; ?>">
                                        <?php echo htmlspecialchars($filial['nome']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary" onclick="salvarEdicao()">Salvar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let editarModal;
        document.addEventListener('DOMContentLoaded', function() {
            editarModal = new bootstrap.Modal(document.getElementById('editarSetorModal'));
        });

        async function editarSetor(id) {
            console.log('Iniciando edição do setor:', id);
            try {
                const response = await fetch(`/app_chamati/controller/setor/controller_gerenciar_setor.php?action=buscar&id=${id}`);
                const data = await response.json();
                console.log('Resposta do servidor:', data);
                
                if (data.success) {
                    document.getElementById('editId').value = data.setor.id;
                    document.getElementById('editNome').value = data.setor.nome;
                    document.getElementById('editFilial').value = data.setor.filial_id;
                    
                    if (editarModal) {
                        editarModal.show();
                    } else {
                        console.error('Modal não inicializado');
                        alert('Erro ao abrir o modal');
                    }
                } else {
                    alert(data.message || 'Erro ao buscar dados do setor');
                }
            } catch (error) {
                console.error('Erro detalhado:', error);
                alert('Erro ao buscar dados do setor');
            }
        }

        async function salvarEdicao() {
            const formData = new FormData(document.getElementById('formEditarSetor'));
            
            try {
                const response = await fetch('/app_chamati/controller/setor/controller_gerenciar_setor.php?action=editar', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao salvar alterações');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao salvar alterações');
            }
        }

        function confirmarExclusao(id) {
            if (confirm('Tem certeza que deseja excluir este setor?')) {
                excluirSetor(id);
            }
        }

        async function excluirSetor(id) {
            try {
                const response = await fetch('/app_chamati/controller/setor/controller_gerenciar_setor.php?action=excluir', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({ id: id })
                });
                
                const data = await response.json();
                
                if (data.success) {
                    window.location.reload();
                } else {
                    alert(data.message || 'Erro ao excluir setor');
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao excluir setor');
            }
        }
    </script>
</body>
</html>