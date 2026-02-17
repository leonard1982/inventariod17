<?php
session_start();

require("conecta.php");

$vemail     = "";

//nos traemos el correo de notificacion
$vsql = "select correo_notificacion from configuraciones where id='1'";
if($dx = $conect_bd_inventario->consulta($vsql))
{
	if($rx = ibase_fetch_object($dx))
	{
		$vemail = $rx->CORREO_NOTIFICACION;
	}
}

// Consulta para obtener todos los grupos y sus configuraciones de vencimiento
$vsql_grupos = "SELECT vg.id, g.codigo||' - '||g.descrip as grupo, vg.meses_vencimiento, vg.grupmatid, g.codigo 
				FROM sn_inv_vence_grupo vg 
				INNER JOIN grupmat g ON vg.grupmatid=g.grupmatid 
				ORDER BY vg.id DESC";

$grupos = $conect_bd_actual->consulta($vsql_grupos);

$productos = array();

while ($grupo = ibase_fetch_object($grupos)) {
	$v_grupo = $grupo->GRUPMATID;
	$v_meses = $grupo->MESES_VENCIMIENTO;

	// Consulta SQL para obtener los productos que pertenecen a un grupo específico y que están próximos a vencerse
	$vsql_productos = "SELECT s.fecultprov as fecha, m.codigo as codproducto, m.descrip as producto, g.grupmatid
					   FROM material m
					   INNER JOIN materialsuc s ON s.matid = m.matid
					   INNER JOIN grupmat g ON m.grupmatid = g.grupmatid
					   WHERE m.grupmatid = '".$v_grupo."' 
					   AND s.fecultprov IS NOT NULL
					   AND (EXTRACT(YEAR FROM CAST('NOW' AS DATE)) - EXTRACT(YEAR FROM s.fecultprov)) * 12 + (EXTRACT(MONTH FROM CAST('NOW' AS DATE)) - EXTRACT(MONTH FROM s.fecultprov)) <= $v_meses
					   AND DATEADD(month, $v_meses, s.fecultprov) <= DATEADD(month, 1, CAST('NOW' AS DATE))";

	if ($datos = $conect_bd_actual->consulta($vsql_productos)) {
		while ($registro = ibase_fetch_object($datos)) {
			$v_fecha = date("d-m-Y", strtotime($registro->FECHA));
			$v_codigo = $registro->CODPRODUCTO;
			$v_descripcion = $registro->PRODUCTO;

			$fecha_vencimiento = strtotime($v_fecha . " + $v_meses months");
			$meses_diferencia = (date("Y", $fecha_vencimiento) - date("Y")) * 12 + (date("m", $fecha_vencimiento) - date("m"));
			$v_meses_vencimiento = round($meses_diferencia);
			$v_meses_vencimiento = round(($fecha_vencimiento - time()) / (30 * 24 * 60 * 60));

			$productos[] = array(
				"fecha_compra" => $v_fecha,
				"codigo" => $v_codigo,
				"descripcion" => utf8_encode($v_descripcion),
				"meses_para_vencerse" => $v_meses_vencimiento,
				"grupo" => utf8_encode($grupo->GRUPO),
				"fecha_vencimiento" => date("d-m-Y", $fecha_vencimiento),
				"meses_diferencia" => $meses_diferencia,
				"meses"=> $v_meses
			);
		}
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
	$filas .= '<td>' . htmlspecialchars($producto['grupo']) . '</td>';
	$filas .= '<td>' . $producto['meses'] . '</td>';
	$vmesesvence = str_replace('-','',$producto['meses_para_vencerse']);
	
	if($vmesesvence<=0)
	{
		if($vmesesvence==0)
		{
			$filas .= '<td style="color:white;background-color:red;">' . htmlspecialchars($vmesesvence) . '</td>';
		}
		
		if($vmesesvence<0)
		{
			$filas .= '<td style="color:white;background-color:red;">' . htmlspecialchars($producto['meses_para_vencerse']) . '</td>';
		}
	}
	else
	{
		switch($vmesesvence)
		{
			case 1:
				$filas .= '<td style="color:white;background-color:orange;">' . htmlspecialchars($vmesesvence) . '</td>';
			break;
			
			case 2:
				$filas .= '<td style="color:white;background-color:yellow;">' . htmlspecialchars($vmesesvence) . '</td>';
			break;

			case 3:
				$filas .= '<td style="color:white;background-color:green;">' . htmlspecialchars($vmesesvence) . '</td>';
			break;

			default:
				$filas .= '<td>' . htmlspecialchars($vmesesvence) . '</td>';
			break;
		}
	}
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
				<th>Grupo</th>
				<th>Config. Vencimiento Meses</th>
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
	// Leer el archivo línea por línea
	$lineas = file('servidor_smtp.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

	// Asignar a variables
	$de = trim($lineas[0]);
	$nombreDe = trim($lineas[1]);
	$servidorSMTP = trim($lineas[2]);
	$puertoSMTP = intval(trim($lineas[3]));
	$usuarioSMTP = trim($lineas[4]);
	$contrasenaSMTP = trim($lineas[5]);

	// Llamada a la función
	$v_accion = enviarCorreoSMTP(
		$destinatario,
		$asunto,
		$mensaje,
		$de,
		$nombreDe,
		$servidorSMTP,
		$puertoSMTP,
		$usuarioSMTP,
		$contrasenaSMTP,
		$cco
	);

} else {
	$v_accion = "No hay productos próximos a vencerse.";
}

echo json_encode(array(
	"accion" => $v_accion,
	"productos" => $productos
));
?>
