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
$bd               = '';
$ip               = '127.0.0.1';
$varchivo         = "bd_admin.txt";
$varchivopj       = "f:/facilweb_fe73_32/htdocs/evento_inventario/prefijos.txt";
$vprefijos        = "";
$vbd_actual       = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_actual.txt";
$vbd_anterior     = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_anterior.txt";
$vbd_inventarios  = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_inventarios.txt";
?>

<html lang="en" dir="ltr">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title></title>
</head>

<tbody>
<?php
//VALIDACION BASE ACTUAL
if(file_exists($vbd_actual))
{
	$fp = fopen($vbd_actual, "r");
	while (!feof($fp))
	{
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
	while (!feof($fp))
	{
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
	while (!feof($fp))
	{
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
	while (!feof($fpj))
	{
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
$vporcentaje_seguridad = array();
$vtiempo_entrega       = array();
$vdias_laborados       = 0;
$vdias_inventario      = array();
$vtendencia_meses      = array();
$v_ultimo_movimiento   = "";
$v_cantidad_vendidas   = 0;
$vgrupo_buscar         = "";
$v_reg                 = 0;
$v_linea               = "";
$buscadorProductos     = "";
$guardarCalculos       = 0;

if(isset($_POST["reg"]) and $_POST["reg"]>0)
{
	$v_reg   = $_POST["reg"];
}
if(isset($_POST["linea"]) and $_POST["linea"]>0)
{
	$v_linea = $_POST["linea"];
}

if(isset($_POST["grupo"]) and $_POST["grupo"]>0)
{
	$vgrupo_buscar = " WHERE GRUPO = '".$_POST["grupo"]."' ";
}
if(isset($_POST["buscadorProductos"]) and !empty($_POST["buscadorProductos"]))
{
	$buscadorProductos = trim($_POST["buscadorProductos"]);
	$buscadorProductos = str_replace(' ',"%",$buscadorProductos);
	$buscadorProductos = " and (m.descrip like '%".$buscadorProductos."%' or m.codigo = '".$buscadorProductos."') ";
}
if(isset($_POST["guardarCalculos"]))
{
	$guardarCalculos = $_POST["guardarCalculos"];
}
else
{
	$guardarCalculos = 2;
}

//para pruebas
$vperiodo = 4;//abril porque es el periodo que el ultimo periodo en la base de prueba de inventarios que tenga movimientos
$vultima_fecha_laborada = "";
$vultima_fecha_menos_30 = "";
$vcodgrupos     = array();
$vdescripgrupos = array();
$vlimitar_regis = "";

$vsql = "SELECT ID,PORCENTAJE_SEGURIDAD,TIEMPO_ENTREGA, DIAS_INVENTARIO,TENDENCIA_MESES, GRUPO FROM CONFIGURACIONES ".$vgrupo_buscar." ORDER BY ID ASC";
//echo $vsql;
if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
{
	$contadorcg = 0;
	if($cox = $conect_bd_inventario->consulta($vsql))
	{
		while($rx = $cox->fetch(PDO::FETCH_OBJ))
		{
			$vporcentaje_seguridad[$contadorcg] = $rx->PORCENTAJE_SEGURIDAD;
			$vtiempo_entrega[$contadorcg]       = $rx->TIEMPO_ENTREGA;
			$vdias_inventario[$contadorcg]      = $rx->DIAS_INVENTARIO;
			$vtendencia_meses[$contadorcg]      = (int)$rx->TENDENCIA_MESES;

			//buscamos los codigos de los grupos
			$vsql = "SELECT CODIGO, DESCRIP FROM GRUPMAT WHERE GRUPMATID='".$rx->GRUPO."'";
			if($cogp = $conect_bd_actual->consulta($vsql))
			{
				if($rgp = $cogp->fetch(PDO::FETCH_OBJ))
				{
					$vcodgrupos[$contadorcg]     = $rgp->CODIGO;
					$vdescripgrupos[$contadorcg] = $rgp->DESCRIP;
					$contadorcg++;
				}
			}
		}
	}
}

if(isset($_POST["grupo"]) or empty($vgrupo_buscar))
{
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

	//DIAS LABORADOS
	$vsql = "SELECT cast(COUNT(DISTINCT FECHA) as char(15)) AS DIAS_LABORADOS FROM KARDEX WHERE EXTRACT(MONTH FROM FECHA) = '".$vmes."' AND CODCOMP='FV' AND FECASENTAD IS NOT NULL AND FECANULADO IS NULL";
	if($co1 = $conect_bd_actual->consulta($vsql))
	{
		if($r1 = $co1->fetch(PDO::FETCH_OBJ))
		{
			$vdias_laborados = intval($r1->DIAS_LABORADOS);
		}
	}
	
	//si días laborados es igual a cero, entonces consultamos la bd del año anterior
	if($vdias_laborados == 0)
	{
		if($co1a = $conect_bd_anterior->consulta($vsql))
		{
			if($r1a = $co1a->fetch(PDO::FETCH_OBJ))
			{
				$vdias_laborados = intval($r1a->DIAS_LABORADOS);
			}
		}
	}

	$contador_registros = 0;

	//recorremos todos los grupos
	foreach($vcodgrupos as $id => $cgrupo)
	{
		if($v_reg>0)
		{
			$vlimitar_regis = " first ".$v_reg." ";
		}
		else
		{
			$vlimitar_regis = "";
		}

		$vsql="select ".$vlimitar_regis." m.matid,m.unidad,m.codigo,m.descrip,g.codigo as codgrupo, a.descrip as marca,
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
			inner join marcaart a on m.marcaartid=a.marcaartid 
			Where g.grupmatid in (select grupmatid from grupmat where CHAR_LENGTH(codigo)=8) and (select cast(sum(d.canmat) as char(15)) as acumulado
			from dekardex d
			inner join kardex k on d.kardexid=k.kardexid
			where 
			g.codigo like '".$cgrupo."%' ".$buscadorProductos." 
			and EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".$vmes."' 
			and k.codcomp='FV' 
			and k.fecasentad is not null 
			and d.matid=m.matid) is not null";

		//echo $vsql."<br><br>";
		if($co = $conect_bd_actual->consulta($vsql))
		{
			/****************************************************************************************
			 * FIX PRINCIPAL:
			 * - Inicializamos SIEMPRE $vmes_inicio_anio_anterior
			 * - Solo lo usamos si realmente aplica (cuando cruza año => queda 1..12)
			 ****************************************************************************************/
			$vmes_inicio_anio_anterior = 13; // "sentinela" (no aplica año anterior)
			if(($vmes-$vtendencia_meses[$id]) < 0)
			{
				$vmes_inicio_anio_anterior = 13 + ($vmes - $vtendencia_meses[$id]); // 1..12
			}

			while($r = $co->fetch(PDO::FETCH_OBJ))
			{
				$v_cantidad_vendidas = 0;
				echo "<script>console.log('entro en el primer sql' );</script>";

				$v_ultimo_movimiento = ""; // para evitar “arrastre” entre iteraciones

				$vsqlu="select iif(fecultcli >fecultprov,fecultcli,fecultprov) as ultimo_movimiento from materialsuc where matid='".$r->MATID."' AND SUCID=1";
				if($cou = $conect_bd_actual->consulta($vsqlu))
				{
					if($ru = $cou->fetch(PDO::FETCH_OBJ))
					{
						$v_ultimo_movimiento = $ru->ULTIMO_MOVIMIENTO;
					}
				}

				$v_mes_aux = date("m", strtotime($v_ultimo_movimiento));
				$v_mes_aux_anterior = date("m",strtotime($v_ultimo_movimiento."- ".$vtendencia_meses[$id]." month"));

				//si se compromete el año anterior
				if($v_mes_aux-$v_mes_aux_anterior<0)
				{
					//cantidades vendidas del año actual
					$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad
						from dekardex as d
						inner join kardex as k on(d.kardexid=k.kardexid)
						where d.matid='".$r->MATID."'
						and EXTRACT(MONTH FROM k.fecha) BETWEEN '01' AND '".date("m", strtotime($v_ultimo_movimiento))."'
						and k.codcomp='FV'
						and k.fecasentad is not null";

					if($coul = $conect_bd_actual->consulta($vsqlul))
					{
						if($rul = $coul->fetch(PDO::FETCH_OBJ))
						{
							$v_cantidad_vendidas = floatval($rul->CANTIDAD);
						}
					}

					//cantidades vendidas del año anterior
					$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad
						from dekardex as d
						inner join kardex as k on(d.kardexid=k.kardexid)
						where d.matid='".$r->MATID."'
						and EXTRACT(MONTH FROM k.fecha) BETWEEN '".$v_mes_aux_anterior."' AND '12'
						and k.codcomp='FV'
						and k.fecasentad is not null";

					if($coul = $conect_bd_anterior->consulta($vsqlul))
					{
						while($rul = $coul->fetch(PDO::FETCH_OBJ))
						{
							$v_cantidad_vendidas += floatval($rul->CANTIDAD);
						}
					}
				}
				else
				{
					//si no compromete el año anterior
					$vsqlul="select coalesce(cast(sum(d.canmat) as char(15)),0) as cantidad
						from dekardex as d
						inner join kardex as k on(d.kardexid=k.kardexid)
						where d.matid='".$r->MATID."'
						and EXTRACT(MONTH FROM k.fecha) BETWEEN '".date("m",strtotime($v_ultimo_movimiento."- ".$vtendencia_meses[$id]." month"))."' AND '".date("m", strtotime($v_ultimo_movimiento))."'
						and k.codcomp='FV'
						and k.fecasentad is not null";

					if($coul = $conect_bd_actual->consulta($vsqlul))
					{
						while($rul = $coul->fetch(PDO::FETCH_OBJ))
						{
							$v_cantidad_vendidas = floatval($rul->CANTIDAD);
						}
					}
				}

				$vventa_periodo = intval($r->ACUMULADO);
				$vsumando_anterior    = 0;
				$vsumando_actual      = 0;
				$vstock_maximo        = 0;
				$vpunto_de_pedido     = 0;
				$vstock_seguridad     = 0;
				$vconsumo_medio       = 0;
				$vcantidad_reposicion = 0;

				/****************************************************************************************
				 * FIX 2:
				 * - SOLO consultamos la BD anterior si $vmes_inicio_anio_anterior aplica (<=12)
				 ****************************************************************************************/
				if($vmes_inicio_anio_anterior <= 12)
				{
					$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_anterior
						from dekardex d
						inner join kardex k on d.kardexid=k.kardexid
						inner join material m on d.matid=m.matid
						where k.codcomp='FV'
						and k.fecasentad is not null
						and m.codigo='".$r->CODIGO."'
						and EXTRACT(MONTH FROM k.fecha)>=".intval($vmes_inicio_anio_anterior);

					if($coa = $conect_bd_anterior->consulta($vsql))
					{
						if($ra = $coa->fetch(PDO::FETCH_OBJ))
						{
							if(isset($ra->ACUMULADO_ANTERIOR) and !empty($ra->ACUMULADO_ANTERIOR))
							{
								$vsumando_anterior = intval($ra->ACUMULADO_ANTERIOR);
							}
						}
					}
				}

				if($v_ultimo_movimiento=="")
				{
					$vsqlu="select iif(fecultcli >fecultprov,fecultcli,fecultprov) as ultimo_movimiento from materialsuc where matid='".$r->MATID."' AND SUCID=1";
					if($cou = $conect_bd_anterior->consulta($vsqlu))
					{
						while($ru = $cou->fetch(PDO::FETCH_OBJ))
						{
							$v_ultimo_movimiento = $ru->ULTIMO_MOVIMIENTO;
						}
					}
				}

				//traemos la suma vendida del año actual
				$vsql = "select cast(sum(d.canmat) as char(15)) as acumulado_actual
					from dekardex d
					inner join kardex k on d.kardexid=k.kardexid
					where k.codcomp='FV'
					and k.fecasentad is not null
					and d.matid='".$r->MATID."'
					and EXTRACT(MONTH FROM k.fecha)<=".intval($vmes);

				if($co3 = $conect_bd_actual->consulta($vsql))
				{
					if($r3 = $co3->fetch(PDO::FETCH_OBJ))
					{
						$vsumando_actual = intval($r3->ACUMULADO_ACTUAL);
					}
				}

				if($vdias_laborados==0)
				{
					$vconsumomedmin = $v_cantidad_vendidas;
				}
				else
				{
					$vconsumomedmin = $v_cantidad_vendidas/($vtendencia_meses[$id]*30);
				}

				$vresultado     = $vconsumomedmin * $vtiempo_entrega[$id];
				$vporseguridad  = $vresultado * ($vporcentaje_seguridad[$id]/100);
				$vstock_minimo  = $vresultado + $vporseguridad;
				$vstock_minimo  = round($vstock_minimo);

				if($vstock_minimo==0)
				{
					$vstock_minimo = 1;
				}

				if($vsumando_actual>0)
				{
					$vstock_maximo    = $vconsumomedmin * $vdias_inventario[$id];
					$vstock_maximo    = round($vstock_maximo);
				}

				if($vstock_minimo>=1 or $vstock_maximo>0)
				{
					if($vstock_maximo==0 and $vstock_minimo==1)
					{
						$vstock_maximo = 1;
					}

					echo "<style>
						.bg-principal { background-color: #00324b !important; color: #fff !important; }
						.bg-secundario { background-color: #004f70 !important; color: #fff !important; }
						.bg-info-custom { background-color: #007191 !important; color: #fff !important; }
						.table-light-custom { background-color: #f8f9fa !important; }
					</style>";

					if($contador_registros==0)
					{
						echo "<div class='table-responsive'>";
						echo "<table id='tablaResultados' class='table table-bordered table-hover table-striped table-sm shadow-sm' style='margin:auto; width:90%; font-size: 0.95rem;'>";
					}

					echo "<tbody class='bloque-producto'>";

					echo "<tr>";
					echo "<th colspan='5' class='bg-principal text-center fw-bold'>GRUPO: ".utf8_encode($cgrupo)." — ".utf8_encode($vdescripgrupos[$id])."</th>";
					echo "</tr>";

					echo "<tr><th rowspan='21' style='vertical-align: middle;'>".($contador_registros+1)."</th></tr>";

					echo "<tr class='table-light-custom'>";
					echo "<th class='bg-secundario'>PRODUCTO:</th><td>".utf8_encode($r->CODIGO)." — ".utf8_encode($r->DESCRIP)."</td><td colspan='2'>FÓRMULA</td>";
					echo "</tr>";

					echo "<tr class='table-light-custom'>";
					echo "<th class='bg-secundario'>Clasificación</th><td colspan='2'><b>".utf8_encode($r->MARCA)."</b></td><td colspan='2'></td>";
					echo "</tr>";

					echo "<tr><th class='table-light-custom'>Última fecha laborada:</th><td>".$vultima_fecha_laborada."</td><td colspan='2'></td></tr>";
					echo "<tr><th class='table-light-custom'>Porcentaje de seguridad:</th><td>".$vporcentaje_seguridad[$id]."%</td><td colspan='2'></td></tr>";
					echo "<tr><th class='table-light-custom'>Tiempo entrega:</th><td>".$vtiempo_entrega[$id]." días</td><td colspan='2'></td></tr>";
					echo "<tr><th class='table-light-custom'>Días de inventario:</th><td>".$vdias_inventario[$id]." días</td><td colspan='2'></td></tr>";
					echo "<tr><th class='table-light-custom'>Días laborados período ($vperiodo):</th><td>".$vdias_laborados."</td><td colspan='2'></td></tr>";
					echo "<tr><th class='table-light-custom'>Venta periodo ($vperiodo):</th><td>".$vsumando_actual." (".utf8_encode($r->UNIDAD).")</td><td colspan='2'>Suma total de la venta del período.</td></tr>";
					echo "<tr><th class='table-light-custom'>Último Movimiento:</th><td>".date("Y-m-d", strtotime($v_ultimo_movimiento))."</td><td colspan='2'></td></tr>";
					echo "<tr><th class='table-light-custom'>Cantidad vendida en ".$vtendencia_meses[$id]." meses:</th><td>".$v_cantidad_vendidas." (".utf8_encode($r->UNIDAD).")</td><td colspan='2'>Venta total en los últimos ".$vtendencia_meses[$id]." meses.</td></tr>";
					echo "<tr><th class='table-light-custom'>Tendencia:</th><td>".($v_cantidad_vendidas / $vtendencia_meses[$id])."</td><td colspan='2'>Venta total / ".$vtendencia_meses[$id]." meses.</td></tr>";

					echo "<tr>";
					echo "<th class='bg-secundario'>Stock Mínimo:</th><td>".$vstock_minimo."</td><td colspan='2'>(Promedio diario x Entrega) + Seguridad.</td></tr>";

					echo "<tr><th class='table-light-custom'>Promedio diario:</th><td>".$vconsumomedmin."</td><td colspan='2'>Total venta / ".($vtendencia_meses[$id]*30)." días.</td></tr>";
					echo "<tr><th class='table-light-custom'>Promedio diario x Lead Time:</th><td>".$vresultado."</td><td colspan='2'>Promedio diario x Entrega.</td></tr>";
					echo "<tr><th class='table-light-custom'>Stock de seguridad:</th><td>".$vporseguridad."</td><td colspan='2'>(Promedio diario x Entrega) x % Seguridad.</td></tr>";

					echo "<tr>";
					echo "<th class='bg-secundario'>Stock Máximo:</th><td>".($vconsumomedmin*$vdias_inventario[$id])."</td><td colspan='2'>Demanda promedio diaria x días deseados de inventario.</td></tr>";
					echo "<tr><th class='table-light-custom'>Demanda promedio diaria:</th><td>".$vconsumomedmin."</td><td colspan='2'>Venta del periodo / Días laborados.</td></tr>";
					echo "<tr><th class='table-light-custom'>Días de inventario:</th><td>".$vdias_inventario[$id]."</td><td colspan='2'>Días deseados de inventario.</td></tr>";

					if((($v_cantidad_vendidas / $vtendencia_meses[$id])-$vstock_minimo)<1)
					{
						$vpunto_pedido = 1;
						echo "<tr><th class='bg-info-custom'>Punto de Pedido:</th><td>".$vpunto_pedido."</td><td colspan='2'>Tendencia - Stock Mínimo (ajustado a 1 si es menor).</td></tr>";
					}
					else
					{
						$vpunto_pedido = intval(($v_cantidad_vendidas / $vtendencia_meses[$id])-$vstock_minimo);
						echo "<tr><th class='bg-info-custom'>Punto de Pedido:</th><td>".$vpunto_pedido."</td><td colspan='2'>Tendencia - Stock Mínimo (ajustado a 1 si es menor).</td></tr>";
					}

					echo "<tr>";
					echo "<th colspan='4' style=''><br></th>";
					echo "</tr>";

					echo "</tbody>";

					$vfecha_actualizacion = date('Y-m-d H:i');

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

					$vsql = "select cast(IIF(costo=0,ULTCOSTPROM,costo) as varchar(20)) as costo,cast(existenc as varchar(20)) as existenc from materialsuc where matid='".$r->MATID."' AND SUCID=1";
					if($co10 = $conect_bd_actual->consulta($vsql))
					{
						if($r10 = $co10->fetch(PDO::FETCH_OBJ))
						{
							$vcosto=$r10->COSTO;
							$vexistencia=$r10->EXISTENC;
						}
					}

					if($guardarCalculos==1 or $guardarCalculos==2)
					{
						$vsql = "INSERT INTO HISTORIOCO_MIN_MAX (ID,FECHAYHORA,ANIO,MES,CODIGO,DESCRIP,MINIMO,MAXIMO,EXISTENC,COSTO,PUNTO_PEDIDO) VALUES('".$vconsec_log."','".$vfecha_actualizacion."','".date("Y",strtotime($vultima_fecha_laborada))."','".date("m",strtotime($vultima_fecha_laborada))."','".$r->CODIGO."','".$r->DESCRIP."','".$vstock_minimo."','".($vconsumomedmin*$vdias_inventario[$id])."',".$vexistencia.",".$vcosto.",'".$vpunto_pedido."')";
						if($conect_bd_inventario = new dbFirebirdPDO($ip,$vbd_inventarios))
						{
							if($cox = $conect_bd_inventario->consulta($vsql))
							{
							}
						}

						$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."',sn_fecha_actualizacion='".$vfecha_actualizacion."', sn_tendencia='".($v_cantidad_vendidas / $vtendencia_meses[$id])."',sn_punto_pedido='".$vpunto_pedido ."',sn_vendido_doce_meses='".$v_cantidad_vendidas."',sn_meses_tendencia='".$vtendencia_meses[$id]."',sn_stock_maximo='".($vconsumomedmin*$vdias_inventario[$id])."' where matid='".$r->MATID."' AND SUCID=1";
						$conect_bd_actual->consulta($vsql);

						$vsql = "update materialsuc set existmin='".$vstock_minimo."', existmax='".$vstock_maximo."' where matid='".$r->MATID."' AND SUCID=1";
						$conect_bd_actual->consulta($vsql);

						if((($vsumando_anterior + $vsumando_actual)/12)<$vstock_maximo)
						{
							$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."', sn_stock_maximo='TENDENCIA',sn_stock_maximodif='".$vstock_maximo."' where matid='".$r->MATID."' AND SUCID=1";
							$conect_bd_actual->consulta($vsql);
						}
						else
						{
							$vsql = "update materialsuc set sn_mm_actualizacion='".$vfecha_actualizacion."', sn_stock_maximo='FORMULA',sn_stock_maximodif='".(($vsumando_anterior + $vsumando_actual)/12)."' where matid='".$r->MATID."' AND SUCID=1";
							$conect_bd_actual->consulta($vsql);
						}
					}
				}

				if($v_reg>0 and ($v_reg-1)==$contador_registros)
				{
					exit();
				}
				$contador_registros++;
			}//fin while
		}//fin recorrer productos
	}

	if($contador_registros>0)
	{
		echo "</table>";
		echo "</div>";
	}
}

//fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' GENERO EL INFORME DE MAXIMOS Y MINIMOS DE LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$vbd_actual);
?>
<br><br><br>
</tbody>
</html>