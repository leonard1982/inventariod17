<?php
require("conecta.php");
?>
<!DOCTYPE html>
<html lang="es" dir="ltr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Configuración Lineas</title>
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
    $vsql = "SELECT * FROM sn_presu_vend_lineas WHERE id='$v_id'";
    $vc = $conect_bd_actual->consulta($vsql);
    $vr = ibase_fetch_object($vc);
} else {
    $vr = (object)[
        'ID' => '',
        'TERID' => '',
        'LINEAID' => '',
        'PRESUPUESTO' => '',
		'LINEA' => '',
		'CANTIDAD' => ''
    ];
}
?>
<div class="table-responsive">
    <center>
        <h4>CONFIGURACIÓN LÍNEA</h4>
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
                    <td>LÍNEA</td>
                    <td>
                        <select class="form-select select2" name="linea" id="linea" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $vsql2 = "SELECT lineamatid, codigo, descrip FROM lineamat ORDER BY codigo ASC";
                            $vc2 = $conect_bd_actual->consulta($vsql2);
                            while ($vr2 = ibase_fetch_object($vc2)) {
                                $selected = ($vr2->LINEAMATID == $vr->LINEAID) ? "selected" : "";
                                echo "<option value='{$vr2->LINEAMATID}' $selected>{$vr2->CODIGO} - {$vr2->DESCRIP}</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
				<tr>
                    <td>ASESOR</td>
                    <td>
                        <select class="form-select select2" name="terid" id="terid" required>
                            <option value="">Seleccionar</option>
                            <?php
                            $vsql5 = "SELECT terid, nit, nombre FROM terceros where VENDED='S' ORDER BY nombre ASC";
                            $vc5 = $conect_bd_actual->consulta($vsql5);
                            while ($vr5 = ibase_fetch_object($vc5)) {
                                $selected = ($vr5->TERID == $vr->TERID) ? "selected" : "";
                                echo "<option value='{$vr5->TERID}' $selected>".utf8_encode($vr5->NOMBRE)."</option>";
                            }
                            ?>
                        </select>
                    </td>
                </tr>
				<tr>
                    <td>PRESUPUESTO</td>
					
					<?php
					if(isset($vr->PRESUPUESTO) and $vr->PRESUPUESTO>0)
					{
						echo "<td><input type='text' id='presupuesto' value='".number_format($vr->PRESUPUESTO, 0, '', ',')."' class='form-control'></td>";
					}
					else
					{
						echo "<td><input type='text' id='presupuesto' value='' class='form-control'></td>";
					}
					?>

                </tr>
				<tr>
                    <td>AGRUPACIÓN</td>
					
					<?php
					if(isset($vr->LINEA))
					{
						echo "<td><input type='text' id='agrupacion' value='".$vr->LINEA."' class='form-control'></td>";
					}
					else
					{
						echo "<td><input type='text' id='agrupacion' value='' class='form-control'></td>";
					}
					?>

                </tr>
				<tr>
                    <td>USAR CANTIDAD O BASE</td>
                    <td>
                        <select class="form-select select2" name="cantidad_base" id="cantidad_base" required>
							<?php $selectedc = ('SI' == $vr->CANTIDAD) ? "selected" : ""; ?>
							<?php $selectedv = ('NO' == $vr->CANTIDAD) ? "selected" : ""; ?>
                            <option value="SI" <?php echo $selectedc; ?> >CANTIDAD</option>
							<option value="NO" <?php echo $selectedv; ?> >BASE (VALOR)</option>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
    </center>
</div>
<script src="js/configuracionLineas.js?f=25072025_01"></script>
<script>

function formatNumberWithCommas(n) {
    return n.replace(/\D/g, "") // eliminar todo lo que no es dígito
            .replace(/\B(?=(\d{3})+(?!\d))/g, ",");
}

document.getElementById('presupuesto').addEventListener('input', function (e) {
    let val = e.target.value.replace(/,/g, '');
    if (!isNaN(val) && val !== '') {
        e.target.value = formatNumberWithCommas(val);
    } else {
        e.target.value = '';
    }
});

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