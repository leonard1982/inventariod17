<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Bogota');
session_start();

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';
require_once 'conecta.php';

$bd = '';
$ip = '127.0.0.1';
$varchivo = 'bd_admin.txt';
$vprefijos = '';
$varchivopj = '';
$vbd_actual = '';
$vbd_anterior = '';
$vbd_inventarios = '';

$drives = range('A', 'Z');
foreach ($drives as $drive) {
    $path = $drive . ':/facilweb/htdocs/evento_inventario/';
    if (file_exists($path . 'prefijos.txt')) {
        $varchivopj = $path . 'prefijos.txt';
        break;
    }
}

foreach ($drives as $drive) {
    $path = $drive . ':/facilweb/htdocs/evento_inventario/';
    if (file_exists($path . 'bd_actual.txt')) {
        $vbd_actual = $path . 'bd_actual.txt';
        break;
    }
}

foreach ($drives as $drive) {
    $path = $drive . ':/facilweb/htdocs/evento_inventario/';
    if (file_exists($path . 'bd_anterior.txt')) {
        $vbd_anterior = $path . 'bd_anterior.txt';
        break;
    }
}

foreach ($drives as $drive) {
    $path = $drive . ':/facilweb/htdocs/evento_inventario/';
    if (file_exists($path . 'bd_inventarios.txt')) {
        $vbd_inventarios = $path . 'bd_inventarios.txt';
        break;
    }
}

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

$conect_bd_anterior = new dbFirebirdPDO($ip, $vbd_anterior);
$conect_bd_actual = new dbFirebirdPDO($ip, $vbd_actual);
fCrearLogTNS($_SESSION['user'], 'EL USUARIO ' . $_SESSION['user'] . ' INGRESO EN LA OPCION (ROTACION INVENTARIO) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO', $vbd_actual);
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Rotacion Inventario</title>
  <?php includeAssets(); ?>
  <style>
    .rot-page {
      padding: 0.85rem 0.45rem 1rem;
    }
    .rot-shell {
      max-width: 1450px;
      margin: 0 auto;
    }
    .rot-card {
      border: 1px solid #d3e2ef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 18px rgba(16, 45, 68, 0.08);
      padding: 0.85rem;
    }
    .rot-title {
      margin: 0 0 0.85rem;
      text-align: center;
      color: #16374f;
      font-size: 1.52rem;
      font-weight: 800;
    }
    .rot-actions {
      display: flex;
      gap: 0.45rem;
      align-items: end;
      padding-bottom: 0.05rem;
    }
    .rot-result {
      margin-top: 0.75rem;
      border: 1px solid #d5e3f0;
      border-radius: 12px;
      overflow: hidden;
      min-height: 250px;
      background: #fff;
    }
    .rot-progress-wrap {
      display: none;
      margin: 0.5rem 0 0.35rem;
    }
    @media (max-width: 992px) {
      .rot-actions {
        align-items: stretch;
      }
    }
  </style>
</head>
<body class="bodyc rot-page">
<section class="rot-shell">
  <div class="rot-card">
    <h2 class="rot-title">ROTACION INVENTARIO</h2>
    <form action="" method="POST">
      <div class="row g-2 align-items-end">
        <div class="col-lg-2 col-md-4 col-sm-6">
          <label for="searchTerm" class="form-label">Buscar</label>
          <input id="searchTerm" type="text" onkeyup="doSearch(event);" class="form-control" placeholder="Buscar">
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <label for="grupo" class="form-label">Grupo/Familia</label>
          <select class="form-select" name="grupo" id="grupo">
            <option value="0" selected>TODOS</option>
            <?php
            $vsql = "select grupmatid,codigo,descrip from grupmat where CHAR_LENGTH(codigo)=8 and grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')";
            if ($cox = $conect_bd_actual->consulta($vsql)) {
                while ($rx = $cox->fetch(PDO::FETCH_OBJ)) {
                    echo "<option value='" . $rx->GRUPMATID . "'>" . $rx->CODIGO . '--' . utf8_encode($rx->DESCRIP) . "</option>";
                }
            }
            ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <label for="linea" class="form-label">Linea</label>
          <select class="form-select" name="linea" id="linea">
            <option value="0">TODAS</option>
            <?php
            $vsql = 'select lineamatid,codigo,descrip from lineamat';
            if ($cox = $conect_bd_actual->consulta($vsql)) {
                while ($rx = $cox->fetch(PDO::FETCH_OBJ)) {
                    echo "<option value='" . $rx->LINEAMATID . "'>" . $rx->CODIGO . '--' . utf8_encode($rx->DESCRIP) . "</option>";
                }
            }
            ?>
          </select>
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <label for="cant" class="form-label">Fecha inicial</label>
          <input class="form-control" id="cant" type="date" value="<?php echo date('Y') . '-01-01'; ?>">
        </div>
        <div class="col-lg-2 col-md-4 col-sm-6">
          <label for="reg" class="form-label">No. Registros</label>
          <input type="number" class="form-control text-end" id="reg" name="reg" value="100">
        </div>
        <div class="col-lg-2 col-md-4 col-sm-12 rot-actions">
          <button type="button" id="actualizar" class="btn btn-success w-100"><i class="fas fa-filter"></i> Generar</button>
          <button type="button" class="btn btn-primary w-100" id="btnExport"><i class="fas fa-file-excel"></i> Excel</button>
        </div>
      </div>
    </form>

    <div id="rotProgressWrap" class="rot-progress-wrap">
      <div class="progress">
        <div id="rotProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width:0%">0%</div>
      </div>
    </div>

    <div id="contenidocomparativo" class="rot-result"></div>
  </div>
</section>

<script>
var rotTimer = null;

function iniciarProgresoRot() {
  var progreso = 4;
  $('#rotProgressWrap').show();
  $('#rotProgressBar').css('width', progreso + '%').text(progreso + '%');
  if (rotTimer) {
    clearInterval(rotTimer);
  }
  rotTimer = setInterval(function() {
    if (progreso < 90) {
      progreso += 2;
      $('#rotProgressBar').css('width', progreso + '%').text(progreso + '%');
    }
  }, 350);
}

function finalizarProgresoRot() {
  if (rotTimer) {
    clearInterval(rotTimer);
    rotTimer = null;
  }
  $('#rotProgressBar').css('width', '100%').text('100%');
  setTimeout(function() {
    $('#rotProgressWrap').hide();
    $('#rotProgressBar').css('width', '0%').text('0%');
  }, 260);
}

$('#btnExport').on('click', function(e) {
  e.preventDefault();
  var reg = $('#reg').val();
  var grupo = $('#grupo').val();
  var linea = $('#linea').val();
  var cant = $('#cant').val();
  window.open('rotacion_inventario_excel.php?reg=' + reg + '&grupo=' + grupo + '&linea=' + linea + '&cant=' + cant, 'ventana1', 'width=1200,height=600,scrollbars=NO');
});

$('#actualizar').on('click', function() {
  Swal.fire({
    title: 'Desea continuar?',
    text: 'Este reporte puede tardar un poco.',
    icon: 'warning',
    showCancelButton: true,
    confirmButtonText: 'Si, continuar',
    cancelButtonText: 'Cancelar',
    reverseButtons: true
  }).then(function(result) {
    if (!result.isConfirmed) {
      return;
    }

    $('body').block({
      message: 'Cargando',
      css: {
        border: 'none',
        padding: '15px',
        backgroundColor: '#000',
        '-webkit-border-radius': '10px',
        '-moz-border-radius': '10px',
        opacity: .5,
        color: '#fff'
      }
    });
    iniciarProgresoRot();

    $.ajax({
      type: 'POST',
      url: 'rotacion_inventario_ajax.php',
      timeout: 600000,
      data: {
        cant: $('#cant').val(),
        reg: $('#reg').val(),
        grupo: $('#grupo').val(),
        linea: $('#linea').val()
      },
      success: function(response) {
        $('#contenidocomparativo').html(response);
      },
      error: function(xhr, status) {
        var mensaje = status === 'timeout' ? 'El proceso esta tardando demasiado. Intenta con menos registros.' : 'No fue posible generar el reporte.';
        Swal.fire('Atencion', mensaje, 'warning');
      },
      complete: function() {
        finalizarProgresoRot();
        $('body').unblock();
      }
    });
  });
});

function doSearch(e) {
  var code = (e.keyCode ? e.keyCode : e.which);
  if (code == 13) {
    return false;
  }

  var tableReg = document.getElementById('dato_productos');
  if (!tableReg) {
    return;
  }

  var searchText = document.getElementById('searchTerm').value.toLowerCase();
  for (var i = 1; i < tableReg.rows.length; i++) {
    var cellsOfRow = tableReg.rows[i].getElementsByTagName('td');
    var found = false;
    for (var j = 0; j < cellsOfRow.length && !found; j++) {
      var compareWith = cellsOfRow[j].innerHTML.toLowerCase();
      if (searchText.length === 0 || compareWith.indexOf(searchText) > -1) {
        found = true;
      }
    }
    tableReg.rows[i].style.display = found ? '' : 'none';
  }
}
</script>
</body>
</html>
