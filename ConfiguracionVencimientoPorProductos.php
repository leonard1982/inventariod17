<?php require("conecta.php"); ?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Configuración Vencimiento por Grupos</title>
	<!-- Llamamos las librerias css y js -->
	<?php includeAssets(); ?>

	<script>
		$(document).ready(function() {
			$('#grupos').select2({
				placeholder: 'Seleccione un grupo',
				allowClear: true,
				dropdownParent: $('#FormularioConexiones')
			});
		});
	</script>
</head>
<body>
	<?php

	$v_contrato = "";
	$v_idcontrato = "";
	$v_numero = "";

	fCrearLogTNS($_SESSION["user"], 'EL USUARIO ' . $_SESSION["user"] . ' INGRESO EN LA OPCION (Configuración Vencimiento por Grupos) DEL MENU DE INVENTARIOS_AUTO', $contenidoBdActual);

	$vsql = "SELECT vg.id, g.descrip as grupo, vg.meses_vencimiento, vg.grupmatid, g.codigo FROM sn_inv_vence_grupo vg inner join grupmat g on vg.grupmatid=g.grupmatid order by vg.id desc";
	if ($vc = $conect_bd_actual->consulta($vsql)) {
	?>
		<div class="table-responsive">
			<center>
				<h4>Configuración Vencimiento por Grupos</h4>
				<div class="input-group" style="justify-content: center;">
					<div style="width:300px;">
						<input type="text" id="search" class="form-control" placeholder="Escribe para buscar..." />
					</div>
					<div style="margin-left:10px;">
						<button type="button" id="nuevaconexion" class="btn btn-primary" style="margin-left: 10px;" onclick="mostrarformularionuevo();">Nuevo</button>
					</div>
				</div>
				<br>
				<table class="table table-striped table-bordered" style="align:center;width:800px;">
					<thead>
						<tr>
							<th><center>#</center></th>
							<th><center>GRUPO</center></th>
							<th><center>MESES VENCE</center></th>
						</tr>
					</thead>
					<tbody id="cuerpo">
						<?php
						$v_cont = 0;
						while ($vr = ibase_fetch_object($vc)) {
						?>
							<tr>
								<td style="text-align:center;">
									<img style="cursor:pointer;" src="imagenes/lapiz.png" onclick="Feditar(<?php echo $vr->ID; ?>,'<?php echo $vr->GRUPMATID; ?>','<?php echo $vr->MESES_VENCIMIENTO; ?>');">
									<img style="cursor:pointer;" src="imagenes/papelera.png" onclick="Fborrar('<?php echo $vr->ID; ?>','<?php echo $vr->GRUPMATID; ?>','<?php echo $vr->MESES_VENCIMIENTO; ?>');">
									<img style="cursor:pointer;" src="imagenes/email.png" onclick="FNotificar('<?php echo $vr->ID; ?>','<?php echo $vr->GRUPMATID; ?>','<?php echo $vr->MESES_VENCIMIENTO; ?>');">
								</td>
								<td style="text-align:left;"><?php echo utf8_encode($vr->CODIGO." - ".$vr->GRUPO); ?></td>
								<td style="text-align:right;"><?php echo $vr->MESES_VENCIMIENTO; ?></td>
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
						<h4 class="modal-title">Nueva Configuración</h4>
						<button type="button" class="btn-close" data-bs-dismiss="modal"></button>
					</div>
					<div class="modal-body">
						<input type="hidden" id="idreg" value="0" class="form-control">
						<div class="row">
							<div class="col">
								<label for="">Grupo:</label>
								<br>
								<select class="form-select" name="grupos" id="grupos">
									<?php
									$vsql = "SELECT grupmatid, descrip, codigo FROM grupmat WHERE CHAR_LENGTH(codigo) = 8 AND grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '00.%') ORDER BY codigo";
									if($vconsulta = $conect_bd_actual->consulta($vsql))
									{
										while($vregistro = ibase_fetch_object($vconsulta))
										{
											echo "<option value='".$vregistro->GRUPMATID."'>".utf8_encode($vregistro->CODIGO." - ".$vregistro->DESCRIP)."</option>";
										}
									}
									?>
								</select>
							</div>
						</div>
						<div class="row">
							<div class="col">
								<label for="">Meses Vencimiento:</label>
								<input type="number" id="meses_vencimiento" value="" class="form-control">
							</div>
						</div>
					</div>
					<div class="modal-footer">
						<button type="button" id="Guardar" class="btn btn-success">Guardar</button>
						<button type="button" class="btn btn-success" data-bs-dismiss="modal">Cancelar</button>
					</div>
				</div>
			</div>
		</div>
	<?php
	}
	?>
	<script>
		function Feditar(id, grupo, meses) {
			$("#FormularioConexiones").modal('show');
			$('#grupos').select2({
				placeholder: 'Seleccione un grupo',
				allowClear: true,
				dropdownParent: $('#FormularioConexiones')
			});
			$("#grupos").val(grupo);
			$("#meses_vencimiento").val(meses);
			$("#idreg").val(id);
		}

		function Fborrar(id, grupo, meses) {
			var opcion = confirm('Desea Eliminar la Conexion?');
			if (opcion) {
				if (!$.isEmptyObject(id)) 
				{
					$.post("BorrarConfiguracionVencimientoPorProducto.php", {
						grupo: grupo,
						meses: meses,
						id: id
					}, function (r) {
						var obj = JSON.parse(r);
						if (obj.accion == "borro") {
							$("#FormularioConexiones").modal('hide');
							alertify.set('notifier', 'position', 'top-center');
							alertify.notify('Se Borro la Conexion', 'success', 3);
							$.ajax({
								type: "POST",
								url: "ConfiguracionVencimientoPorProductos.php",
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
					alertify.notify('No pueden haber campos vacios...', 'error', 3);
				}
			}
		}

		function FNotificar(id, grupo, meses) {
			var opcion = confirm('Desea notificar los vencimientos?');
			if (opcion) {
				if (!$.isEmptyObject(id)) 
				{
					$.post("NotificarConfiguracionVencimientoPorProducto.php", {
						grupo: grupo,
						meses: meses,
						id: id
					}, function (r) {
						console.log(r);
						var obj = JSON.parse(r);
						if (obj.accion == "notifico") {
							$("#FormularioConexiones").modal('hide');
							alertify.set('notifier', 'position', 'top-center');
							alertify.notify('Se envió la notificación al correo', 'success', 3);
							$.ajax({
								type: "POST",
								url: "ConfiguracionVencimientoPorProductos.php",
								success: function (response) {
									$('#contenido').html(response);
								}
							});
						} else {
							alertify.set('notifier', 'position', 'top-center');
							alertify.notify('No se pudo enviar la notificación al correo: '+obj.accion, 'error', 3);
						}
					});
				} else {
					alertify.set('notifier', 'position', 'top-center');
					alertify.notify('No pueden haber campos vacios...', 'error', 3);
				}
			}
		}

		function mostrarformulario() {
			$("#FormularioConexiones").modal('show');
			$('#grupos').select2({
				placeholder: 'Seleccione un grupo',
				allowClear: true,
				dropdownParent: $('#FormularioConexiones')
			});
		}

		function mostrarformularionuevo() {
			$("#grupos").val('').trigger('change');
			$("#meses_vencimiento").val('');
			$("#idreg").val('0');
			$("#FormularioConexiones").modal('show');

			$('#grupos').select2({
				placeholder: 'Seleccione un grupo',
				allowClear: true,
				dropdownParent: $('#FormularioConexiones')
			});
		}

		$(function () {
			$('#search').quicksearch('table tbody tr');
		});

		$("#Guardar").click(function (e) {
			e.preventDefault();
			var grupo = $("#grupos").val();
			var meses = $("#meses_vencimiento").val();
			var id = $("#idreg").val();
			if (!$.isEmptyObject(grupo) && !$.isEmptyObject(meses) && !$.isEmptyObject(id)) {
				$.post("AgregarConfiguracionVencimientoPorProducto.php", {
					grupo: grupo,
					meses: meses,
					id: id
				}, function (r) {
					var obj = JSON.parse(r);
					if (obj.accion == "conregistro" || obj.accion == "actualizo") {
						$("#FormularioConexiones").modal('hide');
						alertify.set('notifier', 'position', 'top-center');
						alertify.notify(obj.accion == "conregistro" ? 'Registro creado' : 'Registro actualizado', 'success', 3);
						$.ajax({
							type: "POST",
							url: "ConfiguracionVencimientoPorProductos.php",
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
		$("#meses_vencimiento").keypress(function (e) {
			if (e.which == 13) {
				$("#Guardar").click();
			}
		});
		$("#grupos").on('select2:select', function (e) {
			$("#meses_vencimiento").focus();
		});
	</script>
</body>
</html>
