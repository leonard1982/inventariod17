<?php
require("conecta.php");

if (isset($_POST["grupo"]))
{
	$v_accion = "";
	$v_grupo = $_POST["grupo"];
	$v_meses = $_POST["meses"];
	$v_id = $_POST["id"];
	$v_consec = 1;

	if ($v_id == 0) {
		$vsql = "SELECT MAX(id) AS id FROM sn_inv_vence_grupo";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			if($vr = ibase_fetch_object($vc))
			{
				if(!empty($vr->ID))
				{
					$v_consec = $vr->ID + 1;
				}
			}
		}

		$vsql = "INSERT INTO sn_inv_vence_grupo (id, grupmatid, meses_vencimiento) VALUES ('$v_consec', '$v_grupo', '$v_meses')";
		$vc = $conect_bd_actual->consulta($vsql);
		$v_accion = $vc ? "conregistro" : "sinregistro";
	} else {
		$vsql = "UPDATE sn_inv_vence_grupo SET grupmatid='$v_grupo', meses_vencimiento='$v_meses' WHERE id='$v_id'";
		$vc = $conect_bd_actual->consulta($vsql);
		$v_accion = $vc ? "actualizo" : "noactualizo";
	}

	echo json_encode([
		"accion" => $v_accion,
		"id" => $v_id,
		"grupmatid" => $v_grupo,
		"meses_vencimiento" => $v_meses,
		"sql" => $vsql
	]);
}
?>
