<?php
require("conecta.php");

if (isset($_POST["porcentaje"])) {
	$v_accion    = "";
	$tipo_accion = "actualizar";

	if(isset($_POST["nuevo_registro"]))
	{
		$tipo_accion = "nuevo";
	}
	$params = [
		'porcentaje' => $_POST["porcentaje"] ?? '',
		'tiempo' => $_POST["tiempo"] ?? '',
		'dias' => $_POST["dias"] ?? '',
		'prefijo' => $_POST["prefijo"] ?? '',
		'dias_pedidos' => $_POST["dias_pedidos"] ?? '',
		'tendencia_meses' => $_POST["tendencia_meses"] ?? '',
		'prefijo_traslado' => $_POST["prefijo_traslado"] ?? '',
		'prefijo_orden' => $_POST["prefijo_orden"] ?? '',
		'correo' => $_POST["correo"] ?? '',
		'prefijo_musical' => $_POST["prefijo_musical"] ?? '',
		'dias_cierre' => $_POST["dias_cierre"] ?? '',
		'grupo' => $_POST["grupo"] ?? '',
		'ejecutar' => $_POST["ejecutar"] ?? '',
		'iniciar' => $_POST["iniciar"] ?? '',
		'id' => $_POST["id"] ?? '',
		'proveedor' => $_POST["proveedor"] ?? ''
	];

	$vdatosn = "Por:{$params['porcentaje']}-Tie:{$params['tiempo']}-Dias:{$params['dias']}-PrP:{$params['prefijo']}-DiasP{$params['dias_pedidos']}";
	$vdatosn1 = "-Ten:{$params['tendencia_meses']}-PrT:{$params['prefijo_traslado']}-PrO:{$params['prefijo_orden']}-Correo:{$params['correo']}";

	fCrearLogTNS($_SESSION["user"], 'EL USUARIO ' . $_SESSION["user"] . ' ACTUALIZO LA CONFIGURACION DE LA PLATAFORMA WEB DE INVENTARIOS_AUTO', $contenidoBdActual);
	fCrearLogTNS($_SESSION["user"], $vdatosn . $vdatosn1, $contenidoBdActual);

	if ($tipo_accion == "nuevo") {
		// Prepare the SQL query for insertion
		$vsql = "INSERT INTO configuraciones (id,
			correo_notificacion, porcentaje_seguridad, tiempo_entrega, dias_inventario, prefijo_pedidos, dias_pedidos, 
			tendencia_meses, prefijo_traslado, prefijo_orden_pedido, prefijo_musical, dias_para_cierre, grupo, 
			ejecutar_cada, iniciar_en, actualizado, usuario, nit_proveedor
		) VALUES (
			(SELECT COALESCE(MAX(id), 0) + 1 FROM configuraciones), 
			" . (empty($params['correo']) ? "NULL" : "'{$params['correo']}'") . ", 
			" . (empty($params['porcentaje']) ? "'0'" : "'{$params['porcentaje']}'") . ", 
			" . (empty($params['tiempo']) ? "NULL" : "'{$params['tiempo']}'") . ", 
			" . (empty($params['dias']) ? "NULL" : "'{$params['dias']}'") . ", 
			" . (empty($params['prefijo']) ? "NULL" : "'{$params['prefijo']}'") . ", 
			" . (empty($params['dias_pedidos']) ? "NULL" : "'{$params['dias_pedidos']}'") . ", 
			" . (empty($params['tendencia_meses']) ? "NULL" : "'{$params['tendencia_meses']}'") . ", 
			" . (empty($params['prefijo_traslado']) ? "NULL" : "'{$params['prefijo_traslado']}'") . ", 
			" . (empty($params['prefijo_orden']) ? "NULL" : "'{$params['prefijo_orden']}'") . ", 
			" . (empty($params['prefijo_musical']) ? "NULL" : "'{$params['prefijo_musical']}'") . ", 
			" . (empty($params['dias_cierre']) ? "NULL" : "'{$params['dias_cierre']}'") . ", 
			" . (empty($params['grupo']) ? "NULL" : "'{$params['grupo']}'") . ", 
			" . (empty($params['ejecutar']) ? "NULL" : "'{$params['ejecutar']}'") . ", 
			" . (empty($params['iniciar']) ? "NULL" : "'{$params['iniciar']}'") . ",
			CAST('NOW' AS TIMESTAMP),
			'".$_SESSION['user']."',
			" . (empty($params['proveedor']) ? "NULL" : "'{$params['proveedor']}'") . ")";
	} else {
		// Prepare the SQL query for update
		$vsql = "UPDATE configuraciones SET 
			correo_notificacion=" . (empty($params['correo']) ? "NULL" : "'{$params['correo']}'") . ", 
			porcentaje_seguridad=" . (empty($params['porcentaje']) ? "'0'" : "'{$params['porcentaje']}'") . ", 
			tiempo_entrega=" . (empty($params['tiempo']) ? "NULL" : "'{$params['tiempo']}'") . ", 
			dias_inventario=" . (empty($params['dias']) ? "NULL" : "'{$params['dias']}'") . ", 
			prefijo_pedidos=" . (empty($params['prefijo']) ? "NULL" : "'{$params['prefijo']}'") . ", 
			dias_pedidos=" . (empty($params['dias_pedidos']) ? "NULL" : "'{$params['dias_pedidos']}'") . ", 
			tendencia_meses=" . (empty($params['tendencia_meses']) ? "NULL" : "'{$params['tendencia_meses']}'") . ", 
			prefijo_traslado=" . (empty($params['prefijo_traslado']) ? "NULL" : "'{$params['prefijo_traslado']}'") . ", 
			prefijo_orden_pedido=" . (empty($params['prefijo_orden']) ? "NULL" : "'{$params['prefijo_orden']}'") . ", 
			prefijo_musical=" . (empty($params['prefijo_musical']) ? "NULL" : "'{$params['prefijo_musical']}'") . ", 
			dias_para_cierre=" . (empty($params['dias_cierre']) ? "NULL" : "'{$params['dias_cierre']}'") . ", 
			grupo=" . (empty($params['grupo']) ? "NULL" : "'{$params['grupo']}'") . ", 
			ejecutar_cada=" . (empty($params['ejecutar']) ? "NULL" : "'{$params['ejecutar']}'") . ", 
			iniciar_en=" . (empty($params['iniciar']) ? "NULL" : "'{$params['iniciar']}'") . ",
			actualizado=CAST('NOW' AS TIMESTAMP),
			usuario='{$_SESSION['user']}',
			nit_proveedor=" . (empty($params['proveedor']) ? "NULL" : "'{$params['proveedor']}'") . "	WHERE id = ".$params['id'];
	}

	$v_accion = $conect_bd_inventario->consulta($vsql) ? ($tipo_accion == "nuevo" ? "creo" : "actualizo") : "noactualizo";

	echo json_encode([
		"accion" => $v_accion,
		"tipo_accion" => $tipo_accion,
		"porcentaje" => $params['porcentaje'],
		"tiempo" => $params['tiempo'],
		"dias" => $params['dias'],
		"prefijo" => $params['prefijo'],
		"sql" => $vsql
	]);
}
?>