<?php
session_start();
require("conecta.php");

if (isset($_POST["id"])) {
    $v_id = intval($_POST["id"]);  // Sanitiza

    $vsql = "DELETE FROM SN_PRESU_VEND_LINEAS WHERE id = $v_id";

    if ($conect_bd_actual->consulta($vsql)) {
        echo "OK";
    } else {
        echo "ERROR: " . ibase_errmsg();
    }
} else {
    echo "ERROR: ID no recibido";
}
?>