<?php
ob_start();
require('conecta.php');

header('Content-Type: application/json; charset=UTF-8');

function rcm_utf8($v) {
    if (!is_string($v)) {
        return $v;
    }
    return preg_match('//u', $v) ? $v : utf8_encode($v);
}

function rcm_utf8_r($d) {
    if (is_array($d)) {
        $o = array();
        foreach ($d as $k => $v) {
            $o[rcm_utf8((string)$k)] = rcm_utf8_r($v);
        }
        return $o;
    }
    return rcm_utf8($d);
}

function rcm_resp($ok, $payload = array()) {
    $out = '';
    if (ob_get_level() > 0) {
        $out = trim(ob_get_contents());
        ob_clean();
    }
    if ($out !== '') {
        $payload['debug_output'] = $out;
    }
    echo json_encode(rcm_utf8_r(array_merge(array('ok' => $ok ? true : false), $payload)), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

register_shutdown_function(function() {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=UTF-8');
        }
        echo json_encode(array(
            'ok' => false,
            'message' => 'Error fatal PHP: ' . $e['message'],
            'file' => isset($e['file']) ? $e['file'] : '',
            'line' => isset($e['line']) ? (int)$e['line'] : 0
        ), JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    }
});

if (empty($_SESSION['user'])) {
    rcm_resp(false, array('message' => 'Sesion no valida.'));
}

function rcm_pdo() {
    static $pdo = null;
    global $contenidoBdActual;
    if ($pdo instanceof PDO) {
        return $pdo;
    }
    $pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function rcm_tabla(PDO $pdo, $tabla) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?");
    $s->execute(array(strtoupper($tabla)));
    return ((int)$s->fetchColumn()) > 0;
}

function rcm_col(PDO $pdo, $tabla, $col) {
    $s = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ? AND RDB\$FIELD_NAME = ?");
    $s->execute(array(strtoupper($tabla), strtoupper($col)));
    return ((int)$s->fetchColumn()) > 0;
}

function rcm_txt($v) {
    return trim((string)$v);
}

function rcm_num($v) {
    if ($v === null) {
        return 0.0;
    }
    if (is_int($v) || is_float($v)) {
        return (float)$v;
    }
    $t = trim((string)$v);
    if ($t === '') {
        return 0.0;
    }
    $t = str_replace(' ', '', $t);
    if (strpos($t, ',') !== false && strpos($t, '.') !== false) {
        if (strrpos($t, ',') > strrpos($t, '.')) {
            $t = str_replace('.', '', $t);
            $t = str_replace(',', '.', $t);
        } else {
            $t = str_replace(',', '', $t);
        }
    } elseif (strpos($t, ',') !== false) {
        $t = str_replace(',', '.', $t);
    }
    return is_numeric($t) ? (float)$t : 0.0;
}

function rcm_remision($p, $n) {
    return rcm_txt($p) . '-' . rcm_txt($n);
}

function rcm_guia($p, $n) {
    return rcm_txt($p) . '-' . rcm_txt($n);
}

try {
    $pdo = rcm_pdo();
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

    if ($action === 'listar_conductores') {
        $where = "COALESCE(CONDUCTOR, 'N') = 'S'";
        if (rcm_col($pdo, 'TERCEROS', 'INACTIVO')) {
            $where .= " AND COALESCE(INACTIVO, 'N') <> 'S'";
        }
        $sql = "SELECT TERID, NOMBRE FROM TERCEROS WHERE $where ORDER BY NOMBRE";
        $stmt = $pdo->query($sql);
        $data = array();
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'terid' => (int)$r['TERID'],
                'nombre' => rcm_txt($r['NOMBRE'])
            );
        }
        rcm_resp(true, array('data' => $data));
    }

    if ($action === 'listar_ruta') {
        if (!rcm_tabla($pdo, 'SN_GUIAS') || !rcm_tabla($pdo, 'SN_GUIAS_DETALLE') || !rcm_tabla($pdo, 'KARDEX')) {
            rcm_resp(false, array('message' => 'Faltan tablas base para consultar la ruta.'));
        }
        if (!rcm_col($pdo, 'KARDEX', 'SN_LONGITUD') || !rcm_col($pdo, 'KARDEX', 'SN_LATITUD')) {
            rcm_resp(false, array('message' => 'Faltan columnas KARDEX.SN_LONGITUD y KARDEX.SN_LATITUD. Ejecuta 06_alter_kardex_geo.sql.'));
        }

        $fecha = trim((string)(isset($_POST['fecha']) ? $_POST['fecha'] : ''));
        $idConductor = isset($_POST['id_conductor']) ? (int)$_POST['id_conductor'] : 0;
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
            $fecha = date('Y-m-d');
        }

        $desde = $fecha . ' 00:00:00';
        $hasta = $fecha . ' 23:59:59';

        $hasEstadoDet = rcm_tabla($pdo, 'SN_GUIAS_DETALLE_ESTADO');
        $hasKs = rcm_tabla($pdo, 'KARDEXSELF');
        $hasTer = rcm_tabla($pdo, 'TERCEROS');

        $sql = "
            SELECT
                g.ID_CONDUCTOR,
                g.PREFIJO AS GUIA_PREFIJO,
                g.CONSECUTIVO AS GUIA_CONSECUTIVO,
                g.FECHA_GUIA,
                d.KARDEX_ID,
                d.ID AS DET_ID,
                k.CODPREFIJO,
                k.NUMERO,
                k.FECHA,
                k.HORA,
                CAST(COALESCE(k.SN_LONGITUD, 0) AS CHAR(40)) AS LONG_TXT,
                CAST(COALESCE(k.SN_LATITUD, 0) AS CHAR(40)) AS LAT_TXT,
                " . ($hasTer ? "CAST(COALESCE(tcon.NOMBRE, '') AS VARCHAR(120))" : "CAST('' AS VARCHAR(120))") . " AS CONDUCTOR,
                " . ($hasTer ? "CAST(COALESCE(tcli.NOMBRE, '') AS VARCHAR(120))" : "CAST('' AS VARCHAR(120))") . " AS CLIENTE,
                CAST(COALESCE(" . ($hasKs ? "ks.DIRECC1, " : "") . ($hasTer ? "tcli.DIRECC1, tcli.DIRECC2" : "''") . ", '') AS VARCHAR(180)) AS DIRECCION,
                " . ($hasEstadoDet ? "CAST(COALESCE(de.ESTADO_ENTREGA, 'PENDIENTE') AS VARCHAR(30))" : "CAST('PENDIENTE' AS VARCHAR(30))") . " AS ESTADO_ENTREGA,
                " . ($hasEstadoDet ? "COALESCE(de.FECHA_ESTADO, g.FECHA_GUIA)" : "g.FECHA_GUIA") . " AS FECHA_EVENTO
            FROM SN_GUIAS_DETALLE d
            INNER JOIN SN_GUIAS g ON g.ID = d.ID_GUIA
            LEFT JOIN KARDEX k ON k.KARDEXID = d.KARDEX_ID
            " . ($hasTer ? "LEFT JOIN TERCEROS tcon ON tcon.TERID = g.ID_CONDUCTOR LEFT JOIN TERCEROS tcli ON tcli.TERID = k.CLIENTE" : "") . "
            " . ($hasKs ? "LEFT JOIN KARDEXSELF ks ON ks.KARDEXID = d.KARDEX_ID" : "") . "
            " . ($hasEstadoDet ? "LEFT JOIN SN_GUIAS_DETALLE_ESTADO de ON de.ID_GUIA = d.ID_GUIA AND de.KARDEX_ID = d.KARDEX_ID" : "") . "
            WHERE g.FECHA_GUIA >= ?
              AND g.FECHA_GUIA <= ?
              AND k.SN_LONGITUD IS NOT NULL
              AND k.SN_LATITUD IS NOT NULL
              AND ABS(k.SN_LONGITUD) > 0
              AND ABS(k.SN_LATITUD) > 0
        ";

        $params = array($desde, $hasta);
        if ($idConductor > 0) {
            $sql .= " AND g.ID_CONDUCTOR = ? ";
            $params[] = $idConductor;
        }
        $sql .= " ORDER BY g.ID_CONDUCTOR, FECHA_EVENTO, k.FECHA, k.HORA, d.ID ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $data = array();
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $data[] = array(
                'id_conductor' => ($r['ID_CONDUCTOR'] !== null ? (int)$r['ID_CONDUCTOR'] : 0),
                'conductor' => rcm_txt($r['CONDUCTOR']) !== '' ? rcm_txt($r['CONDUCTOR']) : 'SIN CONDUCTOR',
                'guia' => rcm_guia($r['GUIA_PREFIJO'], $r['GUIA_CONSECUTIVO']),
                'kardex_id' => (int)$r['KARDEX_ID'],
                'remision' => rcm_remision($r['CODPREFIJO'], $r['NUMERO']),
                'cliente' => rcm_txt($r['CLIENTE']),
                'direccion' => rcm_txt($r['DIRECCION']),
                'estado' => strtoupper(rcm_txt($r['ESTADO_ENTREGA'])),
                'fecha_evento' => $r['FECHA_EVENTO'],
                'latitud' => rcm_num($r['LAT_TXT']),
                'longitud' => rcm_num($r['LONG_TXT'])
            );
        }

        rcm_resp(true, array('data' => $data));
    }

    rcm_resp(false, array('message' => 'Accion no valida.'));
} catch (PDOException $e) {
    rcm_resp(false, array('message' => 'Error SQL: ' . $e->getMessage()));
} catch (Exception $e) {
    rcm_resp(false, array('message' => 'Error: ' . $e->getMessage()));
}
