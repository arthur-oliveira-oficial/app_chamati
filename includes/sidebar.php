<?php
if (!isset($_SESSION)) {
    session_start();
}

// Importa os componentes
require_once __DIR__ . '/components/Sidebar/UserProfile.php';
require_once __DIR__ . '/components/Sidebar/Navigation.php';

// Verifica se o usuário está logado
if (!isset($_SESSION['usuario_id'])) {
    header('Location: /app_chamati/index.php?error=' . urlencode('Acesso não autorizado'));
    exit();
}

// Define as rotas com base no tipo de usuário
$menuItems = [];

if ($_SESSION['usuario_tipo'] === 'Tecnico') {
    $menuItems = [
        [
            'icon' => 'bi bi-plus-circle',
            'text' => 'Abrir Chamado',
            'url' => '/app_chamati/views/chamados/abrir_chamado.php'
        ],
        [
            'icon' => 'bi bi-ticket-detailed',
            'text' => 'Visualizar Chamados',
            'url' => '/app_chamati/views/tecnico/listar_chamado_tecnico.php'
        ],
        [
            'icon' => 'bi bi-file-earmark-text',
            'text' => 'Relatório de Chamados',
            'url' => '/app_chamati/views/relatorio/relatorio_chamado_tecnico.php'
        ],
        [
            'icon' => 'bi bi-building',
            'text' => 'Gerenciar Filiais',
            'url' => '/app_chamati/views/admin/admin_filial/gerenciar_filial.php'
        ],
        [
            'icon' => 'bi bi-diagram-3',
            'text' => 'Gerenciar Setores',
            'url' => '/app_chamati/views/admin/admin_setor/gerenciar_setor.php'
        ],
        [
            'icon' => 'bi bi-people',
            'text' => 'Gerenciar Usuários',
            'url' => '/app_chamati/views/admin/admin_usuario/gerenciar_usuario.php'
        ]
    ];
} else {
    $menuItems = [
        [
            'icon' => 'bi bi-plus-circle',
            'text' => 'Abrir Chamado',
            'url' => '/app_chamati/views/chamados/abrir_chamado.php'
        ],
        [
            'icon' => 'bi bi-ticket-detailed',
            'text' => 'Meus Chamados',
            'url' => '/app_chamati/views/funcionario/listar_chamado_funcionario.php'
        ]
    ];
}

// Adiciona o item de logout para ambos os tipos
$menuItems[] = [
    'icon' => 'bi bi-box-arrow-right',
    'text' => 'Sair',
    'url' => '/app_chamati/controller/login/logout.php'
];

?>

<div class="sidebar" id="sidebar">
    <div class="sidebar__header">
        <button class="sidebar__toggle" id="sidebarToggle">
            <i class="bi bi-list"></i>
        </button>
        <div class="sidebar__brand">
            <?php if ($_SESSION['usuario_tipo'] === 'Tecnico'): ?>
                <a href="/app_chamati/views/tecnico/dashboard_tecnico.php">
                    <img src="/app_chamati/assets/img/logo.png" alt="CHAMATI Logo" class="sidebar__logo">
                </a>
            <?php else: ?>
                <a href="/app_chamati/views/funcionario/dashboard_funcionario.php">
                    <img src="/app_chamati/assets/img/logo.png" alt="CHAMATI Logo" class="sidebar__logo">
                </a>
            <?php endif; ?>
            <h3 class="sidebar__title">CHAMATI</h3>
        </div>
    </div>
    
    <?php 
    renderNavigation($menuItems, $_SERVER['PHP_SELF']);
    ?>
</div>

<style>
/* Reset e variáveis */
:root {
    --sidebar-width: 250px;
    --sidebar-width-mobile: 60px;
    --primary-color: #2c3e50;
    --secondary-color: #34495e;
    --accent-color: #3498db;
    --text-color: #ecf0f1;
    --text-muted: #95a5a6;
    --transition: all 0.3s ease;
    --border-radius: 4px;
    --spacing-xs: 0.25rem;
    --spacing-sm: 0.5rem;
    --spacing-md: 1rem;
    --spacing-lg: 1.5rem;
    --z-index-sidebar: 1000;
}

/* Mobile First Base Styles */
.sidebar {
    width: var(--sidebar-width-mobile);
    height: 100vh;
    background-color: var(--primary-color);
    color: var(--text-color);
    position: fixed;
    left: 0;
    top: 0;
    z-index: var(--z-index-sidebar);
    padding: var(--spacing-xs);
    box-shadow: 2px 0 5px rgba(0,0,0,0.1);
    transition: var(--transition);
    overflow-x: hidden;
    overflow-y: auto;
}

/* Header */
.sidebar__header {
    position: relative;
    padding: var(--spacing-sm);
    height: auto;
    min-height: 100px;
}

/* Mobile First - Botão Toggle */
.sidebar__toggle {
    position: relative;
    width: 100%;
    background: transparent;
    border: none;
    color: var(--text-color);
    font-size: 1.2rem;
    cursor: pointer;
    padding: var(--spacing-xs);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: var(--spacing-sm);
    transition: var(--transition);
}

.sidebar__toggle i {
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Container para Logo e Título */
.sidebar__brand {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: var(--spacing-xs);
}

.sidebar__logo {
    width: 32px;
    height: 32px;
    object-fit: contain;
}

.sidebar__title {
    display: block;
    margin: 0;
    font-size: 0.8rem;
    white-space: nowrap;
    color: var(--text-color);
}

/* Navigation */
.sidebar-nav__list {
    list-style: none;
    padding: var(--spacing-xs) 0;
    margin: 0;
}

.nav-item {
    margin: var(--spacing-xs) 0;
}

.nav-link {
    display: flex;
    align-items: center;
    padding: var(--spacing-sm);
    color: var(--text-color);
    text-decoration: none;
    border-radius: var(--border-radius);
    transition: var(--transition);
    white-space: nowrap;
}

.nav-link:hover {
    background-color: var(--secondary-color);
}

.nav-link--active {
    background-color: var(--accent-color);
}

.nav-link i {
    font-size: 1.2rem;
    min-width: 24px;
    text-align: center;
}

.nav-link__text {
    display: none;
    margin-left: var(--spacing-sm);
}

/* Sidebar Expanded State */
.sidebar--expanded {
    width: var(--sidebar-width);
}

.sidebar--expanded .sidebar__toggle {
    justify-content: flex-start;
    padding-left: var(--spacing-md);
}

.sidebar--expanded .sidebar__title,
.sidebar--expanded .user-profile__details,
.sidebar--expanded .nav-link__text {
    display: block;
}

/* Desktop Styles */
@media (min-width: 769px) {
    .sidebar {
        width: var(--sidebar-width);
        padding: var(--spacing-sm);
    }

    .sidebar__title,
    .user-profile__details,
    .nav-link__text {
        display: block;
    }

    .sidebar__toggle {
        display: none;
    }

    .user-profile {
        flex-direction: row;
        padding: var(--spacing-md);
        justify-content: flex-start;
    }

    .user-profile__details {
        text-align: left;
    }

    .nav-link {
        padding: var(--spacing-md);
    }

    .sidebar__header {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        height: 100px;
        text-align: center;
    }

    .sidebar__toggle {
        display: none;
    }

    .sidebar__brand {
        margin-top: 0;
    }

    .sidebar__logo {
        width: 40px;
        height: 40px;
    }

    .sidebar__title {
        font-size: 1.2rem;
    }
}

/* Scrollbar Customization */
.sidebar::-webkit-scrollbar {
    width: 4px;
}

.sidebar::-webkit-scrollbar-track {
    background: var(--primary-color);
}

.sidebar::-webkit-scrollbar-thumb {
    background: var(--secondary-color);
    border-radius: var(--border-radius);
}

/* Touch Device Optimization */
@media (hover: none) {
    .sidebar {
        -webkit-overflow-scrolling: touch;
    }
}

/* Dark Mode Support */
@media (prefers-color-scheme: dark) {
    :root {
        --primary-color: #1a2634;
        --secondary-color: #2c3e50;
    }
}

/* Removendo os estilos relacionados ao user profile que não serão mais usados */
.user-profile,
.user-profile__avatar,
.user-profile__details {
    display: none;
}

.sidebar__brand a {
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const sidebar = document.getElementById('sidebar');
    const toggleBtn = document.getElementById('sidebarToggle');
    
    // Toggle sidebar on button click
    toggleBtn.addEventListener('click', function() {
        sidebar.classList.toggle('sidebar--expanded');
    });

    // Auto-collapse sidebar on mobile when clicking outside
    document.addEventListener('click', function(event) {
        const isMobile = window.innerWidth < 769;
        const isClickInside = sidebar.contains(event.target);
        
        if (isMobile && !isClickInside && sidebar.classList.contains('sidebar--expanded')) {
            sidebar.classList.remove('sidebar--expanded');
        }
    });

    // Handle window resize
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            if (window.innerWidth >= 769) {
                sidebar.classList.remove('sidebar--expanded');
            }
        }, 250);
    });
});
</script>
