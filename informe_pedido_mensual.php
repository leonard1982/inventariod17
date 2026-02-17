<?php
date_default_timezone_set('America/Bogota');
session_start();
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';
require("conecta.php");
//******************************************************************************************************************
$bd           = '';
$ip           = '127.0.0.1';
$varchivo     = "bd_admin.txt";
$vprefijos    = "";
$varchivopj = "";
$vbd_actual = "";
$vbd_anterior = "";
$vbd_inventarios = "";

$drives = range('A', 'Z');
foreach ($drives as $drive) {
	$path = $drive . ":/facilweb/htdocs/evento_inventario/";
	if (file_exists($path . "prefijos.txt")) {
		$varchivopj = $path . "prefijos.txt";
		break;
	}
}

foreach ($drives as $drive) {
	$path = $drive . ":/facilweb/htdocs/evento_inventario/";
	if (file_exists($path . "bd_actual.txt")) {
		$vbd_actual = $path . "bd_actual.txt";
		break;
	}
}

foreach ($drives as $drive) {
	$path = $drive . ":/facilweb/htdocs/evento_inventario/";
	if (file_exists($path . "bd_anterior.txt")) {
		$vbd_anterior = $path . "bd_anterior.txt";
		break;
	}
}

foreach ($drives as $drive) {
	$path = $drive . ":/facilweb/htdocs/evento_inventario/";
	if (file_exists($path . "bd_inventarios.txt")) {
		$vbd_inventarios = $path . "bd_inventarios.txt";
		break;
	}
}
?>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title></title>

	<!-- Scripts CSS -->
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/datatables.min.css">
	<link rel="stylesheet" href="css/bootstrap-clockpicker.css">
	<link rel="stylesheet" href="css/alertify.min.css">
	<link rel="stylesheet" href="fullcalendar/main.css">
	<link rel="stylesheet" href="css/sortable-theme-dark.css" />
	
 


	<!-- Scripts JS -->
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/datatables.min.js"></script>
	<script src="js/bootstrap-clockpicker.js"></script>
	<script src="js/moment-with-locales.js"></script>
	<script src="js/alertify.js"></script>
	<script src="js/jquery.blockUI.js"></script>
	<script src="js/jquery.quicksearch.js"></script>
	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	<script src="js/sortable.min.js"></script>
	
	</head class="bodylog">
	
	<tbody>
	
	<?php
		//VALIDACION BASE ACTUAL
		if(file_exists($vbd_actual))
		{
			$fp = fopen($vbd_actual, "r");
			while (!feof($fp)){
				$vbd_actual = resolverRutaFirebird(fgets($fp));
			}
			fclose($fp);
			
			if(file_exists($vbd_actual))
			{

			}
			else
			{
				echo "NO SE ENCUENTRA LA BASE DE DATOS ACTUAL DE TNS -- ";
			}
		}
		else
		{
			echo "NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE ACTUAL -- ";
		}

		//VALIDACION BASE ANTERIOR
		if(file_exists($vbd_anterior))
		{
			$fp = fopen($vbd_anterior, "r");
			while (!feof($fp)){
				$vbd_anterior = resolverRutaFirebird(fgets($fp));
			}
			fclose($fp);
			
			if(file_exists($vbd_anterior))
			{

			}
			else
			{
				echo "NO SE ENCUENTRA LA BASE DE DATOS ANTERIOR DE TNS -- ";
			}
		}
		else
		{
			echo "NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE ANTERIOR -- ";
		}

		//VALIDACION BASE INVENTARIOS
		if(file_exists($vbd_inventarios))
		{
			$fp = fopen($vbd_inventarios, "r");
			while (!feof($fp)){
				$vbd_inventarios = resolverRutaFirebird(fgets($fp));
			}
			fclose($fp);
			
			if(file_exists($vbd_inventarios))
			{

			}
			else
			{
				echo "NO SE ENCUENTRA LA BASE DE DATOS DE INVENTARIOS -- ";
			}
		}
		else
		{
			echo "NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACION DE LA BASE DE INVENTARIOS -- ";
		}

		//revisamos si existe el archivo de prefijos
		if(file_exists($varchivopj))
		{
			$fpj = fopen($varchivopj, "r");
			while (!feof($fpj)){
				$vprefijos = fgets($fpj);
			}
			fclose($fpj);
			
			if(empty($vprefijos))
			{
				echo "NO SE HAN CONFIGURADO PREFIJOS -- ";
			}
		}
		else
		{
			echo "NO SE ENCUENTRA EL ARCHIVO DE CONFIGURACIÓN DE PREFIJOS -- ";
		}

		//hacemos conexion a base de datos del año pasado
		$conect_bd_anterior = new dbFirebirdPDO($ip,$vbd_anterior);
		$conect_bd_actual   = new dbFirebirdPDO($ip,$vbd_actual);
		fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' INGRESO EN LA OPCION (INFORME PEDIDO ACTUAL) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$vbd_actual);
	?>
		<center>
			<h4>INFORME DE PEDIDO ACTUAL</h4>
			
				<div class="input-group" style="justify-content: center;">
					<div  style="align:left;margin-right:5px;">
						<label ></label>
						<h4>Fecha de Corte: <?php echo date('d-m-Y H:i'); ?></h4>
					</div>
					
					<div style="width:300px;margin-left:10px;">
						<label ></label>
						<input type="text" id="search" class="form-control" placeholder="Escribe para buscar..." />
					</div>
					
					<div style="margin-left:1px;">
					
						<label for="grupo">Grupo:</label>
						<select class="form-select" name="grupo" id="grupo" >
							<option value="0" selected="selected">TODOS</option>
							<option value="1">MOTOS</option>
							<option value="2">REPUESTOS</option>
						 <?php
							
								/*$vsql = "select grupmatid,codigo,descrip from grupmat where CHAR_LENGTH(codigo)=8 and grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')";
								if($co2 = $conect_bd_actual->consulta($vsql))
								{
									
									while($r2 = $co2->fetch(PDO::FETCH_OBJ))
									{
										 
									?>
										<option value="<?php echo $r2->GRUPMATID; ?>"><?php echo $r2->CODIGO.'--'.utf8_encode($r2->DESCRIP); ?></option>
									<?php	
									}
								}*/
								
						 ?>
						</select>
					</div>
					<div>
						<label for="grupo">Linea:</label>
						<select class="form-select" name="linea" id="linea" >
							<option value="0">TODAS</option>
						 <?php
							
								$vsql = "select lineamatid,codigo,descrip from lineamat";
								if($cox = $conect_bd_actual->consulta($vsql))
								{
									
									while($rx = $cox->fetch(PDO::FETCH_OBJ))
									{
										 
									?>
										<option value="<?php echo $rx->LINEAMATID; ?>"><?php echo $rx->CODIGO.'--'.utf8_encode($rx->DESCRIP); ?></option>
									<?php	
									}
								}
								
						 ?>
						</select>
					</div>
					<div style="margin-left:10px;">
						<label for="reg">No. Registros:</label>
						<input type='number' class='form-control' id='reg'  name='reg' value='10'  style='text-align:right;width: 100px;'/>
					</div>

					<div style="margin-left:10px;">
						<label for=""></label><br>
						<input type='button' id='actualizar' class='btn btn-success' style='float:right;margin-right:10px;' value='Generar'>
					</div>	
					
					<div style="margin-left:5px;">
						<label for=""></label><br>
						<button  type="button" class="btn btn-primary" id="btnExport" >Excel</button>
					</div>
					
				</div>
			

		</center>
		<br>
		
		<h3>Lista de productos donde la existencia es menor o igual al punto de pedido</h3>;
		<div id="contenidoinforme">


		</div>

	</tbody>
</html>

<script>

	$("#btnExport").click(function(e){
			
		e.preventDefault();
			
		var reg = $('#reg').val();
		var grupo = $('#grupo').val();
		var linea = $('#linea').val();
			
			
		window.open("informe_pedido_mensual_excel.php?reg="+reg+"&grupo="+grupo+"&linea="+linea+" ","ventana1","width=1200,height=600,scrollbars=NO");
			
	});

	
	$('#actualizar').on('click', function(){
		
		var opcion = confirm('Este Reporte puede tardar un poco,desea continuar?');

		if(opcion) {
			$('.bodyp').block({ 
				message:'Cargando',
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
			
			var grupo = $('#grupo').val();
			var reg = $('#reg').val();
			var linea = $('#linea').val();
			console.log(grupo+reg);
			$.ajax({
				type: "POST",
				url: "informe_pedido_mensual_detalle.php",
				data: {"grupo" :grupo,"reg":reg,"linea":linea },
				success: function(response) {
					$('#contenidoinforme').html(response);
					$('.bodyp').unblock();
				}
			});
		}

		
	});
	
	
	$(function () {
		
	$('#search').quicksearch('table tbody tr');								
});


</script>
