<?php
require("conecta.php");
date_default_timezone_set('America/Bogota');

/* ==========================================================
   AJUSTES
   ========================================================== */
$kardexCampoPC        = 'CODCOMP';      // o 'CODCON'
$kardexValorPC        = 'PC';           // Pedido de compra
$kardexCampoProveedor = 'CLIENTE';      // Columna proveedor en KARDEX
$kardexCampoPrefijo   = 'CODPREFIJO';
$forzarPuntoFinalEnPrefijo = true;
$vemail               = "";
// Leer el archivo línea por línea
$lineas_smtp = file('F:\facilweb_fe73_32\htdocs\evento_inventario\servidor_smtp.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

// Inicializar variables en blanco para smpt
$de = '';
$nombreDe = '';
$servidorSMTP = '';
$puertoSMTP = 0;
$usuarioSMTP = '';
$contrasenaSMTP = '';

// Asignar valores solo si existen las posiciones en el array
if (isset($lineas_smtp[0])) $de = trim($lineas_smtp[0]);
if (isset($lineas_smtp[1])) $nombreDe = trim($lineas_smtp[1]);
if (isset($lineas_smtp[2])) $servidorSMTP = trim($lineas_smtp[2]);
if (isset($lineas_smtp[3])) $puertoSMTP = intval(trim($lineas_smtp[3]));
if (isset($lineas_smtp[4])) $usuarioSMTP = trim($lineas_smtp[4]);
if (isset($lineas_smtp[5])) $contrasenaSMTP = trim($lineas_smtp[5]);

//nos traemos el correo de notificacion
$vsql = "select correo_notificacion from configuraciones where id='1'";
if($dxx = $conect_bd_inventario->consulta($vsql))
{
	if($rxx = ibase_fetch_object($dxx))
	{
		$vemail = $rxx->CORREO_NOTIFICACION;
	}
}

$destinatario = $vemail;
//Correo para hacer seguimiento
$cco          = "leo2904.trabajo@gmail.com";
$asunto       = "Notificación de Vencimiento";

$vProductos   = [];

function generarMensajeProductos($vProductos)
{
    // Construir las filas de la tabla
    $filas = '';
    if (is_array($vProductos) && count($vProductos) > 0) {
        foreach ($vProductos as $prod) {
            $codigo = isset($prod['codigo']) ? htmlspecialchars(trim($prod['codigo'])) : '';
            $descripcion = isset($prod['descripcion']) ? htmlspecialchars(trim($prod['descripcion'])) : '';
            $filas .= "
                <tr>
                    <td>{$codigo}</td>
                    <td>{$descripcion}</td>
                </tr>";
        }
    }
	else
	{
		return '';
        //$filas = '<tr><td colspan="2">No hay productos para mostrar.</td></tr>';
    }

    // Construir el mensaje completo en HTML
    $mensaje = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
            }
            table {
                width: 100%;
                border-collapse: collapse;
            }
            th, td {
                padding: 8px 12px;
                border: 1px solid #ddd;
                text-align: left;
            }
            th {
                background-color: #f4f4f4;
            }
            .header {
                background-color: #4CAF50;
                color: white;
                padding: 10px 0;
                text-align: center;
            }
        </style>
    </head>
    <body>
        <div class="header">
            <h2>Productos para pedir</h2>
        </div>
        <p>Estimado usuario,</p>
        <p>Le informamos que los siguientes productos han llegado al punto de pedido:</p>
        <table>
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Descripción</th>
                </tr>
            </thead>
            <tbody>'
                . $filas .
            '</tbody>
        </table>
        <p>Por favor, tome las medidas necesarias para gestionar estos productos.</p>
        <p>Atentamente,</p>
        <p><strong>Sistema de Notificación</strong></p>
    </body>
    </html>';

    return $mensaje;
}

function redondear_dos_decimal($valor) 
{ 
   $float_redondeado=round($valor * 100) / 100; 
   return $float_redondeado; 
}

/* ==========================================================
   ROLLBACK: setup utilidades
   ========================================================== */
if (!isset($ROLLBACK_SQL)) $ROLLBACK_SQL = [];
if (!function_exists('rb_add')) {
  function rb_add($sql) { global $ROLLBACK_SQL; $ROLLBACK_SQL[] = rtrim($sql, ";\n ").";"; }
}
$__rb_ts   = date('Ymd_His');
$__rb_file = __DIR__ . '/LOG/rollback_auto_pedido_' . $__rb_ts . '.sql';

/* ==========================================================
   1) LEER CONFIGURACIONES Y MAPEAR A PREFIJOS DE GRUPMAT
   (usa INICIAR_EN (TIMESTAMP/NULL) y DIAS_PEDIDOS (INT))
   ========================================================== */
$sqlCfg = "SELECT GRUPO, PREFIJO_ORDEN_PEDIDO, DIAS_PEDIDOS, NIT_PROVEEDOR, INICIAR_EN
           FROM CONFIGURACIONES";
$rcfg   = $conect_bd_inventario->consulta($sqlCfg);

$prefijos = [];
while ($vr = ibase_fetch_object($rcfg)) {
    $grupo_id = trim($vr->GRUPO);
    if ($grupo_id === '') continue;

    $sqlG  = "SELECT CODIGO FROM GRUPMAT WHERE GRUPMATID = '".addslashes($grupo_id)."'";
    $rgrp  = $conect_bd_actual->consulta($sqlG);
    $gobj  = $rgrp ? ibase_fetch_object($rgrp) : null;

    if ($gobj && !empty($gobj->CODIGO)) {
        $prefix = trim($gobj->CODIGO);
        if ($forzarPuntoFinalEnPrefijo && substr($prefix, -1) !== '.') $prefix .= '.';

        $prefijos[] = [
            'grupo_id' => $grupo_id,
            'prefix'   => $prefix,
            'cfg'      => [
                'prefijo_orden_pedido' => $vr->PREFIJO_ORDEN_PEDIDO,
                'dias_pedidos'         => (int)($vr->DIAS_PEDIDOS ?? 0),
                'nit_proveedor'        => $vr->NIT_PROVEEDOR,
                'iniciar_en'           => $vr->INICIAR_EN, // TIMESTAMP o NULL
            ],
        ];
    }
}

/* ==========================================================
   2) WHERE por lista de prefijos
   ========================================================== */
$vgrupos = "";
if (!empty($prefijos)) {
    $partes = [];
    foreach ($prefijos as $p) $partes[] = "g.codigo LIKE '" . addslashes($p['prefix']) . "%'";
    $vgrupos = " AND (" . implode(" OR ", $partes) . ") ";
}

/* ==========================================================
   3) CONSULTA PRINCIPAL
   ========================================================== */
$sql = "
SELECT 
    m.codigo                              AS codproducto,
    m.descrip                             AS desproducto,
    s.bodid                               AS bodid,
    m.grupmatid                           AS grupmatid,
    g.codigo                              AS codgrupo,
    COALESCE(t.terid, 1)                  AS proveedor,
    ma.codigo                             AS clasificacion,
    ROUND(COALESCE(ms.existmin, 0))       AS existmin,
    IIF(COALESCE(ms.costo,0) = 0, COALESCE(ms.ULTCOSTPROM,0), COALESCE(ms.costo,0)) AS costo,
    ROUND(COALESCE(ms.existmax, 0))       AS existmax,
    COALESCE(ms.sn_punto_pedido, 1)       AS sn_punto_pedido,
    COALESCE(ms.sn_stock_maximodif, 1)    AS sn_stock_maximo,
    ROUND(COALESCE(ms.existenc, 0))       AS existenc
FROM material m
INNER JOIN grupmat g      ON m.grupmatid = g.grupmatid
INNER JOIN salmaterial s  ON m.matid     = s.matid
INNER JOIN materialsuc ms ON ms.matid    = m.matid AND ms.sucid = '1'
INNER JOIN marcaart ma    ON m.marcaartid= ma.marcaartid
LEFT  JOIN terceros t     ON ms.ultpro   = t.terid
WHERE s.sucid = '1'
  AND ma.codigo IN ('A','B')
  AND ms.sn_punto_pedido >= 0
  AND ms.existenc <= ms.sn_punto_pedido
  $vgrupos
ORDER BY ma.codigo ASC
";

//echo $sql;

$r = $conect_bd_actual->consulta($sql);

/* ==========================================================
   4) Helper: tomar config por prefijo más largo que calce
   ========================================================== */
function tomarConfigPorCodigoGrupo($codigoGrupo, $prefijos) {
    $best = null;
    foreach ($prefijos as $p) {
        $pref = $p['prefix'];
        if (strncmp($codigoGrupo, $pref, strlen($pref)) === 0) {
            if ($best === null || strlen($pref) > strlen($best['prefix'])) $best = $p;
        }
    }
    if ($best) {
        return [
            'cfg'          => $best['cfg'],
            'cfg_grupo_id' => $best['grupo_id'],
            'cfg_prefix'   => $best['prefix'],
        ];
    }
    return [
        'cfg'          => ['prefijo_orden_pedido'=>null, 'dias_pedidos'=>0, 'nit_proveedor'=>null, 'iniciar_en'=>null],
        'cfg_grupo_id' => null,
        'cfg_prefix'   => null,
    ];
}

/* ==========================================================
   4.1) Regla calendario (HOY ≥ INICIAR_EN + DIAS_PEDIDOS)
   ========================================================== */
function debeEjecutarHoy($iniciarEn, $diasPedidos) {
    if ($iniciarEn === null) return true;
    $dias = max(0, (int)$diasPedidos);
    $tsIni = strtotime($iniciarEn);
    if ($tsIni === false) return true;
    $tsObjetivo = strtotime("+{$dias} day", $tsIni);
    $hoy = strtotime(date('Y-m-d 00:00:00'));
    return ($hoy >= $tsObjetivo);
}

/* ==========================================================
   5) Recorrido + ejecución de inserciones
   ========================================================== */
$rows            = [];
$ejecutoPorGrupo = []; // para luego actualizar INICIAR_EN
$kardexPorPar    = [];    // cache (terid|prefijo) => {exists,data:{kardexid,numero}}
$vcontador       = 0;

while ($x = ibase_fetch_object($r))
{
    $codgrupo = trim($x->CODGRUPO);
    $match   = tomarConfigPorCodigoGrupo($codgrupo, $prefijos);
    $cfg     = $match['cfg'];
	$npedido = "";

    $runHoy = debeEjecutarHoy($cfg['iniciar_en'] ?? null, $cfg['dias_pedidos'] ?? 0);

    /* =================== BLOQUE DE EJECUCIÓN =================== */
    $ejecutoOk = false;
    if ($runHoy)
	{
        // Reglas de cantidad:
        $stockMax = (int)$x->SN_STOCK_MAXIMO;
        $exist    = (int)$x->EXISTENC;

        if ($stockMax > $exist && !($stockMax <= 1 && $exist >= $stockMax)) {
            $cantidad = $stockMax - $exist; // CANLISTA/CANMAT
            if ($cantidad > 0) {
                $prefijo = trim((string)($cfg['prefijo_orden_pedido'] ?? ''));
                if ($prefijo !== '') {
                    // terid
                    $terid = $x->PROVEEDOR;
                    if (empty($terid)) {
                        $nitConf = $cfg['nit_proveedor'] ?? null;
                        if (!empty($nitConf)) {
                            $rsTer = $conect_bd_actual->consulta(
                                "SELECT FIRST 1 TERID FROM TERCEROS WHERE NIT = '".addslashes($nitConf)."' OR NITTRI = '".addslashes($nitConf)."'"
                            );
                            if ($rsTer && ($terObj = ibase_fetch_object($rsTer))) $terid = $terObj->TERID;
                        }
                        if (empty($terid)) $terid = 1;
                    }

                    // Reusar / buscar KARDEX abierto para (terid|prefijo)
                    $cacheKey = $terid.'|'.$prefijo;
                    $kardexid = null;

                    if (isset($kardexPorPar[$cacheKey]) && $kardexPorPar[$cacheKey]['exists'])
					{
                        $kardexid = (int)$kardexPorPar[$cacheKey]['data']['kardexid'];
						
						if(isset($kardexPorPar[$cacheKey]['data']['npedido']))
						{
							$npedido  = $kardexPorPar[$cacheKey]['data']['npedido'];
						}
                    }
					else
					{
                        $sqlK = "
                            SELECT FIRST 1 KARDEXID, CAST(NUMERO AS INTEGER) AS NUMERO, CODPREFIJO
                            FROM KARDEX
                            WHERE {$kardexCampoPC} = '{$kardexValorPC}'
                              AND {$kardexCampoPrefijo} = '".addslashes($prefijo)."'
                              AND {$kardexCampoProveedor} = '".addslashes($terid)."'
                              AND FECASENTAD IS NULL
                            ORDER BY CAST(NUMERO AS INTEGER) DESC
                        ";
                        $rsK = $conect_bd_actual->consulta($sqlK);
                        if ($rsK && ($k = ibase_fetch_object($rsK))) {
                            $kardexid = (int)$k->KARDEXID;
							$npedido  = $k->CODPREFIJO.$k->NUMERO;
                            $kardexPorPar[$cacheKey] = ['exists'=>true, 'data'=>['kardexid'=>$kardexid,'numero'=>$k->NUMERO,'npedido'=>$npedido]];
                        }
                    }

                    // Si NO hay, crear un KARDEX nuevo (INSERT mínimo para evitar -804)
                    if (empty($kardexid))
					{
                        // Siguiente número por (PC, prefijo, proveedor)
                        $rsNum = $conect_bd_actual->consulta("
                            SELECT COALESCE(MAX(CAST(NUMERO AS INTEGER)),0)+1 AS NUEVONUM
                            FROM KARDEX
                            WHERE {$kardexCampoPC} = '{$kardexValorPC}'
                              AND {$kardexCampoPrefijo} = '".addslashes($prefijo)."'");
							  
                        $newNum = ($rsNum && ($nObj = ibase_fetch_object($rsNum))) ? (int)$nObj->NUEVONUM : 1;
						
						if($newNum==1)
						{
							echo "Numero en uno: ".$newNum."<br>";
						}
						
						$npedido = addslashes($prefijo).$newNum;

                        $hoyStr  = date('Y/m/d');
						$vence   = date('Y-m-d', strtotime($hoyStr . ' +30 days'));
						$periodo = date('m', strtotime($hoyStr)); // devuelve el mes en 2 dígitos
                        $sqlInsK = "
                          INSERT INTO KARDEX (
                            {$kardexCampoPC}, {$kardexCampoPrefijo}, NUMERO, FECHA, FECASENTAD,
                            SUCID, {$kardexCampoProveedor}, VENDEDOR, FORMAPAGO,
                            SN_IDESTADOPEDIDO, SN_ESTADO_INV, CENID, AREADID, PERIODO, PLAZODIAS, FECVENCE,
							RETIVA, RETICA, RETFTE, VRICONSUMO, VRRFTE, VRRICA, VRRIVA, RETCREE, VRRCREE, USUARIO,
							FACTORCONV, VRIVAEXC, USUACREA
                          ) VALUES (
                            '{$kardexValorPC}', '".addslashes($prefijo)."', '{$newNum}', '{$hoyStr}', NULL,
                            '1', '".addslashes($terid)."', 1, 'CR',
                            NULL, 'PENDIENTE','1','1','".$periodo."','30','{$vence}',
							'0','0','0','0','0','0','0','0','0','ADMIN',
							'1', '0', 'ADMIN')";
							
                        $conect_bd_actual->consulta($sqlInsK);

                        // Recuperar KARDEXID insertado
                        $rsKid = $conect_bd_actual->consulta("
                            SELECT FIRST 1 KARDEXID, NUMERO
                            FROM KARDEX
                            WHERE {$kardexCampoPC} = '{$kardexValorPC}'
                              AND {$kardexCampoPrefijo} = '".addslashes($prefijo)."'
                              AND NUMERO = '{$newNum}'
                            ORDER BY KARDEXID DESC");
							
                        if ($rsKid && ($kidObj = ibase_fetch_object($rsKid))) {
                            $kardexid = (int)$kidObj->KARDEXID;
                            $kardexPorPar[$cacheKey] = ['exists'=>true, 'data'=>['kardexid'=>$kardexid,'numero'=>$kidObj->NUMERO]];

                            // KARDEXSELF: marcar AUTOMATICO (insert si no existe, update si existe)
                            $rsSelf = $conect_bd_actual->consulta("SELECT KARDEXID FROM KARDEXSELF WHERE KARDEXID = {$kardexid}");
                            if ($rsSelf && ibase_fetch_object($rsSelf)) {
                                $conect_bd_actual->consulta("UPDATE KARDEXSELF SET PEDIDO='AUTOMATICO' WHERE KARDEXID = {$kardexid}");
                            } else {
                                $conect_bd_actual->consulta("INSERT INTO KARDEXSELF (KARDEXID, PEDIDO) VALUES ({$kardexid}, 'AUTOMATICO')");
                            }

                            // ROLLBACK para este KARDEX nuevo
                            rb_add("DELETE FROM DEKARDEX   WHERE KARDEXID = {$kardexid}");
                            rb_add("DELETE FROM KARDEXSELF WHERE KARDEXID = {$kardexid}");
                            rb_add("DELETE FROM KARDEX     WHERE KARDEXID = {$kardexid}");
                        }
                    }

                    // Si tenemos KARDEXID, intentar insertar DEKARDEX si no existe
                    if (!empty($kardexid)) {
                        // MATID por CODIGO
                        $codigoMat = addslashes($x->CODPRODUCTO);
                        $rsMat = $conect_bd_actual->consulta("SELECT FIRST 1 MATID FROM MATERIAL WHERE CODIGO = '{$codigoMat}'");
                        $matid = ($rsMat && ($mObj = ibase_fetch_object($rsMat))) ? (int)$mObj->MATID : null;

                        if (!empty($matid)) {
                            // ¿Ya existe línea?
                            $rsDk = $conect_bd_actual->consulta("
                                SELECT FIRST 1 DEKARDEXID
                                FROM DEKARDEX
                                WHERE KARDEXID = {$kardexid} AND MATID = {$matid}
                            ");
                            if ($rsDk && ibase_fetch_object($rsDk)) {
                                // ya existe, no hacer nada
                            } else {
                                // PORCIVA desde MATERIAL->TIPOIVA (si falla, 0)
                                $porciva = 0.0;
                                $rsIva = $conect_bd_actual->consulta("
                                    SELECT p.PORCIVA
                                    FROM MATERIAL m
                                    JOIN TIPOIVA p ON p.TIPOIVAID = m.TIPOIVAID
                                    WHERE m.MATID = {$matid}
                                ");
                                if ($rsIva && ($ivaObj = ibase_fetch_object($rsIva)) && $ivaObj->PORCIVA !== null) {
                                    $porciva = (float)$ivaObj->PORCIVA;
                                }

                                // Nuevo DEKARDEXID (si no hay generador/trigger)
                                $rsNewD = $conect_bd_actual->consulta("SELECT COALESCE(MAX(DEKARDEXID),0)+1 AS NUEVOID FROM DEKARDEX");
                                $newDid = ($rsNewD && ($dObj = ibase_fetch_object($rsNewD))) ? (int)$dObj->NUEVOID : 1;

                                $bodid   = (int)($x->BODID ?: 1);
                                $costo   = (float)$x->COSTO;

                                $cantL   = number_format($cantidad, 5, '.', '');
                                $cantM   = number_format($cantidad, 3, '.', '');
                                $precio  = number_format($costo,    2, '.', '');
                                $porIVA  = number_format($porciva,  2, '.', '');
								$precioNETO = number_format(($precio * (($porIVA/100)+1)),  2, '.', '');
								$precioIVA  = number_format((($precio * (($porIVA/100)+1)) - $precio),  2, '.', '');
								$precio  = $precioNETO - $precioIVA;

                                // INSERT DEKARDEX (mínimo válido = columnas = valores)
                                $sqlInsD = "
                                  INSERT INTO DEKARDEX (
                                    KARDEXID, MATID, BODID,
                                    TIPUND, CANLISTA, CANMAT, PRECIOLISTA, PRECIOVTA, PORCIVA,
                                    SN_SELECCION, PRECIOIVA, PRECIOBASE, PRECIONETO, PARCVTA
                                  ) VALUES (
                                    {$kardexid}, {$matid}, {$bodid},
                                    'D', {$cantL}, {$cantM}, {$precio}, {$precio}, {$porIVA},
									0 ,{$precioIVA},{$precio},{$precioNETO}, ".($precio * $cantL).")";
									
                                $conect_bd_actual->consulta($sqlInsD);
								
								//consultamos el codigo y descripción del producto para ponerlo en un array
								$vsql_producto = "select codigo, descrip from material where matid='".$matid."'";
								if($cprod = $conect_bd_actual->consulta($vsql_producto))
								{
								
									if($rprod = ibase_fetch_object($cprod))
									{
										$vProductos[$vcontador]["codigo"]      = $rprod->CODIGO;
										$vProductos[$vcontador]["descripcion"] = $rprod->DESCRIP;
										
										$vcontador++;
 									}
								}
								
								//*************************************
								$vsql= "SELECT KARDEXID,SUM(PRECIOBASE*CANMAT) AS BASE,SUM(PRECIOIVA*CANMAT) AS IVA, SUM(PRECIONETO*CANMAT) AS NETO FROM DEKARDEX WHERE KARDEXID='".$kardexid."' GROUP BY 1";
								if($consulta = $conect_bd_actual->consulta($vsql))
								{
								
									if($rx = ibase_fetch_object($consulta))
									{
										$vbase = $rx->BASE;
										$viva  = $rx->IVA;
										$vneto = $vbase+$rx->IVA;
										$vfpcontado = 0;
										$vfpcredito = 0;
										$vajusteneto =0;
										$vajusteiva  =0;
										
										//echo "neto :".$vneto.", neto2: ".$rx->NETO."<BR>";
										
										if($vneto>0 and floatval($rx->NETO)>0)
										{
											$vajusteneto = redondear_dos_decimal($vneto - $rx->NETO);
											$vajusteneto = $vajusteneto * -1;
											$vajusteiva  = $vajusteneto * -1;
										}
									
										$vfpcredito = $vneto;

										$vsql3 = "UPDATE KARDEX SET AJUSTENETO='".$vajusteneto."',AJUSTEIVA='".$vajusteiva."',VRBASE='".$vbase."',VRIVA='".$viva."',TOTAL='".$vneto."',FPCONTADO='".$vfpcontado."',FPCREDITO='".$vfpcredito."',NETOIVA='".($viva+$vajusteiva)."',NETO='".($vneto+$vajusteneto)."',VRTOTAL='".$vneto."' WHERE KARDEXID='".$kardexid."'";
										$conect_bd_actual->consulta($vsql3);
										
										//echo $vsql3;
										//exit();
									}
								}
								//*************************************

                                // ROLLBACK del ítem agregado
                                rb_add("DELETE FROM DEKARDEX WHERE KARDEXID = {$kardexid} AND MATID = {$matid}");

                                $ejecutoOk = true; // para actualizar INICIAR_EN de su grupo
                            }
                        }
                    }
                }
            }
        }
    }
    /* ================= FIN BLOQUE EJECUCIÓN ================= */

    // Si ejecutó y fue OK => marcar grupo para actualizar INICIAR_EN
    if ($runHoy && $ejecutoOk && !empty($match['cfg_grupo_id'])) {
        $ejecutoPorGrupo[$match['cfg_grupo_id']] = true;
    }
	
	$stockMax = (int)$x->SN_STOCK_MAXIMO;
    $exist    = (int)$x->EXISTENC;
	$cantidad = $stockMax - $exist;

    // Para la tabla
    $rows[] = [
        'codproducto'       => utf8_encode($x->CODPRODUCTO ?? ''),
        'desproducto'       => utf8_encode($x->DESPRODUCTO ?? ''),
        'bodid'             => $x->BODID,
        'grupmatid'         => $x->GRUPMATID,
        'codgrupo'          => $codgrupo,
        'proveedor_terid'   => $x->PROVEEDOR,
        'clasificacion'     => $x->CLASIFICACION,
        'existmin'          => $x->EXISTMIN,
        'costo'             => $x->COSTO,
        'existmax'          => $x->EXISTMAX,
        'sn_punto_pedido'   => $x->SN_PUNTO_PEDIDO,
        'sn_stock_maximo'   => $x->SN_STOCK_MAXIMO,
        'existenc'          => $x->EXISTENC,
        'cfg_prefijo_orden' => $cfg['prefijo_orden_pedido'],
        'cfg_dias_pedidos'  => (int)($cfg['dias_pedidos'] ?? 0),
        'cfg_nit_proveedor' => $cfg['nit_proveedor'],
        'cfg_grupo_id'      => $match['cfg_grupo_id'],
        'cfg_grupo_codigo'  => $match['cfg_prefix'],
        'cfg_iniciar_en'    => $cfg['iniciar_en'],
		'cantidad_pedida'   => $cantidad,
		'npedido'           => $npedido,
    ];
}
$total_registros = count($rows);

/* ==========================================================
   6) ACTUALIZAR INICIAR_EN = NOW() SOLO SI EJECUTÓ OK
   ========================================================== */
if (!empty($ejecutoPorGrupo)) {
    $ahora = date('Y-m-d H:i:s');
    foreach (array_keys($ejecutoPorGrupo) as $gid) {
        $gidEsc = addslashes($gid);
        $sqlUp = "UPDATE CONFIGURACIONES SET INICIAR_EN = '{$ahora}' WHERE GRUPO = '{$gidEsc}'";
        $conect_bd_inventario->consulta($sqlUp);
    }
}

/* ==========================================================
   7) TABLA HTML FINAL (sin columnas extra)
   ========================================================== */
$tabla_html  = '';
$tabla_html .= '<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%;font-family:Arial, Helvetica, sans-serif;font-size:12px">';
$tabla_html .= '<thead style="background:#f2f2f2"><tr>';
$ths = [
    '#','Código','Descripción','Clasificación',
    'Grupo (código)','Grupo (ID)','Bodega',
    'Existencia','Punto pedido','Stock Máx.','Exist. mín.','Exist. máx.','Costo',
    'Proveedor (terid)',
    'Cfg. Prefijo Orden','Cfg. DÍAS_PEDIDOS','Cfg. NIT Proveedor',
    'Cfg. Grupo (ID)','Cfg. Grupo (código)',
    'INICIAR_EN','Cantidad Pedida', 'No Pedido'
];
foreach ($ths as $th) $tabla_html .= '<th>'.htmlspecialchars($th).'</th>';
$tabla_html .= '</tr></thead><tbody>';

$i = 1;
foreach ($rows as $row) {
    $gid       = $row['cfg_grupo_id'];
    $actualizo = (!empty($gid) && isset($ejecutoPorGrupo[$gid]) && $ejecutoPorGrupo[$gid] === true);
    $style     = $actualizo ? ' style="background:#e7ffe7"' : '';

    $tabla_html .= "<tr{$style}>";
    $tabla_html .= '<td>'.$i.'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['codproducto']).'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['desproducto']).'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['clasificacion']).'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['codgrupo']).'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['grupmatid']).'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['bodid']).'</td>';
    $tabla_html .= '<td style="text-align:right">'.number_format((float)$row['existenc'],0,',','.').'</td>';
    $tabla_html .= '<td style="text-align:right">'.number_format((float)$row['sn_punto_pedido'],0,',','.').'</td>';
    $tabla_html .= '<td style="text-align:right">'.number_format((float)$row['sn_stock_maximo'],0,',','.').'</td>';
    $tabla_html .= '<td style="text-align:right">'.number_format((float)$row['existmin'],0,',','.').'</td>';
    $tabla_html .= '<td style="text-align:right">'.number_format((float)$row['existmax'],0,',','.').'</td>';
    $tabla_html .= '<td style="text-align:right">'.number_format((float)$row['costo'],2,',','.').'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['proveedor_terid']).'</td>';

    $tabla_html .= '<td>'.htmlspecialchars($row['cfg_prefijo_orden'] ?? '').'</td>';
    $tabla_html .= '<td style="text-align:right">'.(int)$row['cfg_dias_pedidos'].'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['cfg_nit_proveedor'] ?? '').'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['cfg_grupo_id'] ?? '').'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['cfg_grupo_codigo'] ?? '').'</td>';
    $tabla_html .= '<td>'.htmlspecialchars($row['cfg_iniciar_en'] ?? '').'</td>';
	$tabla_html .= '<td>'.htmlspecialchars($row['cantidad_pedida'] ?? '').'</td>';
	$tabla_html .= '<td>'.htmlspecialchars($row['npedido'] ?? '').'</td>';

    $tabla_html .= '</tr>';
    $i++;
}
$tabla_html .= '</tbody></table>';

echo "<h3>Total registros: {$total_registros}</h3>";
echo $tabla_html;

/* ==========================================================
   8) GUARDAR LOG COMO EXCEL (HTML .XLS) EN /LOG
   ========================================================== */
$ts = date('Ymd_His');
$logDir = __DIR__ . DIRECTORY_SEPARATOR . 'LOG';
if (!is_dir($logDir)) { @mkdir($logDir, 0775, true); }
$filename = $logDir . DIRECTORY_SEPARATOR . "auto_pedido_{$ts}.xls";
$htmlToSave = "<html><head><meta charset='UTF-8'></head><body>" . $tabla_html . "</body></html>";
file_put_contents($filename, $htmlToSave);
echo "<p style='margin-top:8px;font-family:Arial,Helvetica,sans-serif;font-size:12px'>
      Archivo guardado: <strong>{$filename}</strong></p>";

/* ==========================================================
   9) ROLLBACK: volcar a archivo .sql
   ========================================================== */
if (!is_dir(__DIR__ . '/LOG')) { @mkdir(__DIR__ . '/LOG', 0775, true); }
$lines = [];
$lines[] = "-- Rollback generado: " . date('Y-m-d H:i:s');
$lines[] = "SET AUTODDL OFF;";
foreach ($ROLLBACK_SQL as $sql) $lines[] = $sql;
$lines[] = "COMMIT;";
file_put_contents($__rb_file, implode(PHP_EOL, $lines));

echo "<p style='margin:6px 0;font-family:Arial,Helvetica,sans-serif;font-size:12px'>
        Script de reversa: <strong>{$__rb_file}</strong>
      </p>";
	  
	  
// Si todo ok, mandamos el email
if (
	count($vProductos) > 0 &&
	!empty($destinatario) &&
	!empty($asunto) &&
	!empty($mensaje) &&
	!empty($de) &&
	!empty($nombreDe) &&
	!empty($servidorSMTP) &&
	!empty($puertoSMTP) &&
	!empty($usuarioSMTP) &&
	!empty($contrasenaSMTP)
)
{
	//llenamos el mensaje
	$mensaje = generarMensajeProductos($vProductos);
	
	if(!empty($mensaje))
	{
		// Llamada a la función
		$v_accion = enviarCorreoSMTP(
			$destinatario,
			$asunto,
			$mensaje,
			$de,
			$nombreDe,
			$servidorSMTP,
			$puertoSMTP,
			$usuarioSMTP,
			$contrasenaSMTP,
			$cco
		);
	}
}

?>
