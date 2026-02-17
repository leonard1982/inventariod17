<?php
ob_start();
require('conecta.php');

header('Content-Type: application/json; charset=UTF-8');

function dashboardInicioResponder($ok, $payload = array()) {
    $salida = '';
    if (ob_get_level() > 0) {
        $salida = trim(ob_get_contents());
        ob_clean();
    }

    if ($salida !== '') {
        $payload['debug_output'] = $salida;
    }

    echo json_encode(
        array_merge(array('ok' => $ok ? true : false), $payload),
        JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
    );
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
        echo json_encode(
            array(
                'ok' => false,
                'message' => 'Error fatal PHP: ' . $error['message'],
                'file' => isset($error['file']) ? $error['file'] : '',
                'line' => isset($error['line']) ? (int)$error['line'] : 0
            ),
            JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE
        );
    }
});

if (empty($_SESSION['user'])) {
    dashboardInicioResponder(false, array('message' => 'Sesion no valida.'));
}

function dashboardPdoActual() {
    static $pdo = null;
    global $contenidoBdActual;

    if ($pdo instanceof PDO) {
        return $pdo;
    }

    $pdo = new PDO(
        'firebird:dbname=127.0.0.1:' . $contenidoBdActual,
        'SYSDBA',
        'masterkey'
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    return $pdo;
}

function dashboardTablaExiste(PDO $pdo, $tabla) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?");
    $stmt->execute(array(strtoupper($tabla)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function dashboardColumnaExiste(PDO $pdo, $tabla, $columna) {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = ? AND RDB\$FIELD_NAME = ?");
    $stmt->execute(array(strtoupper($tabla), strtoupper($columna)));
    return ((int)$stmt->fetchColumn()) > 0;
}

function dashboardInt(PDO $pdo, $sql, $params = array()) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $val = $stmt->fetchColumn();
    return $val === false ? 0 : (int)$val;
}

function dashboardRows(PDO $pdo, $sql, $params = array()) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function dashboardTexto($v) {
    return trim((string)$v);
}

function dashboardNormalizarDoc($txt) {
    $v = strtoupper(trim((string)$txt));
    $v = str_replace(array(' ', '.', '-', '/', ','), '', $v);
    return $v;
}

function dashboardExtraerNitTexto($txt) {
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

function dashboardValorVarios(PDO $pdo, $variab) {
    if (!dashboardTablaExiste($pdo, 'VARIOS')) {
        return '';
    }
    $stmt = $pdo->prepare('SELECT CONTENIDO FROM VARIOS WHERE VARIAB = ?');
    $stmt->execute(array((string)$variab));
    $v = $stmt->fetchColumn();
    return $v === false ? '' : trim((string)$v);
}

function dashboardBuscarTeridConductorPorDoc(PDO $pdo, $documento) {
    if (!dashboardTablaExiste($pdo, 'TERCEROS')) {
        return 0;
    }

    $doc = trim((string)$documento);
    if ($doc === '') {
        return 0;
    }

    $docCanon = dashboardNormalizarDoc($doc);
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

function dashboardContextoConductor(PDO $pdo, $usuario, $esAdmin) {
    $ctx = array(
        'es_admin' => $esAdmin ? true : false,
        'usuario' => strtoupper(trim((string)$usuario)),
        'terid' => 0,
        'origen' => '',
        'doc_fuente' => ''
    );

    if ($ctx['es_admin'] || $ctx['usuario'] === '') {
        return $ctx;
    }

    if (dashboardTablaExiste($pdo, 'TERCEROS') && dashboardTablaExiste($pdo, 'TERCEROSSELF')) {
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
    }

    $docGvende = dashboardValorVarios($pdo, 'GVENDE' . $ctx['usuario']);
    if ($docGvende !== '') {
        if (preg_match('/^\\d+$/', trim($docGvende))) {
            if (dashboardTablaExiste($pdo, 'TERCEROS')) {
                $stmtTer = $pdo->prepare("
                    SELECT FIRST 1 TERID
                    FROM TERCEROS
                    WHERE TERID = ?
                      AND COALESCE(CONDUCTOR, 'N') = 'S'
                ");
                $stmtTer->execute(array((int)$docGvende));
                $teridNum = $stmtTer->fetchColumn();
                if ($teridNum) {
                    $ctx['terid'] = (int)$teridNum;
                    $ctx['origen'] = 'VARIOS.GVENDE<USUARIO> (TERID)';
                    $ctx['doc_fuente'] = trim($docGvende);
                    return $ctx;
                }
            }
        }

        $teridDoc = dashboardBuscarTeridConductorPorDoc($pdo, $docGvende);
        if ($teridDoc > 0) {
            $ctx['terid'] = $teridDoc;
            $ctx['origen'] = 'VARIOS.GVENDE<USUARIO> (NIT/NITTRI)';
            $ctx['doc_fuente'] = trim($docGvende);
            return $ctx;
        }
    }

    $docVar = dashboardValorVarios($pdo, 'GFVP_ENCAB4' . $ctx['usuario']);
    $docExtraido = dashboardExtraerNitTexto($docVar);
    if ($docExtraido !== '') {
        $teridDoc2 = dashboardBuscarTeridConductorPorDoc($pdo, $docExtraido);
        if ($teridDoc2 > 0) {
            $ctx['terid'] = $teridDoc2;
            $ctx['origen'] = 'VARIOS.GFVP_ENCAB4';
            $ctx['doc_fuente'] = $docExtraido;
            return $ctx;
        }
    }

    $teridDirecto = dashboardBuscarTeridConductorPorDoc($pdo, $ctx['usuario']);
    if ($teridDirecto > 0) {
        $ctx['terid'] = $teridDirecto;
        $ctx['origen'] = 'USUARIO COMO NIT/NITTRI';
        $ctx['doc_fuente'] = $ctx['usuario'];
    }

    return $ctx;
}

function dashboardBuildPcData(PDO $pdo, $anio, $mes) {
    $kpis = array(
        'guias_hoy' => 0,
        'guias_mes' => 0,
        'remisiones_mes' => 0,
        'conductores_activos' => 0,
        'guias_en_ruta' => 0,
        'pendientes_remision' => 0,
        'cumplimiento_pct' => 0,
        'promedio_remision_por_guia' => 0
    );

    $series = array(
        'guias_dia' => array('labels' => array(), 'values' => array()),
        'estados_guia' => array('labels' => array(), 'values' => array()),
        'top_conductores' => array('labels' => array(), 'values' => array())
    );

    if (!dashboardTablaExiste($pdo, 'SN_GUIAS')) {
        return array(
            'kpis' => $kpis,
            'series' => $series,
            'message' => 'No existe SN_GUIAS. Ejecuta los scripts de guias para ver indicadores.'
        );
    }

    $kpis['guias_hoy'] = dashboardInt(
        $pdo,
        "SELECT COUNT(*) FROM SN_GUIAS g
         WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)
           AND EXTRACT(MONTH FROM g.FECHA_GUIA) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
           AND EXTRACT(DAY FROM g.FECHA_GUIA) = EXTRACT(DAY FROM CURRENT_TIMESTAMP)"
    );

    $kpis['guias_mes'] = dashboardInt(
        $pdo,
        "SELECT COUNT(*) FROM SN_GUIAS g WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ?",
        array((int)$anio, (int)$mes)
    );

    $kpis['conductores_activos'] = dashboardInt(
        $pdo,
        "SELECT COUNT(DISTINCT g.ID_CONDUCTOR) FROM SN_GUIAS g WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? AND g.ID_CONDUCTOR IS NOT NULL",
        array((int)$anio, (int)$mes)
    );

    $kpis['guias_en_ruta'] = dashboardInt(
        $pdo,
        "SELECT COUNT(*) FROM SN_GUIAS g WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? AND UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL, ''))) = 'EN_RUTA'",
        array((int)$anio, (int)$mes)
    );

    $hayDetalle = dashboardTablaExiste($pdo, 'SN_GUIAS_DETALLE');
    $hayEstadoDetalle = dashboardTablaExiste($pdo, 'SN_GUIAS_DETALLE_ESTADO');

    if ($hayDetalle) {
        $kpis['remisiones_mes'] = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ?",
            array((int)$anio, (int)$mes)
        );

        if ($hayEstadoDetalle) {
            $kpis['pendientes_remision'] = dashboardInt(
                $pdo,
                "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') <> 'ENTREGADO'",
                array((int)$anio, (int)$mes)
            );

            $entregadasMes = dashboardInt(
                $pdo,
                "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') = 'ENTREGADO'",
                array((int)$anio, (int)$mes)
            );

            if ($kpis['remisiones_mes'] > 0) {
                $kpis['cumplimiento_pct'] = round(($entregadasMes * 100) / $kpis['remisiones_mes'], 1);
            }
        } else {
            $kpis['pendientes_remision'] = $kpis['remisiones_mes'];
        }
    }

    if ($kpis['guias_mes'] > 0) {
        $kpis['promedio_remision_por_guia'] = round($kpis['remisiones_mes'] / $kpis['guias_mes'], 1);
    }

    $diasMes = (int)cal_days_in_month(CAL_GREGORIAN, (int)$mes, (int)$anio);
    $labelsDias = array();
    $valuesDias = array();
    $mapaDias = array();

    for ($i = 1; $i <= $diasMes; $i++) {
        $labelsDias[] = str_pad((string)$i, 2, '0', STR_PAD_LEFT);
        $valuesDias[] = 0;
        $mapaDias[$i] = $i - 1;
    }

    $rowsDias = dashboardRows(
        $pdo,
        "SELECT EXTRACT(DAY FROM g.FECHA_GUIA) AS DIA, COUNT(*) AS TOTAL FROM SN_GUIAS g WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? GROUP BY 1 ORDER BY 1",
        array((int)$anio, (int)$mes)
    );

    foreach ($rowsDias as $rDia) {
        $dia = (int)$rDia['DIA'];
        if (isset($mapaDias[$dia])) {
            $valuesDias[$mapaDias[$dia]] = (int)$rDia['TOTAL'];
        }
    }

    $series['guias_dia'] = array('labels' => $labelsDias, 'values' => $valuesDias);

    $rowsEstados = dashboardRows(
        $pdo,
        "SELECT COALESCE(NULLIF(TRIM(g.ESTADO_ACTUAL), ''), 'SIN_ESTADO') AS ESTADO, COUNT(*) AS TOTAL FROM SN_GUIAS g WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? GROUP BY 1 ORDER BY 2 DESC",
        array((int)$anio, (int)$mes)
    );

    if (!empty($rowsEstados)) {
        $labels = array();
        $values = array();
        foreach ($rowsEstados as $row) {
            $labels[] = dashboardTexto($row['ESTADO']);
            $values[] = (int)$row['TOTAL'];
        }
        $series['estados_guia'] = array('labels' => $labels, 'values' => $values);
    } else {
        $series['estados_guia'] = array('labels' => array('SIN DATOS'), 'values' => array(0));
    }

    if (dashboardTablaExiste($pdo, 'TERCEROS')) {
        $rowsTopConductor = dashboardRows(
            $pdo,
            "SELECT FIRST 8 COALESCE(NULLIF(TRIM(t.NOMBRE), ''), 'SIN_CONDUCTOR') AS NOMBRE, COUNT(*) AS TOTAL FROM SN_GUIAS g LEFT JOIN TERCEROS t ON t.TERID = g.ID_CONDUCTOR WHERE EXTRACT(YEAR FROM g.FECHA_GUIA) = ? AND EXTRACT(MONTH FROM g.FECHA_GUIA) = ? GROUP BY 1 ORDER BY 2 DESC",
            array((int)$anio, (int)$mes)
        );

        if (!empty($rowsTopConductor)) {
            $labels = array();
            $values = array();
            foreach ($rowsTopConductor as $row) {
                $labels[] = dashboardTexto($row['NOMBRE']);
                $values[] = (int)$row['TOTAL'];
            }
            $series['top_conductores'] = array('labels' => $labels, 'values' => $values);
        } else {
            $series['top_conductores'] = array('labels' => array('SIN DATOS'), 'values' => array(0));
        }
    } else {
        $series['top_conductores'] = array('labels' => array('SIN TABLA TERCEROS'), 'values' => array(0));
    }

    return array(
        'kpis' => $kpis,
        'series' => $series,
        'message' => ''
    );
}

function dashboardBuildMobileConductorData(PDO $pdo, $usuario, $esAdmin) {
    $resp = array(
        'habilitado' => false,
        'terid' => 0,
        'conductor' => '',
        'mensaje' => '',
        'kpis' => array(
            'guias_pendientes' => 0,
            'remisiones_pendientes' => 0,
            'entregadas_hoy' => 0,
            'incidencias_hoy' => 0
        ),
        'series' => array(
            'estados' => array('labels' => array('SIN DATOS'), 'values' => array(0)),
            'guias' => array('labels' => array('SIN DATOS'), 'values' => array(0))
        )
    );

    if (!dashboardTablaExiste($pdo, 'SN_GUIAS') || !dashboardTablaExiste($pdo, 'SN_GUIAS_DETALLE')) {
        return $resp;
    }

    // Habilitamos el bloque en Inicio movil para todo usuario autenticado.
    $resp['habilitado'] = true;
    $resp['conductor'] = strtoupper(trim((string)$usuario));

    $ctx = dashboardContextoConductor($pdo, $usuario, $esAdmin);
    if ($ctx['es_admin']) {
        $resp['conductor'] = 'ADMIN';
        $resp['mensaje'] = 'Vista general habilitada para usuario administrador.';
        return $resp;
    }

    if ((int)$ctx['terid'] <= 0) {
        $resp['mensaje'] = 'No se encontro relacion usuario-conductor; muestra en cero hasta configurar VARIOS/TERCEROSSELF.';
        return $resp;
    }

    $terid = (int)$ctx['terid'];
    $resp['terid'] = $terid;
    if (trim((string)$ctx['origen']) !== '') {
        $resp['mensaje'] = 'Vinculado por: ' . trim((string)$ctx['origen']);
    }

    if (dashboardTablaExiste($pdo, 'TERCEROS')) {
        $stmtNom = $pdo->prepare("SELECT FIRST 1 NOMBRE FROM TERCEROS WHERE TERID = ?");
        $stmtNom->execute(array($terid));
        $resp['conductor'] = dashboardTexto($stmtNom->fetchColumn());
    }

    $resp['kpis']['guias_pendientes'] = dashboardInt(
        $pdo,
        "SELECT COUNT(*) FROM SN_GUIAS g WHERE g.ID_CONDUCTOR = ? AND UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL, ''))) <> 'FINALIZADO'",
        array($terid)
    );

    $hayEstadoDetalle = dashboardTablaExiste($pdo, 'SN_GUIAS_DETALLE_ESTADO');
    $cntPend = 0;
    $cntEnt = 0;
    $cntNoEnt = 0;
    $cntPar = 0;

    if ($hayEstadoDetalle) {
        $resp['kpis']['remisiones_pendientes'] = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE g.ID_CONDUCTOR = ? AND UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL, ''))) <> 'FINALIZADO' AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') <> 'ENTREGADO'",
            array($terid)
        );

        $cntPend = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE g.ID_CONDUCTOR = ? AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') = 'PENDIENTE'",
            array($terid)
        );
        $cntEnt = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE g.ID_CONDUCTOR = ? AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') = 'ENTREGADO'",
            array($terid)
        );
        $cntNoEnt = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE g.ID_CONDUCTOR = ? AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') = 'NO_ENTREGADO'",
            array($terid)
        );
        $cntPar = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID WHERE g.ID_CONDUCTOR = ? AND COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') = 'ENTREGA_PARCIAL'",
            array($terid)
        );

        $resp['kpis']['entregadas_hoy'] = dashboardInt(
            $pdo,
            "SELECT COUNT(*)
             FROM SN_GUIAS g
             INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID
             INNER JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID
             WHERE g.ID_CONDUCTOR = ?
               AND COALESCE(e.ESTADO_ENTREGA, '') = 'ENTREGADO'
               AND EXTRACT(YEAR FROM e.FECHA_ESTADO) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)
               AND EXTRACT(MONTH FROM e.FECHA_ESTADO) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
               AND EXTRACT(DAY FROM e.FECHA_ESTADO) = EXTRACT(DAY FROM CURRENT_TIMESTAMP)",
            array($terid)
        );

        $resp['kpis']['incidencias_hoy'] = dashboardInt(
            $pdo,
            "SELECT COUNT(*)
             FROM SN_GUIAS g
             INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID
             INNER JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID
             WHERE g.ID_CONDUCTOR = ?
               AND COALESCE(e.ESTADO_ENTREGA, '') IN ('NO_ENTREGADO', 'ENTREGA_PARCIAL')
               AND EXTRACT(YEAR FROM e.FECHA_ESTADO) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)
               AND EXTRACT(MONTH FROM e.FECHA_ESTADO) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
               AND EXTRACT(DAY FROM e.FECHA_ESTADO) = EXTRACT(DAY FROM CURRENT_TIMESTAMP)",
            array($terid)
        );
    } else {
        $resp['kpis']['remisiones_pendientes'] = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID WHERE g.ID_CONDUCTOR = ? AND UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL, ''))) <> 'FINALIZADO'",
            array($terid)
        );
        $cntPend = dashboardInt(
            $pdo,
            "SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID WHERE g.ID_CONDUCTOR = ?",
            array($terid)
        );
    }

    $resp['series']['estados'] = array(
        'labels' => array('PENDIENTE', 'ENTREGADO', 'NO_ENTREGADO', 'ENTREGA_PARCIAL'),
        'values' => array((int)$cntPend, (int)$cntEnt, (int)$cntNoEnt, (int)$cntPar)
    );

    $rowsGuias = dashboardRows(
        $pdo,
        "SELECT FIRST 8 (TRIM(g.PREFIJO) || '-' || CAST(g.CONSECUTIVO AS VARCHAR(15))) AS NUM_GUIA, COUNT(*) AS TOTAL
         FROM SN_GUIAS g
         INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID
         WHERE g.ID_CONDUCTOR = ?
           AND EXTRACT(YEAR FROM g.FECHA_GUIA) = EXTRACT(YEAR FROM CURRENT_TIMESTAMP)
           AND EXTRACT(MONTH FROM g.FECHA_GUIA) = EXTRACT(MONTH FROM CURRENT_TIMESTAMP)
         GROUP BY 1
         ORDER BY 2 DESC",
        array($terid)
    );

    if (empty($rowsGuias)) {
        $rowsGuias = dashboardRows(
            $pdo,
            "SELECT FIRST 8 (TRIM(g.PREFIJO) || '-' || CAST(g.CONSECUTIVO AS VARCHAR(15))) AS NUM_GUIA, COUNT(*) AS TOTAL
             FROM SN_GUIAS g
             INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA = g.ID
             WHERE g.ID_CONDUCTOR = ?
             GROUP BY 1
             ORDER BY 2 DESC",
            array($terid)
        );
    }

    if (!empty($rowsGuias)) {
        $labels = array();
        $values = array();
        foreach ($rowsGuias as $row) {
            $labels[] = dashboardTexto($row['NUM_GUIA']);
            $values[] = (int)$row['TOTAL'];
        }
        $resp['series']['guias'] = array('labels' => $labels, 'values' => $values);
    }

    return $resp;
}

try {
    $usuarioSesion = strtoupper(trim((string)$_SESSION['user']));
    $esAdmin = function_exists('esUsuarioAdministradorMenu') ? esUsuarioAdministradorMenu($usuarioSesion) : false;

    $pdo = dashboardPdoActual();
    $anio = (int)date('Y');
    $mes = (int)date('n');

    $pc = dashboardBuildPcData($pdo, $anio, $mes);
    $mobileConductor = dashboardBuildMobileConductorData($pdo, $usuarioSesion, $esAdmin);

    dashboardInicioResponder(true, array(
        'data' => array(
            'server_time' => date('Y-m-d H:i:s'),
            'pc' => $pc,
            'mobile_conductor' => $mobileConductor
        )
    ));
} catch (Exception $e) {
    dashboardInicioResponder(false, array('message' => 'Error SQL dashboard_inicio: ' . $e->getMessage()));
}
