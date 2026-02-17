<?php


if(isset($_POST["bd"]))
{
	$v_accion="";
	$v_accionlog="";
	$v_bd 	= $_POST["bd"];
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
		$vsql = "SELECT CANTIDAD FROM SN_CONFIGCARTERA";
		
		//echo $vsql;
		if($co = $bd_desde->consulta($vsql))
		{	
			
			while($r = ibase_fetch_object($co))
			{
				
				$v_consec = $r->CANTIDAD;
				$v_accion = "existe";
			}	
		}
	}
	
	//print_r($v_datos);
	
	echo json_encode(array(

		"accion"=>$v_accion,
		"cantidad"=>$v_consec,
		"sql"=>$vsql
	));
}	
?>	