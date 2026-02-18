<?php
require('conecta.php');

header('Content-Type: application/json; charset=UTF-8');

function respuestaPermisos($ok, $payload = array()) {
    echo json_encode(array_merge(array('ok' => $ok ? true : false), $payload));
    exit;
}

if (empty($_SESSION['user'])) {
    respuestaPermisos(false, array('message' => 'Sesion no valida.'));
}

if (!usuarioPuedeAdministrarPermisosMenu($_SESSION['user'])) {
    respuestaPermisos(false, array('message' => 'Acceso denegado. No tiene permiso para gestionar permisos.'));
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
if ($action === '') {
    respuestaPermisos(false, array('message' => 'Accion no informada.'));
}

function limpiarCadenaSql($txt) {
    return str_replace("'", "''", trim((string)$txt));
}

function siguienteIdPermisoMenu($conexion) {
    $next = 1;
    $sql = "SELECT COALESCE(MAX(ID), 0) + 1 AS NEXT_ID FROM SN_MENU_PERMISOS";
    if ($vc = $conexion->consulta($sql)) {
        if ($vr = ibase_fetch_object($vc)) {
            $next = (int)$vr->NEXT_ID;
        }
    }
    return $next;
}

function normalizarDocumentoConductor($txt) {
    $v = strtoupper(trim((string)$txt));
    $v = str_replace(array(' ', '.', '-', '/', ','), '', $v);
    return $v;
}

function obtenerResumenConductorDesdeValor($conexionActual, $valor) {
    $valor = trim((string)$valor);
    if ($valor === '') {
        return null;
    }

    $valorCanon = normalizarDocumentoConductor($valor);

    if (ctype_digit($valorCanon)) {
        $terid = (int)$valorCanon;
        $sqlTer = "SELECT FIRST 1 TERID, NOMBRE, NIT, NITTRI
                   FROM TERCEROS
                   WHERE TERID = $terid
                     AND COALESCE(CONDUCTOR, 'N') = 'S'";
        if ($vcTer = $conexionActual->consulta($sqlTer)) {
            if ($vrTer = ibase_fetch_object($vcTer)) {
                return array(
                    'terid' => (int)$vrTer->TERID,
                    'nombre' => trim((string)$vrTer->NOMBRE),
                    'nit' => trim((string)$vrTer->NIT),
                    'nittri' => trim((string)$vrTer->NITTRI),
                    'origen' => 'TERID'
                );
            }
        }
    }

    $valorEsc = limpiarCadenaSql($valor);
    $valorCanonEsc = limpiarCadenaSql($valorCanon);

    $sqlDoc = "SELECT FIRST 1 TERID, NOMBRE, NIT, NITTRI
               FROM TERCEROS
               WHERE COALESCE(CONDUCTOR, 'N') = 'S'
                 AND (
                      UPPER(TRIM(COALESCE(NIT, ''))) = UPPER('$valorEsc')
                   OR UPPER(TRIM(COALESCE(NITTRI, ''))) = UPPER('$valorEsc')
                   OR UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(NIT, '')), ' ', ''), '.', ''), '-', ''), '/', ''), ',', '')) = '$valorCanonEsc'
                   OR UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(NITTRI, '')), ' ', ''), '.', ''), '-', ''), '/', ''), ',', '')) = '$valorCanonEsc'
                 )
               ORDER BY TERID";
    if ($vcDoc = $conexionActual->consulta($sqlDoc)) {
        if ($vrDoc = ibase_fetch_object($vcDoc)) {
            return array(
                'terid' => (int)$vrDoc->TERID,
                'nombre' => trim((string)$vrDoc->NOMBRE),
                'nit' => trim((string)$vrDoc->NIT),
                'nittri' => trim((string)$vrDoc->NITTRI),
                'origen' => 'NIT/NITTRI'
            );
        }
    }

    return null;
}

function obtenerValorVariableVarios($conexionActual, $variab) {
    $variabEsc = limpiarCadenaSql($variab);
    $sql = "SELECT FIRST 1 CONTENIDO FROM VARIOS WHERE VARIAB = '$variabEsc'";
    if ($vc = $conexionActual->consulta($sql)) {
        if ($vr = ibase_fetch_object($vc)) {
            return trim((string)$vr->CONTENIDO);
        }
    }
    return '';
}

function guardarValorVariableVarios($conexionActual, $variab, $contenido) {
    $variabEsc = limpiarCadenaSql($variab);
    $contenidoEsc = limpiarCadenaSql($contenido);

    $sqlEx = "SELECT FIRST 1 VARIAB FROM VARIOS WHERE VARIAB = '$variabEsc'";
    $existe = false;
    if ($vcEx = $conexionActual->consulta($sqlEx)) {
        $existe = (bool)ibase_fetch_row($vcEx);
    }

    if ($existe) {
        $sqlUp = "UPDATE VARIOS SET CONTENIDO = '$contenidoEsc' WHERE VARIAB = '$variabEsc'";
        $conexionActual->consulta($sqlUp);
    } else {
        $sqlIn = "INSERT INTO VARIOS (VARIAB, CONTENIDO) VALUES ('$variabEsc', '$contenidoEsc')";
        $conexionActual->consulta($sqlIn);
    }
}

function eliminarVariableVarios($conexionActual, $variab) {
    $variabEsc = limpiarCadenaSql($variab);
    $sqlDel = "DELETE FROM VARIOS WHERE VARIAB = '$variabEsc'";
    $conexionActual->consulta($sqlDel);
}

if (in_array($action, array('obtener_permisos_usuario', 'guardar_permisos_usuario', 'limpiar_permisos_usuario'), true)) {
    if (!$conect_bd_inventario) {
        respuestaPermisos(false, array('message' => 'No hay conexion a BD de inventarios.'));
    }
    if (!existeTablaPermisosMenu()) {
        respuestaPermisos(false, array('message' => 'No existe la tabla SN_MENU_PERMISOS. Ejecuta 02_create_permisos_menu.sql.'));
    }
}

if ($action === 'obtener_permisos_usuario') {
    $usuario = isset($_POST['usuario']) ? normalizarUsuarioMenu($_POST['usuario']) : '';
    if ($usuario === '') {
        respuestaPermisos(false, array('message' => 'Usuario requerido.'));
    }

    $usuarioEsc = limpiarCadenaSql($usuario);
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

    respuestaPermisos(true, array(
        'configurado' => $cont > 0,
        'permisos' => $mapa,
        'usuario_objetivo_admin' => esUsuarioAdministradorMenu($usuario)
    ));
}

if ($action === 'guardar_permisos_usuario') {
    $usuario = isset($_POST['usuario']) ? normalizarUsuarioMenu($_POST['usuario']) : '';
    $estadoJson = isset($_POST['estado_json']) ? (string)$_POST['estado_json'] : '';

    if ($usuario === '') {
        respuestaPermisos(false, array('message' => 'Usuario requerido.'));
    }

    $estado = json_decode($estadoJson, true);
    if (!is_array($estado)) {
        respuestaPermisos(false, array('message' => 'Formato de permisos invalido.'));
    }

    $catalogo = obtenerCatalogoMenusAplicacion();
    $usuarioObjetivoAdmin = esUsuarioAdministradorMenu($usuario);
    $usuarioEdita = normalizarUsuarioMenu($_SESSION['user']);

    $usuarioEsc = limpiarCadenaSql($usuario);
    $sqlDel = "DELETE FROM SN_MENU_PERMISOS WHERE UPPER(USUARIO) = '$usuarioEsc'";
    $conect_bd_inventario->consulta($sqlDel);

    $nextId = siguienteIdPermisoMenu($conect_bd_inventario);

    foreach ($catalogo as $menuId => $meta) {
        if ($menuId === 'salir') {
            continue;
        }

        $menuKey = strtolower($menuId);
        $flag = isset($estado[$menuKey]) ? strtoupper(trim((string)$estado[$menuKey])) : 'N';
        $permitido = ($flag === 'S') ? 'S' : 'N';

        if (!empty($meta['solo_admin']) && empty($meta['delegable_admin']) && !$usuarioObjetivoAdmin) {
            $permitido = 'N';
        }

        $menuEsc = limpiarCadenaSql($menuKey);
        $usuarioEditaEsc = limpiarCadenaSql($usuarioEdita);

        $sqlIns = "INSERT INTO SN_MENU_PERMISOS (ID, USUARIO, MENU_ID, PERMITIDO, FECHA_CREACION, FECHA_EDICION, USUARIO_EDITA) " .
                  "VALUES ($nextId, '$usuarioEsc', '$menuEsc', '$permitido', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, '$usuarioEditaEsc')";
        $conect_bd_inventario->consulta($sqlIns);
        $nextId++;
    }

    respuestaPermisos(true, array('message' => 'Permisos guardados.'));
}

if ($action === 'limpiar_permisos_usuario') {
    $usuario = isset($_POST['usuario']) ? normalizarUsuarioMenu($_POST['usuario']) : '';
    if ($usuario === '') {
        respuestaPermisos(false, array('message' => 'Usuario requerido.'));
    }

    $usuarioEsc = limpiarCadenaSql($usuario);
    $sqlDel = "DELETE FROM SN_MENU_PERMISOS WHERE UPPER(USUARIO) = '$usuarioEsc'";
    $conect_bd_inventario->consulta($sqlDel);

    respuestaPermisos(true, array('message' => 'Permisos restaurados por defecto.'));
}

if (in_array($action, array('obtener_vende_usuario', 'guardar_vende_usuario', 'limpiar_vende_usuario'), true)) {
    if (!$conect_bd_actual) {
        respuestaPermisos(false, array('message' => 'No hay conexion a BD actual para leer VARIOS.'));
    }
}

if ($action === 'obtener_vende_usuario') {
    $usuario = isset($_POST['usuario']) ? normalizarUsuarioMenu($_POST['usuario']) : '';
    if ($usuario === '') {
        respuestaPermisos(false, array('message' => 'Usuario requerido.'));
    }

    $variab = 'GVENDE' . $usuario;
    $variabPv = 'GVENDEPV' . $usuario;
    $valor = obtenerValorVariableVarios($conect_bd_actual, $variab);
    $conductor = obtenerResumenConductorDesdeValor($conect_bd_actual, $valor);

    respuestaPermisos(true, array(
        'variab' => $variab,
        'variab_pv' => $variabPv,
        'valor' => $valor,
        'resuelto' => $conductor ? true : false,
        'conductor' => $conductor
    ));
}

if ($action === 'guardar_vende_usuario') {
    $usuario = isset($_POST['usuario']) ? normalizarUsuarioMenu($_POST['usuario']) : '';
    $valor = isset($_POST['valor']) ? trim((string)$_POST['valor']) : '';

    if ($usuario === '') {
        respuestaPermisos(false, array('message' => 'Usuario requerido.'));
    }

    if ($valor === '') {
        respuestaPermisos(false, array('message' => 'Debes indicar TERID o NIT/NITTRI del conductor.'));
    }

    if (strlen($valor) > 60) {
        $valor = substr($valor, 0, 60);
    }

    $variab = 'GVENDE' . $usuario;
    $variabPv = 'GVENDEPV' . $usuario;
    guardarValorVariableVarios($conect_bd_actual, $variab, $valor);
    guardarValorVariableVarios($conect_bd_actual, $variabPv, $valor);

    $conductor = obtenerResumenConductorDesdeValor($conect_bd_actual, $valor);
    respuestaPermisos(true, array(
        'message' => 'Variables ' . $variab . ' y ' . $variabPv . ' guardadas.',
        'variab' => $variab,
        'variab_pv' => $variabPv,
        'valor' => $valor,
        'resuelto' => $conductor ? true : false,
        'conductor' => $conductor
    ));
}

if ($action === 'limpiar_vende_usuario') {
    $usuario = isset($_POST['usuario']) ? normalizarUsuarioMenu($_POST['usuario']) : '';
    if ($usuario === '') {
        respuestaPermisos(false, array('message' => 'Usuario requerido.'));
    }

    $variab = 'GVENDE' . $usuario;
    $variabPv = 'GVENDEPV' . $usuario;
    eliminarVariableVarios($conect_bd_actual, $variab);
    eliminarVariableVarios($conect_bd_actual, $variabPv);

    respuestaPermisos(true, array(
        'message' => 'Variables ' . $variab . ' y ' . $variabPv . ' eliminadas.',
        'variab' => $variab,
        'variab_pv' => $variabPv
    ));
}

respuestaPermisos(false, array('message' => 'Accion no soportada.'));
