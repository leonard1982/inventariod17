<!DOCTYPE html>
<?php
include_once 'conecta.php';
include_once 'php/importarExcel.php';
?>

<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Sin Movimiento y Con Existencia</title>

    <!--Llamamos las librerias css y js -->
    <?php includeAssets(); ?>


    <script src="js/ItemsMenu.js?v=05092024_01"></script>
    <link rel="stylesheet" href="css/ListaSinMovConExis.css?v=20240913_01">
</head>

<body class="bodyc">
    <div class="container">
        <div class="container-fluid" style="width:100%;">
            <center>
                <br>
                <h2 id="titulo">PRODUCTOS SIN MOVIMIENTO Y CON EXISTENCIA</h2>
            </center>
            <form action="" method="POST">
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="searchTerm">Buscar:</label>
                        <input id="searchTerm" type="text" onkeyup="doSearch(event);" class="form-control" placeholder="Buscar"/>
                    </div>
                    <div class="col-md-3">
                        <label for="grupo">Grupo/Familia</label>
                        <select class="form-select" name="grupo" id="grupo">
                            <option value="0">TODOS</option>
                            <?php
                            $vsql = "SELECT grupmatid, codigo, descrip FROM grupmat WHERE CHAR_LENGTH(codigo)=8 AND grupmatid NOT IN (SELECT gg.grupmatid FROM grupmat gg WHERE gg.codigo LIKE '00.%')";
                            if ($cox = $conect_bd_actual->consulta($vsql)) {
                                while ($rx = ibase_fetch_object($cox)) {
                                    echo "<option value='{$rx->GRUPMATID}'>{$rx->CODIGO}--" . utf8_encode($rx->DESCRIP) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="linea">Linea</label>
                        <select class="form-select" name="linea" id="linea">
                            <option value="0">TODAS</option>
                            <?php
                            $vsql = "SELECT lineamatid, codigo, descrip FROM lineamat";
                            if ($cox = $conect_bd_actual->consulta($vsql)) {
                                while ($rx = ibase_fetch_object($cox)) {
                                    echo "<option value='{$rx->LINEAMATID}'>{$rx->CODIGO}--" . utf8_encode($rx->DESCRIP) . "</option>";
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="fecha">Fecha</label>
                        <select class="form-select" name="fecha" id="fecha">
                            <option value="U">U. Movimiento</option>
                            <option value="V">Venta</option>
                            <option value="C">Compra</option>
                        </select>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label for="anios">Antiguëdad</label>
                        <select class="form-select" name="anios" id="anios">
                            <option value="1">1 Año</option>
                            <option value="2">2 Años</option>
                            <option value="3">3 Años</option>
                            <option value="4">4 Años</option>
                            <option value="5">5 Años</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="bodega">Bodega</label>
                        <select class="form-select" name="bodega" id="bodega">
                            <option value="">TODAS</option>
                            <?php
                            $sqlBodega = "SELECT bodid, codigo, nombre FROM bodega WHERE codigo NOT IN ('98', '99')";
                            echo generarOpcionesSelect($conect_bd_actual, $sqlBodega, 'BODID', 'CODIGO');
                            ?>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="reg">No. Registros:</label>
                        <input type="number" class="form-control" id="reg" name="reg" value="15" style="text-align:right;">
                    </div>
                    <!-- Segunda línea del formulario -->
                    <div class="col-md-2">
                        <label for="traslado">Traslado</label>
                        <select class="form-select" name="traslado" id="traslado">
                            <option value="NO" selected="selected">NO</option>
                            <option value="SI">SI</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label>&nbsp;</label>
                        <div  class="input-group">
                            <button type="button" id="actualizar" class="btn btn-success" onclick="generarInforme('generar', 'ListaSinMovConExis_ajax.php', 'Productos Sin Movimiento y Con Existencia')">
                                <i class="fas fa-sync-alt"></i> Generar
                            </button>
                            <button type="button" class="btn btn-primary" id="btnExport" onclick="generarInforme('excel', 'ListaSinMovConExis_ajax.php', 'Productos Sin Movimiento y Con Existencia')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <div id="contenidosmovsexis" class="table-responsive" style="width: 100%;"></div>
        </div>
    </div>
    <?php
    // Example usage:
    createFloatingButton("fas fa-arrow-up", "Back to Top", "#titulo");
    ?>
</body>
</html>