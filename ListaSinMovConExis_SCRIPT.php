<?php
date_default_timezone_set('America/Bogota');
//date_default_timezone_set('Europe/London');
session_start();



	$v_contrato="";
	$v_idcontrato="";
	$v_numero="";
	$v_existencia=0;
	$v_totalcosto =0;
	$v_fecha = date('Y-m-d');
	$v_fechaanterior = date("Y-m-d",strtotime($v_fecha."- 1 month"));
	$v_fechaanterior = date("Y-m-d",strtotime($v_fechaanterior."- 1 year"));
	$v_mes = date("m",strtotime($v_fechaanterior));
	$v_year = date("Y",strtotime($v_fechaanterior));

	$v_fechabusqueda=date("Y-m-d",strtotime($v_year.'-'.$v_mes.'-'.'01'));
	
	$ip           = '127.0.0.1';
	$vbd_inventarios = "f:/facilweb_fe73_32/htdocs/evento_inventario/bd_inventarios.txt";

	require("tns/conexion.php");
	require("conecta.php");
	
	//$vbd = "C:\Datos TNS\COMERCIALMEYER2022.GDB";
	
	$v_numerotras =1;
	$v_existeidtras=0;

	$vbd = "".$linea."";
	$vbd_inventarios= "".$lineainv."";
	if($cxf = new dbFirebird($vbd))
	{
		
		
		$vsql = "select m.unidad,m.matid,m.descrip as descripcion,sm.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
		,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
		from material as m inner join grupmat g on m.grupmatid=g.grupmatid
		inner join materialsuc as ms on(m.matid=ms.matid) 
		inner join salmaterial as sm on(m.matid=sm.matid)
		inner join lineamat as l on (m.lineamatid=l.lineamatid)
		where  m.matid not in(select d.matid from dekardex d inner join kardex as k on d.kardexid=k.kardexid where k.codcomp!='NI') and ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and sm.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98') and  g.codigo not between '01.04.' AND '01.05.ZZ'   order by ms.fecultcli asc ";
		//echo "<script>console.log('Console: " . $vsql. "' );</script>";
		//echo $vsql;
		
		if($vc = $conect_bd_actual->consulta($vsql))
		{
					
					$vsql = "SELECT ID,PORCENTAJE_SEGURIDAD,TIEMPO_ENTREGA, DIAS_INVENTARIO,TENDENCIA_MESES,PREFIJO_TRASLADO,PREFIJO_MUSICAL FROM CONFIGURACIONES WHERE ID='1'";
					if($cxf1 = new dbFirebird($vbd_inventarios))
					{
						if($vcc = $cxf1->consulta($vsql))
						{
							if($vrc = ibase_fetch_object($vcc))
							{
								$vporcentaje_seguridad = $vrc->PORCENTAJE_SEGURIDAD;
								$vtiempo_entrega       = $vrc->TIEMPO_ENTREGA;
								$vdias_inventario      = $vrc->DIAS_INVENTARIO;
								$vtendencia_meses      = (int)$vrc->TENDENCIA_MESES;
								$vprefijo_traslado     = $vrc->PREFIJO_TRASLADO;
								$vprefijo_musical     = $vrc->PREFIJO_MUSICAL;
							}
						}
					}
					
					while($vr = ibase_fetch_object($vc))
					{
						$v_existeidtras=0;
											
						
					
							$vsql="select * from trasla where codprefijo='".$vprefijo_traslado."' and bodini='".$vr->IDBODEGA."' and fecasentad is null and fecha>='2024-06-01'";
							if($vcv = $conect_bd_actual->consulta($vsql))
							{
								if($vrv = ibase_fetch_object($vcv))
								{
									$v_existeidtras = $vrv->TRASLAID;
								}	
							}
							
						
						
						
						if($v_existeidtras==0){
						
								$vsql="select max(numero) as numero from trasla where codprefijo='".$vprefijo_traslado."'";
								if($vc1 = $conect_bd_actual->consulta($vsql))
								{
									if($vr1 = ibase_fetch_object($vc1))
									{
										$v_numerotras = $vr1->NUMERO+1;
									}	
								}

							
							
							if($vr->IDBODEGA==17 or $vr->IDBODEGA=='17'){
								$vsql="insert into trasla (traslaid,codprefijo,numero,fecha,observ,periodo,bodini,bodfin,sucid) values(GEN_ID(TRASLAID_GEN,1),'".$vprefijo_traslado."','".$v_numerotras."','".date('Y-m-d')."','CREADO AUTOMATICAMENTE','".date('m')."','".$vr->IDBODEGA."',(select bodid from bodega where codigo='98'),'1')";
							}else{
								$vsql="insert into trasla (traslaid,codprefijo,numero,fecha,observ,periodo,bodini,bodfin,sucid) values(GEN_ID(TRASLAID_GEN,1),'".$vprefijo_traslado."','".$v_numerotras."','".date('Y-m-d')."','CREADO AUTOMATICAMENTE','".date('m')."','".$vr->IDBODEGA."',(select bodid from bodega where codigo='99'),'1')";
							}												
							//echo $vsql;
							if($vc2 = $conect_bd_actual->consulta($vsql))
							{
								
							}
						}
						
						if($v_existeidtras==0){
			
			
								fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' GENERO EL TRASLADO '.$vprefijo_traslado.'-'.$v_numerotras.' DE FORMA MANUAL',$vbd);
								$vsql="select traslaid  from trasla where codprefijo='".$vprefijo_traslado."' and numero='".$v_numerotras."'";
								if($vc3 = $conect_bd_actual->consulta($vsql))
								{
									if($vr3 = ibase_fetch_object($vc3))
									{
										$v_existedet=true;
										$vsql="select * from detrasla where traslaid='".$vr3->TRASLAID."' and matid='".$vr->MATID."' ";
										if($vcvd = $conect_bd_actual->consulta($vsql))
										{
											if($vrvd = ibase_fetch_object($vcvd))
											{
												$v_existedet=false;
											}	
										}
										
										if($v_existedet){
											$vsql="insert into detrasla (detraslaid,traslaid,matid,cantidad,tipund) values(GEN_ID(DETRASLAID_GEN,1),'".$vr3->TRASLAID."','".$vr->MATID."','".$vr->EXISTENCIA."','D')";
											if($vc4 = $conect_bd_actual->consulta($vsql))
											{
													
											}
										}	
									}	
								}
							
						}else{

							$v_existedet=true;
							$vsql="select * from detrasla where traslaid='".$v_existeidtras."' and matid='".$vr->MATID."' ";
							if($vcvd = $conect_bd_actual->consulta($vsql))
							{
								if($vrvd = ibase_fetch_object($vcvd))
								{
									$v_existedet=false;
								}	
							}
							
							if($v_existedet){
								$vsql="insert into detrasla (detraslaid,traslaid,matid,cantidad,tipund) values(GEN_ID(DETRASLAID_GEN,1),'".$v_existeidtras."','".$vr->MATID."','".$vr->EXISTENCIA."','D')";
								if($vc4 = $conect_bd_actual->consulta($vsql))
								{
										
								}
							}	
								
							
						}
								
							
						
						
						$v_existencia = $v_existencia + $vr->EXISTENCIA;
						$v_costo = $vr->EXISTENCIA * $vr->COSTO;
						$v_totalcosto = $v_totalcosto + $v_costo;
						setlocale(LC_ALL,"es_ES","esp");
						$v_fecha = date("Y-m-d", strtotime($vr->FECHA));
						//$v_fecha=strftime("%d de %B de %Y", strtotime($v_fecha));
						
						//cantidad de dias
						$vcantidad_dias = "";
						$vultventa      = "";
						if(!empty($vr->FECULTCLI))
						{
							if($vr->FECULTCLI > $vr->FECHA){
								$vf1 = substr($vr->FECULTCLI, 0, -9);
								$vultventa = $vf1;
								$vf2 = date("Y-m-d");
								
								$vf1  = new DateTime($vf1);
								$vf2  = new DateTime($vf2);
								$diff = $vf1->diff($vf2);
								
								$vcantidad_dias = $diff->days;
							}else{
								$vf1 = substr($vr->FECHA, 0, -9);
								$vultventa = $vf1;
								$vf2 = date("Y-m-d");
								
								$vf1  = new DateTime($vf1);
								$vf2  = new DateTime($vf2);
								$diff = $vf1->diff($vf2);
								
								$vcantidad_dias = $diff->days;
							}
							
						}else{
							
							if($vr->FECULTCLI > $vr->FECHA){
								$vf1 = substr($vr->FECULTCLI, 0, -9);
								$vultventa = $vf1;
								$vf2 = date("Y-m-d");
								
								$vf1  = new DateTime($vf1);
								$vf2  = new DateTime($vf2);
								$diff = $vf1->diff($vf2);
								
								$vcantidad_dias = $diff->days;
							}else{
								$vf1 = substr($vr->FECHA, 0, -9);
								$vultventa = "";
								$vf2 = date("Y-m-d");
								
								$vf1  = new DateTime($vf1);
								$vf2  = new DateTime($vf2);
								$diff = $vf1->diff($vf2);
								
								$vcantidad_dias = $diff->days;
							}
						}
						
						$vcolor="background-color:white;";
						
						if(!empty($vr->TRASLADO)){
							$vcolor="background-color:YELLOW;";
						}else{
							$vcolor="background-color:white;";
						}
						
					}
		}
	}
		
?>	
