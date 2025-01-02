<?php
session_start();

// Se já estiver logado, redireciona para o dashboard apropriado
if (isset($_SESSION['usuario_id'])) {
    $tipo = $_SESSION['usuario_tipo'];
    if ($tipo === 'Tecnico') {
        header('Location: /app_chamati/views/tecnico/dashboard_tecnico.php');
        exit();
    } elseif ($tipo === 'Funcionario') {
        header('Location: /app_chamati/views/funcionario/dashboard_funcionario.php');
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Sistema de Chamados TI - CHAMATI">
    <meta name="author" content="Seu Nome">
    <title>Login - CHAMATI</title>
    
    <!-- Favicon -->
    <link rel="shortcut icon" href="/app_chamati/assets/img/favicon.ico" type="image/x-icon">
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/index.css">
</head>
<body>
    <div class="login-wrapper">
        <div class="login-container">
            <div class="login-card">
                <!-- Logo Container -->
                <div class="logo-container">
                    <img src="/app_chamati/assets/img/logo.png" alt="CHAMATI Logo" class="login-logo">
                    <h1 class="login-title">CHAMATI</h1>
                </div>
                
                <p class="login-subtitle">Sistema de Gerenciamento de Chamados</p>

                <!-- Alertas -->
                <div class="alerts-container">
                    <?php if (isset($_GET['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <?php echo htmlspecialchars($_GET['error']); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($_GET['logout'])): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            Logout realizado com sucesso!
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Fechar"></button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Formulário de Login -->
                <form method="POST" action="/app_chamati/controller/login/controller_login.php" class="login-form needs-validation" novalidate>
                    <div class="form-floating mb-3">
                        <input type="email" 
                               class="form-control" 
                               id="email" 
                               name="email" 
                               placeholder="seu.email@exemplo.com" 
                               required>
                        <label for="email">
                            <i class="bi bi-envelope"></i> Email
                        </label>
                        <div class="invalid-feedback">
                            Por favor, insira um email válido.
                        </div>
                    </div>

                    <div class="form-floating mb-4">
                        <input type="password" 
                               class="form-control" 
                               id="senha" 
                               name="senha" 
                               placeholder="Sua senha" 
                               required 
                               minlength="6">
                        <label for="senha">
                            <i class="bi bi-lock"></i> Senha
                        </label>
                        <div class="invalid-feedback">
                            A senha deve ter no mínimo 6 caracteres.
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 btn-login">
                        <span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
                        <span class="btn-text">Entrar</span>
                    </button>
                </form>

                <footer class="login-footer">
                    <small class="text-muted">
                        &copy; <?php echo date('Y'); ?> CHAMATI - Todos os direitos reservados
                    </small>
                </footer>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Validação do formulário
            const form = document.querySelector('.login-form');
            const spinner = document.querySelector('.spinner-border');
            const btnLogin = document.querySelector('.btn-login');

            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                } else {
                    spinner.classList.remove('d-none');
                    btnLogin.setAttribute('disabled', true);
                }
                form.classList.add('was-validated');
            });
        });
    </script>
</body>
</html>