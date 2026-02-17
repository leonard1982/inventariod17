<?php
session_start();
require("conecta.php");

// Verifica si las columnas necesarias existen en la tabla material
function verificarColumna($conexion, $nombre_columna) {
    $vsql_check_column = "SELECT RDB\$FIELD_NAME AS SI
                          FROM RDB\$RELATION_FIELDS 
                          WHERE RDB\$RELATION_NAME = 'MATERIAL' 
                          AND RDB\$FIELD_NAME = UPPER('$nombre_columna')";
    $stmt = $conexion->consulta($vsql_check_column);
    return ibase_fetch_object($stmt) !== false;
}

if (!verificarColumna($conect_bd_actual, 'MARCAARTID_ANTERIOR')) {
    $vsql_add_column = "ALTER TABLE material ADD marcaartid_anterior INTEGER";
    echo $vsql_add_column . "<br>";
    $conect_bd_actual->consulta($vsql_add_column);
}

if (!verificarColumna($conect_bd_actual, 'ULTIMA_CLASIFICACION')) {
    $vsql_add_column = "ALTER TABLE material ADD ultima_clasificacion DATE";
    echo $vsql_add_column . "<br>";
    $conect_bd_actual->consulta($vsql_add_column);
}

// Función principal de clasificación
function obtenerClasificacion($conect_bd_actual, $conect_bd_anterior, $codigo_producto) {
    $rotaciones_array = [];

    // Fechas objetivo: desde el primer día del mes anterior, 6 meses hacia atrás
    $meses_referencia = [];
    for ($i = 0; $i < 6; $i++) {
        $mes = (new DateTime('first day of last month'))->modify("-$i months");
        $meses_referencia[] = $mes->format('Ym'); // ej: "202504"
    }

    // Consulta las fechas de movimiento desde ambas bases si aplica
    $obtenerFechas = function($conexion) use ($codigo_producto, $meses_referencia) {
        $fecha_limite = (new DateTime('first day of last month'))->modify('-5 months')->format('Y-m-d');

        $sql = "SELECT k.fecha
                FROM kardex k
                INNER JOIN dekardex d ON d.kardexid = k.kardexid
                WHERE d.matid = (SELECT matid FROM material WHERE codigo = '$codigo_producto')
                AND k.fecasentad IS NOT NULL
                AND k.fecanulado IS NULL
                AND k.codcomp = 'FV'
                AND k.fecha >= '$fecha_limite'
                ORDER BY k.fecha";
		//echo $sql."<br><br>";
        $stmt = $conexion->consulta($sql);
        $fechas = [];
        while ($row = ibase_fetch_object($stmt)) {
            $fechas[] = new DateTime($row->FECHA);
        }
        return $fechas;
    };

    $usa_anterior = ((int)date("m") <= 6);
    if ($usa_anterior) {
        $rotaciones_array = array_merge($rotaciones_array, $obtenerFechas($conect_bd_anterior));
    }
    $rotaciones_array = array_merge($rotaciones_array, $obtenerFechas($conect_bd_actual));
	
	//print_r($rotaciones_array);

    // Marcar meses con movimiento
    $meses_rotados = array_fill(0, 6, false);
    foreach ($rotaciones_array as $fecha) {
        $mes_mov = $fecha->format('Ym');
        foreach ($meses_referencia as $index => $mes_ref) {
            if ($mes_mov === $mes_ref) {
                $meses_rotados[$index] = true;
            }
        }
    }

    // Clasificación
    $clasificacion = 'D';
    $suma = array_sum($meses_rotados);

	if ($suma === 6) {
		$clasificacion = 'A';
	} 
	elseif ($suma >= 3 && $suma <= 5) {
		if (
			($meses_rotados[0] && $meses_rotados[2] && $meses_rotados[4]) ||
			($meses_rotados[1] && $meses_rotados[3] && $meses_rotados[5]) ||
			($meses_rotados[3] && $meses_rotados[4] && $meses_rotados[5]) ||
			($meses_rotados[2] && $meses_rotados[4] && $meses_rotados[5])
		) {
			$clasificacion = 'B';
		} else {
			$clasificacion = 'C';
		}
	} 
	elseif ($suma > 0) {
		$clasificacion = 'C';
	} 
	else {
		$clasificacion = 'D';
	}

    return $clasificacion;
}

// Procesa todos los productos
function procesarProductos($conect_bd_actual, $conect_bd_anterior, $callback) {
    $vsql_productos = "SELECT m.codigo, m.descrip, g.codigo || ' - ' || g.descrip as grupo
                       FROM material m 
                       INNER JOIN grupmat g ON m.grupmatid = g.grupmatid 
                       WHERE g.codigo NOT LIKE '00.%'";
					   
	//echo $vsql_productos;
    $stmt = $conect_bd_actual->consulta($vsql_productos);
    $productos = [];
    while ($row = ibase_fetch_object($stmt)) {
        $productos[] = $row;
    }
	//print_r($productos);
	
    foreach ($productos as $producto) {
        $codigo = $producto->CODIGO;
        $clasificacion = obtenerClasificacion($conect_bd_actual, $conect_bd_anterior, $codigo);
		
		//echo $clasificacion;

        $producto_clasificado = [
            'grupo' => utf8_encode($producto->GRUPO),
            'codigo' => utf8_encode($codigo),
            'descripcion' => utf8_encode($producto->DESCRIP),
            'clasificacion' => utf8_encode($clasificacion)
        ];

        // Inicia transacción
        $conect_bd_actual->startTransaction();

        // Actualiza los campos
        $sql1 = "UPDATE material 
                 SET marcaartid_anterior = marcaartid, 
                     ultima_clasificacion = '" . date("Y-m-d") . "' 
                 WHERE codigo = '$codigo'";

        $sql2 = "UPDATE material 
                 SET marcaartid = (SELECT marcaartid FROM marcaart WHERE codigo = '$clasificacion') 
                 WHERE codigo = '$codigo'";

        $conect_bd_actual->consulta($sql1);
        $conect_bd_actual->consulta($sql2);
        $conect_bd_actual->commit();

        $callback($producto_clasificado);
    }
}

// Mostrar resultados
echo "<table border='1'>";
echo "<tr><th>Grupo</th><th>Codigo</th><th>Producto</th><th>Clasificacion</th></tr>";

procesarProductos($conect_bd_actual, $conect_bd_anterior, function($producto) {
    echo "<tr>
            <td>{$producto['grupo']}</td>
            <td>{$producto['codigo']}</td>
            <td>{$producto['descripcion']}</td>
            <td>{$producto['clasificacion']}</td>
          </tr>";
    flush();
});

echo "</table>";
?>