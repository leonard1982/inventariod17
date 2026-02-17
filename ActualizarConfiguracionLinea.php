<?php
require("conecta.php");

$id          = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$lineaid     = isset($_POST['lineaid']) ? (int)$_POST['lineaid'] : null;
$terid       = isset($_POST['terid']) ? (int)$_POST['terid'] : null;
$presupuesto = isset($_POST['presupuesto']) ? (float)$_POST['presupuesto'] : null;
$agrupacion  = isset($_POST['agrupacion']) ? (string)$_POST['agrupacion'] : null;
$cantidad_base=isset($_POST['cantidad_base']) ? (string)$_POST['cantidad_base'] : null; 

if ($lineaid === null || $terid === null || $presupuesto === null) {
    echo json_encode(['error' => 'Datos incompletos']);
    exit;
}

$v_accion = "";
$vsql     = "";

if ($id === 0) {
    // Obtener nuevo ID
    $sql_id = "SELECT COALESCE(MAX(ID), 0) + 1 AS NEXT_ID FROM SN_PRESU_VEND_LINEAS";
    if ($res_id = $conect_bd_actual->consulta($sql_id)) {
        $row_id = ibase_fetch_object($res_id);
        if ($row_id && isset($row_id->NEXT_ID)) {
            $id = (int)$row_id->NEXT_ID;
        } else {
            echo json_encode(['error' => 'No se pudo obtener nuevo ID']);
            exit;
        }
    } else {
        echo json_encode(['error' => 'Error al obtener nuevo ID: ' . ibase_errmsg()]);
        exit;
    }

    // Insertar
    $vsql = "INSERT INTO SN_PRESU_VEND_LINEAS (ID, TERID, LINEAID, PRESUPUESTO, LINEA, CANTIDAD)
             VALUES ($id, $terid, $lineaid, $presupuesto, '$agrupacion', '$cantidad_base')";
    if ($conect_bd_actual->consulta($vsql)) {
        $v_accion = "nuevo";
    } else {
        $v_accion = "error_insertando: " . ibase_errmsg();
    }

} else {
    // Actualizar
    $vsql = "UPDATE SN_PRESU_VEND_LINEAS SET 
                TERID = $terid,
                LINEAID = $lineaid,
                PRESUPUESTO = $presupuesto,
				LINEA = '$agrupacion',
				CANTIDAD = '$cantidad_base'
             WHERE ID = $id";
    if ($conect_bd_actual->consulta($vsql)) {
        $v_accion = "actualizar";
    } else {
        $v_accion = "error_actualizando: " . ibase_errmsg();
    }
}

// --------- Crear Log ---------
$vdatos = "ID: $id - TERID: $terid - LINEAID: $lineaid - PRESUPUESTO: $presupuesto";
fCrearLogTNS($_SESSION["user"], "EL USUARIO {$_SESSION["user"]} {$v_accion} presupuesto por línea en INVENTARIO_AUTO", $contenidoBdActual);
fCrearLogTNS($_SESSION["user"], $vdatos, $contenidoBdActual);

// --------- Respuesta ---------
echo json_encode([
    'accion' => $v_accion,
    'id'     => $id,
    'sql'    => $vsql // para depuración, puedes quitarlo luego
]);
?>