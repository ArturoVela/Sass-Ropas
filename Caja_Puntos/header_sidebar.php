<?php
// Asegurarse de que la sesión esté iniciada e incluir la configuración de colores
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once 'config_colors.php';

// Obtener datos del usuario
$user = $_SESSION['user'] ?? [];
$empName = htmlspecialchars($user['empresa']['nombre'] ?? 'Empresa', ENT_QUOTES);

// Mapear nombres de archivo a títulos amigables para el usuario
$page_titles = [
    'dashboard.php'        => 'Dashboard',
    'PuntosClientes.php'   => 'Puntos de Clientes',
    'historial_puntos.php' => 'Historial de Puntos',
    'recompensas.php'      => 'Gestión de Recompensas',
    'canjes.php'           => 'Canjes de Puntos',
    'caja.php'             => 'Gestión de Caja',
    'movimientos_caja.php' => 'Movimientos de Caja',
    'configuracion.php'    => 'Configuración',
];

$current_page_script = basename($_SERVER['SCRIPT_NAME']);
$pageTitle = $page_titles[$current_page_script] ?? 'Módulo';

?>
<style>
    /* Estilos del Header, adaptados para ser reutilizables */
    :root {
        --header-height: 70px;
        --sidebar-width: 250px;
        --border-color: #dee2e6;
        --shadow: 0 0.125rem 0.25rem rgba(0,0,0, .075);
        /* Las variables de color se establecen aquí desde PHP y se actualizan con JS */
        --brand-color: <?= $brandColor ?>;
        --brand-color-hover: <?= $hoverColor ?>;
    }
    .main-header {
        position: fixed;
        top: 0;
        left: var(--sidebar-width);
        right: 0;
        height: var(--header-height);
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(10px);
        border-bottom: 1px solid var(--border-color);
        z-index: 1030;
        box-shadow: var(--shadow);
    }
    @media (max-width: 767.98px) { .main-header { left: 0; } }

    .header-content {
        display: flex;
        align-items: center;
        justify-content: space-between;
        height: 100%;
        padding: 0 2rem;
    }
    .page-title {
        font-size: 1.5rem;
        font-weight: 600;
        margin: 0;
        color: var(--brand-color) !important;
    }
    .user-menu { position: relative; }
    .user-menu-toggle {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 1rem;
        background: transparent;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        cursor: pointer;
        transition: all 0.3s ease;
        text-decoration: none;
        color: #343a40;
    }
    .user-menu-toggle:hover {
        border-color: var(--brand-color);
        color: var(--brand-color);
    }
    .user-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        background-color: var(--brand-color);
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-weight: 600;
        font-size: 1rem;
    }
    .user-info { text-align: left; }
    .user-name { font-weight: 600; font-size: 0.9rem; margin: 0; }
    .user-role { font-size: 0.75rem; color: #6c757d; }
    .user-menu-dropdown {
        position: absolute;
        top: 100%;
        right: 0;
        width: 220px;
        background: white;
        border: 1px solid var(--border-color);
        border-radius: 0.75rem;
        box-shadow: 0 0.5rem 1rem rgba(0,0,0, .15);
        opacity: 0;
        visibility: hidden;
        transform: translateY(-10px);
        transition: all 0.3s ease;
        z-index: 1000;
        margin-top: 0.5rem;
    }
    .user-menu-dropdown.show { opacity: 1; visibility: visible; transform: translateY(0); }
    .user-menu-dropdown .dropdown-header { padding: 1rem 1.5rem; border-bottom: 1px solid var(--border-color); }
    .user-menu-dropdown .dropdown-item { display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem 1.5rem; color: #343a40; text-decoration: none; }
    .user-menu-dropdown .dropdown-item:hover { background: #f8f9fa; color: var(--brand-color); }
    .user-menu-dropdown .dropdown-divider { height: 1px; background: var(--border-color); margin: 0.5rem 0; }

    /* Styles for Theme Switcher */
    .theme-btn {
        background: none;
        border: 1px solid var(--brand-color);
        border-radius: 50%;
        color: var(--brand-color);
        width: 40px;
        height: 40px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    .theme-btn:hover, .theme-btn.active {
        border-color: var(--brand-color-hover);
        color: var(--brand-color-hover);
    }
    .theme-dot {
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: inline-block;
        margin-right: .75rem;
        border: 1px solid #ccc;
    }

    /* Layout adjustment for main content */
    main.content {
        margin-top: var(--header-height);
        margin-left: var(--sidebar-width);
        width: calc(100% - var(--sidebar-width));
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    @media (max-width: 767.98px) { main.content { margin-left: 0; width: 100%; } }

    /* Global styles for metric cards that can be controlled by the theme */
    .metric-card .card-title,
    .metric-card .display-4 {
        color: var(--brand-color) !important;
    }
</style>

<header class="main-header">
    <div class="header-content">
        <h1 class="page-title"><?= htmlspecialchars($pageTitle) ?></h1>
        <div class="d-flex align-items-center">
            <!-- Theme Switcher -->
            <div class="dropdown me-3">
                <button class="theme-btn" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Cambiar Tema">
                    <i class="fas fa-palette"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <?php foreach ($themeColors as $theme): ?>
                    <li>
                        <a class="dropdown-item theme-select" href="#" data-color="<?= $theme['hex'] ?>">
                            <span class="theme-dot" style="background-color: <?= $theme['hex'] ?>;"></span>
                            <?= htmlspecialchars($theme['name']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            
            <!-- User Menu -->
            <div class="user-menu">
                <button class="user-menu-toggle" onclick="toggleUserMenu()">
                    <div class="user-avatar"><?= strtoupper(substr($user['nombre'] ?? 'U', 0, 1)) ?></div>
                    <div class="user-info">
                        <p class="user-name mb-0"><?= htmlspecialchars($user['nombre'] ?? 'Usuario') ?></p>
                        <p class="user-role mb-0"><?= htmlspecialchars($user['rol'] ?? 'Rol') ?></p>
                    </div>
                    <i class="fas fa-chevron-down"></i> 
                </button>
                <div class="user-menu-dropdown" id="userMenuDropdown">
                    <div class="dropdown-header">
                        <h6><?= htmlspecialchars($user['nombre'] ?? 'Usuario') ?></h6>
                        <p class="text-muted mb-0"><?= htmlspecialchars($empName) ?></p>
                    </div>
                    <a href="configuracion.php" class="dropdown-item"><i class="fas fa-cog"></i><span>Configuración</span></a>
                    <a href="#" class="dropdown-item"><i class="fas fa-question-circle"></i><span>Ayuda</span></a>
                    <div class="dropdown-divider"></div>
                    <a href="logout.php" class="dropdown-item text-danger"><i class="fas fa-sign-out-alt"></i><span>Cerrar Sesión</span></a>
                </div>
            </div>
        </div>
    </div>
</header>

<script>
    // User menu toggle logic
    if (typeof toggleUserMenu !== 'function') {
        function toggleUserMenu() {
            document.getElementById('userMenuDropdown').classList.toggle('show');
        }
        document.addEventListener('click', function(event) {
            const userMenu = document.querySelector('.user-menu');
            if (userMenu && !userMenu.contains(event.target)) {
                document.getElementById('userMenuDropdown')?.classList.remove('show');
            }
        });
    }

    // Theme Switcher Logic
    document.addEventListener('DOMContentLoaded', () => {
        const root = document.documentElement;

        const darkenColorJS = (hex, percent) => {
            if (!hex || hex.length < 7) return '#000000';
            hex = hex.replace('#', '');
            let r = parseInt(hex.substring(0, 2), 16);
            let g = parseInt(hex.substring(2, 4), 16);
            let b = parseInt(hex.substring(4, 6), 16);
            r = Math.max(0, r - Math.round(r * (percent / 100)));
            g = Math.max(0, g - Math.round(g * (percent / 100)));
            b = Math.max(0, b - Math.round(b * (percent / 100)));
            return `#${r.toString(16).padStart(2, '0')}${g.toString(16).padStart(2, '0')}${b.toString(16).padStart(2, '0')}`;
        };

        const applyTheme = (color) => {
            const hoverColor = darkenColorJS(color, 10);
            root.style.setProperty('--brand-color', color);
            root.style.setProperty('--brand-color-hover', hoverColor);
            
            // This is needed to override sidebar styles which are set after this file might be included
            root.style.setProperty('--sidebar-bg', color);
            root.style.setProperty('--sidebar-hover-bg', hoverColor);
            
            localStorage.setItem('userThemeColor', color);
        };

        document.querySelectorAll('.theme-select').forEach(el => {
            el.addEventListener('click', e => {
                e.preventDefault();
                const selectedColor = el.dataset.color;
                applyTheme(selectedColor);
            });
        });

        const savedColor = localStorage.getItem('userThemeColor');
        if (savedColor) {
            applyTheme(savedColor);
        }
    });
</script> 