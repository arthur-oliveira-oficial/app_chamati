<div class="modal fade" id="modalDetalhesChamado" tabindex="-1" aria-labelledby="modalDetalhesChamadoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalDetalhesChamadoLabel">Detalhes do Chamado</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Fechar"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Número do Chamado:</strong> <span id="numeroChamado"></span></p>
                        <p><strong>Status:</strong> <span id="statusChamado"></span></p>
                        <p><strong>Filial:</strong> <span id="filialChamado"></span></p>
                        <p><strong>Setor:</strong> <span id="setorChamado"></span></p>
                        <p><strong>Prioridade:</strong> <span id="prioridadeChamado"></span></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Data de Abertura:</strong> <span id="dataAberturaChamado"></span></p>
                        <p><strong>Email para Contato:</strong> <span id="emailContatoChamado"></span></p>
                        <p><strong>Telefone para Contato:</strong> <span id="telefoneContatoChamado"></span></p>
                        <p><strong>Código de Acesso Remoto:</strong> <span id="codigoAcessoChamado"></span></p>
                    </div>
                </div>
                
                <div class="mt-3">
                    <h6>Descrição do Problema:</h6>
                    <div class="descricao-box" id="descricaoChamado"></div>
                </div>

                <div class="mt-3" id="arquivosContainer" style="display: none;">
                    <h6>Arquivos Anexados:</h6>
                    <div class="arquivos-list" id="arquivosList"></div>
                </div>

                <div class="mt-3" id="imagemChamadoContainer">
                    <h6>Imagem Anexada:</h6>
                    <img id="imagemChamado" class="img-fluid" style="max-height: 300px;" alt="Imagem do chamado">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Fechar</button>
            </div>
        </div>
    </div>
</div>

<style>
.descricao-box {
    background-color: #f8f9fa;
    padding: 1rem;
    border-radius: 5px;
    border: 1px solid #dee2e6;
    min-height: 100px;
    white-space: pre-wrap;
}

#imagemChamadoContainer {
    display: none;
}

.modal-lg {
    max-width: 800px;
}

@media (max-width: 768px) {
    .modal-body {
        padding: 1rem;
    }
    
    .col-md-6 {
        margin-bottom: 1rem;
    }
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
