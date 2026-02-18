<?php
ob_start();
require('conecta.php');

header('Content-Type: application/json; charset=UTF-8');

function toUtf8Seguro($valor) {
    if (!is_string($valor)) {
        return $valor;
    }

    if (preg_match('//u', $valor)) {
        return $valor;
    }

    return utf8_encode($valor);
}

function normalizarUtf8Recursivo($dato) {
    if (is_array($dato)) {
        $out = array();
        foreach ($dato as $k => $v) {
            $out[toUtf8Seguro((string)$k)] = normalizarUtf8Recursivo($v);
        }
        return $out;
    }

    return toUtf8Seguro($dato);
}

function opcionesJson() {
    $flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) {
        $flags |= JSON_UNESCAPED_UNICODE;
    }
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) {
        $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    }
    return $flags;
}

function responder($ok, $payload = array()) {
    $salidaPrevia = '';
    if (ob_get_level() > 0) {
        $salidaPrevia = trim(ob_get_contents());
        ob_clean();
    }

    if ($salidaPrevia !== '') {
        $payload['debug_output'] = $salidaPrevia;
    }

    $base = array('ok' => $ok ? true : false);
    $respuesta = normalizarUtf8Recursivo(array_merge($base, $payload));
    $json = json_encode($respuesta, opcionesJson());

    if ($json === false) {
        $json = json_encode(array(
            'ok' => false,
            'message' => 'No se pudo serializar JSON',
            'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : 'json_error'
        ));
    }

    echo $json;
    exit;
}

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(normalizarUtf8Recursivo(array(
            'ok' => false,
            'message' => 'Error fatal PHP: ' . $error['message'],
            'file' => isset($error['file']) ? $error['file'] : '',
            'line' => isset($error['line']) ? (int)$error['line'] : 0
        )), opcionesJson());
    }
});

function obtenerPdoActual() {
    static $pdo = null;
    global $contenidoBdActual;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    // Importante: no forzar charset UTF8 en la conexion porque algunas bases legacy
    // tienen datos con codificacion mixta y Firebird puede lanzar "Malformed string".
    // La normalizacion a UTF-8 se hace al serializar la respuesta JSON.
    $pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function tablaExiste(PDO $pdo, $nombreTabla) {
    $sql = "SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(strtoupper($nombreTabla)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function columnaExiste(PDO $pdo, $tabla, $columna) {
    $sql = "SELECT COUNT(*) FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ? AND RDB\$FIELD_NAME = ?";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(strtoupper($tabla), strtoupper($columna)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function validarTablasModulo(PDO $pdo) {
    $faltantes = array();
    $requeridas = array('SN_GUIAS', 'SN_GUIAS_ESTADOS', 'SN_GUIAS_DETALLE');

    foreach ($requeridas as $tabla) {
        if (!tablaExiste($pdo, $tabla)) {
            $faltantes[] = $tabla;
        }
    }

    return $faltantes;
}

function normalizarFechaInput($fecha, $esHasta = false) {
    $valor = trim((string)$fecha);
    if ($valor === '') {
        return null;
    }

    $valor = str_replace('T', ' ', $valor);

    // datetime-local (YYYY-MM-DD HH:MM) viene sin segundos.
    // Para "hasta" usamos :59 para no excluir registros del mismo minuto.
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/', $valor)) {
        $valor .= $esHasta ? ':59' : ':00';
        return $valor;
    }

    // Si solo llega fecha (YYYY-MM-DD), expandimos al inicio/fin del dia.
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        $valor .= $esHasta ? ' 23:59:59' : ' 00:00:00';
    }

    return $valor;
}

function estadoNormalizado($estado) {
    $e = strtoupper(trim((string)$estado));
    if ($e === 'FINALIZADO') {
        return 'ENTREGADO';
    }
    return $e;
}

function estadoPresentacion($estado) {
    return estadoNormalizado($estado);
}

function catalogoEstadosDefecto() {
    return array(
        array(
            'ID' => 0,
            'CODIGO' => 'EN_ALISTAMIENTO',
            'NOMBRE' => 'EN ALISTAMIENTO',
            'ACTIVO' => 'S',
            'ORDEN_VISUAL' => 10
        ),
        array(
            'ID' => 0,
            'CODIGO' => 'EN_RUTA',
            'NOMBRE' => 'EN RUTA',
            'ACTIVO' => 'S',
            'ORDEN_VISUAL' => 20
        ),
        array(
            'ID' => 0,
            'CODIGO' => 'ENTREGADO',
            'NOMBRE' => 'ENTREGADO',
            'ACTIVO' => 'S',
            'ORDEN_VISUAL' => 30
        )
    );
}

function existeTablaCatalogoEstados(PDO $pdo) {
    return tablaExiste($pdo, 'SN_GUIAS_ESTADOS_CFG')
        && columnaExiste($pdo, 'SN_GUIAS_ESTADOS_CFG', 'CODIGO')
        && columnaExiste($pdo, 'SN_GUIAS_ESTADOS_CFG', 'NOMBRE')
        && columnaExiste($pdo, 'SN_GUIAS_ESTADOS_CFG', 'ACTIVO');
}

function obtenerCatalogoEstados(PDO $pdo, $soloActivos = true) {
    if (!existeTablaCatalogoEstados($pdo)) {
        return catalogoEstadosDefecto();
    }

    $sql = "
        SELECT
            ID,
            UPPER(TRIM(CODIGO)) AS CODIGO,
            TRIM(NOMBRE) AS NOMBRE,
            COALESCE(ACTIVO, 'S') AS ACTIVO,
            COALESCE(ORDEN_VISUAL, 0) AS ORDEN_VISUAL
        FROM SN_GUIAS_ESTADOS_CFG
    ";

    if ($soloActivos) {
        $sql .= " WHERE COALESCE(ACTIVO, 'S') = 'S'";
    }

    $sql .= " ORDER BY COALESCE(ORDEN_VISUAL, 0), UPPER(TRIM(CODIGO))";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (!empty($rows)) {
        return $rows;
    }

    return catalogoEstadosDefecto();
}

function obtenerEstadoInicialGuia(PDO $pdo) {
    $catalogo = obtenerCatalogoEstados($pdo, true);
    foreach ($catalogo as $row) {
        $codigo = estadoNormalizado(isset($row['CODIGO']) ? $row['CODIGO'] : '');
        if ($codigo === 'EN_ALISTAMIENTO') {
            return $codigo;
        }
    }
    if (!empty($catalogo)) {
        return estadoNormalizado($catalogo[0]['CODIGO']);
    }
    return 'EN_ALISTAMIENTO';
}

function estadoEstaEnUso(PDO $pdo, $codigoEstado) {
    $codigo = estadoNormalizado($codigoEstado);
    if ($codigo === '') {
        return false;
    }

    $sql1 = "SELECT COUNT(*) FROM SN_GUIAS WHERE UPPER(TRIM(COALESCE(ESTADO_ACTUAL, ''))) = ?";
    $stmt1 = $pdo->prepare($sql1);
    $stmt1->execute(array($codigo));
    if ((int)$stmt1->fetchColumn() > 0) {
        return true;
    }

    $sql2 = "SELECT COUNT(*) FROM SN_GUIAS_ESTADOS WHERE UPPER(TRIM(COALESCE(ESTADO, ''))) = ?";
    $stmt2 = $pdo->prepare($sql2);
    $stmt2->execute(array($codigo));
    return ((int)$stmt2->fetchColumn()) > 0;
}

function siguienteId(PDO $pdo, $tabla) {
    $stmt = $pdo->query('SELECT COALESCE(MAX(ID), 0) + 1 AS NEXT_ID FROM ' . $tabla);
    return (int)$stmt->fetchColumn();
}

function siguienteConsecutivo(PDO $pdo, $prefijo) {
    $stmt = $pdo->prepare('SELECT COALESCE(MAX(CONSECUTIVO), 0) + 1 AS NEXT_CONS FROM SN_GUIAS WHERE PREFIJO = ?');
    $stmt->execute(array($prefijo));
    return (int)$stmt->fetchColumn();
}

function esErrorConcurrencia(PDOException $e) {
    $msg = strtolower($e->getMessage());
    if (strpos($msg, 'violation of primary or unique key') !== false) {
        return true;
    }
    if (strpos($msg, 'lock conflict on no wait transaction') !== false) {
        return true;
    }
    return false;
}

function esErrorCheckEstadoLegacy(PDOException $e) {
    $msg = strtoupper((string)$e->getMessage());
    if (strpos($msg, 'CK_SN_GUIAS_ESTADO') !== false) {
        return true;
    }
    if (strpos($msg, 'CK_SN_GUIAS_ESTADO_H') !== false) {
        return true;
    }
    if (strpos($msg, 'CHECK_297') !== false) {
        return true;
    }
    if (strpos($msg, 'VIOLATES CHECK CONSTRAINT') !== false && strpos($msg, 'SN_GUIAS') !== false) {
        return true;
    }
    return false;
}

function limpiarTransaccionActiva(PDO $pdo) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (PDOException $e) {
        $msg = strtolower($e->getMessage());
        if (strpos($msg, 'no transaction is active') === false && strpos($msg, 'no active transaction') === false) {
            throw $e;
        }
    }
}

function textoSeguro($valor) {
    return trim((string)$valor);
}

function construirRemision($prefijo, $numero) {
    return textoSeguro($prefijo) . '-' . textoSeguro($numero);
}

function construirFechaHoraTexto($fecha, $hora) {
    $f = textoSeguro($fecha);
    $h = textoSeguro($hora);
    if ($f === '' && $h === '') {
        return '';
    }
    if ($f === '') {
        return $h;
    }
    if ($h === '') {
        return $f;
    }
    return $f . ' ' . $h;
}

function tokenRemisionEntrega($kardexId) {
    return strtoupper(substr(sha1('D17_REMISION_' . (int)$kardexId . '_2026'), 0, 12));
}

function numeroDesdeFirebird($valor) {
    if ($valor === null) {
        return 0.0;
    }

    if (is_int($valor) || is_float($valor)) {
        return (float)$valor;
    }

    $txt = trim((string)$valor);
    if ($txt === '') {
        return 0.0;
    }

    $txt = str_replace(' ', '', $txt);

    if (strpos($txt, ',') !== false && strpos($txt, '.') !== false) {
        if (strrpos($txt, ',') > strrpos($txt, '.')) {
            // Formato 1.234,56
            $txt = str_replace('.', '', $txt);
            $txt = str_replace(',', '.', $txt);
        } else {
            // Formato 1,234.56
            $txt = str_replace(',', '', $txt);
        }
    } elseif (strpos($txt, ',') !== false) {
        $txt = str_replace(',', '.', $txt);
    }

    return is_numeric($txt) ? (float)$txt : 0.0;
}

function sqlPesoRemision($exprKardexId) {
    return "
        COALESCE(
            (
                SELECT SUM(COALESCE(dk.CANMAT, dk.CANLISTA, 0) * COALESCE(m.PESO, 0))
                FROM DEKARDEX dk
                LEFT JOIN MATERIAL m ON m.MATID = dk.MATID
                WHERE dk.KARDEXID = " . $exprKardexId . "
            ),
            0
        )
    ";
}

try {
    $pdo = obtenerPdoActual();
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

    if ($action === '') {
        responder(false, array('message' => 'Accion no informada.'));
    }

    $faltantes = validarTablasModulo($pdo);
    if (!empty($faltantes)) {
        responder(false, array(
            'message' => 'No existen las tablas del modulo en la BD actual: ' . implode(', ', $faltantes) . '. Ejecuta primero 00_create_sn_guias.sql y 01_alter_kardex.sql.'
        ));
    }

    if (!columnaExiste($pdo, 'KARDEX', 'SN_GUIA_ID')) {
        responder(false, array(
            'message' => 'No existe el campo KARDEX.SN_GUIA_ID. Ejecuta primero 01_alter_kardex.sql.'
        ));
    }

    $usaVehiculo = columnaExiste($pdo, 'SN_GUIAS', 'ID_VEHICULO') && tablaExiste($pdo, 'VEHICULO');
    $usaZonas = tablaExiste($pdo, 'ZONAS')
        && columnaExiste($pdo, 'ZONAS', 'ZONAID')
        && columnaExiste($pdo, 'ZONAS', 'NOMBRE')
        && columnaExiste($pdo, 'TERCEROS', 'ZONA1')
        && columnaExiste($pdo, 'TERCEROS', 'ZONA2');
    $usaZonaTextoTercero = columnaExiste($pdo, 'TERCEROS', 'ZONA');

    if ($action === 'listar_estados_catalogo') {
        $soloActivos = strtoupper(trim((string)(isset($_POST['solo_activos']) ? $_POST['solo_activos'] : 'S'))) !== 'N';
        $catalogo = obtenerCatalogoEstados($pdo, $soloActivos);
        $data = array();

        foreach ($catalogo as $row) {
            $codigo = estadoNormalizado(isset($row['CODIGO']) ? $row['CODIGO'] : '');
            $data[] = array(
                'id' => isset($row['ID']) ? (int)$row['ID'] : 0,
                'codigo' => $codigo,
                'nombre' => trim((string)(isset($row['NOMBRE']) ? $row['NOMBRE'] : $codigo)),
                'activo' => strtoupper(trim((string)(isset($row['ACTIVO']) ? $row['ACTIVO'] : 'S'))) === 'N' ? 'N' : 'S',
                'orden_visual' => isset($row['ORDEN_VISUAL']) ? (int)$row['ORDEN_VISUAL'] : 0,
                'en_uso' => estadoEstaEnUso($pdo, $codigo) ? 1 : 0
            );
        }

        responder(true, array(
            'usa_catalogo_db' => existeTablaCatalogoEstados($pdo) ? 1 : 0,
            'data' => $data
        ));
    }

    if ($action === 'agregar_estado_catalogo') {
        if (!existeTablaCatalogoEstados($pdo)) {
            responder(false, array('message' => 'No existe SN_GUIAS_ESTADOS_CFG. Ejecuta 04_create_estados_guias_y_vehiculo.sql.'));
        }

        $codigo = estadoNormalizado(isset($_POST['codigo']) ? $_POST['codigo'] : '');
        $nombre = strtoupper(trim((string)(isset($_POST['nombre']) ? $_POST['nombre'] : '')));
        $orden = isset($_POST['orden_visual']) ? (int)$_POST['orden_visual'] : 0;
        $usuario = isset($_SESSION['user']) ? trim((string)$_SESSION['user']) : 'sistema';

        if ($codigo === '' || strlen($codigo) > 30) {
            responder(false, array('message' => 'Codigo de estado invalido (maximo 30).'));
        }
        if ($nombre === '') {
            $nombre = str_replace('_', ' ', $codigo);
        }
        if (strlen($nombre) > 60) {
            $nombre = substr($nombre, 0, 60);
        }
        if (strlen($usuario) > 25) {
            $usuario = substr($usuario, 0, 25);
        }

        $stmtEx = $pdo->prepare("SELECT COUNT(*) FROM SN_GUIAS_ESTADOS_CFG WHERE UPPER(TRIM(CODIGO)) = ?");
        $stmtEx->execute(array($codigo));
        if ((int)$stmtEx->fetchColumn() > 0) {
            responder(false, array('message' => 'El codigo ya existe en el catalogo.'));
        }

        $id = siguienteId($pdo, 'SN_GUIAS_ESTADOS_CFG');
        $sql = "
            INSERT INTO SN_GUIAS_ESTADOS_CFG (
                ID, CODIGO, NOMBRE, ACTIVO, ORDEN_VISUAL, FECHA_CREACION, FECHA_EDICION, USUARIO_EDITA
            ) VALUES (?, ?, ?, 'S', ?, CURRENT_TIMESTAMP, NULL, ?)
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($id, $codigo, $nombre, $orden, $usuario));
        responder(true, array('message' => 'Estado agregado.'));
    }

    if ($action === 'editar_estado_catalogo') {
        if (!existeTablaCatalogoEstados($pdo)) {
            responder(false, array('message' => 'No existe SN_GUIAS_ESTADOS_CFG. Ejecuta 04_create_estados_guias_y_vehiculo.sql.'));
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $codigo = estadoNormalizado(isset($_POST['codigo']) ? $_POST['codigo'] : '');
        $nombre = strtoupper(trim((string)(isset($_POST['nombre']) ? $_POST['nombre'] : '')));
        $activo = strtoupper(trim((string)(isset($_POST['activo']) ? $_POST['activo'] : 'S'))) === 'N' ? 'N' : 'S';
        $orden = isset($_POST['orden_visual']) ? (int)$_POST['orden_visual'] : 0;
        $usuario = isset($_SESSION['user']) ? trim((string)$_SESSION['user']) : 'sistema';

        if ($id <= 0) {
            responder(false, array('message' => 'ID de estado invalido.'));
        }
        if ($codigo === '' || strlen($codigo) > 30) {
            responder(false, array('message' => 'Codigo de estado invalido (maximo 30).'));
        }
        if ($nombre === '') {
            $nombre = str_replace('_', ' ', $codigo);
        }
        if (strlen($nombre) > 60) {
            $nombre = substr($nombre, 0, 60);
        }
        if (strlen($usuario) > 25) {
            $usuario = substr($usuario, 0, 25);
        }

        $stmtAct = $pdo->prepare("SELECT CODIGO FROM SN_GUIAS_ESTADOS_CFG WHERE ID = ?");
        $stmtAct->execute(array($id));
        $codigoActual = $stmtAct->fetchColumn();
        if ($codigoActual === false) {
            responder(false, array('message' => 'El estado no existe.'));
        }

        $codigoActual = estadoNormalizado($codigoActual);
        if (estadoEstaEnUso($pdo, $codigoActual)) {
            responder(false, array('message' => 'No se puede editar un estado que ya esta en uso.'));
        }

        $stmtEx = $pdo->prepare("SELECT COUNT(*) FROM SN_GUIAS_ESTADOS_CFG WHERE UPPER(TRIM(CODIGO)) = ? AND ID <> ?");
        $stmtEx->execute(array($codigo, $id));
        if ((int)$stmtEx->fetchColumn() > 0) {
            responder(false, array('message' => 'Ya existe otro estado con ese codigo.'));
        }

        $sql = "
            UPDATE SN_GUIAS_ESTADOS_CFG
            SET CODIGO = ?, NOMBRE = ?, ACTIVO = ?, ORDEN_VISUAL = ?, FECHA_EDICION = CURRENT_TIMESTAMP, USUARIO_EDITA = ?
            WHERE ID = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($codigo, $nombre, $activo, $orden, $usuario, $id));
        responder(true, array('message' => 'Estado actualizado.'));
    }

    if ($action === 'eliminar_estado_catalogo') {
        if (!existeTablaCatalogoEstados($pdo)) {
            responder(false, array('message' => 'No existe SN_GUIAS_ESTADOS_CFG. Ejecuta 04_create_estados_guias_y_vehiculo.sql.'));
        }

        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            responder(false, array('message' => 'ID de estado invalido.'));
        }

        $stmtAct = $pdo->prepare("SELECT CODIGO FROM SN_GUIAS_ESTADOS_CFG WHERE ID = ?");
        $stmtAct->execute(array($id));
        $codigoActual = $stmtAct->fetchColumn();
        if ($codigoActual === false) {
            responder(false, array('message' => 'El estado no existe.'));
        }

        $codigoActual = estadoNormalizado($codigoActual);
        if (estadoEstaEnUso($pdo, $codigoActual)) {
            responder(false, array('message' => 'No se puede eliminar un estado que ya esta en uso.'));
        }

        $stmt = $pdo->prepare("DELETE FROM SN_GUIAS_ESTADOS_CFG WHERE ID = ?");
        $stmt->execute(array($id));
        responder(true, array('message' => 'Estado eliminado.'));
    }

    if ($action === 'listar') {
        $fechaDesde = normalizarFechaInput(isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '', false);
        $fechaHasta = normalizarFechaInput(isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '', true);
        $estado = estadoNormalizado(isset($_POST['estado']) ? $_POST['estado'] : '');
        $busqueda = trim((string)(isset($_POST['busqueda']) ? $_POST['busqueda'] : ''));

        $where = array();
        $params = array();

        if ($fechaDesde !== null) {
            $where[] = 'g.FECHA_GUIA >= ?';
            $params[] = $fechaDesde;
        }
        if ($fechaHasta !== null) {
            $where[] = 'g.FECHA_GUIA <= ?';
            $params[] = $fechaHasta;
        }
        if ($estado !== '') {
            if ($estado === 'ENTREGADO') {
                $where[] = "UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL, ''))) IN ('ENTREGADO', 'FINALIZADO')";
            } else {
                $where[] = "UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL, ''))) = ?";
                $params[] = $estado;
            }
        }
        if ($busqueda !== '') {
            $where[] = "(
                CAST(COALESCE(g.PREFIJO, '') AS VARCHAR(10)) CONTAINING ?
                OR CAST(g.CONSECUTIVO AS VARCHAR(20)) CONTAINING ?
                OR CAST(CAST(COALESCE(g.PREFIJO, '') AS VARCHAR(10)) || '-' || CAST(g.CONSECUTIVO AS VARCHAR(20)) AS VARCHAR(40)) CONTAINING ?
                OR CAST(COALESCE(tc.NOMBRE, '') AS VARCHAR(120)) CONTAINING ?
            )";
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        $campoVehiculo = $usaVehiculo ? "COALESCE(v.PLACA, '') AS PLACA_VEHICULO," : "'' AS PLACA_VEHICULO,";
        $joinVehiculo = $usaVehiculo ? "LEFT JOIN VEHICULO v ON v.VEHICULOID = g.ID_VEHICULO" : '';

        $sql = "
            SELECT
                g.ID,
                g.PREFIJO,
                g.CONSECUTIVO,
                g.FECHA_GUIA,
                g.ESTADO_ACTUAL,
                g.USUARIO_CREA,
                $campoVehiculo
                CAST(COALESCE(tc.NOMBRE, '') AS VARCHAR(120)) AS CONDUCTOR,
                (SELECT COUNT(*) FROM SN_GUIAS_DETALLE d WHERE d.ID_GUIA = g.ID) AS TOTAL_REMISIONES,
                CAST((
                    SELECT COALESCE(SUM(COALESCE(dk.CANMAT, dk.CANLISTA, 0) * COALESCE(m.PESO, 0)), 0)
                    FROM SN_GUIAS_DETALLE d
                    LEFT JOIN DEKARDEX dk ON dk.KARDEXID = d.KARDEX_ID
                    LEFT JOIN MATERIAL m ON m.MATID = dk.MATID
                    WHERE d.ID_GUIA = g.ID
                ) AS CHAR(30)) AS TOTAL_PESO_TXT,
                CAST((SELECT COALESCE(SUM(d.VALOR_BASE), 0) FROM SN_GUIAS_DETALLE d WHERE d.ID_GUIA = g.ID) AS CHAR(30)) AS TOTAL_VALOR_BASE_TXT
            FROM SN_GUIAS g
            LEFT JOIN TERCEROS tc ON tc.TERID = g.ID_CONDUCTOR
            $joinVehiculo
        ";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY g.FECHA_GUIA DESC, g.ID DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'id' => (int)$row['ID'],
                'prefijo' => trim((string)$row['PREFIJO']),
                'consecutivo' => (int)$row['CONSECUTIVO'],
                'fecha_guia' => $row['FECHA_GUIA'],
                'estado_actual' => estadoPresentacion($row['ESTADO_ACTUAL']),
                'usuario_crea' => trim((string)$row['USUARIO_CREA']),
                'conductor' => trim((string)$row['CONDUCTOR']),
                'placa_vehiculo' => trim((string)$row['PLACA_VEHICULO']),
                'total_remisiones' => (int)$row['TOTAL_REMISIONES'],
                'total_peso' => numeroDesdeFirebird($row['TOTAL_PESO_TXT']),
                'total_valor_base' => numeroDesdeFirebird($row['TOTAL_VALOR_BASE_TXT'])
            );
        }

        responder(true, array('data' => $data));
    }

    if ($action === 'crear_guia') {
        $prefijo = strtoupper(substr(trim((string)(isset($_POST['prefijo']) ? $_POST['prefijo'] : '')), 0, 2));
        $idConductor = trim((string)(isset($_POST['id_conductor']) ? $_POST['id_conductor'] : ''));
        $idVehiculo = trim((string)(isset($_POST['id_vehiculo']) ? $_POST['id_vehiculo'] : ''));
        $observacion = trim((string)(isset($_POST['observacion']) ? $_POST['observacion'] : ''));
        $usuario = isset($_SESSION['user']) ? trim((string)$_SESSION['user']) : 'sistema';
        $estadoInicial = obtenerEstadoInicialGuia($pdo);

        if ($prefijo === '') {
            responder(false, array('message' => 'El prefijo es obligatorio.'));
        }

        $idConductorFinal = null;
        if ($idConductor !== '' && ctype_digit($idConductor)) {
            $idConductorFinal = (int)$idConductor;
        }

        $idVehiculoFinal = null;
        if ($idVehiculo !== '' && ctype_digit($idVehiculo)) {
            $idVehiculoFinal = (int)$idVehiculo;
        }

        if ($usaVehiculo && $idVehiculoFinal !== null) {
            $stmtVeh = $pdo->prepare('SELECT COUNT(*) FROM VEHICULO WHERE VEHICULOID = ?');
            $stmtVeh->execute(array($idVehiculoFinal));
            if ((int)$stmtVeh->fetchColumn() === 0) {
                responder(false, array('message' => 'El vehiculo seleccionado no existe.'));
            }
        }

        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }
        if (strlen($observacion) > 200) {
            $observacion = substr($observacion, 0, 200);
        }

        $maxIntentos = 3;
        $ultimoError = null;

        for ($intento = 1; $intento <= $maxIntentos; $intento++) {
            try {
                limpiarTransaccionActiva($pdo);
                $pdo->beginTransaction();

                $idGuia = siguienteId($pdo, 'SN_GUIAS');
                $consecutivo = siguienteConsecutivo($pdo, $prefijo);

                $sqlInsGuia = "
                    INSERT INTO SN_GUIAS (
                        ID, FECHA_CREACION, FECHA_EDICION, USUARIO_CREA, USUARIO_EDITA,
                        PREFIJO, CONSECUTIVO, FECHA_GUIA, ID_CONDUCTOR, ESTADO_ACTUAL" . ($usaVehiculo ? ", ID_VEHICULO" : "") . "
                    ) VALUES (?, CURRENT_TIMESTAMP, NULL, ?, NULL, ?, ?, CURRENT_TIMESTAMP, ?, ?" . ($usaVehiculo ? ", ?" : "") . ")
                ";
                $stmtInsGuia = $pdo->prepare($sqlInsGuia);
                $paramsInsGuia = array(
                    $idGuia,
                    $usuario,
                    $prefijo,
                    $consecutivo,
                    $idConductorFinal,
                    $estadoInicial
                );
                if ($usaVehiculo) {
                    $paramsInsGuia[] = $idVehiculoFinal;
                }
                $stmtInsGuia->execute($paramsInsGuia);

                $idEstado = siguienteId($pdo, 'SN_GUIAS_ESTADOS');
                $sqlInsEstado = "
                    INSERT INTO SN_GUIAS_ESTADOS (
                        ID, ID_GUIA, ESTADO, FECHA_HORA_ESTADO, USUARIO, OBSERVACION
                    ) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?)
                ";
                $stmtInsEstado = $pdo->prepare($sqlInsEstado);
                $stmtInsEstado->execute(array(
                    $idEstado,
                    $idGuia,
                    $estadoInicial,
                    $usuario,
                    ($observacion !== '' ? $observacion : null)
                ));

                $pdo->commit();

                responder(true, array(
                    'id_guia' => $idGuia,
                    'prefijo' => $prefijo,
                    'consecutivo' => $consecutivo,
                    'num_guia' => $prefijo . '-' . $consecutivo
                ));
            } catch (PDOException $e) {
                $ultimoError = $e;
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                if (esErrorConcurrencia($e) && $intento < $maxIntentos) {
                    continue;
                }

                throw $e;
            }
        }

        if ($ultimoError instanceof PDOException) {
            throw $ultimoError;
        }

        responder(false, array('message' => 'No fue posible crear la guia.'));
    }

    if ($action === 'obtener_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }

        $campoVehiculo = $usaVehiculo ? "g.ID_VEHICULO, COALESCE(v.PLACA, '') AS PLACA_VEHICULO," : "NULL AS ID_VEHICULO, '' AS PLACA_VEHICULO,";
        $joinVehiculo = $usaVehiculo ? "LEFT JOIN VEHICULO v ON v.VEHICULOID = g.ID_VEHICULO" : '';

        $sql = "
            SELECT
                g.ID,
                g.PREFIJO,
                g.CONSECUTIVO,
                g.FECHA_GUIA,
                g.ID_CONDUCTOR,
                $campoVehiculo
                g.ESTADO_ACTUAL
            FROM SN_GUIAS g
            $joinVehiculo
            WHERE g.ID = ?
        ";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($idGuia));
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            responder(false, array('message' => 'La guia no existe.'));
        }

        responder(true, array(
            'data' => array(
                'id' => (int)$row['ID'],
                'prefijo' => trim((string)$row['PREFIJO']),
                'consecutivo' => (int)$row['CONSECUTIVO'],
                'fecha_guia' => $row['FECHA_GUIA'],
                'id_conductor' => ($row['ID_CONDUCTOR'] !== null ? (int)$row['ID_CONDUCTOR'] : null),
                'id_vehiculo' => ($row['ID_VEHICULO'] !== null ? (int)$row['ID_VEHICULO'] : null),
                'placa_vehiculo' => trim((string)$row['PLACA_VEHICULO']),
                'estado_actual' => estadoPresentacion($row['ESTADO_ACTUAL'])
            )
        ));
    }

    if ($action === 'actualizar_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $idConductor = trim((string)(isset($_POST['id_conductor']) ? $_POST['id_conductor'] : ''));
        $idVehiculo = trim((string)(isset($_POST['id_vehiculo']) ? $_POST['id_vehiculo'] : ''));
        $usuario = isset($_SESSION['user']) ? trim((string)$_SESSION['user']) : 'sistema';

        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }
        $idConductorFinal = null;
        if ($idConductor !== '' && ctype_digit($idConductor)) {
            $idConductorFinal = (int)$idConductor;
        }

        $idVehiculoFinal = null;
        if ($idVehiculo !== '' && ctype_digit($idVehiculo)) {
            $idVehiculoFinal = (int)$idVehiculo;
        }

        if ($usaVehiculo && $idVehiculoFinal !== null) {
            $stmtVeh = $pdo->prepare('SELECT COUNT(*) FROM VEHICULO WHERE VEHICULOID = ?');
            $stmtVeh->execute(array($idVehiculoFinal));
            if ((int)$stmtVeh->fetchColumn() === 0) {
                responder(false, array('message' => 'El vehiculo seleccionado no existe.'));
            }
        }

        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }

        try {
            limpiarTransaccionActiva($pdo);
            $pdo->beginTransaction();

            $stmtExiste = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS WHERE ID = ?');
            $stmtExiste->execute(array($idGuia));
            if ((int)$stmtExiste->fetchColumn() === 0) {
                $pdo->rollBack();
                responder(false, array('message' => 'La guia no existe.'));
            }

            $sqlUpd = "UPDATE SN_GUIAS SET ID_CONDUCTOR = ?, FECHA_EDICION = CURRENT_TIMESTAMP, USUARIO_EDITA = ?" . ($usaVehiculo ? ", ID_VEHICULO = ?" : "") . " WHERE ID = ?";
            $stmtUpd = $pdo->prepare($sqlUpd);
            $paramsUpd = array($idConductorFinal, $usuario);
            if ($usaVehiculo) {
                $paramsUpd[] = $idVehiculoFinal;
            }
            $paramsUpd[] = $idGuia;
            $stmtUpd->execute($paramsUpd);

            $pdo->commit();
            responder(true, array('message' => 'Guia actualizada.'));
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    if ($action === 'historial_estados') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }

        $sql = "
            SELECT
                ID,
                ID_GUIA,
                ESTADO,
                FECHA_HORA_ESTADO,
                USUARIO,
                OBSERVACION
            FROM SN_GUIAS_ESTADOS
            WHERE ID_GUIA = ?
            ORDER BY FECHA_HORA_ESTADO DESC, ID DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($idGuia));

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'id' => (int)$row['ID'],
                'id_guia' => (int)$row['ID_GUIA'],
                'estado' => estadoPresentacion($row['ESTADO']),
                'fecha_hora_estado' => $row['FECHA_HORA_ESTADO'],
                'usuario' => trim((string)$row['USUARIO']),
                'observacion' => trim((string)$row['OBSERVACION'])
            );
        }

        responder(true, array('data' => $data));
    }

    if ($action === 'cambiar_estado') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $estado = estadoNormalizado(isset($_POST['estado']) ? $_POST['estado'] : '');
        $observacion = trim((string)(isset($_POST['observacion']) ? $_POST['observacion'] : ''));
        $usuario = isset($_SESSION['user']) ? trim((string)$_SESSION['user']) : 'sistema';

        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }
        $catalogoEstados = obtenerCatalogoEstados($pdo, true);
        $codigosValidos = array();
        foreach ($catalogoEstados as $estadoCfg) {
            $codigosValidos[] = estadoNormalizado(isset($estadoCfg['CODIGO']) ? $estadoCfg['CODIGO'] : '');
        }
        if (!in_array($estado, $codigosValidos, true)) {
            responder(false, array('message' => 'Estado no valido.'));
        }

        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }
        if (strlen($observacion) > 200) {
            $observacion = substr($observacion, 0, 200);
        }

        try {
            limpiarTransaccionActiva($pdo);
            $pdo->beginTransaction();

            $stmtExiste = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS WHERE ID = ?');
            $stmtExiste->execute(array($idGuia));
            if ((int)$stmtExiste->fetchColumn() === 0) {
                $pdo->rollBack();
                responder(false, array('message' => 'La guia no existe.'));
            }

            $estadoPersistir = $estado;
            $sqlUpd = 'UPDATE SN_GUIAS SET ESTADO_ACTUAL = ?, FECHA_EDICION = CURRENT_TIMESTAMP, USUARIO_EDITA = ? WHERE ID = ?';
            $sqlIns = 'INSERT INTO SN_GUIAS_ESTADOS (ID, ID_GUIA, ESTADO, FECHA_HORA_ESTADO, USUARIO, OBSERVACION) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?)';

            $intentosEstado = array($estadoPersistir);
            if ($estadoPersistir === 'ENTREGADO') {
                $intentosEstado[] = 'FINALIZADO';
            }

            $actualizado = false;
            foreach ($intentosEstado as $idxIntento => $estadoTry) {
                try {
                    $stmtUpd = $pdo->prepare($sqlUpd);
                    $stmtUpd->execute(array($estadoTry, $usuario, $idGuia));

                    $idEstado = siguienteId($pdo, 'SN_GUIAS_ESTADOS');
                    $stmtIns = $pdo->prepare($sqlIns);
                    $stmtIns->execute(array(
                        $idEstado,
                        $idGuia,
                        $estadoTry,
                        $usuario,
                        ($observacion !== '' ? $observacion : null)
                    ));

                    $estadoPersistir = $estadoTry;
                    $actualizado = true;
                    break;
                } catch (PDOException $eEstado) {
                    $esUltimo = ($idxIntento === count($intentosEstado) - 1);
                    if ($esUltimo || !esErrorCheckEstadoLegacy($eEstado)) {
                        throw $eEstado;
                    }
                }
            }

            if (!$actualizado) {
                throw new PDOException('No fue posible actualizar estado en SN_GUIAS.');
            }

            $pdo->commit();

            responder(true, array('message' => 'Estado actualizado correctamente.'));
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    if ($action === 'listar_detalle_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }

        $sql = "
            SELECT
                d.ID,
                d.KARDEX_ID,
                k.CODPREFIJO,
                k.NUMERO,
                k.FECHA,
                k.HORA,
                COALESCE(tc.NOMBRE, '') AS CLIENTE,
                COALESCE(ks.TELEF1, tc.TELEF1, tc.TELEF2, '') AS TELEFONO,
                CAST(COALESCE((" . sqlPesoRemision('d.KARDEX_ID') . "), d.PESO, 0) AS CHAR(30)) AS PESO_TXT,
                CAST(COALESCE(d.VALOR_BASE, 0) AS CHAR(30)) AS VALOR_BASE_TXT
            FROM SN_GUIAS_DETALLE d
            LEFT JOIN KARDEX k ON k.KARDEXID = d.KARDEX_ID
            LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
            LEFT JOIN KARDEXSELF ks ON ks.KARDEXID = d.KARDEX_ID
            WHERE d.ID_GUIA = ?
            ORDER BY d.ID DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($idGuia));

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'id' => (int)$row['ID'],
                'kardex_id' => (int)$row['KARDEX_ID'],
                'remision' => construirRemision($row['CODPREFIJO'], $row['NUMERO']),
                'fecha_hora' => construirFechaHoraTexto($row['FECHA'], $row['HORA']),
                'cliente' => textoSeguro($row['CLIENTE']),
                'telefono' => textoSeguro($row['TELEFONO']),
                'peso' => numeroDesdeFirebird($row['PESO_TXT']),
                'valor_base' => numeroDesdeFirebird($row['VALOR_BASE_TXT']),
                'token_pdf' => tokenRemisionEntrega((int)$row['KARDEX_ID'])
            );
        }

        responder(true, array('data' => $data));
    }

    if ($action === 'listar_candidatas_remision') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $busqueda = trim((string)(isset($_POST['busqueda']) ? $_POST['busqueda'] : ''));
        $fechaDesde = trim((string)(isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : ''));
        $fechaHasta = trim((string)(isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : ''));
        $prefijoFiltro = strtoupper(trim((string)(isset($_POST['prefijo']) ? $_POST['prefijo'] : '')));
        $zonasJson = isset($_POST['zonas_json']) ? trim((string)$_POST['zonas_json']) : '[]';

        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }

        $where = array();
        $params = array();
        $zonasSel = json_decode($zonasJson, true);
        if (!is_array($zonasSel)) {
            $zonasSel = array();
        }
        $zonasSelLimpio = array();
        foreach ($zonasSel as $z) {
            $zt = trim((string)$z);
            if ($zt !== '') {
                $zonasSelLimpio[$zt] = $zt;
            }
        }
        $zonasSelLimpio = array_values($zonasSelLimpio);

        $where[] = "k.CODCOMP = 'RS'";
        $where[] = "k.FECASENTAD IS NOT NULL";
        $where[] = "k.FECANULADO IS NULL";

        if ($fechaDesde !== '') {
            $where[] = 'k.FECHA >= ?';
            $params[] = $fechaDesde;
        }
        if ($fechaHasta !== '') {
            $where[] = 'k.FECHA <= ?';
            $params[] = $fechaHasta;
        }
        if (in_array($prefijoFiltro, array('00', '01', '50'), true)) {
            $where[] = 'k.CODPREFIJO = ?';
            $params[] = $prefijoFiltro;
        }
        if ($busqueda !== '') {
            $where[] = "(
                CAST(COALESCE(k.CODPREFIJO, '') AS VARCHAR(10)) CONTAINING ?
                OR CAST(k.NUMERO AS VARCHAR(30)) CONTAINING ?
                OR CAST(COALESCE(tc.NOMBRE, '') AS VARCHAR(120)) CONTAINING ?
                OR CAST(COALESCE(tv.NOMBRE, '') AS VARCHAR(120)) CONTAINING ?
            )";
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        $exprZona = "''";
        if ($usaZonas) {
            $exprZona = "COALESCE(
                NULLIF(CASE WHEN z1.ZONAID IS NOT NULL THEN TRIM(CAST(z1.ZONAID AS VARCHAR(10))) || ' - ' || TRIM(COALESCE(z1.NOMBRE, '')) ELSE '' END, ''),
                NULLIF(CASE WHEN z2.ZONAID IS NOT NULL THEN TRIM(CAST(z2.ZONAID AS VARCHAR(10))) || ' - ' || TRIM(COALESCE(z2.NOMBRE, '')) ELSE '' END, '')
                " . ($usaZonaTextoTercero ? ", NULLIF(TRIM(tc.ZONA), '')" : "") . "
            )";
        } elseif ($usaZonaTextoTercero) {
            $exprZona = "COALESCE(NULLIF(TRIM(tc.ZONA), ''), '')";
        }

        if (!empty($zonasSelLimpio)) {
            $in = array();
            foreach ($zonasSelLimpio as $zonaSel) {
                $in[] = '?';
                $params[] = $zonaSel;
            }
            $where[] = "TRIM(COALESCE(" . $exprZona . ", '')) IN (" . implode(',', $in) . ")";
        }

        $sql = "
            SELECT FIRST 250
                k.KARDEXID,
                k.CODPREFIJO,
                k.NUMERO,
                k.FECHA,
                k.HORA,
                CAST(COALESCE(tc.NOMBRE, '') AS VARCHAR(120)) AS CLIENTE,
                CAST(" . $exprZona . " AS VARCHAR(120)) AS ZONA_TXT,
                CAST(COALESCE(tv.NOMBRE, '') AS VARCHAR(120)) AS VENDEDOR,
                CAST((" . sqlPesoRemision('k.KARDEXID') . ") AS CHAR(30)) AS PESO_TXT,
                CAST(COALESCE(k.VRBASE, 0) AS CHAR(30)) AS VALOR_BASE_TXT,
                k.SN_GUIA_ID
            FROM KARDEX k
            LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
            LEFT JOIN TERCEROS tv ON tv.TERID = k.VENDEDOR
            " . ($usaZonas ? "LEFT JOIN ZONAS z1 ON z1.ZONAID = tc.ZONA1 LEFT JOIN ZONAS z2 ON z2.ZONAID = tc.ZONA2" : "") . "
        ";

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY k.FECHA DESC, k.HORA DESC, k.KARDEXID DESC';

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'kardex_id' => (int)$row['KARDEXID'],
                'remision' => construirRemision($row['CODPREFIJO'], $row['NUMERO']),
                'fecha_hora' => construirFechaHoraTexto($row['FECHA'], $row['HORA']),
                'cliente' => textoSeguro($row['CLIENTE']),
                'zona' => textoSeguro($row['ZONA_TXT']),
                'vendedor' => textoSeguro($row['VENDEDOR']),
                'peso' => numeroDesdeFirebird($row['PESO_TXT']),
                'valor_base' => numeroDesdeFirebird($row['VALOR_BASE_TXT']),
                'sn_guia_id' => ($row['SN_GUIA_ID'] !== null ? (int)$row['SN_GUIA_ID'] : null)
            );
        }

        responder(true, array('data' => $data));
    }

    if ($action === 'listar_zonas_filtro_remision') {
        $prefijoFiltro = strtoupper(trim((string)(isset($_POST['prefijo']) ? $_POST['prefijo'] : '')));

        $where = array();
        $params = array();
        $where[] = "k.CODCOMP = 'RS'";
        $where[] = "k.FECASENTAD IS NOT NULL";
        $where[] = "k.FECANULADO IS NULL";

        if (in_array($prefijoFiltro, array('00', '01', '50'), true)) {
            $where[] = 'k.CODPREFIJO = ?';
            $params[] = $prefijoFiltro;
        }

        $exprZona = "''";
        if ($usaZonas) {
            $exprZona = "COALESCE(
                NULLIF(CASE WHEN z1.ZONAID IS NOT NULL THEN TRIM(CAST(z1.ZONAID AS VARCHAR(10))) || ' - ' || TRIM(COALESCE(z1.NOMBRE, '')) ELSE '' END, ''),
                NULLIF(CASE WHEN z2.ZONAID IS NOT NULL THEN TRIM(CAST(z2.ZONAID AS VARCHAR(10))) || ' - ' || TRIM(COALESCE(z2.NOMBRE, '')) ELSE '' END, '')
                " . ($usaZonaTextoTercero ? ", NULLIF(TRIM(tc.ZONA), '')" : "") . "
            )";
        } elseif ($usaZonaTextoTercero) {
            $exprZona = "COALESCE(NULLIF(TRIM(tc.ZONA), ''), '')";
        }

        if ($exprZona === "''") {
            responder(true, array('data' => array()));
        }

        $sql = "
            SELECT DISTINCT " . $exprZona . " AS ZONA_TXT
            FROM KARDEX k
            LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
            " . ($usaZonas ? "LEFT JOIN ZONAS z1 ON z1.ZONAID = tc.ZONA1 LEFT JOIN ZONAS z2 ON z2.ZONAID = tc.ZONA2" : "") . "
            WHERE " . implode(' AND ', $where) . "
              AND COALESCE(" . $exprZona . ", '') <> ''
            ORDER BY 1
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $zonas = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $z = textoSeguro($row['ZONA_TXT']);
            if ($z !== '') {
                $zonas[] = $z;
            }
        }
        responder(true, array('data' => $zonas));
    }

    if ($action === 'agregar_remision_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $kardexId = isset($_POST['kardex_id']) ? (int)$_POST['kardex_id'] : 0;
        $usuario = isset($_SESSION['user']) ? trim((string)$_SESSION['user']) : 'sistema';

        if ($idGuia <= 0 || $kardexId <= 0) {
            responder(false, array('message' => 'Parametros invalidos para agregar remision.'));
        }
        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }

        try {
            limpiarTransaccionActiva($pdo);
            $pdo->beginTransaction();

            $stmtGuia = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS WHERE ID = ?');
            $stmtGuia->execute(array($idGuia));
            if ((int)$stmtGuia->fetchColumn() === 0) {
                $pdo->rollBack();
                responder(false, array('message' => 'La guia no existe.'));
            }

            $sqlK = "
                SELECT
                    k.KARDEXID,
                    k.SN_GUIA_ID,
                    CAST((" . sqlPesoRemision('k.KARDEXID') . ") AS CHAR(30)) AS PESO_TXT,
                    CAST(COALESCE(k.VRBASE, 0) AS CHAR(30)) AS VALOR_BASE_TXT
                FROM KARDEX k
                WHERE k.KARDEXID = ?
                  AND k.CODCOMP = 'RS'
                  AND k.FECASENTAD IS NOT NULL
                  AND k.FECANULADO IS NULL
            ";
            $stmtK = $pdo->prepare($sqlK);
            $stmtK->execute(array($kardexId));
            $kRow = $stmtK->fetch(PDO::FETCH_ASSOC);

            if (!$kRow) {
                $pdo->rollBack();
                responder(false, array('message' => 'La remision no es valida para asignar (RS, sentada y no anulada).'));
            }

            if ($kRow['SN_GUIA_ID'] !== null && (int)$kRow['SN_GUIA_ID'] !== $idGuia) {
                $pdo->rollBack();
                responder(false, array('message' => 'La remision ya esta asignada a otra guia.'));
            }

            $stmtEx = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS_DETALLE WHERE ID_GUIA = ? AND KARDEX_ID = ?');
            $stmtEx->execute(array($idGuia, $kardexId));
            if ((int)$stmtEx->fetchColumn() === 0) {
                $idDet = siguienteId($pdo, 'SN_GUIAS_DETALLE');
                $sqlInsDet = "
                    INSERT INTO SN_GUIAS_DETALLE (
                        ID, ID_GUIA, KARDEX_ID, FECHA_AGREGADO, USUARIO_AGREGA, PESO, VALOR_BASE
                    ) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?, ?)
                ";
                $stmtInsDet = $pdo->prepare($sqlInsDet);
                $stmtInsDet->execute(array(
                    $idDet,
                    $idGuia,
                    $kardexId,
                    $usuario,
                    numeroDesdeFirebird($kRow['PESO_TXT']),
                    numeroDesdeFirebird($kRow['VALOR_BASE_TXT'])
                ));
            }

            $stmtUpdK = $pdo->prepare('UPDATE KARDEX SET SN_GUIA_ID = ? WHERE KARDEXID = ? AND (SN_GUIA_ID IS NULL OR SN_GUIA_ID = ?)');
            $stmtUpdK->execute(array($idGuia, $kardexId, $idGuia));

            $pdo->commit();
            responder(true, array('message' => 'Remision agregada correctamente.'));
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    if ($action === 'quitar_remision_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $kardexId = isset($_POST['kardex_id']) ? (int)$_POST['kardex_id'] : 0;

        if ($idGuia <= 0 || $kardexId <= 0) {
            responder(false, array('message' => 'Parametros invalidos para quitar remision.'));
        }

        try {
            limpiarTransaccionActiva($pdo);
            $pdo->beginTransaction();

            $stmtDel = $pdo->prepare('DELETE FROM SN_GUIAS_DETALLE WHERE ID_GUIA = ? AND KARDEX_ID = ?');
            $stmtDel->execute(array($idGuia, $kardexId));

            $stmtUpd = $pdo->prepare('UPDATE KARDEX SET SN_GUIA_ID = NULL WHERE KARDEXID = ? AND SN_GUIA_ID = ?');
            $stmtUpd->execute(array($kardexId, $idGuia));

            $pdo->commit();
            responder(true, array('message' => 'Remision retirada de la guia.'));
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    if ($action === 'eliminar_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        if ($idGuia <= 0) {
            responder(false, array('message' => 'ID de guia invalido.'));
        }

        try {
            limpiarTransaccionActiva($pdo);
            $pdo->beginTransaction();

            $stmtExiste = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS WHERE ID = ?');
            $stmtExiste->execute(array($idGuia));
            if ((int)$stmtExiste->fetchColumn() === 0) {
                $pdo->rollBack();
                responder(false, array('message' => 'La guia no existe.'));
            }

            $stmtDetalle = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS_DETALLE WHERE ID_GUIA = ?');
            $stmtDetalle->execute(array($idGuia));
            $totalDetalle = (int)$stmtDetalle->fetchColumn();
            if ($totalDetalle > 0) {
                $pdo->rollBack();
                responder(false, array('message' => 'No se puede eliminar la guia porque tiene remisiones relacionadas.'));
            }

            // Limpieza defensiva en caso de marcaciones residuales.
            $stmtLimpiaKardex = $pdo->prepare('UPDATE KARDEX SET SN_GUIA_ID = NULL WHERE SN_GUIA_ID = ?');
            $stmtLimpiaKardex->execute(array($idGuia));

            $stmtDel = $pdo->prepare('DELETE FROM SN_GUIAS WHERE ID = ?');
            $stmtDel->execute(array($idGuia));

            $pdo->commit();
            responder(true, array('message' => 'Guia eliminada.'));
        } catch (PDOException $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    responder(false, array('message' => 'Accion no soportada.'));
} catch (PDOException $e) {
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $rollbackException) {
        }
    }
    $accionError = isset($action) ? $action : 'desconocida';
    responder(false, array('message' => 'Error SQL (' . $accionError . '): ' . $e->getMessage()));
} catch (Exception $e) {
    responder(false, array('message' => 'Error: ' . $e->getMessage()));
}
