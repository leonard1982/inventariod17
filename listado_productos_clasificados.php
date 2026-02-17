<?php
require('conecta.php');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Listado de Productos Clasificados</title>
  <?php includeAssets(); ?>
  <style>
    .clas-page {
      padding: 0.9rem 0.45rem 1rem;
    }
    .clas-shell {
      max-width: 1440px;
      margin: 0 auto;
    }
    .clas-card {
      border: 1px solid #d3e2ef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 18px rgba(16, 45, 68, 0.08);
      padding: 0.9rem;
    }
    .clas-head {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 0.55rem;
      margin-bottom: 0.75rem;
    }
    .clas-title {
      margin: 0 0 0.8rem;
      color: #17374f;
      font-size: 1.45rem;
      font-weight: 800;
      text-align: center;
    }
    .clas-table-wrap {
      border: 1px solid #d6e3ef;
      border-radius: 12px;
      overflow: hidden;
    }
    #tablaProductos th,
    #tablaProductos td {
      vertical-align: middle;
      white-space: nowrap;
    }
    #tablaProductos td.text-start {
      white-space: normal;
    }
    @media (max-width: 768px) {
      .clas-head {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<body class="bodyc clas-page">
<section class="clas-shell">
  <div class="clas-card">
    <h2 class="clas-title"><i class="fas fa-boxes-stacked"></i> Listado de Productos con Clasificacion</h2>

    <div class="clas-head">
      <input type="text" id="buscar" class="form-control" placeholder="Buscar por grupo, codigo, producto o clasificacion...">
      <button class="btn btn-success" type="button" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Exportar a Excel</button>
    </div>

    <div class="table-responsive clas-table-wrap">
      <table class="table table-bordered table-striped table-hover mb-0" id="tablaProductos">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Clasificacion</th>
            <th>Grupo</th>
            <th>Codigo</th>
            <th>Producto</th>
            <th>Stock</th>
            <th>Unidad</th>
            <th>Ult. Costo Prom.</th>
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
    echo '<tr>';
    echo '<td class="text-center">' . $contador++ . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($row->CLASIFICACION ?? 'Sin clasificar') . '</td>';
    echo '<td class="text-start">' . utf8_encode($row->GRUPO) . '</td>';
    echo '<td class="text-center"><a href="listado_productos_movimientos.php?codigo=' . urlencode($row->CODIGO) . '" target="_blank">' . utf8_encode($row->CODIGO) . '</a></td>';
    echo '<td class="text-start">' . utf8_encode($row->DESCRIP) . '</td>';
    echo '<td class="text-end">' . number_format($row->STOCK, 0) . '</td>';
    echo '<td class="text-center">' . htmlspecialchars($row->UNIDAD) . '</td>';
    echo '<td class="text-end">' . number_format($row->ULTIMO_COSTO_PROM) . '</td>';
    echo '</tr>';
}
?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<script>
let tablaClasificados = null;

$(document).ready(function() {
  if ($.fn.DataTable) {
    tablaClasificados = $('#tablaProductos').DataTable({
      pageLength: 25,
      lengthChange: false,
      ordering: true,
      info: true,
      scrollX: true,
      language: {
        search: 'Buscar:',
        emptyTable: 'No hay datos disponibles',
        info: 'Mostrando _START_ a _END_ de _TOTAL_',
        paginate: { previous: '<', next: '>' }
      }
    });
  }

  $('#buscar').on('input', function() {
    if (tablaClasificados) {
      tablaClasificados.search(this.value).draw();
      return;
    }

    const filtro = this.value.toLowerCase();
    $('#tablaProductos tbody tr').each(function() {
      const texto = $(this).text().toLowerCase();
      $(this).toggle(texto.indexOf(filtro) !== -1);
    });
  });
});

function exportarExcel() {
  const tabla = document.getElementById('tablaProductos');
  const wb = XLSX.utils.table_to_book(tabla, { sheet: 'Productos' });
  XLSX.writeFile(wb, 'Listado_Productos_Clasificados.xlsx');
}
</script>
</body>
</html>
