<!DOCTYPE html>
<?php
require("conecta.php");
?>
<html lang="es" dir="ltr">
  	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Estados</title>
		<!--Llamamos las librerias css y js -->
		<?php includeAssets(); ?>
	</head>
<?php

	$v_contrato="";
	$v_idcontrato="";
	$v_numero="";
	fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' INGRESO EN LA OPCION (ESTADOS PEDIDOS) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$contenidoBdActual);
	$vsql = "select id,codigo,descripcion,orden from estados_pedidos";
	if($vc = $conect_bd_inventario->consulta($vsql))
	{
		
		?>
		<div class="table-responsive">
			<center>
			<h4>ESTADOS</h4>
			<div class="input-group" style="justify-content: center;">
					<div style="width:300px;">
						<input type="text" id="search" class="form-control" placeholder="Escribe para buscar..." />
					</div>
					
					<div style="margin-left:10px;">
						<button type="button"  id="nuevoestado" class="btn btn-primary" style="margin-left: 10px;" onclick="mostrarformulario();">Nuevo</button>
						
					</div>
			</div>
			<br>
			<table class="table table-striped table-bordered" style="align:center; width:1000px; height:300px;">
				<thead>
				<th><center>OPCIONES</center></th>
				<th><center>CODIGO</center></th>
				<th><center>DESCRIPCION</center></th>
				<th><center>ORDEN</center></th>
				</thead>
				<tbody id="cuerpo">
				
		<?php	
				$v_cont=0;
				while($vr = ibase_fetch_object($vc))
				{
					
					?>
					
					<tr>
						<td style="text-align:center;">
							<img style="cursor:pointer;" src="imagenes/lapiz.png" onclick="Feditar(<?php echo $vr->ID; ?>,'<?php echo $vr->CODIGO; ?>','<?php echo $vr->DESCRIPCION; ?>',<?php echo $vr->ORDEN; ?>); " >
							<img style="cursor:pointer;" src="imagenes/papelera.png" onclick="Fborrar(<?php echo $vr->ID; ?>,'<?php echo $vr->CODIGO; ?>','<?php echo $vr->DESCRIPCION; ?>',<?php echo $vr->ORDEN; ?>); " >
						</td>
						<td style="text-align:center;">
							<?php echo $vr->CODIGO; ?>
						</td>
						<td style="text-align:left;">
							<?php echo $vr->DESCRIPCION; ?>
						</td>
						<td style="text-align:right;">
							<?php echo $vr->ORDEN; ?>
						</td>
						
					</tr>
					
					<?php
						
				}
		?>
				</tbody>
			</table>
			</center>
		</div>

		<!-- Formulario estados -->
	<div class="modal fade" id="FormularioEstados" tabindex="-1" role="dialog">
		<div class="modal-dialog" role="document">
		<div class="modal-content">
			<div class="modal-header">
			<h4 class="modal-title">Nuevo Estado</h4>
			<button type="button" class="btn-close" data-bs-dismiss="modal">
			</button>
			</div>

		<div class="modal-body">
			
			<input type="hidden" id="idreg" value="0" class="form-control">
			<div class="row">
				<div class="col">
				<label for="">Codigo:</label>
				<input type="text" id="codigo" value="" class="form-control">
				</div>
			</div> 
			
			<div class="row">
				<div class="col">
				<label for="">Descripcion:</label>
				<input type="text" id="descripcion" value="" class="form-control">
				</div>
			</div> 

			<div class="row">
				<div class="col">
				<label for="">Orden:</label>
				<input type="number" min="1" step="1" id="orden" value="" class="form-control" onkeydown="filtro();">
				</div>
			</div> 

		</div>  

			<div class="modal-footer">
			<button type="button" id="GuardarEstado" class="btn btn-success">Guardar</button>
			<button type="button" class="btn btn-success" data-bs-dismiss="modal">Cancelar</button>
			</div>

		</div>
		</div>
	</div>


		<?php
	}
		
?>	
<script>
	
	
	function filtro()
	{
		console.log("entro");
		var tecla = event.key;
		if (['.','e'].includes(tecla)){
			event.preventDefault();
		}	
	}
	
	function Feditar(id,codigo,descripcion,orden){
				
				var opcion = confirm('Desea Editar el Estado?');

				if(opcion) {
					
					$("#FormularioEstados").modal('show');
					$("#codigo").val(codigo);
					$("#descripcion").val(descripcion);
					$("#idreg").val(id);
					$("#orden").val(orden);
				}
	}
	
	function Fborrar(id,codigo,descripcion,orden){
								
		var opcion = confirm('Desea Eliminar el Estado?');
		
		
		
		if(opcion) {

			if( !$.isEmptyObject(codigo)) 
			{
			   console.log("entro");
			   $.post("BorrarEstado.php",{

			   	    codigo: codigo,
					descripcion : descripcion,
					id   : id,
					orden : orden

				},function(r){

					console.log(r);
					var obj = JSON.parse(r);

					if(obj.accion=="borro")
					{
						$("#FormularioEstados").modal('hide');
						alertify.set('notifier','position', 'top-center');
						var notification = alertify.notify('Se Borro el Estado', 'success', 3, function(){  console.log('dismissed'); });
						$.ajax({
							type: "POST",
							url: "estados_pedidos.php",
							success: function(response) {
							$('#contenido').html(response);
							}
						});
					}else{
						alertify.set('notifier','position', 'top-center');
						var notification = alertify.notify('No se pudo Borrar el Estado', 'error', 3, function(){  console.log('dismissed'); });
								
					}
				});
			}	
			else
			{
				alertify.set('notifier','position', 'top-center');
				var notification = alertify.notify('No pueden haber campos vacios.', 'error', 3, function(){  console.log('dismissed'); });
							
			}
		}
	}
	

	function mostrarformulario(){
		$("#FormularioEstados").modal('show');
		
	}

	$(function () {
		
		$('#search').quicksearch('table tbody tr');								
	});


	$("#GuardarEstado").click(function(e){
						e.preventDefault();
						
						var codigo					= $("#codigo").val();
						var descripcion				= $("#descripcion").val();
						var id						= $("#idreg").val();
						var orden					= $("#orden").val();
						
						if( !$.isEmptyObject(codigo) && !$.isEmptyObject(descripcion) && !$.isEmptyObject(id) && !$.isEmptyObject(orden) ) 
						{
							console.log("entro");
						   $.post("AgregarEstado.php",{

								 codigo : codigo,
								 descripcion : descripcion,
								 id   : id,
								 orden : orden

						   },function(r){

								console.log(r);
								var obj = JSON.parse(r);

								if(obj.accion=="conregistro")
								{
									$("#FormularioEstados").modal('hide');
									alertify.set('notifier','position', 'top-center');
									var notification = alertify.notify('Se Agrego el Estado', 'success', 3, function(){  console.log('dismissed'); });
									$.ajax({
										type: "POST",
										url: "estados_pedidos.php",
										success: function(response) {
											$('#contenido').html(response);
										}
									});
								}
								
								if(obj.accion=="actualizo")
								{
									$("#FormularioEstados").modal('hide');
									alertify.set('notifier','position', 'top-center');
									var notification = alertify.notify('Se Actualizo Correctamente el Estado', 'success', 3, function(){  console.log('dismissed'); });
									$.ajax({
										type: "POST",
										url: "estados_pedidos.php",
										success: function(response) {
											$('#contenido').html(response);
										}
									});
								}
						   });
						}
						else
						{
							alertify.set('notifier','position', 'top-center');
							var notification = alertify.notify('No pueden haber campos vacios.', 'error', 3, function(){  console.log('dismissed'); });
							
						}
						
	});



</script>	

</html>