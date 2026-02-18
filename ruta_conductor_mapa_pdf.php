<?php
require_once('conecta.php');
require_once('fpdf/fpdf.php');

if (empty($_SESSION['user'])) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Sesion no valida.';
    exit;
}

function rcp_txt($v) {
    $txt = trim((string)$v);
    if ($txt === '') {
        return '';
    }
    if (!preg_match('//u', $txt)) {
        $txt = utf8_encode($txt);
    }
    return utf8_decode($txt);
}

function rcp_num($v) {
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

function rcp_km($lat1, $lng1, $lat2, $lng2) {
    $R = 6371.0;
    $dLat = deg2rad($lat2 - $lat1);
    $dLng = deg2rad($lng2 - $lng1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
        cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
        sin($dLng / 2) * sin($dLng / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $R * $c;
}

function rcp_color($idx) {
    $colors = array(
        array(29, 94, 136),
        array(47, 148, 96),
        array(208, 139, 36),
        array(155, 66, 105),
        array(93, 110, 123),
        array(74, 92, 181),
        array(162, 104, 36)
    );
    return $colors[$idx % count($colors)];
}

function rcp_minimap(FPDF $pdf, $x, $y, $w, $h, $grupos) {
    $pdf->SetDrawColor(120, 145, 165);
    $pdf->Rect($x, $y, $w, $h);

    $all = array();
    foreach ($grupos as $g) {
        foreach ($g['puntos'] as $p) {
            $all[] = $p;
        }
    }
    if (count($all) < 1) {
        $pdf->SetXY($x + 3, $y + ($h / 2) - 2);
        $pdf->SetFont('Arial', 'I', 8);
        $pdf->Cell($w - 6, 4, 'Sin puntos georreferenciados', 0, 0, 'C');
        return;
    }

    $minLat = 999; $maxLat = -999; $minLng = 999; $maxLng = -999;
    foreach ($all as $p) {
        $lat = $p['latitud'];
        $lng = $p['longitud'];
        if ($lat < $minLat) { $minLat = $lat; }
        if ($lat > $maxLat) { $maxLat = $lat; }
        if ($lng < $minLng) { $minLng = $lng; }
        if ($lng > $maxLng) { $maxLng = $lng; }
    }
    if (abs($maxLat - $minLat) < 0.000001) {
        $maxLat += 0.0005;
        $minLat -= 0.0005;
    }
    if (abs($maxLng - $minLng) < 0.000001) {
        $maxLng += 0.0005;
        $minLng -= 0.0005;
    }

    $pad = 3;
    $drawW = $w - ($pad * 2);
    $drawH = $h - ($pad * 2);

    $toX = function($lng) use ($x, $pad, $drawW, $minLng, $maxLng) {
        return $x + $pad + (($lng - $minLng) / ($maxLng - $minLng)) * $drawW;
    };
    $toY = function($lat) use ($y, $pad, $drawH, $minLat, $maxLat) {
        return $y + $pad + (1 - (($lat - $minLat) / ($maxLat - $minLat))) * $drawH;
    };

    $idx = 0;
    foreach ($grupos as $g) {
        $rgb = rcp_color($idx);
        $pdf->SetDrawColor($rgb[0], $rgb[1], $rgb[2]);
        $pdf->SetFillColor($rgb[0], $rgb[1], $rgb[2]);
        $pts = $g['puntos'];
        $prev = null;
        foreach ($pts as $k => $p) {
            $px = $toX($p['longitud']);
            $py = $toY($p['latitud']);
            if ($prev !== null) {
                $pdf->Line($prev[0], $prev[1], $px, $py);
            }
            $pdf->Rect($px - 0.8, $py - 0.8, 1.6, 1.6, 'F');
            if (($k + 1) % 2 === 1) {
                $pdf->SetXY($px + 0.8, $py - 1.4);
                $pdf->SetFont('Arial', '', 6);
                $pdf->Cell(5, 2.6, (string)($k + 1), 0, 0, 'L');
            }
            $prev = array($px, $py);
        }
        $idx++;
    }
}

$fecha = isset($_GET['fecha']) ? trim((string)$_GET['fecha']) : '';
$idConductor = isset($_GET['id_conductor']) ? (int)$_GET['id_conductor'] : 0;
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) {
    $fecha = date('Y-m-d');
}
$desde = $fecha . ' 00:00:00';
$hasta = $fecha . ' 23:59:59';

$pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$chkCol = $pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME = 'KARDEX' AND RDB\$FIELD_NAME = ?");
$chkCol->execute(array('SN_LONGITUD'));
$hasLong = ((int)$chkCol->fetchColumn()) > 0;
$chkCol->execute(array('SN_LATITUD'));
$hasLat = ((int)$chkCol->fetchColumn()) > 0;
if (!$hasLong || !$hasLat) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Faltan columnas KARDEX.SN_LONGITUD y/o KARDEX.SN_LATITUD. Ejecuta 06_alter_kardex_geo.sql.';
    exit;
}

$sql = "
    SELECT
        g.ID_CONDUCTOR,
        g.PREFIJO AS GUIA_PREFIJO,
        g.CONSECUTIVO AS GUIA_CONSECUTIVO,
        d.KARDEX_ID,
        d.ID AS DET_ID,
        k.CODPREFIJO,
        k.NUMERO,
        CAST(COALESCE(k.SN_LONGITUD, 0) AS CHAR(40)) AS LONG_TXT,
        CAST(COALESCE(k.SN_LATITUD, 0) AS CHAR(40)) AS LAT_TXT,
        CAST(COALESCE(tcon.NOMBRE, '') AS VARCHAR(120)) AS CONDUCTOR,
        CAST(COALESCE(tcli.NOMBRE, '') AS VARCHAR(120)) AS CLIENTE,
        CAST(COALESCE(ks.DIRECC1, tcli.DIRECC1, tcli.DIRECC2, '') AS VARCHAR(180)) AS DIRECCION,
        CAST(COALESCE(de.ESTADO_ENTREGA, 'PENDIENTE') AS VARCHAR(30)) AS ESTADO_ENTREGA,
        COALESCE(de.FECHA_ESTADO, g.FECHA_GUIA) AS FECHA_EVENTO
    FROM SN_GUIAS_DETALLE d
    INNER JOIN SN_GUIAS g ON g.ID = d.ID_GUIA
    LEFT JOIN KARDEX k ON k.KARDEXID = d.KARDEX_ID
    LEFT JOIN TERCEROS tcon ON tcon.TERID = g.ID_CONDUCTOR
    LEFT JOIN TERCEROS tcli ON tcli.TERID = k.CLIENTE
    LEFT JOIN KARDEXSELF ks ON ks.KARDEXID = d.KARDEX_ID
    LEFT JOIN SN_GUIAS_DETALLE_ESTADO de ON de.ID_GUIA = d.ID_GUIA AND de.KARDEX_ID = d.KARDEX_ID
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
$sql .= " ORDER BY g.ID_CONDUCTOR, FECHA_EVENTO, d.ID ";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$rows = array();
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $rows[] = array(
        'id_conductor' => ($r['ID_CONDUCTOR'] !== null ? (int)$r['ID_CONDUCTOR'] : 0),
        'conductor' => trim((string)$r['CONDUCTOR']) !== '' ? trim((string)$r['CONDUCTOR']) : 'SIN CONDUCTOR',
        'guia' => trim((string)$r['GUIA_PREFIJO']) . '-' . trim((string)$r['GUIA_CONSECUTIVO']),
        'kardex_id' => (int)$r['KARDEX_ID'],
        'remision' => trim((string)$r['CODPREFIJO']) . '-' . trim((string)$r['NUMERO']),
        'cliente' => trim((string)$r['CLIENTE']),
        'direccion' => trim((string)$r['DIRECCION']),
        'estado' => strtoupper(trim((string)$r['ESTADO_ENTREGA'])),
        'fecha_evento' => trim((string)$r['FECHA_EVENTO']),
        'latitud' => rcp_num($r['LAT_TXT']),
        'longitud' => rcp_num($r['LONG_TXT'])
    );
}

$grupos = array();
foreach ($rows as $r) {
    $key = (string)$r['id_conductor'];
    if (!isset($grupos[$key])) {
        $grupos[$key] = array(
            'conductor' => $r['conductor'],
            'puntos' => array(),
            'km' => 0.0
        );
    }
    $grupos[$key]['puntos'][] = $r;
}

foreach ($grupos as $k => $g) {
    $km = 0.0;
    $pts = $g['puntos'];
    for ($i = 1; $i < count($pts); $i++) {
        $km += rcp_km($pts[$i - 1]['latitud'], $pts[$i - 1]['longitud'], $pts[$i]['latitud'], $pts[$i]['longitud']);
    }
    $grupos[$k]['km'] = $km;
}

$pdf = new FPDF('L', 'mm', 'A4');
$pdf->SetMargins(8, 8, 8);
$pdf->AddPage();

$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 7, rcp_txt('INFORME RUTA CONDUCTOR - MAPA Y DETALLE'), 0, 1, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5.5, rcp_txt('Fecha: ' . $fecha . ' | Filtro conductor: ' . ($idConductor > 0 ? 'SI' : 'TODOS')), 0, 1, 'L');
$pdf->Cell(0, 5.5, rcp_txt('Puntos: ' . count($rows) . ' | Conductores: ' . count($grupos)), 0, 1, 'L');
$pdf->Ln(1);

rcp_minimap($pdf, 8, 27, 180, 70, $grupos);

$pdf->SetXY(192, 27);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(95, 5.5, 'RESUMEN CONDUCTOR', 0, 1, 'L');
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(236, 244, 251);
$pdf->SetX(192);
$pdf->Cell(53, 6, 'Conductor', 1, 0, 'L', true);
$pdf->Cell(20, 6, 'Puntos', 1, 0, 'C', true);
$pdf->Cell(22, 6, 'Km aprox', 1, 1, 'R', true);
$pdf->SetFont('Arial', '', 8);
$idx = 0;
foreach ($grupos as $g) {
    if ($idx >= 10) {
        break;
    }
    $pdf->SetX(192);
    $pdf->Cell(53, 5.5, rcp_txt($g['conductor']), 1, 0, 'L');
    $pdf->Cell(20, 5.5, number_format(count($g['puntos']), 0, ',', '.'), 1, 0, 'C');
    $pdf->Cell(22, 5.5, number_format($g['km'], 1, ',', '.'), 1, 1, 'R');
    $idx++;
}

$pdf->SetXY(8, 101);
$pdf->SetFont('Arial', 'B', 8);
$pdf->SetFillColor(236, 244, 251);
$pdf->Cell(7, 6, '#', 1, 0, 'C', true);
$pdf->Cell(45, 6, 'Conductor', 1, 0, 'L', true);
$pdf->Cell(20, 6, 'Guia', 1, 0, 'L', true);
$pdf->Cell(22, 6, 'Remision', 1, 0, 'L', true);
$pdf->Cell(50, 6, 'Cliente', 1, 0, 'L', true);
$pdf->Cell(62, 6, 'Direccion', 1, 0, 'L', true);
$pdf->Cell(18, 6, 'Estado', 1, 0, 'C', true);
$pdf->Cell(30, 6, 'Fecha evento', 1, 0, 'L', true);
$pdf->Cell(26, 6, 'Ubicacion', 1, 1, 'L', true);

$pdf->SetFont('Arial', '', 7.4);
$n = 1;
foreach ($rows as $r) {
    if ($pdf->GetY() > 195) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 8);
        $pdf->SetFillColor(236, 244, 251);
        $pdf->Cell(7, 6, '#', 1, 0, 'C', true);
        $pdf->Cell(45, 6, 'Conductor', 1, 0, 'L', true);
        $pdf->Cell(20, 6, 'Guia', 1, 0, 'L', true);
        $pdf->Cell(22, 6, 'Remision', 1, 0, 'L', true);
        $pdf->Cell(50, 6, 'Cliente', 1, 0, 'L', true);
        $pdf->Cell(62, 6, 'Direccion', 1, 0, 'L', true);
        $pdf->Cell(18, 6, 'Estado', 1, 0, 'C', true);
        $pdf->Cell(30, 6, 'Fecha evento', 1, 0, 'L', true);
        $pdf->Cell(26, 6, 'Ubicacion', 1, 1, 'L', true);
        $pdf->SetFont('Arial', '', 7.4);
    }
    $ubi = number_format($r['longitud'], 6, '.', '') . ';' . number_format($r['latitud'], 6, '.', '');
    $pdf->Cell(7, 5.6, (string)$n, 1, 0, 'C');
    $pdf->Cell(45, 5.6, rcp_txt($r['conductor']), 1, 0, 'L');
    $pdf->Cell(20, 5.6, rcp_txt($r['guia']), 1, 0, 'L');
    $pdf->Cell(22, 5.6, rcp_txt($r['remision']), 1, 0, 'L');
    $pdf->Cell(50, 5.6, rcp_txt($r['cliente']), 1, 0, 'L');
    $pdf->Cell(62, 5.6, rcp_txt($r['direccion']), 1, 0, 'L');
    $pdf->Cell(18, 5.6, rcp_txt($r['estado']), 1, 0, 'C');
    $pdf->Cell(30, 5.6, rcp_txt($r['fecha_evento']), 1, 0, 'L');
    $pdf->Cell(26, 5.6, rcp_txt($ubi), 1, 1, 'L');
    $n++;
}

$nombre = 'ruta_conductor_' . str_replace('-', '', $fecha) . '.pdf';
$pdf->Output('I', $nombre);
exit;
