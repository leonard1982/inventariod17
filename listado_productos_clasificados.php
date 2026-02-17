<?php
require("conecta.php");
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Listado de Productos Clasificados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css">
    <style>
        body { padding: 20px; }
        table th, table td { text-align: center; vertical-align: middle; }
        td.text-start { text-align: left !important; }
    </style>
</head>
<body>

<h2>Listado de Productos con Clasificación</h2>
<input type="text" id="buscar" class="form-control mb-3" placeholder="Buscar por grupo, código, producto o clasificación...">

<button class="btn btn-success mb-3" onclick="exportarExcel()">Exportar a Excel</button>

<table class="table table-bordered table-striped" id="tablaProductos">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            <th>Clasificación</th>
            <th>Grupo</th>
            <th>Código</th>
            <th>Producto</th>
            <th>Stock</th>
            <th>Unidad</th>
			<th>Ult.Costo Prom</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $sql = "SELECT
                m.codigo,
                m.descrip,
                g.codigo || ' - ' || g.descrip as grupo,
                a.codigo as clasificacion,
                s.existenc as stock,
                m.unidad,
				s.ultcostprom as ultimo_costo_prom
            FROM material m
            INNER JOIN grupmat g ON m.grupmatid = g.grupmatid
            LEFT JOIN marcaart a ON m.marcaartid = a.marcaartid
            INNER JOIN materialsuc s ON s.matid = m.matid
            WHERE g.codigo NOT LIKE '00.%' AND s.sucid = 1
            ORDER BY a.codigo ASC NULLS LAST";

    $stmt = $conect_bd_actual->consulta($sql);
    $contador = 1;
    while ($row = ibase_fetch_object($stmt)) {
        echo "<tr>";
        echo "<td>" . $contador++ . "</td>";
        echo "<td>" . htmlspecialchars($row->CLASIFICACION ?? 'Sin clasificar') . "</td>";
        echo "<td class='text-start'>" . utf8_encode($row->GRUPO) . "</td>";
        echo "<td><a href='listado_productos_movimientos.php?codigo=" . urlencode($row->CODIGO) . "' target='_blank'>" . utf8_encode($row->CODIGO) . "</a></td>";
        echo "<td class='text-start'>" . utf8_encode($row->DESCRIP) . "</td>";
        echo "<td>" . number_format($row->STOCK, 0) . "</td>";
        echo "<td>" . htmlspecialchars($row->UNIDAD) . "</td>";
		echo "<td style='text-align: right;'>" . number_format($row->ULTIMO_COSTO_PROM) . "</td>";
        echo "</tr>";
    }
    ?>
    </tbody>
</table>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
    document.getElementById('buscar').addEventListener('input', function () {
        let filtro = this.value.toLowerCase();
        document.querySelectorAll('#tablaProductos tbody tr').forEach(function (fila) {
            fila.style.display = Array.from(fila.cells).some(td =>
                td.textContent.toLowerCase().includes(filtro)
            ) ? '' : 'none';
        });
    });

    function exportarExcel() {
        const tabla = document.getElementById('tablaProductos');
        const wb = XLSX.utils.table_to_book(tabla, { sheet: "Productos" });
        XLSX.writeFile(wb, "Listado_Productos_Clasificados.xlsx");
    }
</script>

</body>
</html>
