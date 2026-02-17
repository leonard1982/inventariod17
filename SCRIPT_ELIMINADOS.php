<?php
date_default_timezone_set('America/Bogota');
//date_default_timezone_set('Europe/London');
session_start();


	$v_contrato="";
	$v_idcontrato="";
	$v_numero="";
	$v_ultimo_movimiento="";

	$v_consec=1;

	$v_fecha = date('Y-m-d H:i');


	require("tns/conexion.php");
	require("conecta.php");
	

	$vbd = "".$linea."";
	$vbd_inv = "".$lineainv."";
	if($cxf = new dbFirebird($vbd))
	{
		if($cxf1 = new dbFirebird($vbd_inv))
		{
			$vsql = "select m.matid as matid,m.codigo as codigo,m.descrip as descripcion,(select first 1 (k.codcomp||k.codprefijo||k.numero) as documento from kardex as k inner join dekardex as d on(k.kardexid=d.kardexid) where d.matid=m.matid order by k.fecha desc) as documento,(select first 1 k.fecha as fecha_documento from kardex as k inner join dekardex as d on(k.kardexid=d.kardexid) where d.matid=m.matid order by k.fecha desc) as fecha_documento 
					from material as m inner join materialsuc as ms on(m.matid=ms.matid) where m.marcaartid=(select ma.marcaartid from marcaart ma where ma.codigo='PELIMINAR') ";
					//echo "<script>console.log('Console: " . $vsql. "' );</script>";
			
			if($vc = $conect_bd_actual->consulta($vsql))
			{
					
				while($vr = ibase_fetch_object($vc))
				{
					
					$vsql1 = "select max(id) as consec from eliminados";
					if($vc1 = $cxf1->consulta($vsql1))
					{	
						if($vr1 = ibase_fetch_object($vc1))
						{
							$v_consec = $vr1->CONSEC+1;
						}
					}
					
					$v_ultimo_movimiento = $vr->DOCUMENTO."-".$vr->FECHA_DOCUMENTO;
					//NOS CONECTAMOS A LA BASE DE DATOS DE INVENTARIOS E INSERTAMOS LOS REGISTROS
					$vsql2 = "insert into eliminados(id,fecha,codigo,descripcion,ultimo_movimiento,matid) values ('".$v_consec."','".$v_fecha."','".$vr->CODIGO."','".$vr->DESCRIPCION."','".$v_ultimo_movimiento."','".$vr->MATID."')";
					//echo $vsql2;
					if($vc2 = $cxf1->consulta($vsql2))
					{	
						$vsqlel="delete from iniinvedad where matid='".$vr->MATID."' ";
						if($vcel = $conect_bd_actual->consulta($vsqlel))
						{
							$vsqle="delete from material where matid='".$vr->MATID."' ";
							if($vce = $conect_bd_actual->consulta($vsqle))
							{
								$vsqlu="update eliminados set eliminado='SI' where matid='".$vr->MATID."' and codigo='".$vr->CODIGO."' ";
								if($vcu = $cxf1->consulta($vsqlu))
								{
									
								}	
							}
						}
							
					}
				}
			}
		}
	}
		
?>	
