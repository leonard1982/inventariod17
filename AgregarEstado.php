<?php
require("conecta.php");

function getMaxId($table, $conect_bd_inventario) {
	$vsql = "SELECT MAX(id) AS id FROM $table";
	$v_consec = 1;
	if ($vc = $conect_bd_inventario->consulta($vsql)) {
		while ($vr = ibase_fetch_object($vc)) {
			$v_consec = utf8_encode($vr->ID);
			$v_consec++;
		}
	}
	return $v_consec;
}

function insertLog($conect_bd_inventario, $v_consecl, $v_fecha, $v_datosant, $v_datos, $operacion, $usuario) {
	$vsql = "INSERT INTO log_estados_pedidos (id, fecha, datos_ant, datos_nue, operacion, usuario) VALUES ('$v_consecl', '$v_fecha', '$v_datosant', '$v_datos', '$operacion', '$usuario')";
	$conect_bd_inventario->consulta($vsql);
}

if (isset($_POST["codigo"])) {
	$v_accion = "";
	$v_codigo = $_POST["codigo"];
	$v_descripcion = $_POST["descripcion"];
	$v_id = $_POST["id"];
	$v_orden = $_POST["orden"];
	$v_consec = getMaxId('estados_pedidos', $conect_bd_inventario);
	$v_consecl = getMaxId('log_estados_pedidos', $conect_bd_inventario);
	$v_fecha = date("Y-m-d H:i:s");

	if ($v_id == 0) {
		// Insertar nuevo estado
		$vsql = "INSERT INTO estados_pedidos (id, codigo, descripcion, orden) VALUES ('$v_consec', '$v_codigo', '" . strtoupper($v_descripcion) . "', '$v_orden')";
		$v_accion = $conect_bd_inventario->consulta($vsql) ? "conregistro" : "sinregistro";

		// Insertar en el log
		$v_datos = "CODIGO:$v_codigo, DESCRIPCION:$v_descripcion, ORDEN:$v_orden";
		insertLog($conect_bd_inventario, $v_consecl, $v_fecha, '', $v_datos, 'INSERTA', $_SESSION["user"]);
	} else {
		// Obtener datos anteriores
		$vsql = "SELECT * FROM estados_pedidos WHERE id='$v_id'";
		$v_datosant = "";
		if ($vc = $conect_bd_inventario->consulta($vsql)) {
			while ($vr = ibase_fetch_object($vc)) {
				$v_datosant = "ID:$vr->ID, CODIGO:$vr->CODIGO, DESCRIPCION:$vr->DESCRIPCION, ORDEN:$vr->ORDEN";
			}
		}

		// Actualizar estado
		$vsql = "UPDATE estados_pedidos SET codigo='$v_codigo', descripcion='" . strtoupper($v_descripcion) . "', orden='$v_orden' WHERE id='$v_id'";
		$v_accion = $conect_bd_inventario->consulta($vsql) ? "actualizo" : "noactualizo";

		// Insertar en el log
		$v_datos = "ID:$v_id, CODIGO:$v_codigo, DESCRIPCION:$v_descripcion, ORDEN:$v_orden";
		insertLog($conect_bd_inventario, $v_consecl, $v_fecha, $v_datosant, $v_datos, 'ACTUALIZA', $_SESSION["user"]);
	}

	echo json_encode(array(
		"accion" => $v_accion,
		"id" => $v_id,
		"codigo" => $v_codigo,
		"descripcion" => $v_descripcion,
		"orden" => $v_orden,
		"sql" => $vsql
	));
}
?>
