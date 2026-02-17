<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include_once 'php/baseDeDatos.php';
include_once 'php/importarExcel.php';

$filename = "Rotacion Inventario.xls";
header("Content-type: application/x-msdownload; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, GET-check=0, pre-check=0");

echo "<div class='' style='overflow:auto;width:100%;'>";

echo "<br>";


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
$vmes_inicial = $vmes;
$vporcentaje_seguridad = 0;
$vtiempo_entrega       = 0;
$vdias_laborados       = 0;
$vdias_inventario      = 0;
$vsumatotal            = 0;
$vexistenciafinal      = 0;


if(isset($_GET['cant']))
{
	$vfinicial = $_GET['cant'];
	//$vfinicial = substr($vfinicial, 0, -9);
	$vfinicial = date_create($vfinicial);
	$vfinicial = date_format($vfinicial,'Y-m-d');
	
	$vcantidad_dias = "";
	
	$v_cantidad  = $_GET['cant'];
	$v_registros = $_GET['reg'];
	$v_grupo     = $_GET['grupo'];
	$v_linea     = $_GET['linea'];
	//si es mes es menor a junio consultamos también la base de datos anterior

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
				$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia, coalesce(s.fecultprov,'') as fecultprov, s.fecultcli,l.descrip as linea,CAST(s.existenc as varchar(10)) as existencia 
				from material m inner join grupmat g on m.grupmatid=g.grupmatid 
				inner join materialsuc s on s.matid=m.matid 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)	
				Where s.sucid='1' and g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.01.' AND '01.01.59') and s.fecultprov>='".$vfinicial."'  and m.lineamatid='".$v_linea."'";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}else{
				$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia, coalesce(s.fecultprov,'') as fecultprov, s.fecultcli,l.descrip as linea ,CAST(s.existenc as varchar(10)) as existencia 
				from material m inner join grupmat g on m.grupmatid=g.grupmatid 
				inner join materialsuc s on s.matid=m.matid 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)	
				Where s.sucid='1' and g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.01.' AND '01.01.59') and s.fecultprov>='".$vfinicial."' ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}
		}

		if($v_grupo==2)
		{
			//REPUESTOS
			if($v_linea>0){
				$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia, coalesce(s.fecultprov,'') as fecultprov, s.fecultcli,l.descrip as linea,CAST(s.existenc as varchar(10)) as existencia 
				from material m inner join grupmat g on m.grupmatid=g.grupmatid 
				inner join materialsuc s on s.matid=m.matid 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)	
				Where s.sucid='1' and g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and s.fecultprov>='".$vfinicial."'  and m.lineamatid='".$v_linea."'";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}else{
				$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia, coalesce(s.fecultprov,'') as fecultprov, s.fecultcli,l.descrip as linea ,CAST(s.existenc as varchar(10)) as existencia 
				from material m inner join grupmat g on m.grupmatid=g.grupmatid 
				inner join materialsuc s on s.matid=m.matid 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)	
				Where s.sucid='1' and g.grupmatid in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and s.fecultprov>='".$vfinicial."' ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}
		}
	}
	else
	{
		if($v_linea>0){
			$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia, coalesce(s.fecultprov,'') as fecultprov, s.fecultcli,l.descrip as linea ,CAST(s.existenc as varchar(10)) as existencia 
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			inner join materialsuc s on s.matid=m.matid 
			inner join lineamat as l on (m.lineamatid=l.lineamatid)	
			Where s.sucid='1' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and s.fecultprov>='".$vfinicial."' and m.lineamatid='".$v_linea."'";
			echo "<script>console.log('Console: " . $vsql. "' );</script>";
		}else{
			$vsql = "select first ".$v_registros." m.matid,m.codigo,m.descrip,g.codigo as codgrupo,g.descrip as familia, coalesce(s.fecultprov,'') as fecultprov, s.fecultcli,l.descrip as linea ,CAST(s.existenc as varchar(10)) as existencia 
			from material m inner join grupmat g on m.grupmatid=g.grupmatid 
			inner join materialsuc s on s.matid=m.matid 
			inner join lineamat as l on (m.lineamatid=l.lineamatid)	
			Where s.sucid='1' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and s.fecultprov>='".$vfinicial."'";
			echo "<script>console.log('Console: " . $vsql. "' );</script>";
		}
	}
	
	if($co = $conect_bd_actual->consulta($vsql))
	{
		while($r = $co->fetch(PDO::FETCH_OBJ))
		{
			if($vcount==0)
			{
				//ponemos los titulos
				echo "<table border='1' id='dato_productos' class='table' style='margin-left:2px;margin-right:2px;width:99%;'>";
				echo "<thead>";
				echo "<tr>";	
				echo "<td style='color:white;background:black;text-align:left;'>FAMILIA</td>";
				echo "<td style='color:white;background:black;text-align:left;'>LINEA</td>";
				echo "<td style='color:white;background:black;text-align:left;'>CODIGO</td>";
				echo "<td style='color:white;background:black;text-align:left;'>DESCRIPCION</td>";
				echo "<td style='color:white;background:black;text-align:left;'>EXISTENCIA</td>";
				echo "<td style='color:white;background:black;text-align:left;'>A 30 DÍAS</td>";
				echo "<td style='color:white;background:black;text-align:left;'>A 60 DÍAS</td>";
				echo "<td style='color:white;background:black;text-align:left;'>A 90 DÍAS</td>";
				echo "<td style='color:white;background:black;text-align:left;'>A 120 DÍAS</td>";
				echo "<td style='color:white;background:black;text-align:left;'>MÁS DE 120 DÍAS</td>";
				echo "</tr>";
				echo "</thead>";
			}//fin $vcount
			
			$vcantidades_compradas = 0;
			$vcantidades_vendidas  = 0;
			
			if(!empty($r->FECULTPROV) and !empty($r->FECULTCLI))
			{
				$vf1 = substr($r->FECULTPROV, 0, -9);
				$vf2 = substr($r->FECULTCLI, 0, -9);
				
				$vf1  = new DateTime($vf1);
				$vf2  = new DateTime($vf2);
				$diff = $vf1->diff($vf2);
				
				$vcantidad_dias = $diff->days;
			}
			/*
			$vsql = "select cast(sum(IIF(k.codcomp='FV',d.canmat,0)) as char(15)) cantidades, cast(sum(IIF(k.codcomp='FC',d.canmat,0)) as char(15)) cantidades_comp
			from dekardex d inner join kardex k on d.kardexid=k.kardexid
			inner join material m on d.matid=m.matid
			where k.codcomp in('FC','FV') and k.fecasentad is not null and m.codigo like '".$r->CODIGO."' and k.fecha>='".$vfinicial."' and k.fecha<='".$vffinal."'";
			
			//echo $vsql."<br>";
			
			//sumamos el años anterior	
			if($co2 = $conect_bd_actual->consulta($vsql))
			{
				if($r2 = $co2->fetch(PDO::FETCH_OBJ))
				{
					$vcantidades_compradas = floatval($r2->CANTIDADES_COMP);
					$vcantidades_vendidas  = floatval($r2->CANTIDADES);
				}//fin segundo while
			}//fin consulta sumado
			*/

			echo "<tr>";
			echo "<td style='text-align:left;'>".$r->FAMILIA."</td>";
			echo "<td style='text-align:left;'>".utf8_encode($r->LINEA)."</td>";
			echo "<td style='text-align:left;'>".utf8_encode($r->CODIGO)."</td>";
			echo "<td style='text-align:left;'>".$r->DESCRIP."</td>";
			echo "<td style='text-align:left;'>".number_format($r->EXISTENCIA)."</td>";
			
			if($vcantidad_dias>=0 and $vcantidad_dias<=30)
			{
				echo "<td style='text-align:left;'>".number_format($vcantidad_dias)."</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
			}
			
			if($vcantidad_dias>30 and $vcantidad_dias<=60)
			{
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>".number_format($vcantidad_dias)."</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
			}
			
			if($vcantidad_dias>60 and $vcantidad_dias<=90)
			{
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>".number_format($vcantidad_dias)."</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
			}
			
			if($vcantidad_dias>90 and $vcantidad_dias<=120)
			{
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>".number_format($vcantidad_dias)."</td>";
				echo "<td style='text-align:left;'>0</td>";
			}
			
			if($vcantidad_dias>120)
			{
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>0</td>";
				echo "<td style='text-align:left;'>".number_format($vcantidad_dias)."</td>";
			}
			echo "</tr>";
			
			$vcount++;
		}//fin while
	}//fin recorrer productos
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
