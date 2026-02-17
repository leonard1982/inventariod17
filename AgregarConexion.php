<?php
require("conecta.php");

if (isset($_POST["anio"])) {
	$v_accion = "";
	$v_anio = $_POST["anio"];
	$v_ruta = $_POST["ruta"];
	$v_id = $_POST["id"];
	$v_consec = 1;

	if ($v_id == 0) {
		$vsql = "SELECT MAX(id) AS id FROM bd_anios";
		$vc = $conect_bd_inventario->consulta($vsql);
		if ($vc) {
			$vr = ibase_fetch_object($vc);
			if ($vr) {
				$v_consec = utf8_encode($vr->ID) + 1;
			}
		}

		$vsql = "INSERT INTO bd_anios (id, anio, ruta_bd) VALUES ('$v_consec', '$v_anio', '$v_ruta')";
		$vc = $conect_bd_inventario->consulta($vsql);
		$v_accion = $vc ? "conregistro" : "sinregistro";
	} else {
		$vsql = "UPDATE bd_anios SET anio='$v_anio', ruta_bd='$v_ruta' WHERE id='$v_id'";
		$vc = $conect_bd_inventario->consulta($vsql);
		$v_accion = $vc ? "actualizo" : "noactualizo";
	}

	echo json_encode([
		"accion" => $v_accion,
		"id" => $v_id,
		"anio" => $v_anio,
		"ruta" => $v_ruta,
		"sql" => $vsql
	]);
}
?>
