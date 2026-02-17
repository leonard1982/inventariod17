<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';

$filename = "Comparativo.xls";
header("Content-type: application/x-msdownload");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");


echo "<div class='' style='overflow:auto;width:100%;'>";

echo "<br>";


//******************************************************************************************************************
$bd           = '';
$ip           = '127.0.0.1';
$varchivo     = "bd_admin.txt";
$vprefijos    = "";
$drives = range('A', 'Z');
$varchivopj = "";
$vbd_actual = "";
$vbd_anterior = "";
$vbd_inventarios = "";

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
		$vmes     = intval($vperiodo);
	}
}


if(isset($_GET['cant'])){
	$v_cantidad = $_GET['cant'];
	$v_registros = $_GET['reg'];
	$v_grupo     = $_GET['grupo'];
	$v_linea    = $_GET['linea'];
	$v_valida=0;
	//si es mes es menor a junio consultamos también la base de datos anterior
	//if($vmes <= $v_cantidad)
	//{
		//echo "entro";
		$vcount = 0;
		//RECOREMOS TODOS LOS PRODUCTOS QUE NO ESTÁN EN EL GRUPO DE SERVICIOS
		//$vsql = "select first 100 m.matid,m.codigo,m.descrip,g.codigo as codgrupo from material m inner join grupmat g on m.grupmatid=g.grupmatid Where g.grupmatid>(select gg.grupmatid from grupmat gg where gg.codigo='00.00.00') and m.codigo in('YC110D-23NEG','9079END03000')";	
		
		if($v_grupo>0)
		{
			if($v_grupo==1)
			{
				//MOTOS
				if($v_linea>0){
					$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,l.descrip as linea,g.descrip as grupo,l.descrip as linea
					from material m inner join grupmat g on m.grupmatid=g.grupmatid 
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					Where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.01.' AND '01.01.59') and m.codigo not like '%.'  and m.lineamatid='".$v_linea."'  ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}else{
					$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,l.descrip as linea,g.descrip as grupo,l.descrip as linea 
					from material m inner join grupmat g on m.grupmatid=g.grupmatid 
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					Where g.grupmatid  in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.01.' AND '01.01.59') and m.codigo not like '%.' ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}
			}
			
			if($v_grupo==2)
			{
				//REPUESTOS
				if($v_linea>0){
					$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,l.descrip as linea,g.descrip as grupo,l.descrip as linea
					from material m inner join grupmat g on m.grupmatid=g.grupmatid 
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					Where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and m.codigo not like '%.'  and m.lineamatid='".$v_linea."'  ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}else{
					$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,l.descrip as linea,g.descrip as grupo,l.descrip as linea 
					from material m inner join grupmat g on m.grupmatid=g.grupmatid 
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					Where g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and m.codigo not like '%.'  ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}
			}
		}else{
			if($v_linea>0){
				$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,l.descrip as linea,g.descrip as grupo,l.descrip as linea 
				from material m inner join grupmat g on m.grupmatid=g.grupmatid 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)
				Where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and m.codigo not like '%.'  and m.lineamatid='".$v_linea."'  ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}else{
				$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,l.descrip as linea,g.descrip as grupo,l.descrip as linea 
				from material m inner join grupmat g on m.grupmatid=g.grupmatid 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)
				Where g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and m.codigo not like '%.'  ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}	
		}
		
		
		if($co = $conect_bd_actual->consulta($vsql))
		{
			
			while($r = $co->fetch(PDO::FETCH_OBJ))
			{
				//if($vcount==0)
				//{
					
					
					if($v_cantidad>$vmes){
						$valor =(13-($v_cantidad-$vmes));
						$limite = 12;
						
						//ponemos los titulos
						
							if($v_valida==0)
							{
								echo "<table border='1' id='dato_productos' class='table' style='margin-left:2px;margin-right:2px;width:99%;'>";
								
								//año anterior
								echo "<thead>";
								echo "<tr>";
								echo "<td colspan='".($v_cantidad-($vmes)+4)."' style='text-align:left;'>AÑO: ".(date("Y")-1)."</td>";
								
								//año actual
								echo "<td colspan='".($vmes+4)."'  style='text-align:left;'>AÑO: ".date("Y")."</td>";
								echo "</tr>";
								
								//echo "<tr>";
								
								echo "<tr>";
								echo "<td style='color:white;background:black;'></td>";
								echo "<td style='color:white;background:black;'></td>";
								echo "<td style='color:white;background:black;'>GRUPO</td>";
								echo "<td style='color:white;background:black;'>LINEA</td>";
								//echo "</tr>";
								
								for($v=$valor;$v<=$limite;$v++)
								{
											
									switch($v)
									{
										case 1:
											echo "<td style='color:white;background:black;'>ENE</td>";
										break;
										
										case 2:
											echo "<td style='color:white;background:black;'>FEB</td>";
										break;
										
										case 3:
											echo "<td style='color:white;background:black;'>MAR</td>";
										break;
										
										case 4:
											echo "<td style='color:white;background:black;'>ABR</td>";
										break;
										
										case 5:
											echo "<td style='color:white;background:black;'>MAY</td>";
										break;
										
										case 6:
											echo "<td style='color:white;background:black;'>JUN</td>";
										break;
										
										case 7:
											echo "<td style='color:white;background:black;'>JUL</td>";
										break;
										
										case 8:
											echo "<td style='color:white;background:black;'>AGO</td>";
										break;
										
										case 9:
											echo "<td style='color:white;background:black;'>SEP</td>";
										break;
										
										case 10:
											echo "<td style='color:white;background:black;'>OCT</td>";
										break;
										
										case 11:
											echo "<td style='color:white;background:black;'>NOV</td>";
										break;
										
										case 12:
											echo "<td style='color:white;background:black;'>DIC</td>";
										break;
									}
								}
							}
						
						if($v_valida==0)
						{
							for($v=1;$v<=$vmes;$v++)
							{
								
								
								switch($v)
								{
									case 1:
										echo "<td style='color:white;background:black;'>ENE</td>";
									break;
									
									case 2:
										echo "<td style='color:white;background:black;'>FEB</td>";
									break;
									
									case 3:
										echo "<td style='color:white;background:black;'>MAR</td>";
									break;
									
									case 4:
										echo "<td style='color:white;background:black;'>ABR</td>";
									break;
									
									case 5:
										echo "<td style='color:white;background:black;'>MAY</td>";
									break;
									
									case 6:
										echo "<td style='color:white;background:black;'>JUN</td>";
									break;
									
									case 7:
										echo "<td style='color:white;background:black;'>JUL</td>";
									break;
									
									case 8:
										echo "<td style='color:white;background:black;'>AGO</td>";
									break;
									
									case 9:
										echo "<td style='color:white;background:black;'>SEP</td>";
									break;
									
									case 10:
										echo "<td style='color:white;background:black;'>OCT</td>";
									break;
									
									case 11:
										echo "<td style='color:white;background:black;'>NOV</td>";
									break;
									
									case 12:
										echo "<td style='color:white;background:black;'>DIC</td>";
									break;
								}
								
								if($v==($vmes))
								{	
									echo "<td style='color:white;background:black;'>PROM</td>";
									echo "<td style='color:white;background:black;'>EXIS</td>";
									echo "<td style='color:white;background:black;'>MIN</td>";
									echo "<td style='color:white;background:black;'>MAX</td>";
									echo "</tr>";
									echo "</thead>";
									//echo "<tbody>";
								}
							}
							$v_valida=1;
						}
					}else{
						$valor = ($vmes-$v_cantidad)+1;
						//$limite =$vmes; 
						
						
							
							if($v_valida==0)
							{
								echo "<table border='1' id='dato_productos' class='table' style='margin-left:2px;margin-right:2px;width:99%;'>";
								
								//año anterior
							/*	echo "<thead>";
								echo "<tr>";
								echo "<td colspan='".($v_cantidad-($vmes)+2)."' style='text-align:left;'>AÑO: ".(date("Y")-1)."</td>";*/
								
								//año actual
								echo "<td colspan='".($v_cantidad+8)."'  style='text-align:left;'>AÑO: ".date("Y")."</td>";
								echo "</tr>";
								
								//echo "<tr>";
								
								echo "<tr>";
								echo "<td style='color:white;background:black;'></td>";
								echo "<td style='color:white;background:black;'></td>";
								echo "<td style='color:white;background:black;'>GRUPO</td>";
								echo "<td style='color:white;background:black;'>LINEA</td>";
								//echo "</tr>";
								
							
								for($v=$valor;$v<=$vmes;$v++)
								{
							
									switch($v)
									{
										case 1:
											echo "<td style='color:white;background:black;'>ENE</td>";
										break;
										
										case 2:
											echo "<td style='color:white;background:black;'>FEB</td>";
										break;
										
										case 3:
											echo "<td style='color:white;background:black;'>MAR</td>";
										break;
										
										case 4:
											echo "<td style='color:white;background:black;'>ABR</td>";
										break;
										
										case 5:
											echo "<td style='color:white;background:black;'>MAY</td>";
										break;
										
										case 6:
											echo "<td style='color:white;background:black;'>JUN</td>";
										break;
										
										case 7:
											echo "<td style='color:white;background:black;'>JUL</td>";
										break;
										
										case 8:
											echo "<td style='color:white;background:black;'>AGO</td>";
										break;
										
										case 9:
											echo "<td style='color:white;background:black;'>SEP</td>";
										break;
										
										case 10:
											echo "<td style='color:white;background:black;'>OCT</td>";
										break;
										
										case 11:
											echo "<td style='color:white;background:black;'>NOV</td>";
										break;
										
										case 12:
											echo "<td style='color:white;background:black;'>DIC</td>";
										break;
									}
									
									if($v==($vmes))
									{	
										echo "<td style='color:white;background:black;'>PROM</td>";
										echo "<td style='color:white;background:black;'>EXIS</td>";
										echo "<td style='color:white;background:black;'>MIN</td>";
										echo "<td style='color:white;background:black;'>MAX</td>";
										echo "</tr>";
										echo "</thead>";
										//echo "<tbody>";
									}
									
								}
								$v_valida=1;
							}
						
					}
					
					
					
					
					
					if($v_cantidad>$vmes){
						$valor =(13-($v_cantidad-$vmes));
						$limite = 12;
						
						//ponemos los titulos
						
						
						
						for($v=$valor;$v<=$limite;$v++)
						{
							//echo $v."<br>";
							$vsql = "select EXTRACT(MONTH FROM k.fecha) as periodo, cast(sum(d.canmat) as char(15)) as acumulado_anterior,
								cast((SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as dias_laborados,
								cast(sum(d.canmat)/(SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as promedio_diario
								from dekardex d inner join kardex k on d.kardexid=k.kardexid
								inner join material m on d.matid=m.matid
								where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)='".$v."' group by 1";
								
							//echo $vsql."<br><br>";
							
							
							
							//ponemos el producto
							if($v==$valor)
							{
								echo "<tr>";
								echo "<td style='text-align:left;'>".utf8_encode($r->CODIGO)."</td>";
								echo "<td style='text-align:left;'>".utf8_encode($r->DESCRIP)."</td>";
								echo "<td style='text-align:left;'>".utf8_encode($r->GRUPO)."</td>";
								echo "<td style='text-align:left;'>".utf8_encode($r->LINEA)."</td>";
							}
							
							$vsiconexion = false;
								
							if($co2 = $conect_bd_anterior->consulta($vsql))
							{
								$vcontador = 1;
								$vcontador_mes = $valor;
								while($r2 = $co2->fetch(PDO::FETCH_OBJ))
								{
									$vsiconexion = true;
							
									echo "<td>".intval($r2->ACUMULADO_ANTERIOR)."</td>";
									$vsumatotal += intval($r2->ACUMULADO_ANTERIOR); 
													
									$vcontador++;
									$vcontador_mes++;
								}//fin segundo while
							}//fin consulta sumado
							
							if(!$vsiconexion and $v<=$limite)
							{
								echo "<td>0</td>";
							}
						}
						
						for($v=1;$v<=$vmes;$v++)
						{
							//echo $v."<br><br>";
							
							$vsiconexion = false;
							
							//consulta sumado
							$vsql = "select EXTRACT(MONTH FROM k.fecha) as periodo, cast(sum(d.canmat) as char(15)) as acumulado,
									cast((SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as dias_laborados,
									cast(sum(d.canmat)/(SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as promedio_diario
									from dekardex d inner join kardex k on d.kardexid=k.kardexid
									inner join material m on d.matid=m.matid
									where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)='".$v."' group by 1";
									
									//echo $vsql."<br><br>";
									
							if($co3 = $conect_bd_actual->consulta($vsql))
							{
								$vcontador2 = 1;
								while($r3 = $co3->fetch(PDO::FETCH_OBJ))
								{
									$vsiconexion = true;
									
									echo "<td>".intval($r3->ACUMULADO)."</td>";
									$vsumatotal += intval($r3->ACUMULADO);
									
									$vcontador2++;
								}
							}
							
							if(!$vsiconexion and $v<=$vmes)
							{
								echo "<td>0</td>";
							}
							
							if($v==$vmes)
							{
								$vsql = "select cast(s.existenc as char(15)) as existencia, cast(s.existmin as char(15)) as minimo,cast(s.existmax as char(15)) as maximo from materialsuc s inner join material m on s.matid=m.matid where m.codigo='".$r->CODIGO."' and s.sucid=1";
								//echo $vsql;
								if($co4 = $conect_bd_actual->consulta($vsql))
								{
									if($r4 = $co4->fetch(PDO::FETCH_OBJ))
									{
										$vexistenciafinal = floatval($r4->EXISTENCIA);
										$vmin = floatval($r4->MINIMO);
										$vmax= floatval($r4->MAXIMO);
									}
								}
								
								$vpromedio = $vsumatotal/$v_cantidad;
								echo "<td>".number_format($vpromedio,2)."</td>";
								echo "<td>".number_format($vexistenciafinal,2)."</td>";
								echo "<td>".number_format($vmin,2)."</td>";
								echo "<td>".number_format($vmax,2)."</td>";
								$vsumatotal = 0;
							}
							
							if(!$vsiconexion and $v==$vmes)
							{
								echo "</tr>";
							}
						}
						
					}else{
						$valor = ($vmes-$v_cantidad)+1;
						//$limite =$vmes; 
						
						for($v=$valor;$v<=$vmes;$v++)
						{
							//echo $v."<br><br>";
							
							$vsiconexion = false;
							
							if($v==$valor)
							{
								echo "<tr>";
								echo "<td style='text-align:left;'>".utf8_encode($r->CODIGO)."</td>";
								echo "<td style='text-align:left;'>".utf8_encode($r->DESCRIP)."</td>";
								echo "<td style='text-align:left;'>".utf8_encode($r->GRUPO)."</td>";
								echo "<td style='text-align:left;'>".utf8_encode($r->LINEA)."</td>";
							}
							
							//consulta sumado
							$vsql = "select EXTRACT(MONTH FROM k.fecha) as periodo, cast(sum(d.canmat) as char(15)) as acumulado,
									cast((SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as dias_laborados,
									cast(sum(d.canmat)/(SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as promedio_diario
									from dekardex d inner join kardex k on d.kardexid=k.kardexid
									inner join material m on d.matid=m.matid
									where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)='".$v."' group by 1";
									
									//echo $vsql."<br><br>";
									
							if($co3 = $conect_bd_actual->consulta($vsql))
							{
								$vcontador2 = 1;
								while($r3 = $co3->fetch(PDO::FETCH_OBJ))
								{
									$vsiconexion = true;
									
									echo "<td>".intval($r3->ACUMULADO)."</td>";
									$vsumatotal += intval($r3->ACUMULADO);
									
									$vcontador2++;
								}
							}
							
							if(!$vsiconexion and $v<=$vmes)
							{
								echo "<td>0</td>";
							}
							
							if($v==$vmes)
							{
								$vsql = "select cast(s.existenc as char(15)) as existencia, cast(s.existmin as char(15)) as minimo,cast(s.existmax as char(15)) as maximo from materialsuc s inner join material m on s.matid=m.matid where m.codigo='".$r->CODIGO."' and s.sucid=1";
								//echo $vsql;
								if($co4 = $conect_bd_actual->consulta($vsql))
								{
									if($r4 = $co4->fetch(PDO::FETCH_OBJ))
									{
										$vexistenciafinal = floatval($r4->EXISTENCIA);
										$vmin = floatval($r4->MINIMO);
										$vmax= floatval($r4->MAXIMO);
									}
								}
								
								$vpromedio = $vsumatotal/$v_cantidad;
								echo "<td>".number_format($vpromedio,2)."</td>";
								echo "<td>".number_format($vexistenciafinal,2)."</td>";
								echo "<td>".number_format($vmin,2)."</td>";
								echo "<td>".number_format($vmax,2)."</td>";
								$vsumatotal = 0;
							}
							
							if(!$vsiconexion and $v==$vmes)
							{
								echo "</tr>";
							}
						}
					} 
					
					
					
					
				//}//fin $vcount
				
				
				
				
				/*
				if($co2 = $conect_bd_anterior->consulta($vsql))
				{
					$vcontador = 1;
					$vcontador_mes = $vmes;
					while($r2 = $co2->fetch(PDO::FETCH_OBJ))
					{
				
						echo "<td>".intval($r2->ACUMULADO_ANTERIOR)."</td>";
						
						if($vcontador==$vmes)
						{
						
							//consulta sumado
							$vsql = "select EXTRACT(MONTH FROM k.fecha) as periodo, cast(sum(d.canmat) as char(15)) as acumulado,
									cast((SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as dias_laborados,
									cast(sum(d.canmat)/(SELECT count(DISTINCT kk.FECHA) AS DIAS_LABORADOS FROM KARDEX kk WHERE EXTRACT(MONTH FROM kk.FECHA)=EXTRACT(MONTH FROM k.fecha) AND kk.CODCOMP='FV' AND kk.FECASENTAD IS NOT NULL AND kk.FECANULADO IS NULL) as char(15)) as promedio_diario
									from dekardex d inner join kardex k on d.kardexid=k.kardexid
									inner join material m on d.matid=m.matid
									where k.codcomp='FV' and k.fecasentad is not null and m.codigo='".$r->CODIGO."' and EXTRACT(MONTH FROM k.fecha)<'".$vmes."' group by 1";
									
							if($co3 = $conect_bd_actual->consulta($vsql))
							{
								$vcontador2 = 1;
								while($r3 = $co3->fetch(PDO::FETCH_OBJ))
								{
									echo "<td>".intval($r3->ACUMULADO)."</td>";
									
									$vcontador2++;
								}
							}
							echo "</tr>";
							echo "</table><br><br>";
						}
						$vcontador++;
						$vcontador_mes++;
					}//fin segundo while
				}//fin consulta sumado*/
				
				$vcount++;
			}//fin while
		}//fin recorrer productos
	//}
}	
//echo "</tbody>";
echo "</table>";
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

</script>