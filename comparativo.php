<!DOCTYPE html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
date_default_timezone_set('America/Bogota');
session_start();

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';

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

	</head>
<?php
	echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">';
	echo "<div class='' style='overflow:auto;width:100%;'>";
	echo "<center>";
	echo "<br>";
	echo "<h2>COMPARATIVO DE UNIDADES VENDIDAS ENTRE PERIODOS</h2>";
	echo "</center>";
	echo "<table border='0' style='margin-left:5px;'>";
	echo "<tr>";
	echo "<td style='width:300px;'><div style='text-align:left;'><label for='searchTerm'>Buscar:</label></div><form><input id='searchTerm' type='text' onkeyup='doSearch(event);' class='form-control' placeholder='Buscar'/></form></td>";

?>		
	
		<form action='' method='POST'>
			<td>
				<div style='text-align:left;'><label for="grupo">Grupo/Familia</label></div>
				<select class="form-select" name="grupo" id="grupo" >
					<option value="0" selected="selected">TODOS</option>
					<option value="1">MOTOS</option>
					<option value="2">REPUESTOS</option>
				 <?php
					
					/*	$vsql = "select grupmatid,codigo,descrip from grupmat where CHAR_LENGTH(codigo)=8 and  grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')";
						if($cox = $conect_bd_actual->consulta($vsql))
						{
							
							while($rx = $cox->fetch(PDO::FETCH_OBJ))
							{
								 
							?>
								<option value="<?php echo $rx->GRUPMATID; ?>"><?php echo $rx->CODIGO.'--'.utf8_encode($rx->DESCRIP); ?></option>
							<?php	
							}
						}
						*/
				 ?>
				</select>
			</td>
			<td>
				<div style='text-align:left;'><label for="linea">Linea</label></div>
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
			</td>
		
			<td><div style='text-align:left;'><label for="cant">Meses:</label></div><input type='number' class='form-control' id='cant'  name='cant' value='12' max='12' style='text-align:right;width: 100px;'/></td>
			<td><div style='text-align:left;'><label for="reg">No. Registros:</label></div><input type='number' class='form-control' id='reg'  name='reg' value='100'  style='text-align:right;width: 100px;'/></td>
			<td>
				<div style='text-align:left;'><label for="reg"></label></div>
				<div class="input-group">
					<div style='margin-left:10px;'>
						<input type='button' id='actualizar' class='btn btn-success' style='float:right;margin-right:10px;' value='Generar'>
					</div>
					
					<div style='margin-left:10px;'>
						<button  type="button" class="btn btn-primary" id="btnExport" >Exportar Excel</button></center>
					</div>
				</div>	
			</td>
		</form>
		
<?php
echo "</tr>";
echo "</table>";
//echo "<br>";
?>
<style>
td{
	text-align:right;
}
thead tr th { 
	position: sticky;
	top: 0;
	z-index: 10;
}
</style>

<body class="bodyc">

	<div id="contenidocomparativo">


	</div>
</body>

</html>	

<script>

function exportTableToExcel(tableID, filename = ''){
	
	console.log("entro");
	
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'Comparativo.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}

$("#btnExport").click(function(e){
			
			e.preventDefault();
			
			var reg = $('#reg').val();
			var grupo = $('#grupo').val();
			var linea = $('#linea').val();
			var cant = $('#cant').val();
			
			window.open("comparativo_excel.php?reg="+reg+"&grupo="+grupo+"&linea="+linea+"&cant="+cant+" ","ventana1","width=1200,height=600,scrollbars=NO");
			
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
			
			var cant = $('#cant').val();
			var reg = $('#reg').val();
			var grupo = $('#grupo').val();
			var linea = $('#linea').val();
			
			$.ajax({
				type: "POST",
				url: "comparativo1.php",
				data: {"cant" :cant,"reg":reg,"grupo":grupo,"linea":linea},
				success: function(response) {
					$('#contenidocomparativo').html(response);
					$('.bodyp').unblock();
				}
			});
		}

		
});



function doSearch(e)
{

	var code = (e.keyCode ? e.keyCode : e.which);

	if(code == 13) {

		return false;

	}else{

		var tableReg = document.getElementById('dato_productos');
		var searchText = document.getElementById('searchTerm').value.toLowerCase();
		var cellsOfRow="";
		var found=false;
		var compareWith="";

		// Recorremos todas las filas con contenido de la tabla
		for (var i = 1; i < tableReg.rows.length; i++)
		{
			cellsOfRow = tableReg.rows[i].getElementsByTagName('td');
			found = false;
			// Recorremos todas las celdas
			for (var j = 0; j < cellsOfRow.length && !found; j++)
			{
				compareWith = cellsOfRow[j].innerHTML.toLowerCase();
				// Buscamos el texto en el contenido de la celda
				if (searchText.length === 0 || (compareWith.indexOf(searchText) > -1))
				{
					found = true;
				}
			}
			if(found)
			{
				tableReg.rows[i].style.display = '';
			} else {
				// si no ha encontrado ninguna coincidencia, esconde la
				// fila de la tabla
				tableReg.rows[i].style.display = 'none';
			}
		}
	}
}
</script>
