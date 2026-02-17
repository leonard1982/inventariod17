<?php
session_start();

if(isset($_POST["grupo"]))
{
	
	$v_accion   ="";
	$v_grupo	= $_POST["grupo"];
	$v_meses    = $_POST["meses"];
	$v_id		= $_POST["id"];
	$productos  = array();
	$vemail     = "";
	
	require("conecta.php");
	
	//nos traemos el correo de notificacion
	$vsql = "select correo_notificacion from configuraciones where id='".$v_id."'";
	if($dx = $conect_bd_inventario->consulta($vsql))
	{
		if($rx = ibase_fetch_object($dx))
		{
			$vemail = $rx->CORREO_NOTIFICACION;
		}
	}

	// Consulta SQL para obtener los productos que pertenecen a un grupo específico y que están próximos a vencerse
	// - Se seleccionan las columnas: fecha, codprefijo, numero, codproducto, producto
	// - Se unen las tablas: kardex (k), dekardex (d), material (m), grupmat (g)
	// - Se filtran los registros donde:
	//   - k.codcomp es 'FC'
	//   - k.fecasentad no es nulo
	//   - m.grupmatid es igual al grupo especificado ($v_grupo)
	//   - La diferencia en meses entre la fecha actual y la fecha de compra es menor o igual a los meses especificados ($v_meses)
	//   - La fecha de compra más los meses especificados es menor o igual a un mes después de la fecha actual
	$vsql = "select s.fecultprov as fecha, m.codigo as codproducto, m.descrip as producto
	from material m
	inner join materialsuc s on s.matid = m.matid
	inner join grupmat g on m.grupmatid = g.grupmatid
	where m.grupmatid = '".$v_grupo."' 
	and s.fecultprov is not null
	and (EXTRACT(YEAR FROM CAST('NOW' AS DATE)) - EXTRACT(YEAR FROM s.fecultprov)) * 12 + (EXTRACT(MONTH FROM CAST('NOW' AS DATE)) - EXTRACT(MONTH FROM s.fecultprov)) <= $v_meses
	and DATEADD(month, $v_meses, s.fecultprov) <= DATEADD(month, 1, CAST('NOW' AS DATE))";

	//echo $vsql;
	

	if($datos = $conect_bd_actual->consulta($vsql))
	{
		while($registro = ibase_fetch_object($datos))
		{
			$v_fecha = date("d-m-Y", strtotime($registro->FECHA));
			$v_codigo = $registro->CODPRODUCTO;
			$v_descripcion = $registro->PRODUCTO;
			$v_meses_vencimiento = round((strtotime($v_fecha . " + $v_meses months") - time()) / (30 * 86400));

			$productos[] = array(
				"fecha_compra" => $v_fecha,
				"codigo" => $v_codigo,
				"descripcion" => $v_descripcion,
				"meses_para_vencerse" => $v_meses_vencimiento
			);
		}

	}

	$destinatario = $vemail;
	$cco          = "leo2904.trabajo@gmail.com";
	$asunto       = "Notificación de Vencimiento";

	// Generar las filas de la tabla
	$filas = '';
	foreach ($productos as $producto) {
		$filas .= '<tr>';
		$filas .= '<td>' . htmlspecialchars($producto['fecha_compra']) . '</td>';
		$filas .= '<td>' . htmlspecialchars($producto['codigo']) . '</td>';
		$filas .= '<td>' . htmlspecialchars($producto['descripcion']) . '</td>';
		$filas .= '<td>' . $v_meses. '</td>';
		$filas .= '<td>' . htmlspecialchars($producto['meses_para_vencerse']) . '</td>';
		$filas .= '</tr>';
	}

	$mensaje = "Este es un mensaje de prueba para notificar el vencimiento del producto.";
	$mensaje = '
	<!DOCTYPE html>
	<html lang="es">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<style>
			body {
				font-family: Arial, sans-serif;
				line-height: 1.6;
			}
			table {
				width: 100%;
				border-collapse: collapse;
			}
			th, td {
				padding: 8px 12px;
				border: 1px solid #ddd;
				text-align: left;
			}
			th {
				background-color: #f4f4f4;
			}
			.header {
				background-color: #4CAF50;
				color: white;
				padding: 10px 0;
				text-align: center;
			}
		</style>
	</head>
	<body>
		<div class="header">
			<h2>Notificación de Vencimiento de Productos</h2>
		</div>
		<p>Estimado usuario,</p>
		<p>Le informamos que los siguientes productos están próximos a vencerse o ya se han vencido:</p>
		<table>
			<thead>
				<tr>
					<th>Fecha de Compra</th>
					<th>Código</th>
					<th>Descripción</th>
					<th>Configuración Meses</th>
					<th>Meses para Vencerse</th>
				</tr>
			</thead>
			<tbody>
				' . $filas . '
			</tbody>
		</table>
		<p>Por favor, tome las medidas necesarias para gestionar estos productos.</p>
		<p>Atentamente,</p>
		<p>Sistema de Notificación</p>
	</body>
	</html>';

	if (!empty($productos)) {
		$de = "facturaelectronica.sectorsalud@solucionesnavarro.com";
		$nombreDe = "Sistema de Notificación";
		$servidorSMTP = "mail.solucionesnavarro.com";
		$puertoSMTP = 587;
		$usuarioSMTP = "facturaelectronica.sectorsalud@solucionesnavarro.com";
		$contrasenaSMTP = "Cw.9fn1-4yk.";

		$v_accion = enviarCorreoSMTP($destinatario, $asunto, $mensaje, $de, $nombreDe, $servidorSMTP, $puertoSMTP, $usuarioSMTP, $contrasenaSMTP,$cco);
	} else {
		$v_accion = "No hay productos próximos a vencerse.";
	}
	echo json_encode(array(

		"accion"=>$v_accion,
		"id"=>$v_id,
		"grupo"=>$v_grupo,
		"meses"=>$v_meses,
		"productos"=>$productos
	));
}	
?>	