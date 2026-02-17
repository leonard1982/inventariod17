<?php require("conecta.php"); ?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Conexiones</title>
	<!-- Llamamos las librerias css y js -->
	<?php includeAssets(); ?>
</head>
<body>
	<?php

	$v_contrato = "";
	$v_idcontrato = "";
	$v_numero = "";

	fCrearLogTNS($_SESSION["user"], 'EL USUARIO ' . $_SESSION["user"] . ' INGRESO EN LA OPCION (CONEXIONES) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO', $contenidoBdActual);

	$vsql = "SELECT id, anio, ruta_bd FROM bd_anios";
	if ($vc = $conect_bd_inventario->consulta($vsql)) {
	?>
		<div class="table-responsive">
			<center>
				<h4>CONEXIONES</h4>
				<div class="input-group" style="justify-content: center;">
					<div style="width:300px;">
						<input type="text" id="search" class="form-control" placeholder="Escribe para buscar..." />
					</div>
					<div style="margin-left:10px;">
						<button type="button" id="nuevaconexion" class="btn btn-primary" style="margin-left: 10px;" onclick="mostrarformulario();">Nuevo</button>
					</div>
				</div>
				<br>
				<table class="table table-striped table-bordered" style="align:center; width:1000px; height:300px;">
					<thead>
						<tr>
							<th><center>OPCIONES</center></th>
							<th><center>AÑO</center></th>
							<th><center>RUTA BD</center></th>
						</tr>
					</thead>
					<tbody id="cuerpo">
						<?php
						$v_cont = 0;
						while ($vr = ibase_fetch_object($vc)) {
						?>
							<tr>
								<td style="text-align:center;">
									<input type="hidden" id="r<?php echo $v_cont; ?>" value="<?php echo $vr->RUTA_BD; ?>" class="form-control">
									<img style="cursor:pointer;" src="imagenes/lapiz.png" onclick="Feditar(<?php echo $vr->ID; ?>, '<?php echo $vr->ANIO; ?>', <?php echo $v_cont; ?>);">
									<img style="cursor:pointer;" src="imagenes/papelera.png" onclick="Fborrar(<?php echo $vr->ID; ?>, '<?php echo $vr->ANIO; ?>', '<?php echo $vr->RUTA_BD; ?>');">
								</td>
								<td style="text-align:center;"><?php echo $vr->ANIO; ?></td>
								<td style="text-align:left;"><?php echo $vr->RUTA_BD; ?></td>
							</tr>
						<?php
							$v_cont++;
						}
						?>
					</tbody>
				</table>
			</center>
		</div>

		<!-- Formulario prefijos -->
		<div class="modal fade" id="FormularioConexiones" tabindex="-1" role="dialog">
			<div class="modal-dialog" role="document">
				<div class="modal-content">
					<div class="modal-header">
						<h4 class="modal-title">Nueva Conexion</h4>
						<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" id="idreg" value="0" class="form-control">
						<div class="row">
							<div class="col">
								<label for="">Año:</label>
								<br>
								<select class="form-select" name="anios" id="anios">
									<?php
									$v_year = date("Y");
									for ($i = $v_year; $i <= ($v_year + 10); $i++) {
									?>
										<option value="<?php echo $i; ?>"><?php echo $i; ?></option>
									<?php
									}
									?>
								</select>
							</div>
						</div>
						<div class="row">
							<label for="">Ruta BD:</label>
							<input type="text" id="ruta" value="" class="form-control">
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" id="GuardarConexion" class="btn btn-success">Guardar</button>
						<button type="button" class="btn btn-success" data-bs-dismiss="modal">Cancelar</button>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	?>
	<script>
		function Feditar(id, anio, ruta) {
			var opcion = confirm('Desea Editar la Conexion?');
			if (opcion) {
				ruta = $("#r" + ruta).val();
				$("#FormularioConexiones").modal('show');
				$("#anios").val(anio);
				$("#ruta").val(ruta);
				$("#idreg").val(id);
			}
		}

		function Fborrar(id, anio, ruta) {
			var opcion = confirm('Desea Eliminar la Conexion?');
			if (opcion) {
				if (!$.isEmptyObject(anio) && !$.isEmptyObject(ruta)) {
					$.post("BorrarConexion.php", {
						anio: anio,
						ruta: ruta,
						id: id
					}, function (r) {
						var obj = JSON.parse(r);
						if (obj.accion == "borro") {
							$("#FormularioConexiones").modal('hide');
							alertify.set('notifier', 'position', 'top-center');
							alertify.notify('Se Borro la Conexion', 'success', 3);
							$.ajax({
								type: "POST",
								url: "conexiones.php",
								success: function (response) {
									$('#contenido').html(response);
								}
							});
						} else {
							alertify.set('notifier', 'position', 'top-center');
							alertify.notify('No se pudo Borrar la Conexion', 'error', 3);
						}
					});
				} else {
					alertify.set('notifier', 'position', 'top-center');
					alertify.notify('No pueden haber campos vacios.', 'error', 3);
				}
			}
		}

		function mostrarformulario() {
			$("#FormularioConexiones").modal('show');
		}

		$(function () {
			$('#search').quicksearch('table tbody tr');
		});

		$("#GuardarConexion").click(function (e) {
			e.preventDefault();
			var anio = $("#anios").val();
			var ruta = $("#ruta").val();
			var id = $("#idreg").val();
			if (!$.isEmptyObject(anio) && !$.isEmptyObject(ruta) && !$.isEmptyObject(id)) {
				$.post("AgregarConexion.php", {
					anio: anio,
					ruta: ruta,
					id: id
				}, function (r) {
					var obj = JSON.parse(r);
					if (obj.accion == "conregistro" || obj.accion == "actualizo") {
						$("#FormularioConexiones").modal('hide');
						alertify.set('notifier', 'position', 'top-center');
						alertify.notify(obj.accion == "conregistro" ? 'Se Agrego la Conexion' : 'Se Actualizo Correctamente la Conexion', 'success', 3);
						$.ajax({
							type: "POST",
							url: "conexiones.php",
							success: function (response) {
								$('#contenido').html(response);
							}
						});
					}
				});
			} else {
				alertify.set('notifier', 'position', 'top-center');
				alertify.notify('No pueden haber campos vacios.', 'error', 3);
			}
		});
	</script>
</body>
</html>
