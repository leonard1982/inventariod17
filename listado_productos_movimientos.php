<?php
require("conecta.php");

$codigo = $_GET['codigo'] ?? '';
if (!$codigo) {
    echo "Código de producto no especificado.";
    exit;
}

$sql_matid = "SELECT matid FROM material WHERE codigo = '$codigo'";
$stmt_matid = $conect_bd_actual->consulta($sql_matid);
$matid_obj = ibase_fetch_object($stmt_matid);
if (!$matid_obj) {
    echo "Producto no encontrado.";
    exit;
}
$matid = $matid_obj->MATID;

// FECHA DE INICIO: desde el mes anterior al actual hacia atrás 6 meses
$fecha_base = new DateTime('first day of last month');
$fecha_limite = (clone $fecha_base)->modify('-5 months'); // total 6 meses incluyendo el mes anterior

function obtenerMovimientos($conexion, $matid, $fecha_limite) {
    $sql = "SELECT 
                k.fecha, 
                d.canlista, 
                k.codcomp || k.codprefijo || k.numero AS factura
            FROM kardex k
            INNER JOIN dekardex d ON d.kardexid = k.kardexid
            WHERE d.matid = '$matid'
              AND k.fecasentad IS NOT NULL
              AND k.fecanulado IS NULL
              AND k.codcomp = 'FV'
              AND k.fecha >= '" . $fecha_limite->format('Y-m-d') . "'
            ORDER BY k.fecha DESC";

    $resultados = [];
    $stmt = $conexion->consulta($sql);
    while ($row = ibase_fetch_object($stmt)) {
        $resultados[] = $row;
    }
    return $resultados;
}

$movimientos = [];
$usa_anterior = ((int)(new DateTime())->format("m") <= 6);
if ($usa_anterior) {
    $movimientos = array_merge($movimientos, obtenerMovimientos($conect_bd_anterior, $matid, $fecha_limite));
}
$movimientos = array_merge($movimientos, obtenerMovimientos($conect_bd_actual, $matid, $fecha_limite));

// Ordenar por fecha descendente
usort($movimientos, function($a, $b) {
    return strtotime($b->FECHA) - strtotime($a->FECHA);
});
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Movimientos del Producto <?php echo htmlspecialchars($codigo); ?></title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        table th, table td { text-align: center; vertical-align: middle; }
    </style>
</head>
<body>

<h2>Movimientos del producto: <?php echo htmlspecialchars($codigo); ?></h2>
<p><strong>Desde: <?php echo $fecha_limite->format('Y-m-d'); ?> hasta <?php echo date('Y-m-d'); ?></strong></p>
<?php if ($usa_anterior): ?>
    <p><em>Incluye datos de la base del año anterior.</em></p>
<?php endif; ?>

<input type="text" id="buscar" class="form-control mb-3" placeholder="Buscar por fecha, cantidad o factura...">

<table class="table table-bordered table-striped" id="tablaMovimientos">
    <thead class="table-dark">
        <tr>
            <th>Fecha</th>
            <th>Cantidad</th>
            <th>Factura</th>
        </tr>
    </thead>
    <tbody>
    <?php
    if (empty($movimientos)) {
        echo "<tr><td colspan='3'>No se encontraron movimientos en los últimos 6 meses.</td></tr>";
    } else {
        foreach ($movimientos as $mov) {
            echo "<tr>";
            echo "<td>" . date("Y-m-d", strtotime($mov->FECHA)) . "</td>";
            echo "<td>" . number_format($mov->CANLISTA, 2) . "</td>";
            echo "<td>" . htmlspecialchars($mov->FACTURA) . "</td>";
            echo "</tr>";
        }
    }
    ?>
    </tbody>
</table>

<a href="listado_productos_clasificados.php" class="btn btn-secondary mt-3">← Volver al listado</a>

<script>
    document.getElementById('buscar').addEventListener('input', function () {
        let filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaMovimientos tbody tr').forEach(function (fila) {
            fila.style.display = Array.from(fila.cells).some(td =>
                td.textContent.toLowerCase().includes(filtro)
            ) ? '' : 'none';
        });
    });
</script>

</body>
</html>
