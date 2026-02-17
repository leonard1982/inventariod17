<?php
require('conecta.php');
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Configuracion Lineas</title>
  <?php includeAssets(); ?>
  <style>
    .lineas-page {
      padding: 0.9rem 0.45rem 1rem;
    }
    .lineas-shell {
      max-width: 1320px;
      margin: 0 auto;
    }
    .lineas-card {
      border: 1px solid #d3e2ef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 18px rgba(16, 45, 68, 0.08);
      padding: 0.9rem;
    }
    .lineas-head {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.6rem;
      margin-bottom: 0.75rem;
      flex-wrap: wrap;
    }
    .lineas-title {
      margin: 0;
      color: #17374f;
      font-size: 1.35rem;
      font-weight: 800;
    }
    .lineas-table-wrap {
      border: 1px solid #d6e3ef;
      border-radius: 12px;
      overflow: hidden;
    }
    .lineas-actions a {
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 30px;
      height: 30px;
      border-radius: 8px;
      border: 1px solid #c9dbee;
      background: #f5faff;
      color: #1f557c;
    }
    .lineas-actions a:hover {
      background: #e9f3ff;
    }
  </style>
</head>
<?php
$vsql_inventario = 'SELECT ID, TERID, LINEAID, PRESUPUESTO, LINEA FROM sn_presu_vend_lineas ORDER BY ID DESC';
$configuraciones = [];

if ($vc_inventario = $conect_bd_actual->consulta($vsql_inventario)) {
    while ($vr_inventario = ibase_fetch_object($vc_inventario)) {
        if ($vr_inventario !== false && !empty($vr_inventario)) {
            $configuraciones[] = $vr_inventario;
        }
    }
}

$grupos_descrip = [];
$grupos_codigo = [];
$grupos_presupuesto = [];
$grupos_proveed = [];
$lineas_agrupacion = [];

foreach ($configuraciones as $configuracion) {
    $grupo_id = $configuracion->LINEAID;
    $vsql_actual = "SELECT descrip,codigo FROM LINEAMAT WHERE LINEAMATID = '" . $grupo_id . "'";
    if ($vc_actual = $conect_bd_actual->consulta($vsql_actual)) {
        if ($vr_actual = ibase_fetch_object($vc_actual)) {
            $grupos_descrip[$configuracion->ID] = $vr_actual->DESCRIP;
            $grupos_codigo[$configuracion->ID] = $vr_actual->CODIGO;
            $grupos_presupuesto[$configuracion->ID] = $configuracion->PRESUPUESTO;

            $vsql = "select nittri, nombre from terceros where TERID='" . $configuracion->TERID . "'";
            if ($vc_provee = $conect_bd_actual->consulta($vsql)) {
                if ($vr_provee = ibase_fetch_object($vc_provee)) {
                    $grupos_proveed[$configuracion->ID] = $vr_provee->NOMBRE;
                }
            }
            $lineas_agrupacion[$configuracion->ID] = $configuracion->LINEA;
        }
    }
}
?>
<body class="bodyc lineas-page">
<section class="lineas-shell">
  <div class="lineas-card">
    <div class="lineas-head">
      <h2 class="lineas-title"><i class="fas fa-sitemap"></i> Configuracion de Lineas</h2>
      <button class="btn btn-primary" type="button" onclick="crearNuevoRegistro()"><i class="fas fa-plus"></i> Crear nuevo registro</button>
    </div>

    <div class="table-responsive lineas-table-wrap">
      <table class="table table-striped table-hover align-middle mb-0" style="width:100%;" id="tabledatos">
        <thead class="table-dark">
          <tr>
            <th class="text-center">Item</th>
            <th>Linea</th>
            <th>Asesor</th>
            <th class="text-end">Presupuesto</th>
            <th>Agrupacion</th>
            <th class="text-center">Editar</th>
            <th class="text-center">Eliminar</th>
          </tr>
        </thead>
        <tbody id="cuerpo">
<?php
$vcontador = 1;
foreach ($configuraciones as $configuracion) {
    $grupo_descrip = isset($grupos_descrip[$configuracion->ID]) ? utf8_encode($grupos_descrip[$configuracion->ID]) : '';
    $grupo_codigo = isset($grupos_codigo[$configuracion->ID]) ? utf8_encode($grupos_codigo[$configuracion->ID]) : '';
    $proveedor = isset($grupos_proveed[$configuracion->ID]) ? utf8_encode($grupos_proveed[$configuracion->ID]) : '';
    $presupuesto = isset($grupos_presupuesto[$configuracion->ID]) ? $grupos_presupuesto[$configuracion->ID] : 0;
    $agrupacion = isset($lineas_agrupacion[$configuracion->ID]) ? utf8_encode($lineas_agrupacion[$configuracion->ID]) : '';

    echo '<tr>';
    echo '<td class="text-center">' . $vcontador . '</td>';
    echo '<td>' . $grupo_codigo . ' - ' . $grupo_descrip . '</td>';
    echo '<td>' . $proveedor . '</td>';
    echo '<td class="text-end">' . number_format((float)$presupuesto) . '</td>';
    echo '<td>' . $agrupacion . '</td>';
    echo '<td class="text-center lineas-actions"><a href="#" onclick="confirmEditL(' . $configuracion->ID . '); return false;"><i class="fas fa-pen"></i></a></td>';
    echo '<td class="text-center lineas-actions"><a href="#" onclick="confirmDeleteL(' . $configuracion->ID . '); return false;"><i class="fas fa-trash"></i></a></td>';
    echo '</tr>';
    $vcontador++;
}
?>
        </tbody>
      </table>
    </div>
  </div>
</section>

<script>
function crearNuevoRegistro() {
  window.location.href = 'configuracionLineas.php';
}

function confirmEditL(id) {
  Swal.fire({
    title: 'Deseas editar esta configuracion?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, editar',
    cancelButtonText: 'Cancelar'
  }).then(function(result) {
    if (result.isConfirmed) {
      window.location.href = 'configuracionLineas.php?id=' + id;
    }
  });
}

function confirmDeleteL(id) {
  Swal.fire({
    title: 'Deseas eliminar esta configuracion?',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, eliminar',
    cancelButtonText: 'Cancelar'
  }).then(function(result) {
    if (!result.isConfirmed) {
      return;
    }

    $.ajax({
      url: 'ListaConfiguracionLineasEliminar.php',
      type: 'POST',
      data: { id: id },
      success: function(response) {
        if ((response || '').trim() === 'OK') {
          Swal.fire('Eliminado', 'La configuracion fue eliminada.', 'success').then(function() {
            window.location.reload();
          });
        } else {
          Swal.fire('Error', 'No se pudo eliminar la configuracion.', 'error');
        }
      },
      error: function() {
        Swal.fire('Error', 'Ocurrio un error al eliminar.', 'error');
      }
    });
  });
}

$(document).ready(function() {
  if ($.fn.DataTable) {
    $('#tabledatos').DataTable({
      pageLength: 20,
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
});
</script>
</body>
</html>
