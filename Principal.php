<!doctype html>
<?php
session_start();

// Función para verificar si el usuario ha iniciado sesión
function verificarSesion() {
    if (empty($_SESSION["user"])) {
        // Redirigir al usuario a la página de inicio de sesión si no ha iniciado sesión
        header("Location: index.php");
        exit();
    }
}

// Verificar la sesión del usuario
verificarSesion();

?>

<html lang="es" data-bs-theme="auto">
<head>
    <!-- Metadatos -->
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="Página Principal">
    <meta name="author" content="Leonardo Navarro">

    <title>Página Principal</title>
	
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

	<!-- Select2 -->
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>


    <!-- Incluir estilos -->
    <link href="css/bootstrap.min.css" rel="stylesheet">
    <link href="css/styles.css" rel="stylesheet">
    <link href="css/sidebars.css" rel="stylesheet">
    <link href="css/bootstrap-clockpicker.css" rel="stylesheet">
    <link href="css/datatables.min.css" rel="stylesheet">
    <link href="css/alertify.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
	<link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- Incluir scripts -->
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

    <script src="js/scripts.js?v=<?php echo date('Ymd_his'); ?>"></script>
</head>

<body class="bodyp">
    <!-- Etiqueta para abrir/cerrar el sidebar -->
    <label for="abrir-cerrar">
        <div class="row">
            <div class="col">
                <span id="abr" class="abrir" onclick="mostrar()">&#9776; Abrir</span>
            </div>
            <div class="col text-end">
                <span><?php echo htmlspecialchars($_SESSION["user"]); ?></span>
            </div>
        </div>
    </label>

    <!-- Sidebar -->
    <div id="sidebar" class="sidebar">
        <a href="#" class="boton-cerrar" onclick="ocultar()">&times;</a>
        <ul class="menu">
            <?php
            // Función para generar los enlaces del menú
            function generarEnlaceMenu($id, $icono, $texto) {
                echo '<li>';
                echo '<a href="#" class="nav-link text-white" id="' . $id . '">';
                echo '<i class="fas ' . $icono . '"></i> ' . $texto;
                echo '</a>';
                echo '</li>';
            }

            // Generar enlaces del menú
            generarEnlaceMenu("listasmovsexis", "fa-tachometer-alt", "Lista sin Mov y sin Exis"); // Enlace para la lista sin movimientos y sin existencias
            generarEnlaceMenu("listasmovcexis", "fa-table", "Lista Sin Mov y Con Exis"); // Enlace para la lista sin movimientos y con existencias
            generarEnlaceMenu("listaclasificac", "fa-chart-pie", "ABC Costo Inventario"); // Enlace para la clasificación ABC del costo de inventario
            //generarEnlaceMenu("listaabcexistenciainventario", "fa-boxes", "ABC Existencia Inventario"); // Enlace para la clasificación ABC de la existencia en inventario
            //generarEnlaceMenu("listaabcventainventario", "fa-shopping-cart", "ABC Venta Inventario"); // Enlace para la clasificación ABC de la venta en inventario
            //generarEnlaceMenu("listaabccostorepuestos", "fa-tools", "ABC Costo Repuestos"); // Enlace para la clasificación ABC del costo de repuestos
            //generarEnlaceMenu("listaabccostomotos", "fa-motorcycle", "ABC Costo Motos"); // Enlace para la clasificación ABC del costo de motos
            //generarEnlaceMenu("listaabcexistenciarepuestos", "fa-cogs", "ABC Existencia Repuestos"); // Enlace para la clasificación ABC de la existencia de repuestos
            //generarEnlaceMenu("listaabcexistenciamotos", "fa-motorcycle", "ABC Existencia Motos"); // Enlace para la clasificación ABC de la existencia de motos
            //generarEnlaceMenu("listaabcventarepuestos", "fa-tools", "ABC Venta Repuestos"); // Enlace para la clasificación ABC de la venta de repuestos
            //generarEnlaceMenu("listaabcventamotos", "fa-motorcycle", "ABC Venta Motos"); // Enlace para la clasificación ABC de la venta de motos
            //generarEnlaceMenu("comparativo", "fa-balance-scale", "Comparativo"); // Enlace para el comparativo
            generarEnlaceMenu("log", "fa-history", "Log Máximos y Mínimos"); // Enlace para el log de máximos y mínimos
			generarEnlaceMenu("backorder", "fa-table", "BackOrder");
            //generarEnlaceMenu("informeped", "fa-file-alt", "Informe Pedido Actual"); // Enlace para el informe del pedido actual
            generarEnlaceMenu("listarotacion", "fa-sync-alt", "Rotación Inventario"); // Enlace para la rotación de inventario
			generarEnlaceMenu("pedidosgeneradosauto", "fa-table", "Pedidos Generados");
            generarEnlaceMenu("listaestados", "fa-clipboard-list", "Estados Pedidos"); // Enlace para los estados de pedidos
            generarEnlaceMenu("configuracionvencimientoxgrupos", "fa-hourglass-end", "Configuración Vencimiento por grupos"); // Enlace Configuración Vencimiento por grupos
            generarEnlaceMenu("recalcularnumericas", "fa-history", "Recalcular Numéricas");
			generarEnlaceMenu("listaconfiguracionlineas", "fa-table", "Configuración Lineas");
			generarEnlaceMenu("listadoproductosclasificados", "fa-boxes", "Productos Clasificados");
			generarEnlaceMenu("listaconexiones", "fa-database", "Conexiones"); // Enlace para las conexiones
            generarEnlaceMenu("listaconfiguraciones", "fa-cogs", "Configuraciones"); // Enlace para las configuraciones
            generarEnlaceMenu("salir", "fa-sign-out-alt", "Salir"); // Enlace para salir
            ?>
        </ul>
    </div>

    <!-- Contenido principal -->
    <div id="contenido" class="container-fluid">
        <center></center>
    </div>
</body>
</html>