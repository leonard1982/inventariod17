<?php require("conecta.php"); ?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
	<head>
		<meta charset="UTF-8">
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<title>ABC Costo Inventario</title>
		<!--Llamamos las librerias css y js -->
		<?php includeAssets(); ?>
		<link rel="stylesheet" href="css/ListaClasificacionCosto.css?v=20260217_02">
	</head>	
<?php

	$v_existencia=0;
	$v_totalcompleto=0;

	$v_fecha = date('Y-m-d');
	$v_fechaanterior = date("Y-m-d",strtotime($v_fecha."- 1 month"));
	$v_fechaanterior = date("Y-m-d",strtotime($v_fechaanterior."- 1 year"));
	$v_mes = date("m",strtotime($v_fechaanterior));
	$v_year = date("Y",strtotime($v_fechaanterior));

	$v_fechabusqueda=date("Y-m-d",strtotime($v_year.'-'.$v_mes.'-'.'01'));
	
	fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' INGRESO EN LA OPCION (ABC COSTO INVENTARIO) DEL MENU EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO',$contenidoBdActual);
	$vsql = "select m.descrip as descripcion,ms.existenc as existencia,ms.precio1 as precio,t.porciva as iva,coalesce(ms.precultprov,ms.costo) as costo,m.codigo as codigo from material as m inner join materialsuc as ms on(m.matid=ms.matid) inner join tipoiva as t on(m.tipoivaid=t.tipoivaid) and ms.existenc>0 order by (ms.precio1* ms.existenc) DESC";
	if($vc = $conect_bd_actual->consulta($vsql))
	{
		while($vr = ibase_fetch_object($vc))
		{
			
			$v_costobase = $vr->COSTO;
			$v_total = $v_costobase * $vr->EXISTENCIA;
			
			$v_totalcompleto +=$v_total;
			
		}
		$v_porcentaje = $v_totalcompleto *0.8;
		$v_porcentaje2 = $v_totalcompleto *0.85;
		$v_porcentaje3 = $v_totalcompleto *0.95;
		
		$v5=$v_totalcompleto *0.05;
		$v15=$v_totalcompleto *0.15;
		$v80=$v_porcentaje;
		echo "<script>console.log('p" . $v_porcentaje ." t".$v_totalcompleto."' );</script>";
	}	
	
	$vsql = "select m.unidad as unidad,m.descrip as descripcion,ms.existenc as existencia,ms.precio1 as precio,t.porciva as iva,coalesce(ms.precultprov,ms.costo) as costo,m.codigo as codigo from material as m inner join materialsuc as ms on(m.matid=ms.matid) inner join grupmat g on (m.grupmatid=g.grupmatid) inner join tipoiva as t on(m.tipoivaid=t.tipoivaid) where  m.codigo not like '%.'  and g.grupmatid not in(select gg.grupmatid from grupmat gg where gg.codigo like '00.%') and ms.existenc>0 order by (ms.precio1* ms.existenc) DESC";
	if($vc = $conect_bd_actual->consulta($vsql))
	{
		
		$v_totalc=0;

		?>
		
		<div class="container abc-wrapper">
			<div class="abc-header">
				<h4 id="titulo">ABC COSTO INVENTARIO</h4>
				<p class="abc-fecha"><i class="fas fa-calendar-alt"></i> Fecha de corte: <?php echo date('d-m-Y H:i'); ?></p>
			</div>

			<div class="abc-toolbar">
				<div class="abc-search-wrap">
					<label for="search"><i class="fas fa-search"></i> Buscar</label>
					<input type="text" id="search" class="form-control" placeholder="Escribe para buscar..." />
				</div>
				<div class="abc-actions">
					<button type="button" class="btn btn-primary" id="btnExport"><i class="fas fa-file-excel"></i> Excel</button>
				</div>
			</div>

			<div class="abc-kpis">
				<article class="abc-kpi-card" id="marcaTOTAL">
					<span class="abc-kpi-label"><i class="fas fa-coins"></i> Total</span>
					<strong><?php echo number_format($v_totalcompleto); ?></strong>
				</article>
				<article class="abc-kpi-card" id="marca5">
					<span class="abc-kpi-label"><i class="fas fa-layer-group"></i> Tramo 5%</span>
					<strong><?php echo number_format($v5); ?></strong>
				</article>
				<article class="abc-kpi-card" id="marca15">
					<span class="abc-kpi-label"><i class="fas fa-layer-group"></i> Tramo 15%</span>
					<strong><?php echo number_format($v15); ?></strong>
				</article>
				<article class="abc-kpi-card" id="marca80">
					<span class="abc-kpi-label"><i class="fas fa-layer-group"></i> Tramo 80%</span>
					<strong><?php echo number_format($v80); ?></strong>
				</article>
			</div>

			<div class="table-responsive abc-table-wrap">
			<p id='iniciotabla'></p>
			<table class="table table-striped table-bordered abc-table" style="align:center; width:100%;" id="tabledatos">
				<thead>
				<tr>
				<th></th>
				<th><center>CODIGO</center></th>
				<th><center>PRODUCTO</center></th>
				<th><center>EXISTENCIA</center></th>
				<th><center>U.COSTO</center></th>
				<th><center>TOTAL</center></th>
				<th><center>%</center></th>
				<th><center>UNIDAD</center></th>
				</tr>
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
					$v_total = $v_costobase * $vr->EXISTENCIA;
					
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
							<?php echo $vr->EXISTENCIA; ?>
						</td>
						<td>
							<center><?php echo number_format($vr->COSTO); ?></center>
						</td>	
						<td style="text-align:right;">
							<?php echo number_format($v_total); ?>
						</td>
						<td style="text-align:right;">
							<?php echo number_format(($v_total/$v_totalcompleto)*100,2,'.',','); ?>
						</td>
						<td style="text-align:right;">
							<?php echo utf8_encode($vr->UNIDAD); ?>
						</td>
						
					</tr>
					
					<?php
					
					if($v_totalc>=$v_porcentaje and $valida==0){
						$valida=1;
						$valor80=$v_totalc;
					?>
						<tr>
							<td>
								
							</td>
							<td id="marca80" colspan="5" class="abc-corte-row">
								<center><i class="fas fa-chart-line"></i> SUBTOTAL APROX 80%</center>
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
								
							</td>
							<td id="marca15" colspan="5" class="abc-corte-row">
								<center><i class="fas fa-chart-bar"></i> SUBTOTAL APROX 15%</center>
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
								
							</td>
							<td id="marca5" colspan="5" class="abc-corte-row">
								<center><i class="fas fa-chart-pie"></i> SUBTOTAL APROX 5%</center>
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
							
						</td>
						<td id="marca5" colspan="5" class="abc-corte-row">
							<center><i class="fas fa-chart-pie"></i> SUBTOTAL APROX 5%</center>
						</td>
						<td style="text-align:right;">
							<?php echo number_format($valor5); ?>
						</td>
						<td></td>
					</tr>	
				<?php	
				}
		?>
				<tr class="abc-total-row">
						<td>
							
						</td>
						<td id='marcaTOTAL' colspan="2" style="text-align:center;">
							<i class="fas fa-calculator"></i> TOTALES
						</td>
						<td style="text-align:left;">
							<?php echo number_format($v_existencia); ?>
						</td>
						<td colspan="2" style="text-align:right;">
							<?php echo number_format($v_totalcompleto); ?>
						</td>
						<td >
							
						</td>
						<td></td>
						
					</tr>
				</tbody>
			</table>
			</div>
		</div>


		<?php
	}
		
    createFloatingButton("fas fa-arrow-up", "Back to Top", "#titulo");
?>
<script>


function exportTableToExcel(tableID, filename = ''){
    var downloadLink;
    var dataType = 'application/vnd.ms-excel';
    var tableSelect = document.getElementById(tableID);
    var tableHTML = tableSelect.outerHTML.replace(/ /g, '%20');
    
    // Specify file name
    filename = filename?filename+'.xls':'Lista Clasificacion Costo.xls';
    
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
			
			
			
			window.open("ListaClasificacionCosto_excel.php","ventana1","width=1200,height=600,scrollbars=NO");
			
	});


function ExportToExcel(type, fn, dl) {
            var elt = document.getElementById('tabledatos');
            var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
            return dl ?
                XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) :
                XLSX.writeFile(wb, fn || ('Lista Clasificacion Costo.' + (type || 'xlsx')));
}

$(function () {
    if ($.fn.DataTable) {
        $.fn.dataTable.ext.errMode = 'none';
        var tablaAbc = $('#tabledatos').DataTable({
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Todos']],
            paging: true,
            ordering: false,
            dom: 'lrtip',
            language: {
                lengthMenu: 'Mostrar _MENU_ registros',
                info: 'Mostrando _START_ a _END_ de _TOTAL_ registros',
                infoEmpty: 'Sin registros disponibles',
                infoFiltered: '(filtrado de _MAX_ registros)',
                zeroRecords: 'No se encontraron resultados',
                paginate: {
                    first: '<<',
                    previous: '<',
                    next: '>',
                    last: '>>'
                }
            }
        });

        $('#tabledatos').on('error.dt', function (e, settings, techNote, message) {
            console.error('DataTables error:', message);
        });

        $('#search').on('keyup', function () {
            tablaAbc.search($(this).val()).draw();
        });
    } else {
        $('#search').quicksearch('table tbody tr');
    }
});

</script>	

</html>
