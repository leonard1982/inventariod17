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
		$vsql = "select m.descrip as descripcion,ms.existenc as existencia,ms.precio1 as precio,t.porciva as iva,coalesce(ms.precultprov,ms.costo) as costo,m.codigo as codigo,(select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as ventas,(select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='DV' and d.matid=m.matid) as devoluciones,(select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as cantidad from material as m inner join materialsuc as ms on(m.matid=ms.matid) inner join grupmat g on (m.grupmatid=g.grupmatid) inner join tipoiva as t on(m.tipoivaid=t.tipoivaid) where  m.codigo not like '%.'  and g.grupmatid  in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and (select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) >0 order by (select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) DESC";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			while($vr = ibase_fetch_object($vc))
			{
				
				$v_costobase = $vr->COSTO;
				$v_total = $vr->VENTAS - $vr->DEVOLUCIONES;
				
				$v_totalcompleto +=$v_total;
				$v_totalcantidad += $vr->CANTIDAD;
				
			}
			$v_porcentaje = $v_totalcompleto *0.8;
			$v_porcentaje2 = $v_totalcompleto *0.85;
			$v_porcentaje3 = $v_totalcompleto *0.95;
			
			$v5=$v_totalcompleto *0.05;
			$v15=$v_totalcompleto *0.15;
			$v80=$v_porcentaje;
			echo "<script>console.log('p" . $v_porcentaje ." t".$v_totalcompleto."' );</script>";
		}	
		
		$vsql = "select m.matid as matid,m.descrip as descripcion,ms.existenc as existencia,ms.precio1 as precio,t.porciva as iva,coalesce(ms.precultprov,ms.costo) as costo,m.codigo as codigo,(select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as ventas,(select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='DV' and d.matid=m.matid) as devoluciones,(select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as cantidad from material as m inner join materialsuc as ms on(m.matid=ms.matid) inner join grupmat g on (m.grupmatid=g.grupmatid) inner join tipoiva as t on(m.tipoivaid=t.tipoivaid) where  m.codigo not like '%.'  and g.grupmatid  in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and (select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) >0 order by (select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) DESC";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			
			$v_totalc=0;
	
					$v_cont=1;
					$valida=0;
					$valida2=0;
					$valida3=0;
					$valida4=0;
					$valida5=0;
					$valida6=0;
					$v_totalc1=0;
					$v_totalc2=0;
				?>	
					<table class="table table-striped table-bordered" data-sortable style="align:center; width:90%;" id="tabledatos">
					  <thead>
						<th></th>
						<th><center>CODIGO</center></th>
						<th><center>PRODUCTO</center></th>
						<th><center>CANTIDAD</center></th>
						<th><center>VENTA</center></th>
						<th><center>MARCA</center></th>
					  </thead>
					  <tbody id="cuerpo">
				<?php	
					while($vr = ibase_fetch_object($vc))
					{
						$v_existencia = $v_existencia + $vr->EXISTENCIA;
						$v_costobase = $vr->COSTO;
						$v_total = $vr->VENTAS - $vr->DEVOLUCIONES;
						
						$v_totalc+=$v_total;
						
						if($valida==1){
							$v_totalc1+=$v_total;
						}
						
						if($valida2==1){
							$v_totalc2+=$v_total;
						}
						
						if($v_totalc>0 and $v_totalc<=$v_porcentaje and $valida==0){
							
							$vsql1 = "update material set marcaartid=(select mar.marcaartid from marcaart as mar where mar.codigo='A') where matid='".$vr->MATID."'";
							if($vc1 = $conect_bd_actual->consulta($vsql1))
							{
								?>
								
									<tr>
										<td>
											<center><?php echo $v_cont;?></center>
										</td>
										<td style="text-align:left;">
											<?php echo $vr->CODIGO; ?>
										</td>
										<td style="text-align:left;">
											<?php echo utf8_encode($vr->DESCRIPCION); ?>
										</td>
										<td style="text-align:left;">
											<?php echo $vr->CANTIDAD; ?>
										</td>	
										<td style="text-align:right;">
											<?php echo number_format($v_total); ?>
										</td>
										<td style="text-align:right;">
											A
										</td>
										
									</tr>
								<?php
								
							}	
							
						}else{
							if($valida4==0){
								$valida=1;
								$valida4=1;
							}
						}		
						
						if($v_totalc1>0 and$v_totalc1<=$v15 and $valida2==0){
							
							$vsql2 = "update material set marcaartid=(select mar.marcaartid from marcaart as mar where mar.codigo='B') where matid='".$vr->MATID."'";
							if($vc2 = $conect_bd_actual->consulta($vsql2))
							{
								?>
								
									<tr>
										<td>
											<center><?php echo $v_cont;?></center>
										</td>
										<td style="text-align:left;">
											<?php echo $vr->CODIGO; ?>
										</td>
										<td style="text-align:left;">
											<?php echo utf8_encode($vr->DESCRIPCION); ?>
										</td>
										<td style="text-align:left;">
											<?php echo $vr->CANTIDAD; ?>
										</td>	
										<td style="text-align:right;">
											<?php echo number_format($v_total); ?>
										</td>
										<td style="text-align:right;">
											B
										</td>
										
									</tr>
								<?php
							}
		
						}else{
							if($valida5==0 and $valida==1 and $v_totalc1>0){
								$valida2=1;
								$valida5=1;
							}
						}	
						
						if($v_totalc2>0 and $v_totalc2<=$v5 and $valida3==0){
							
							$vsql3 = "update material set marcaartid=(select mar.marcaartid from marcaart as mar where mar.codigo='C') where matid='".$vr->MATID."'";
							if($vc3 = $conect_bd_actual->consulta($vsql3))
							{
								?>
								
									<tr>
										<td>
											<center><?php echo $v_cont;?></center>
										</td>
										<td style="text-align:left;">
											<?php echo $vr->CODIGO; ?>
										</td>
										<td style="text-align:left;">
											<?php echo utf8_encode($vr->DESCRIPCION); ?>
										</td>
										<td style="text-align:left;">
											<?php echo $vr->CANTIDAD; ?>
										</td>	
										<td style="text-align:right;">
											<?php echo number_format($v_total); ?>
										</td>
										<td style="text-align:right;">
											C
										</td>
										
									</tr>
								<?php
							}
		
						}	

						
							$v_cont++;
					}

		}
	}
		
?>	
