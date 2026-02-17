<?php
require("conecta.php");

//<!--Llamamos las librerias css y js -->
includeAssets();

$v_contrato = "";
$v_idcontrato = "";
$v_numero = "";
$v_existencia = 0;
$v_totalcosto = 0;
$anios = 1;

// Consulta a la base de datos de inventarios
$vsql_inventario = "SELECT ID, TERID, LINEAID, PRESUPUESTO, LINEA FROM sn_presu_vend_lineas ORDER BY ID DESC";
$configuraciones = [];

if ($vc_inventario = $conect_bd_actual->consulta($vsql_inventario)) {
	while ($vr_inventario = ibase_fetch_object($vc_inventario)) {
		if ($vr_inventario === false || empty($vr_inventario)) {
			error_log("Error fetching inventory object: " . ibase_errmsg());
		} else {
			$configuraciones[] = $vr_inventario;
		}
	}
} else {
	error_log("Error executing inventory query: " . ibase_errmsg());
}

//print_r($configuraciones);

// Consulta a la base de datos actual para obtener las descripciones de los grupos
$grupos_descrip = [];
$grupos_codigo  = [];
$grupos_presupuesto = [];
$grupos_proveed = [];
$lineas_agrupacion = [];

foreach ($configuraciones as $configuracion)
{
	$grupo_id = $configuracion->LINEAID;
	$vsql_actual = "SELECT descrip,codigo FROM LINEAMAT WHERE LINEAMATID = '$grupo_id'";
	
	if ($vc_actual = $conect_bd_actual->consulta($vsql_actual))
	{
		if ($vr_actual = ibase_fetch_object($vc_actual))
		{
			if ($vr_actual === false)
			{
				error_log("Error fetching actual object: " . ibase_errmsg());
			}
			else
			{
				$grupos_descrip[$configuracion->ID] = $vr_actual->DESCRIP;
				$grupos_codigo[$configuracion->ID]  = $vr_actual->CODIGO;
				$grupos_presupuesto[$configuracion->ID] = $configuracion->PRESUPUESTO;
				
				//consultamos el nombre del proveedor
				$vsql = "select nittri, nombre from terceros where TERID='".$configuracion->TERID."'";
				if ($vc_provee = $conect_bd_actual->consulta($vsql))
				{
					if ($vr_provee = ibase_fetch_object($vc_provee))
					{
						if ($vr_provee === false)
						{
							error_log("Error fetching actual object: " . ibase_errmsg());
						}
						else
						{
							$grupos_proveed[$configuracion->ID] = $vr_provee->NOMBRE;
						}
					}
				}
				
				$lineas_agrupacion[$configuracion->ID] =  $configuracion->LINEA;
			}
		}
	} else {
		error_log("Error executing actual query: " . ibase_errmsg());
	}
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<title>Configuración Lineas</title>
	<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
<div class="container">
<div class="table-responsive">
	<button class="btn btn-primary mb-3" onclick="crearNuevoRegistro()">Crear Nuevo Registro</button>
	<style>
		.color-1 { background-color: #d4edda !important; } /* verde claro */
		.color-2 { background-color: #d1ecf1 !important; } /* celeste */
		.color-3 { background-color: #fff3cd !important; } /* amarillo */
		.color-4 { background-color: #f8d7da !important; } /* rojo claro */
		.color-5 { background-color: #e2e3e5 !important; } /* gris claro */
	</style>

	<table class="table table-striped table-bordered" data-sortable style="align:center; width:100%;" id="tabledatos">
		<thead>
			<th><center>Item</center></th>
			<th><center>LINEA</center></th>
			<th><center>ASESOR</center></th>
			<th><center>PRESUPUESTO</center></th>
			<th><center>AGRUPACIÓN</center></th>
			<th><center>EDITAR</center></th>
			<th><center>ELIMINAR</center></th>
		</thead>
		<tbody id="cuerpo">
		<?php
		// Paso 4: Pintar filas según prefijo
		$vcontador = 1;
		foreach ($configuraciones as $configuracion) {
			$grupo_descrip = isset($grupos_descrip[$configuracion->ID]) ? utf8_encode($grupos_descrip[$configuracion->ID]) : '';
			$grupo_codigo  = isset($grupos_codigo[$configuracion->ID]) ? utf8_encode($grupos_codigo[$configuracion->ID]) : '';
			$proveedor     = isset($grupos_proveed[$configuracion->ID]) ? utf8_encode($grupos_proveed[$configuracion->ID]) : '';
			$presupuesto   = isset($grupos_presupuesto[$configuracion->ID]) ? utf8_encode($grupos_presupuesto[$configuracion->ID]) : '';
			$agrupacion    = isset($lineas_agrupacion[$configuracion->ID]) ? utf8_encode($lineas_agrupacion[$configuracion->ID]) : '';

			?>
			<tr class="<?php echo $clase; ?>">
				<td style="text-align:center;"><?php echo $vcontador; ?></td>
				<td style="text-align:left;"><?php echo $grupo_codigo." - ".$grupo_descrip; ?></td>
				<td style="text-align:left;"><?php echo $proveedor; ?></td>
				<td style="text-align:right;"><?php echo number_format(floatval($presupuesto)); ?></td>
				<td style="text-align:left;"><?php echo $agrupacion; ?></td>
				<td style="text-align:center;">
					<a href="#" onclick="confirmEditL(<?php echo $configuracion->ID; ?>)"><i class="fas fa-edit"></i></a>
				</td>
				<td style="text-align:center;">
					<a href="#" onclick="confirmDeleteL(<?php echo $configuracion->ID; ?>)"><i class="fas fa-trash-alt"></i></a>
				</td>
			</tr>
			<?php
			$vcontador++;
		}
		?>
		</tbody>
	</table>

</div>
</div>

<script>
function crearNuevoRegistro() {
	$.post("configuracionLineas.php", function (response) {
		$('#contenido').html(response);
	});
}

function confirmEditL(id) {
	Swal.fire({
		title: '¿Deseas editar esta configuración?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Sí, editar',
		cancelButtonText: 'No, cancelar'
	}).then((result) => {
		if (result.isConfirmed) {
			var pagina = 'configuracionLineas.php?id=' + id;
			cargarReporte(pagina, 'Cargando');
		}
	});
}

function confirmDeleteL(id) {
	Swal.fire({
		title: '¿Deseas eliminar esta configuración?',
		icon: 'warning',
		showCancelButton: true,
		confirmButtonText: 'Sí, eliminar',
		cancelButtonText: 'No, cancelar'
	}).then((result) => {
		if (result.isConfirmed) {
			$.ajax({
				url: 'ListaConfiguracionLineasEliminar.php',
				type: 'POST',
				data: { id: id },
				success: function(response) {
					if (response.trim() === 'OK') {
						Swal.fire('Eliminado', 'La configuración ha sido eliminada.', 'success').then(() => {
							cargarReporte("ListaConfiguracionLineas.php", 'Cargando');
						});
					} else {
						Swal.fire('Error', 'No se pudo eliminar la configuración.', 'error');
						console.error('Respuesta del servidor:', response);
					}
				},
				error: function(xhr, status, error) {
					Swal.fire('Error', 'Ocurrió un error al intentar eliminar.', 'error');
					console.error('AJAX Error:', error);
				}
			});
		}
	});
}


// Función genérica para manejar la carga de reportes con AJAX
function cargarReporte(url, mensaje, confirmacion = false) {
	if (confirmacion) {
		Swal.fire({
			title: mensaje,
			icon: 'warning',
			showCancelButton: true,
			confirmButtonColor: '#3085d6',
			cancelButtonColor: '#d33',
			confirmButtonText: 'Sí, continuar',
			cancelButtonText: 'No, cancelar'
		}).then((result) => {
			if (result.isConfirmed) {
				//mostrarCargando();
				realizarPeticion(url);
				cerrarMenu();
			}
		});
	} else {
		//mostrarCargando();
		realizarPeticion(url);
	}
}

// Función para mostrar el mensaje de carga
function mostrarCargando() {
	$('.bodyp').block({
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
}
</script>
</body>
</html>
