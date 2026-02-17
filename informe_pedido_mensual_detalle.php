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
	
	</head>
	
	<style>
.table-responsive  thead tr th{ 
          position: sticky;
          top: 0;
          z-index: 10;
          background-color: #ffffff;
        }
        
        .table-responsive { 
          height:700px;
          overflow:scroll;
        }
</style>	
	
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

//CREAMOS RUTINA PARA SABER EN QUE MES ESTAMOS
$vmes = date("m");
$vporcentaje_seguridad = 0;
$vtiempo_entrega       = 0;
$vdias_laborados       = 0;
$vdias_inventario      = 0;
$vtendencia_meses      = 0;
$v_ultimo_movimiento="";
$v_cantidad_vendidas =0;

//para pruebas
$vperiodo = 4;//abril porque es el periodo que el ultimo periodo en la base de prueba de inventarios que tenga movimientos
$vultima_fecha_laborada = "";
$vultima_fecha_menos_30 = "";

$vsql = "SELECT ID,PORCENTAJE_SEGURIDAD,TIEMPO_ENTREGA, DIAS_INVENTARIO,TENDENCIA_MESES FROM CONFIGURACIONES WHERE ID='1'";
if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
{
	if($cox = $conect_bd_inventario->consulta($vsql))
	{
		if($rx = $cox->fetch(PDO::FETCH_OBJ))
		{
			$vporcentaje_seguridad = $rx->PORCENTAJE_SEGURIDAD;
			$vtiempo_entrega       = $rx->TIEMPO_ENTREGA;
			$vdias_inventario      = $rx->DIAS_INVENTARIO;
			$vtendencia_meses      = (int)$rx->TENDENCIA_MESES;
		}
	}
}

if(isset($_POST["grupo"])){
	 echo "<script>console.log('entro');</script>";
	$v_grupo=$_POST["grupo"];
	$v_reg = $_POST["reg"];
	$v_linea = $_POST["linea"];

	fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' GENERO EL INFORME DE LA OPCION (INFORME PEDIDO ACTUAL) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$vbd_actual);

	if($v_grupo>0){
			
		//MOTOS
		if($v_grupo==1){
			if($v_linea>0){
				
				
				$vsql="select first ".$v_reg." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,coalesce(cast(ms.existenc as char(15)),0) as existencia,ms.sn_punto_pedido,ms.sn_stock_maximo,cast(coalesce(ms.precultprov,0) as char(15)) as precultprov
						from material m
						inner join grupmat g on m.grupmatid=g.grupmatid
						inner join materialsuc as ms on m.matid=ms.matid
						where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.01.' AND '01.01.59') and m.lineamatid='".$v_linea."' and ms.sn_punto_pedido is not null and ms.existenc<=ms.sn_punto_pedido order by cast(ms.sn_stock_maximo as float) desc";
				
			}else{
				
				
				$vsql="select first ".$v_reg." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,coalesce(cast(ms.existenc as char(15)),0) as existencia,ms.sn_punto_pedido,ms.sn_stock_maximo,cast(coalesce(ms.precultprov,0) as char(15)) as precultprov
						from material m
						inner join grupmat g on m.grupmatid=g.grupmatid
						inner join materialsuc as ms on m.matid=ms.matid
						where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.01.' AND '01.01.59') and ms.sn_punto_pedido is not null  and ms.existenc<=ms.sn_punto_pedido order by cast(ms.sn_stock_maximo as float) desc";
				
			}
		}

		//REPUESTOS
		if($v_grupo==2){
			if($v_linea>0){
				
				
				$vsql="select first ".$v_reg." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,coalesce(cast(ms.existenc as char(15)),0) as existencia,ms.sn_punto_pedido,ms.sn_stock_maximo,cast(coalesce(ms.precultprov,0) as char(15)) as precultprov
						from material m
						inner join grupmat g on m.grupmatid=g.grupmatid
						inner join materialsuc as ms on m.matid=ms.matid
						where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and m.lineamatid='".$v_linea."' and ms.sn_punto_pedido is not null and ms.existenc<=ms.sn_punto_pedido  order by cast(ms.sn_stock_maximo as float) desc";
				
			}else{
				
				
				$vsql="select first ".$v_reg." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,coalesce(cast(ms.existenc as char(15)),0) as existencia,ms.sn_punto_pedido,ms.sn_stock_maximo,cast(coalesce(ms.precultprov,0) as char(15)) as precultprov
						from material m
						inner join grupmat g on m.grupmatid=g.grupmatid
						inner join materialsuc as ms on m.matid=ms.matid
						where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and ms.sn_punto_pedido is not null and ms.existenc<=ms.sn_punto_pedido order by cast(ms.sn_stock_maximo as float) desc";
				
			}
		}
		//echo $vsql;
	}else{
		if($v_linea>0){
			/*$vsql = "select first ".$v_reg." m.matid,m.codigo,m.descrip,g.codigo as codgrupo 
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			Where g.grupmatid='".$v_grupo."' and m.lineamatid='".$v_linea."'";*/
			
			$vsql="select first ".$v_reg." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,coalesce(cast(ms.existenc as char(15)),0) as existencia,ms.sn_punto_pedido,ms.sn_stock_maximo,cast(coalesce(ms.precultprov,0) as char(15)) as precultprov
					from material m
					inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on m.matid=ms.matid
					where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and m.lineamatid='".$v_linea."' and ms.sn_punto_pedido is not null and ms.existenc<=ms.sn_punto_pedido order by cast(ms.sn_stock_maximo as float) desc";
					
		}else{
		/*	$vsql = "select first ".$v_reg." m.matid,m.codigo,m.descrip,g.codigo as codgrupo 
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			Where g.grupmatid='".$v_grupo."'"; */
			
			$vsql="select first ".$v_reg." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,coalesce(cast(ms.existenc as char(15)),0) as existencia,ms.sn_punto_pedido,ms.sn_stock_maximo,cast(coalesce(ms.precultprov,0) as char(15)) as precultprov
					from material m
					inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on m.matid=ms.matid
					where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and ms.sn_punto_pedido is not null  and ms.existenc<=ms.sn_punto_pedido order by cast(ms.sn_stock_maximo as float) desc";
			
		}
		//echo $vsql;
	}
	if($co4 = $conect_bd_actual->consulta($vsql))
	{
		echo "<div class='table-responsive'>";
		echo "<table border='1' class='table table-striped table-bordered' style='align:center; width:90%;'>";
		echo "<thead>";
		echo "<tr>";
		echo "<th style='color:white;background:black;'></th>";
		echo "<th style='color:white;background:black;'>PRODUCTO</th>";
		echo "<th style='color:white;background:black;'>EXISTENCIA</th>";
		echo "<th style='color:white;background:black;'>PUNTO DE PEDIDO</th>";
		echo "<th style='color:white;background:black;'>STOCK MAXIMO</th>";
		echo "<th style='color:white;background:black;'>U.COSTO</th>";
		echo "</tr>";
		echo "</thead><tbody>";
		
		$vcontador = 1;
		$vtcosto   = 0;
		while($r4 = $co4->fetch(PDO::FETCH_OBJ))
		{
			
			echo "<tr>";
			echo "<td>".$vcontador."</td>";
			echo "<td>".utf8_encode($r4->CODIGO)." -- ".utf8_encode($r4->DESCRIP)."</td>";
			echo "<td>".round($r4->EXISTENCIA)."</td>";
			echo "<td>".round($r4->SN_PUNTO_PEDIDO)."</td>";
			echo "<td>".round($r4->SN_STOCK_MAXIMO)."</td>";
			$vucosto = floatval($r4->PRECULTPROV);
			echo "<td>".number_format($vucosto)."</td>";
			echo "</tr>";
			
			$vtcosto += $vucosto;
			$vcontador++;
			
		}
		echo "<tr><td colspan='5' style='text-align:right;'><b>TOTAL COSTO</b></td><th>".number_format($vtcosto)."</th></tr>";
		echo "</tbody></table><br><br>";
		echo "</div>";
	}
	
	
}	
?>

</tbody>
</html>

<script>

$(function () {
		
	$('#search').quicksearch('table tbody tr');								
});

</script>
