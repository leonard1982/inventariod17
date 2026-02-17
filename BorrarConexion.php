<?php
session_start();

if(isset($_POST["anio"]))
{
	
	$v_accion="";
	$v_anio 	= $_POST["anio"];
	$v_ruta		= $_POST["ruta"];
	$v_id		= $_POST["id"];
	$v_consec=1;

	
	require("conecta.php");
	
	$vsql = "delete from bd_anios where id ='".$v_id."'";
	if($conect_bd_inventario->consulta($vsql))
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
		"anio"=>$v_anio,
		"ruta"=>$v_ruta,
		"sql"=>$vsql
	));
}	
?>	