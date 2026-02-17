<!DOCTYPE html>
<?php
//date_default_timezone_set('America/Bogota');

session_start();

$filename = "Lista Sin Movimiento y Con Existencia.xls";
header("Content-type: application/x-msdownload; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, GET-check=0, pre-check=0");


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
	

	require("tns/conexion.php");
	require("conecta.php");
	
	
	//$vbd = "C:\Datos TNS\COMERCIALMEYER2022.GDB";
	
if(isset($_GET['reg']))
{	
	$v_registros = $_GET['reg'];
	$v_grupo     = $_GET['grupo'];
	$v_linea    = $_GET['linea'];
	$v_bodega    = $_GET['bodega'];

	$vbd = "".$linea."";
	if($cxf = new dbFirebird($vbd))
	{
		
		if($v_grupo>0)
		{
			if($v_linea>0){
				if($v_bodega>0){
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where sm.bodid='".$v_bodega."' and ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' and m.lineamatid='".$v_linea."' and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98')  order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}else{
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' and m.lineamatid='".$v_linea."' and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98') and  g.codigo not between '01.04.' AND '01.05.ZZ' order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}	
			}else{
				if($v_bodega>0){
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where sm.bodid='".$v_bodega."' and ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98') order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}else{
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98') and  g.codigo not between '01.04.' AND '01.05.ZZ' order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}	
			}
		}else{
			if($v_linea>0){
				if($v_bodega>0){
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where sm.bodid='".$v_bodega."' and ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')  and m.lineamatid='".$v_linea."'  and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98')  order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}else{
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')  and m.lineamatid='".$v_linea."'  and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98') and  g.codigo not between '01.04.' AND '01.05.ZZ' order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}	
			}else{
				if($v_bodega>0){
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where sm.bodid='".$v_bodega."' and ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98')  order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}else{
					$vsql = "select first ".$v_registros." m.unidad,m.matid,m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,ms.fecultcli,m.codigo as codigo,coalesce(ms.precultprov,ms.costo) as costo,l.descrip as linea,g.descrip as grupo 
					,(select b.codigo||'-'||b.nombre from bodega as b where b.bodid=sm.bodid) as bodega,sm.bodid as idbodega,(select tr.codprefijo||'-'||tr.numero from detrasla as d inner join trasla as tr on(d.traslaid=tr.traslaid) where d.matid=m.matid and tr.fecasentad is null) as traslado
					from material as m inner join grupmat g on m.grupmatid=g.grupmatid
					inner join materialsuc as ms on(m.matid=ms.matid) 
					inner join salmaterial as sm on(m.matid=sm.matid)
					inner join lineamat as l on (m.lineamatid=l.lineamatid)
					where ((coalesce(ms.fecultprov,'1900-01-01') > coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultprov,'1900-01-01') < '".$v_fechabusqueda."') or (coalesce(ms.fecultprov,'1900-01-01') < coalesce(ms.fecultcli,'1900-01-01') and coalesce(ms.fecultcli,'1900-01-01') < '".$v_fechabusqueda."')) and ms.existenc>0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and sm.bodid not in (select b.bodid from bodega as b where b.codigo='99' or b.codigo='98') and  g.codigo not between '01.04.' AND '01.05.ZZ'  order by ms.fecultcli asc ";
					echo "<script>console.log('Console: " . $vsql. "' );</script>";
				}	
			}	
		}
		
		
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			

			?>
			
			<div class="table-responsive">
				
				<table  class="table table-striped table-bordered" data-sortable style="align:center; width:90%;" id="tabledatos">
				  <thead>
					<th></th>
					<th><center>CODIGO</center></th>
					<th><center>PRODUCTO</center></th>
					<th><center>GRUPO</center></th>
					<th><center>LINEA</center></th>
					<th><center>U.COMPRA</center></th>
					<th><center>COSTO UND</center></th>
					<th><center>EXIST.</center></th>
					<th><center>UND</center></th>
					<th><center>BODEGA</center></th>
					<th><center>COSTO</center></th>
					<th><center>U.VENTA</center></th>
					<th><center>DÍAS</center></th>
					<th><center>TRASLADO</center></th>
				  </thead>
				  <tbody id="cuerpo">
				  
			<?php	
					$v_cont=1;
					while($vr = ibase_fetch_object($vc))
					{
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
						
						?>
						
						<tr>
							<td>
								<center><?php echo $v_cont;?></center>
							</td>
							<td style="text-align:left;<?php echo $vcolor; ?>">
									<?php echo $vr->CODIGO; ?>
							</td>
							<td style="text-align:left;">
								<?php echo $vr->DESCRIPCION; ?>
							</td>
							<td style="text-align:left;">
								<?php echo $vr->GRUPO; ?>
							</td>
							<td style="text-align:left;">
								<?php echo utf8_encode($vr->LINEA); ?>
							</td>
							<td>
								<center><?php echo utf8_encode($v_fecha); ?></center>
							</td>
							<td style="text-align:right;">
								<?php echo number_format($vr->COSTO); ?>
							</td>
							<td style="text-align:right;">
								<?php echo $vr->EXISTENCIA; ?>
							</td>
							<td style="text-align:left;">
								<?php echo $vr->UNIDAD; ?>
							</td>
							<td style="text-align:left;">
								<?php echo $vr->BODEGA; ?>
							</td>
							<td style="text-align:right;">
								<?php echo number_format($v_costo); ?>
							</td>	
							<td style="text-align:right;">
								<?php echo $vultventa; ?>
							</td>
							<td style="text-align:right;">
								<?php  if ($vcantidad_dias !=''){echo number_format($vcantidad_dias);}else{ echo $vcantidad_dias;} ?>
							</td>
							<td style="text-align:left;">
								<?php echo $vr->TRASLADO; ?>
							</td>							
							
						</tr>
						
						<?php
							$v_cont++;
					}
			?>
						<tr>
							<td colspan="10" style="text-align:center;">
								TOTAL EXISTENCIA
							</td>
							<td style="text-align:right;">
								<?php echo number_format($v_existencia); ?>
							</td>
							<td colspan="2" style="text-align:center;">
								TOTAL COSTO
							</td>
							<td style="text-align:right;">
								<?php echo number_format($v_totalcosto); ?>
							</td>
						
						</tr>
					</tbody>
				</table>
				</center>
			</div>


			<?php
		}
	}
}		
?>	
<script>

function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'Lista Sin Movimiento y Sin Existencia.xls';
    
    // Create download link element
    downloadLink = document.createElement("a");
    
    document.body.appendChild(downloadLink);
    
    if(navigator.msSaveOrOpenBlob){
        var blob = new Blob(['ufeff', tableHTML], {
            type: dataType
        });
        navigator.msSaveOrOpenBlob( blob, filename);
    }else{
        // Create a link to the file
        downloadLink.href = 'data:' + dataType + ', ' + tableHTML;
    
        // Setting the file name
        downloadLink.download = filename;
        
        //triggering the function
        downloadLink.click();
    }
}

function ExportToExcel(type, fn, dl) {
            var elt = document.getElementById('tabledatos');
            var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
            return dl ?
                XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) :
                XLSX.writeFile(wb, fn || ('Productos_sinmovimientos_sinexistencia.' + (type || 'xlsx')));
}

$(function () {
		
	$('#search').quicksearch('table tbody tr');								
});

</script>	

</html>