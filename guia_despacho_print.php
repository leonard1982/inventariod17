<?php
require('conecta.php');

$idGuia = isset($_GET['id_guia']) ? (int)$_GET['id_guia'] : 0;

function textoSeguroPrint($valor) {
    $txt = trim((string)$valor);
    if ($txt !== '' && !preg_match('//u', $txt)) {
        $txt = utf8_encode($txt);
    }
    return htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
}

function numeroEnteroPrint($valor) {
    return number_format((float)$valor, 0, ',', '.');
}

function fechaHoraPrint($fecha, $hora) {
    $f = trim((string)$fecha);
    $h = trim((string)$hora);

    if ($f === '' && $h === '') {
        return '';
    }

    if ($f !== '' && strtotime($f) !== false) {
        $f = date('d/m/Y', strtotime($f));
    }

    if ($h === '') {
        return $f;
    }

    return trim($f . ' ' . $h);
}

function fechaGuiaPrint($timestamp) {
    $txt = trim((string)$timestamp);
    if ($txt === '') {
        return '';
    }

    $ts = strtotime($txt);
    if ($ts === false) {
        return $txt;
    }

    return date('d/m/Y H:i', $ts);
}

if ($idGuia <= 0) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>Guia no valida</title></head>
    <body><h3>Guia no valida.</h3></body>
    </html>
    <?php
    exit;
}

$pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlGuia = "
    SELECT
        g.ID,
        g.PREFIJO,
        g.CONSECUTIVO,
        g.FECHA_GUIA,
        g.ESTADO_ACTUAL,
        g.USUARIO_CREA,
        COALESCE(tc.NOMBRE, '') AS CONDUCTOR,
        CAST((SELECT COALESCE(SUM(d.PESO), 0) FROM SN_GUIAS_DETALLE d WHERE d.ID_GUIA = g.ID) AS CHAR(30)) AS TOTAL_PESO,
        CAST((SELECT COALESCE(SUM(d.VALOR_BASE), 0) FROM SN_GUIAS_DETALLE d WHERE d.ID_GUIA = g.ID) AS CHAR(30)) AS TOTAL_VALOR,
        (SELECT COUNT(*) FROM SN_GUIAS_DETALLE d WHERE d.ID_GUIA = g.ID) AS TOTAL_REMISIONES
    FROM SN_GUIAS g
    LEFT JOIN TERCEROS tc ON tc.TERID = g.ID_CONDUCTOR
    WHERE g.ID = ?
";

$stmtGuia = $pdo->prepare($sqlGuia);
$stmtGuia->execute(array($idGuia));
$guia = $stmtGuia->fetch(PDO::FETCH_ASSOC);

if (!$guia) {
    ?>
    <!DOCTYPE html>
    <html lang="es">
    <head><meta charset="UTF-8"><title>Guia no encontrada</title></head>
    <body><h3>No se encontro la guia solicitada.</h3></body>
    </html>
    <?php
    exit;
}

$sqlDet = "
    SELECT
        d.KARDEX_ID,
        k.CODPREFIJO,
        k.NUMERO,
        k.FECHA,
        k.HORA,
        COALESCE(tc.NOMBRE, '') AS CLIENTE,
        COALESCE(tv.NOMBRE, '') AS VENDEDOR,
        CAST(COALESCE(d.PESO, 0) AS CHAR(30)) AS PESO,
        CAST(COALESCE(d.VALOR_BASE, 0) AS CHAR(30)) AS VALOR_BASE
    FROM SN_GUIAS_DETALLE d
    LEFT JOIN KARDEX k ON k.KARDEXID = d.KARDEX_ID
    LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
    LEFT JOIN TERCEROS tv ON tv.TERID = k.VENDEDOR
    WHERE d.ID_GUIA = ?
    ORDER BY d.ID ASC
";

$stmtDet = $pdo->prepare($sqlDet);
$stmtDet->execute(array($idGuia));
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

$numeroGuia = trim((string)$guia['PREFIJO']) . '-' . trim((string)$guia['CONSECUTIVO']);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Impresion Guia <?php echo textoSeguroPrint($numeroGuia); ?></title>
    <style>
        :root {
            --c-pri: #0f3e60;
            --c-sec: #5f7f98;
            --c-bor: #d4e1ec;
            --c-fon: #f4f8fc;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 18px;
            font-family: "Segoe UI", Tahoma, Arial, sans-serif;
            color: #1a2a36;
            background: #fff;
            font-size: 13px;
        }
        .print-wrap {
            max-width: 980px;
            margin: 0 auto;
        }
        .tools {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            margin-bottom: 14px;
        }
        .btn {
            border: 1px solid var(--c-bor);
            background: #fff;
            color: var(--c-pri);
            border-radius: 7px;
            padding: 7px 11px;
            font-weight: 700;
            cursor: pointer;
            font-size: 12px;
        }
        .btn-pri {
            background: var(--c-pri);
            color: #fff;
            border-color: var(--c-pri);
        }
        .head {
            border: 1px solid var(--c-bor);
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 14px;
        }
        .head-top {
            background: linear-gradient(135deg, #0f3e60 0%, #1f5d89 100%);
            color: #fff;
            padding: 12px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .title {
            margin: 0;
            font-size: 17px;
            font-weight: 800;
            letter-spacing: 0.3px;
        }
        .sub {
            margin: 2px 0 0;
            font-size: 12px;
            opacity: 0.92;
        }
        .chip {
            border: 1px solid rgba(255,255,255,0.5);
            border-radius: 999px;
            padding: 4px 10px;
            font-weight: 700;
            font-size: 11px;
            background: rgba(255,255,255,0.15);
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 10px;
            padding: 12px 14px;
            background: var(--c-fon);
            border-top: 1px solid var(--c-bor);
        }
        .info-card {
            background: #fff;
            border: 1px solid var(--c-bor);
            border-radius: 8px;
            padding: 8px 9px;
            min-height: 58px;
        }
        .info-lab {
            display: block;
            color: #637b8f;
            font-size: 10px;
            text-transform: uppercase;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
            font-weight: 700;
        }
        .info-val {
            font-size: 13px;
            font-weight: 700;
            color: #163a56;
            line-height: 1.2;
        }
        .panel {
            border: 1px solid var(--c-bor);
            border-radius: 12px;
            overflow: hidden;
        }
        .panel-head {
            padding: 10px 14px;
            background: #eef4fa;
            border-bottom: 1px solid var(--c-bor);
            font-weight: 800;
            color: #173f5d;
            font-size: 13px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border-bottom: 1px solid #e3ebf3;
            padding: 7px 8px;
            text-align: left;
            vertical-align: middle;
        }
        thead th {
            background: #f7fafd;
            font-weight: 800;
            color: #36566f;
            font-size: 11px;
            text-transform: uppercase;
        }
        .text-end { text-align: right; }
        .tfoot td {
            background: #fbfdff;
            font-weight: 800;
        }
        .empty {
            text-align: center;
            color: #71899d;
            padding: 14px;
        }
        @media (max-width: 900px) {
            .info-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }
        @media print {
            body { padding: 0; }
            .tools { display: none !important; }
            .print-wrap { max-width: none; }
            .head, .panel { break-inside: avoid; }
        }
    </style>
</head>
<body>
<div class="print-wrap">
    <div class="tools">
        <button class="btn" onclick="window.close();">Cerrar</button>
        <button class="btn btn-pri" onclick="window.print();">Imprimir</button>
    </div>

    <section class="head">
        <div class="head-top">
            <div>
                <h1 class="title">GUIA DE DESPACHO</h1>
                <p class="sub">Gestion de Inventarios y Despachos</p>
            </div>
            <span class="chip"><?php echo textoSeguroPrint($numeroGuia); ?></span>
        </div>
        <div class="info-grid">
            <div class="info-card">
                <span class="info-lab">Fecha guia</span>
                <span class="info-val"><?php echo textoSeguroPrint(fechaGuiaPrint($guia['FECHA_GUIA'])); ?></span>
            </div>
            <div class="info-card">
                <span class="info-lab">Estado</span>
                <span class="info-val"><?php echo textoSeguroPrint($guia['ESTADO_ACTUAL']); ?></span>
            </div>
            <div class="info-card">
                <span class="info-lab">Conductor</span>
                <span class="info-val"><?php echo textoSeguroPrint($guia['CONDUCTOR']); ?></span>
            </div>
            <div class="info-card">
                <span class="info-lab">Usuario</span>
                <span class="info-val"><?php echo textoSeguroPrint($guia['USUARIO_CREA']); ?></span>
            </div>
        </div>
    </section>

    <section class="panel">
        <div class="panel-head">Remisiones asociadas</div>
        <table>
            <thead>
                <tr>
                    <th>Remision</th>
                    <th>Fecha/Hora</th>
                    <th>Cliente</th>
                    <th>Vendedor</th>
                    <th class="text-end">Peso</th>
                    <th class="text-end">Valor base</th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($detalles)): ?>
                <?php foreach ($detalles as $item): ?>
                    <tr>
                        <td><?php echo textoSeguroPrint(trim((string)$item['CODPREFIJO']) . '-' . trim((string)$item['NUMERO'])); ?></td>
                        <td><?php echo textoSeguroPrint(fechaHoraPrint($item['FECHA'], $item['HORA'])); ?></td>
                        <td><?php echo textoSeguroPrint($item['CLIENTE']); ?></td>
                        <td><?php echo textoSeguroPrint($item['VENDEDOR']); ?></td>
                        <td class="text-end"><?php echo numeroEnteroPrint($item['PESO']); ?></td>
                        <td class="text-end">$ <?php echo numeroEnteroPrint($item['VALOR_BASE']); ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr>
                    <td colspan="6" class="empty">No hay remisiones asociadas a esta guia.</td>
                </tr>
            <?php endif; ?>
            </tbody>
            <tfoot class="tfoot">
                <tr>
                    <td colspan="4" class="text-end">Totales</td>
                    <td class="text-end"><?php echo numeroEnteroPrint($guia['TOTAL_PESO']); ?></td>
                    <td class="text-end">$ <?php echo numeroEnteroPrint($guia['TOTAL_VALOR']); ?></td>
                </tr>
                <tr>
                    <td colspan="6"><strong>Total remisiones:</strong> <?php echo (int)$guia['TOTAL_REMISIONES']; ?></td>
                </tr>
            </tfoot>
        </table>
    </section>
</div>
</body>
</html>
