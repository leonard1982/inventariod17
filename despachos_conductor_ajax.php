<?php
ob_start();
require('conecta.php');

header('Content-Type: application/json; charset=UTF-8');

function toUtf8SeguroConductor($valor) {
    if (!is_string($valor)) {
        return $valor;
    }
    if (preg_match('//u', $valor)) {
        return $valor;
    }
    return utf8_encode($valor);
}

function normalizarUtf8RecursivoConductor($dato) {
    if (is_array($dato)) {
        $out = array();
        foreach ($dato as $k => $v) {
            $out[toUtf8SeguroConductor((string)$k)] = normalizarUtf8RecursivoConductor($v);
        }
        return $out;
    }
    return toUtf8SeguroConductor($dato);
}

function respConductor($ok, $payload = array()) {
    $salidaPrevia = '';
    if (ob_get_level() > 0) {
        $salidaPrevia = trim(ob_get_contents());
        ob_clean();
    }

    if ($salidaPrevia !== '') {
        $payload['debug_output'] = $salidaPrevia;
    }

    $base = array('ok' => $ok ? true : false);
    $respuesta = normalizarUtf8RecursivoConductor(array_merge($base, $payload));
    echo json_encode($respuesta, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
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
        echo json_encode(array(
            'ok' => false,
            'message' => 'Error fatal PHP: ' . $error['message'],
            'file' => isset($error['file']) ? $error['file'] : '',
            'line' => isset($error['line']) ? (int)$error['line'] : 0
        ), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
});

if (empty($_SESSION['user'])) {
    respConductor(false, array('message' => 'Sesion no valida.'));
}

function pdoActualConductor() {
    static $pdo = null;
    global $contenidoBdActual;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function tablaExisteConductor(PDO $pdo, $tabla) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?");
    $stmt->execute(array(strtoupper($tabla)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function columnaExisteConductor(PDO $pdo, $tabla, $columna) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ? AND RDB\$FIELD_NAME = ?");
    $stmt->execute(array(strtoupper($tabla), strtoupper($columna)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function asegurarEstructuraConductor(PDO $pdo) {
    $requeridas = array('SN_GUIAS', 'SN_GUIAS_DETALLE', 'SN_GUIAS_ESTADOS', 'SN_GUIAS_DETALLE_ESTADO', 'SN_GUIAS_DETALLE_CHAT');
    $faltantes = array();

    foreach ($requeridas as $t) {
        if (!tablaExisteConductor($pdo, $t)) {
            $faltantes[] = $t;
        }
    }

    if (!empty($faltantes)) {
        respConductor(false, array('message' => 'Faltan tablas para modulo conductor: ' . implode(', ', $faltantes) . '. Ejecuta 03_create_despachos_conductor.sql.'));
    }

    if (!columnaExisteConductor($pdo, 'KARDEX', 'SN_GUIA_ID')) {
        respConductor(false, array('message' => 'Falta KARDEX.SN_GUIA_ID. Ejecuta 01_alter_kardex.sql.'));
    }
}

function limpiarTxConductor(PDO $pdo) {
    try {
        if ($pdo->inTransaction()) {
            $pdo->commit();
        }
    } catch (Exception $e) {
    }
}

function nextIdConductor(PDO $pdo, $tabla) {
    $stmt = $pdo->query('SELECT COALESCE(MAX(ID), 0) + 1 FROM ' . $tabla);
    return (int)$stmt->fetchColumn();
}

function normalizarDocConductor($txt) {
    $v = strtoupper(trim((string)$txt));
    $v = str_replace(array(' ', '.', '-', '/', ','), '', $v);
    return $v;
}

function extraerNitDesdeTextoConductor($txt) {
    $val = strtoupper(trim((string)$txt));
    if ($val === '') {
        return '';
    }

    if (preg_match('/NIT[^0-9A-Z]*([0-9A-Z\\.\\-]{5,25})/i', $val, $m)) {
        return trim((string)$m[1]);
    }

    if (preg_match('/([0-9A-Z\\.\\-]{5,25})/', $val, $m2)) {
        return trim((string)$m2[1]);
    }

    return '';
}

function obtenerValorVariosConductor(PDO $pdo, $variab) {
    $stmt = $pdo->prepare('SELECT CONTENIDO FROM VARIOS WHERE VARIAB = ?');
    $stmt->execute(array((string)$variab));
    $v = $stmt->fetchColumn();
    return ($v === false) ? '' : trim((string)$v);
}

function buscarTeridConductorPorDocumento(PDO $pdo, $documento) {
    $doc = trim((string)$documento);
    if ($doc === '') {
        return 0;
    }

    $docCanon = normalizarDocConductor($doc);
    $sql = "
        SELECT FIRST 1 TERID
        FROM TERCEROS
        WHERE COALESCE(CONDUCTOR, 'N') = 'S'
          AND (
                UPPER(TRIM(COALESCE(NIT, ''))) = ?
             OR UPPER(TRIM(COALESCE(NITTRI, ''))) = ?
             OR UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(NIT, '')), ' ', ''), '.', ''), '-', ''), '/', ''), ',', '')) = ?
             OR UPPER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(COALESCE(NITTRI, '')), ' ', ''), '.', ''), '-', ''), '/', ''), ',', '')) = ?
          )
        ORDER BY TERID
    ";

    $stmt = $pdo->prepare($sql);
    $stmt->execute(array(strtoupper($doc), strtoupper($doc), $docCanon, $docCanon));
    $terid = $stmt->fetchColumn();
    return $terid ? (int)$terid : 0;
}

function obtenerContextoConductorUsuario(PDO $pdo, $usuario, $esAdmin) {
    $ctx = array(
        'es_admin' => $esAdmin ? true : false,
        'usuario' => strtoupper(trim((string)$usuario)),
        'terid' => 0,
        'origen' => '',
        'doc_fuente' => ''
    );

    if ($ctx['es_admin']) {
        return $ctx;
    }

    if ($ctx['usuario'] === '') {
        return $ctx;
    }

    $stmt1 = $pdo->prepare("
        SELECT FIRST 1 t.TERID
        FROM TERCEROS t
        INNER JOIN TERCEROSSELF ts ON ts.TERID = t.TERID
        WHERE COALESCE(t.CONDUCTOR, 'N') = 'S'
          AND UPPER(TRIM(COALESCE(ts.USUARIO, ''))) = ?
    ");
    $stmt1->execute(array($ctx['usuario']));
    $terid1 = $stmt1->fetchColumn();
    if ($terid1) {
        $ctx['terid'] = (int)$terid1;
        $ctx['origen'] = 'TERCEROSSELF.USUARIO';
        return $ctx;
    }

    // Regla usada en otros modulos: VARIOS.VARIAB = 'GVENDE' + USUARIO
    // CONTENIDO puede traer TERID o documento (NIT/NITTRI).
    $docVariosGvende = obtenerValorVariosConductor($pdo, 'GVENDE' . $ctx['usuario']);
    if ($docVariosGvende !== '') {
        if (preg_match('/^\d+$/', trim($docVariosGvende))) {
            $stmtTer = $pdo->prepare("
                SELECT FIRST 1 TERID
                FROM TERCEROS
                WHERE TERID = ?
                  AND COALESCE(CONDUCTOR, 'N') = 'S'
            ");
            $stmtTer->execute(array((int)$docVariosGvende));
            $teridNum = $stmtTer->fetchColumn();
            if ($teridNum) {
                $ctx['terid'] = (int)$teridNum;
                $ctx['origen'] = 'VARIOS.GVENDE<USUARIO> (TERID)';
                $ctx['doc_fuente'] = trim($docVariosGvende);
                return $ctx;
            }
        }

        $teridGvendeDoc = buscarTeridConductorPorDocumento($pdo, $docVariosGvende);
        if ($teridGvendeDoc > 0) {
            $ctx['terid'] = $teridGvendeDoc;
            $ctx['origen'] = 'VARIOS.GVENDE<USUARIO> (NIT/NITTRI)';
            $ctx['doc_fuente'] = trim($docVariosGvende);
            return $ctx;
        }
    }

    $docPreferidoEsNittri = strtoupper(obtenerValorVariosConductor($pdo, 'GFVP_NITTRI')) === 'S';
    $docVar = obtenerValorVariosConductor($pdo, 'GFVP_ENCAB4' . $ctx['usuario']);
    $docExtraido = extraerNitDesdeTextoConductor($docVar);

    if ($docExtraido !== '') {
        $teridDoc = buscarTeridConductorPorDocumento($pdo, $docExtraido);
        if ($teridDoc > 0) {
            $ctx['terid'] = $teridDoc;
            $ctx['origen'] = $docPreferidoEsNittri ? 'VARIOS.GFVP_ENCAB4 + NITTRI' : 'VARIOS.GFVP_ENCAB4 + NIT';
            $ctx['doc_fuente'] = $docExtraido;
            return $ctx;
        }
    }

    $teridDirecto = buscarTeridConductorPorDocumento($pdo, $ctx['usuario']);
    if ($teridDirecto > 0) {
        $ctx['terid'] = $teridDirecto;
        $ctx['origen'] = 'USUARIO COMO NIT/NITTRI';
        $ctx['doc_fuente'] = $ctx['usuario'];
        return $ctx;
    }

    return $ctx;
}

function guiaPerteneceAConductor(PDO $pdo, $idGuia, $teridConductor) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS WHERE ID = ? AND ID_CONDUCTOR = ?');
    $stmt->execute(array((int)$idGuia, (int)$teridConductor));
    return ((int)$stmt->fetchColumn()) > 0;
}

function detallePerteneceAGuiaConductor(PDO $pdo, $idGuia, $kardexId) {
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM SN_GUIAS_DETALLE WHERE ID_GUIA = ? AND KARDEX_ID = ?');
    $stmt->execute(array((int)$idGuia, (int)$kardexId));
    return ((int)$stmt->fetchColumn()) > 0;
}

function txtConductor($v) {
    return trim((string)$v);
}

function fechaHoraTxtConductor($fecha, $hora) {
    $f = txtConductor($fecha);
    $h = txtConductor($hora);
    if ($f === '' && $h === '') {
        return '';
    }
    if ($h === '') {
        return $f;
    }
    return trim($f . ' ' . $h);
}

function numeroFbConductor($valor) {
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
            $txt = str_replace('.', '', $txt);
            $txt = str_replace(',', '.', $txt);
        } else {
            $txt = str_replace(',', '', $txt);
        }
    } elseif (strpos($txt, ',') !== false) {
        $txt = str_replace(',', '.', $txt);
    }

    return is_numeric($txt) ? (float)$txt : 0.0;
}

function tokenRemisionEntregaConductor($kardexId) {
    return strtoupper(substr(sha1('D17_REMISION_' . (int)$kardexId . '_2026'), 0, 12));
}

function directorioPodConductor() {
    return __DIR__ . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'pod';
}

function asegurarDirectorioPodConductor() {
    $dir = directorioPodConductor();
    if (!is_dir($dir)) {
        @mkdir($dir, 0775, true);
    }
    if (!is_dir($dir) || !is_writable($dir)) {
        throw new Exception('No se pudo preparar carpeta POD en uploads/pod.');
    }
    return $dir;
}

function rutaRelativaPodConductor($nombreArchivo) {
    return 'uploads/pod/' . $nombreArchivo;
}

function guardarFotoPodConductor($file, $idGuia, $kardexId) {
    if (!isset($file) || !is_array($file) || !isset($file['tmp_name'])) {
        throw new Exception('Foto POD no recibida.');
    }
    if ((int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Error cargando foto POD.');
    }
    if (!is_uploaded_file($file['tmp_name'])) {
        throw new Exception('Archivo de foto POD invalido.');
    }

    $dir = asegurarDirectorioPodConductor();
    $ext = strtolower(pathinfo(isset($file['name']) ? (string)$file['name'] : '', PATHINFO_EXTENSION));
    if (!in_array($ext, array('jpg', 'jpeg', 'png'), true)) {
        throw new Exception('Formato de foto POD no soportado. Usa JPG o PNG.');
    }

    $nombre = 'podf_' . (int)$idGuia . '_' . (int)$kardexId . '_' . date('Ymd_His') . '_' . mt_rand(100, 999) . '.' . $ext;
    $abs = $dir . DIRECTORY_SEPARATOR . $nombre;
    if (!@move_uploaded_file($file['tmp_name'], $abs)) {
        throw new Exception('No se pudo guardar foto POD.');
    }

    return rutaRelativaPodConductor($nombre);
}

function guardarFirmaPodConductor($firmaData, $idGuia, $kardexId) {
    $txt = trim((string)$firmaData);
    if ($txt === '') {
        throw new Exception('Firma POD no recibida.');
    }

    if (!preg_match('/^data:image\/(png|jpeg|jpg);base64,/', $txt, $m)) {
        throw new Exception('Formato de firma POD no valido.');
    }

    $tipo = strtolower((string)$m[1]);
    $ext = ($tipo === 'png') ? 'png' : 'jpg';
    $base64 = substr($txt, strpos($txt, ',') + 1);
    $bin = base64_decode($base64, true);
    if ($bin === false || strlen($bin) < 100) {
        throw new Exception('Firma POD vacia o corrupta.');
    }

    $dir = asegurarDirectorioPodConductor();
    $nombre = 'pods_' . (int)$idGuia . '_' . (int)$kardexId . '_' . date('Ymd_His') . '_' . mt_rand(100, 999) . '.' . $ext;
    $abs = $dir . DIRECTORY_SEPARATOR . $nombre;
    if (@file_put_contents($abs, $bin) === false) {
        throw new Exception('No se pudo guardar firma POD.');
    }

    return rutaRelativaPodConductor($nombre);
}

function borrarArchivosPodConductor($rutasRelativas) {
    if (!is_array($rutasRelativas)) {
        return;
    }

    $base = realpath(__DIR__);
    if ($base === false) {
        return;
    }
    $base = rtrim($base, "\\/") . DIRECTORY_SEPARATOR;

    foreach ($rutasRelativas as $rel) {
        $r = trim((string)$rel);
        if ($r === '') {
            continue;
        }
        $abs = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $r));
        if ($abs === false) {
            continue;
        }
        if (strpos($abs, $base) !== 0) {
            continue;
        }
        if (is_file($abs)) {
            @unlink($abs);
        }
    }
}

function construirMensajePodConductor($meta) {
    $ts = isset($meta['ts']) ? trim((string)$meta['ts']) : '';
    $lat = isset($meta['lat']) ? trim((string)$meta['lat']) : '';
    $lng = isset($meta['lng']) ? trim((string)$meta['lng']) : '';
    $acc = isset($meta['acc']) ? trim((string)$meta['acc']) : '';
    $foto = isset($meta['foto']) ? trim((string)$meta['foto']) : '';
    $firma = isset($meta['firma']) ? trim((string)$meta['firma']) : '';
    $estado = isset($meta['estado']) ? strtoupper(trim((string)$meta['estado'])) : 'ENTREGADO';

    $msg = 'POD|EST=' . $estado
        . '|TS=' . $ts
        . '|LAT=' . $lat
        . '|LNG=' . $lng
        . '|ACC=' . $acc
        . '|FOTO=' . $foto
        . '|FIRMA=' . $firma;

    if (strlen($msg) > 500) {
        $msg = substr($msg, 0, 500);
    }
    return $msg;
}

function esErrorCheckEstadoLegacyConductor($e) {
    if (!($e instanceof Exception)) {
        return false;
    }
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

function aplicarCierreGuiaCompatibleConductor(PDO $pdo, $idGuia, $usuario) {
    $estadosIntento = array('ENTREGADO', 'FINALIZADO');

    foreach ($estadosIntento as $idx => $estadoCierre) {
        try {
            $stmtUpd = $pdo->prepare("UPDATE SN_GUIAS SET ESTADO_ACTUAL = ?, FECHA_EDICION = CURRENT_TIMESTAMP, USUARIO_EDITA = ? WHERE ID = ?");
            $stmtUpd->execute(array($estadoCierre, $usuario, $idGuia));

            $idEstado = nextIdConductor($pdo, 'SN_GUIAS_ESTADOS');
            $stmtIns = $pdo->prepare("INSERT INTO SN_GUIAS_ESTADOS (ID, ID_GUIA, ESTADO, FECHA_HORA_ESTADO, USUARIO, OBSERVACION) VALUES (?, ?, ?, CURRENT_TIMESTAMP, ?, ?)");
            $stmtIns->execute(array($idEstado, $idGuia, $estadoCierre, $usuario, 'CIERRE AUTOMATICO POR ENTREGA TOTAL DE REMISIONES'));
            return $estadoCierre;
        } catch (PDOException $e) {
            $esUltimo = ($idx === count($estadosIntento) - 1);
            if ($esUltimo || !esErrorCheckEstadoLegacyConductor($e)) {
                throw $e;
            }
        }
    }

    return 'ENTREGADO';
}

function actualizarEstadoGuiaSiAplica(PDO $pdo, $idGuia, $usuario) {
    $sqlPend = "
        SELECT COUNT(*)
        FROM SN_GUIAS_DETALLE d
        LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID
        WHERE d.ID_GUIA = ?
          AND (e.ID IS NULL OR UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA, ''))) IN ('', 'PENDIENTE'))
    ";
    $stmtPend = $pdo->prepare($sqlPend);
    $stmtPend->execute(array($idGuia));
    $pendientes = (int)$stmtPend->fetchColumn();

    if ($pendientes > 0) {
        return;
    }

    $stmtEstado = $pdo->prepare('SELECT ESTADO_ACTUAL FROM SN_GUIAS WHERE ID = ?');
    $stmtEstado->execute(array($idGuia));
    $estadoActual = strtoupper(txtConductor($stmtEstado->fetchColumn()));

    if ($estadoActual === 'FINALIZADO' || $estadoActual === 'ENTREGADO') {
        return;
    }

    aplicarCierreGuiaCompatibleConductor($pdo, $idGuia, $usuario);
}

try {
    $pdo = pdoActualConductor();
    asegurarEstructuraConductor($pdo);

    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    if ($action === '') {
        respConductor(false, array('message' => 'Accion no informada.'));
    }

    $usuarioSesion = strtoupper(trim((string)$_SESSION['user']));
    $esAdminSesion = function_exists('esUsuarioAdministradorMenu') ? esUsuarioAdministradorMenu($usuarioSesion) : false;
    $ctxConductor = obtenerContextoConductorUsuario($pdo, $usuarioSesion, $esAdminSesion);

    if ($action === 'listar_guias') {
        $estadoGuia = strtoupper(trim((string)(isset($_POST['estado_guia']) ? $_POST['estado_guia'] : 'PENDIENTES')));
        if (!in_array($estadoGuia, array('PENDIENTES', 'TODAS', 'ENTREGADAS'), true)) {
            $estadoGuia = 'PENDIENTES';
        }

        $where = array();
        if ($estadoGuia === 'PENDIENTES') {
            $where[] = "(
                SELECT COUNT(*)
                FROM SN_GUIAS_DETALLE d0
                LEFT JOIN SN_GUIAS_DETALLE_ESTADO e0 ON e0.ID_GUIA = d0.ID_GUIA AND e0.KARDEX_ID = d0.KARDEX_ID
                WHERE d0.ID_GUIA = g.ID
                  AND (e0.ID IS NULL OR UPPER(TRIM(COALESCE(e0.ESTADO_ENTREGA, ''))) IN ('', 'PENDIENTE'))
            ) > 0";
        } elseif ($estadoGuia === 'ENTREGADAS') {
            $where[] = "(SELECT COUNT(*) FROM SN_GUIAS_DETALLE d1 WHERE d1.ID_GUIA = g.ID) > 0";
            $where[] = "(
                SELECT COUNT(*)
                FROM SN_GUIAS_DETALLE d0
                LEFT JOIN SN_GUIAS_DETALLE_ESTADO e0 ON e0.ID_GUIA = d0.ID_GUIA AND e0.KARDEX_ID = d0.KARDEX_ID
                WHERE d0.ID_GUIA = g.ID
                  AND (e0.ID IS NULL OR UPPER(TRIM(COALESCE(e0.ESTADO_ENTREGA, ''))) IN ('', 'PENDIENTE'))
            ) = 0";
        }

        $warning = '';
        if (!$ctxConductor['es_admin']) {
            if ((int)$ctxConductor['terid'] > 0) {
                $where[] = "g.ID_CONDUCTOR = " . (int)$ctxConductor['terid'];
            } else {
                $where[] = "1 = 0";
                $warning = 'No se encontro relacion de usuario-conductor en TERCEROSSELF o VARIOS (GVENDE<USUARIO>). Configura el dato para filtrar guias.';
            }
        }

        if (empty($where)) {
            $where[] = "1 = 1";
        }

        $sql = "
            SELECT
                g.ID,
                g.PREFIJO,
                g.CONSECUTIVO,
                g.FECHA_GUIA,
                g.ESTADO_ACTUAL,
                COALESCE(tc.NOMBRE, '') AS CONDUCTOR,
                (SELECT COUNT(*) FROM SN_GUIAS_DETALLE d WHERE d.ID_GUIA = g.ID) AS TOTAL_REMISIONES,
                (
                    SELECT COUNT(*)
                    FROM SN_GUIAS_DETALLE d
                    LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID
                    WHERE d.ID_GUIA = g.ID
                      AND (e.ID IS NULL OR UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA, ''))) IN ('', 'PENDIENTE'))
                ) AS TOTAL_PENDIENTES
            FROM SN_GUIAS g
            LEFT JOIN TERCEROS tc ON tc.TERID = g.ID_CONDUCTOR
            WHERE " . implode(' AND ', $where) . "
            ORDER BY g.FECHA_GUIA ASC, g.ID ASC
        ";

        $stmt = $pdo->query($sql);

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'id' => (int)$row['ID'],
                'num_guia' => txtConductor($row['PREFIJO']) . '-' . txtConductor($row['CONSECUTIVO']),
                'fecha_guia' => $row['FECHA_GUIA'],
                'estado_actual' => txtConductor($row['ESTADO_ACTUAL']),
                'conductor' => txtConductor($row['CONDUCTOR']),
                'total_remisiones' => (int)$row['TOTAL_REMISIONES'],
                'total_pendientes' => (int)$row['TOTAL_PENDIENTES']
            );
        }

        respConductor(true, array(
            'data' => $data,
            'warning' => $warning,
            'estado_guia' => $estadoGuia,
            'conductor' => array(
                'terid' => (int)$ctxConductor['terid'],
                'origen' => $ctxConductor['origen'],
                'doc_fuente' => $ctxConductor['doc_fuente']
            )
        ));
    }

    if ($action === 'listar_remisiones_guia') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        if ($idGuia <= 0) {
            respConductor(false, array('message' => 'Guia no valida.'));
        }

        if (!$ctxConductor['es_admin']) {
            if ((int)$ctxConductor['terid'] <= 0 || !guiaPerteneceAConductor($pdo, $idGuia, (int)$ctxConductor['terid'])) {
                respConductor(false, array('message' => 'No tienes permiso sobre esta guia.'));
            }
        }

        $sql = "
            SELECT
                d.KARDEX_ID,
                k.CODPREFIJO,
                k.NUMERO,
                k.FECHA,
                k.HORA,
                COALESCE(tc.NOMBRE, '') AS CLIENTE,
                COALESCE(ks.DIRECC1, tc.DIRECC1, tc.DIRECC2, '') AS DIRECCION,
                COALESCE(ks.TELEF1, tc.TELEF1, tc.TELEF2, '') AS TELEFONO,
                COALESCE(tv.NOMBRE, '') AS VENDEDOR,
                CAST(COALESCE(d.PESO, 0) AS CHAR(30)) AS PESO_TXT,
                CAST(COALESCE(d.VALOR_BASE, 0) AS CHAR(30)) AS VALOR_TXT,
                COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') AS ESTADO_ENTREGA,
                COALESCE(e.OBSERVACION, '') AS OBSERVACION,
                (
                    SELECT COUNT(*)
                    FROM SN_GUIAS_DETALLE_CHAT c
                    WHERE c.ID_GUIA = d.ID_GUIA
                      AND c.KARDEX_ID = d.KARDEX_ID
                ) AS TOTAL_CHAT
                ,
                (
                    SELECT COUNT(*)
                    FROM SN_GUIAS_DETALLE_CHAT cp
                    WHERE cp.ID_GUIA = d.ID_GUIA
                      AND cp.KARDEX_ID = d.KARDEX_ID
                      AND UPPER(TRIM(COALESCE(cp.TIPO, ''))) = 'POD'
                ) AS TOTAL_POD
            FROM SN_GUIAS_DETALLE d
            LEFT JOIN KARDEX k ON k.KARDEXID = d.KARDEX_ID
            LEFT JOIN KARDEXSELF ks ON ks.KARDEXID = d.KARDEX_ID
            LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
            LEFT JOIN TERCEROS tv ON tv.TERID = k.VENDEDOR
            LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID
            WHERE d.ID_GUIA = ?
            ORDER BY k.FECHA ASC, k.HORA ASC, d.ID ASC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute(array($idGuia));

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'kardex_id' => (int)$row['KARDEX_ID'],
                'remision' => txtConductor($row['CODPREFIJO']) . '-' . txtConductor($row['NUMERO']),
                'fecha_hora' => fechaHoraTxtConductor($row['FECHA'], $row['HORA']),
                'cliente' => txtConductor($row['CLIENTE']),
                'direccion' => txtConductor($row['DIRECCION']),
                'telefono' => txtConductor($row['TELEFONO']),
                'vendedor' => txtConductor($row['VENDEDOR']),
                'peso' => numeroFbConductor($row['PESO_TXT']),
                'valor' => numeroFbConductor($row['VALOR_TXT']),
                'estado_entrega' => txtConductor($row['ESTADO_ENTREGA']),
                'observacion' => txtConductor($row['OBSERVACION']),
                'total_chat' => (int)$row['TOTAL_CHAT'],
                'tiene_pod' => ((int)$row['TOTAL_POD']) > 0 ? 1 : 0,
                'token_pdf' => tokenRemisionEntregaConductor((int)$row['KARDEX_ID'])
            );
        }

        respConductor(true, array('data' => $data));
    }

    if ($action === 'guardar_estado_entrega' || $action === 'marcar_entregado' || $action === 'justificar_no_entregado' || $action === 'justificar_parcial') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $kardexId = isset($_POST['kardex_id']) ? (int)$_POST['kardex_id'] : 0;
        $obs = trim((string)(isset($_POST['observacion']) ? $_POST['observacion'] : ''));
        $usuario = strtoupper(trim((string)$_SESSION['user']));
        $latitud = trim((string)(isset($_POST['latitud']) ? $_POST['latitud'] : ''));
        $longitud = trim((string)(isset($_POST['longitud']) ? $_POST['longitud'] : ''));
        $precisionGps = trim((string)(isset($_POST['precision_gps']) ? $_POST['precision_gps'] : ''));
        $firmaData = (string)(isset($_POST['firma_data']) ? $_POST['firma_data'] : '');
        $fotoFile = isset($_FILES['foto']) ? $_FILES['foto'] : null;

        if ($idGuia <= 0 || $kardexId <= 0) {
            respConductor(false, array('message' => 'Parametros invalidos.'));
        }

        if (!$ctxConductor['es_admin']) {
            if ((int)$ctxConductor['terid'] <= 0 || !guiaPerteneceAConductor($pdo, $idGuia, (int)$ctxConductor['terid'])) {
                respConductor(false, array('message' => 'No tienes permiso sobre esta guia.'));
            }
        }

        $estado = 'ENTREGADO';
        if ($action === 'guardar_estado_entrega') {
            $estado = strtoupper(trim((string)(isset($_POST['estado_entrega']) ? $_POST['estado_entrega'] : 'ENTREGADO')));
            if (!in_array($estado, array('ENTREGADO', 'NO_ENTREGADO', 'ENTREGA_PARCIAL'), true)) {
                respConductor(false, array('message' => 'Estado de entrega no valido.'));
            }
        } else {
            if ($action === 'justificar_no_entregado') {
                $estado = 'NO_ENTREGADO';
            } elseif ($action === 'justificar_parcial') {
                $estado = 'ENTREGA_PARCIAL';
            }
        }

        if ($estado !== 'ENTREGADO' && $obs === '') {
            respConductor(false, array('message' => 'Debes ingresar una justificacion para este estado.'));
        }

        if ($estado === 'ENTREGADO') {
            if ($latitud === '' || $longitud === '') {
                respConductor(false, array('message' => 'Debes capturar geolocalizacion para ENTREGADO.'));
            }
            if (!is_numeric($latitud) || !is_numeric($longitud)) {
                respConductor(false, array('message' => 'La geolocalizacion es invalida.'));
            }
            if (trim($firmaData) === '') {
                respConductor(false, array('message' => 'Debes registrar la firma para ENTREGADO.'));
            }
            if (!$fotoFile || !isset($fotoFile['tmp_name']) || (int)$fotoFile['error'] !== UPLOAD_ERR_OK) {
                respConductor(false, array('message' => 'Debes adjuntar foto para ENTREGADO.'));
            }
        }

        if (strlen($obs) > 300) {
            $obs = substr($obs, 0, 300);
        }
        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }

        if (!detallePerteneceAGuiaConductor($pdo, $idGuia, $kardexId)) {
            respConductor(false, array('message' => 'La remision no pertenece a la guia indicada.'));
        }

        $podFotoRuta = '';
        $podFirmaRuta = '';
        $podTimestamp = date('Y-m-d H:i:s');
        $archivosTmpPod = array();
        $usaGpsKardex = columnaExisteConductor($pdo, 'KARDEX', 'SN_LONGITUD') && columnaExisteConductor($pdo, 'KARDEX', 'SN_LATITUD');
        if ($estado === 'ENTREGADO' && !$usaGpsKardex) {
            respConductor(false, array('message' => 'Faltan columnas KARDEX.SN_LONGITUD y/o KARDEX.SN_LATITUD. Ejecuta 06_alter_kardex_geo.sql.'));
        }

        try {
            if ($estado === 'ENTREGADO') {
                $podFotoRuta = guardarFotoPodConductor($fotoFile, $idGuia, $kardexId);
                $podFirmaRuta = guardarFirmaPodConductor($firmaData, $idGuia, $kardexId);
                $archivosTmpPod = array($podFotoRuta, $podFirmaRuta);
            }

            limpiarTxConductor($pdo);
            $pdo->beginTransaction();

            $stmtEx = $pdo->prepare('SELECT ID FROM SN_GUIAS_DETALLE_ESTADO WHERE ID_GUIA = ? AND KARDEX_ID = ?');
            $stmtEx->execute(array($idGuia, $kardexId));
            $idExistente = $stmtEx->fetchColumn();

            if ($idExistente) {
                $pdo->rollBack();
                respConductor(false, array('message' => 'Esta remision ya tiene estado registrado y no se puede modificar.'));
            }

            $idEstado = nextIdConductor($pdo, 'SN_GUIAS_DETALLE_ESTADO');
            $stmtIn = $pdo->prepare('INSERT INTO SN_GUIAS_DETALLE_ESTADO (ID, ID_GUIA, KARDEX_ID, ESTADO_ENTREGA, OBSERVACION, FECHA_ESTADO, USUARIO) VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, ?)');
            $stmtIn->execute(array($idEstado, $idGuia, $kardexId, $estado, ($obs !== '' ? $obs : null), $usuario));

            if ($estado === 'ENTREGADO') {
                $stmtGps = $pdo->prepare('UPDATE KARDEX SET SN_LONGITUD = ?, SN_LATITUD = ? WHERE KARDEXID = ?');
                $stmtGps->execute(array((float)$longitud, (float)$latitud, $kardexId));
            }

            if ($obs !== '') {
                $idChat = nextIdConductor($pdo, 'SN_GUIAS_DETALLE_CHAT');
                $msg = 'EVENTO ENTREGA: ' . $estado . ' - ' . $obs;
                if (strlen($msg) > 500) {
                    $msg = substr($msg, 0, 500);
                }
                $stmtChat = $pdo->prepare('INSERT INTO SN_GUIAS_DETALLE_CHAT (ID, ID_GUIA, KARDEX_ID, MENSAJE, FECHA_MENSAJE, USUARIO, TIPO) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)');
                $stmtChat->execute(array($idChat, $idGuia, $kardexId, $msg, $usuario, 'EVENTO'));
            }

            if ($estado === 'ENTREGADO') {
                $idPod = nextIdConductor($pdo, 'SN_GUIAS_DETALLE_CHAT');
                $mensajePod = construirMensajePodConductor(array(
                    'estado' => $estado,
                    'ts' => $podTimestamp,
                    'lat' => $latitud,
                    'lng' => $longitud,
                    'acc' => $precisionGps,
                    'foto' => $podFotoRuta,
                    'firma' => $podFirmaRuta
                ));
                $stmtPod = $pdo->prepare('INSERT INTO SN_GUIAS_DETALLE_CHAT (ID, ID_GUIA, KARDEX_ID, MENSAJE, FECHA_MENSAJE, USUARIO, TIPO) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)');
                $stmtPod->execute(array($idPod, $idGuia, $kardexId, $mensajePod, $usuario, 'POD'));
            }

            actualizarEstadoGuiaSiAplica($pdo, $idGuia, $usuario);

            $pdo->commit();
            respConductor(true, array(
                'message' => 'Estado de entrega actualizado.',
                'pod' => array(
                    'tiene_pod' => ($estado === 'ENTREGADO') ? 1 : 0,
                    'token_pdf' => tokenRemisionEntregaConductor($kardexId)
                )
            ));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            if (!empty($archivosTmpPod)) {
                borrarArchivosPodConductor($archivosTmpPod);
            }
            throw $e;
        }
    }

    if ($action === 'obtener_chat_remision') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $kardexId = isset($_POST['kardex_id']) ? (int)$_POST['kardex_id'] : 0;

        if ($idGuia <= 0 || $kardexId <= 0) {
            respConductor(false, array('message' => 'Parametros invalidos.'));
        }

        if (!$ctxConductor['es_admin']) {
            if ((int)$ctxConductor['terid'] <= 0 || !guiaPerteneceAConductor($pdo, $idGuia, (int)$ctxConductor['terid'])) {
                respConductor(false, array('message' => 'No tienes permiso sobre esta guia.'));
            }
        }

        if (!detallePerteneceAGuiaConductor($pdo, $idGuia, $kardexId)) {
            respConductor(false, array('message' => 'La remision no pertenece a la guia indicada.'));
        }

        $stmt = $pdo->prepare('SELECT ID, MENSAJE, FECHA_MENSAJE, USUARIO, TIPO FROM SN_GUIAS_DETALLE_CHAT WHERE ID_GUIA = ? AND KARDEX_ID = ? ORDER BY FECHA_MENSAJE ASC, ID ASC');
        $stmt->execute(array($idGuia, $kardexId));

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'id' => (int)$row['ID'],
                'mensaje' => txtConductor($row['MENSAJE']),
                'fecha_mensaje' => $row['FECHA_MENSAJE'],
                'usuario' => txtConductor($row['USUARIO']),
                'tipo' => txtConductor($row['TIPO'])
            );
        }

        respConductor(true, array('data' => $data));
    }

    if ($action === 'enviar_chat_remision') {
        $idGuia = isset($_POST['id_guia']) ? (int)$_POST['id_guia'] : 0;
        $kardexId = isset($_POST['kardex_id']) ? (int)$_POST['kardex_id'] : 0;
        $mensaje = trim((string)(isset($_POST['mensaje']) ? $_POST['mensaje'] : ''));
        $usuario = strtoupper(trim((string)$_SESSION['user']));

        if ($idGuia <= 0 || $kardexId <= 0) {
            respConductor(false, array('message' => 'Parametros invalidos.'));
        }

        if (!$ctxConductor['es_admin']) {
            if ((int)$ctxConductor['terid'] <= 0 || !guiaPerteneceAConductor($pdo, $idGuia, (int)$ctxConductor['terid'])) {
                respConductor(false, array('message' => 'No tienes permiso sobre esta guia.'));
            }
        }

        if ($mensaje === '') {
            respConductor(false, array('message' => 'Escribe un mensaje para enviar.'));
        }

        if (!detallePerteneceAGuiaConductor($pdo, $idGuia, $kardexId)) {
            respConductor(false, array('message' => 'La remision no pertenece a la guia indicada.'));
        }

        if (strlen($mensaje) > 500) {
            $mensaje = substr($mensaje, 0, 500);
        }
        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }

        $idChat = nextIdConductor($pdo, 'SN_GUIAS_DETALLE_CHAT');
        $stmt = $pdo->prepare('INSERT INTO SN_GUIAS_DETALLE_CHAT (ID, ID_GUIA, KARDEX_ID, MENSAJE, FECHA_MENSAJE, USUARIO, TIPO) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, ?)');
        $stmt->execute(array($idChat, $idGuia, $kardexId, $mensaje, $usuario, 'CHAT'));

        respConductor(true, array('message' => 'Mensaje enviado.'));
    }

    respConductor(false, array('message' => 'Accion no soportada.'));
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $e2) {
        }
    }
    $accionError = isset($action) ? $action : 'desconocida';
    respConductor(false, array('message' => 'Error (' . $accionError . '): ' . $e->getMessage()));
}
