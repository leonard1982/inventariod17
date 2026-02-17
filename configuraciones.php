<?php
require("conecta.php");
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuraciones</title>
    <?php includeAssets(); ?>
    <link rel="stylesheet" href="css/configuraciones.css">

    <!-- jQuery y Select2 -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/css/select2.min.css" rel="stylesheet" />
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.1.0-beta.1/js/select2.min.js"></script>
</head>
<body>
<?php
$v_id = isset($_GET["id"]) ? $_GET["id"] : "";

if (!empty($v_id)) {
    $vsql = "SELECT * FROM configuraciones WHERE id='$v_id'";
    $vc = $conect_bd_inventario->consulta($vsql);
    $vr = ibase_fetch_object($vc);
} else {
    $vr = (object)[
        'ID' => '',
        'GRUPO' => '',
        'PORCENTAJE_SEGURIDAD' => '',
        'TIEMPO_ENTREGA' => '',
        'DIAS_INVENTARIO' => '',
        'DIAS_PEDIDOS' => '',
        'DIAS_CIERRE' => '',
        'TENDENCIA_MESES' => '',
        'CORREO_NOTIFICACION' => '',
        'PREFIJO_ORDEN_PEDIDO' => '',
        'PREFIJO_TRASLADO' => ''
    ];
}
?>
<div class="table-responsive">
    <center>
        <h4>CONFIGURACIONES</h4>
        <div class="input-group" style="justify-content: center;">
            <button type="button" id="GuardarConfiguracion" class="btn btn-success">Guardar</button>
            <button type="button" id="NuevoRegistro" class="btn btn-primary">Nuevo Registro</button>
            <button type="button" id="Volver" class="btn btn-secondary">Volver</button>
        </div>
        <br>
        <table class="table table-striped table-bordered" style="width:1000px;">
            <tbody>
                <tr>
                    <td>ID</td>
                    <td><input type="text" id="id" value="<?= $vr->ID ?>" class="form-control" readonly></td>
                </tr>
                <tr>
                    <td>GRUPO</td>
                    <td>
                        <select class="form-select select2" name="grupo" id="grupo" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $vsql2 = "SELECT grupmatid, codigo, descrip FROM grupmat WHERE codigo NOT LIKE '00.%' ORDER BY codigo ASC";
                            $vc2 = $conect_bd_actual->consulta($vsql2);
                            while ($vr2 = ibase_fetch_object($vc2)) {
                                $selected = ($vr2->GRUPMATID == $vr->GRUPO) ? "selected" : "";
                                echo "<option value='{$vr2->GRUPMATID}' $selected>{$vr2->CODIGO} - {$vr2->DESCRIP}</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>PORCENTAJE SEGURIDAD</td>
                    <td><input type="number" id="porcentaje" value="<?= $vr->PORCENTAJE_SEGURIDAD ?>" class="form-control" step="0.1"></td>
                </tr>
                <tr>
                    <td>TIEMPO ENTREGA</td>
                    <td><input type="number" id="tiempo" value="<?= $vr->TIEMPO_ENTREGA ?>" class="form-control"></td>
                </tr>
                <tr>
                    <td>DIAS DE INVENTARIO</td>
                    <td><input type="number" id="dias" value="<?= $vr->DIAS_INVENTARIO ?>" class="form-control"></td>
                </tr>
                <tr>
                    <td>DIAS PEDIDOS</td>
                    <td><input type="number" id="dias_pedidos" value="<?= $vr->DIAS_PEDIDOS ?>" class="form-control"></td>
                </tr>
                <tr>
                    <td>DIAS PARA CERRAR BACKORDER</td>
                    <td><input type="number" id="dias_cierre" value="<?= $vr->DIAS_PARA_CIERRE ?>" class="form-control"></td>
                </tr>
                <tr>
                    <td>TENDENCIA EN MESES</td>
                    <td><input type="number" id="tendencia_meses" value="<?= $vr->TENDENCIA_MESES ?>" class="form-control"></td>
                </tr>
                <tr>
                    <td>CORREO NOTIFICACION</td>
                    <td><input type="email" id="correo_notificacion" value="<?= $vr->CORREO_NOTIFICACION ?>" class="form-control"></td>
                </tr>
                <tr>
                    <td>PREFIJO ORDEN DE PEDIDO</td>
                    <td>
                        <select class="form-select select2" name="prefijo_orden" id="prefijo_orden" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $vsql3 = "SELECT codprefijo FROM prefijo ORDER BY codprefijo ASC";
                            $vc3 = $conect_bd_actual->consulta($vsql3);
                            while ($vr3 = ibase_fetch_object($vc3)) {
                                $selected = ($vr3->CODPREFIJO == $vr->PREFIJO_ORDEN_PEDIDO) ? "selected" : "";
                                echo "<option value='{$vr3->CODPREFIJO}' $selected>{$vr3->CODPREFIJO}</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td>PREFIJO TRASLADO</td>
                    <td>
                        <select class="form-select select2" name="prefijo_traslado" id="prefijo_traslado" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $vc4 = $conect_bd_actual->consulta($vsql3);
                            while ($vr4 = ibase_fetch_object($vc4)) {
                                $selected = ($vr4->CODPREFIJO == $vr->PREFIJO_TRASLADO) ? "selected" : "";
                                echo "<option value='{$vr4->CODPREFIJO}' $selected>{$vr4->CODPREFIJO}</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
				<tr>
                    <td>PROVEEDOR</td>
                    <td>
                        <select class="form-select select2" name="proveedor" id="proveedor" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $vsql5 = "SELECT nit, nombre FROM terceros where proveed='S' ORDER BY nombre ASC";
                            $vc5 = $conect_bd_actual->consulta($vsql5);
                            while ($vr5 = ibase_fetch_object($vc5)) {
                                $selected = ($vr5->NIT == $vr->NIT_PROVEEDOR) ? "selected" : "";
                                echo "<option value='{$vr5->NIT}' $selected>".utf8_encode($vr5->NOMBRE)."</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </center>
</div>
<script src="js/configuraciones.js?f=09072025_01"></script>
<script>
$(document).ready(function() {
    $('.select2').select2({
        width: '100%',
        placeholder: 'Seleccionar...',
        allowClear: true
    });
});
</script>
</body>
</html>