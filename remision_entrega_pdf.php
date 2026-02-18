<?php
require_once('conecta.php');
require_once('fpdf/fpdf.php');

$kardexId = isset($_GET['kardex_id']) ? (int)$_GET['kardex_id'] : 0;
$token = isset($_GET['t']) ? strtoupper(trim((string)$_GET['t'])) : '';

function tokenRemisionEntrega($kardexId) {
    return strtoupper(substr(sha1('D17_REMISION_' . (int)$kardexId . '_2026'), 0, 12));
}

function txtPdf($v) {
    $txt = trim((string)$v);
    if ($txt === '') {
        return '';
    }
    if (!preg_match('//u', $txt)) {
        $txt = utf8_encode($txt);
    }
    return utf8_decode($txt);
}

function numeroFb($valor) {
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

function parsePodMensaje($mensaje) {
    $meta = array();
    $txt = trim((string)$mensaje);
    if ($txt === '') {
        return $meta;
    }
    $partes = explode('|', $txt);
    foreach ($partes as $item) {
        $item = trim((string)$item);
        if ($item === '' || strpos($item, '=') === false) {
            continue;
        }
        list($k, $v) = explode('=', $item, 2);
        $k = strtoupper(trim((string)$k));
        $v = trim((string)$v);
        if ($k !== '') {
            $meta[$k] = $v;
        }
    }
    return $meta;
}

function rutaPodAbsoluta($relativa) {
    $rel = trim((string)$relativa);
    if ($rel === '') {
        return '';
    }
    $rel = str_replace('\\', '/', $rel);
    if (strpos($rel, 'uploads/pod/') !== 0) {
        return '';
    }
    $base = realpath(__DIR__);
    if ($base === false) {
        return '';
    }
    $abs = realpath(__DIR__ . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel));
    if ($abs === false || !is_file($abs)) {
        return '';
    }
    $baseNorm = rtrim(str_replace('\\', '/', $base), '/') . '/';
    $absNorm = str_replace('\\', '/', $abs);
    if (strpos($absNorm, $baseNorm) !== 0) {
        return '';
    }
    return $abs;
}

function imagenCompatibleFpdf($abs) {
    if ($abs === '' || !is_file($abs)) {
        return false;
    }
    $info = @getimagesize($abs);
    if (!$info || !isset($info[2])) {
        return false;
    }
    return ((int)$info[2] === IMAGETYPE_JPEG || (int)$info[2] === IMAGETYPE_PNG);
}

if ($kardexId <= 0 || $token === '' || $token !== tokenRemisionEntrega($kardexId)) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Acceso no valido al documento.';
    exit;
}

$pdo = new PDO('firebird:dbname=127.0.0.1:' . $contenidoBdActual, 'SYSDBA', 'masterkey');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$sqlEnc = "
    SELECT FIRST 1
        k.KARDEXID,
        k.CODPREFIJO,
        k.NUMERO,
        k.FECHA,
        k.HORA,
        COALESCE(tc.NOMBRE, '') AS CLIENTE,
        COALESCE(ks.DIRECC1, tc.DIRECC1, tc.DIRECC2, '') AS DIRECCION,
        COALESCE(ks.TELEF1, tc.TELEF1, tc.TELEF2, '') AS TELEFONO,
        COALESCE(tv.NOMBRE, '') AS VENDEDOR,
        CAST(COALESCE(k.VRBASE, 0) AS CHAR(30)) AS VALOR_TXT,
        CAST(COALESCE(d.PESO, 0) AS CHAR(30)) AS PESO_GUIA_TXT,
        COALESCE(e.ESTADO_ENTREGA, 'PENDIENTE') AS ESTADO_ENTREGA,
        COALESCE(g.PREFIJO, '') AS GUIA_PREFIJO,
        CAST(COALESCE(g.CONSECUTIVO, 0) AS CHAR(20)) AS GUIA_CONSECUTIVO
    FROM KARDEX k
    LEFT JOIN SN_GUIAS_DETALLE d ON d.KARDEX_ID = k.KARDEXID
    LEFT JOIN SN_GUIAS g ON g.ID = d.ID_GUIA
    LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA = d.ID_GUIA AND e.KARDEX_ID = d.KARDEX_ID
    LEFT JOIN TERCEROS tc ON tc.TERID = k.CLIENTE
    LEFT JOIN TERCEROS tv ON tv.TERID = k.VENDEDOR
    LEFT JOIN KARDEXSELF ks ON ks.KARDEXID = k.KARDEXID
    WHERE k.KARDEXID = ?
      AND k.CODCOMP = 'RS'
    ORDER BY d.ID DESC
";
$stmtEnc = $pdo->prepare($sqlEnc);
$stmtEnc->execute(array($kardexId));
$enc = $stmtEnc->fetch(PDO::FETCH_ASSOC);

if (!$enc) {
    header('Content-Type: text/plain; charset=UTF-8');
    echo 'Remision no encontrada.';
    exit;
}

$sqlDet = "
    SELECT
        d.MATID,
        COALESCE(m.CODIGO, '') AS CODIGO,
        COALESCE(m.DESCRIP, '') AS DESCRIPCION,
        CAST(COALESCE(d.CANMAT, d.CANLISTA, 0) AS CHAR(30)) AS CANT_TXT,
        CAST(COALESCE(m.PESO, 0) AS CHAR(30)) AS PESO_UNIT_TXT
    FROM DEKARDEX d
    LEFT JOIN MATERIAL m ON m.MATID = d.MATID
    WHERE d.KARDEXID = ?
    ORDER BY d.MATID
";
$stmtDet = $pdo->prepare($sqlDet);
$stmtDet->execute(array($kardexId));
$detalles = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

$sqlPod = "
    SELECT FIRST 1
        c.MENSAJE,
        c.FECHA_MENSAJE,
        c.USUARIO
    FROM SN_GUIAS_DETALLE_CHAT c
    WHERE c.KARDEX_ID = ?
      AND UPPER(TRIM(COALESCE(c.TIPO, ''))) = 'POD'
    ORDER BY c.FECHA_MENSAJE DESC, c.ID DESC
";
$stmtPod = $pdo->prepare($sqlPod);
$stmtPod->execute(array($kardexId));
$podRow = $stmtPod->fetch(PDO::FETCH_ASSOC);
$podMeta = $podRow ? parsePodMensaje($podRow['MENSAJE']) : array();

$estadoEntrega = strtoupper(trim((string)$enc['ESTADO_ENTREGA']));
if ($estadoEntrega === '') {
    $estadoEntrega = 'PENDIENTE';
}

$pdf = new FPDF('P', 'mm', 'A4');
$pdf->SetMargins(10, 8, 10);
$pdf->AddPage();

$numRemision = trim((string)$enc['CODPREFIJO']) . '-' . trim((string)$enc['NUMERO']);
$numGuia = '';
if (trim((string)$enc['GUIA_PREFIJO']) !== '' && trim((string)$enc['GUIA_CONSECUTIVO']) !== '') {
    $numGuia = trim((string)$enc['GUIA_PREFIJO']) . '-' . trim((string)$enc['GUIA_CONSECUTIVO']);
}

$pdf->SetFont('Arial', 'B', 13);
$pdf->Cell(0, 8, 'REMISION DE ENTREGA / POD', 0, 1, 'L');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 6, txtPdf('Documento: ' . $numRemision), 0, 1, 'L');
$pdf->Cell(0, 6, txtPdf('Fecha: ' . trim((string)$enc['FECHA']) . ' ' . trim((string)$enc['HORA'])), 0, 1, 'L');
$pdf->Cell(0, 6, txtPdf('Estado remision: ' . $estadoEntrega), 0, 1, 'L');
if ($numGuia !== '') {
    $pdf->Cell(0, 6, txtPdf('Guia: ' . $numGuia), 0, 1, 'L');
}
$pdf->Ln(1);

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(28, 6, 'Cliente:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, txtPdf($enc['CLIENTE']), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(28, 6, 'Direccion:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, txtPdf($enc['DIRECCION']), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(28, 6, 'Telefono:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, txtPdf($enc['TELEFONO']), 0, 1, 'L');

$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(28, 6, 'Vendedor:', 0, 0, 'L');
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 6, txtPdf($enc['VENDEDOR']), 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('Arial', 'B', 9);
$pdf->SetFillColor(235, 242, 248);
$pdf->Cell(28, 7, 'Codigo', 1, 0, 'C', true);
$pdf->Cell(96, 7, 'Descripcion', 1, 0, 'C', true);
$pdf->Cell(24, 7, 'Cantidad', 1, 0, 'C', true);
$pdf->Cell(22, 7, 'Peso', 1, 0, 'C', true);
$pdf->Cell(20, 7, 'Subtotal', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 8.5);
$totalPeso = 0.0;

foreach ($detalles as $item) {
    $cant = numeroFb($item['CANT_TXT']);
    $pesoU = numeroFb($item['PESO_UNIT_TXT']);
    $pesoSub = $cant * $pesoU;
    $totalPeso += $pesoSub;

    $pdf->Cell(28, 6, txtPdf($item['CODIGO']), 1, 0, 'L');
    $pdf->Cell(96, 6, txtPdf($item['DESCRIPCION']), 1, 0, 'L');
    $pdf->Cell(24, 6, number_format($cant, 0, ',', '.'), 1, 0, 'R');
    $pdf->Cell(22, 6, number_format($pesoU, 3, ',', '.'), 1, 0, 'R');
    $pdf->Cell(20, 6, number_format($pesoSub, 2, ',', '.'), 1, 1, 'R');

    if ($pdf->GetY() > 260) {
        $pdf->AddPage();
        $pdf->SetFont('Arial', 'B', 9);
        $pdf->SetFillColor(235, 242, 248);
        $pdf->Cell(28, 7, 'Codigo', 1, 0, 'C', true);
        $pdf->Cell(96, 7, 'Descripcion', 1, 0, 'C', true);
        $pdf->Cell(24, 7, 'Cantidad', 1, 0, 'C', true);
        $pdf->Cell(22, 7, 'Peso', 1, 0, 'C', true);
        $pdf->Cell(20, 7, 'Subtotal', 1, 1, 'C', true);
        $pdf->SetFont('Arial', '', 8.5);
    }
}

if ($totalPeso <= 0.0001) {
    $pesoGuia = numeroFb($enc['PESO_GUIA_TXT']);
    if ($pesoGuia > 0) {
        $totalPeso = $pesoGuia;
    }
}

$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(170, 7, 'Total peso', 1, 0, 'R');
$pdf->Cell(20, 7, number_format($totalPeso, 2, ',', '.'), 1, 1, 'R');

$pdf->Ln(2);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'EVIDENCIA POD', 0, 1, 'L');
$pdf->SetFont('Arial', '', 8.5);

$podTs = isset($podMeta['TS']) ? trim((string)$podMeta['TS']) : '';
if ($podTs === '' && $podRow && isset($podRow['FECHA_MENSAJE'])) {
    $podTs = trim((string)$podRow['FECHA_MENSAJE']);
}
$podLat = isset($podMeta['LAT']) ? trim((string)$podMeta['LAT']) : '';
$podLng = isset($podMeta['LNG']) ? trim((string)$podMeta['LNG']) : '';
$podAcc = isset($podMeta['ACC']) ? trim((string)$podMeta['ACC']) : '';
$podUsuario = $podRow ? trim((string)$podRow['USUARIO']) : '';
$podFotoRel = isset($podMeta['FOTO']) ? trim((string)$podMeta['FOTO']) : '';
$podFirmaRel = isset($podMeta['FIRMA']) ? trim((string)$podMeta['FIRMA']) : '';

$pdf->Cell(0, 5, txtPdf('Registrado por: ' . ($podUsuario !== '' ? $podUsuario : '-')), 0, 1, 'L');
$pdf->Cell(0, 5, txtPdf('Fecha/hora POD: ' . ($podTs !== '' ? $podTs : '-')), 0, 1, 'L');
$pdf->Cell(0, 5, txtPdf('Geo: ' . (($podLat !== '' && $podLng !== '') ? ($podLat . ', ' . $podLng) : 'No registrada')), 0, 1, 'L');
$pdf->Cell(0, 5, txtPdf('Precision: ' . ($podAcc !== '' ? ($podAcc . ' m') : '-')), 0, 1, 'L');

$fotoAbs = rutaPodAbsoluta($podFotoRel);
$firmaAbs = rutaPodAbsoluta($podFirmaRel);
$fotoOk = imagenCompatibleFpdf($fotoAbs);
$firmaOk = imagenCompatibleFpdf($firmaAbs);

$pdf->Ln(1);
$yIniPod = $pdf->GetY();

if ($fotoOk) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(85, 5, 'Foto entrega', 0, 0, 'L');
} else {
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(85, 5, txtPdf('Foto entrega: no disponible'), 0, 0, 'L');
}

if ($firmaOk) {
    $pdf->SetFont('Arial', 'B', 8);
    $pdf->Cell(85, 5, 'Firma receptor', 0, 1, 'L');
} else {
    $pdf->SetFont('Arial', '', 8);
    $pdf->Cell(85, 5, txtPdf('Firma receptor: no disponible'), 0, 1, 'L');
}

$yImgs = $pdf->GetY() + 1;
if ($fotoOk) {
    $pdf->Image($fotoAbs, 10, $yImgs, 85, 55);
}
if ($firmaOk) {
    $pdf->Image($firmaAbs, 105, $yImgs, 85, 55);
}

if ($fotoOk || $firmaOk) {
    $pdf->SetY(max($yIniPod + 62, $yImgs + 56));
}

$pdf->Ln(2);
$pdf->SetFont('Arial', '', 8);
$pdf->Cell(0, 5, txtPdf('Generado por Gestion de Inventarios y Despachos D17'), 0, 1, 'L');

$nombre = 'remision_' . trim((string)$enc['CODPREFIJO']) . '_' . trim((string)$enc['NUMERO']) . '.pdf';
$pdf->Output('I', $nombre);
exit;
