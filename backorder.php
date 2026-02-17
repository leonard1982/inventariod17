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
$varchivopj   = __DIR__ . "/prefijos.txt";
$vprefijos    = "";
$vbd_actual   = __DIR__ . "/bd_actual.txt";
$vbd_anterior = __DIR__ . "/bd_anterior.txt";
$vbd_inventarios = __DIR__ . "/bd_inventarios.txt";
?>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>BackOrder</title>
  </head>
	
  <tbody>
  <button class='btn btn-primary' id='recargar' >Recargar</button>
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

/*$vsql = 'select d.dekardexid as id, 
            cast(substring((m.codigo||" - "||m.descrip) from 1 for 40) as char(40)) as producto,
			d.canmat as cantidad_pedida,
			iif(cast(d.canmat as int)-cast(coalesce((select sum(dd.canmat) from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and dd.matid=d.matid),0) as int)>=0,cast(d.canmat as int)-cast(coalesce((select sum(dd.canmat) from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and dd.matid=d.matid),0) as int),0) as cantidad_recibida,
			m.unidad, 
            cast(substring(coalesce(d.sn_estado_backorder,"PENDIENTE") from 1 for 10) as char(10)) as estado 
            from kardex k   
            inner join dekardex d on d.kardexid=k.kardexid   
            inner join material m on d.matid=m.matid  
            where k.kardexid in(select kk.sn_orden_compra from kardex kk)  
            and (d.matid not in(select dd.matid from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid) or   
            d.matid in(select dd.matid from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and d.canmat>dd.canmat) or 
            d.matid in(select dd.matid from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and d.canmat<dd.canmat))';
*/
$vsihaybackorder = false;
$vkardexid = "";
$vcontador = 0;
$vsql = "select distinct k.kardexid, k.codprefijo, k.numero, k.fecha ,t.nombre as proveedor
            from kardex k   
            inner join terceros t on k.cliente=t.terid 
            where k.kardexid in(select kk.sn_orden_compra from kardex kk) and k.codcomp='PC' and k.fecasentad is not null and k.sn_estado_inv='FACTURADO'";


if($conect_bd_actual = new dbFirebirdPDO($ip,$vbd_actual))
{
	if($cox2 = $conect_bd_actual->consulta($vsql))
	{
		while($rx2 = $cox2->fetch(PDO::FETCH_OBJ))
		{
			$vkardexid = $rx2->KARDEXID;
			
			$vsql2 = "select distinct m.codigo from dekardex d inner join material m on d.matid=m.matid where d.kardexid='".$vkardexid."' 
			and m.codigo in(select distinct mm.codigo from dekardex dd inner join material mm on dd.matid=mm.matid 
			where dd.kardexid in(select kk.kardexid from kardex kk where kk.sn_orden_compra='".$vkardexid."') and dd.canmat<>d.canmat)";
			
			//echo $vsql2."<br><br>";
			
			if($cox3 = $conect_bd_actual->consulta($vsql2))
			{
				$vsihaybackorder = false;
				
				while($rx3 = $cox3->fetch(PDO::FETCH_OBJ))
				{
					if(!empty($rx3->CODIGO))
					{
						$vsihaybackorder = true;
					}
				}
				
				if($vsihaybackorder)
				{
					if($vcontador==0)
					{
						echo '<div class="container mt-4">';
						echo '<h2 class="text-center">Informe de BackOrder</h2>';
						echo '<table class="table table-striped table-bordered">';
						echo '<thead class="table-dark">';
						echo '<tr>';
						//echo '<th></th>';
						echo '<th>Pedido</th>';
						echo '<th>Fecha</th>';
						echo '<th>Proveedor</th>';
						echo '<th>Acción</th>';
						echo '</tr>';
						echo '</thead>';
						echo '<tbody>';
					}
					$fecha = substr($rx2->FECHA, 0, -9);
					$fecha = date_create($fecha);
					$fecha = date_format($fecha,'d-m-Y');
		
					echo "<tr>";
					//echo "<td>".$rx2->KARDEXID."</td>";
					echo "<td>".$rx2->CODPREFIJO."/".$rx2->NUMERO."</td>";
					echo "<td>".$fecha."</td>";
					echo "<td>".$rx2->PROVEEDOR."</td>";
					echo "<td><button class='btn btn-primary' onclick='abrirVentana({$rx2->KARDEXID})'>Ver Detalle</button></td>";
					echo "</tr>";
					
					$vcontador++;

				}
			}
			
		}
		
		if($vcontador>0)
		{
			echo "</tbody></table></div>";
		}
	}
}
?>	
<!-- Modal Bootstrap -->
<div class="modal fade" id="modalBackOrder" tabindex="-1" aria-labelledby="modalLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl"> <!-- Cambiado a modal-xl para mayor ancho -->
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detalle del BackOrder</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
				<div class="ratio ratio-16x9">
					<iframe id="iframeBackOrder" src="" width="100%" height="800px" frameborder="0"></iframe>
				</div>
			</div>

        </div>
    </div>
</div>


<script>
function abrirVentana(kardexId) {
    document.getElementById('iframeBackOrder').src = 'backorder_detalle.php?kardexid=' + kardexId;
    var modal = new bootstrap.Modal(document.getElementById('modalBackOrder'));
    modal.show();
}
$(document).ready(function(){
	
	$("#recargar").click(function(){
		$("#backorder").click();
	});
});
</script>
