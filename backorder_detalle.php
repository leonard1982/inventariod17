
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
$varchivopj   = "f:/facilweb_fe73_32/htdocs/evento_inventario/prefijos.txt";
$vprefijos    = "";
$vbd_actual   = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_actual.txt";
$vbd_anterior = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_anterior.txt";
$vbd_inventarios = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_inventarios.txt";
$kardexid     = "";
$vcontador    = 0;
if(isset($_GET["kardexid"]))
{
	$kardexid = $_GET["kardexid"];
}
?>

<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Detalle BackOrder</title>
	
	<style>
	.estado-select {
		min-width: 150px; /* Ajusta según sea necesario */
	}
	</style>
	<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
	<script>
	document.addEventListener("DOMContentLoaded", function() {
		document.querySelectorAll('.estado-select').forEach(select => {
			select.addEventListener('change', function() {
				const estadoNuevo = this.value;
				const estadoAnterior = this.getAttribute('data-estado-actual'); 
				const dekardexid = this.getAttribute('data-dekardexid');

				Swal.fire({
					title: "¿Confirmas el cambio de estado?",
					text: `Pasará de "${estadoAnterior}" a "${estadoNuevo}".`,
					icon: "warning",
					showCancelButton: true,
					confirmButtonColor: "#3085d6",
					cancelButtonColor: "#d33",
					confirmButtonText: "Sí, cambiar",
					cancelButtonText: "No, cancelar"
				}).then((result) => {
					console.log(JSON.stringify({ estado: estadoNuevo, dekardexid: dekardexid }));
					if (result.isConfirmed) {
						fetch('backorder_actualizar_estado.php', {
							method: 'POST',
							headers: { 'Content-Type': 'application/json' },
							body: JSON.stringify({ estado: estadoNuevo, dekardexid: dekardexid })
						})
						.then(response => {
							console.log("Estado de la respuesta:", response.status);
							return response.text(); // 🔹 Verifica si hay contenido antes de parsear JSON
						})
						.then(text => {
							console.log("Respuesta del servidor:", text);
							return JSON.parse(text); // 🔹 Solo convierte a JSON si no está vacío
						})
						.then(data => {
							console.log("Datos procesados:", data);
							if (data.success) {
								location.reload();
								this.setAttribute('data-estado-actual', estadoNuevo);
								Swal.fire("¡Cambio exitoso!", "El estado ha sido actualizado.", "success");
							} else {
								this.value = estadoAnterior; 
								Swal.fire("Error", "No se pudo actualizar el estado.", "error");
							}
						})
						.catch(error => {
							console.error("Error de JSON:", error);
							this.value = estadoAnterior; 
							Swal.fire("Error", "Hubo un problema en la conexión.", "error");
						});
					} else {
						console.log("Console log antes: ");
						console.log(this.value);
						this.value = estadoAnterior; // 🔹 Restaurar inmediatamente el estado anterior
						console.log("Console log despues: ");
						console.log(this.value);
					}
				});
			});
		});
	});
	</script>
  </head>
	
  <tbody>
<?php
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

//echo $kardexid;
$vsql = "select d.dekardexid,
            cast(substring((m.codigo||' - '||m.descrip) from 1 for 40) as char(40)) as producto,
			cast(d.canmat as int) as cantidad_pedida,
			iif(cast(d.canmat as int)<>cast(coalesce((select sum(dd.canmat) from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and dd.matid=d.matid),0) as int),cast(coalesce((select sum(dd.canmat) from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and dd.matid=d.matid),0) as int),0) as cantidad_recibida,
			m.unidad,
            cast(substring(coalesce(d.sn_estado_backorder,'PENDIENTE') from 1 for 10) as char(10)) as estado,
			k.codprefijo,
			k.numero,
			t.nombre as proveedor,
		    coalesce((select first 1 kkk.codprefijo||'/'||kkk.numero from kardex kkk inner join dekardex ddd on ddd.kardexid=kkk.kardexid
		    where ddd.matid=d.matid and kkk.sn_orden_compra=k.kardexid),'') as factura
            from kardex k
            inner join dekardex d on d.kardexid=k.kardexid
            inner join material m on d.matid=m.matid
			inner join terceros t on k.cliente=t.terid
            where k.kardexid in(select kk.sn_orden_compra from kardex kk)
            and (d.matid not in(select dd.matid from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid) or
            d.matid in(select dd.matid from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and d.canmat>dd.canmat) or
            d.matid in(select dd.matid from dekardex dd inner join kardex kk on dd.kardexid=kk.kardexid where kk.sn_orden_compra=k.kardexid and d.canmat<dd.canmat))
			and k.kardexid='".$kardexid."'";
			
			//echo $vsql;

?>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<div class="container mt-4">
	<?php
	if ($conect_bd_actual = new dbFirebirdPDO($ip, $vbd_actual)) {
		if ($cox = $conect_bd_actual->consulta($vsql)) {
			
			while ($rx = $cox->fetch(PDO::FETCH_OBJ))
			{
				$vcant_recibida = 0;
				$vcant_pedida   = 0;
			
				if($vcontador==0)
				{
					?>
					<h3 class="text-center">Detalle BackOrder - Pedido <?php echo $rx->CODPREFIJO."/".$rx->NUMERO." - ".utf8_encode($rx->PROVEEDOR); ?></h3>
					<table class="table table-striped table-bordered">
					<thead class="table-dark">
						<tr>
							<th>Producto</th>
							<th>Cantidad Pedida</th>
							<th>Cantidad Recibida</th>
							<th style='background-color:red;'>Diferencia</th>
							<th>Unidad</th>
							<th>Estado</th>
							<th>Factura</th>
						</tr>
					</thead>
					<tbody>
					<?php
				}
				if($rx->CANTIDAD_PEDIDA>0)
				{
					$vcant_pedida = $rx->CANTIDAD_PEDIDA;
				}
				if($rx->CANTIDAD_RECIBIDA>0)
				{
					$vcant_recibida = $rx->CANTIDAD_RECIBIDA;
				}
				
				$vdiferencia = floatval($vcant_recibida)-floatval($vcant_pedida);
				
				if (trim($rx->ESTADO) === "CERRADO") {
					echo "<tr class='table-secondary'>"; // Gris claro con Bootstrap
				} elseif ($vdiferencia > 0) {
					echo "<tr class='table-primary'>"; // Azul claro con Bootstrap
				} elseif ($vdiferencia < 0) {
					echo "<tr class='table-danger'>"; // Rojo claro con Bootstrap
				} else {
					echo "<tr class='table-success'>"; // Verde claro con Bootstrap
				}


				echo "<td>".utf8_encode($rx->PRODUCTO)."</td>";
				echo "<td style='text-align:right;'>".$vcant_pedida."</td>";
				echo "<td style='text-align:right;'>".$vcant_recibida."</td>";
				echo "<td style='text-align:right;'>".($vdiferencia)."</td>";
				echo "<td>".utf8_encode($rx->UNIDAD)."</td>";
				
				if($vdiferencia==0)
				{
					echo "<td></td>";
				}
				else
				{
					//echo "<td>{$rx->ESTADO}</td>";
					echo '<td>';
					echo '<select class="form-select estado-select" data-dekardexid="' . $rx->DEKARDEXID . '" data-estado-actual="' . trim($rx->ESTADO) . '">';
					echo '<option value="PENDIENTE" ' . (trim($rx->ESTADO) == "PENDIENTE" ? "selected" : "") . '>PENDIENTE</option>';
					echo '<option value="CERRADO" ' . (trim($rx->ESTADO) == "CERRADO" ? "selected" : "") . '>CERRADO</option>';
					echo '</select>';
					echo '</td>';
				}
				echo "<td>{$rx->FACTURA}</td>";
				echo "</tr>";
				
				$vcontador++;
			}
			
			if($vcontador>0)
			{
				echo "</tbody></table>";
			}
		}
	}
	?>
</div>
