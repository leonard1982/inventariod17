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
