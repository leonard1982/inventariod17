<?php


if(isset($_POST["cantidad"]))
{
	$v_accion="";
	$v_accionlog="";
	$v_cantidad 	= $_POST["cantidad"];
	$v_bd  = $_POST["bd"];
	$v_contador =0;
	$v_consec=1;
	$vfec='';
	$vfec1='';
	
	include_once 'php/baseDeDatos.php';
	$ip         = '127.0.0.1';
	
	$vbd_seleccionada1 = str_replace("\\", "/", $v_bd);
	$vsi    = true;
	
	if(file_exists($v_bd))
	{	
		$bd_desde = new dbFirebird($v_bd,$ip);
	}
	else
	{
		echo "<h5 style='color:red;'>NO EXISTE LA BASE DE DATOS SELECCIONADA</h5>";
		$vsi = false;
	}
	
	if($vsi)
	{	
		$vsql = "UPDATE SN_CONFIGCARTERA SET CANTIDAD='".$v_cantidad."' WHERE ID=1 ";
		
		//echo $vsql;
		if($co = $bd_desde->consulta($vsql))
		{	
			
			$v_accion = "existe";
		
		}
	}
	
	//print_r($v_datos);
	
	echo json_encode(array(

		"accion"=>$v_accion,
		"cantidad"=>$v_cantidad,
		"sql"=>$vsql
	));
}	
?>	