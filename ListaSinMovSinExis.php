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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

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
    <script src="js/ItemsMenu.js?v=20260217_02"></script>
    <link rel="stylesheet" href="css/ListaSinMovSinExis.css?v=20260217_03">
</head>

<body class="bodyc report-page">
    <div class="container report-shell">
        <div class="container-fluid report-card" style="width:100%;">
            <h2 id="titulo" class="report-title">PRODUCTOS SIN MOVIMIENTO Y SIN EXISTENCIA</h2>
            <form action="" method="POST" class="report-form">
                <div class="row g-3 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="searchTerm" class="form-label">Buscar:</label>
                        <input id="searchTerm" type="text" onkeyup="doSearch(event);" class="form-control" placeholder="Buscar"/>
                    </div>
                    <div class="col-md-3">
                        <label for="grupo" class="form-label">Grupo/Familia</label>
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
                        <label for="linea" class="form-label">Linea</label>
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
                        <label for="fecha" class="form-label">Fecha</label>
                        <select class="form-select" name="fecha" id="fecha">
                            <option value="U">U. Movimiento</option>
                            <option value="V">Venta</option>
                            <option value="C">Compra</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="anios" class="form-label">Antiguëdad</label>
                        <select class="form-select" name="anios" id="anios">
                            <option value="1">1 Año</option>
                            <option value="2">2 Años</option>
                            <option value="3">3 Años</option>
                            <option value="4">4 Años</option>
                            <option value="5">5 Años</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label for="reg" class="form-label">No. Registros:</label>
                        <input type="number" class="form-control" id="reg" name="reg" value="20" style="text-align:right;"/>
                    </div>
                    <div class="col-md-3">
                        <label for="paraeliminar" class="form-label">Para Eliminar</label>
                        <select class="form-select" name="paraeliminar" id="paraeliminar">
                            <option value="NO">NO</option>
                            <option value="SI">SI</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <div class="input-group action-buttons">
                            <button type="button" id="actualizar" class="btn btn-success" onclick="generarInforme('generar', 'ListaSinMovSinExis_ajax.php', 'Productos Sin Movimiento y Sin Existencia')">
                                <i class="fas fa-filter"></i> Generar
                            </button>
                            <button type="button" class="btn btn-primary" id="btnExport" onclick="generarInforme('excel', 'ListaSinMovSinExis_ajax.php', 'Productos Sin Movimiento y Sin Existencia')">
                                <i class="fas fa-file-excel"></i> Excel
                            </button>
                        </div>
                    </div>
                </div>
            </form>
            <div id="contenidosmovsexis" class="table-responsive result-panel" style="width: 100%;"></div>
        </div>
    </div>
    <?php
    // Example usage:
    createFloatingButton("fas fa-arrow-up", "Back to Top", "#titulo");
    ?>
</body>
</html>
