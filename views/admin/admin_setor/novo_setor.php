<?php
session_start();
require_once __DIR__ . '/../../../database/conexaodb.php';

// Verifica se usuário está logado e é Tecnico
if (!isset($_SESSION['usuario_id']) || $_SESSION['usuario_tipo'] !== 'Tecnico') {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();

// Buscar filiais para o select
$stmt = $db->prepare("SELECT id, nome FROM filiais ORDER BY nome");
$stmt->execute();
$filiais = $stmt->fetchAll();

// Verifica se há uma mensagem de retorno
$message = '';
$alert_type = '';
if (isset($_GET['success'])) {
    $message = 'Setor cadastrado com sucesso!';
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
    <meta name="description" content="Cadastro de Novo Setor - CHAMATI">
    <title>Novo Setor - CHAMATI</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/gerenciar_filial.css">
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

        /* Ajustes para dispositivos móveis */
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 0.5rem 0;
                margin-left: var(--sidebar-width-mobile);
            }

            .container {
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

            .form-group {
                margin-bottom: 1rem;
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
        <div class="container">
            <div class="chamado-form-container">
                <h2 class="text-center mb-4 fs-4 text-primary">
                    <i class="bi bi-diagram-3 me-2"></i>Cadastrar Novo Setor
                </h2>

                <?php if ($message): ?>
                    <div class="alert alert-<?php echo $alert_type; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-end">
                            <a href="gerenciar_setor.php" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Voltar
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <form id="formNovoSetor" action="/app_chamati/controller/setor/controller_novo_setor.php" method="POST" class="needs-validation" novalidate>
                            <div class="form-group mb-3">
                                <label for="nome" class="form-label">Nome do Setor</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-diagram-3"></i>
                                    </span>
                                    <input type="text" class="form-control" id="nome" name="nome" 
                                           required minlength="3" maxlength="100"
                                           pattern="[A-Za-zÀ-ÿ0-9\s\-]+"
                                           placeholder="Digite o nome do setor">
                                    <div class="invalid-feedback">
                                        Por favor, insira um nome válido para o setor (mínimo 3 caracteres).
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-group mb-4">
                                <label for="filial" class="form-label">Filial</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="bi bi-building"></i>
                                    </span>
                                    <select class="form-select" id="filial" name="filial_id" required>
                                        <option value="">Selecione uma filial</option>
                                        <?php foreach($filiais as $filial): ?>
                                            <option value="<?= htmlspecialchars($filial['id']) ?>">
                                                <?= htmlspecialchars($filial['nome']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor, selecione uma filial.
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-check-circle"></i> Cadastrar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('#formNovoSetor');
            
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Normalização do input
            const nomeSetorInput = document.querySelector('#nome');
            nomeSetorInput.addEventListener('input', function(e) {
                let value = e.target.value;
                // Remove caracteres especiais exceto letras, números, espaços e hífen
                value = value.replace(/[^A-Za-zÀ-ÿ0-9\s\-]/g, '');
                e.target.value = value;
            });
        });
    </script>
</body>
</html>