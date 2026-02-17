<?php
require("conecta.php");
?>
<html lang="es" dir="ltr">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Log Máximos y Mínimos</title>
    <?php includeAssets(); ?>
    <style>
      .form-section {
        max-width: 1150px;
        margin: auto;
        padding: 20px;
      }
      .select2-container--default .select2-selection--single {
        height: 38px !important;
        padding: 6px 12px;
        font-size: 1rem;
        line-height: 1.5;
        border: 1px solid #ced4da;
        border-radius: .25rem;
      }
      .no-border-table td {
        border: none !important;
        padding: 5px 10px;
        vertical-align: middle;
      }
      .log-progress-wrap {
        max-width: 700px;
        margin: 10px auto 12px;
        display: none;
      }
      .log-progress-text {
        font-size: 0.85rem;
        color: #4f667b;
        margin-bottom: 5px;
      }
    </style>
  </head>
  <body class="bodylog">
    <?php
    fCrearLogTNS($_SESSION["user"], 'EL USUARIO ' . $_SESSION["user"] . ' INGRESO EN LA OPCION (LOG MAXIMOS Y MINIMO) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO', $contenidoBdActual);
    ?>
    <div class="form-section">
	  <center><h4 id="titulo">LOG DE MAXIMOS Y MINIMOS</h4></center>
	  <form action='' method='POST'>
		<table class="table no-border-table align-middle">
		  <tr>
			<td><label for="grupo">Grupo:</label></td>
			<td style="width: 300px;">
			  <select class="form-select" name="grupo" id="grupo">
				<option value="0" selected="selected">TODOS</option>
				<?php
				if($conect_bd_inventario = new dbFirebirdPDO($ip,$contenidoBdInventarios)) {
				  $vsql = "SELECT GRUPO FROM CONFIGURACIONES ORDER BY ID ASC";
				  if($cox = $conect_bd_inventario->consulta($vsql)) {
					while($rr = $cox->fetch(PDO::FETCH_OBJ)) {
					  $vsql = "select grupmatid,codigo,descrip from grupmat where grupmatid='".$rr->GRUPO."'";
					  if($co2 = $conect_bd_actualPDO->consulta($vsql)) {
						if($r2 = $co2->fetch(PDO::FETCH_OBJ)) {
						  ?>
						  <option value="<?php echo $r2->GRUPMATID; ?>"><?php echo $r2->CODIGO . '--' . utf8_encode($r2->DESCRIP); ?></option>
						  <?php
						}
					  }
					}
				  }
				}
				?>
			  </select>
			</td>
			<td style="display:none;"><label for="linea">Línea:</label></td>
			<td style="display:none; width: 300px;">
			  <select class="form-select" name="linea" id="linea">
				<option value="0">TODAS</option>
				<?php
				$vsql = "select lineamatid,codigo,descrip from lineamat";
				if($cox = $conect_bd_actual->consulta($vsql)) {
				  while($rx = ibase_fetch_object($cox)) {
				?>
					<option value="<?php echo $rx->LINEAMATID; ?>"><?php echo $rx->CODIGO . '--' . utf8_encode($rx->DESCRIP); ?></option>
				<?php
				  }
				}
				?>
			  </select>
			</td>
			<td><input type='text' id='buscadorProductos' class='form-control' style='width:300px;' placeholder='Buscar en la tabla...'></td>
			<td><label for="reg">Reg:</label></td>
			<td style="width: 100px;">
			  <input type='number' class='form-control' id='reg' name='reg' value='0' style='text-align:right; max-width: 90px;' />
			</td>
			<td>
			  <div class="form-check" style="margin-left: 15px;">
				<input class="form-check-input" type="checkbox" id="guardarCalculos" name="guardarCalculos">
				<label class="form-check-label" for="guardarCalculos">Guardar Cálculos</label>
			  </div>
			</td>
			<td>
			  <input type='button' id='actualizar' class='btn btn-success' value='Generar'>
			</td>
			<td>
			  <button id='btnExportar' class='btn btn-primary'>Excel</button>
			</td>
		  </tr>
		</table>
	  </form>
	</div>

    <div id="logProgressWrap" class="log-progress-wrap">
      <div class="log-progress-text"><i class="fas fa-cogs"></i> Generando informe, por favor espera...</div>
      <div class="progress">
        <div id="logProgressBar" class="progress-bar progress-bar-striped progress-bar-animated bg-info" role="progressbar" style="width: 0%">0%</div>
      </div>
    </div>
	
    <div id="contenidolog"></div>

    <script>
	let logProgressTimer = null;

	function iniciarProgresoLog() {
	  var progreso = 3;
	  $('#logProgressWrap').show();
	  $('#logProgressBar').css('width', progreso + '%').text(progreso + '%');
	  if (logProgressTimer) {
		clearInterval(logProgressTimer);
	  }
	  logProgressTimer = setInterval(function() {
		if (progreso < 90) {
		  progreso += 2;
		  $('#logProgressBar').css('width', progreso + '%').text(progreso + '%');
		}
	  }, 380);
	}

	function finalizarProgresoLog() {
	  if (logProgressTimer) {
		clearInterval(logProgressTimer);
		logProgressTimer = null;
	  }
	  $('#logProgressBar').css('width', '100%').text('100%');
	  setTimeout(function() {
		$('#logProgressWrap').hide();
		$('#logProgressBar').css('width', '0%').text('0%');
	  }, 300);
	}

	$(document).ready(function()
	{
		console.log('jQuery versión:', $.fn.jquery);
		console.log('Select2 disponible:', typeof $.fn.select2);

		if ($.fn.select2) {
		  $('#grupo').select2({
			placeholder: "Seleccione un grupo",
			allowClear: true,
			width: '100%'
		  });
		} else {
		  console.error('Select2 no está cargado correctamente.');
		}
		
		const buscador = document.getElementById("buscadorProductos");
		if (buscador)
		{
			buscador.addEventListener("keyup", function () {
			  const filtro = buscador.value.toLowerCase();
			  const bloques = document.querySelectorAll(".bloque-producto");

			  bloques.forEach((tbody) => {
				const texto = tbody.textContent.toLowerCase();
				tbody.style.display = texto.includes(filtro) ? "" : "none";
			  });
			});
		}
    });
	
	document.getElementById("btnExportar").addEventListener("click", function (e) {
		
	  e.preventDefault();
	  const tabla = document.getElementById("tablaResultados");

	  if (!tabla) {
		Swal.fire({
		  icon: 'error',
		  title: 'Tabla no encontrada',
		  text: 'No hay resultados para exportar.'
		});
		return;
	  }

	  const wb = XLSX.utils.table_to_book(tabla, { sheet: "Reporte" });
	  XLSX.writeFile(wb, "reporte_maximos_minimos.xlsx");
	});

      $('#actualizar').on('click', function() {
        Swal.fire({
          title: '¿Desea continuar?',
          text: 'Este reporte puede tardar un poco.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Sí, continuar',
          cancelButtonText: 'Cancelar',
          reverseButtons: true
        }).then((result) => {
          if (result.isConfirmed) {
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
            iniciarProgresoLog();

            var grupo = $('#grupo').val();
			var reg = $('#reg').val();
			var linea = $('#linea').val();
			var buscadorProductos = $('#buscadorProductos').val();
			var guardarCalculos = $('#guardarCalculos').is(':checked') ? 1 : 0;

			console.log(grupo + reg);

			$.ajax({
			  type: "POST",
			  url: "index2_detalle.php",
			  timeout: 600000,
			  data: { 
				"grupo": grupo, 
				"reg": reg, 
				"linea": linea,
				"buscadorProductos": buscadorProductos,
				"guardarCalculos": guardarCalculos
			  },
			  success: function(response) {
				$('#contenidolog').html(response);
			  },
			  error: function(xhr, status) {
				var mensaje = status === 'timeout' ? 'El proceso esta tardando demasiado. Intenta con menos registros.' : 'No se pudo generar el informe.';
				Swal.fire('Atencion', mensaje, 'warning');
			  },
			  complete: function() {
				finalizarProgresoLog();
				$('body').unblock();
			  }
			});

          }
        });
      });
    </script>

    <?php
    createFloatingButton("fas fa-arrow-up", "Back to Top", "#titulo");
    ?>

  </body>
</html>
