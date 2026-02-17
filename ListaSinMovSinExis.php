<!DOCTYPE html>
<?php
include_once 'conecta.php';
include_once 'php/importarExcel.php';
?>

<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Productos Sin Movimiento y Sin Existencia</title>

    <!-- Scripts CSS -->
    <link rel="stylesheet" href="css/bootstrap.min.css">
    <link rel="stylesheet" href="css/datatables.min.css">
    <link rel="stylesheet" href="css/bootstrap-clockpicker.css">
    <link rel="stylesheet" href="css/alertify.min.css">
    <link rel="stylesheet" href="fullcalendar/main.css">
    <link rel="stylesheet" href="css/sortable-theme-dark.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/css/bootstrap.min.css" integrity="sha384-rbsA2VBKQhggwzxH7pPCaAqO46MgnOM80zW1RWuH61DGLwZJEdK2Kadq2F9CUG65" crossorigin="anonymous">

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
    <script src="https://unpkg.com/xlsx@0.15.1/dist/xlsx.full.min.js"></script>
    <script src="js/sortable.min.js"></script>
    <script src="js/ItemsMenu.js?v=05092024_01"></script>
    <link rel="stylesheet" href="css/ListaSinMovSinExis.css">
</head>

<body class="bodyc">
    <div class="container">
        <div class="container-fluid" style="overflow:auto;width:100%;">
            <center>
                <br>
                <h2 id="titulo">PRODUCTOS SIN MOVIMIENTO Y SIN EXISTENCIA</h2>
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
                        <label for="reg">No. Registros:</label>
                        <input type="number" class="form-control" id="reg" name="reg" value="20" style="text-align:right;"/>
                    </div>
                    <div class="col-md-3">
                        <label for="paraeliminar">Para Eliminar</label>
                        <select class="form-select" name="paraeliminar" id="paraeliminar">
                            <option value="NO">NO</option>
                            <option value="SI">SI</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>&nbsp;</label>
                        <div class="input-group">
                            <input type="button" id="actualizar" class="btn btn-success" value="Generar" onclick="generarInforme('generar', 'ListaSinMovSinExis_ajax.php', 'Productos Sin Movimiento y Sin Existencia')">
                            <button type="button" class="btn btn-primary" id="btnExport" onclick="generarInforme('excel', 'ListaSinMovSinExis_ajax.php', 'Productos Sin Movimiento y Sin Existencia')">Excel</button>
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