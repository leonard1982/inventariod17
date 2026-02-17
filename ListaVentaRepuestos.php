<!DOCTYPE html>
<?php
date_default_timezone_set('America/Bogota');
session_start();



?>
<html lang="en" dir="ltr">
  <head>
    <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>ABC Venta Repuestos</title>

	<!-- Scripts CSS -->
	<link rel="stylesheet" href="css/bootstrap.min.css">
	<link rel="stylesheet" href="css/datatables.min.css">
	<link rel="stylesheet" href="css/bootstrap-clockpicker.css">
	<link rel="stylesheet" href="css/alertify.min.css">
	<link rel="stylesheet" href="fullcalendar/main.css">
	<link rel="stylesheet" href="css/sortable-theme-dark.css" />
		

	<!-- Scripts JS -->
	<script src="js/jquery-3.6.0.min.js"></script>
	<script src="js/popper.min.js"></script>
	<script src="js/bootstrap.min.js"></script>
	<script src="js/datatables.min.js"></script>
	<script src="js/bootstrap-clockpicker.js"></script>
	<script src="js/moment-with-locales.js"></script>
	<script src="js/alertify.js"></script>
	<script src="js/jquery.blockUI.js"></script>
	<script src="js/jquery.quicksearch.js"></script>
	<script type="text/javascript" src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
	<script src="js/sortable.min.js"></script>

	</head>
<style>
.table-responsived  thead tr th{ 
          position: sticky;
          top: 0;
          z-index: 10;
          background-color: #ffffff;
        }
        
        .table-responsived { 
          height:700px;
          overflow:scroll;
        }
</style>	
	
<?php

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
		fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' INGRESO EN LA OPCION (ABC VENTA REPUESTOS) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$vbd);
		$vsql = "select m.descrip as descripcion,ms.existenc as existencia,ms.precio1 as precio,t.porciva as iva,coalesce(ms.precultprov,ms.costo) as costo,m.codigo as codigo,(select sum(d.preciobase*d.canmat) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as ventas,(select sum(d.preciobase*d.canmat) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='DV' and d.matid=m.matid) as devoluciones,(select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as cantidad from material as m inner join materialsuc as ms on(m.matid=ms.matid) inner join grupmat g on (m.grupmatid=g.grupmatid) inner join tipoiva as t on(m.tipoivaid=t.tipoivaid) where  m.codigo not like '%.'  and g.grupmatid  in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and (select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) >0 order by (select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) DESC";
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
		
		$vsql = "select m.unidad as unidad,m.descrip as descripcion,ms.existenc as existencia,ms.precio1 as precio,t.porciva as iva,coalesce(ms.precultprov,ms.costo) as costo,m.codigo as codigo,(select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as ventas,(select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='DV' and d.matid=m.matid) as devoluciones,(select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) as cantidad from material as m inner join materialsuc as ms on(m.matid=ms.matid) inner join grupmat g on (m.grupmatid=g.grupmatid) inner join tipoiva as t on(m.tipoivaid=t.tipoivaid) where  m.codigo not like '%.'  and g.grupmatid  in(select gg.grupmatid from grupmat gg where gg.codigo BETWEEN '01.02.' AND '01.02.VL') and (select count(*) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) >0 order by (select sum(d.parcvta) from dekardex as d inner join kardex as k on(k.kardexid=d.kardexid) where k.fecasentad is not null and k.fecanulado is null and k.codcomp='FV' and d.matid=m.matid) DESC";
		if($vc = $conect_bd_actual->consulta($vsql))
		{
			
			$v_totalc=0;

			?>
			
			<div class="table-responsive">
				<center>
				<h4>ABC VENTA REPUESTOS</h4>
				<center>
					<div class="input-group" style="justify-content: center;">
						<div style="align:left;">
							<h4>Fecha de Corte: <?php echo date('d-m-Y H:i'); ?></h4>
						</div>
						<div style="width:300px;margin-left:10px;">
							<input type="text" id="search" class="form-control" placeholder="Escribe para buscar..." />
						</div>
						
						<div style="margin-left:10px;">
							<button  type="button" class="btn btn-primary" id="btnExport" >Excel</button>
						</div>
						<div style="margin-left:10px;">
							<table class="table table-striped table-bordered">
								<thead>
									<th><center><a href='#marcaTOTAL'>TOTAL</a></center></th>
									<th><center><a href='#marca5'>5%</a></center></th>
									<th><center><a href='#marca15'>15%</a></center></th>
									<th><center><a href='#marca80'>80%</a></center></th>
								</thead>
								
								<tbody>
									<tr>
										<td style="text-align:right;">
											<?php echo number_format($v_totalcompleto); ?>
										</td>
										<td style="text-align:right;">
											<?php echo number_format($v5); ?>
										</td>
										<td style="text-align:right;">
											<?php echo number_format($v15); ?>
										</td>
										<td style="text-align:right;">
											<?php echo number_format($v80); ?>
										</td>
									</tr>
								</tbody>
							</table>	
						</div>
					</div>	
				</center>
				<br>
				<br>
				<div class="table-responsived">
				<p id='iniciotabla'></p>
				<table class="table table-striped table-bordered" data-sortable style="align:center; width:90%;" id="tabledatos">
				  <thead>
					<th></th>
					<th><center>CODIGO</center></th>
					<th><center>PRODUCTO</center></th>
					<th><center>CANTIDAD</center></th>
					<th><center>VENTA</center></th>
					<th><center>UNIDAD</center></th>
				  </thead>
				  <tbody id="cuerpo">
				  
			<?php	
					$v_cont=1;
					$valida=0;
					$valida2=0;
					$valida3=0;
					$valida4=0;
					$valida5=0;
					$v_totalc1=0;
					$v_totalc2=0;
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
								<?php echo $vr->UNIDAD; ?>
							</td>
							
						</tr>
						
						<?php
						
						if($v_totalc>=$v_porcentaje and $valida==0){
							$valida=1;
							$valor80=$v_totalc;
						?>
							<tr>
								<td>
									<center><a href='#iniciotabla'>Subir</a></center>
								</td>
								<td id='marca80' colspan="2" style="background-color:yellow;">
									<center>SUBTOTAL APROX 80%</center>
								</td>
								<td style="text-align:right;">
									<?php echo number_format($valor80); ?>
								</td>
								<td></td>
							</tr>	
						<?php	
						}	
						
						if($v_totalc1>=$v15 and $valida2==0){
							$valida2=1;
							$valor15=$v_totalc1;
						?>
						
							<tr>
								<td>
									<center><a href='#iniciotabla'>Subir</a></center>
								</td>
								<td id='marca15' colspan="2" style="background-color:yellow;">
									<center>SUBTOTAL APROX 15%</center>
								</td>
								<td style="text-align:right;">
									<?php echo number_format($valor15); ?>
								</td>
								<td></td>
							</tr>	
						<?php	
						}
						
						if($v_totalc2>=$v5 and $valida3==0){
							$valida3=1;
							$valor5=$v_totalc2;
						?>
							<tr>
								<td>
									<center><a href='#iniciotabla'>Subir</a></center>
								</td>
								<td id='marca5' colspan="2" style="background-color:yellow;">
									<center>SUBTOTAL APROX 5%</center>
								</td>
								<td style="text-align:right;">
									<?php echo number_format($valor5); ?>
								</td>
								<td></td>
							</tr>	
						<?php	
						}
						
							$v_cont++;
					}
					
					if($valida3==0){
						$valor5=$v_totalc2;
						?>
							<tr>
								<td>
									<center><a href='#iniciotabla'>Subir</a></center>
								</td>
								<td id='marca5' colspan="2" style="background-color:yellow;">
									<center>SUBTOTAL APROX 5%</center>
								</td>
								<td style="text-align:right;">
									<?php echo number_format($valor5); ?>
								</td>
								<td></td>
								<td></td>
							</tr>	
						<?php	
					}
					
			?>
					<tr>
							<td>
								<center><a href='#iniciotabla'>Subir</a></center>
							</td>
							<td  id='marcaTOTAL' colspan="2" style="text-align:center;">
								<B>TOTALES</B>
							</td>
							<td style="text-align:left;">
								<?php echo number_format($v_totalcantidad); ?>
							</td>
							<td style="text-align:right;">
								<?php echo number_format($v_totalcompleto); ?>
							</td>
							
						</tr>
					</tbody>
				</table>
				</div>
				</center>
			</div>


			<?php
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
    filename = filename?filename+'.xls':'Lista Venta Inventario.xls';
    
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

$("#btnExport").click(function(e){
			
			e.preventDefault();
			
			
			
			window.open("ListaVentaRepuestos_excel.php","ventana1","width=1200,height=600,scrollbars=NO");
			
	});


function ExportToExcel(type, fn, dl) {
            var elt = document.getElementById('tabledatos');
            var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
            return dl ?
                XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) :
                XLSX.writeFile(wb, fn || ('Lista Venta Inventario.' + (type || 'xlsx')));
}

$(function () {
		
	$('#search').quicksearch('table tbody tr');								
});

</script>	

</html>