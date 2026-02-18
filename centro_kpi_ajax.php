<?php
ob_start();
require('conecta.php');
header('Content-Type: application/json; charset=UTF-8');

function kpi_utf8($v){ if(!is_string($v)){ return $v; } return preg_match('//u',$v)?$v:utf8_encode($v); }
function kpi_utf8_r($d){ if(is_array($d)){ $o=array(); foreach($d as $k=>$v){ $o[kpi_utf8((string)$k)] = kpi_utf8_r($v);} return $o; } return kpi_utf8($d); }
function kpi_resp($ok,$payload=array()){ $out=''; if(ob_get_level()>0){ $out=trim(ob_get_contents()); ob_clean(); } if($out!==''){ $payload['debug_output']=$out; } echo json_encode(kpi_utf8_r(array_merge(array('ok'=>$ok?true:false),$payload)), JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); exit; }
register_shutdown_function(function(){ $e=error_get_last(); if($e && in_array($e['type'], array(E_ERROR,E_PARSE,E_CORE_ERROR,E_COMPILE_ERROR), true)){ while(ob_get_level()>0){ ob_end_clean(); } header('Content-Type: application/json; charset=UTF-8'); echo json_encode(array('ok'=>false,'message'=>'Error fatal PHP: '.$e['message'],'file'=>isset($e['file'])?$e['file']:'','line'=>isset($e['line'])?(int)$e['line']:0), JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE); } });
if(empty($_SESSION['user'])){ kpi_resp(false, array('message'=>'Sesion no valida.')); }

function kpi_pdo(){ static $pdo=null; global $contenidoBdActual; if($pdo instanceof PDO){ return $pdo; } $pdo=new PDO('firebird:dbname=127.0.0.1:'.$contenidoBdActual,'SYSDBA','masterkey'); $pdo->setAttribute(PDO::ATTR_ERRMODE,PDO::ERRMODE_EXCEPTION); return $pdo; }
function kpi_tabla(PDO $pdo,$t){ $s=$pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATIONS WHERE RDB\$RELATION_NAME = ?"); $s->execute(array(strtoupper($t))); return ((int)$s->fetchColumn())>0; }
function kpi_col(PDO $pdo,$t,$c){ $s=$pdo->prepare("SELECT COUNT(*) FROM RDB\$RELATION_FIELDS WHERE RDB\$RELATION_NAME=? AND RDB\$FIELD_NAME=?"); $s->execute(array(strtoupper($t),strtoupper($c))); return ((int)$s->fetchColumn())>0; }
function kpi_rows(PDO $pdo,$sql,$params=array()){ $s=$pdo->prepare($sql); $s->execute($params); return $s->fetchAll(PDO::FETCH_ASSOC); }
function kpi_scalar(PDO $pdo,$sql,$params=array()){ $s=$pdo->prepare($sql); $s->execute($params); $v=$s->fetchColumn(); return $v===false?0:(float)$v; }
function kpi_txt($v){ return trim((string)$v); }
function kpi_num($v){ if($v===null){return 0.0;} if(is_int($v)||is_float($v)){return (float)$v;} $t=str_replace(' ','',trim((string)$v)); if($t===''){return 0.0;} if(strpos($t,',')!==false && strpos($t,'.')!==false){ if(strrpos($t,',')>strrpos($t,'.')){ $t=str_replace('.','',$t); $t=str_replace(',','.',$t);} else { $t=str_replace(',','',$t);} } elseif(strpos($t,',')!==false){ $t=str_replace(',','.',$t);} return is_numeric($t)?(float)$t:0.0; }
function kpi_fechain($f,$hasta){ $v=trim((string)$f); if($v===''){ return null; } $v=str_replace('T',' ',$v); if(preg_match('/^\d{4}-\d{2}-\d{2}\s\d{2}:\d{2}$/',$v)){ return $v.($hasta?':59':':00'); } if(preg_match('/^\d{4}-\d{2}-\d{2}$/',$v)){ return $v.($hasta?' 23:59:59':' 00:00:00'); } return $v; }
function kpi_num_guia($p,$n){ return kpi_txt($p).'-'.kpi_txt($n); }
function kpi_num_remision($p,$n){
    $pp = kpi_txt($p);
    $nn = kpi_txt($n);
    if($pp==='' || $nn===''){ return '-'; }
    return $pp.'-'.$nn;
}
function kpi_estado_guia($e){ $x=strtoupper(kpi_txt($e)); return $x==='FINALIZADO'?'ENTREGADO':$x; }
function kpi_diff_min($a,$b){ if($a===null||$b===null){ return null; } $ta=strtotime((string)$a); $tb=strtotime((string)$b); if($ta===false||$tb===false||$tb<$ta){ return null; } return (int)round(($tb-$ta)/60); }
function kpi_horas($f){ $t=strtotime((string)$f); if($t===false){ return 0; } $d=time()-$t; return $d>0?(int)floor($d/3600):0; }
function kpi_prom($arr){ $n=0; $s=0.0; foreach($arr as $v){ if($v===null){continue;} $s+=(float)$v; $n++; } return $n?round($s/$n,1):0; }

function kpi_meta_zona(PDO $pdo){
    $usaZ = kpi_tabla($pdo,'ZONAS') && kpi_col($pdo,'ZONAS','ZONAID') && kpi_col($pdo,'ZONAS','NOMBRE') && kpi_col($pdo,'TERCEROS','ZONA1') && kpi_col($pdo,'TERCEROS','ZONA2');
    $usaTxt = kpi_col($pdo,'TERCEROS','ZONA');
    $expr = "'SIN ZONA'"; $joins='';
    if($usaZ){
        $expr = "COALESCE(NULLIF(CASE WHEN z1.ZONAID IS NOT NULL THEN TRIM(CAST(z1.ZONAID AS VARCHAR(10))) || ' - ' || TRIM(COALESCE(z1.NOMBRE,'')) ELSE '' END,''),NULLIF(CASE WHEN z2.ZONAID IS NOT NULL THEN TRIM(CAST(z2.ZONAID AS VARCHAR(10))) || ' - ' || TRIM(COALESCE(z2.NOMBRE,'')) ELSE '' END,'')";
        if($usaTxt){ $expr .= ", NULLIF(TRIM(cli.ZONA),'')"; }
        $expr .= ", 'SIN ZONA')";
        $joins='LEFT JOIN ZONAS z1 ON z1.ZONAID = cli.ZONA1 LEFT JOIN ZONAS z2 ON z2.ZONAID = cli.ZONA2';
    } elseif($usaTxt){
        $expr = "COALESCE(NULLIF(TRIM(cli.ZONA),''), 'SIN ZONA')";
    }
    return array('expr'=>$expr,'joins'=>$joins);
}

function data_tiempo_real(PDO $pdo,$d,$h){
    if(!kpi_tabla($pdo,'SN_GUIAS')){ return array('cards'=>array(),'chart'=>array('title'=>'Guias por franja horaria','labels'=>array(),'series'=>array()),'tables'=>array(),'notes'=>array('No existe SN_GUIAS.')); }
    $hasDet=kpi_tabla($pdo,'SN_GUIAS_DETALLE');
    $hasEst=kpi_tabla($pdo,'SN_GUIAS_DETALLE_ESTADO');
    $hasChat=kpi_tabla($pdo,'SN_GUIAS_DETALLE_CHAT');
    $hasTer=kpi_tabla($pdo,'TERCEROS');

    $guias=(int)kpi_scalar($pdo,'SELECT COUNT(*) FROM SN_GUIAS g WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?',array($d,$h));
    $ruta=(int)kpi_scalar($pdo,"SELECT COUNT(*) FROM SN_GUIAS g WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? AND UPPER(TRIM(COALESCE(g.ESTADO_ACTUAL,'')))='EN_RUTA'",array($d,$h));
    $cond=(int)kpi_scalar($pdo,'SELECT COUNT(DISTINCT g.ID_CONDUCTOR) FROM SN_GUIAS g WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? AND g.ID_CONDUCTOR IS NOT NULL',array($d,$h));
    $rem=0; $ent=0; $pen=0; $cum=0; $ev=0;
    if($hasDet){
        $rem=(int)kpi_scalar($pdo,'SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?',array($d,$h));
        if($hasEst){
            $ent=(int)kpi_scalar($pdo,"SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? AND UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE')))='ENTREGADO'",array($d,$h));
        }
        $pen=max(0,$rem-$ent); $cum=$rem>0?round(($ent*100)/$rem,1):0;
    }
    if($hasChat && $hasDet){
        $ev=(int)kpi_scalar($pdo,'SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID INNER JOIN SN_GUIAS_DETALLE_CHAT c ON c.ID_GUIA=d.ID_GUIA AND c.KARDEX_ID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?',array($d,$h));
    }

    $rowsH=kpi_rows($pdo,'SELECT EXTRACT(HOUR FROM g.FECHA_GUIA) AS HORA, COUNT(*) AS TOTAL FROM SN_GUIAS g WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1 ORDER BY 1',array($d,$h));
    $labels=array(); $vals=array(); for($i=0;$i<24;$i++){ $labels[]=str_pad((string)$i,2,'0',STR_PAD_LEFT).':00'; $vals[]=0; }
    foreach($rowsH as $r){ $hr=(int)$r['HORA']; if($hr>=0&&$hr<=23){ $vals[$hr]=(int)$r['TOTAL']; } }

    $tc=array();
    if($hasDet){
        $sql='SELECT FIRST 15 '.($hasTer?"CAST(COALESCE(NULLIF(TRIM(t.NOMBRE),''),'SIN CONDUCTOR') AS VARCHAR(120))":"CAST('SIN CONDUCTOR' AS VARCHAR(120))").' AS CONDUCTOR, COUNT(d.ID) AS TOTAL_REMISIONES, '.($hasEst?"SUM(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE')))='ENTREGADO' THEN 1 ELSE 0 END)":'0').' AS TOTAL_ENTREGADAS FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID '.($hasTer?'LEFT JOIN TERCEROS t ON t.TERID=g.ID_CONDUCTOR ':'').($hasEst?'LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID ':'').'WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1 ORDER BY 2 DESC';
        foreach(kpi_rows($pdo,$sql,array($d,$h)) as $r){ $tot=(int)$r['TOTAL_REMISIONES']; $en=(int)$r['TOTAL_ENTREGADAS']; $tc[]=array('Conductor'=>kpi_txt($r['CONDUCTOR']),'Despachadas'=>$tot,'Entregadas'=>$en,'Pendientes'=>max(0,$tot-$en),'% Cumpl.'=>$tot>0?round(($en*100)/$tot,1):0); }
    }
    $ta=array();
    if($hasDet){
        $sql='SELECT FIRST 25 g.PREFIJO,g.CONSECUTIVO,g.FECHA_GUIA, CAST(COALESCE(NULLIF(TRIM(g.ESTADO_ACTUAL),\'\'),\'SIN_ESTADO\') AS VARCHAR(30)) AS ESTADO_GUIA, CAST(COALESCE(NULLIF(TRIM(t.NOMBRE),\'\'),\'SIN CONDUCTOR\') AS VARCHAR(120)) AS CONDUCTOR, COUNT(d.ID) AS TOTAL_REMISIONES, '.($hasEst?"SUM(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE'))) <> 'ENTREGADO' THEN 1 ELSE 0 END)":"COUNT(d.ID)").' AS TOTAL_PENDIENTES FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID LEFT JOIN TERCEROS t ON t.TERID=g.ID_CONDUCTOR '.($hasEst?'LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID ':'').'WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY g.PREFIJO,g.CONSECUTIVO,g.FECHA_GUIA,g.ESTADO_ACTUAL,t.NOMBRE HAVING '.($hasEst?"SUM(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE'))) <> 'ENTREGADO' THEN 1 ELSE 0 END) > 0":'COUNT(d.ID)>0').' ORDER BY g.FECHA_GUIA ASC';
        foreach(kpi_rows($pdo,$sql,array($d,$h)) as $r){
            $hrs=kpi_horas($r['FECHA_GUIA']); $pend=(int)$r['TOTAL_PENDIENTES']; $nivel='MEDIA';
            if($pend>=8 || $hrs>=24){ $nivel='CRITICA'; } elseif($pend>=4 || $hrs>=12 || kpi_estado_guia($r['ESTADO_GUIA'])==='EN_RUTA'){ $nivel='ALTA'; }
            $ta[]=array('Guia'=>kpi_num_guia($r['PREFIJO'],$r['CONSECUTIVO']),'Conductor'=>kpi_txt($r['CONDUCTOR']),'Estado'=>kpi_estado_guia($r['ESTADO_GUIA']),'Pendientes'=>$pend,'Horas abiertas'=>$hrs,'Nivel'=>$nivel);
        }
    }

    return array(
        'cards'=>array(
            array('label'=>'Guias en rango','value'=>$guias,'hint'=>'Control operativo del periodo'),
            array('label'=>'Remisiones despachadas','value'=>$rem,'hint'=>'Total en guias'),
            array('label'=>'Remisiones entregadas','value'=>$ent,'hint'=>'Con estado ENTREGADO'),
            array('label'=>'% cumplimiento','value'=>$cum,'hint'=>'Entregadas/despachadas'),
            array('label'=>'Guias en ruta','value'=>$ruta,'hint'=>'Seguimiento en curso'),
            array('label'=>'Conductores activos','value'=>$cond,'hint'=>'Con guias en rango'),
            array('label'=>'Pendientes','value'=>$pen,'hint'=>'Remisiones por cerrar'),
            array('label'=>'Evidencias POD','value'=>$ev,'hint'=>'Mensajes/evidencias de entrega')
        ),
        'chart'=>array('title'=>'Guias por franja horaria','labels'=>$labels,'series'=>array(array('label'=>'Guias','data'=>$vals,'color'=>'#1f5f8b'))),
        'tables'=>array('principal'=>array('title'=>'Cumplimiento por conductor','rows'=>$tc),'secundaria'=>array('title'=>'Alertas accionables','rows'=>$ta)),
        'notes'=>array('Tablero operativo en tiempo real con alertas de gestion.')
    );
}

function data_tiempos(PDO $pdo,$d,$h){
    if(!kpi_tabla($pdo,'SN_GUIAS')||!kpi_tabla($pdo,'SN_GUIAS_ESTADOS')){ return array('cards'=>array(),'chart'=>array('title'=>'Distribucion de tiempos','labels'=>array(),'series'=>array()),'tables'=>array(),'notes'=>array('No existen SN_GUIAS o SN_GUIAS_ESTADOS.')); }
    $hasTer=kpi_tabla($pdo,'TERCEROS');
    $sql='SELECT FIRST 700 g.PREFIJO,g.CONSECUTIVO,g.FECHA_GUIA,'.($hasTer?"CAST(COALESCE(NULLIF(TRIM(t.NOMBRE),''),'SIN CONDUCTOR') AS VARCHAR(120))":"CAST('SIN CONDUCTOR' AS VARCHAR(120))").' AS CONDUCTOR,MIN(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO,\'\'))) = \'EN_ALISTAMIENTO\' THEN e.FECHA_HORA_ESTADO ELSE NULL END) AS FECHA_ALI,MIN(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO,\'\'))) = \'EN_RUTA\' THEN e.FECHA_HORA_ESTADO ELSE NULL END) AS FECHA_RUTA,MIN(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO,\'\'))) IN (\'ENTREGADO\',\'FINALIZADO\') THEN e.FECHA_HORA_ESTADO ELSE NULL END) AS FECHA_FIN FROM SN_GUIAS g LEFT JOIN SN_GUIAS_ESTADOS e ON e.ID_GUIA=g.ID '.($hasTer?'LEFT JOIN TERCEROS t ON t.TERID=g.ID_CONDUCTOR ':'').'WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY g.PREFIJO,g.CONSECUTIVO,g.FECHA_GUIA,'.($hasTer?'t.NOMBRE':'1').' ORDER BY g.FECHA_GUIA DESC';
    $rows=kpi_rows($pdo,$sql,array($d,$h));
    $tabla=array(); $ali=array(); $rut=array(); $tot=array(); $b0=0;$b1=0;$b2=0;$b3=0;
    foreach($rows as $r){
        $m1=kpi_diff_min($r['FECHA_ALI'],$r['FECHA_RUTA']);
        $m2=kpi_diff_min($r['FECHA_RUTA'],$r['FECHA_FIN']);
        $m3=kpi_diff_min($r['FECHA_GUIA'],$r['FECHA_FIN']);
        if($m1!==null){$ali[]=$m1;} if($m2!==null){$rut[]=$m2;} if($m3!==null){$tot[]=$m3; if($m3<=120){$b0++;} elseif($m3<=240){$b1++;} elseif($m3<=480){$b2++;} else {$b3++;}}
        $tabla[]=array('Guia'=>kpi_num_guia($r['PREFIJO'],$r['CONSECUTIVO']),'Conductor'=>kpi_txt($r['CONDUCTOR']),'Ali->Ruta (min)'=>$m1===null?'-':$m1,'Ruta->Entrega (min)'=>$m2===null?'-':$m2,'Total (min)'=>$m3===null?'-':$m3);
    }
    usort($tabla,function($a,$b){ $ta=is_numeric($a['Total (min)'])?(int)$a['Total (min)']:-1; $tb=is_numeric($b['Total (min)'])?(int)$b['Total (min)']:-1; if($ta===$tb){return 0;} return $ta<$tb?1:-1; });
    return array(
        'cards'=>array(
            array('label'=>'Guias analizadas','value'=>count($rows),'hint'=>'Con historial en rango'),
            array('label'=>'Prom. Ali->Ruta (min)','value'=>kpi_prom($ali),'hint'=>'Tiempo de alistamiento'),
            array('label'=>'Prom. Ruta->Entrega (min)','value'=>kpi_prom($rut),'hint'=>'Tiempo de distribucion'),
            array('label'=>'Prom. Total (min)','value'=>kpi_prom($tot),'hint'=>'Desde guia hasta cierre')
        ),
        'chart'=>array('title'=>'Distribucion de tiempo total','labels'=>array('0-2h','2-4h','4-8h','>8h'),'series'=>array(array('label'=>'Guias','data'=>array($b0,$b1,$b2,$b3),'color'=>'#2d7fb8'))),
        'tables'=>array('principal'=>array('title'=>'Guias con mayor ciclo','rows'=>array_slice($tabla,0,120))),
        'notes'=>array('Medicion de tiempos entre estados de la guia.')
    );
}

function data_entregas(PDO $pdo,$d,$h){
    if(!kpi_tabla($pdo,'SN_GUIAS')||!kpi_tabla($pdo,'SN_GUIAS_DETALLE')){ return array('cards'=>array(),'chart'=>array('title'=>'Despachadas vs entregadas','labels'=>array(),'series'=>array()),'tables'=>array(),'notes'=>array('No existe SN_GUIAS_DETALLE.')); }
    $hasEst=kpi_tabla($pdo,'SN_GUIAS_DETALLE_ESTADO'); $hasTer=kpi_tabla($pdo,'TERCEROS');
    $sql='SELECT EXTRACT(YEAR FROM g.FECHA_GUIA) AS ANIO,EXTRACT(MONTH FROM g.FECHA_GUIA) AS MES,EXTRACT(DAY FROM g.FECHA_GUIA) AS DIA,COUNT(d.ID) AS TOTAL_DESP,'.($hasEst?"SUM(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE')))='ENTREGADO' THEN 1 ELSE 0 END)":'0').' AS TOTAL_ENT FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID '.($hasEst?'LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID ':'').'WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1,2,3 ORDER BY 1,2,3';
    $rows=kpi_rows($pdo,$sql,array($d,$h));
    $labels=array();$vd=array();$ve=array();$td=array();$totD=0;$totE=0;
    foreach($rows as $r){ $anio=(int)$r['ANIO'];$mes=(int)$r['MES'];$dia=(int)$r['DIA'];$des=(int)$r['TOTAL_DESP'];$ent=(int)$r['TOTAL_ENT'];$totD+=$des;$totE+=$ent; $lab=str_pad((string)$dia,2,'0',STR_PAD_LEFT).'/'.str_pad((string)$mes,2,'0',STR_PAD_LEFT); $labels[]=$lab; $vd[]=$des; $ve[]=$ent; $td[]=array('Fecha'=>$lab.'/'.$anio,'Despachadas'=>$des,'Entregadas'=>$ent,'Pendientes'=>max(0,$des-$ent),'% Cumpl.'=>$des>0?round(($ent*100)/$des,1):0); }
    $tc=array();
    $sqlc='SELECT FIRST 20 '.($hasTer?"CAST(COALESCE(NULLIF(TRIM(t.NOMBRE),''),'SIN CONDUCTOR') AS VARCHAR(120))":"CAST('SIN CONDUCTOR' AS VARCHAR(120))").' AS CONDUCTOR,COUNT(d.ID) AS TOTAL_DESP,'.($hasEst?"SUM(CASE WHEN UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE')))='ENTREGADO' THEN 1 ELSE 0 END)":'0').' AS TOTAL_ENT FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID '.($hasTer?'LEFT JOIN TERCEROS t ON t.TERID=g.ID_CONDUCTOR ':'').($hasEst?'LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID ':'').'WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1 ORDER BY 2 DESC';
    foreach(kpi_rows($pdo,$sqlc,array($d,$h)) as $r){ $des=(int)$r['TOTAL_DESP'];$ent=(int)$r['TOTAL_ENT']; $tc[]=array('Conductor'=>kpi_txt($r['CONDUCTOR']),'Despachadas'=>$des,'Entregadas'=>$ent,'Pendientes'=>max(0,$des-$ent),'% Cumpl.'=>$des>0?round(($ent*100)/$des,1):0); }
    return array('cards'=>array(array('label'=>'Despachadas','value'=>$totD,'hint'=>'Remisiones en guias'),array('label'=>'Entregadas','value'=>$totE,'hint'=>'Con cierre de entrega'),array('label'=>'Pendientes','value'=>max(0,$totD-$totE),'hint'=>'Por gestionar'),array('label'=>'% efectividad','value'=>$totD>0?round(($totE*100)/$totD,1):0,'hint'=>'Entregadas/despachadas')),'chart'=>array('title'=>'Despachadas vs entregadas por fecha','labels'=>$labels,'series'=>array(array('label'=>'Despachadas','data'=>$vd,'color'=>'#2f86bf'),array('label'=>'Entregadas','data'=>$ve,'color'=>'#2c9b5f'))),'tables'=>array('principal'=>array('title'=>'Cumplimiento diario','rows'=>$td),'secundaria'=>array('title'=>'Cumplimiento por conductor','rows'=>$tc)),'notes'=>array('KPI clave de entrega vs despacho.'));
}
function data_ruteo(PDO $pdo,$d,$h){
    if(!kpi_tabla($pdo,'SN_GUIAS')||!kpi_tabla($pdo,'SN_GUIAS_DETALLE')||!kpi_tabla($pdo,'KARDEX')){ return array('cards'=>array(),'chart'=>array('title'=>'Pendientes por zona','labels'=>array(),'series'=>array()),'tables'=>array(),'notes'=>array('Faltan tablas para ruteo.')); }
    $hasEst=kpi_tabla($pdo,'SN_GUIAS_DETALLE_ESTADO'); $hasKs=kpi_tabla($pdo,'KARDEXSELF'); $z=kpi_meta_zona($pdo);
    $sql='SELECT FIRST 400 g.PREFIJO,g.CONSECUTIVO,g.FECHA_GUIA,d.KARDEX_ID,k.CODPREFIJO AS REM_PREFIJO,k.NUMERO AS REM_NUMERO,CAST(COALESCE(NULLIF(TRIM(tc.NOMBRE),\'\'),\'SIN CONDUCTOR\') AS VARCHAR(120)) AS CONDUCTOR,CAST(COALESCE(NULLIF(TRIM(cli.NOMBRE),\'\'),\'SIN CLIENTE\') AS VARCHAR(120)) AS CLIENTE,CAST(COALESCE('.($hasKs?'ks.DIRECC1, ':'').'cli.DIRECC1,cli.DIRECC2,\'\') AS VARCHAR(180)) AS DIRECCION,CAST('.$z['expr'].' AS VARCHAR(120)) AS ZONA_TXT,'.($hasEst?"CAST(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE') AS VARCHAR(30))":"CAST('PENDIENTE' AS VARCHAR(30))").' AS ESTADO_ENTREGA,CAST(COALESCE(d.PESO,0) AS CHAR(30)) AS PESO_TXT,CAST(COALESCE(d.VALOR_BASE,0) AS CHAR(30)) AS BASE_TXT FROM SN_GUIAS_DETALLE d INNER JOIN SN_GUIAS g ON g.ID=d.ID_GUIA LEFT JOIN KARDEX k ON k.KARDEXID=d.KARDEX_ID LEFT JOIN TERCEROS cli ON cli.TERID=k.CLIENTE LEFT JOIN TERCEROS tc ON tc.TERID=g.ID_CONDUCTOR '.($hasKs?'LEFT JOIN KARDEXSELF ks ON ks.KARDEXID=d.KARDEX_ID ':'').($hasEst?'LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID ':'').$z['joins'].' WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? AND '.($hasEst?"COALESCE(UPPER(TRIM(e.ESTADO_ENTREGA)),'PENDIENTE') <> 'ENTREGADO'":'1=1').' ORDER BY ZONA_TXT,CONDUCTOR,g.FECHA_GUIA,d.KARDEX_ID';
    $rows=kpi_rows($pdo,$sql,array($d,$h));
    $tabla=array(); $rz=array(); $seq=array();
    foreach($rows as $r){
        $zona=kpi_txt($r['ZONA_TXT']); if($zona===''){ $zona='SIN ZONA'; }
        $con=kpi_txt($r['CONDUCTOR']); $key=strtoupper($con.'|'.$zona); if(!isset($seq[$key])){ $seq[$key]=1; } else { $seq[$key]++; }
        $hrs=kpi_horas($r['FECHA_GUIA']); $est=strtoupper(kpi_txt($r['ESTADO_ENTREGA'])); if($est===''){ $est='PENDIENTE'; }
        $pri='Normal'; if($hrs>=24 || $est==='NO_ENTREGADO'){ $pri='Alta'; } elseif($hrs>=8 || $est==='ENTREGA_PARCIAL'){ $pri='Media'; }
        $tabla[]=array('Guia'=>kpi_num_guia($r['PREFIJO'],$r['CONSECUTIVO']),'Remision'=>kpi_num_remision($r['REM_PREFIJO'],$r['REM_NUMERO']),'Conductor'=>$con,'Zona'=>$zona,'Cliente'=>kpi_txt($r['CLIENTE']),'Secuencia sugerida'=>$seq[$key],'Horas abiertas'=>$hrs,'Prioridad'=>$pri);
        if(!isset($rz[$zona])){ $rz[$zona]=array('Zona'=>$zona,'Pendientes'=>0,'Peso total'=>0.0,'$Base total'=>0.0); }
        $rz[$zona]['Pendientes']++; $rz[$zona]['Peso total']+=kpi_num($r['PESO_TXT']); $rz[$zona]['$Base total']+=kpi_num($r['BASE_TXT']);
    }
    $tz=array_values($rz); usort($tz,function($a,$b){ if($a['Pendientes']===$b['Pendientes']){return 0;} return $a['Pendientes']<$b['Pendientes']?1:-1; });
    $lz=array();$vz=array(); foreach($tz as $r){ $lz[]=$r['Zona']; $vz[]=(int)$r['Pendientes']; }
    $altas=0; foreach($tabla as $r){ if($r['Prioridad']==='Alta'){ $altas++; } }
    return array('cards'=>array(array('label'=>'Remisiones pendientes','value'=>count($rows),'hint'=>'Pendientes por gestionar'),array('label'=>'Zonas activas','value'=>count($tz),'hint'=>'Con carga operativa'),array('label'=>'Conductores en ruteo','value'=>count($seq),'hint'=>'Con zona asignada'),array('label'=>'Prioridad alta','value'=>$altas,'hint'=>'Atencion inmediata')),'chart'=>array('title'=>'Pendientes por zona','labels'=>$lz,'series'=>array(array('label'=>'Pendientes','data'=>$vz,'color'=>'#e08a2e'))),'tables'=>array('principal'=>array('title'=>'Secuencia sugerida por zona/conductor','rows'=>$tabla),'secundaria'=>array('title'=>'Carga consolidada por zona','rows'=>$tz)),'notes'=>array('Ruteo inteligente por zona, tiempo y prioridad.'));
}

function data_auditoria(PDO $pdo,$d,$h,$busq){
    $blocks=array(); $params=array();
    if(kpi_tabla($pdo,'SN_GUIAS_ESTADOS') && kpi_tabla($pdo,'SN_GUIAS')){ $blocks[]="SELECT g.PREFIJO,g.CONSECUTIVO,CAST(NULL AS INTEGER) AS KARDEX_ID,CAST(NULL AS VARCHAR(5)) AS REM_PREFIJO,CAST(NULL AS INTEGER) AS REM_NUMERO,e.FECHA_HORA_ESTADO AS FECHA_EVENTO,CAST('GUIA_ESTADO' AS VARCHAR(20)) AS TIPO_EVENTO,CAST(COALESCE(e.USUARIO,'') AS VARCHAR(60)) AS USUARIO,CAST(COALESCE(e.ESTADO,'') AS VARCHAR(120)) AS DETALLE,CAST(COALESCE(e.OBSERVACION,'') AS VARCHAR(250)) AS OBSERVACION FROM SN_GUIAS_ESTADOS e INNER JOIN SN_GUIAS g ON g.ID=e.ID_GUIA WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?"; $params[]=$d; $params[]=$h; }
    if(kpi_tabla($pdo,'SN_GUIAS_DETALLE_ESTADO') && kpi_tabla($pdo,'SN_GUIAS_DETALLE') && kpi_tabla($pdo,'SN_GUIAS') && kpi_tabla($pdo,'KARDEX')){ $blocks[]="SELECT g.PREFIJO,g.CONSECUTIVO,d.KARDEX_ID,k.CODPREFIJO AS REM_PREFIJO,k.NUMERO AS REM_NUMERO,de.FECHA_ESTADO AS FECHA_EVENTO,CAST('REMISION_ESTADO' AS VARCHAR(20)) AS TIPO_EVENTO,CAST(COALESCE(de.USUARIO,'') AS VARCHAR(60)) AS USUARIO,CAST(COALESCE(de.ESTADO_ENTREGA,'') AS VARCHAR(120)) AS DETALLE,CAST(COALESCE(de.OBSERVACION,'') AS VARCHAR(250)) AS OBSERVACION FROM SN_GUIAS_DETALLE_ESTADO de INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=de.ID_GUIA AND d.KARDEX_ID=de.KARDEX_ID INNER JOIN SN_GUIAS g ON g.ID=d.ID_GUIA LEFT JOIN KARDEX k ON k.KARDEXID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?"; $params[]=$d; $params[]=$h; }
    if(kpi_tabla($pdo,'SN_GUIAS_DETALLE_CHAT') && kpi_tabla($pdo,'SN_GUIAS_DETALLE') && kpi_tabla($pdo,'SN_GUIAS') && kpi_tabla($pdo,'KARDEX')){ $blocks[]="SELECT g.PREFIJO,g.CONSECUTIVO,d.KARDEX_ID,k.CODPREFIJO AS REM_PREFIJO,k.NUMERO AS REM_NUMERO,c.FECHA_MENSAJE AS FECHA_EVENTO,CAST('CHAT' AS VARCHAR(20)) AS TIPO_EVENTO,CAST(COALESCE(c.USUARIO,'') AS VARCHAR(60)) AS USUARIO,CAST(COALESCE(c.TIPO,'') AS VARCHAR(120)) AS DETALLE,CAST(COALESCE(c.MENSAJE,'') AS VARCHAR(250)) AS OBSERVACION FROM SN_GUIAS_DETALLE_CHAT c INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=c.ID_GUIA AND d.KARDEX_ID=c.KARDEX_ID INNER JOIN SN_GUIAS g ON g.ID=d.ID_GUIA LEFT JOIN KARDEX k ON k.KARDEXID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?"; $params[]=$d; $params[]=$h; }
    if(empty($blocks)){ return array('cards'=>array(),'chart'=>array('title'=>'Eventos por tipo','labels'=>array(),'series'=>array()),'tables'=>array(),'notes'=>array('No hay tablas de trazabilidad disponibles.')); }
    $sql='SELECT FIRST 600 * FROM ('.implode(' UNION ALL ',$blocks).') X';
    if($busq!==''){ $sql .= " WHERE (CAST(COALESCE(X.PREFIJO,'') AS VARCHAR(10)) CONTAINING ? OR CAST(COALESCE(X.CONSECUTIVO,0) AS VARCHAR(20)) CONTAINING ? OR CAST(COALESCE(X.REM_PREFIJO,'') AS VARCHAR(10)) CONTAINING ? OR CAST(COALESCE(X.REM_NUMERO,0) AS VARCHAR(20)) CONTAINING ? OR CAST(COALESCE(X.USUARIO,'') AS VARCHAR(100)) CONTAINING ? OR CAST(COALESCE(X.DETALLE,'') AS VARCHAR(250)) CONTAINING ? OR CAST(COALESCE(X.OBSERVACION,'') AS VARCHAR(250)) CONTAINING ?)"; for($i=0;$i<7;$i++){ $params[]=$busq; } }
    $sql .= ' ORDER BY X.FECHA_EVENTO DESC';
    $rows=kpi_rows($pdo,$sql,$params);
    $tabla=array(); $cnt=array(); $usr=array();
    foreach($rows as $r){ $tipo=strtoupper(kpi_txt($r['TIPO_EVENTO'])); if(!isset($cnt[$tipo])){ $cnt[$tipo]=0; } $cnt[$tipo]++; $u=strtoupper(kpi_txt($r['USUARIO'])); if($u!==''){ $usr[$u]=1; }
        $tabla[]=array('Fecha'=>kpi_txt($r['FECHA_EVENTO']),'Tipo'=>$tipo,'Guia'=>kpi_num_guia($r['PREFIJO'],$r['CONSECUTIVO']),'Remision'=>kpi_num_remision(isset($r['REM_PREFIJO'])?$r['REM_PREFIJO']:'',isset($r['REM_NUMERO'])?$r['REM_NUMERO']:''),'Usuario'=>kpi_txt($r['USUARIO']),'Detalle'=>kpi_txt($r['DETALLE']),'Observacion'=>kpi_txt($r['OBSERVACION'])); }
    return array('cards'=>array(array('label'=>'Eventos auditados','value'=>count($rows),'hint'=>'Historial unificado'),array('label'=>'Tipos de evento','value'=>count($cnt),'hint'=>'GUIA_ESTADO/REMISION_ESTADO/CHAT'),array('label'=>'Usuarios con actividad','value'=>count($usr),'hint'=>'Trazabilidad por usuario'),array('label'=>'Filtro busqueda','value'=>$busq===''?'N/A':$busq,'hint'=>'Aplicado en auditoria')),'chart'=>array('title'=>'Eventos por tipo','labels'=>array_keys($cnt),'series'=>array(array('label'=>'Eventos','data'=>array_values($cnt),'color'=>'#8e59c2'))),'tables'=>array('principal'=>array('title'=>'Trazabilidad detallada','rows'=>$tabla)),'notes'=>array('Auditoria y trazabilidad avanzada por guia/remision.'));
}

function data_historica(PDO $pdo,$d,$h){
    if(!kpi_tabla($pdo,'SN_GUIAS')||!kpi_tabla($pdo,'SN_GUIAS_DETALLE')){ return array('cards'=>array(),'chart'=>array('title'=>'Top zonas','labels'=>array(),'series'=>array()),'tables'=>array(),'notes'=>array('No existe SN_GUIAS_DETALLE.')); }
    $hasEst=kpi_tabla($pdo,'SN_GUIAS_DETALLE_ESTADO'); $z=kpi_meta_zona($pdo);
    $rz=kpi_rows($pdo,'SELECT FIRST 12 CAST('.$z['expr'].' AS VARCHAR(120)) AS ZONA, COUNT(d.ID) AS TOTAL FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID LEFT JOIN KARDEX k ON k.KARDEXID=d.KARDEX_ID LEFT JOIN TERCEROS cli ON cli.TERID=k.CLIENTE '.$z['joins'].' WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1 ORDER BY 2 DESC',array($d,$h));
    $rc=kpi_rows($pdo,'SELECT FIRST 12 CAST(COALESCE(NULLIF(TRIM(cli.NOMBRE),\'\'),\'SIN CLIENTE\') AS VARCHAR(120)) AS CLIENTE, COUNT(d.ID) AS TOTAL FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID LEFT JOIN KARDEX k ON k.KARDEXID=d.KARDEX_ID LEFT JOIN TERCEROS cli ON cli.TERID=k.CLIENTE WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1 ORDER BY 2 DESC',array($d,$h));
    $rCause=array(); if($hasEst){ $rCause=kpi_rows($pdo,"SELECT FIRST 10 CAST(COALESCE(NULLIF(TRIM(e.OBSERVACION),''),'SIN OBSERVACION') AS VARCHAR(180)) AS CAUSA, COUNT(*) AS TOTAL FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID INNER JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? AND UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE'))) IN ('NO_ENTREGADO','ENTREGA_PARCIAL') GROUP BY 1 ORDER BY 2 DESC",array($d,$h)); }
    $rPref=kpi_rows($pdo,"SELECT FIRST 12 CAST(COALESCE(NULLIF(TRIM(k.CODPREFIJO),''),'SIN PREFIJO') AS VARCHAR(20)) AS PREFIJO, COUNT(d.ID) AS TOTAL FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID LEFT JOIN KARDEX k ON k.KARDEXID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? GROUP BY 1 ORDER BY 2 DESC",array($d,$h));
    $totRem=(int)kpi_scalar($pdo,'SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=?',array($d,$h));
    $totEnt=0; if($hasEst){ $totEnt=(int)kpi_scalar($pdo,"SELECT COUNT(*) FROM SN_GUIAS g INNER JOIN SN_GUIAS_DETALLE d ON d.ID_GUIA=g.ID LEFT JOIN SN_GUIAS_DETALLE_ESTADO e ON e.ID_GUIA=d.ID_GUIA AND e.KARDEX_ID=d.KARDEX_ID WHERE g.FECHA_GUIA>=? AND g.FECHA_GUIA<=? AND UPPER(TRIM(COALESCE(e.ESTADO_ENTREGA,'PENDIENTE')))='ENTREGADO'",array($d,$h)); }
    $tZ=array(); $labels=array(); $vals=array(); foreach($rz as $r){ $zona=kpi_txt($r['ZONA']); $n=(int)$r['TOTAL']; $tZ[]=array('Zona'=>$zona,'Remisiones'=>$n); $labels[]=$zona; $vals[]=$n; }
    $tC=array(); foreach($rc as $r){ $tC[]=array('Cliente'=>kpi_txt($r['CLIENTE']),'Remisiones'=>(int)$r['TOTAL']); }
    $tCause=array(); foreach($rCause as $r){ $tCause[]=array('Causa no entrega'=>kpi_txt($r['CAUSA']),'Casos'=>(int)$r['TOTAL']); }
    $tPref=array(); foreach($rPref as $r){ $tPref[]=array('Codigo prefijo'=>kpi_txt($r['PREFIJO']),'Remisiones'=>(int)$r['TOTAL']); }
    $notes=array('Analitica historica por zona, cliente y codigos de remision.'); if(!empty($tCause)){ $notes[]='Incluye ranking de causas de no entrega.'; }
    return array('cards'=>array(array('label'=>'Remisiones analizadas','value'=>$totRem,'hint'=>'Base historica en rango'),array('label'=>'% entrega historica','value'=>$totRem>0?round(($totEnt*100)/$totRem,1):0,'hint'=>'Entregadas sobre total'),array('label'=>'Zonas con movimiento','value'=>count($tZ),'hint'=>'Ranking por volumen'),array('label'=>'Clientes activos','value'=>count($tC),'hint'=>'Top clientes')),'chart'=>array('title'=>'Top zonas por remisiones','labels'=>$labels,'series'=>array(array('label'=>'Remisiones','data'=>$vals,'color'=>'#2f9460'))),'tables'=>array('principal'=>array('title'=>'Ranking de zonas','rows'=>$tZ),'secundaria'=>array('title'=>'Top clientes','rows'=>$tC),'terciaria'=>array('title'=>'Causas no entrega','rows'=>$tCause),'cuarta'=>array('title'=>'Top codigos de prefijo','rows'=>$tPref)),'notes'=>$notes);
}

try{
    $pdo=kpi_pdo();
    $action=isset($_POST['action'])?trim((string)$_POST['action']):'';
    if($action!=='cargar_kpi'){ kpi_resp(false,array('message'=>'Accion no valida.')); }
    $sec=strtolower(trim((string)(isset($_POST['seccion'])?$_POST['seccion']:'tiempo_real')));
    $desde=kpi_fechain(isset($_POST['fecha_desde'])?$_POST['fecha_desde']:'',false);
    $hasta=kpi_fechain(isset($_POST['fecha_hasta'])?$_POST['fecha_hasta']:'',true);
    $busq=strtoupper(trim((string)(isset($_POST['busqueda'])?$_POST['busqueda']:'')));
    if($desde===null){ $desde=date('Y-m-01 00:00:00'); }
    if($hasta===null){ $hasta=date('Y-m-d 23:59:59'); }
    if(strlen($busq)>60){ $busq=substr($busq,0,60); }
    if($sec==='tiempo_real'){ $data=data_tiempo_real($pdo,$desde,$hasta); }
    elseif($sec==='tiempos_estados'){ $data=data_tiempos($pdo,$desde,$hasta); }
    elseif($sec==='entregas'){ $data=data_entregas($pdo,$desde,$hasta); }
    elseif($sec==='ruteo'){ $data=data_ruteo($pdo,$desde,$hasta); }
    elseif($sec==='auditoria'){ $data=data_auditoria($pdo,$desde,$hasta,$busq); }
    elseif($sec==='historica'){ $data=data_historica($pdo,$desde,$hasta); }
    else { kpi_resp(false,array('message'=>'Seccion KPI no valida.')); }
    kpi_resp(true,array('seccion'=>$sec,'filtros'=>array('fecha_desde'=>$desde,'fecha_hasta'=>$hasta,'busqueda'=>$busq),'data'=>$data));
}catch(PDOException $e){
    kpi_resp(false,array('message'=>'Error SQL KPI: '.$e->getMessage()));
}catch(Exception $e){
    kpi_resp(false,array('message'=>'Error KPI: '.$e->getMessage()));
}
