# CHAMATI - Sistema de Gerenciamento de Chamados de TI

## 📋 Sobre o Projeto

CHAMATI é um sistema web desenvolvido para gerenciamento de chamados de TI, permitindo o registro, acompanhamento e resolução de problemas técnicos de forma eficiente.

## 🚀 Tecnologias Utilizadas

### Backend
- PHP 8.2
- MySQL com PDO
- Arquitetura MVC
- PSR-1, PSR-2 e PSR-4
- Princípios SOLID

### Frontend
- HTML5
- CSS3 (Metodologia BEM)
- JavaScript
- Bootstrap 5.3
- Bootstrap Icons
- Design Responsivo

### Bibliotecas PHP
- DomPDF (Geração de PDFs)
- PHPSpreadsheet (Manipulação de planilhas)
- ZipStream-PHP (Compressão de arquivos)
- HTML Purifier (Segurança)

## 🛠️ Funcionalidades

### Área do Técnico
- Dashboard com métricas e indicadores
- Gerenciamento de chamados
- Atualização de status
- Atribuição de prioridades
- Geração de relatórios

### Área do Funcionário
- Abertura de chamados
- Acompanhamento de solicitações
- Histórico de chamados
- Avaliação do atendimento

### Área Administrativa
- Gerenciamento de usuários
- Controle de filiais
- Gerenciamento de setores
- Relatórios gerenciais
- Configurações do sistema

## 🔐 Segurança

- Autenticação com sessões
- Password hashing
- Proteção contra CSRF
- Prevenção de XSS
- Prepared Statements
- Validação e sanitização de dados
- Timeout por inatividade (30 min)

## 🌐 Requisitos

- PHP >= 8.2
- MySQL >= 5.7
- Servidor Web (Apache/Nginx)
- Composer (Gerenciador de dependências)

## ⚙️ Instalação

1. Clone o repositório:

2. Configure o banco de dados:
3. Importe o arquivo SQL localizado em docs/implantacao/chamati.sql
4. Configure as credenciais do banco no arquivo .env
5. Configure o servidor web:
   Aponte o DocumentRoot para o diretório do projeto use o docs/implantacao/htaccess_exemplo.txt
   Habilite o mod_rewrite (Apache)


🤝 Contribuição
Contribuições são bem-vindas!

📄 Licença
Este projeto está sob a licença MIT.
