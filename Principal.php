<!doctype html>
<?php
session_start();

function verificarSesion() {
    if (empty($_SESSION["user"])) {
        header("Location: index.php");
        exit();
    }
}

verificarSesion();

require_once "conecta.php";

$usuarioSesion = $_SESSION["user"];
$estadoPermisosMenu = obtenerEstadoPermisosMenuUsuario($usuarioSesion);
$esAdminMenu = !empty($estadoPermisosMenu['es_admin']);
$idsPermitidos = isset($estadoPermisosMenu['permitidos']) ? array_values($estadoPermisosMenu['permitidos']) : array();
$menusUsuarioPermitidos = obtenerMenusPermitidosUsuario($usuarioSesion, 'usuario');

$menusPrincipalesDef = array(
    array('id' => 'listasmovsexis', 'icono' => 'fa-tachometer-alt', 'texto' => 'Lista sin Mov y sin Exis'),
    array('id' => 'listasmovcexis', 'icono' => 'fa-table', 'texto' => 'Lista sin Mov y con Exis'),
    array('id' => 'listaclasificac', 'icono' => 'fa-chart-pie', 'texto' => 'ABC Costo Inventario'),
    array('id' => 'log', 'icono' => 'fa-history', 'texto' => 'Log Maximos y Minimos'),
    array('id' => 'backorder', 'icono' => 'fa-table', 'texto' => 'BackOrder'),
    array('id' => 'guiasdespachos', 'icono' => 'fa-truck-fast', 'texto' => 'GUIAS (Despachos)'),
    array('id' => 'centrokpi', 'icono' => 'fa-chart-line', 'texto' => 'Centro KPI'),
    array('id' => 'despachosconductor', 'icono' => 'fa-truck-ramp-box', 'texto' => 'Despachos conductor'),
    array('id' => 'rutaconductor', 'icono' => 'fa-route', 'texto' => 'Ruta conductor (Mapa)'),
    array('id' => 'retirados', 'icono' => 'fa-box-open', 'texto' => 'Retirados'),
    array('id' => 'listarotacion', 'icono' => 'fa-sync-alt', 'texto' => 'Rotacion Inventario'),
    array('id' => 'pedidosgeneradosauto', 'icono' => 'fa-table', 'texto' => 'Pedidos Generados'),
    array('id' => 'listaestados', 'icono' => 'fa-clipboard-list', 'texto' => 'Estados Pedidos'),
    array('id' => 'configuracionvencimientoxgrupos', 'icono' => 'fa-hourglass-end', 'texto' => 'Configuracion Vencimiento por grupos'),
    array('id' => 'recalcularnumericas', 'icono' => 'fa-history', 'texto' => 'Recalcular Numericas'),
    array('id' => 'listaconfiguracionlineas', 'icono' => 'fa-table', 'texto' => 'Configuracion Lineas'),
    array('id' => 'listadoproductosclasificados', 'icono' => 'fa-boxes', 'texto' => 'Productos Clasificados')
);

$menusPrincipalesPermitidos = array();
foreach ($menusPrincipalesDef as $menuDef) {
    if (usuarioTienePermisoMenu($usuarioSesion, $menuDef['id'])) {
        $menusPrincipalesPermitidos[] = $menuDef;
    }
}
?>

<html lang="es" data-bs-theme="auto">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Pagina Principal">
    <meta name="author" content="Leonardo Navarro">
    <title>GESTI&Oacute;N DE INVENTARIOS Y DESPACHOS</title>
    <link rel="icon" type="image/svg+xml" href="imagenes/favicon_gestion.svg">

    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>

    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/sidebars.css" rel="stylesheet">
    <link href="css/menu_profesional.css?v=20260218_01" rel="stylesheet">
    <link href="css/bootstrap-clockpicker.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
    <link href="css/alertify.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/sidebars.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/bootstrap-clockpicker.js"></script>
    <script src="js/moment-with-locales.js"></script>
    <script src="js/alertify.min.js"></script>
    <script src="js/jquery.blockUI.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="js/color-modes.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        window.MENU_IDS_PERMITIDOS = <?php echo json_encode($idsPermitidos); ?>;
        window.ES_USUARIO_ADMIN = <?php echo $esAdminMenu ? 'true' : 'false'; ?>;
    </script>
    <script src="js/scripts.js?v=<?php echo date('Ymd_his'); ?>"></script>
</head>

<body class="bodyp layout-menu">
    <header class="app-topbar">
        <div class="app-topbar-left">
            <button type="button" class="menu-toggle-btn" onclick="alternarSidebar()" aria-label="Abrir o cerrar menu">
                <i class="fas fa-bars"></i>
            </button>
            <div class="brand-block">
                <span class="brand-title brand-title-desktop"><i class="fas fa-boxes-stacked brand-icon"></i> GESTI&Oacute;N DE INVENTARIOS Y DESPACHOS</span>
                <span class="brand-title brand-title-mobile"><i class="fas fa-boxes-stacked brand-icon"></i> DESPACHOS</span>
                <span class="brand-subtitle">Panel operativo D17</span>
            </div>
        </div>
        <div class="app-topbar-right">
            <div class="user-menu" id="userMenu">
                <button type="button" id="userMenuToggle" class="user-chip user-chip-btn" aria-expanded="false">
                    <i class="fas fa-user-circle"></i>
                    <?php echo htmlspecialchars($_SESSION["user"]); ?>
                    <i class="fas fa-angle-down user-menu-arrow"></i>
                </button>
                <div id="userMenuDropdown" class="user-menu-dropdown" aria-hidden="true">
                    <?php if (isset($menusUsuarioPermitidos['listaconfiguraciones'])): ?>
                        <a href="#" class="user-menu-item" id="listaconfiguraciones">
                            <i class="fas fa-cogs"></i>
                            <span>Configuraciones</span>
                        </a>
                    <?php endif; ?>
                    <?php if (isset($menusUsuarioPermitidos['listaconexiones'])): ?>
                        <a href="#" class="user-menu-item" id="listaconexiones">
                            <i class="fas fa-database"></i>
                            <span>Conexiones</span>
                        </a>
                    <?php endif; ?>
                    <?php if (isset($menusUsuarioPermitidos['permisosmenu'])): ?>
                        <a href="#" class="user-menu-item" id="permisosmenu">
                            <i class="fas fa-user-shield"></i>
                            <span>Permisos de menu</span>
                        </a>
                    <?php endif; ?>
                    <a href="#" class="user-menu-item item-danger" id="salir">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Salir</span>
                    </a>
                </div>
            </div>
        </div>
    </header>

    <div id="menu-overlay" class="menu-overlay" onclick="ocultar()"></div>

    <aside id="sidebar" class="sidebar" aria-hidden="true">
        <div class="sidebar-head">
            <div class="sidebar-head-text">
                <h2 class="sidebar-title">Menu Principal</h2>
                <p class="sidebar-subtitle">Reportes y configuracion</p>
            </div>
            <div class="sidebar-head-actions">
                <button type="button" id="menuModeToggle" class="menu-mode-toggle" aria-label="Alternar menu iconos/completo">
                    <i class="fas fa-compress-arrows-alt"></i>
                </button>
                <a href="#" class="boton-cerrar" onclick="ocultar()" aria-label="Cerrar menu">&times;</a>
            </div>
        </div>

        <ul class="menu">
            <?php
            function generarEnlaceMenu($id, $icono, $texto) {
                echo '<li>';
                echo '<a href="#" class="menu-link" id="' . $id . '">';
                echo '<i class="fas ' . $icono . '"></i><span>' . $texto . '</span>';
                echo '</a>';
                echo '</li>';
            }

            foreach ($menusPrincipalesPermitidos as $menuDef) {
                generarEnlaceMenu($menuDef['id'], $menuDef['icono'], $menuDef['texto']);
            }
            ?>
        </ul>
    </aside>

    <main id="contenido" class="container-fluid content-shell">
        <section class="tab-workspace">
            <nav id="tabBar" class="tab-bar" aria-label="Pestanas abiertas"></nav>
            <section id="tabPanels" class="tab-panels"></section>
        </section>
    </main>
</body>
</html>
