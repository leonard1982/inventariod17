<?php
if(isset($_GET['tipo']) and $_GET['tipo']=='excel')
{
    $filename = "Lista Sin Movimiento y Con Existencia.xls";
    header("Content-type: application/x-msdownload; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
}

require("conecta.php");

	$v_contrato="";
	$v_idcontrato="";
	$v_numero="";
	$v_existencia=0;
	$v_totalcosto =0;
	$anios   = 1;
	
if (isset($_POST['reg']) or isset($_GET['reg'])) {

	if ($_SERVER['REQUEST_METHOD'] === 'GET') {
		$v_registros = $_GET['reg'];
		$v_grupo = $_GET['grupo'];
		$v_linea = $_GET['linea'];
		$v_fecha_condicion = $_GET['fecha'];
		$anios = $_GET['anios'];
		$v_traslado  = $_GET['traslado'];
		$v_bodega    = $_GET['bodega'];
		$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;

	} else if ($_SERVER['REQUEST_METHOD'] === 'POST') {
		$v_registros = $_POST['reg'];
		$v_grupo = $_POST['grupo'];
		$v_linea = $_POST['linea'];
		$v_fecha_condicion = $_POST['fecha'];
		$anios = $_POST['anios'];
		$v_traslado  = $_POST['traslado'];
		$v_bodega    = $_POST['bodega'];
		$page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
	}
	
	$offset = ($page - 1) * $v_registros;	
	$v_numerotras      =1;
	$v_existeidtras    =0;

	$v_fecha = date('Y-m-d');
	$v_fechaanterior = date("Y-m-d", strtotime("$v_fecha - $anios year - 1 month"));
	$v_fechabusqueda = date("Y-m-d", strtotime(date("Y-m", strtotime($v_fechaanterior)) . '-01'));
	
	fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' GENERO EL INFORME DE LA OPCION (LISTA SIN MOV Y CON EXIS)',$contenidoBdActual);
	$queryConditions = [
		"m.matid NOT IN (SELECT d.matid FROM dekardex d INNER JOIN kardex AS k ON d.kardexid = k.kardexid WHERE k.codcomp != 'NI')",
		"ms.existenc > 0",
		"sm.existenc > 0",
		"g.grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '00.%')",
		"sm.bodid NOT IN (SELECT b.bodid FROM bodega AS b WHERE b.codigo = '99' OR b.codigo = '98')"
	];

	if ($v_grupo > 0) {
		$queryConditions[] = "g.grupmatid = '$v_grupo'";
	}

	if ($v_linea > 0) {
		$queryConditions[] = "m.lineamatid = '$v_linea'";
	}

	if ($v_bodega > 0) {
		$queryConditions[] = "sm.bodid = '$v_bodega'";
	}

	$vsql = "SELECT FIRST $v_registros SKIP $offset 
				m.unidad, m.matid, m.descrip AS descripcion, sm.existenc AS existencia, ms.fecultprov AS fecha, ms.fecultcli, m.codigo AS codigo, 
				COALESCE(ms.precultprov, ms.costo) AS costo, l.descrip AS linea, g.descrip AS grupo, 
				(SELECT b.codigo || '-' || b.nombre FROM bodega AS b WHERE b.bodid = sm.bodid) AS bodega, sm.bodid AS idbodega, 
				(SELECT tr.codprefijo || '-' || tr.numero FROM detrasla AS d INNER JOIN trasla AS tr ON d.traslaid = tr.traslaid WHERE d.matid = m.matid AND tr.fecasentad IS NULL) AS traslado 
			FROM material AS m 
			INNER JOIN grupmat g ON m.grupmatid = g.grupmatid 
			INNER JOIN materialsuc AS ms ON m.matid = ms.matid 
			INNER JOIN salmaterial AS sm ON m.matid = sm.matid 
			INNER JOIN lineamat AS l ON m.lineamatid = l.lineamatid 
			WHERE " . implode(' AND ', $queryConditions);
	//echo $vsql;

	// Añadir condiciones basadas en la fecha
	switch ($v_fecha_condicion) {
		case 'V':
			$vsql .= " AND ms.fecultcli <= '$v_fechabusqueda' AND ms.fecultcli IS NOT NULL";
			break;
		case 'C':
			$vsql .= " AND ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultprov IS NOT NULL";
			break;
		case 'U':
			$vsql .= " AND ((ms.fecultprov > ms.fecultcli AND ms.fecultprov <= '$v_fechabusqueda') OR 
							  (ms.fecultprov < ms.fecultcli AND ms.fecultcli <= '$v_fechabusqueda') OR 
							  (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli <= '$v_fechabusqueda') OR 
							  (ms.fecultprov IS NULL AND ms.fecultcli <= '$v_fechabusqueda') OR 
							  (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli IS NULL) OR 
							  (ms.fecultprov IS NULL AND ms.fecultcli IS NULL AND ms.fecact <= '$v_fechabusqueda'))";
			break;
	}

	// Ordenar por fecha de última compra o venta
    if ($v_fecha_condicion == 'U') {
        $vsql .= " ORDER BY CASE 
                        WHEN ms.fecultcli IS NOT NULL AND ms.fecultprov IS NOT NULL THEN 
                            (CASE WHEN ms.fecultcli > ms.fecultprov THEN ms.fecultcli ELSE ms.fecultprov END)
                        WHEN ms.fecultcli IS NOT NULL THEN ms.fecultcli
                        WHEN ms.fecultprov IS NOT NULL THEN ms.fecultprov
                        ELSE '0000-00-00'
                     END DESC";
    } else {
        $vsql .= " ORDER BY " . ($v_fecha_condicion == 'V' ? "ms.fecultcli" : "ms.fecultprov") . " DESC";
    }
	
	if($vc = $conect_bd_actual->consulta($vsql))
	{
		?>
		
		<div class="table-responsive sinmovcexis-grid">
			
			<table  class="table table-striped table-bordered" style="align:center; min-width:2050px; width:max-content;" id="tabledatos">
				<thead>
				<th></th>
				<th><center>CODIGO</center></th>
				<th><center>PRODUCTO</center></th>
				<th><center>GRUPO</center></th>
				<th><center>LINEA</center></th>
				<th><center>COSTO.U</center></th>
				<th><center>EXIST.</center></th>
				<th><center>UND</center></th>
				<th><center>BODEGA</center></th>
				<th><center>COSTO</center></th>
				<th><center>U.COMPRA</center></th>
				<th><center>U.VENTA</center></th>
				<th><center>DÍAS</center></th>
				<th><center>TRASLADO</center></th>
				</thead>
				<tbody id="cuerpo">
				
		<?php	
				$v_cont=1;
				while($vr = ibase_fetch_object($vc))
				{
					$v_existeidtras=0;
					if($v_traslado=='SI'){
						
							
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
										
										
											$vsql="select * from trasla where codprefijo='".$vprefijo_traslado."' and bodini='".$vr->IDBODEGA."' and sucid='1' and fecasentad is null and fecha>='2024-06-01'";
											if($vcv = $conect_bd_actual->consulta($vsql))
											{
												if($vrv = ibase_fetch_object($vcv))
												{
													$v_existeidtras = $vrv->TRASLAID;
												}	
											}
											
										
										if($v_existeidtras==0){
											
										
												$vsql="select max(cast(numero as Integer)) as numero from trasla where codprefijo='".$vprefijo_traslado."'";
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
											
												fCrearLogTNS($_SESSION["user"],'EL USUARIO '.$_SESSION["user"].' GENERO EL TRASLADO '.$vprefijo_traslado.'-'.$v_numerotras.' DE FORMA MANUAL',$contenidoBdActual);
												$vsql="select traslaid  from trasla where codprefijo='".$vprefijo_traslado."' and numero='".$v_numerotras."' and sucid='1' ";
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
										
									}
								}
							}
							
						
					}
					
					$v_existencia = $v_existencia + $vr->EXISTENCIA;
					$v_costo = $vr->EXISTENCIA * $vr->COSTO;
					$v_totalcosto = $v_totalcosto + $v_costo;
					setlocale(LC_ALL,"es_ES","esp");
					$v_fecha = $vr->FECHA ? date("Y-m-d", strtotime($vr->FECHA)): "";
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
							<center><?php echo $v_cont; ?></center>
						</td>
						<td style="text-align:left;<?php echo $vcolor; ?>">
							<?php echo $vr->CODIGO; ?>
						</td>
						<td style="text-align:left;">
							<?php echo utf8_encode($vr->DESCRIPCION); ?>
						</td>
						<td style="text-align:left;">
							<?php echo utf8_encode($vr->GRUPO); ?>
						</td>
						<td style="text-align:left;">
							<?php echo utf8_encode($vr->LINEA); ?>
						</td>
						<td style="text-align:right;">
							<?php echo number_format($vr->COSTO); ?>
						</td>
						<td style="text-align:right;">
							<?php echo $vr->EXISTENCIA; ?>
						</td>
						<td style="text-align:left;">
							<?php echo utf8_encode($vr->UNIDAD); ?>
						</td>
						<td style="text-align:left;">
							<?php echo utf8_encode($vr->BODEGA); ?>
						</td>
						<td style="text-align:right;">
							<?php echo number_format($v_costo); ?>
						</td>
						<td style="text-align:right; <?php if ($v_fecha > $vultventa) { echo 'background-color: #DFF2BF;'; } ?>">
							<?php echo $v_fecha; ?>
						</td>
						<td style="text-align:right; <?php if ($vultventa > $v_fecha) { echo 'background-color: #DFF2BF;'; } ?>">
							<?php echo $vultventa; ?>
						</td>
						<td style="text-align:right; <?php if ($vcantidad_dias > 0) { echo 'background-color: #FFDDDD;'; } ?>">
							<?php if ($vcantidad_dias != '') { echo number_format($vcantidad_dias); } else { echo $vcantidad_dias; } ?>
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
						<td colspan="7" style="text-align:right;">
							<b>TOTAL EXISTENCIA</b>
						</td>
						<td style="text-align:right;">
							<?php echo "<b>".number_format($v_existencia)."</b>"; ?>
						</td>
						<td colspan="2" style="text-align:right;">
							<b>TOTAL COSTO</b>
						</td>
						<td style="text-align:right;">
							<?php echo "<b>".number_format($v_totalcosto)."</b>"; ?>
						</td>
						<td colspan="3"></td>
					</tr>
				</tbody>
			</table>
			</center>
		</div>
		<?php

		// Obtener el número total de registros
		$vsqlCount = "SELECT COUNT(*) AS total FROM material AS m 
					  INNER JOIN grupmat g ON m.grupmatid = g.grupmatid 
					  INNER JOIN materialsuc AS ms ON m.matid = ms.matid 
					  INNER JOIN salmaterial AS sm ON m.matid = sm.matid
					  INNER JOIN lineamat AS l ON m.lineamatid = l.lineamatid 
					  WHERE m.matid NOT IN (SELECT d.matid FROM dekardex d INNER JOIN kardex AS k ON d.kardexid = k.kardexid WHERE k.codcomp != 'NI')";

		if (!empty($v_bodega)) {
			$vsqlCount .= " AND sm.bodid = '".$v_bodega."'";
		}

		$vsqlCount .= " AND ms.existenc > 0 
					  AND sm.existenc > 0 
					  AND g.grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '00.%') 
					  AND sm.bodid NOT IN (SELECT b.bodid FROM bodega AS b WHERE b.codigo = '99' OR b.codigo = '98')";

		if ($v_grupo > 0) {
			$vsqlCount .= " AND g.grupmatid = '$v_grupo'";
		}

		if ($v_linea > 0) {
			$vsqlCount .= " AND m.lineamatid = '$v_linea'";
		}

		switch ($v_fecha_condicion) {
			case 'V':
				$vsqlCount .= " AND ms.fecultcli <= '$v_fechabusqueda' AND ms.fecultcli IS NOT NULL";
				break;
			case 'C':
				$vsqlCount .= " AND ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultprov IS NOT NULL";
				break;
			case 'U':
				$vsqlCount .= " AND ((ms.fecultprov > ms.fecultcli AND ms.fecultprov < '$v_fechabusqueda') 
								  OR (ms.fecultprov < ms.fecultcli AND ms.fecultcli < '$v_fechabusqueda') 
								  OR (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli <= '$v_fechabusqueda') 
								  OR (ms.fecultprov IS NULL AND ms.fecultcli <= '$v_fechabusqueda') 
								  OR (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli IS NULL) 
								  OR (ms.fecultprov IS NULL AND ms.fecultcli IS NULL AND ms.fecact <= '$v_fechabusqueda'))";
				break;
		}

		$resultCount = $conect_bd_actual->consulta($vsqlCount);
		$totalRecords = ibase_fetch_object($resultCount)->TOTAL;
		$totalPages = ceil($totalRecords / $v_registros);

		// Mostrar el total de registros, el total de páginas y el número de página actual
        echo '<div style="text-align: center; margin-top: 10px;">';
        echo 'Total de registros: <strong>' . $totalRecords . '</strong> | ';
        echo 'Total de paginas: <strong>' . $totalPages . '</strong> | ';
        echo 'Pagina actual: <strong>' . $page . '</strong>';
        echo '</div>';

		// Controles de paginación
		echo '<nav aria-label="Page navigation" style="display: flex; justify-content: center;">';
		echo '<ul class="pagination">';
		if ($page > 1) {
			echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(1, \'ListaSinMovConExis_ajax.php\', \'Productos\')"><<</a></li>';
			echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(' . ($page - 1) . ', \'ListaSinMovConExis_ajax.php\', \'Productos\')"><</a></li>';
		}
		$maxPages = min($totalPages, 10);
		$startPage = max(1, $page - floor($maxPages / 2));
		$endPage = min($startPage + $maxPages - 1, $totalPages);
		for ($i = $startPage; $i <= $endPage; $i++) {
			echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="#" onclick="cambiarPagina(' . $i . ', \'ListaSinMovConExis_ajax.php\', \'Productos\')">' . $i . '</a></li>';
		}
		if ($page < $totalPages) {
			echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(' . ($page + 1) . ', \'ListaSinMovConExis_ajax.php\', \'Productos\')">></a></li>';
			echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(' . $totalPages . ', \'ListaSinMovConExis_ajax.php\', \'Productos\')">>></a></li>';
		}
		echo '</ul>';
		echo '</nav>';
	}
}		
if(isset($_GET['tipo']) and $_GET['tipo']=='excel')
{
?>
<script>
function ExportToExcel(type, fn, dl) {
    var elt = document.getElementById('tabledatos');
    var wb = XLSX.utils.table_to_book(elt, { sheet: "sheet1" });
    return dl ?
        XLSX.write(wb, { bookType: type, bookSST: true, type: 'base64' }) :
        XLSX.writeFile(wb, fn || ('Productos_sinmovimientos_conexistencia.' + (type || 'xlsx')));
}

$(function () {
    $('#search').quicksearch('table tbody tr');								
});
</script>
<?php
}
?>
