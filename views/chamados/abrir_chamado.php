<?php
session_start();
require_once __DIR__ . '/../../database/conexaodb.php';

// Verifica se usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /app_chamati/login.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

$db = Database::getInstance();

// Buscar filiais
$stmt = $db->prepare("SELECT id, nome FROM filiais ORDER BY nome");
$stmt->execute();
$filiais = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Abertura de Chamados - CHAMATI">
    <title>Abrir Chamado - CHAMATI</title>
    
    <!-- CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="/app_chamati/assets/css/abrir_chamados.css">
    <style>
        /* Mobile-First Styles */
        .content-wrapper {
            position: relative;
            min-height: 100vh;
            padding: var(--spacing-xs);
            transition: padding-left 0.3s;
            z-index: 1;
        }

        /* Adicionando estilo para o título */
        .chamado-form-container h2 {
            color: var(--primary-color);
            font-size: 1.75rem;
            font-weight: 500;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
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

        .card-body {
            padding: 1rem;
        }

        /* Form Styles */
        .form-label {
            font-weight: 500;
            margin-bottom: 0.5rem;
        }

        .form-control, .form-select {
            padding: 0.5rem;
            border-radius: 0.5rem;
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

            .row {
                margin: 0;
            }

            .col-md-6 {
                padding: 0.5rem;
            }

            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
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

        /* Estilos para o modal em dispositivos móveis */
        @media (max-width: 576px) {
            .modal-dialog {
                margin: 0.5rem;
            }

            .modal-content {
                border-radius: 0.5rem;
            }

            .modal-body {
                padding: 1rem;
            }

            .modal-footer {
                padding: 0.75rem;
            }
        }

        /* Ajustes para tabela responsiva em dispositivos móveis */
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

            .card-header {
                padding: 0.75rem;
            }

            .card-body {
                padding: 0.75rem;
            }
        }

        /* Ajustes para o modal */
        .modal-content {
            color: #000000;
            background-color: #ffffff;
        }

        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #dee2e6;
        }

        .modal-header h5 {
            color: #000000;
            font-weight: 600;
        }

        .modal-body {
            color: #000000;
        }

        .modal-body p {
            color: #000000;
            margin-bottom: 0.5rem;
            font-size: 1rem;
            opacity: 1;
        }

        .modal-body strong {
            color: #000000;
            font-weight: 600;
            opacity: 1;
        }

        .descricao-box {
            color: #000000;
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 0.5rem;
            margin-top: 0.5rem;
            opacity: 1;
        }

        #imagemChamadoContainer h6,
        .modal-body h6 {
            color: #000000;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .modal-footer {
            border-top: 1px solid #dee2e6;
            background-color: #f8f9fa;
        }

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
<body>
    <?php include_once __DIR__ . '/../../includes/sidebar.php'; ?>
    
    <div class="content-wrapper">
        <div class="container">
            <div class="chamado-form-container">
                <h2 class="text-center mb-4 fs-4 text-primary">
                    <i class="bi bi-plus-circle me-2"></i>Abrir Novo Chamado
                </h2>

                <?php if (isset($_GET['error'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_GET['error']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Chamado aberto com sucesso!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <h5 class="mb-0">Lista de Chamados</h5>
                        </div>
                    </div>
                    <div class="card-body p-0 p-sm-3">
                        <form id="formChamado" method="POST" action="/app_chamati/controller/chamados/controller_abrir_chamado.php" enctype="multipart/form-data" class="needs-validation" novalidate>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="filial" class="form-label">Filial</label>
                                    <select class="form-select" id="filial" name="filial_id" required>
                                        <option value="">Selecione a filial</option>
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

                                <div class="col-md-6 mb-3">
                                    <label for="setor" class="form-label">Setor</label>
                                    <select class="form-select" id="setor" name="setor_id" required disabled>
                                        <option value="">Primeiro selecione uma filial</option>
                                    </select>
                                    <div class="invalid-feedback">
                                        Por favor, selecione um setor.
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="tipo_prioridade" class="form-label">Prioridade</label>
                                    <select class="form-select" id="tipo_prioridade" name="tipo_prioridade" required>
                                        <option value="Normal">Normal</option>
                                        <option value="Urgente">Urgente</option>
                                    </select>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="codigo_acesso" class="form-label">Código de Acesso Remoto (opcional)</label>
                                    <input type="text" class="form-control" id="codigo_acesso" name="codigo_acesso_remoto">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="email_contato" class="form-label">Email para Contato</label>
                                    <input type="email" class="form-control" id="email_contato" name="email_contato" 
                                           value="<?= htmlspecialchars($_SESSION['usuario_email'] ?? '') ?>" required>
                                </div>

                                <div class="col-md-6 mb-3">
                                    <label for="telefone_contato" class="form-label">Telefone para Contato</label>
                                    <input type="tel" class="form-control" id="telefone_contato" name="telefone_contato">
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="descricao" class="form-label">
                                    Descrição do Problema 
                                    <span class="text-danger">*</span>
                                    <small class="text-muted">(mínimo 10 caracteres)</small>
                                </label>
                                <textarea class="form-control" id="descricao" name="descricao" rows="4" required minlength="10" maxlength="1000"></textarea>
                                <div class="invalid-feedback">
                                    Por favor, descreva o problema com pelo menos 10 caracteres.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="arquivos" class="form-label">
                                    Anexar Arquivos (opcional)
                                    <small class="text-muted">
                                        (Formatos permitidos: jpg, jpeg, png, pdf, doc, docx, xls, xlsx, txt - Máx. 5MB por arquivo)
                                    </small>
                                </label>
                                <input type="file" 
                                       class="form-control" 
                                       id="arquivos" 
                                       name="arquivos[]" 
                                       accept=".jpg,.jpeg,.png,.pdf,.doc,.docx,.xls,.xlsx,.txt"
                                       multiple>
                                <div class="invalid-feedback">
                                    Por favor, selecione apenas arquivos nos formatos permitidos.
                                </div>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Abrir Chamado</button>
                                <a href="javascript:history.back()" class="btn btn-secondary">Voltar</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir o modal -->
    <?php include 'modal_detalhes_do_chamado.php'; ?>

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Carregar setores quando filial for selecionada
            $('#filial').change(function() {
                const filialId = $(this).val();
                const setorSelect = $('#setor');
                
                if (filialId) {
                    $.ajax({
                        url: '/app_chamati/controller/chamados/controller_abrir_chamado.php',
                        type: 'GET',
                        data: { action: 'getSetores', filial_id: filialId },
                        success: function(response) {
                            setorSelect.html('<option value="">Selecione o setor</option>');
                            response.forEach(function(setor) {
                                setorSelect.append(`<option value="${setor.id}">${setor.nome}</option>`);
                            });
                            setorSelect.prop('disabled', false);
                        },
                        error: function() {
                            alert('Erro ao carregar setores');
                        }
                    });
                } else {
                    setorSelect.html('<option value="">Primeiro selecione uma filial</option>');
                    setorSelect.prop('disabled', true);
                }
            });

            // Validação do formulário
            const form = document.getElementById('formChamado');
            form.addEventListener('submit', function(event) {
                const descricao = document.getElementById('descricao').value.trim();
                
                // Validação específica para o campo de descrição
                if (descricao.length < 10) {
                    event.preventDefault();
                    event.stopPropagation();
                    document.getElementById('descricao').setCustomValidity('A descrição deve ter pelo menos 10 caracteres');
                } else {
                    document.getElementById('descricao').setCustomValidity('');
                }
                
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            });

            // Validação de arquivos
            const arquivosInput = document.getElementById('arquivos');
            arquivosInput.addEventListener('change', function(event) {
                const files = event.target.files;
                const maxFileSize = 5 * 1024 * 1024; // 5MB em bytes
                const allowedTypes = [
                    'image/jpeg',
                    'image/jpg',
                    'image/png',
                    'application/pdf',
                    'application/msword',
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/vnd.ms-excel',
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'text/plain'
                ];

                for (let i = 0; i < files.length; i++) {
                    const file = files[i];
                    
                    // Verificar tamanho
                    if (file.size > maxFileSize) {
                        alert(`O arquivo ${file.name} excede o tamanho máximo permitido de 5MB`);
                        event.target.value = '';
                        return;
                    }

                    // Verificar tipo
                    if (!allowedTypes.includes(file.type)) {
                        alert(`O arquivo ${file.name} não está em um formato permitido`);
                        event.target.value = '';
                        return;
                    }
                }
            });

            // Verificar se há um chamado_id na URL e exibir o modal
            const urlParams = new URLSearchParams(window.location.search);
            const chamadoId = urlParams.get('chamado_id');
            
            if (chamadoId && urlParams.get('success')) {
                $.ajax({
                    url: '/app_chamati/controller/chamados/controller_abrir_chamado.php',
                    type: 'GET',
                    data: { 
                        action: 'getChamadoDetalhes', 
                        chamado_id: chamadoId 
                    },
                    success: function(response) {
                        if (response.success) {
                            const chamado = response.data;
                            
                            // Preencher os dados básicos do modal (código existente)
                            $('#numeroChamado').text(chamado.numero_chamado);
                            $('#statusChamado').text(chamado.status);
                            $('#filialChamado').text(chamado.filial_nome);
                            $('#setorChamado').text(chamado.setor_nome);
                            $('#prioridadeChamado').text(chamado.tipo_prioridade);
                            $('#dataAberturaChamado').text(chamado.data_abertura);
                            $('#emailContatoChamado').text(chamado.email_contato);
                            $('#telefoneContatoChamado').text(chamado.telefone_contato || 'Não informado');
                            $('#codigoAcessoChamado').text(chamado.codigo_acesso_remoto || 'Não informado');
                            $('#descricaoChamado').text(chamado.descricao);
                            
                            // Tratar arquivos anexados
                            const arquivosList = $('#arquivosList');
                            arquivosList.empty();
                            
                            if (chamado.arquivos && chamado.arquivos.length > 0) {
                                chamado.arquivos.forEach(function(arquivo) {
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
                                    arquivosList.append(html);
                                });
                                
                                $('#arquivosContainer').show();
                            } else {
                                $('#arquivosContainer').hide();
                            }

                            // Exibir o modal
                            $('#modalDetalhesChamado').modal('show');
                        }
                    },
                    error: function() {
                        alert('Erro ao carregar detalhes do chamado');
                    }
                });
            }
        });
    </script>
</body>
</html>
