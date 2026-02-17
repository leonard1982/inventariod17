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
	
	
	
	//echo $vmes;
	//si es mes es menor a junio consultamos también la base de datos anterior
	if($vmes-$vtendencia_meses <0)
	{
		$vmes_inicio_anio_anterior = 13+($vmes-$vtendencia_meses);
	
		//DIAS LABORADOS
		$vsql = "SELECT cast(COUNT(DISTINCT FECHA) as char(15)) AS DIAS_LABORADOS FROM KARDEX WHERE EXTRACT(MONTH FROM FECHA) = '".$vmes."' AND CODCOMP='FV' AND FECASENTAD IS NOT NULL AND FECANULADO IS NULL";
		if($co1 = $conect_bd_actual->consulta($vsql))
		{
			if($r1 = $co1->fetch(PDO::FETCH_OBJ))
			{
				$vdias_laborados = intval($r1->DIAS_LABORADOS);
			}
		}
		
		//RECOREMOS TODOS LOS PRODUCTOS QUE NO ESTÁN EN EL GRUPO DE SERVICIOS
	

		
		$vsql="select  m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,
				(select cast(sum(d.canmat) as char(15)) as vendido
				from dekardex d
				inner join kardex k on d.kardexid=k.kardexid
				where EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid=m.matid) as vendido,
				(select cast(sum(d.canmat) as char(15)) as acumulado
				from dekardex d
				inner join kardex k on d.kardexid=k.kardexid
				where EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid=m.matid) as acumulado
				from material m
				inner join grupmat g on m.grupmatid=g.grupmatid
				Where g.grupmatid in (select grupmatid from grupmat where CHAR_LENGTH(codigo)=8) and (select cast(sum(d.canmat) as char(15)) as acumulado
				from dekardex d
				inner join kardex k on d.kardexid=k.kardexid
				where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid=m.matid) is not null";
		

		
		if($co = $conect_bd_actual->consulta($vsql))
		{
			while($r = $co->fetch(PDO::FETCH_OBJ))
			{
				$v_cantidad_vendidas=0;
				 echo "<script>console.log('entro en el primer sql' );</script>";
				//consulta sumado
			/*	$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado, cast(sum(d.canmat) as char(15)) as vendido from dekardex d inner join kardex k on d.kardexid=k.kardexid where EXTRACT(MONTH FROM k.fecha)='".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid='".$r->MATID."'";
				if($co2 = $conect_bd_actual->consulta($vsql))
				{
					//echo $vsql."<br><br>";
					while($r2 = $co2->fetch(PDO::FETCH_OBJ))
					*/
						//echo $r2->ACUMULADO."<BR><BR>";
						$vsqlu="select iif(fecultcli >fecultprov,fecultcli,fecultprov) as ultimo_movimiento from materialsuc where matid='".$r->MATID."' ";
						if($cou = $conect_bd_actual->consulta($vsqlu))
						{
							while($ru = $cou->fetch(PDO::FETCH_OBJ))
							{
								$v_ultimo_movimiento = $ru->ULTIMO_MOVIMIENTO;
							}
						}	
						
						
						$v_mes_aux =date("m", strtotime($v_ultimo_movimiento));
						$v_mes_aux_anterior = date("m",strtotime($v_ultimo_movimiento."- ".$vtendencia_meses." month"));
						
						if($v_mes_aux-$v_mes_aux_anterior<0){
							$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad  from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".date("m", strtotime($v_ultimo_movimiento))."' and k.codcomp='FV' and k.fecasentad is not null  ";
							//echo "actual".$vsqlul."<br>";
							if($coul = $conect_bd_actual->consulta($vsqlul))
							{
								while($rul = $coul->fetch(PDO::FETCH_OBJ))
								{
									$v_cantidad_vendidas = floatval($rul->CANTIDAD);
									//echo floatval($rul->CANTIDAD);
								}
							}
							
							$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad  from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) BETWEEN '".$v_mes_aux_anterior."' AND '12' and k.codcomp='FV' and k.fecasentad is not null  ";
							//echo "anterior".$vsqlul."<br>";
							if($coul = $conect_bd_anterior->consulta($vsqlul))
							{
								while($rul = $coul->fetch(PDO::FETCH_OBJ))
								{
									$v_cantidad_vendidas += floatval($rul->CANTIDAD);
									//echo floatval($rul->CANTIDAD);
								}
							}
							
						}else{
							$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad  from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) BETWEEN '".date("m",strtotime($v_ultimo_movimiento."- ".$vtendencia_meses." month"))."' AND '".date("m", strtotime($v_ultimo_movimiento))."' and k.codcomp='FV' and k.fecasentad is not null  ";
							//echo "solo actual".$vsqlul."<br>";
							if($coul = $conect_bd_actual->consulta($vsqlul))
							{
								while($rul = $coul->fetch(PDO::FETCH_OBJ))
								{
									$v_cantidad_vendidas = floatval($rul->CANTIDAD);
									
								}
							}
						}
						
						
						$vventa_periodo = intval($r->ACUMULADO);
				/*		if($vdias_laborados==0){
							$vconsumomedmin = $vventa_periodo;
						}else{
							$vconsumomedmin = $vventa_periodo/$vdias_laborados;
						}	
						$vresultado     = $vconsumomedmin * $vtiempo_entrega;
						$vporseguridad  = $vresultado * ($vporcentaje_seguridad/100);
						$vstock_minimo  = $vresultado + $vporseguridad;
						$vstock_minimo  = round($vstock_minimo);
						
						if($vstock_minimo==0)
						{
							$vstock_minimo = 1;
						}*/ //06-06-2024
						
						$vsumando_anterior    = 0;
						$vsumando_actual      = 0;
						$vstock_maximo        = 0;
						$vpunto_de_pedido     = 0;
						$vstock_seguridad     = 0;
						$vconsumo_medio       = 0;
						$vcantidad_reposicion = 0;
						
						//traemos la suma vendida del productos en el año actual y el año anterior
						$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_anterior from dekardex d inner join kardex k on d.kardexid=k.kardexid inner join material m on d.matid=m.matid where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)>=".intval($vmes_inicio_anio_anterior);
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
						
						if($v_ultimo_movimiento==""){
							$vsqlu="select iif(fecultcli >fecultprov,fecultcli,fecultprov) as ultimo_movimiento from materialsuc where matid='".$r->MATID."' ";
							if($cou = $conect_bd_anterior->consulta($vsqlu))
							{
								while($ru = $cou->fetch(PDO::FETCH_OBJ))
								{
									$v_ultimo_movimiento = $ru->ULTIMO_MOVIMIENTO;
								}
							}	
						}	
						
						//echo $vsql."<br><br>";
						
						//traemos la suma vendida del año actual
						$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_actual from dekardex d inner join kardex k on d.kardexid=k.kardexid where k.codcomp='FV' and k.fecasentad is not null and d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha)=".intval($vmes);
						if($co3 = $conect_bd_actual->consulta($vsql))
						{
							if($r3 = $co3->fetch(PDO::FETCH_OBJ))
							{
								$vsumando_actual = intval($r3->ACUMULADO_ACTUAL);
							}
						}
						
						if($vdias_laborados==0){
							$vconsumomedmin = $vsumando_actual;
						}else{
							$vconsumomedmin = $vsumando_actual/$vdias_laborados;
						}	
						$vresultado     = $vconsumomedmin * $vtiempo_entrega;
						$vporseguridad  = $vresultado * ($vporcentaje_seguridad/100);
						$vstock_minimo  = $vresultado + $vporseguridad;
						$vstock_minimo  = round($vstock_minimo);
						
						if($vstock_minimo==0)
						{
							$vstock_minimo = 1;
						}
						
						//if($vsumando_anterior>0 or $vsumando_actual>0) 06-06-2024
						if($vsumando_actual>0)
						{	
							/*
							$vconsumo_medio   = ($vsumando_anterior + $vsumando_actual)/12;
							$vstock_seguridad = $vconsumo_medio * ($vporcentaje_seguridad/100);
							$vpunto_de_pedido = $vstock_seguridad + ($vconsumo_medio * $vtiempo_entrega);
							$vstock_maximo    = $vpunto_de_pedido + $vcantidad_reposicion - ($vstock_minimo * $vtiempo_entrega);
							$vstock_maximo    = round($vstock_maximo);
							*/
					
							$vstock_maximo    = $vconsumomedmin * $vdias_inventario;
							$vstock_maximo    = round($vstock_maximo);
						}
						
						if($vstock_minimo>=1 or $vstock_maximo>0)
						{	
							//GUARDAMOS LA VENTA PROMEDIO DIARIA
						//	$vsql4 = "update materialsuc set sn_venta_promedio='".$vconsumomedmin."' where matid='".$r->MATID."' ";
							//if($co4 = $conect_bd_actual->consulta($vsql4))
							//{
								
							//}	
							
							//echo "Sumando anterior: ".$vsumando_anterior."<br>";
							//echo "Sumando actual: ".$vsumando_actual."<br>";
							echo "<div class='table-responsive'>";
							
							echo "<table border='1' class='table table-striped table-bordered' style='align:center; width:90%;'>";
							
							echo "<tr>";
							echo "<th style='color:white;background:black;'>PRODUCTO: </th><td>".utf8_encode($r->CODIGO)." -- ".utf8_encode($r->DESCRIP)."</td><td>FÓRMULA</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Ultima fecha laborada: </th><td>".$vultima_fecha_laborada."</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Porcentaje de seguridad: </th><td>".$vporcentaje_seguridad."%</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Tiempo entrega: </th><td>".$vtiempo_entrega." dìas</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Días de inventario: </th><td>".$vdias_inventario." dìas</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Días laborados periodo(".$vperiodo."): </th><td>".$vdias_laborados."</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Venta periodo(".$vperiodo."): </th><td>".$vsumando_actual." (".$r->UNIDAD.")</td><td>Suma total de la venta del periodo.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Ultimo Movimiento: </th><td>".date("Y-m-d", strtotime($v_ultimo_movimiento))."</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Cantidad total vendide en ".$vtendencia_meses." meses anteriores: </th><td>".$v_cantidad_vendidas." (".$r->UNIDAD.")</td><td>Venta total del producto en los ".$vtendencia_meses." meses anteriores.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Tendencia : </th><td>".($v_cantidad_vendidas / $vtendencia_meses)."</td><td> Cantidad total vendida en ".$vtendencia_meses." meses anteriores / ".$vtendencia_meses." meses</td>";
							echo "</tr>";
							
							
							//STOCK MINIMO
							echo "<tr>";
							echo "<th style='color:white;background:black;'>Stock Mínimo: </th><td>".$vstock_minimo."</td><td>(Venta promedio diaria x Tiempo de entrega) + Stock de seguridad.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Venta promedio diaria: </th><td>".$vconsumomedmin."</td><td> La venta del periodo / Días laborados.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Venta promedio diaria x Lead Time: </th><td>".$vresultado."</td><td>Venta promedio diaria x Tiempo de entrega.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Stock de seguridad: </th><td>".$vporseguridad."</td><td>(Venta promedio diaria x Tiempo de entrega) x Porcentaje de seguridad.</td>";
							echo "</tr>";
							
							
							//STOCK MAXIMO
							/*
							echo "<tr>";
							echo "<th style='color:white;background:black;'>Stock Máximo: </th><td>".$vstock_maximo."</td><td>Punto de pedido + Cantidad de reposición - (Stock mínimo x Tiempo de entrega)</td>";
							echo "</tr>";

							echo "<tr>";
							echo "<th>Consumo medio(Maximo): </th><td>".$vconsumo_medio."</td><td>Total vendido 12 meses anteriores / 12 meses</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Stock de seguridad(Maximo): </th><td>".$vstock_seguridad."</td><td>Consumo medio(Maximo) x Porcentaje de seguridad.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Stock mínimo x Lead Time(Maximo): </th><td>".($vstock_minimo * $vtiempo_entrega)."</td><td>Stock mínimo x Tiempo de entrega.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Punto de pedido (Maximo): </th><td>".$vpunto_de_pedido."</td><td>Stock de seguridad(Maximo) + (Consumo medio(Maximo) x Tiempo de entrega)</td>";
							echo "</tr>";
							*/
							if((($vsumando_anterior + $vsumando_actual)/12)<$vstock_maximo){
								echo "<tr>";
								echo "<th style='color:white;background:black;'>Stock Máximo: </th><td>".($vconsumomedmin*$vdias_inventario)."</td><td>Demanda promedio ventas diarias * días que queremos tener de inventario.</td>";
								echo "</tr>";
							}else{
								echo "<tr>";
								echo "<th style='color:white;background:black;'>Stock Máximo: </th><td>".($vconsumomedmin*$vdias_inventario)."</td><td>Demanda promedio ventas diarias * días que queremos tener de inventario.</td>";
								echo "</tr>";
							}	

							echo "<tr>";
							echo "<th>Demanda promedio ventas diarias: </th><td>".$vconsumomedmin."</td><td>La venta del periodo / Días laborados.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Días de inventario: </th><td>".$vdias_inventario."</td><td>Días que queremos tener de inventario.</td>";
							echo "</tr>";
							
							if((($v_cantidad_vendidas / $vtendencia_meses)-$vstock_minimo)<1){
								$vpunto_pedido =1;
								echo "<tr>";
								echo "<th>Punto de Pedido: </th><td>".$vpunto_pedido ."</td><td> Tendencia-Stock Minimo, pero si es menor a 1,entonces el valor lo dejamos en 1. </td>";
								echo "</tr>";
							}else{
								$vpunto_pedido = (($v_cantidad_vendidas / $vtendencia_meses)-$vstock_minimo);
								echo "<tr>";
								echo "<th>Punto de Pedido: </th><td>".$vpunto_pedido ."</td><td> Tendencia-Stock Minimo, pero si es menor a 1, entonces el valor lo dejamos en 1. </td>";
								echo "</tr>";
							}
							echo "</table><br><br>";
							echo "</div>";
							
							$vfecha_actualizacion =date('Y-m-d H:i');
							
							//INSERTAMOS EN LA BASE DE DATOS DE INVENTARIOS EL LOG
							$vconsec_log=1;
							$vsql = "SELECT MAX(ID) AS CONSEC FROM HISTORIOCO_MIN_MAX ";
							if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
							{
								if($cox = $conect_bd_inventario->consulta($vsql))
								{
									if($rx = $cox->fetch(PDO::FETCH_OBJ))
									{
										$vconsec_log = $rx->CONSEC+1;
									}
								}
							}
							
							$vcosto=0;
							$vexistencia=0;
							
							
							$vsql = "select cast(costo as varchar(20)) as costo,cast(existenc as varchar(20)) as existenc from materialsuc where matid='".$r->MATID."' ";
							if($co10 = $conect_bd_actual->consulta($vsql))
							{
								if($r10 = $co10->fetch(PDO::FETCH_OBJ))
								{
									
									$vcosto=$r10->COSTO;
									$vexistencia=$r10->EXISTENC;
								}
							}
							
							
							$vsql = "INSERT INTO HISTORIOCO_MIN_MAX (ID,FECHAYHORA,ANIO,MES,CODIGO,DESCRIP,MINIMO,MAXIMO,EXISTENC,COSTO,PUNTO_PEDIDO) VALUES('".$vconsec_log."','".$vfecha_actualizacion."','".date("Y",strtotime($vultima_fecha_laborada))."','".date("m",strtotime($vultima_fecha_laborada))."','".utf8_encode($r->CODIGO)."','".utf8_encode($r->DESCRIP)."','".$vstock_minimo."','".($vconsumomedmin*$vdias_inventario)."',".$vexistencia.",".$vcosto.",'".$vpunto_pedido."')";
							if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
							{
								if($cox = $conect_bd_inventario->consulta($vsql))
								{
								}
							}
							
							
							
							$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."',sn_fecha_actualizacion='".$vfecha_actualizacion."', sn_tendencia='".($v_cantidad_vendidas / $vtendencia_meses)."',sn_punto_pedido='".$vpunto_pedido."',sn_vendido_doce_meses='".$v_cantidad_vendidas."',sn_meses_tendencia='".$vtendencia_meses."',sn_stock_maximo='".($vconsumomedmin*$vdias_inventario)."' where matid='".$r->MATID."'";
							//echo $vsql;
							$conect_bd_actual->consulta($vsql);
							
							$vsql = "update materialsuc set existmin='".$vstock_minimo."', existmax='".$vstock_maximo."' where matid='".$r->MATID."'";
							//echo $vsql."<br><br>";
							$conect_bd_actual->consulta($vsql);
							
							if((($vsumando_anterior + $vsumando_actual)/12)<$vstock_maximo){
								$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."', sn_stock_maximo='TENDENCIA',sn_stock_maximodif='".$vstock_maximo."' where matid='".$r->MATID."'";
								$conect_bd_actual->consulta($vsql);
							}else{
								$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."', sn_stock_maximo='FORMULA',sn_stock_maximodif='".(($vsumando_anterior + $vsumando_actual)/12)."' where matid='".$r->MATID."'";
								$conect_bd_actual->consulta($vsql);
							}
						}
						
					//}//fin segundo while
				//}//fin consulta sumado
			}//fin while
		}//fin recorrer productos
	}else{
		
		$vmes_inicio = $vmes-$vtendencia_meses;
		if($vmes_inicio==0){
			$vmes_inicio=1;
		}
	
		//DIAS LABORADOS
		$vsql = "SELECT cast(COUNT(DISTINCT FECHA) as char(15)) AS DIAS_LABORADOS FROM KARDEX WHERE EXTRACT(MONTH FROM FECHA) = '".$vmes."' AND CODCOMP='FV' AND FECASENTAD IS NOT NULL AND FECANULADO IS NULL";
		if($co1 = $conect_bd_actual->consulta($vsql))
		{
			if($r1 = $co1->fetch(PDO::FETCH_OBJ))
			{
				$vdias_laborados = intval($r1->DIAS_LABORADOS);
			}
		}
		
		//RECOREMOS TODOS LOS PRODUCTOS QUE NO ESTÁN EN EL GRUPO DE SERVICIOS
		
				
		$vsql="select  m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo,
				(select cast(sum(d.canmat) as char(15)) as vendido
				from dekardex d
				inner join kardex k on d.kardexid=k.kardexid
				where EXTRACT(MONTH FROM k.fecha) BETWEEN '".$vmes_inicio."' AND '".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid=m.matid) as vendido,
				(select cast(sum(d.canmat) as char(15)) as acumulado
				from dekardex d
				inner join kardex k on d.kardexid=k.kardexid
				where EXTRACT(MONTH FROM k.fecha) BETWEEN '".$vmes_inicio."' AND '".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid=m.matid) as acumulado
				from material m
				inner join grupmat g on m.grupmatid=g.grupmatid
				Where g.grupmatid in (select grupmatid from grupmat where CHAR_LENGTH(codigo)=8) and (select cast(sum(d.canmat) as char(15)) as acumulado
				from dekardex d
				inner join kardex k on d.kardexid=k.kardexid
				where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and EXTRACT(MONTH FROM k.fecha) BETWEEN '".$vmes_inicio."' AND '".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid=m.matid) is not null";

		
		
		if($co = $conect_bd_actual->consulta($vsql))
		{
			while($r = $co->fetch(PDO::FETCH_OBJ))
			{
				$v_cantidad_vendidas =0;
				 echo "<script>console.log('entro en el primer sql' );</script>";
				//consulta sumado
			/*	$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado, cast(sum(d.canmat) as char(15)) as vendido from dekardex d inner join kardex k on d.kardexid=k.kardexid where EXTRACT(MONTH FROM k.fecha)='".$vmes."' and k.codcomp='FV' and k.fecasentad is not null and d.matid='".$r->MATID."'";
				if($co2 = $conect_bd_actual->consulta($vsql))
				{
					//echo $vsql."<br><br>";
					while($r2 = $co2->fetch(PDO::FETCH_OBJ))
					*/
						//echo $r2->ACUMULADO."<BR><BR>";
						
						$vsqlu="select iif(fecultcli >fecultprov,fecultcli,fecultprov) as ultimo_movimiento from materialsuc where matid='".$r->MATID."' ";
						if($cou = $conect_bd_actual->consulta($vsqlu))
						{
							while($ru = $cou->fetch(PDO::FETCH_OBJ))
							{
								$v_ultimo_movimiento = date("Y-m-d", strtotime($ru->ULTIMO_MOVIMIENTO));
								
							}
						}
						
						$v_mes_aux =date("m", strtotime($v_ultimo_movimiento));
						$v_mes_aux_anterior = date("m",strtotime($v_ultimo_movimiento."- ".$vtendencia_meses." month"));
						
						if($v_mes_aux-$v_mes_aux_anterior<0){
							$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad  from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".date("m", strtotime($v_ultimo_movimiento))."' and k.codcomp='FV' and k.fecasentad is not null  ";
							//echo "actual".$vsqlul."<br>";
							if($coul = $conect_bd_actual->consulta($vsqlul))
							{
								while($rul = $coul->fetch(PDO::FETCH_OBJ))
								{
									$v_cantidad_vendidas = floatval($rul->CANTIDAD);
									
								}
							}
							
							$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad  from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) BETWEEN '".$v_mes_aux_anterior."' AND '12' and k.codcomp='FV' and k.fecasentad is not null  ";
							//echo "anterior".$vsqlul."<br>";
							if($coul = $conect_bd_anterior->consulta($vsqlul))
							{
								while($rul = $coul->fetch(PDO::FETCH_OBJ))
								{
									$v_cantidad_vendidas += floatval($rul->CANTIDAD);
									
								}
							}
							
						}else{
							$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad  from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) BETWEEN '".date("m",strtotime($v_ultimo_movimiento."- ".$vtendencia_meses." month"))."' AND '".date("m", strtotime($v_ultimo_movimiento))."' and k.codcomp='FV' and k.fecasentad is not null  ";
							//echo "solo actual".$vsqlul."<br>";
							if($coul = $conect_bd_actual->consulta($vsqlul))
							{
								while($rul = $coul->fetch(PDO::FETCH_OBJ))
								{
									$v_cantidad_vendidas = floatval($rul->CANTIDAD);
									
								}
							}
						}
						
						

						
						
						$vventa_periodo = intval($r->ACUMULADO);
						/*if($vdias_laborados==0){
							$vconsumomedmin = $vventa_periodo;
						}else{
							$vconsumomedmin = $vventa_periodo/$vdias_laborados;
						}	
						$vresultado     = $vconsumomedmin * $vtiempo_entrega;
						$vporseguridad  = $vresultado * ($vporcentaje_seguridad/100);
						$vstock_minimo  = $vresultado + $vporseguridad;
						$vstock_minimo  = round($vstock_minimo);
						
						if($vstock_minimo==0)
						{
							$vstock_minimo = 1;
						} */ // 06-006-2024
						
						$vsumando_anterior    = 0;
						$vsumando_actual      = 0;
						$vstock_maximo        = 0;
						$vpunto_de_pedido     = 0;
						$vstock_seguridad     = 0;
						$vconsumo_medio       = 0;
						$vcantidad_reposicion = 0;
						
						//traemos la suma vendida del productos en el año actual y el año anterior
					/*	$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_anterior from dekardex d inner join kardex k on d.kardexid=k.kardexid inner join material m on d.matid=m.matid where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)>=".intval($vmes_inicio_anio_anterior);
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
						}*/
						
						//echo $vsql."<br><br>";
						
						//traemos la suma vendida del año actual
						$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_actual from dekardex d inner join kardex k on d.kardexid=k.kardexid where k.codcomp='FV' and k.fecasentad is not null and d.matid='".$r->MATID."' and EXTRACT(MONTH FROM k.fecha) ='".$vmes."' " ;
						if($co3 = $conect_bd_actual->consulta($vsql))
						{
							if($r3 = $co3->fetch(PDO::FETCH_OBJ))
							{
								$vsumando_actual = intval($r3->ACUMULADO_ACTUAL);
							}
						}
						
						if($vdias_laborados==0){
							$vconsumomedmin = $vsumando_actual;
						}else{
							$vconsumomedmin = $vsumando_actual/$vdias_laborados;
						}	
						$vresultado     = $vconsumomedmin * $vtiempo_entrega;
						$vporseguridad  = $vresultado * ($vporcentaje_seguridad/100);
						$vstock_minimo  = $vresultado + $vporseguridad;
						$vstock_minimo  = round($vstock_minimo);
						
						if($vstock_minimo==0)
						{
							$vstock_minimo = 1;
						}
						
						
						if($vsumando_anterior>0 or $vsumando_actual>0)
						{	
							/*
							$vconsumo_medio   = ($vsumando_anterior + $vsumando_actual)/12;
							$vstock_seguridad = $vconsumo_medio * ($vporcentaje_seguridad/100);
							$vpunto_de_pedido = $vstock_seguridad + ($vconsumo_medio * $vtiempo_entrega);
							$vstock_maximo    = $vpunto_de_pedido + $vcantidad_reposicion - ($vstock_minimo * $vtiempo_entrega);
							$vstock_maximo    = round($vstock_maximo);
							*/
					
							$vstock_maximo    = $vconsumomedmin * $vdias_inventario;
							$vstock_maximo    = round($vstock_maximo);
						}
						
						if($vstock_minimo>=1 or $vstock_maximo>0)
						{	
							//GUARDAMOS LA VENTA PROMEDIO DIARIA
						//	$vsql4 = "update materialsuc set sn_venta_promedio='".$vconsumomedmin."' where matid='".$r->MATID."' ";
							//if($co4 = $conect_bd_actual->consulta($vsql4))
							//{
								
							//}	
							
							//echo "Sumando anterior: ".$vsumando_anterior."<br>";
							//echo "Sumando actual: ".$vsumando_actual."<br>";
							echo "<div class='table-responsive'>";
							
							echo "<table border='1' class='table table-striped table-bordered' style='align:center; width:90%;'>";
							
							echo "<tr>";
							echo "<th style='color:white;background:black;'>PRODUCTO: </th><td>".utf8_encode($r->CODIGO)." -- ".utf8_encode($r->DESCRIP)."</td><td>FÓRMULA</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Ultima fecha laborada: </th><td>".$vultima_fecha_laborada."</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Porcentaje de seguridad: </th><td>".$vporcentaje_seguridad."%</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Tiempo entrega: </th><td>".$vtiempo_entrega." dìas</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Días de inventario: </th><td>".$vdias_inventario." dìas</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Días laborados periodo(".$vperiodo."): </th><td>".$vdias_laborados." dìas</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Venta periodo(".$vperiodo."): </th><td>".$vsumando_actual." (".$r->UNIDAD.")</td><td>Suma total de la venta del periodo.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Ultimo Movimiento: </th><td>".$v_ultimo_movimiento."</td><td></td>";
							echo "</tr>";
							
							echo "<tr>";
							//echo "<th>Total vendido ".$vtendencia_meses." meses anteriores: </th><td>".($vsumando_anterior + $vsumando_actual)."</td><td>Venta total del producto en los doce(12) meses anteriores.</td>";
							echo "<th>Cantidad total vendida en ".$vtendencia_meses." meses anteriores: </th><td>".$v_cantidad_vendidas." (".$r->UNIDAD.")</td><td>Cantidad total vendida del producto en los ".$vtendencia_meses." meses anteriores.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Tendencia : </th><td>".($v_cantidad_vendidas / $vtendencia_meses)."</td><td>Total vendido ".$vtendencia_meses." meses anteriores / ".$vtendencia_meses." meses</td>";
							echo "</tr>";
							
							
							//STOCK MINIMO
							echo "<tr>";
							echo "<th style='color:white;background:black;'>Stock Mínimo: </th><td>".$vstock_minimo."</td><td>(Venta promedio diaria x Tiempo de entrega) + Stock de seguridad.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Venta promedio diaria: </th><td>".$vconsumomedmin."</td><td> La venta del periodo / Días laborados.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Venta promedio diaria x Lead Time: </th><td>".$vresultado."</td><td>Venta promedio diaria x Tiempo de entrega.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Stock de seguridad: </th><td>".$vporseguridad."</td><td>(Venta promedio diaria x Tiempo de entrega) x Porcentaje de seguridad.</td>";
							echo "</tr>";
							
							
							//STOCK MAXIMO
							/*
							echo "<tr>";
							echo "<th style='color:white;background:black;'>Stock Máximo: </th><td>".$vstock_maximo."</td><td>Punto de pedido + Cantidad de reposición - (Stock mínimo x Tiempo de entrega)</td>";
							echo "</tr>";

							echo "<tr>";
							echo "<th>Consumo medio(Maximo): </th><td>".$vconsumo_medio."</td><td>Total vendido 12 meses anteriores / 12 meses</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Stock de seguridad(Maximo): </th><td>".$vstock_seguridad."</td><td>Consumo medio(Maximo) x Porcentaje de seguridad.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Stock mínimo x Lead Time(Maximo): </th><td>".($vstock_minimo * $vtiempo_entrega)."</td><td>Stock mínimo x Tiempo de entrega.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Punto de pedido (Maximo): </th><td>".$vpunto_de_pedido."</td><td>Stock de seguridad(Maximo) + (Consumo medio(Maximo) x Tiempo de entrega)</td>";
							echo "</tr>";
							*/
							if((($vsumando_anterior + $vsumando_actual)/12)<$vstock_maximo){
								echo "<tr>";
								echo "<th style='color:white;background:black;'>Stock Máximo: </th><td>".($vconsumomedmin*$vdias_inventario)."</td><td>Demanda promedio ventas diarias * días que queremos tener de inventario.</td>";
								echo "</tr>";
							}else{
								echo "<tr>";
								echo "<th style='color:white;background:black;'>Stock Máximo: </th><td>".($vconsumomedmin*$vdias_inventario)."</td><td>Demanda promedio ventas diarias * días que queremos tener de inventario.</td>";
								echo "</tr>";
							}	

							echo "<tr>";
							echo "<th>Demanda promedio ventas diarias: </th><td>".$vconsumomedmin."</td><td>La venta del periodo / Días laborados.</td>";
							echo "</tr>";
							
							echo "<tr>";
							echo "<th>Días de inventario: </th><td>".$vdias_inventario."</td><td>Días que queremos tener de inventario.</td>";
							echo "</tr>";
							
							if((($v_cantidad_vendidas / $vtendencia_meses)-$vstock_minimo)<1){
								$vpunto_pedido =1;
								echo "<tr>";
								echo "<th>Punto de Pedido: </th><td>".$vpunto_pedido ."</td><td> Tendencia-Stock Minimo, pero si es menor a 1,entonces el valor lo dejamos en 1. </td>";
								echo "</tr>";
							}else{
								$vpunto_pedido = (($v_cantidad_vendidas / $vtendencia_meses)-$vstock_minimo);
								echo "<tr>";
								echo "<th>Punto de Pedido: </th><td>".$vpunto_pedido ."</td><td> Tendencia-Stock Minimo, pero si es menor a 1,entonces el valor lo dejamos en 1. </td>";
								echo "</tr>";
							}
							echo "</table><br><br>";
							echo "</div>";
							
							$vfecha_actualizacion =date('Y-m-d H:i');
							
							
							//INSERTAMOS EN LA BASE DE DATOS DE INVENTARIOS EL LOG
							$vconsec_log=1;
							$vsql = "SELECT MAX(ID) AS CONSEC FROM HISTORIOCO_MIN_MAX ";
							if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
							{
								if($cox = $conect_bd_inventario->consulta($vsql))
								{
									if($rx = $cox->fetch(PDO::FETCH_OBJ))
									{
										$vconsec_log = $rx->CONSEC+1;
									}
								}
							}
							
							$vcosto=0;
							$vexistencia=0;
							
							
							$vsql = "select cast(costo as varchar(20)) as costo,cast(existenc as varchar(20)) as existenc from materialsuc where matid='".$r->MATID."' ";
							if($co10 = $conect_bd_actual->consulta($vsql))
							{
								if($r10 = $co10->fetch(PDO::FETCH_OBJ))
								{
									
									$vcosto=$r10->COSTO;
									$vexistencia=$r10->EXISTENC;
								}
							}
							
							
							$vsql = "INSERT INTO HISTORIOCO_MIN_MAX (ID,FECHAYHORA,ANIO,MES,CODIGO,DESCRIP,MINIMO,MAXIMO,EXISTENC,COSTO,PUNTO_PEDIDO) VALUES('".$vconsec_log."','".$vfecha_actualizacion."','".date("Y",strtotime($vultima_fecha_laborada))."','".date("m",strtotime($vultima_fecha_laborada))."','".utf8_encode($r->CODIGO)."','".utf8_encode($r->DESCRIP)."','".$vstock_minimo."','".($vconsumomedmin*$vdias_inventario)."',".$vexistencia.",".$vcosto.",'".$vpunto_pedido."')";
							if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
							{
								if($cox = $conect_bd_inventario->consulta($vsql))
								{
								}
							}
							
							
							$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."',sn_fecha_actualizacion='".$vfecha_actualizacion."', sn_tendencia='".($v_cantidad_vendidas / $vtendencia_meses)."',sn_punto_pedido='".$vpunto_pedido ."',sn_vendido_doce_meses='".$v_cantidad_vendidas."',sn_meses_tendencia='".$vtendencia_meses."',sn_stock_maximo='".($vconsumomedmin*$vdias_inventario)."' where matid='".$r->MATID."'";
							
							$conect_bd_actual->consulta($vsql);
							
							$vsql = "update materialsuc set existmin='".$vstock_minimo."', existmax='".$vstock_maximo."' where matid='".$r->MATID."'";
							//echo $vsql."<br><br>";
							$conect_bd_actual->consulta($vsql);
							
							if((($vsumando_anterior + $vsumando_actual)/12)<$vstock_maximo){
								$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."', sn_stock_maximo='TENDENCIA',sn_stock_maximodif='".$vstock_maximo."' where matid='".$r->MATID."'";
								$conect_bd_actual->consulta($vsql);
							}else{
								$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."', sn_stock_maximo='FORMULA',sn_stock_maximodif='".(($vsumando_anterior + $vsumando_actual)/12)."' where matid='".$r->MATID."'";
								$conect_bd_actual->consulta($vsql);
							}
						}
						
					//}//fin segundo while
				//}//fin consulta sumado
			}//fin while
		}//fin recorrer productos
		
	}
fCrearLogTNS('AUTOMATICO','SE GENERO DE MANERA AUTOMATICA EL REPORTE DE MAXIMOS Y MINIMOS',$vbd_actual);
?>


<script>

$(function () {
		
	$('#search').quicksearch('table tbody tr');								
});

</script>
