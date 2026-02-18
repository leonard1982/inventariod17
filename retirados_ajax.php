<?php
ob_start();
require('conecta.php');

header('Content-Type: application/json; charset=UTF-8');

function toUtf8SeguroRet($valor) {
    if (!is_string($valor)) {
        return $valor;
    }
    if (preg_match('//u', $valor)) {
        return $valor;
    }
    return utf8_encode($valor);
}

function normalizarUtf8RecRet($dato) {
    if (is_array($dato)) {
        $out = array();
        foreach ($dato as $k => $v) {
            $out[toUtf8SeguroRet((string)$k)] = normalizarUtf8RecRet($v);
        }
        return $out;
    }
    return toUtf8SeguroRet($dato);
}

function respRet($ok, $payload = array()) {
    $salidaPrevia = '';
    if (ob_get_level() > 0) {
        $salidaPrevia = trim(ob_get_contents());
        ob_clean();
    }
    if ($salidaPrevia !== '') {
        $payload['debug_output'] = $salidaPrevia;
    }

    $base = array('ok' => $ok ? true : false);
    $respuesta = normalizarUtf8RecRet(array_merge($base, $payload));
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
    respRet(false, array('message' => 'Sesion no valida.'));
}

function pdoActualRet() {
    static $pdo = null;
    global $contenidoBdActual;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function tablaExisteRet(PDO $pdo, $tabla) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?");
    $stmt->execute(array(strtoupper($tabla)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function txtRet($v) {
    return trim((string)$v);
}

function numeroFbRet($valor) {
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

function fechaHoraRet($fecha, $hora) {
    $f = txtRet($fecha);
    $h = txtRet($hora);
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

function siguienteIdRet(PDO $pdo, $tabla) {
    $stmt = $pdo->query('SELECT COALESCE(MAX(ID), 0) + 1 FROM ' . $tabla);
    return (int)$stmt->fetchColumn();
}

function normalizarFechaRet($fecha, $esHasta = false) {
    $valor = trim((string)$fecha);
    if ($valor === '') {
        return null;
    }

    $valor = str_replace('T', ' ', $valor);
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $valor)) {
        $valor .= $esHasta ? ' 23:59:59' : ' 00:00:00';
    }

    return $valor;
}

try {
    $pdo = pdoActualRet();
    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    if ($action === '') {
        respRet(false, array('message' => 'Accion no informada.'));
    }

    if (!tablaExisteRet($pdo, 'SN_RETIROS_ESTADO')) {
        respRet(false, array('message' => 'No existe SN_RETIROS_ESTADO. Ejecuta 05_create_retirados.sql.'));
    }

    if ($action === 'listar') {
        $busqueda = trim((string)(isset($_POST['busqueda']) ? $_POST['busqueda'] : ''));
        $prefijo = strtoupper(trim((string)(isset($_POST['prefijo']) ? $_POST['prefijo'] : 'TODOS')));
        $estadoFiltro = strtoupper(trim((string)(isset($_POST['estado']) ? $_POST['estado'] : 'TODOS')));
        $fechaDesde = normalizarFechaRet(isset($_POST['fecha_desde']) ? $_POST['fecha_desde'] : '', false);
        $fechaHasta = normalizarFechaRet(isset($_POST['fecha_hasta']) ? $_POST['fecha_hasta'] : '', true);

        $where = array();
        $params = array();

        $where[] = "k.CODCOMP = 'RS'";
        $where[] = "k.FECASENTAD IS NOT NULL";
        $where[] = "k.FECANULADO IS NULL";
        $where[] = "k.SN_GUIA_ID IS NULL";

        if ($fechaDesde !== null) {
            $where[] = "k.FECHA >= ?";
            $params[] = $fechaDesde;
        }
        if ($fechaHasta !== null) {
            $where[] = "k.FECHA <= ?";
            $params[] = $fechaHasta;
        }

        if (in_array($prefijo, array('00', '01', '50'), true)) {
            $where[] = "k.CODPREFIJO = ?";
            $params[] = $prefijo;
        }

        if ($busqueda !== '') {
            $where[] = "(
                CAST(k.CODPREFIJO AS VARCHAR(10)) CONTAINING ?
                OR CAST(k.NUMERO AS VARCHAR(30)) CONTAINING ?
                OR CAST(COALESCE(tc.NOMBRE, '') AS VARCHAR(120)) CONTAINING ?
                OR CAST(COALESCE(ks.DIRECC1, tc.DIRECC1, tc.DIRECC2, '') AS VARCHAR(180)) CONTAINING ?
            )";
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
            $params[] = $busqueda;
        }

        if ($estadoFiltro === 'DISPONIBLE') {
            $where[] = "COALESCE(UPPER(TRIM(r.ESTADO)), '') = ''";
        } elseif ($estadoFiltro === 'PARA_RETIRAR') {
            $where[] = "UPPER(TRIM(COALESCE(r.ESTADO, ''))) = 'PARA_RETIRAR'";
        } elseif ($estadoFiltro === 'RETIRADO') {
            $where[] = "UPPER(TRIM(COALESCE(r.ESTADO, ''))) = 'RETIRADO'";
        }

        $sql = "
            SELECT FIRST 350
                k.KARDEXID,
                k.CODPREFIJO,
                k.NUMERO,
                k.FECHA,
                k.HORA,
                CAST(COALESCE(tc.NOMBRE, '') AS VARCHAR(120)) AS CLIENTE,
                CAST(COALESCE(ks.DIRECC1, tc.DIRECC1, tc.DIRECC2, '') AS VARCHAR(180)) AS DIRECCION,
                CAST(COALESCE(ks.TELEF1, tc.TELEF1, tc.TELEF2, '') AS VARCHAR(80)) AS TELEFONO,
                COALESCE(r.ESTADO, '') AS ESTADO_RETIRO,
                CAST(COALESCE(r.OBSERVACION, '') AS VARCHAR(200)) AS OBSERVACION,
                CAST(COALESCE(k.VRBASE, 0) AS CHAR(30)) AS VALOR_TXT
            FROM KARDEX k
            LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
            LEFT JOIN KARDEXSELF ks ON ks.KARDEXID = k.KARDEXID
            LEFT JOIN SN_RETIROS_ESTADO r ON r.KARDEX_ID = k.KARDEXID
            WHERE " . implode(' AND ', $where) . "
            ORDER BY k.FECHA DESC, k.HORA DESC, k.KARDEXID DESC
        ";

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $estadoRet = strtoupper(txtRet($row['ESTADO_RETIRO']));
            $estadoVista = $estadoRet;
            if ($estadoVista === '') {
                $estadoVista = 'DISPONIBLE';
            }

            $data[] = array(
                'kardex_id' => (int)$row['KARDEXID'],
                'remision' => txtRet($row['CODPREFIJO']) . '-' . txtRet($row['NUMERO']),
                'fecha_hora' => fechaHoraRet($row['FECHA'], $row['HORA']),
                'cliente' => txtRet($row['CLIENTE']),
                'direccion' => txtRet($row['DIRECCION']),
                'telefono' => txtRet($row['TELEFONO']),
                'estado_retiro' => $estadoVista,
                'estado_retiro_raw' => $estadoRet,
                'observacion' => txtRet($row['OBSERVACION']),
                'valor_base' => numeroFbRet($row['VALOR_TXT'])
            );
        }

        respRet(true, array('data' => $data));
    }

    if ($action === 'actualizar_estado') {
        $kardexId = isset($_POST['kardex_id']) ? (int)$_POST['kardex_id'] : 0;
        $estado = strtoupper(trim((string)(isset($_POST['estado']) ? $_POST['estado'] : '')));
        $obs = trim((string)(isset($_POST['observacion']) ? $_POST['observacion'] : ''));
        $usuario = strtoupper(trim((string)$_SESSION['user']));

        if ($kardexId <= 0) {
            respRet(false, array('message' => 'KARDEX_ID invalido.'));
        }
        if (!in_array($estado, array('PARA_RETIRAR', 'RETIRADO'), true)) {
            respRet(false, array('message' => 'Estado no valido.'));
        }
        if (strlen($usuario) > 50) {
            $usuario = substr($usuario, 0, 50);
        }
        if (strlen($obs) > 200) {
            $obs = substr($obs, 0, 200);
        }

        $sqlK = "
            SELECT KARDEXID, SN_GUIA_ID
            FROM KARDEX
            WHERE KARDEXID = ?
              AND CODCOMP = 'RS'
              AND FECASENTAD IS NOT NULL
              AND FECANULADO IS NULL
        ";
        $stmtK = $pdo->prepare($sqlK);
        $stmtK->execute(array($kardexId));
        $k = $stmtK->fetch(PDO::FETCH_ASSOC);
        if (!$k) {
            respRet(false, array('message' => 'Remision no valida para Retirados.'));
        }

        if ($k['SN_GUIA_ID'] !== null) {
            respRet(false, array('message' => 'La remision esta asignada a una guia. No se puede gestionar en Retirados.'));
        }

        $stmtEx = $pdo->prepare("SELECT ID, ESTADO FROM SN_RETIROS_ESTADO WHERE KARDEX_ID = ?");
        $stmtEx->execute(array($kardexId));
        $ex = $stmtEx->fetch(PDO::FETCH_ASSOC);
        $estadoActual = $ex ? strtoupper(txtRet($ex['ESTADO'])) : '';

        if ($estadoActual === 'RETIRADO') {
            respRet(false, array('message' => 'La remision ya esta RETIRADO y no se puede modificar.'));
        }

        if ($estado === 'RETIRADO' && $estadoActual !== 'PARA_RETIRAR') {
            respRet(false, array('message' => 'Primero debes marcar la remision como PARA_RETIRAR.'));
        }

        try {
            if ($pdo->inTransaction()) {
                $pdo->commit();
            }
        } catch (Exception $e) {
        }

        $pdo->beginTransaction();
        try {
            if ($ex) {
                $stmtUpd = $pdo->prepare("UPDATE SN_RETIROS_ESTADO SET ESTADO = ?, OBSERVACION = ?, FECHA_ESTADO = CURRENT_TIMESTAMP, USUARIO = ?, FECHA_EDICION = CURRENT_TIMESTAMP WHERE ID = ?");
                $stmtUpd->execute(array($estado, ($obs !== '' ? $obs : null), $usuario, (int)$ex['ID']));
            } else {
                $id = siguienteIdRet($pdo, 'SN_RETIROS_ESTADO');
                $stmtIns = $pdo->prepare("INSERT INTO SN_RETIROS_ESTADO (ID, KARDEX_ID, ESTADO, OBSERVACION, FECHA_ESTADO, USUARIO, FECHA_CREACION, FECHA_EDICION) VALUES (?, ?, ?, ?, CURRENT_TIMESTAMP, ?, CURRENT_TIMESTAMP, NULL)");
                $stmtIns->execute(array($id, $kardexId, $estado, ($obs !== '' ? $obs : null), $usuario));
            }

            $pdo->commit();
            respRet(true, array('message' => 'Estado actualizado.'));
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $e;
        }
    }

    respRet(false, array('message' => 'Accion no soportada.'));
} catch (Exception $e) {
    if (isset($pdo) && $pdo instanceof PDO) {
        try {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
        } catch (Exception $e2) {
        }
    }
    $accion = isset($action) ? $action : 'desconocida';
    respRet(false, array('message' => 'Error (' . $accion . '): ' . $e->getMessage()));
}
