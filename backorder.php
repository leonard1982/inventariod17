<?php
date_default_timezone_set('America/Bogota');
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';
require('conecta.php');

$bd = '';
$ip = '127.0.0.1';
$varchivo = 'bd_admin.txt';
$varchivopj = __DIR__ . '/prefijos.txt';
$vprefijos = '';
$vbd_actual = __DIR__ . '/bd_actual.txt';
$vbd_anterior = __DIR__ . '/bd_anterior.txt';
$vbd_inventarios = __DIR__ . '/bd_inventarios.txt';
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BackOrder</title>
  <?php includeAssets(); ?>
  <style>
    .backorder-page {
      padding: 0.85rem 0.45rem 1rem;
    }
    .backorder-shell {
      max-width: 1220px;
      margin: 0 auto;
    }
    .backorder-header {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.7rem;
    }
    .backorder-header h2 {
      margin: 0;
      font-size: 1.22rem;
      color: #17364e;
    }
    .backorder-card {
      border: 1px solid #d3e2ef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 18px rgba(16, 45, 68, 0.08);
      overflow: hidden;
    }
    .backorder-card .table {
      margin-bottom: 0;
    }
    .backorder-card .table thead th {
      background: linear-gradient(180deg, #edf5fd 0%, #e4effa 100%);
      color: #153851;
      white-space: nowrap;
      border-bottom: 1px solid #d0e0ee;
    }
    .backorder-empty {
      border: 1px dashed #c7d8e8;
      background: #f8fbff;
      border-radius: 12px;
      color: #486279;
      padding: 0.85rem 1rem;
    }
  </style>
</head>
<body class="bodyc backorder-page">
<section class="backorder-shell">
  <div class="backorder-header">
    <h2><i class="fas fa-truck-loading"></i> Informe de BackOrder</h2>
    <button class="btn btn-primary" id="recargar"><i class="fas fa-rotate-right"></i> Recargar</button>
  </div>

<?php
if (file_exists($vbd_actual)) {
    $fp = fopen($vbd_actual, 'r');
    while (!feof($fp)) {
        $vbd_actual = resolverRutaFirebird(fgets($fp));
    }
    fclose($fp);
    if (!file_exists($vbd_actual)) {
        echo 'NO SE ENCUENTRA LA BASE DE DATOS ACTUAL DE TNS -- ';
    }
} else {
    echo 'NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE ACTUAL -- ';
}

if (file_exists($vbd_anterior)) {
    $fp = fopen($vbd_anterior, 'r');
    while (!feof($fp)) {
        $vbd_anterior = resolverRutaFirebird(fgets($fp));
    }
    fclose($fp);
    if (!file_exists($vbd_anterior)) {
        echo 'NO SE ENCUENTRA LA BASE DE DATOS ANTERIOR DE TNS -- ';
    }
} else {
    echo 'NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE ANTERIOR -- ';
}

if (file_exists($vbd_inventarios)) {
    $fp = fopen($vbd_inventarios, 'r');
    while (!feof($fp)) {
        $vbd_inventarios = resolverRutaFirebird(fgets($fp));
    }
    fclose($fp);
    if (!file_exists($vbd_inventarios)) {
        echo 'NO SE ENCUENTRA LA BASE DE DATOS DE INVENTARIOS -- ';
    }
} else {
    echo 'NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE DE INVENTARIOS -- ';
}

if (file_exists($varchivopj)) {
    $fpj = fopen($varchivopj, 'r');
    while (!feof($fpj)) {
        $vprefijos = fgets($fpj);
    }
    fclose($fpj);
    if (empty($vprefijos)) {
        echo 'NO SE HAN CONFIGURADO PREFIJOS -- ';
    }
} else {
    echo 'NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE PREFIJOS -- ';
}

$vsihaybackorder = false;
$vkardexid = '';
$vcontador = 0;
$vsql = "select distinct k.kardexid, k.codprefijo, k.numero, k.fecha ,t.nombre as proveedor
         from kardex k
         inner join terceros t on k.cliente=t.terid
         where k.kardexid in(select kk.sn_orden_compra from kardex kk) and k.codcomp='PC' and k.fecasentad is not null and k.sn_estado_inv='FACTURADO'";

if ($conect_bd_actual = new dbFirebirdPDO($ip, $vbd_actual)) {
    if ($cox2 = $conect_bd_actual->consulta($vsql)) {
        while ($rx2 = $cox2->fetch(PDO::FETCH_OBJ)) {
            $vkardexid = $rx2->KARDEXID;
            $vsql2 = "select distinct m.codigo
                      from dekardex d
                      inner join material m on d.matid=m.matid
                      where d.kardexid='" . $vkardexid . "'
                      and m.codigo in(
                          select distinct mm.codigo
                          from dekardex dd
                          inner join material mm on dd.matid=mm.matid
                          where dd.kardexid in(select kk.kardexid from kardex kk where kk.sn_orden_compra='" . $vkardexid . "')
                          and dd.canmat<>d.canmat
                      )";

            if ($cox3 = $conect_bd_actual->consulta($vsql2)) {
                $vsihaybackorder = false;
                while ($rx3 = $cox3->fetch(PDO::FETCH_OBJ)) {
                    if (!empty($rx3->CODIGO)) {
                        $vsihaybackorder = true;
                    }
                }

                if ($vsihaybackorder) {
                    if ($vcontador == 0) {
                        echo '<div class="backorder-card">';
                        echo '<div class="table-responsive">';
                        echo '<table id="tablaBackorder" class="table table-striped table-hover align-middle">';
                        echo '<thead><tr>';
                        echo '<th>Pedido</th>';
                        echo '<th>Fecha</th>';
                        echo '<th>Proveedor</th>';
                        echo '<th>Accion</th>';
                        echo '</tr></thead><tbody>';
                    }

                    $fecha = substr($rx2->FECHA, 0, -9);
                    $fecha = date_create($fecha);
                    $fecha = date_format($fecha, 'd-m-Y');

                    echo '<tr>';
                    echo '<td>' . $rx2->CODPREFIJO . '/' . $rx2->NUMERO . '</td>';
                    echo '<td>' . $fecha . '</td>';
                    echo '<td>' . utf8_encode($rx2->PROVEEDOR) . '</td>';
                    echo '<td><button class="btn btn-primary btn-sm" onclick="abrirVentana(' . $rx2->KARDEXID . ')"><i class="fas fa-eye"></i> Ver detalle</button></td>';
                    echo '</tr>';
                    $vcontador++;
                }
            }
        }
    }
}

if ($vcontador > 0) {
    echo '</tbody></table></div></div>';
} else {
    echo '<div class="backorder-empty"><i class="fas fa-circle-info"></i> No hay pedidos con diferencias de backorder para mostrar.</div>';
}
?>
</section>

<div class="modal fade" id="modalBackOrder" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detalle del BackOrder</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body p-0">
        <iframe id="iframeBackOrder" src="" width="100%" height="700" frameborder="0"></iframe>
      </div>
    </div>
  </div>
</div>

<script>
function abrirVentana(kardexId) {
  document.getElementById('iframeBackOrder').src = 'backorder_detalle.php?kardexid=' + kardexId;
  var modal = new bootstrap.Modal(document.getElementById('modalBackOrder'));
  modal.show();
}

$(document).ready(function() {
  if ($.fn.DataTable && $('#tablaBackorder').length) {
    $('#tablaBackorder').DataTable({
      pageLength: 15,
      lengthChange: false,
      ordering: true,
      info: true,
      language: {
        search: 'Buscar:',
        emptyTable: 'No hay datos disponibles',
        info: 'Mostrando _START_ a _END_ de _TOTAL_',
        paginate: { previous: '<', next: '>' }
      }
    });
  }

  $('#recargar').on('click', function() {
    if (window.parent && window.parent.$) {
      window.parent.$('#backorder').trigger('click');
    } else {
      window.location.reload();
    }
  });
});
</script>
</body>
</html>
