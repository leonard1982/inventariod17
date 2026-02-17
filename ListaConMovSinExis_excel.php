<?php
//date_default_timezone_set('America/Bogota');
//date_default_timezone_set('Europe/London');
session_start();


$filename = "Lista Con Movimiento y Sin Existencia.xls";
header("Content-type: application/x-msdownload; charset=utf-8");
header("Content-Disposition: attachment; filename=$filename");
header("Pragma: no-cache");
header("Cache-Control: must-revalidate, post-check=0, pre-check=0");


	$v_contrato="";
	$v_idcontrato="";
	$v_numero="";

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

	$vbd = "".$linea."";
	if($cxf = new dbFirebird($vbd))
	{
		
		if($v_grupo>0)
		{
			if($v_linea>0){
				$vsql = "select first ".$v_registros." m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,m.codigo as codigo,l.descrip as linea,g.descrip as grupo 
				from material as m inner join grupmat g on m.grupmatid=g.grupmatid
				inner join materialsuc as ms on(m.matid=ms.matid) 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)
				where ms.fecultcli > '".$v_fechabusqueda."' and ms.fecultcli is not null and ms.fecultprov is not null and ms.existenc=0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' and m.lineamatid='".$v_linea."'  order by ms.fecultcliasc ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}else{
				$vsql = "select first ".$v_registros." m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,m.codigo as codigo,l.descrip as linea,g.descrip as grupo 
				from material as m inner join grupmat g on m.grupmatid=g.grupmatid
				inner join materialsuc as ms on(m.matid=ms.matid) 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)
				where ms.fecultcli > '".$v_fechabusqueda."' and ms.fecultcli is not null and ms.fecultprov is not null and ms.existenc=0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and g.grupmatid='".$v_grupo."' order by ms.fecultcli asc ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}
		}else{
			if($v_linea>0){
				$vsql = "select first ".$v_registros." m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,m.codigo as codigo,l.descrip as linea,g.descrip as grupo 
				from material as m inner join grupmat g on m.grupmatid=g.grupmatid
				inner join materialsuc as ms on(m.matid=ms.matid) 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)
				where ms.fecultcli > '".$v_fechabusqueda."' and ms.fecultcli is not null and ms.fecultprov is not null and ms.existenc=0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')  and m.lineamatid='".$v_linea."'  order by ms.fecultcli asc ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
			}else{
				$vsql = "select first ".$v_registros." m.descrip as descripcion,ms.existenc as existencia,ms.fecultprov as fecha,m.codigo as codigo,l.descrip as linea,g.descrip as grupo 
				from material as m inner join grupmat g on m.grupmatid=g.grupmatid
				inner join materialsuc as ms on(m.matid=ms.matid) 
				inner join lineamat as l on (m.lineamatid=l.lineamatid)
				where ms.fecultcli > '".$v_fechabusqueda."' and ms.fecultcli is not null and ms.fecultprov is not null and ms.existenc=0 and m.codigo not like '%.' and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%')   order by ms.fecultcli asc ";
				echo "<script>console.log('Console: " . $vsql. "' );</script>";
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
					<th><center>FECHA</center></th>
					<th><center>EXISTENCIA</center></th>
				  </thead>
				  <tbody id="cuerpo">
				  
			<?php	
					$v_cont=1;
					while($vr = ibase_fetch_object($vc))
					{
						setlocale(LC_ALL,"es_ES","esp");
						$v_fecha = date("Y-m-d", strtotime($vr->FECHA));
						//$v_fecha=strftime("%d de %B de %Y", strtotime($v_fecha));
						?>
						
						<tr>
							<td>
								<center><?php echo $v_cont;?></center>
							</td>
							<td style="text-align:left;">
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
								<?php echo $vr->EXISTENCIA; ?>
							</td>								
							
						</tr>
						
						<?php
							$v_cont++;
					}
			?>
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