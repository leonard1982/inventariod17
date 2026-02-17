<?php
if(isset($_POST["desde"]))
{
	$vdesde = $_POST["desde"];
	
	include_once 'php/baseDeDatos.php';
	$ip  = '127.0.0.1';

	$bd_desde = new dbFirebird($vdesde,$ip);
	
	//consultamos todos los valores de financiacion del la base de datos origen y los buscamos en la base destino y actualizamos
	$vsql = "select (codcomp||codprefijo||numero) as doc, coalesce(vrfinan,0) as vrfinan from documento where (codcomp||codprefijo||numero)<>'000000'";
	if($co = $bd_desde->consulta($vsql))
	{
		while($r = ibase_fetch_object($co))
		{
			//consultamos en la base de datos destino
			$vdoc    = $r->DOC;
			$vfinan  = $r->VRFINAN;
			
			//si existe el documento en cartera lo actualiza en vrfinan
			$vsql = "select docuid from documento where (codcomp||codprefijo||numero) = '".$vdoc."'";
			if($co2 = $bd_hasta->consulta($vsql))
			{
				if($r2 = ibase_fetch_object($co2))
				{
					$vdocuid = $r2->DOCUID;
					
					$vsql = "update documento set vrfinan='".$vfinan."' where docuid='".$vdocuid."'";
					$bd_hasta->consulta($vsql);
				}
			}
		}
	}
}
else
{
	echo "No existen los parámetros.";
}
?>