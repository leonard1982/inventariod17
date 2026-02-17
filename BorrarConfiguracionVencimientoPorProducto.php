<?php
session_start();

if(isset($_POST["grupo"]))
{
	
	$v_accion="";
	$v_grupo	= $_POST["grupo"];
	$v_meses    = $_POST["meses"];
	$v_id		= $_POST["id"];
	$v_consec=1;

	
	require("conecta.php");
	
	$vsql = "delete from sn_inv_vence_grupo where id ='".$v_id."'";
	if($conect_bd_actual->consulta($vsql))
	{
		$v_accion = "borro";
	}
	else
	{
		$v_accion = "noborro";
	}
	
	echo json_encode(array(

		"accion"=>$v_accion,
		"id"=>$v_id,
		"grupo"=>$v_grupo,
		"meses"=>$v_meses,
		"sql"=>$vsql
	));
}	
?>	