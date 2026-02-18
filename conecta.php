<?php
// Establece la zona horaria
date_default_timezone_set('America/Bogota');

// Verificar si la sesión ya está iniciada
if (!isset($_SESSION)) {
    session_start();
}

// Mostrar errores para depuración
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Configurar la zona horaria
date_default_timezone_set('America/Bogota');

// Incluir archivos necesarios
include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';

// Variables iniciales
$bd = '';
$ip = '127.0.0.1';
$varchivo = "bd_admin.txt";
$vprefijos = "";
$varchivopj = "";
$vbd_actual = "";
$vbd_anterior = "";
$vbd_inventarios = "";

// Función para leer el contenido de un archivo
function leerArchivo($nombreArchivo) {
    $contenido = '';
    if (file_exists($nombreArchivo)) {
        $fp = fopen($nombreArchivo, "r");
        if ($fp) {
            $contenido = fgets($fp);
            fclose($fp);
        }
    }
    return $contenido;
}

// Resuelve rutas de BD sin unidad (ej: :\DATOS TNS\archivo.GDB)
function resolverRutaBaseDatos($rutaCruda) {
    $ruta = trim($rutaCruda);

    if ($ruta === '') {
        return '';
    }

    // Ruta completa con unidad (ej: C:\ o C:/)
    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $ruta)) {
        return $ruta;
    }

    // Ruta sin unidad (ej: :\DATOS TNS\...)
    if (preg_match('/^:[\\\\\\/]/', $ruta)) {
        $sufijoRuta = substr($ruta, 1);

        // 1) Probar la misma unidad donde está el proyecto
        if (preg_match('/^([A-Za-z]):/', __DIR__, $matches)) {
            $unidadProyecto = strtoupper($matches[1]) . ':';
            $candidata = $unidadProyecto . $sufijoRuta;
            if (file_exists($candidata)) {
                return $candidata;
            }
        }

        // 2) Buscar en otras unidades
        foreach (range('A', 'Z') as $unidad) {
            $candidata = $unidad . ':' . $sufijoRuta;
            if (file_exists($candidata)) {
                return $candidata;
            }
        }
    }

    return $ruta;
}

// Función para buscar un archivo en diferentes unidades
function buscarArchivo($nombreArchivo) {
    // 1) Buscar primero en la carpeta real del proyecto (donde vive conecta.php)
    $localPath = __DIR__ . DIRECTORY_SEPARATOR . $nombreArchivo;
    if (file_exists($localPath)) {
        return $localPath;
    }

    // 2) Compatibilidad con instalaciones heredadas en diferentes unidades
    $rutasProyecto = array(
        "/facilweb/htdocs/evento_inventario/",
        "/facilweb_fe73_32/htdocs/evento_inventario/"
    );

    $drives = range('A', 'Z');
    foreach ($drives as $drive) {
        foreach ($rutasProyecto as $rutaProyecto) {
            $path = $drive . ":" . $rutaProyecto;
            if (file_exists($path . $nombreArchivo)) {
                return $path . $nombreArchivo;
            }
        }
    }
    return '';
}

// Buscar archivos necesarios
$varchivopj = buscarArchivo("prefijos.txt");
$vbd_actual = buscarArchivo("bd_actual.txt");
$vbd_anterior = buscarArchivo("bd_anterior.txt");
$vbd_inventarios = buscarArchivo("bd_inventarios.txt");

// Leer el contenido de los archivos
$contenidoPrefijos = leerArchivo($varchivopj);
$contenidoBdActual = resolverRutaBaseDatos(leerArchivo($vbd_actual));
$contenidoBdAnterior = resolverRutaBaseDatos(leerArchivo($vbd_anterior));
$contenidoBdInventarios = resolverRutaBaseDatos(leerArchivo($vbd_inventarios));

// Validar bases de datos
function validarBaseDatos($rutaArchivo, $nombreBase, $mostrarError = true) {
    if (file_exists($rutaArchivo)) {
        $rutaConfigurada = leerArchivo($rutaArchivo);
        $rutaBaseDatos = resolverRutaBaseDatos($rutaConfigurada);

        if (empty($rutaBaseDatos) || !file_exists($rutaBaseDatos)) {
            if ($mostrarError) {
                echo "NO SE ENCUENTRA LA BASE DE DATOS $nombreBase -- ";
            }
            return false;
        }
    } else {
        if ($mostrarError) {
            echo "NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE $nombreBase -- ";
        }
        return false;
    }

    return true;
}

$bdActualDisponible = validarBaseDatos($vbd_actual, "ACTUAL", true);
$bdAnteriorDisponible = validarBaseDatos($vbd_anterior, "ANTERIOR", false);
$bdInventariosDisponible = validarBaseDatos($vbd_inventarios, "DE INVENTARIOS", false);

// Validar archivo de prefijos
if (file_exists($varchivopj)) {
    $fpj = fopen($varchivopj, "r");
    while (!feof($fpj)) {
        $vprefijos = fgets($fpj);
    }
    fclose($fpj);

    if (empty($vprefijos)) {
        echo "NO SE HAN CONFIGURADO PREFIJOS -- ";
    }
} else {
    echo "NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACIÓN DE PREFIJOS -- ";
}

if (empty($contenidoBdActual)) {
    exit;
}

// Hacer conexión a bases de datos
$conect_bd_anterior = null;
$conect_bd_actual = null;
$conect_bd_actualPDO = null;
$conect_bd_inventario = null;

// La BD actual es obligatoria para autenticación y operación principal
if (!$bdActualDisponible || !file_exists($contenidoBdActual)) {
    echo "NO SE ENCUENTRA LA BASE DE DATOS ACTUAL -- ";
    exit;
}

$conect_bd_actual = new dbFirebird($ip, $contenidoBdActual);
$conect_bd_actualPDO = new dbFirebirdPDO($ip, $contenidoBdActual);

// Conexiones auxiliares: se crean solo si la ruta existe
if ($bdAnteriorDisponible && !empty($contenidoBdAnterior) && file_exists($contenidoBdAnterior)) {
    $conect_bd_anterior = new dbFirebird($ip, $contenidoBdAnterior);
}

if ($bdInventariosDisponible && !empty($contenidoBdInventarios) && file_exists($contenidoBdInventarios)) {
    $conect_bd_inventario = new dbFirebird($ip, $contenidoBdInventarios);
}

// Función para generar opciones de un select a partir de una consulta SQL
function generarOpcionesSelect($conexion, $sql, $valueField, $textField) {
    $options = '';
    if ($result = $conexion->consulta($sql)) {
        while ($row = ibase_fetch_object($result)) {
            $options .= '<option value="' . htmlspecialchars($row->$valueField) . '">' . htmlspecialchars($row->$textField) . '</option>';
        }
    }
    return $options;
}

function createFloatingButton($icon, $name, $target) {
    echo '
    <button class="btn btn-primary floating-button" id="scrollButton" style="display: none; position: fixed; bottom: 20px; right: 20px;">
        <a href="' . $target . '" style="text-decoration:none;color:white;"><i class="' . $icon . '"></i></a>
    </button>

    <script>
        window.addEventListener("scroll", function() {
            var scrollButton = document.getElementById("scrollButton");
            if (window.pageYOffset > 0) {
                scrollButton.style.display = "block";
            } else {
                scrollButton.style.display = "none";
            }
        });
    </script>';
}
function includeAssets() {
    echo '
    <!-- Scripts CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/datatables.min.css">
    <link rel="stylesheet" href="css/bootstrap-clockpicker.css">
    <link rel="stylesheet" href="css/alertify.min.css">
    <link rel="stylesheet" href="fullcalendar/main.css">
    <link rel="stylesheet" href="css/sortable-theme-dark.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/css/bootstrap.min.css">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/css/select2.min.css" rel="stylesheet" />

    <!-- Scripts JS -->
    <script src="js/jquery-3.6.0.min.js"></script>
    <script src="js/popper.min.js"></script>
    <script src="js/bootstrap.min.js"></script>
    <script src="js/datatables.min.js"></script>
    <script src="js/bootstrap-clockpicker.js"></script>
    <script src="js/moment-with-locales.js"></script>
    <script src="js/alertify.js"></script>
    <script src="js/jquery.blockUI.js"></script>
    <script src="js/jquery.quicksearch.js"></script>
    <script src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
    <script src="js/sortable.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/2.11.6/umd/popper.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.1.3/js/bootstrap.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    ';
}

function normalizarUsuarioMenu($usuario) {
    return strtoupper(trim((string)$usuario));
}

function obtenerCatalogoMenusAplicacion() {
    return array(
        'listasmovsexis' => array('texto' => 'Lista sin Mov y sin Exis', 'tipo' => 'principal', 'url' => 'ListaSinMovSinExis.php'),
        'listasmovcexis' => array('texto' => 'Lista sin Mov y con Exis', 'tipo' => 'principal', 'url' => 'ListaSinMovConExis.php'),
        'listacmovsexis' => array('texto' => 'Lista con Mov y sin Exis', 'tipo' => 'principal', 'url' => 'ListaConMovSinExis.php'),
        'listaclasificac' => array('texto' => 'ABC Costo Inventario', 'tipo' => 'principal', 'url' => 'ListaClasificacionCosto.php'),
        'log' => array('texto' => 'Log Maximos y Minimos', 'tipo' => 'principal', 'url' => 'Log_maximos_minimos.php'),
        'backorder' => array('texto' => 'BackOrder', 'tipo' => 'principal', 'url' => 'backorder.php'),
        'guiasdespachos' => array('texto' => 'GUIAS (Despachos)', 'tipo' => 'principal', 'url' => 'guias_despachos.php'),
        'centrokpi' => array('texto' => 'Centro KPI', 'tipo' => 'principal', 'url' => 'centro_kpi.php'),
        'despachosconductor' => array('texto' => 'Despachos conductor', 'tipo' => 'principal', 'url' => 'despachos_conductor.php'),
        'rutaconductor' => array('texto' => 'Ruta conductor (Mapa)', 'tipo' => 'principal', 'url' => 'ruta_conductor_mapa.php'),
        'retirados' => array('texto' => 'Retirados', 'tipo' => 'principal', 'url' => 'retirados.php'),
        'listarotacion' => array('texto' => 'Rotacion Inventario', 'tipo' => 'principal', 'url' => 'rotacion_inventario.php'),
        'pedidosgeneradosauto' => array('texto' => 'Pedidos Generados', 'tipo' => 'principal', 'url' => 'PedidosGeneradosAutomaticamente.php'),
        'listaestados' => array('texto' => 'Estados Pedidos', 'tipo' => 'principal', 'url' => 'estados_pedidos.php'),
        'configuracionvencimientoxgrupos' => array('texto' => 'Configuracion Vencimiento por grupos', 'tipo' => 'principal', 'url' => 'ConfiguracionVencimientoPorProductos.php'),
        'recalcularnumericas' => array('texto' => 'Recalcular Numericas', 'tipo' => 'principal', 'url' => 'recalcularnumericas.php'),
        'listaconfiguracionlineas' => array('texto' => 'Configuracion Lineas', 'tipo' => 'principal', 'url' => 'ListaConfiguracionLineas.php'),
        'listadoproductosclasificados' => array('texto' => 'Productos Clasificados', 'tipo' => 'principal', 'url' => 'listado_productos_clasificados.php'),
        'listaconfiguraciones' => array('texto' => 'Configuraciones', 'tipo' => 'usuario', 'url' => 'ListaConfiguraciones.php'),
        'listaconexiones' => array('texto' => 'Conexiones', 'tipo' => 'usuario', 'url' => 'conexiones.php'),
        'permisosmenu' => array('texto' => 'Permisos de menu', 'tipo' => 'usuario', 'url' => 'permisos_menu.php', 'solo_admin' => true, 'delegable_admin' => true),
        'salir' => array('texto' => 'Salir', 'tipo' => 'usuario', 'url' => 'index.php')
    );
}

function esUsuarioAdministradorMenu($usuario) {
    $usuarioNorm = normalizarUsuarioMenu($usuario);
    if ($usuarioNorm === 'ADMIN') {
        return true;
    }

    global $conect_bd_actual;
    if (!$conect_bd_actual) {
        return false;
    }

    $usuarioEsc = str_replace("'", "''", $usuarioNorm);
    $sql = "SELECT FIRST 1 ROL FROM USUARIOS WHERE UPPER(NOMBRE) = '$usuarioEsc'";
    if ($vc = $conect_bd_actual->consulta($sql)) {
        if ($vr = ibase_fetch_object($vc)) {
            $rol = strtoupper(trim((string)$vr->ROL));
            if (in_array($rol, array('ADMIN', 'ADMINISTRADOR'), true)) {
                return true;
            }
        }
    }

    return false;
}

function existeTablaPermisosMenu() {
    global $conect_bd_inventario;
    if (!$conect_bd_inventario) {
        return false;
    }

    $sql = "SELECT FIRST 1 RDB\$RELATION_NAME FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = 'SN_MENU_PERMISOS'";
    if ($vc = $conect_bd_inventario->consulta($sql)) {
        if (ibase_fetch_row($vc)) {
            return true;
        }
    }

    return false;
}

function obtenerEstadoPermisosMenuUsuario($usuario) {
    static $cache = array();

    $usuarioNorm = normalizarUsuarioMenu($usuario);
    if ($usuarioNorm === '') {
        return array(
            'es_admin' => false,
            'configurado' => false,
            'permitidos' => array(),
            'mapa' => array()
        );
    }

    if (isset($cache[$usuarioNorm])) {
        return $cache[$usuarioNorm];
    }

    $catalogo = obtenerCatalogoMenusAplicacion();
    $todosIds = array_keys($catalogo);
    $esAdmin = esUsuarioAdministradorMenu($usuarioNorm);

    if ($esAdmin) {
        $cache[$usuarioNorm] = array(
            'es_admin' => true,
            'configurado' => true,
            'permitidos' => $todosIds,
            'mapa' => array()
        );
        return $cache[$usuarioNorm];
    }

    global $conect_bd_inventario;
    if (!$conect_bd_inventario || !existeTablaPermisosMenu()) {
        $cache[$usuarioNorm] = array(
            'es_admin' => false,
            'configurado' => false,
            'permitidos' => $todosIds,
            'mapa' => array()
        );
        return $cache[$usuarioNorm];
    }

    $usuarioEsc = str_replace("'", "''", $usuarioNorm);
    $sql = "SELECT MENU_ID, PERMITIDO FROM SN_MENU_PERMISOS WHERE UPPER(USUARIO) = '$usuarioEsc'";
    $mapa = array();
    $cont = 0;

    if ($vc = $conect_bd_inventario->consulta($sql)) {
        while ($vr = ibase_fetch_object($vc)) {
            $menuId = strtolower(trim((string)$vr->MENU_ID));
            $permitido = strtoupper(trim((string)$vr->PERMITIDO));
            $mapa[$menuId] = ($permitido === 'S') ? 'S' : 'N';
            $cont++;
        }
    }

    if ($cont === 0) {
        $cache[$usuarioNorm] = array(
            'es_admin' => false,
            'configurado' => false,
            'permitidos' => $todosIds,
            'mapa' => array()
        );
        return $cache[$usuarioNorm];
    }

    $permitidos = array('salir');
    foreach ($mapa as $id => $flag) {
        if ($flag === 'S') {
            $permitidos[] = $id;
        }
    }

    $cache[$usuarioNorm] = array(
        'es_admin' => false,
        'configurado' => true,
        'permitidos' => array_values(array_unique($permitidos)),
        'mapa' => $mapa
    );
    return $cache[$usuarioNorm];
}

function usuarioTienePermisoMenu($usuario, $menuId) {
    $menu = strtolower(trim((string)$menuId));
    if ($menu === '' || $menu === 'salir') {
        return true;
    }

    $estado = obtenerEstadoPermisosMenuUsuario($usuario);
    if ($estado['es_admin']) {
        return true;
    }

    if (!$estado['configurado']) {
        return true;
    }

    if (!isset($estado['mapa'][$menu])) {
        return false;
    }

    return $estado['mapa'][$menu] === 'S';
}

function usuarioTienePermisoExplicitoMenu($usuario, $menuId) {
    $menu = strtolower(trim((string)$menuId));
    if ($menu === '' || $menu === 'salir') {
        return true;
    }

    $estado = obtenerEstadoPermisosMenuUsuario($usuario);
    if ($estado['es_admin']) {
        return true;
    }

    if (!$estado['configurado']) {
        return false;
    }

    if (!isset($estado['mapa'][$menu])) {
        return false;
    }

    return $estado['mapa'][$menu] === 'S';
}

function usuarioPuedeAdministrarPermisosMenu($usuario) {
    if (esUsuarioAdministradorMenu($usuario)) {
        return true;
    }
    return usuarioTienePermisoExplicitoMenu($usuario, 'permisosmenu');
}

function obtenerMenusPermitidosUsuario($usuario, $tipo = null) {
    $catalogo = obtenerCatalogoMenusAplicacion();
    $estado = obtenerEstadoPermisosMenuUsuario($usuario);
    $esAdmin = $estado['es_admin'];
    $resultado = array();

    foreach ($catalogo as $menuId => $meta) {
        if ($tipo !== null && isset($meta['tipo']) && $meta['tipo'] !== $tipo) {
            continue;
        }

        $soloAdmin = !empty($meta['solo_admin']);
        if ($soloAdmin && !$esAdmin) {
            $delegableAdmin = !empty($meta['delegable_admin']);
            if (!$delegableAdmin) {
                continue;
            }

            $menuKey = strtolower((string)$menuId);
            if (!$estado['configurado'] || !isset($estado['mapa'][$menuKey]) || $estado['mapa'][$menuKey] !== 'S') {
                continue;
            }
        }

        if (!usuarioTienePermisoMenu($usuario, $menuId)) {
            continue;
        }

        $resultado[$menuId] = $meta;
    }

    return $resultado;
}

function obtenerMapaArchivoMenuAplicacion() {
    return array(
        'listasinmovsinexis.php' => 'listasmovsexis',
        'listasinmovsinexis_ajax.php' => 'listasmovsexis',
        'listasinmovconexis.php' => 'listasmovcexis',
        'listasinmovconexis_ajax.php' => 'listasmovcexis',
        'listaconmovsinexis.php' => 'listacmovsexis',
        'listaconmovsinexis_ajax.php' => 'listacmovsexis',
        'listaclasificacioncosto.php' => 'listaclasificac',
        'log_maximos_minimos.php' => 'log',
        'backorder.php' => 'backorder',
        'backorder_detalle.php' => 'backorder',
        'backorder_actualizar_estado.php' => 'backorder',
        'guias_despachos.php' => 'guiasdespachos',
        'guias_despachos_ajax.php' => 'guiasdespachos',
        'guia_despacho_print.php' => 'guiasdespachos',
        'centro_kpi.php' => 'centrokpi',
        'centro_kpi_ajax.php' => 'centrokpi',
        'despachos_conductor.php' => 'despachosconductor',
        'despachos_conductor_ajax.php' => 'despachosconductor',
        'ruta_conductor_mapa.php' => 'rutaconductor',
        'ruta_conductor_mapa_ajax.php' => 'rutaconductor',
        'ruta_conductor_mapa_pdf.php' => 'rutaconductor',
        'retirados.php' => 'retirados',
        'retirados_ajax.php' => 'retirados',
        'rotacion_inventario.php' => 'listarotacion',
        'rotacion_inventario_ajax.php' => 'listarotacion',
        'pedidosgeneradosautomaticamente.php' => 'pedidosgeneradosauto',
        'estados_pedidos.php' => 'listaestados',
        'configuracionvencimientoporproductos.php' => 'configuracionvencimientoxgrupos',
        'recalcularnumericas.php' => 'recalcularnumericas',
        'recalcularnumericas_ajax.php' => 'recalcularnumericas',
        'listaconfiguracionlineas.php' => 'listaconfiguracionlineas',
        'configuracionlineas.php' => 'listaconfiguracionlineas',
        'actualizarconfiguracionlinea.php' => 'listaconfiguracionlineas',
        'listadoproductosclasificados.php' => 'listadoproductosclasificados',
        'listaconfiguraciones.php' => 'listaconfiguraciones',
        'configuraciones.php' => 'listaconfiguraciones',
        'actualizarconfiguracion.php' => 'listaconfiguraciones',
        'listaconfiguracioneseliminar.php' => 'listaconfiguraciones',
        'conexiones.php' => 'listaconexiones',
        'agregarconexion.php' => 'listaconexiones',
        'borrarconexion.php' => 'listaconexiones',
        'permisos_menu.php' => 'permisosmenu',
        'permisos_menu_ajax.php' => 'permisosmenu'
    );
}

function aplicarControlAccesoMenuActual() {
    if (php_sapi_name() === 'cli') {
        return;
    }

    if (empty($_SESSION['user'])) {
        return;
    }

    $archivo = strtolower(basename((string)$_SERVER['SCRIPT_NAME']));

    $excluir = array(
        'index.php',
        'validauser.php',
        'principal.php',
        'conecta.php'
    );

    if (in_array($archivo, $excluir, true)) {
        return;
    }

    $mapa = obtenerMapaArchivoMenuAplicacion();
    if (!isset($mapa[$archivo])) {
        return;
    }

    $menuId = $mapa[$archivo];
    if (usuarioTienePermisoMenu($_SESSION['user'], $menuId)) {
        return;
    }

    if (!headers_sent()) {
        http_response_code(403);
    }
    echo 'ACCESO DENEGADO: NO TIENE PERMISO PARA ESTE MODULO.';
    exit;
}

aplicarControlAccesoMenuActual();

//
function enviarCorreoSMTP($destinatario, $asunto, $mensaje, $de, $nombreDe, $servidorSMTP, $puertoSMTP, $usuarioSMTP, $contrasenaSMTP, $bcc = null)
{
    // Cargar el autoload de Composer
    require 'vendor/autoload.php';

    // Crear una instancia de PHPMailer
    $mail = new PHPMailer\PHPMailer\PHPMailer();

    try {
        // Configuración del servidor SMTP
        $mail->isSMTP();
        $mail->Host = $servidorSMTP;
        $mail->SMTPAuth = true;
        $mail->Username = $usuarioSMTP;
        $mail->Password = $contrasenaSMTP;
        $mail->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $puertoSMTP;

        // Configuración del correo
        $mail->setFrom($de, $nombreDe);
        $mail->addAddress($destinatario);
        if ($bcc) {
            $mail->addBCC($bcc);
        }
        $mail->Subject = $asunto;
        $mail->Body = $mensaje;
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';

        // Enviar el correo
        if ($mail->send()) {
            return 'notifico';
        } else {
            return 'Error al enviar el correo: ' . $mail->ErrorInfo;
        }
    } catch (Exception $e) {
        return 'Excepción al enviar el correo: ' . $e->getMessage();
    }
}
?>
