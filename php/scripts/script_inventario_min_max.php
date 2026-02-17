<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once __DIR__ . '/../baseDeDatos.php';
include_once __DIR__ . '/../importarExcel.php';

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
	if (file_exists($path . "bd_actual_produccion.txt")) {
		$vbd_actual = $path . "bd_actual_produccion.txt";
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

//CREAMOS RUTINA PARA SABER EN QUE MES ESTAMOS
$vmes = date("m");
$vporcentaje_seguridad = 0;
$vtiempo_entrega       = 0;
$vdias_laborados       = 0;
$vdias_inventario      = 0;

//para pruebas
$vperiodo = 4;//abril porque es el periodo que el ultimo periodo en la base de prueba de inventarios que tenga movimientos
$vultima_fecha_laborada = "";
$vultima_fecha_menos_30 = "";

$vsql = "SELECT ID,PORCENTAJE_SEGURIDAD,TIEMPO_ENTREGA, DIAS_INVENTARIO FROM CONFIGURACIONES WHERE ID='1'";
if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
{
	if($cox = $conect_bd_inventario->consulta($vsql))
	{
		if($rx = $cox->fetch(PDO::FETCH_OBJ))
		{
			$vporcentaje_seguridad = $rx->PORCENTAJE_SEGURIDAD;
			$vtiempo_entrega       = $rx->TIEMPO_ENTREGA;
			$vdias_inventario      = $rx->DIAS_INVENTARIO;
		}
	}
}

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
		$vmes     = intval($vperiodo);
	}
}

//si es mes es menor a junio consultamos también la base de datos anterior
if($vmes <= 12)
{
		
	//DIAS LABORADOS
	$vsql = "SELECT cast(COUNT(DISTINCT FECHA) as char(15)) AS DIAS_LABORADOS FROM KARDEX WHERE EXTRACT(MONTH FROM FECHA)='".$vmes."' AND CODCOMP='FV' AND FECASENTAD IS NOT NULL AND FECANULADO IS NULL";
	if($co1 = $conect_bd_actual->consulta($vsql))
	{
		if($r1 = $co1->fetch(PDO::FETCH_OBJ))
		{
			$vdias_laborados = intval($r1->DIAS_LABORADOS);
		}
	}
	
	//RECOREMOS TODOS LOS PRODUCTOS QUE NO ESTÁN EN EL GRUPO DE SERVICIOS
	$vsql = "select m.matid,m.codigo,m.descrip,g.codigo as codgrupo from material m inner join grupmat g on m.grupmatid=g.grupmatid Where g.grupmatid>(select gg.grupmatid from grupmat gg where gg.codigo='00.00.00')";	
	if($co = $conect_bd_actual->consulta($vsql))
	{
		while($r = $co->fetch(PDO::FETCH_OBJ))
		{
			//consulta sumado
			$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado, cast(sum(d.canmat) as char(15)) as vendido from dekardex d inner join kardex k on d.kardexid=k.kardexid where EXTRACT(MONTH FROM k.fecha)='".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid='".$r->MATID."'";
			if($co2 = $conect_bd_actual->consulta($vsql))
			{
				//echo $vsql."<br><br>";
				while($r2 = $co2->fetch(PDO::FETCH_OBJ))
				{
					//echo $r2->ACUMULADO."<BR><BR>";
					$vventa_periodo = intval($r2->ACUMULADO);
					$vconsumomedmin = $vventa_periodo/$vdias_laborados;
					$vresultado     = $vconsumomedmin * $vtiempo_entrega;
					$vporseguridad  = $vresultado * ($vporcentaje_seguridad/100);
					$vstock_minimo  = $vresultado + $vporseguridad;
					$vstock_minimo  = round($vstock_minimo);
					
					if($vstock_minimo==0)
					{
						$vstock_minimo = 1;
					}
					
					$vsumando_anterior    = 0;
					$vsumando_actual      = 0;
					$vstock_maximo        = 0;
					$vpunto_de_pedido     = 0;
					$vstock_seguridad     = 0;
					$vconsumo_medio       = 0;
					$vcantidad_reposicion = 0;
					
					//traemos la suma vendida del productos en el año actual y el año anterior
					$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_anterior from dekardex d inner join kardex k on d.kardexid=k.kardexid inner join material m on d.matid=m.matid where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)>=".intval($vmes);
					if($coa = $conect_bd_anterior->consulta($vsql))
					{
						if($ra = $coa->fetch(PDO::FETCH_OBJ))
						{
							if(isset($ra->ACUMULADO_ANTERIOR) and !empty($ra->ACUMULADO_ANTERIOR))
							{
								$vsumando_anterior = intval($ra->ACUMULADO_ANTERIOR);
								
								//echo $vsumando_anterior."<br><br>";
							}
						}
					}
					
					//echo $vsql."<br><br>";
					
					//traemos la suma vendida del año actual
					$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_actual from dekardex d inner join kardex k on d.kardexid=k.kardexid where k.codcomp='FV' and k.fecasentad is not null and d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha)<=".intval($vmes);
					if($co3 = $conect_bd_actual->consulta($vsql))
					{
						if($r3 = $co3->fetch(PDO::FETCH_OBJ))
						{
							$vsumando_actual = intval($r3->ACUMULADO_ACTUAL);
						}
					}
					
					if($vsumando_anterior>0 or $vsumando_actual>0)
					{	
						$vstock_maximo    = $vconsumomedmin * $vdias_inventario;
						$vstock_maximo    = round($vstock_maximo);
					}
					
					if($vstock_minimo>1 or $vstock_maximo>0)
					{	
						echo utf8_encode($r->CODIGO)." -- ".utf8_encode($r->DESCRIP)." -- minimo: ".$vstock_minimo." -- maximo: ".$vstock_maximo.PHP_EOL;
						
						
						$vsql = "update materialsuc set existmin='".$vstock_minimo."', existmax='".$vstock_maximo."' where matid='".$r->MATID."'";
						//echo $vsql."<br><br>";
						$conect_bd_actual->consulta($vsql);
						
						$vsql = "update material set bono2='".$vconsumomedmin."' where matid='".$r->MATID."'";
						//echo $vsql."<br><br>";
						$conect_bd_actual->consulta($vsql);
					}
					
				}//fin segundo while
			}//fin consulta sumado
		}//fin while
	}//fin recorrer productos
}
?>
