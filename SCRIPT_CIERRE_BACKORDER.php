<?php
date_default_timezone_set('America/Bogota');

session_start();


	$v_contrato="";
	$v_idcontrato="";
	$v_numero="";
	$v_ultimo_movimiento="";

	$v_consec=1;

	$v_fecha = date('Y-m-d H:i');
	$v_dia = date('Y-m-d');


	require("tns/conexion.php");
	require("conecta.php");
	

	$vbd = "".$linea."";
	$vbd_inv = "".$lineainv."";
	if($cxf = new dbFirebird($vbd))
	{
		if($cxf1 = new dbFirebird($vbd_inv))
		{
			
			//NOS CONECTAMOS A LA BASE DE DATOS DE INVENTARIOS 
			$vsql2 = "SELECT ID,PREFIJO_ORDEN_PEDIDO,PREFIJO_MUSICAL,DIAS_PARA_CIERRE AS DIAS_CIERRE FROM CONFIGURACIONES WHERE ID='1'";
			//echo $vsql2;
			if($vc2 = $cxf1->consulta($vsql2))
			{	
				if($vr2 = ibase_fetch_object($vc2)){
					
					
					$vsql = "select k.fecasentad as fecha_asentado,d.dekardexid as dekardexid,d.matid as matid,k.codprefijo||k.numero as pedido from dekardex as d inner join kardex as k on(d.kardexid=k.kardexid) where k.codcomp='PC' and (k.codprefijo='".$vr2->PREFIJO_ORDEN_PEDIDO."' or k.codprefijo='".$vr2->PREFIJO_MUSICAL."') and k.fecasentad is not null and (k.sn_estado_inv='FACTURADO' or k.sn_estado_inv='PROCESO')";
					//echo "<script>console.log('Console: " . $vsql. "' );</script>";
			
					if($vc = $conect_bd_actual->consulta($vsql))
					{
							
						while($vr = ibase_fetch_object($vc))
						{
							$vtemp="+ ".$vr2->DIAS_CIERRE." day";
							$v_fechamaxima = date("Y-m-d",strtotime($vr->FECHA_ASENTADO.$vtemp)); 
							
							echo $v_dia."<br>";
							echo $v_fechamaxima,"<br>";
							
							if($v_dia>=$v_fechamaxima){
								
								$vsql1 = "update dekardex set sn_estado_backorder='CERRADO',sn_estadobk_update='".$v_fecha."'";
								if($vc1 = $cxf1->consulta($vsql1))
								{	
									
								}
								
								$vsql1 = "select max(id) as consec from sn_log_cierre_backorder";
								if($vc1 = $cxf1->consulta($vsql1))
								{	
									if($vr1 = ibase_fetch_object($vc1))
									{
										$v_consec = $vr1->CONSEC+1;
									}
								}
								
								$vsql2 = "insert into sn_log_cierre_backorder(id,fecha,dekardexid,matid,pedido) values ('".$v_consec."','".$v_fecha."','".$vr->DEKARDEXID."','".$vr->MATID."','".$vr->PEDIDO."')";
								//echo $vsql2;
								if($vc2 = $cxf1->consulta($vsql2))
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
