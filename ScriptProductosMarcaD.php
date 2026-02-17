<?php
date_default_timezone_set('America/Bogota');
session_start();




	$v_existencia=0;
	$v_totalcompleto=0;
	$v_totalcantidad=0;

	$v_fecha = date('Y-m-d');
	$v_fechaanterior = date("Y-m-d",strtotime($v_fecha."- 1 month"));
	$v_fechaanterior = date("Y-m-d",strtotime($v_fechaanterior."- 1 year"));
	$v_mes = date("m",strtotime($v_fechaanterior));
	$v_year = date("Y",strtotime($v_fechaanterior));

	$v_fechabusqueda=date("Y-m-d",strtotime($v_year.'-'.$v_mes.'-'.'01'));
	

	require("tns/conexion.php");
	require("conecta.php");
	
	//$vbd = "C:\Datos TNS\COMERCIALMEYER2022.GDB";
	$vbd = "".$linea."";
	if($cxf = new dbFirebird($vbd))
	{
		$vsql = "select m.unidad,m.matid as matid,m.descrip as descripcion,sm.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid)
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where  m.matid not in(select d.matid from dekardex d inner join kardex as k on d.kardexid=k.kardexid where k.codcomp!='NI') and (ms.fecultcli is null or ms.fecultcli<='".$v_fechabusqueda."') and ms.fecultprov >'".$v_fechabusqueda."'  and ms.existenc>0 and sm.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98' ) and  g.codigo not between '01.04.' AND '01.05.ZZ'";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			while($vr = ibase_fetch_object($vc))
			{
				
				$vsql1 = "update material set marcaartid=(select mar.marcaartid from marcaart as mar where mar.codigo='D') where matid='".$vr->MATID."'";
				if($vc1 = $conect_bd_actual->consulta($vsql1))
				{
					echo $vsql1."<br>";
				}	
				
			}
		}	
		
	}
		
?>	
