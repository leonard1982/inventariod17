<?php
session_start();

if(isset($_POST["codigo"]))
{
	
	$v_accion="";
	$v_codigo 	= $_POST["codigo"];
	$v_descripcion	= $_POST["descripcion"];
	$v_id		= $_POST["id"];
	$v_orden		= $_POST["orden"];
	$v_consec=1;

	
	require("tns/conexion.php");
	require("conecta.php");
	
	$vbd = "".$lineainv."";
	if($cxf = new dbFirebird($vbd))
	{	
		
		$vsql = "delete from estados_pedidos where id ='".$v_id."'";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			$v_accion		   = "borro";
			
		}else{
			$v_accion		   = "noborro";
		}
		
		//insertamos en el log
		$vsql = "select max(id) as id from log_estados_pedidos";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			while($vr = ibase_fetch_object($vc))
			{
				$v_consecl	  			= utf8_encode($vr->ID);
				$v_consecl++;
			}
		}
		$v_fecha = date("Y-m-d H:m:s");
		$v_datos = "ID:".$v_id.", CODIGO:".$v_codigo.", DESCRIPCION:".$v_descripcion.", ORDEN:".$v_orden;
		$vsql = "insert into log_estados_pedidos (id,fecha,datos_ant,operacion,usuario) values ('".$v_consecl."','".$v_fecha."','".$v_datos."','ELIMINA','".$_SESSION["user"]."')";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
				
		}else{
			
		}
		
	}
	
	//print_r($v_datos);
	
	echo json_encode(array(

		"accion"=>$v_accion,
		"id"=>$v_id,
		"codigo"=>$v_codigo,
		"descripcion"=>$v_descripcion,
		"orden"=>$v_orden,
		"sql"=>$vsql
	));
}	
?>	