<?php require("conecta.php"); ?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>Pedidos Automáticos Generados</title>
		<!--Llamamos las librerias css y js -->
		<?php includeAssets(); ?>	
	</head>	
<?php

	$v_existencia=0;
	$v_totalcompleto=0;

	$v_fecha = date('Y-m-d');
	$v_fechaanterior = date("Y-m-d",strtotime($v_fecha."- 1 month"));
	$v_fechaanterior = date("Y-m-d",strtotime($v_fechaanterior."- 1 year"));
	$v_mes = date("m",strtotime($v_fechaanterior));
	$v_year = date("Y",strtotime($v_fechaanterior));

	$v_fechabusqueda=date("Y-m-d",strtotime($v_year.'-'.$v_mes.'-'.'01'));
	
	fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' INGRESO EN LA OPCION (PEDIDOS GENERADOS) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$contenidoBdActual);
	
	$vsql = "SELECT
			  K.CODPREFIJO, 
			  K.CODPREFIJO ||'/'|| K.NUMERO AS NUMERO,
			  T.NOMBRE AS PROVEEDOR,
			  K.FECHA,
			  K.FECASENTAD AS ASENTADO,
			  K.SN_ESTADO_INV AS ESTADO,
			  DATEDIFF(DAY, K.FECHA, CURRENT_TIMESTAMP) AS DIAS
			FROM KARDEX K
			INNER JOIN TERCEROS T ON K.CLIENTE = T.TERID
			INNER JOIN KARDEXSELF S ON S.KARDEXID = K.KARDEXID 
			WHERE K.CODCOMP = 'PC'
			  AND K.SN_ESTADO_INV <> 'FINALIZADO'
			  AND S.PEDIDO IS NOT NULL AND S.PEDIDO <> '' 
			ORDER BY K.CODPREFIJO ASC, K.CLIENTE, K.KARDEXID DESC";
	?>
<body class="container py-4">

	<div class="container">
	<h2 class="mb-4">Pedidos Automáticos Generados</h2>

	<input type="text" id="filtro" class="form-control mb-3" placeholder="Buscar en la tabla...">

	<button id="exportar" class="btn btn-success mb-3">Exportar a Excel</button>

	<div class="table-responsive">
		<table id="tablaPedidos" class="table table-striped table-bordered">
			<thead style="background-color:#00324b;">
				<tr>
					<th style="color:white;">#</th>
					<th style="color:white;">Número</th>
					<th style="color:white;">Proveedor</th>
					<th style="color:white;">Fecha</th>
					<th style="color:white;">Asentado</th>
					<th style="color:white;">Estado</th>
					<th style="color:white;">Días Transcurridos</th>
					<th style="color:white;">Días Pedido</th>
				</tr>
			</thead>
			<tbody>
<?php
	$contador = 1;
	if($vc = $conect_bd_actual->consulta($vsql))
	{
		while($vr = ibase_fetch_object($vc))
		{
			$fecha     = substr($vr->FECHA, 0, 10);     // yyyy-mm-dd
			$asentado  = substr($vr->ASENTADO, 0, 10);  // yyyy-mm-dd

			echo "<tr>";
			echo "<td>" . $contador++ . "</td>";
			echo "<td>" . htmlentities($vr->NUMERO) . "</td>";
			echo "<td>" . htmlentities($vr->PROVEEDOR) . "</td>";
			echo "<td>" . htmlentities($fecha) . "</td>";
			echo "<td>" . htmlentities($asentado) . "</td>";
			echo "<td>" . htmlentities($vr->ESTADO) . "</td>";
			echo "<td>" . htmlentities($vr->DIAS) . "</td>";
			$vsql = "select first 1 dias_pedidos from configuraciones where prefijo_orden_pedido='".$vr->CODPREFIJO."'";
			if($cox = $conect_bd_inventario->consulta($vsql))
			{
				if($rx = ibase_fetch_object($cox))
				{
					echo "<td>" . htmlentities($rx->DIAS_PEDIDOS) . "</td>";
				}
			}
			echo "</tr>";
		}
	}
?>
			</tbody>
		</table>
	</div>
	</div>
	
	<script>
		// Filtro de búsqueda en tabla
		document.getElementById("filtro").addEventListener("keyup", function() {
			const filtro = this.value.toLowerCase();
			const filas = document.querySelectorAll("#tablaPedidos tbody tr");
			filas.forEach(fila => {
				const texto = fila.textContent.toLowerCase();
				fila.style.display = texto.includes(filtro) ? "" : "none";
			});
		});

		// Exportar a Excel
		document.getElementById("exportar").addEventListener("click", function() {
			const tabla = document.getElementById("tablaPedidos");
			const wb = XLSX.utils.table_to_book(tabla, {sheet: "Pedidos"});
			XLSX.writeFile(wb, "pedidos_generados.xlsx");
		});
	</script>
</body>
</html>
