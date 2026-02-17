<?php require('conecta.php'); ?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Pedidos Automaticos Generados</title>
  <?php includeAssets(); ?>
  <style>
    .pedidos-page {
      padding: 0.9rem 0.45rem 1rem;
    }
    .pedidos-shell {
      max-width: 1320px;
      margin: 0 auto;
    }
    .pedidos-card {
      border: 1px solid #d3e2ef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 18px rgba(16, 45, 68, 0.08);
      padding: 0.9rem;
    }
    .pedidos-title {
      margin: 0 0 0.8rem;
      color: #17374f;
      font-size: 1.45rem;
      font-weight: 800;
      text-align: center;
    }
    .pedidos-actions {
      display: grid;
      grid-template-columns: 1fr auto;
      gap: 0.5rem;
      margin-bottom: 0.75rem;
    }
    .pedidos-table-wrap {
      border: 1px solid #d5e3f0;
      border-radius: 12px;
      overflow: hidden;
    }
    .estado-pill {
      display: inline-block;
      border-radius: 999px;
      padding: 0.18rem 0.55rem;
      font-size: 0.78rem;
      font-weight: 700;
      white-space: nowrap;
      border: 1px solid #c7d9ea;
      color: #1d4c70;
      background: #ecf5ff;
    }
    .asentado-pendiente {
      color: #7a5d18;
      font-weight: 700;
      background: #fff5d8;
      border: 1px solid #ecd79f;
      border-radius: 999px;
      padding: 0.14rem 0.52rem;
      display: inline-block;
    }
    @media (max-width: 768px) {
      .pedidos-actions {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>
<?php
$v_fecha = date('Y-m-d');
$v_fechaanterior = date('Y-m-d', strtotime($v_fecha . '- 1 month'));
$v_fechaanterior = date('Y-m-d', strtotime($v_fechaanterior . '- 1 year'));
$v_mes = date('m', strtotime($v_fechaanterior));
$v_year = date('Y', strtotime($v_fechaanterior));
$v_fechabusqueda = date('Y-m-d', strtotime($v_year . '-' . $v_mes . '-01'));

fCrearLogTNS($_SESSION['user'], 'EL USUARIO ' . $_SESSION['user'] . ' INGRESO EN LA OPCION (PEDIDOS GENERADOS) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO', $contenidoBdActual);

$vsql = "SELECT
          K.CODPREFIJO,
          K.CODPREFIJO ||'/'|| K.NUMERO AS NUMERO,
          T.NOMBRE AS PROVEEDOR,
          K.FECHA,
          K.FECASENTAD AS ASENTADO,
          K.SN_ESTADO_INV AS ESTADO,
          DATEDIFF(DAY, K.FECHA, CURRENT_TIMESTAMP) AS DIAS
        FROM KARDEX K
        INNER JOIN TERCEROS T ON K.CLIENTE = T.TERID
        INNER JOIN KARDEXSELF S ON S.KARDEXID = K.KARDEXID
        WHERE K.CODCOMP = 'PC'
          AND K.SN_ESTADO_INV <> 'FINALIZADO'
          AND S.PEDIDO IS NOT NULL AND S.PEDIDO <> ''
        ORDER BY K.CODPREFIJO ASC, K.CLIENTE, K.KARDEXID DESC";

function formatearFechaPedido($fechaValor)
{
    if (empty($fechaValor)) {
        return '';
    }
    $soloFecha = substr($fechaValor, 0, 10);
    $dt = date_create($soloFecha);
    return $dt ? date_format($dt, 'Y-m-d') : $soloFecha;
}
?>
<body class="bodyc pedidos-page">
<section class="pedidos-shell">
  <div class="pedidos-card">
    <h2 class="pedidos-title"><i class="fas fa-clipboard-check"></i> Pedidos Automaticos Generados</h2>

    <div class="pedidos-actions">
      <input type="text" id="filtro" class="form-control" placeholder="Buscar en la tabla...">
      <button id="exportar" class="btn btn-success"><i class="fas fa-file-excel"></i> Exportar a Excel</button>
    </div>

    <div class="table-responsive pedidos-table-wrap">
      <table id="tablaPedidos" class="table table-striped table-hover align-middle mb-0">
        <thead class="table-dark">
          <tr>
            <th>#</th>
            <th>Numero</th>
            <th>Proveedor</th>
            <th>Fecha</th>
            <th>Asentado</th>
            <th>Estado</th>
            <th>Dias Transcurridos</th>
            <th>Dias Pedido</th>
          </tr>
        </thead>
        <tbody>
<?php
$contador = 1;
if ($vc = $conect_bd_actual->consulta($vsql)) {
    while ($vr = ibase_fetch_object($vc)) {
        $fecha = formatearFechaPedido($vr->FECHA);
        $asentado = formatearFechaPedido($vr->ASENTADO);
        $asentadoHtml = $asentado !== '' ? htmlentities($asentado) : '<span class="asentado-pendiente">SIN ASENTAR</span>';
        $diasPedido = 0;

        $vsqlDias = "select first 1 dias_pedidos from configuraciones where prefijo_orden_pedido='" . $vr->CODPREFIJO . "'";
        if ($cox = $conect_bd_inventario->consulta($vsqlDias)) {
            if ($rx = ibase_fetch_object($cox)) {
                $diasPedido = (int)$rx->DIAS_PEDIDOS;
            }
        }

        echo '<tr>';
        echo '<td>' . $contador++ . '</td>';
        echo '<td>' . htmlentities($vr->NUMERO) . '</td>';
        echo '<td>' . htmlentities($vr->PROVEEDOR) . '</td>';
        echo '<td>' . htmlentities($fecha) . '</td>';
        echo '<td>' . $asentadoHtml . '</td>';
        echo '<td><span class="estado-pill">' . htmlentities($vr->ESTADO) . '</span></td>';
        echo '<td class="text-end">' . htmlentities($vr->DIAS) . '</td>';
        echo '<td class="text-end">' . htmlentities($diasPedido) . '</td>';
        echo '</tr>';
    }
}
?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
$(document).ready(function() {
  var tabla = null;
  if ($.fn.DataTable) {
    tabla = $('#tablaPedidos').DataTable({
      pageLength: 20,
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

  $('#filtro').on('keyup', function() {
    if (tabla) {
      tabla.search(this.value).draw();
      return;
    }

    var filtro = this.value.toLowerCase();
    $('#tablaPedidos tbody tr').each(function() {
      var texto = $(this).text().toLowerCase();
      $(this).toggle(texto.indexOf(filtro) !== -1);
    });
  });

  $('#exportar').on('click', function() {
    var tablaExport = document.getElementById('tablaPedidos');
    var wb = XLSX.utils.table_to_book(tablaExport, { sheet: 'Pedidos' });
    XLSX.writeFile(wb, 'pedidos_generados.xlsx');
  });
});
</script>
</body>
</html>
