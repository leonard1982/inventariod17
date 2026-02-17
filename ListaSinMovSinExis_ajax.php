<?php
/**
 * Este script genera un informe de productos sin movimiento y sin existencia.
 * 
 * Funcionalidades principales:
 * - Exportar el informe a un archivo Excel.
 * - Generar una consulta SQL basada en varios parámetros.
 * - Mostrar los resultados en una tabla HTML con paginación.
 * - Permitir la eliminación de productos marcados.
 * 
 * Variables principales:
 * - $v_registros: Número máximo de registros a mostrar.
 * - $v_grupo: ID del grupo de productos.
 * - $v_linea: ID de la línea de productos.
 * - $v_fecha_condicion: Condición de fecha para filtrar los productos.
 * - $offset: Desplazamiento para la paginación.
 * - $v_para_eliminar: Indica si los productos deben ser marcados para eliminación.
 * - $anios: Número de años para calcular la fecha de búsqueda.
 * - $v_fecha: Fecha actual.
 * - $v_fechaanterior: Fecha calculada restando años y un mes a la fecha actual.
 * - $v_fechabusqueda: Fecha de búsqueda calculada.
 * 
 * Funciones:
 * - generarConsultaSQL: Genera la consulta SQL basada en los parámetros proporcionados.
 * 
 * Procesamiento del formulario:
 * - Se procesan tanto solicitudes GET como POST.
 * - Se calculan los valores de las variables basadas en los parámetros de la solicitud.
 * - Se genera la consulta SQL y se ejecuta.
 * - Se muestran los resultados en una tabla HTML con paginación.
 * - Se permite la eliminación de productos marcados.
 * 
 * Exportación a Excel:
 * - Si el parámetro 'tipo' es 'excel', se exporta la tabla a un archivo Excel.
 */

if(isset($_GET['tipo']) and $_GET['tipo']=='excel')
{
    $filename = "Lista Sin Movimiento y Sin Existencia.xls";
    header("Content-type: application/x-msdownload; charset=utf-8");
    header("Content-Disposition: attachment; filename=$filename");
    header("Pragma: no-cache");
    header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
}

require("conecta.php");

$v_registros = 9999999;
$v_grupo     = 0;
$v_linea     = 0;
$v_fecha_condicion = 'U';
$offset      = 1;
$v_para_eliminar   = "NO";

// Variables iniciales
$anios   = 1;
if(isset($_POST['anios']))
{
    $anios = $_POST['anios'];
}
if(isset($_GET['anios']))
{
    $anios = $_GET['anios'];
}
$v_fecha = date('Y-m-d');
$v_fechaanterior = date("Y-m-d", strtotime("$v_fecha - $anios year - 1 month"));
$v_fechabusqueda = date("Y-m-d", strtotime(date("Y-m", strtotime($v_fechaanterior)) . '-01'));

// Función para generar la consulta SQL
function generarConsultaSQL($v_registros, $v_grupo, $v_linea, $v_fechabusqueda, $v_fecha_condicion, $offset = 0) {

    //Omitimos los servicios y el grupo 10. que es GERFOR
    $baseSQL = "SELECT FIRST $v_registros SKIP $offset m.descrip AS descripcion, ms.existenc AS existencia, ms.fecultcli AS fecha_venta, ms.fecultprov AS fecha_compra, m.codigo AS codigo, l.descrip AS linea, g.descrip AS grupo, 
                (SELECT FIRST 1 (k.codcomp || k.codprefijo || k.numero) AS documento FROM kardex AS k INNER JOIN dekardex AS d ON (k.kardexid = d.kardexid) WHERE d.matid = m.matid ORDER BY k.fecha DESC) AS documento, 
                (SELECT FIRST 1 k.fecha AS fecha_documento FROM kardex AS k INNER JOIN dekardex AS d ON (k.kardexid = d.kardexid) WHERE d.matid = m.matid ORDER BY k.fecha DESC) AS fecha_documento 
                FROM material AS m 
                INNER JOIN grupmat g ON m.grupmatid = g.grupmatid 
                INNER JOIN materialsuc AS ms ON m.matid = ms.matid 
                INNER JOIN lineamat AS l ON m.lineamatid = l.lineamatid 
                WHERE ms.sucid=1 and ms.existenc = 0 AND m.codigo NOT LIKE '%.' 
                AND g.grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '00.%')
                AND g.grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '10.%')";

    // Añadir condiciones basadas en la fecha
    switch ($v_fecha_condicion) {
        case 'V':
            $baseSQL .= " AND ms.fecultcli < '$v_fechabusqueda' AND ms.fecultcli IS NOT NULL AND ms.fecultprov IS NOT NULL";
            break;
        case 'C':
            $baseSQL .= " AND ms.fecultprov < '$v_fechabusqueda' AND ms.fecultcli IS NOT NULL AND ms.fecultprov IS NOT NULL";
            break;
        case 'U':
            $baseSQL .= " AND ((ms.fecultprov > ms.fecultcli AND ms.fecultprov < '$v_fechabusqueda') OR 
                              (ms.fecultprov < ms.fecultcli AND ms.fecultcli < '$v_fechabusqueda') OR 
                              (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli <= '$v_fechabusqueda') OR 
                              (ms.fecultprov IS NULL AND ms.fecultcli <= '$v_fechabusqueda') OR 
                              (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli IS NULL) OR 
                              (ms.fecultprov IS NULL AND ms.fecultcli IS NULL AND ms.fecact <= '$v_fechabusqueda'))";
            break;
    }

    // Añadir condiciones basadas en el grupo y la línea
    if ($v_grupo > 0) {
        $baseSQL .= " AND g.grupmatid = '$v_grupo'";
    }

    if ($v_linea > 0) {
        $baseSQL .= " AND m.lineamatid = '$v_linea'";
    }

    // Ordenar por fecha de última compra o venta
    if ($v_fecha_condicion == 'U') {
        $baseSQL .= " ORDER BY CASE 
                        WHEN ms.fecultcli IS NOT NULL AND ms.fecultprov IS NOT NULL THEN 
                            (CASE WHEN ms.fecultcli > ms.fecultprov THEN ms.fecultcli ELSE ms.fecultprov END)
                        WHEN ms.fecultcli IS NOT NULL THEN ms.fecultcli
                        WHEN ms.fecultprov IS NOT NULL THEN ms.fecultprov
                        ELSE '0000-00-00'
                     END DESC";
    } else {
        $baseSQL .= " ORDER BY " . ($v_fecha_condicion == 'V' ? "ms.fecultcli" : "ms.fecultprov") . " DESC";
    }

    return $baseSQL;
}

// Procesar el formulario si se ha enviado
if($_SERVER['REQUEST_METHOD'] === 'GET' or $_SERVER['REQUEST_METHOD'] === 'POST'){

    $page = 1;
    $totalRecords = 0;
    $totalPages = 0;

    if (isset($_GET['reg'])) {
        $v_registros = $_GET['reg'];
        $v_grupo = $_GET['grupo'];
        $v_linea = $_GET['linea'];
        $v_fecha_condicion = $_GET['fecha'];
        $v_para_eliminar = $_GET['paraeliminar'];
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    } else if (isset($_POST['reg'])) {
        $v_registros = $_POST['reg'];
        $v_grupo = $_POST['grupo'];
        $v_linea = $_POST['linea'];
        $v_fecha_condicion = $_POST['fecha'];
        $v_para_eliminar = $_POST['paraeliminar'];
        $page = isset($_POST['page']) ? (int)$_POST['page'] : 1;
    }

    $offset = ($page - 1) * $v_registros;

    if(isset($_SESSION["user"]))
    {
        fCrearLogTNS($_SESSION["user"], 'EL USUARIO ' . $_SESSION["user"] . ' GENERO EL INFORME DE LA OPCION (LISTA SIN MOV Y SIN EXIS)', $contenidoBdActual);
    }

    $vsql = generarConsultaSQL($v_registros, $v_grupo, $v_linea, $v_fechabusqueda, $v_fecha_condicion, $offset);

    if ($vc = $conect_bd_actual->consulta($vsql)){
        ?>
        <div <?php echo isset($_GET['anios']) ? '' : ' class="table-responsive" '; ?> >
            <table <?php echo isset($_GET['anios']) ? '' : ' class="table table-striped table-bordered" '; ?> data-sortable style="align:center;" id="tabledatos">
                <thead>
                <tr>
                    <th></th>
                    <th><center>CODIGO</center></th>
                    <th><center>PRODUCTO</center></th>
                    <th><center>GRUPO</center></th>
                    <th><center>LINEA</center></th>
                    <th><center>ULT. COMPRA</center></th>
                    <th><center>ULT. VENTA</center></th>
                    <th><center>EXISTENCIA</center></th>
                    <th><center>ULT. DOCUMENTO</center></th>
                    <th><center>FECHA DOCUMENTO</center></th>
                    <?php if ($v_para_eliminar == "SI" && $v_fecha_condicion == 'U' && empty($v_grupo) && empty($v_linea)) {
                        if(isset($_SESSION["user"]))
                        {
                            fCrearLogTNS($_SESSION["user"], 'EL USUARIO ' . $_SESSION["user"] . ' MARCO PRODUCTOS PARA ELIMINAR EN LA OPCION (LISTA SIN MOV Y SIN EXIS)', $vbd);
                        }
                        echo "<th></th>";
                    } ?>
                </tr>
                </thead>
                <tbody id="cuerpo">
                <?php
                $v_cont = 1;
                while ($vr = ibase_fetch_object($vc))
                {
                    //$v_fechacompra = date("Y-m-d", strtotime($vr->FECHA_COMPRA));
                    $v_fechacompra = $vr->FECHA_COMPRA ? date("Y-m-d", strtotime($vr->FECHA_COMPRA)) : "";
                    $v_fechaventa  = $vr->FECHA_VENTA ? date("Y-m-d", strtotime($vr->FECHA_VENTA)) : "";
                    $v_fechadocumento = $vr->FECHA_DOCUMENTO ? date("Y-m-d", strtotime($vr->FECHA_DOCUMENTO)) : "";

                    $row = function($vr, $v_cont, $v_fechacompra, $v_fechaventa, $v_fechadocumento, $v_para_eliminar, $v_fecha_condicion, $v_grupo, $v_linea, $conect_bd_actual) {
                        ?>
                        <tr>
                            <td><center><?php echo $v_cont; ?></center></td>
                            <td style="text-align:left;"><?php echo $vr->CODIGO; ?></td>
                            <td style="text-align:left;"><?php echo utf8_encode($vr->DESCRIPCION); ?></td>
                            <td style="text-align:left;"><?php echo utf8_encode($vr->GRUPO); ?></td>
                            <td style="text-align:left;"><?php echo utf8_encode($vr->LINEA); ?></td>
                            <td style="text-align:right; <?php if ($v_fechacompra > $v_fechaventa) { echo 'background-color: #DFF2BF;'; } ?>">
                                <?php echo $v_fechacompra; ?>
                            </td>
                            <td style="text-align:right; <?php if ($v_fechaventa > $v_fechacompra) { echo 'background-color: #DFF2BF;'; } ?>">
                                <?php echo $v_fechaventa; ?>
                            </td>
                            <td style="text-align:right;"><?php echo $vr->EXISTENCIA; ?></td>
                            <td style="text-align:right;"><?php echo $vr->DOCUMENTO; ?></td>
                            <td style="text-align:right;"><?php echo $v_fechadocumento; ?></td>
                            <?php
                            if ($v_para_eliminar == "SI" && $v_fecha_condicion == 'U' && empty($v_grupo) && empty($v_linea)) {
                                echo "<td>Eliminar</td>";
                                $vsql = "UPDATE material SET marcaartid = (SELECT ma.marcaartid FROM marcaart ma WHERE ma.codigo = 'PELIMINAR') WHERE codigo = '$vr->CODIGO'";
                                $conect_bd_actual->consulta($vsql);
                            }

                            if ($v_para_eliminar == "NO" && $v_fecha_condicion == 'U' && empty($v_grupo) && empty($v_linea)) {
                                $vsql = "UPDATE material SET marcaartid = 1 WHERE codigo = '$vr->CODIGO'";
                                $conect_bd_actual->consulta($vsql);
                            }
                            ?>
                        </tr>
                        <?php
                    };

                    if(isset($_GET["anios"])) {
                        if(empty($v_fechadocumento)) {
                            $row($vr, $v_cont, $v_fechacompra, $v_fechaventa, $v_fechadocumento, $v_para_eliminar, $v_fecha_condicion, $v_grupo, $v_linea, $conect_bd_actual);
                            $v_cont++;
                            $totalRecords++;
                        }
                    } else {
                        $row($vr, $v_cont, $v_fechacompra, $v_fechaventa, $v_fechadocumento, $v_para_eliminar, $v_fecha_condicion, $v_grupo, $v_linea, $conect_bd_actual);
                        $v_cont++;
                    }
                }
                ?>
                </tbody>
            </table>
        </div>
        <?php

        if($totalRecords==0)
        {
            // Obtener el número total de registros
            $vsqlCount = "SELECT COUNT(*) AS total FROM material AS m 
                        INNER JOIN grupmat g ON m.grupmatid = g.grupmatid 
                        INNER JOIN materialsuc AS ms ON m.matid = ms.matid 
                        INNER JOIN lineamat AS l ON m.lineamatid = l.lineamatid 
                        WHERE ms.sucid=1 and ms.existenc = 0 AND m.codigo NOT LIKE '%.' AND g.grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '00.%')";
            if ($v_grupo > 0) {
                $vsqlCount .= " AND g.grupmatid = '$v_grupo'";
            }
            if ($v_linea > 0) {
                $vsqlCount .= " AND m.lineamatid = '$v_linea'";
            }
            switch ($v_fecha_condicion) {
                case 'V':
                    $vsqlCount .= " AND ms.fecultcli < '$v_fechabusqueda' AND ms.fecultcli IS NOT NULL AND ms.fecultprov IS NOT NULL";
                    break;
                case 'C':
                    $vsqlCount .= " AND ms.fecultprov < '$v_fechabusqueda' AND ms.fecultcli IS NOT NULL AND ms.fecultprov IS NOT NULL";
                    break;
                case 'U':
                    $vsqlCount .= " AND ((ms.fecultprov > ms.fecultcli AND ms.fecultprov < '$v_fechabusqueda') OR 
                                    (ms.fecultprov < ms.fecultcli AND ms.fecultcli < '$v_fechabusqueda') OR 
                                    (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli <= '$v_fechabusqueda') OR 
                                    (ms.fecultprov IS NULL AND ms.fecultcli <= '$v_fechabusqueda') OR 
                                    (ms.fecultprov <= '$v_fechabusqueda' AND ms.fecultcli IS NULL) OR 
                                    (ms.fecultprov IS NULL AND ms.fecultcli IS NULL AND ms.fecact <= '$v_fechabusqueda'))";
                    break;
            }
            $resultCount = $conect_bd_actual->consulta($vsqlCount);
            $totalRecords = ibase_fetch_object($resultCount)->TOTAL;
            $totalPages = ceil($totalRecords / $v_registros);
        }

        // Mostrar el total de registros, el total de páginas y el número de página actual
        echo '<div style="text-align: center; margin-top: 10px;">';
        echo 'Total de registros: <strong>' . $totalRecords . '</strong> | ';
        echo 'Total de paginas: <strong>' . $totalPages . '</strong> | ';
        echo 'Pagina actual: <strong>' . $page . '</strong>';
        echo '</div>';

        if(!isset($_GET["anios"]))
        {
            // Controles de paginación
            echo '<nav aria-label="Page navigation" style="display: flex; justify-content: center;">';
            echo '<ul class="pagination">';
            if ($page > 1) {
                echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(1, \'ListaSinMovSinExis_ajax.php\', \'Productos\')"><<</a></li>';
                echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(' . ($page - 1) . ', \'ListaSinMovSinExis_ajax.php\', \'Productos\')"><</a></li>';
            }
            $maxPages = min($totalPages, 10);
            $startPage = max(1, $page - floor($maxPages / 2));
            $endPage = min($startPage + $maxPages - 1, $totalPages);
            for ($i = $startPage; $i <= $endPage; $i++) {
                echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '"><a class="page-link" href="#" onclick="cambiarPagina(' . $i . ', \'ListaSinMovSinExis_ajax.php\', \'Productos\')">' . $i . '</a></li>';
            }
            if ($page < $totalPages) {
                echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(' . ($page + 1) . ', \'ListaSinMovSinExis_ajax.php\', \'Productos\')">></a></li>';
                echo '<li class="page-item"><a class="page-link" href="#" onclick="cambiarPagina(' . $totalPages . ', \'ListaSinMovSinExis_ajax.php\', \'Productos\')">>></a></li>';
            }
            echo '</ul>';
            echo '</nav>';
        }
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
        XLSX.writeFile(wb, fn || ('Productos_sinmovimientos_sinexistencia.' + (type || 'xlsx')));
}

$(function () {
    $('#search').quicksearch('table tbody tr');								
});
</script>
<?php
}
?>