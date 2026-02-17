<!DOCTYPE html>
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';
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
	<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">
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
//echo '<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">';
echo "<div class='' style='overflow:auto;width:100%;'>";
?>
<!--<center><button  type="button" class="btn btn-primary" id="btnExport" onclick="exportTableToExcel('dato_productos');">Exportar Excel</button></center> -->
<?php
echo "<br>";
?>
<style>
td{
	text-align:right;
}
thead tr td { 
	position: sticky;
	top: 0;
	z-index: 10;
}
</style>

<script>
/*function doSearch(e)
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
}*/
</script>
<?php

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
	$path = $drive . ":/facilweb_fe73_32/htdocs/evento_inventario/";
	if (file_exists($path . "prefijos.txt")) {
		$varchivopj = $path . "prefijos.txt";
		break;
	}
}

foreach ($drives as $drive) {
	$path = $drive . ":/facilweb_fe73_32/htdocs/evento_inventario/";
	if (file_exists($path . "bd_actual.txt")) {
		$vbd_actual = $path . "bd_actual.txt";
		break;
	}
}

foreach ($drives as $drive) {
	$path = $drive . ":/facilweb_fe73_32/htdocs/evento_inventario/";
	if (file_exists($path . "bd_anterior.txt")) {
		$vbd_anterior = $path . "bd_anterior.txt";
		break;
	}
}

foreach ($drives as $drive) {
	$path = $drive . ":/facilweb_fe73_32/htdocs/evento_inventario/";
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
		$vbd_actual = addslashes(fgets($fp));
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
		$vbd_anterior = addslashes(fgets($fp));
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
		$vbd_inventarios = addslashes(fgets($fp));
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

//CREAMOS RUTINA PARA SABER EN QUE MES ESTAMOS
$vmes = date("m");
$vmes_inicial = $vmes;
$vporcentaje_seguridad = 0;
$vtiempo_entrega       = 0;
$vdias_laborados       = 0;
$vdias_inventario      = 0;
$vsumatotal            = 0;
$vexistenciafinal      = 0;

//para pruebas
$vperiodo = 4;//abril porque es el periodo que el ultimo periodo en la base de prueba de inventarios que tenga movimientos
$vultima_fecha_laborada = "";
$vultima_fecha_menos_30 = "";

//ULTIMA FECHA LABORADA
$vsql = "SELECT FIRST 1 FECHA FROM KARDEX WHERE CODCOMP='FV' AND FECASENTAD IS NOT NULL AND FECANULADO IS NULL ORDER BY FECHA DESC";
if($co4 = $conect_bd_actual->consulta($vsql))
{
	if($r4 = $co4->fetch(PDO::FETCH_OBJ))
	{
		$fecha = substr($r4->FECHA, 0, -9);
		$fecha = date_create($fecha);
		$fecha = date_format($fecha,'Y-m-d');
		$vultima_fecha_laborada = $fecha;
		
		$date_now  = $vultima_fecha_laborada;
		$date_past = strtotime('-30 day', strtotime($date_now));
		$vultima_fecha_menos_30 = date('Y-m-d', $date_past);
		
		//buscamos el periodo anterior al ultimo movimiento
		$fecha2   = date_create($vultima_fecha_menos_30);
		$vperiodo = date_format($fecha2,'m');
		$vmes_inicial = intval($vperiodo);
		$vmes     = $vmes_inicial-1;
	}
}

if(isset($_POST['cant'])){
	$v_cantidad = $_POST['cant'];
	$v_registros = $_POST['reg'];
	$v_grupo     = $_POST['grupo'];
	$v_linea    = $_POST['linea'];
	//si es mes es menor a junio consultamos también la base de datos anterior

	//echo "entro";
	$vcount = 0;
	//RECOREMOS TODOS LOS PRODUCTOS QUE NO ESTÁN EN EL GRUPO DE SERVICIOS
	//$vsql = "select first 100 m.matid,m.codigo,m.descrip,g.codigo as codgrupo from material m inner join grupmat g on m.grupmatid=g.grupmatid Where g.grupmatid>(select gg.grupmatid from grupmat gg where gg.codigo='00.00.00') and m.codigo in('YC110D-23NEG','9079END03000')";	
	if($v_grupo>0)
	{
		if($v_linea>0){
			$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia,l.descrip as linea 
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			inner join lineamat as l on (m.lineamatid=l.lineamatid)
			Where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' and m.lineamatid='".$v_linea."'";
		}else{
			$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia,l.descrip as linea  
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			inner join lineamat as l on (m.lineamatid=l.lineamatid)
			Where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."'";
		}
	}else{
		if($v_linea>0){
			$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia,l.descrip as linea  
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			inner join lineamat as l on (m.lineamatid=l.lineamatid)
			Where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and m.lineamatid='".$v_linea."'";
		}else{
			$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia,l.descrip as linea  
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			inner join lineamat as l on (m.lineamatid=l.lineamatid)
			Where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')";
		}	
	}	
		
	if($co = $conect_bd_actual->consulta($vsql))
	{
		while($r = $co->fetch(PDO::FETCH_OBJ))
		{
			if($vcount==0)
			{
				//ponemos los titulos
				?>
				<!--<table border='1' id='dato_productos' data-sortable class='table' style='margin-left:2px;margin-right:2px;width:99%;'>-->
				<table class="table table-striped table-bordered" border='1' data-sortable  style='margin-left:2px;margin-right:2px;width:99%;' id='dato_productos'>
				<thead>
						
					<th style='color:white;background:black;text-align:left;'>FAMILIA</th>
					<th style='color:white;background:black;text-align:left;'>LINEA</th>
					<th style='color:white;background:black;text-align:left;'>CODIGO</th>
					<th style='color:white;background:black;text-align:left;'>DESCRIPCION</th>
					<th style='color:white;background:black;text-align:left;'>VALOR</th>
				</thead>
				<tbody>
				<?php
			}//fin $vcount
			
			$vcantidades_compradas = 0;
			$vvalor  = 0;
			
			if($v_cantidad>$vmes)
			{

				$vsql = "select cast(sum(IIF(k.codcomp='FV',d.canmat,0)*d.preciobase) as char(15)) valor, cast(sum(IIF(k.codcomp='FC',d.canmat,0)) as char(15)) cantidades_comp
				from dekardex d inner join kardex k on d.kardexid=k.kardexid
				inner join material m on d.matid=m.matid
				where k.codcomp in('FC','FV') and k.fecasentad is not null and m.codigo like '".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)>='".$vmes_inicial."'";
				
				
				//echo $vsql."<br>";
				
				//sumamos el años anterior	
				if($co2 = $conect_bd_anterior->consulta($vsql))
				{
					if($r2 = $co2->fetch(PDO::FETCH_OBJ))
					{
						$vcantidades_compradas += floatval($r2->CANTIDADES_COMP);
						$vvalor += floatval($r2->VALOR);
					}//fin segundo while
				}//fin consulta sumado
			}
			
			if($v_cantidad>=$vmes)
			{
				$vsql = "select cast(sum(IIF(k.codcomp='FV',d.canmat,0)*d.preciobase) as char(15)) valor, cast(sum(IIF(k.codcomp='FC',d.canmat,0)) as char(15)) cantidades_comp
				from dekardex d inner join kardex k on d.kardexid=k.kardexid
				inner join material m on d.matid=m.matid
				where k.codcomp in('FC','FV') and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)<='".$vmes."'";
			}
			else
			{
				$vsql = "select cast(sum(IIF(k.codcomp='FV',d.canmat,0)*d.preciobase) as char(15)) valor, cast(sum(IIF(k.codcomp='FC',d.canmat,0)) as char(15)) cantidades_comp
				from dekardex d inner join kardex k on d.kardexid=k.kardexid
				inner join material m on d.matid=m.matid
				where k.codcomp in('FC','FV') and k.fecasentad is not null and m.codigo like '".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)>='".$v_cantidad."'";
			}
			
			//sumamos el año actual
			if($co3 = $conect_bd_actual->consulta($vsql))
			{
				if($r3 = $co3->fetch(PDO::FETCH_OBJ))
				{
				?>
					<tr>
						<td style='text-align:left;'><?php echo utf8_encode($r->LINEA);?></td>
						<td style='text-align:left;'><?php echo utf8_encode($r->FAMILIA);?></td>
						<td style='text-align:left;'><?php echo  utf8_encode($r->CODIGO);?></td>
						<td style='text-align:left;'><?php echo  utf8_encode($r->DESCRIP);?></td>
						<?php
						$vvalor += floatval($r3->VALOR);
						?>
						<td style='text-align:left;'><?php echo  number_format($vvalor);?></td>
					</tr>
				<?php	
				}//fin segundo while
			}//fin consulta sumado
			
			$vcount++;
		}//fin while
		?>
			</tbody>
			</table>
		<?php
	}//fin recorrer productos
}	
echo "</div>";
?>
<script>

function exportTableToExcel(tableID, filename = ''){
	
	console.log("entro");
	
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'ABC_Rotacion.xls';
    
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

</script>