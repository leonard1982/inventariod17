<?php
session_start();

require("conecta.php");

$vemail    = "";
$vmatid    = "";
$v_accion  = "";
$vcodigo   = "";
$vdescrip  = "";

if (isset($argv[1]))
{
    $vmatid = $argv[1];
}

if (isset($_GET["m"]))
{
    $vmatid = $_GET["m"];
}

if(!empty($vmatid))
{
	//nos traemos el correo de notificacion
	$vsql = "select correo_notificacion from configuraciones where id='1'";
	if($dx = $conect_bd_inventario->consulta($vsql))
	{
		if($rx = ibase_fetch_object($dx))
		{
			$vemail = $rx->CORREO_NOTIFICACION;
		}
	}

	// Consulta para obtener los datos del producto
	$vsql = "select codigo, descrip from material where matid='".$vmatid."'";

	$vproducto = $conect_bd_actual->consulta($vsql);

	if($vprod = ibase_fetch_object($vproducto))
	{
		$vcodigo  = $vprod->CODIGO;
		$vdescrip = $vprod->DESCRIP;
		
		$destinatario = $vemail;
		$cco          = "leo2904.trabajo@gmail.com";
		$asunto       = "Notificación de Vencimiento";

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
				<h2>Producto para pedir</h2>
			</div>
			<p>Estimado usuario,</p>
			<p>Le informamos que el siguiente producto han llegado al punto de pedido:</p>
			<table>
				<thead>
					<tr>
						<th>Código</th>
						<th>Descripción</th>
					</tr>
				</thead>
				<tbody>
					<tr>
						<th>'.$vcodigo.'</th>
						<th>'.$vdescrip.'</th>
					</tr>
				</tbody>
			</table>
			<p>Por favor, tome las medidas necesarias para gestionar estos productos.</p>
			<p>Atentamente,</p>
			<p>Sistema de Notificación</p>
		</body>
		</html>';

		if (!empty($vcodigo)) {
			// Leer el archivo línea por línea
			$lineas = file('F:\facilweb_fe73_32\htdocs\evento_inventario\servidor_smtp.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

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

		}
		else
		{
			$v_accion = "No hay producto.";
		}
	}
}
echo json_encode(array(
	"accion" => $v_accion,
	"codigo" => $vcodigo,
	"descripcion" => $vdescrip,
	"matid" => $vmatid
));
?>
