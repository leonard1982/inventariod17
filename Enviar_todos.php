<?php
date_default_timezone_set('America/Bogota');
setlocale(LC_ALL, 'es_ES');
include_once 'fpdf/fpdf.php';
include_once 'php/baseDeDatos.php';

$v_cliente ="";
$v_direccion="";
$v_ciudepa="";
$v_nombrefiador ="";
$v_direccionfiador="";
$v_ciudepafiador="";
$v_bd = $_POST["bd"];
$v_inicio = $_POST["inicio"];
$v_fin = $_POST["fin"];
$v_prefijos = $_POST["prefijos"];
$dias="";

$v_limite = 29;

/*if($v_dias <= 55){
	$dias="30";
}
if($v_dias >=56 and $v_dias <= 85){
	$dias="60";
}
if($v_dias >=86 and $v_dias <= 115){
	$dias="90";
}
if($v_dias >=116 ){
	$dias="120";
}*/

if(!file_exists("pdfs/".date('m')."")){
	@mkdir("pdfs/".date("m"), 0700); 
}

$v_mes=date("F");

switch($v_mes){
	case "January":
		$v_mes="Enero";
	break;
	case "February":
		$v_mes="Febrero";
	break;
	case "March":
		$v_mes="Marzo";
	break;
	case "April":
		$v_mes="Abril";
	break;
	case "May":
		$v_mes="Mayo";
	break;
	case "June":
		$v_mes="Junio";
	break;
	case "July":
		$v_mes="Julio";
	break;
	case "August":
		$v_mes="Agosto";
	break;
	case "September":
		$v_mes="Septiembre";
	break;
	case "Octuber":
		$v_mes="Octubre";
	break;
	case "November":
		$v_mes="Noviembre";
	break;
	case "December":
		$v_mes="Diciembre";
	break;
}	

	function txtentities($html){
		$trans = get_html_translation_table(HTML_ENTITIES);
		$trans = array_flip($trans);
		return strtr($html, $trans);
	}





$ip         = '127.0.0.1';
$vbd_seleccionada = $_POST['bd'];
$vsi    = true;

		
if(file_exists($vbd_seleccionada))
{	
	$bd_desde = new dbFirebird($vbd_seleccionada,$ip);
}
else
{
	echo "<h5 style='color:red;'>NO EXISTE LA BASE DE DATOS SELECCIONADA</h5>";
	$vsi = false;
}

if($vsi)
{
	
	$vsql = "SELECT CANTIDAD FROM SN_CONFIGCARTERA";
		
	if($co = $bd_desde->consulta($vsql))
	{	
			
		while($r = ibase_fetch_object($co))
		{
			
			$v_limite = $r->CANTIDAD;
			
		}	
	}
	
	
	
	$vsql = "SELECT   d.docuid, d.Codprefijo||d.Numero NroObligacion , d.Fecha, t.nittri DocCliente, t.Nombre NomCliente, t.Telef1 TelCliente,
                      f1.Nittri DocFiador1, f1.Nombre NomFiador1, f1.telef1 TelFiador1,
                      f2.Nittri DocFiador2, f2.Nombre NomFiador2, f2.telef1 TelFiador2,
             	        (SELECT min(oo.fecvence) FROM dedocum oo WHERE oo.docuid = d.docuid and  oo.salval > 0 ) Fecha_Limite,
             	        (SELECT max(CAST(CURRENT_TIMESTAMP - oo.fecvence AS INTEGER)) FROM dedocum oo WHERE oo.docuid = d.docuid and  oo.salval > 0 ) Dias_Vencido,
                      (SELECT sum(oo.salval) FROM dedocum oo WHERE oo.docuid = d.docuid and  oo.salval > 0 AND oo.fecvence < '".date("Y-m-d")."' ) Saldo_Vencido,t.direcc1 as direcc1
						FROM   (((Documento d Left Join Terceros f1 on d.fiador1 = f1.terid) Left Join Terceros f2 on d.fiador2 = f2.terid ) Inner Join Terceros t on d.terid = t.terid )
						WHERE   d.fecasent is not null and d.codcomp = 'FV' and Codprefijo in (".$v_prefijos.") and d.saldo > 0 AND 
                      (SELECT max(CAST(CURRENT_TIMESTAMP - oo.fecvence AS INTEGER)) FROM dedocum oo WHERE oo.docuid = d.docuid and  oo.salval > 0 ) >= '".$v_inicio."' and 
                      (SELECT max(CAST(CURRENT_TIMESTAMP - oo.fecvence AS INTEGER)) FROM dedocum oo WHERE oo.docuid = d.docuid and  oo.salval > 0 ) <= '".$v_fin."'
					  and T.Telef1 NOT LIKE '%JUR%' and T.Telef1 NOT LIKE '%PREJ%' and T.Telef1 NOT LIKE '%INSOLVEN%' and T.Telef1 NOT LIKE '%JM%'
					  and T.Telef1 NOT LIKE '%JW%' and T.Telef1 NOT LIKE '%FALLECIO%'
					  ";
				
	if($co = $bd_desde->consulta($vsql))
	{
		while($r = ibase_fetch_object($co))
		{
			$v_cliente = $r->NOMCLIENTE;
			$v_nit = $r->DOCCLIENTE;
			$v_fiador = $r->DOCFIADOR1;
			$v_fiador2 = $r->DOCFIADOR2;
			$v_dias = $r->DIAS_VENCIDO;
			$v_obligacion = $r->NROOBLIGACION;
			
			$vtelcli = $r->TELCLIENTE;
			$vtelcli = str_replace("-","",$vtelcli);
			$vtelcli = str_replace("POLICIA","",$vtelcli);
			$vtelcli = str_replace("ESPOSA","",$vtelcli);
			$vtelcli = str_replace("MILITAR","",$vtelcli);
			$vtelcli = str_replace("COMIDAS RAPIDAS","",$vtelcli);
			$vtelcli = str_replace("EMPLEADO PRESENCIA SAS CALI","",$vtelcli);
			$vtelcli = str_replace("PENSIONADA","",$vtelcli);
			$vtelcli = str_replace("RAAA","",$vtelcli);
			$vtelcli = str_replace("ESTUDIANTE","",$vtelcli);
			$vtelcli = str_replace("EMPLEADA ACTISALUD","",$vtelcli);
			$vtelcli = str_replace("EMPRES VIGILANCIA LIBERTADORA","",$vtelcli);
			$vtelcli = str_replace("PENSIO POLICIA","",$vtelcli);
			$vtelcli = str_replace("PENSIO","",$vtelcli);
			$vtelcli = str_replace("COMERCIANTE","",$vtelcli);
			$vtelcli = str_replace("POLICIA NACIONAL","",$vtelcli);
			$vtelcli = str_replace("EMPLEADO RED SERV GROUP","",$vtelcli);
			$vtelcli = str_replace("EMPLEADA YYY BUSINESS","",$vtelcli);
			$vtelcli = str_replace("TEN CRISTIAN","",$vtelcli);
			$vtelcli = str_replace("BILLETERA","",$vtelcli);
			$vtelcli = str_replace("PROGRE MEYER","",$vtelcli);
			$vtelcli = str_replace("TESORERIA","",$vtelcli);
			$vtelcli = str_replace("AUTOMARCOL","",$vtelcli);
			$vtelcli = str_replace("PENSIONADO","",$vtelcli);
			
			$vtelcli = trim($vtelcli);
			$vbuscar = strpos($vtelcli, " ");
			if ($vbuscar === false)
			{
				
			}
			else
			{
				$vtelpartes = explode(" ",$vtelcli);
				$vtelcli    = "";
				
				if(isset($vtelpartes[0]))
				{
					if(strlen($vtelpartes[0])==10)
					{
						$vtelcli = $vtelpartes[0];
					}
				}
				
				if(isset($vtelpartes[1]))
				{
					if(strlen($vtelpartes[1])==10)
					{
						$vbuscar2 = strpos($vtelcli, "-");
						if ($vbuscar2 === false)
						{
							if(!empty($vtelcli))
							{
								$vtelcli .= "-".$vtelpartes[1];
							}
							else
							{
								$vtelcli .= $vtelpartes[1];
							}
						}
						else
						{
							if(!empty($vtelcli))
							{
								$vtelcli .= "-".$vtelpartes[1];
							}
							else
							{
								$vtelcli .= $vtelpartes[1];
							}
						}
					}
				}
				
				if(isset($vtelpartes[2]))
				{
					if(strlen($vtelpartes[2])==10)
					{
						$vbuscar3 = strpos($vtelcli, "-");
						if ($vbuscar3 === false)
						{
							if(!empty($vtelcli))
							{
								$vtelcli .= "-".$vtelpartes[2];
							}
							else
							{
								$vtelcli .= $vtelpartes[2];
							}
						}
						else
						{
							if(!empty($vtelcli))
							{
								$vtelcli .= "-".$vtelpartes[2];
							}
							else
							{
								$vtelcli .= $vtelpartes[2];
							}
						}
					}
				}
			}
			
			$vtelfia = $r->TELFIADOR1;
			$vtelfia = str_replace("-","",$vtelfia);
			$vtelfia = str_replace("POLICIA","",$vtelfia);
			$vtelfia = str_replace("ESPOSA","",$vtelfia);
			$vtelfia = str_replace("MILITAR","",$vtelfia);
			$vtelfia = str_replace("COMIDAS RAPIDAS","",$vtelfia);
			$vtelfia = str_replace("EMPLEADO PRESENCIA SAS CALI","",$vtelfia);
			$vtelfia = str_replace("PENSIONADA","",$vtelfia);
			$vtelfia = str_replace("RAAA","",$vtelfia);
			$vtelfia = str_replace("ESTUDIANTE","",$vtelfia);
			$vtelfia = str_replace("EMPLEADA ACTISALUD","",$vtelfia);
			$vtelfia = str_replace("EMPRES VIGILANCIA LIBERTADORA","",$vtelfia);
			$vtelfia = str_replace("PENSIO POLICIA","",$vtelfia);
			$vtelfia = str_replace("PENSIO","",$vtelfia);
			$vtelfia = str_replace("COMERCIANTE","",$vtelfia);
			$vtelfia = str_replace("POLICIA NACIONAL","",$vtelfia);
			$vtelfia = str_replace("EMPLEADO RED SERV GROUP","",$vtelfia);
			$vtelfia = str_replace("EMPLEADA YYY BUSINESS","",$vtelfia);
			$vtelfia = str_replace("TEN CRISTIAN","",$vtelfia);
			$vtelfia = str_replace("BILLETERA","",$vtelfia);
			$vtelfia = str_replace("PROGRE MEYER","",$vtelfia);
			$vtelfia = str_replace("TESORERIA","",$vtelfia);
			$vtelfia = str_replace("AUTOMARCOL","",$vtelfia);
			$vtelfia = str_replace("PENSIONADO","",$vtelfia);
			
			$vtelfia = trim($vtelfia);
			$vbuscar = strpos($vtelfia, " ");
			if ($vbuscar === false)
			{
				
			}
			else
			{
				$vtelpartes = explode(" ",$vtelfia);
				$vtelfia    = "";
				
				if(isset($vtelpartes[0]))
				{
					if(strlen($vtelpartes[0])==10)
					{
						$vtelfia = $vtelpartes[0];
					}
				}
				
				if(isset($vtelpartes[1]))
				{
					if(strlen($vtelpartes[1])==10)
					{
						$vbuscar2 = strpos($vtelfia, "-");
						if ($vbuscar2 === false)
						{
							if(!empty($vtelfia))
							{
								$vtelfia .= "-".$vtelpartes[1];
							}
							else
							{
								$vtelfia .= $vtelpartes[1];
							}
						}
						else
						{
							if(!empty($vtelfia))
							{
								$vtelfia .= "-".$vtelpartes[1];
							}
							else
							{
								$vtelfia .= $vtelpartes[1];
							}
						}
					}
				}
				
				if(isset($vtelpartes[2]))
				{
					if(strlen($vtelpartes[2])==10)
					{
						$vbuscar3 = strpos($vtelfia, "-");
						if ($vbuscar3 === false)
						{
							if(!empty($vtelfia))
							{
								$vtelfia .= "-".$vtelpartes[2];
							}
							else
							{
								$vtelfia .= $vtelpartes[2];
							}
						}
						else
						{
							if(!empty($vtelfia))
							{
								$vtelfia .= "-".$vtelpartes[2];
							}
							else
							{
								$vtelfia .= $vtelpartes[2];
							}
						}
					}
				}
			}
			
			
			$vtelfia2 = $r->TELFIADOR2;
			$vtelfia2 = str_replace("-","",$vtelfia2);
			$vtelfia2 = str_replace("POLICIA","",$vtelfia2);
			$vtelfia2 = str_replace("ESPOSA","",$vtelfia2);
			$vtelfia2 = str_replace("MILITAR","",$vtelfia2);
			$vtelfia2 = str_replace("COMIDAS RAPIDAS","",$vtelfia2);
			$vtelfia2 = str_replace("EMPLEADO PRESENCIA SAS CALI","",$vtelfia2);
			$vtelfia2 = str_replace("PENSIONADA","",$vtelfia2);
			$vtelfia2 = str_replace("RAAA","",$vtelfia2);
			$vtelfia2 = str_replace("ESTUDIANTE","",$vtelfia2);
			$vtelfia2 = str_replace("EMPLEADA ACTISALUD","",$vtelfia2);
			$vtelfia2 = str_replace("EMPRES VIGILANCIA LIBERTADORA","",$vtelfia2);
			$vtelfia2 = str_replace("PENSIO POLICIA","",$vtelfia2);
			$vtelfia2 = str_replace("PENSIO","",$vtelfia2);
			$vtelfia2 = str_replace("COMERCIANTE","",$vtelfia2);
			$vtelfia2 = str_replace("POLICIA NACIONAL","",$vtelfia2);
			$vtelfia2 = str_replace("EMPLEADO RED SERV GROUP","",$vtelfia2);
			$vtelfia2 = str_replace("EMPLEADA YYY BUSINESS","",$vtelfia2);
			$vtelfia2 = str_replace("TEN CRISTIAN","",$vtelfia2);
			$vtelfia2 = str_replace("BILLETERA","",$vtelfia2);
			$vtelfia2 = str_replace("PROGRE MEYER","",$vtelfia2);
			$vtelfia2 = str_replace("TESORERIA","",$vtelfia2);
			$vtelfia2 = str_replace("AUTOMARCOL","",$vtelfia2);
			$vtelfia2 = str_replace("PENSIONADO","",$vtelfia2);
			
			$vtelfia2 = trim($vtelfia2);
			$vbuscar = strpos($vtelfia2, " ");
			if ($vbuscar === false)
			{
				
			}
			else
			{
				$vtelpartes = explode(" ",$vtelfia2);
				$vtelfia2    = "";
				
				if(isset($vtelpartes[0]))
				{
					if(strlen($vtelpartes[0])==10)
					{
						$vtelfia2 = $vtelpartes[0];
					}
				}
				
				if(isset($vtelpartes[1]))
				{
					if(strlen($vtelpartes[1])==10)
					{
						$vbuscar2 = strpos($vtelfia2, "-");
						if ($vbuscar2 === false)
						{
							if(!empty($vtelfia2))
							{
								$vtelfia2 .= "-".$vtelpartes[1];
							}
							else
							{
								$vtelfia2 .= $vtelpartes[1];
							}
						}
						else
						{
							if(!empty($vtelfia2))
							{
								$vtelfia2 .= "-".$vtelpartes[1];
							}
							else
							{
								$vtelfia2 .= $vtelpartes[1];
							}
						}
					}
				}
				
				if(isset($vtelpartes[2]))
				{
					if(strlen($vtelpartes[2])==10)
					{
						$vbuscar3 = strpos($vtelfia2, "-");
						if ($vbuscar3 === false)
						{
							if(!empty($vtelfia2))
							{
								$vtelfia2 .= "-".$vtelpartes[2];
							}
							else
							{
								$vtelfia2 .= $vtelpartes[2];
							}
						}
						else
						{
							if(!empty($vtelfia2))
							{
								$vtelfia2 .= "-".$vtelpartes[2];
							}
							else
							{
								$vtelfia2 .= $vtelpartes[2];
							}
						}
					}
				}
			}
			
			
			$vsql1 = "select t.nombre as nombre,t.direcc1 as direcc1,c.departamento as departamento,c.nombre as ciudad  from terceros as t left join ciudane as c on(t.ciudaneid=c.ciudaneid) where nittri='".$v_nit."'";
					
			if($co1 = $bd_desde->consulta($vsql1))
			{
				while($r1 = ibase_fetch_object($co1))
				{
					
					$v_direccion = $r1->DIRECC1;
					$v_ciudepa = $r1->CIUDAD.' - '.$r1->DEPARTAMENTO;
				}
			}
			
			
			if ($v_fiador!=''){
				$vsql2 = "select t.nombre as nombre,t.direcc1 as direcc1,c.departamento as departamento,c.nombre as ciudad,t.telef1 as telefono from terceros as t left join ciudane as c on(t.ciudaneid=c.ciudaneid) where nittri='".$v_fiador."'";
							
				if($co2 = $bd_desde->consulta($vsql2))
				{
					while($r2 = ibase_fetch_object($co2))
					{
						
						$v_nombrefiador= $r2->NOMBRE;
						$v_direccionfiador = $r2->DIRECC1;
						$v_ciudepafiador = $r2->CIUDAD.' - '.$r2->DEPARTAMENTO;
						$v_telefonofiador = $r2->TELEFONO;
					}
				}
			}
			
			if ($v_fiador2!=''){
				$vsql3 = "select t.nombre as nombre,t.direcc1 as direcc1,c.departamento as departamento,c.nombre as ciudad,t.telef1 as telefono from terceros as t left join ciudane as c on(t.ciudaneid=c.ciudaneid) where nittri='".$v_fiador."'";
							
				if($co3 = $bd_desde->consulta($vsql3))
				{
					while($r3 = ibase_fetch_object($co3))
					{
						
						$v_nombrefiador2= $r3->NOMBRE;
						$v_direccionfiador2 = $r3->DIRECC1;
						$v_ciudepafiador2 = $r3->CIUDAD.' - '.$r3->DEPARTAMENTO;
						$v_telefonofiador2 = $r3->TELEFONO;
					}
				}
			}
			
			
			if($v_dias<=$v_limite){
				$pdf = new FPDF('P','mm','letter');
				$pdf->SetMargins(30, 40, 30);
				$pdf->SetAutoPageBreak(true,10);
				$pdf->AddPage();
				$pdf->SetFont('Arial','',10);
				if(substr($v_obligacion,0,2)=='Y5'){
					$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
					$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
				}else{
					$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
					$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
				}
				$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
				$pdf->Ln(15);
				$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
				$pdf->Ln(7);
				$pdf->SetFont('Arial','B',10);
				$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
				$pdf->Cell(25);
				$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
				$pdf->Ln(6);
				$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
				$pdf->Ln(6);
				$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
				$pdf->Ln(10);
				$pdf->SetFont('Arial','',10);
				$pdf->Cell(80,5,utf8_decode("Asunto: Notificación previa al reporte en centrales de riesgo."),0,0,'L');
				$pdf->Ln(10);
				$pdf->MultiCell(155,5,utf8_decode("Con el fin de darle cumplimiento a la Ley de Habeas Data, LEY ESTATUTARIA 1266 DE 2008 (Artículo 12.) Te informamos que tu obligación N° ".$v_obligacion.", con Meyer Motos Yamaha presenta ".$v_dias." días en mora; realiza el pago en las fechas de corte y evita incremento de intereses."),0,'J');
				$pdf->Ln(7);
				$msj=utf8_decode("Si pasados 20 días calendario continua la mora el dato será reportado de manera negativa a las centrales de información. Si ya cancelaste tu obligación, haz caso omiso a esta comunicación y envía tu comprobante de pago para actualizar inmediatamente tu estado de cuenta y atender tus dudas puedes comunicarte al chat");
				$pdf->MultiCell(155,5,"".$pdf->WriteHTML('<p>'.$msj.' <a href="https://wa.link/92rnsj" target="_blank">+57 3045888750</a></p>'),0,'J');
				$pdf->Ln(46);
				/*$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
				$pdf->Ln(7);
				$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118,142, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
				$pdf->Ln(7);
				$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L'); 
				$pdf->Ln(10); */
				$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
				$pdf->Ln(10);
				$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
				$pdf->Ln(5);
				$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
				$pdf->Ln(20);
				$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
				$pdf->Cell(25);
				$pdf->Cell(50,5,'C.C. ',0,0,'L');
				$pdf->Ln(5);
				$pdf->Cell(50,5,'Direccion: ',0,0,'L');
				
				
				$pdf->AddPage();
				$pdf->SetFont('Arial','B',18);
				$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
				$pdf->SetFont('Arial','',10);
				$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
				$pdf->Ln(130);
				$pdf->SetFont('Arial','B',18);
				$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
				$pdf->SetFont('Arial','',10);
				$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');
			}else{
				
				if($v_dias>=30 and $v_dias <=50){
					$pdf = new FPDF('P','mm','letter');
					$pdf->SetMargins(30, 40, 30);
					$pdf->SetAutoPageBreak(true,10);
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					if(substr($v_obligacion,0,2)=='Y5'){
						$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
						$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
					}else{
						$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
						$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
					}	
					$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
					$pdf->Ln(15);
					$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
					$pdf->Ln(7);
					$pdf->SetFont('Arial','B',10);
					$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
					$pdf->Ln(10);
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 30 DIAS DE MORA',0,0,'L');
					$pdf->Ln(10);
					$pdf->MultiCell(155,5,utf8_decode("El registro de una buena hoja de vida crediticia que hable de su óptimo comportamiento de pago, resulta indispensable para el estudio y posterior acceso a beneficios del sistema financiero, tales como préstamos y referencias, los cuales se derivan en mejores condiciones de vida para usted y los suyos."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta lo anterior, nos permitimos informarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, los cuales le invitamos a cancelar de manera inmediata, permitiendo de esta manera, la normalización de su crédito y su continuidad por el camino del éxito que le brinda MEYER MOTOS YAMAHA"),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118, WhatsApp 3045888750, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
					$pdf->Ln(7);
					$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
					$pdf->Ln(20);
					$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. ',0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(50,5,'Direccion: ',0,0,'L');
					
					
					$pdf->AddPage();
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
					$pdf->Ln(130);
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
				}
				
				
				if($v_dias>=51 and $v_dias <=80){
					$pdf = new FPDF('P','mm','letter');
					$pdf->SetMargins(30, 40, 30);
					$pdf->SetAutoPageBreak(true,10);
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					if(substr($v_obligacion,0,2)=='Y5'){
						$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
						$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
					}else{
						$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
						$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
					}
					$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
					$pdf->Ln(15);
					$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
					$pdf->Ln(7);
					$pdf->SetFont('Arial','B',10);
					$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
					$pdf->Ln(10);
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 60 DIAS DE MORA',0,0,'L');
					$pdf->Ln(10);
					$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta que usted ha hecho caso omiso a las comunicaciones anteriores entregadas en su dirección de domicilio, teléfono o correo electrócnico, las cuales pretendían notificarla de la mora existente en sus obligaciones para con MEYER MOTOS YAMAHA, nos permitimos informarle que nos hemos visto obligados a reportarla negativamente ante las diferentes centrales de riesgo, tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros"),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera prejurídica y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual solo le acarreará mayores perjuicios y sobrecostos para su patrimonio"),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("No obstante lo anterior, le informamos que de asistirle voluntad de pago, agradecemos contactarse con el teléfono 5960066 Ext 117,118, WhatsApp 3045888750, ó comparecer en la dirección Calle 7 N* 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de sus situación crediticia."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su factura, la hará acreedora de los beneficios de la ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
					$pdf->Ln(7);
					$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
					$pdf->Ln(20);
					$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. ',0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(50,5,'Direccion: ',0,0,'L');
					
					
					$pdf->AddPage();
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
					$pdf->Ln(130);
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
				}
				
				
				if($v_dias>=81 and $v_dias <=110){
					$pdf = new FPDF('P','mm','letter');
					$pdf->SetMargins(30, 40, 30);
					$pdf->SetAutoPageBreak(true,10);
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					if(substr($v_obligacion,0,2)=='Y5'){
						$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
						$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
					}else{
						$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
						$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
					}
					$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
					$pdf->Ln(15);
					$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
					$pdf->Ln(7);
					$pdf->SetFont('Arial','B',10);
					$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
					$pdf->Ln(10);
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 90 DIAS DE MORA',0,0,'L');
					$pdf->Ln(10);
					$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta que usted ha hecho caso omiso a las comunicaciones anteriores enviadas a través de los medios de contacto suministradas por el Area de cartera de MEYER MOTOS YAMAHA, las cuales pretendían notificarle de la mora existente en sus obligaciones y llegar a una conciliación de pago, damos por notificado el incumplimiento al contrato y posterior inicio un proceso Coactivo a su nombre y codeudor si aplica."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("Nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos en aras de encontrar una alternativa que evite el avance de dicho proceso a presentarse en nuestras instalaciones ubicadas en calle 7 1-53 Barrio Latino o al teléfono 5960066 Ext. 117,118, WhatsApp 3045888750, para una negociación de manera inmediata donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de su situación crediticia."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su mora, la hará acreedora de los beneficios de la Ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
					$pdf->Ln(7);
					/*$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su factura, la hará acreedora de los beneficios de la ley de “habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
					$pdf->Ln(7);*/
					$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
					$pdf->Ln(20);
					$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. ',0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(50,5,'Direccion: ',0,0,'L');
				
				
					$pdf->AddPage();
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
					$pdf->Ln(130);
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
				}
				
				
				if($v_dias>=111){
					$pdf = new FPDF('P','mm','letter');
					$pdf->SetMargins(30, 40, 30);
					$pdf->SetAutoPageBreak(true,10);
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					if(substr($v_obligacion,0,2)=='Y5'){
						$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
						$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
					}else{
						$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
						$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
					}
					$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
					$pdf->Ln(15);
					$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
					$pdf->Ln(7);
					$pdf->SetFont('Arial','B',10);
					$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
					$pdf->Ln(10);
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 120 DIAS DE MORA',0,0,'L');
					$pdf->Ln(10);
					$pdf->MultiCell(155,5,utf8_decode("Tomando en cuenta la renuencia de su parte a una negociación con el Área de cartera de MEYER MOTOS YAMAHA, se impulsará el cobro jurídico a su nombre y codeudores si tuviese, recuerde que todos los gastos de honorarios derivados del proceso jurídico y demás gastos procesales que correspondan correrán a su cargo y serán incluidos dentro del monto a cancelar."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera JURÍDICA y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual sólo le acarreará mayores perjuicios y sobrecostos para su patrimonio."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("No obstante, lo anterior, le informamos que, de asistirle voluntad de pago, lo invitamos a contactarse con el teléfono 5960066 ext. 117,118, WhatsApp 3045888750 o comparecer en la dirección Calle 7 N* 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin ala mora existente y a la normalización de su situación crediticia."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su mora, la hará acreedora de los beneficios de la Ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
					$pdf->Ln(7);
					$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
					$pdf->Ln(20);
					$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. ',0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(50,5,'Direccion: ',0,0,'L');
					
					
					$pdf->AddPage();
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
					$pdf->Ln(130);
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
				}
				
			}
			


			$pdf->Output('F','pdfs/'.date("m").'/ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf');
			
			
			if($v_fiador!=''){
				if($v_dias<=$v_limite){
					$pdf = new FPDF('P','mm','letter');
					$pdf->SetMargins(30, 40, 30);
					$pdf->SetAutoPageBreak(true,10);
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					if(substr($v_obligacion,0,2)=='Y5'){
						$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
						$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
					}else{
						$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
						$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
					}
					$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
					$pdf->Ln(15);
					$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
					$pdf->Ln(7);
					$pdf->SetFont('Arial','B',10);
					$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
					$pdf->Ln(10);
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(80,5,utf8_decode("Asunto: Notificación previa al reporte en centrales de riesgo."),0,0,'L');
					$pdf->Ln(10);
					$pdf->MultiCell(155,5,utf8_decode("Con el fin de darle cumplimiento a la Ley de Habeas Data, LEY ESTATUTARIA 1266 DE 2008 (Artículo 12.) Te informamos que tu obligación N° ".$v_obligacion.", con Meyer Motos Yamaha presenta ".$v_dias." días en mora; realiza el pago en las fechas de corte y evita incremento de intereses."),0,'J');
					$pdf->Ln(7);
					$msj=utf8_decode("Si pasados 20 días calendario continua la mora el dato será reportado de manera negativa a las centrales de información. Si ya cancelaste tu obligación, haz caso omiso a esta comunicación y envía tu comprobante de pago para actualizar inmediatamente tu estado de cuenta y atender tus dudas puedes comunicarte al chat");
					$pdf->MultiCell(155,5,"".$pdf->WriteHTML('<p>'.$msj.' <a href="https://wa.link/92rnsj" target="_blank">+57 3045888750</a></p>'),0,'J');
					$pdf->Ln(46);
					/*$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118,142, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
					$pdf->Ln(7);
					$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L'); 
					$pdf->Ln(10); */
					$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
					$pdf->Ln(20);
					$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
					
					
					$pdf->AddPage();
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
					$pdf->Ln(130);
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');
				}else{
					
					if($v_dias>=30 and $v_dias <=50){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}	
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 30 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("El registro de una buena hoja de vida crediticia que hable de su óptimo comportamiento de pago, resulta indispensable para el estudio y posterior acceso a beneficios del sistema financiero, tales como préstamos y referencias, los cuales se derivan en mejores condiciones de vida para usted y los suyos."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta lo anterior, nos permitimos informarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, los cuales le invitamos a cancelar de manera inmediata, permitiendo de esta manera, la normalización de su crédito y su continuidad por el camino del éxito que le brinda MEYER MOTOS YAMAHA"),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118, WhatsApp 3045888750, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
						$pdf->Ln(7);
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
						
						
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
					
					if($v_dias>=51 and $v_dias <=80){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 60 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta que usted ha hecho caso omiso a las comunicaciones anteriores entregadas en su dirección de domicilio, teléfono o correo electrócnico, las cuales pretendían notificarla de la mora existente en sus obligaciones para con MEYER MOTOS YAMAHA, nos permitimos informarle que nos hemos visto obligados a reportarla negativamente ante las diferentes centrales de riesgo, tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros"),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera prejurídica y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual solo le acarreará mayores perjuicios y sobrecostos para su patrimonio"),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("No obstante lo anterior, le informamos que de asistirle voluntad de pago, agradecemos contactarse con el teléfono 5960066 Ext 117,118, WhatsApp 3045888750, ó comparecer en la dirección Calle 7 N* 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de sus situación crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su factura, la hará acreedora de los beneficios de la ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
						
						
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
					
					if($v_dias>=81 and $v_dias <=110){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 90 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta que usted ha hecho caso omiso a las comunicaciones anteriores enviadas a través de los medios de contacto suministradas por el Area de cartera de MEYER MOTOS YAMAHA, las cuales pretendían notificarle de la mora existente en sus obligaciones y llegar a una conciliación de pago, damos por notificado el incumplimiento al contrato y posterior inicio un proceso Coactivo a su nombre y codeudor si aplica."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos en aras de encontrar una alternativa que evite el avance de dicho proceso a presentarse en nuestras instalaciones ubicadas en calle 7 1-53 Barrio Latino o al teléfono 5960066 Ext. 117,118, WhatsApp 3045888750, para una negociación de manera inmediata donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de su situación crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su mora, la hará acreedora de los beneficios de la Ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);
						/*$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su factura, la hará acreedora de los beneficios de la ley de “habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);*/
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
					
					
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
					
					if($v_dias>=111){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 120 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("Tomando en cuenta la renuencia de su parte a una negociación con el Área de cartera de MEYER MOTOS YAMAHA, se impulsará el cobro jurídico a su nombre y codeudores si tuviese, recuerde que todos los gastos de honorarios derivados del proceso jurídico y demás gastos procesales que correspondan correrán a su cargo y serán incluidos dentro del monto a cancelar."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera JURÍDICA y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual sólo le acarreará mayores perjuicios y sobrecostos para su patrimonio."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("No obstante, lo anterior, le informamos que, de asistirle voluntad de pago, lo invitamos a contactarse con el teléfono 5960066 ext. 117,118, WhatsApp 3045888750 o comparecer en la dirección Calle 7 N* 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin ala mora existente y a la normalización de su situación crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su mora, la hará acreedora de los beneficios de la Ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
						
						
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
				}
			}
			
			$pdf->Output('F','pdfs/'.date("m").'/ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador).'.pdf');
			
			if($v_fiador2!=''){
				if($v_dias<=$v_limite){
					$pdf = new FPDF('P','mm','letter');
					$pdf->SetMargins(30, 40, 30);
					$pdf->SetAutoPageBreak(true,10);
					$pdf->AddPage();
					$pdf->SetFont('Arial','',10);
					if(substr($v_obligacion,0,2)=='Y5'){
						$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
						$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
					}else{
						$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
						$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
					}
					$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
					$pdf->Ln(15);
					$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
					$pdf->Ln(7);
					$pdf->SetFont('Arial','B',10);
					$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
					$pdf->Ln(6);
					$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
					$pdf->Ln(10);
					$pdf->SetFont('Arial','',10);
					$pdf->Cell(80,5,utf8_decode("Asunto: Notificación previa al reporte en centrales de riesgo."),0,0,'L');
					$pdf->Ln(10);
					$pdf->MultiCell(155,5,utf8_decode("Con el fin de darle cumplimiento a la Ley de Habeas Data, LEY ESTATUTARIA 1266 DE 2008 (Artículo 12.) Te informamos que tu obligación N° ".$v_obligacion.", con Meyer Motos Yamaha presenta ".$v_dias." días en mora; realiza el pago en las fechas de corte y evita incremento de intereses."),0,'J');
					$pdf->Ln(7);
					$msj=utf8_decode("Si pasados 20 días calendario continua la mora el dato será reportado de manera negativa a las centrales de información. Si ya cancelaste tu obligación, haz caso omiso a esta comunicación y envía tu comprobante de pago para actualizar inmediatamente tu estado de cuenta y atender tus dudas puedes comunicarte al chat");
					$pdf->MultiCell(155,5,"".$pdf->WriteHTML('<p>'.$msj.' <a href="https://wa.link/92rnsj" target="_blank">+57 3045888750</a></p>'),0,'J');
					$pdf->Ln(46);
					/*$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
					$pdf->Ln(7);
					$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118,142, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
					$pdf->Ln(7);
					$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L'); 
					$pdf->Ln(10); */
					$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
					$pdf->Ln(10);
					$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
					$pdf->Ln(20);
					$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador2),0,0,'L');
					$pdf->Cell(25);
					$pdf->Cell(50,5,'C.C. '.$v_fiador2,0,0,'L');
					$pdf->Ln(5);
					$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador2,0,0,'L');
					
					
					$pdf->AddPage();
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
					$pdf->Ln(130);
					$pdf->SetFont('Arial','B',18);
					$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
					$pdf->SetFont('Arial','',10);
					$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');
				}else{
					
					if($v_dias>=30 and $v_dias <=50){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}	
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 30 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("El registro de una buena hoja de vida crediticia que hable de su óptimo comportamiento de pago, resulta indispensable para el estudio y posterior acceso a beneficios del sistema financiero, tales como préstamos y referencias, los cuales se derivan en mejores condiciones de vida para usted y los suyos."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta lo anterior, nos permitimos informarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, los cuales le invitamos a cancelar de manera inmediata, permitiendo de esta manera, la normalización de su crédito y su continuidad por el camino del éxito que le brinda MEYER MOTOS YAMAHA"),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118, WhatsApp 3045888750, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
						$pdf->Ln(7);
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador2),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador2,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador2,0,0,'L');
						
						
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
					
					if($v_dias>=51 and $v_dias <=80){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 60 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta que usted ha hecho caso omiso a las comunicaciones anteriores entregadas en su dirección de domicilio, teléfono o correo electrócnico, las cuales pretendían notificarla de la mora existente en sus obligaciones para con MEYER MOTOS YAMAHA, nos permitimos informarle que nos hemos visto obligados a reportarla negativamente ante las diferentes centrales de riesgo, tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros"),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera prejurídica y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual solo le acarreará mayores perjuicios y sobrecostos para su patrimonio"),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("No obstante lo anterior, le informamos que de asistirle voluntad de pago, agradecemos contactarse con el teléfono 5960066 Ext 117,118, WhatsApp 3045888750, ó comparecer en la dirección Calle 7 N* 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de sus situación crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su factura, la hará acreedora de los beneficios de la ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador2),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador2,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador2,0,0,'L');
						
						
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
					
					if($v_dias>=81 and $v_dias <=110){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 90 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta que usted ha hecho caso omiso a las comunicaciones anteriores enviadas a través de los medios de contacto suministradas por el Area de cartera de MEYER MOTOS YAMAHA, las cuales pretendían notificarle de la mora existente en sus obligaciones y llegar a una conciliación de pago, damos por notificado el incumplimiento al contrato y posterior inicio un proceso Coactivo a su nombre y codeudor si aplica."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos en aras de encontrar una alternativa que evite el avance de dicho proceso a presentarse en nuestras instalaciones ubicadas en calle 7 1-53 Barrio Latino o al teléfono 5960066 Ext. 117,118, WhatsApp 3045888750, para una negociación de manera inmediata donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de su situación crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su mora, la hará acreedora de los beneficios de la Ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);
						/*$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su factura, la hará acreedora de los beneficios de la ley de “habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);*/
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador2),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador2,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador2,0,0,'L');
					
					
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
					
					if($v_dias>=111){
						$pdf = new FPDF('P','mm','letter');
						$pdf->SetMargins(30, 40, 30);
						$pdf->SetAutoPageBreak(true,10);
						$pdf->AddPage();
						$pdf->SetFont('Arial','',10);
						if(substr($v_obligacion,0,2)=='Y5'){
							$pdf->Image('imagenes/meyer_motos.jpg', 10, 5,'JPG' );
							$pdf->Image('imagenes/musical.jpg', 150, 10,'JPG' );
						}else{
							$pdf->Image('imagenes/comercial_meyer.jpg', 10, 10,'JPG' );
							$pdf->Image('imagenes/yamaha.jpg', 160, 10,'JPG' );
						}
						$pdf->Cell(80,5,'San Jose de '.utf8_decode("Cúcuta,").date("j").' de '.$v_mes.' de '.date("Y") ,0,0,'L');
						$pdf->Ln(15);
						$pdf->Cell(80,5,utf8_decode("Señor(a):"),0,0,'L');
						$pdf->Ln(7);
						$pdf->SetFont('Arial','B',10);
						$pdf->Cell(80,5,utf8_decode($v_cliente),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.utf8_decode($v_nit),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_direccion),0,0,'L');
						$pdf->Ln(6);
						$pdf->Cell(50,5,utf8_decode($v_ciudepa),0,0,'L');
						$pdf->Ln(10);
						$pdf->SetFont('Arial','',10);
						$pdf->Cell(80,5,'Ref.: CARTERA VENCIDA MAS DE 120 DIAS DE MORA',0,0,'L');
						$pdf->Ln(10);
						$pdf->MultiCell(155,5,utf8_decode("Tomando en cuenta la renuencia de su parte a una negociación con el Área de cartera de MEYER MOTOS YAMAHA, se impulsará el cobro jurídico a su nombre y codeudores si tuviese, recuerde que todos los gastos de honorarios derivados del proceso jurídico y demás gastos procesales que correspondan correrán a su cargo y serán incluidos dentro del monto a cancelar."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera JURÍDICA y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual sólo le acarreará mayores perjuicios y sobrecostos para su patrimonio."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("No obstante, lo anterior, le informamos que, de asistirle voluntad de pago, lo invitamos a contactarse con el teléfono 5960066 ext. 117,118, WhatsApp 3045888750 o comparecer en la dirección Calle 7 N* 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin ala mora existente y a la normalización de su situación crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->MultiCell(155,5,utf8_decode("Recuerde que el pago inmediato de su mora, la hará acreedora de los beneficios de la Ley de 'habeas data', mediante los cuales usted obtendrá una buena calificación ante las centrales de riesgo mencionadas, recuperando su vida crediticia."),0,'J');
						$pdf->Ln(7);
						$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
						$pdf->Ln(10);
						$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
						$pdf->Ln(20);
						$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador2),0,0,'L');
						$pdf->Cell(25);
						$pdf->Cell(50,5,'C.C. '.$v_fiador2,0,0,'L');
						$pdf->Ln(5);
						$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador2,0,0,'L');
						
						
						$pdf->AddPage();
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'MEDIOS DE PAGO',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/medios_pagos_yamaha.png',45 , 50);
						$pdf->Ln(130);
						$pdf->SetFont('Arial','B',18);
						$pdf->Cell(155,5,'PAGA EN LINEA',0,0,'C');
						$pdf->SetFont('Arial','',10);
						$pdf->Image('imagenes/paga_linea_yamaha.png',7 , 180,0,0,'PNG','https://www.zonapagos.com/t_ccialmeyermotos/pagos.asp');	
					}
					
				}
			}
			
			$pdf->Output('F','pdfs/'.date("m").'/ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador2).'.pdf');
			
			
			$v_pdf   = 'pdfs/'.date("m").'/ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf';
			$v_pdf1   = 'pdfs/'.date("m").'/ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador).'.pdf';
			$v_pdf2   = 'pdfs/'.date("m").'/ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador2).'.pdf';

			if(file_exists($v_pdf))
			{
				
				$tel = explode("-",$vtelcli);
				
				if(strlen($tel[0])==10){
					$params=array(
					'token' => 'kpg0xr5v5ae5v8dt',
					'to' => '+57'.$tel[0],
					//'to' => '+573125627758',
					'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf',
					'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf,
					'caption' => 'Buen dia sr '.utf8_encode($v_cliente).' le saludamos de meyer motos yamaha notificando su crédito en mora, obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
					);
					$curl = curl_init();
					curl_setopt_array($curl, array(
					  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
					  CURLOPT_RETURNTRANSFER => true,
					  CURLOPT_ENCODING => "",
					  CURLOPT_MAXREDIRS => 10,
					  CURLOPT_TIMEOUT => 30,
					  CURLOPT_SSL_VERIFYHOST => 0,
					  CURLOPT_SSL_VERIFYPEER => 0,
					  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					  CURLOPT_CUSTOMREQUEST => "POST",
					  CURLOPT_POSTFIELDS => http_build_query($params),
					  CURLOPT_HTTPHEADER => array(
						"content-type: application/x-www-form-urlencoded"
					  ),
					));

					$response = curl_exec($curl);
					$err = curl_error($curl);

					curl_close($curl);

					if ($err) {
					  //$v_accion= "cURL Error #:" . $err;
					} else {
					  $v_accion= json_decode($response,true)["message"];
					}
				}
				
				if(isset($tel[1]) and strlen($tel[1])==10){
					$params=array(
					'token' => 'kpg0xr5v5ae5v8dt',
					'to' => '+57'.$tel[1],
					//'to' => '+573125627758',
					'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf',
					'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf,
					'caption' => 'Buen dia sr '.utf8_encode($v_cliente).' le saludamos de meyer motos yamaha notificando su crédito en mora, obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
					);
					$curl = curl_init();
					curl_setopt_array($curl, array(
					  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
					  CURLOPT_RETURNTRANSFER => true,
					  CURLOPT_ENCODING => "",
					  CURLOPT_MAXREDIRS => 10,
					  CURLOPT_TIMEOUT => 30,
					  CURLOPT_SSL_VERIFYHOST => 0,
					  CURLOPT_SSL_VERIFYPEER => 0,
					  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					  CURLOPT_CUSTOMREQUEST => "POST",
					  CURLOPT_POSTFIELDS => http_build_query($params),
					  CURLOPT_HTTPHEADER => array(
						"content-type: application/x-www-form-urlencoded"
					  ),
					));

					$response = curl_exec($curl);
					$err = curl_error($curl);

					curl_close($curl);

					if ($err) {
					  //$v_accion= "cURL Error #:" . $err;
					} else {
					  $v_accion= json_decode($response,true)["message"];
					}
				}

				if(isset($tel[2]) and strlen($tel[2])==10){
					$params=array(
					'token' => 'kpg0xr5v5ae5v8dt',
					'to' => '+57'.$tel[2],
					//'to' => '+573125627758',
					'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf',
					'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf,
					'caption' => 'Buen dia sr '.utf8_encode($v_cliente).' le saludamos de meyer motos yamaha notificando su crédito en mora, obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
					);
					$curl = curl_init();
					curl_setopt_array($curl, array(
					  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
					  CURLOPT_RETURNTRANSFER => true,
					  CURLOPT_ENCODING => "",
					  CURLOPT_MAXREDIRS => 10,
					  CURLOPT_TIMEOUT => 30,
					  CURLOPT_SSL_VERIFYHOST => 0,
					  CURLOPT_SSL_VERIFYPEER => 0,
					  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
					  CURLOPT_CUSTOMREQUEST => "POST",
					  CURLOPT_POSTFIELDS => http_build_query($params),
					  CURLOPT_HTTPHEADER => array(
						"content-type: application/x-www-form-urlencoded"
					  ),
					));

					$response = curl_exec($curl);
					$err = curl_error($curl);

					curl_close($curl);

					if ($err) {
					  //$v_accion= "cURL Error #:" . $err;
					} else {
					  $v_accion= json_decode($response,true)["message"];
					}
				}
				
				
				
				
				if ($v_fiador!=''){
					
					
					$tel = explode("-",$vtelfia);
				
					if(strlen($tel[0])==10){
						$params=array(
						'token' => 'kpg0xr5v5ae5v8dt',
						'to' => '+57'.$tel[0],
						//'to' => '+573125627758',
						'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador).'.pdf',
						'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf1,
						'caption' => 'Buen dia sr '.utf8_encode($v_nombrefiador).' le saludamos de meyer motos yamaha en calidad de codeudor notificando el crédito en mora a nombre del señor '.utf8_encode($v_cliente).' obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
						);
						$curl = curl_init();
						curl_setopt_array($curl, array(
						  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_ENCODING => "",
						  CURLOPT_MAXREDIRS => 10,
						  CURLOPT_TIMEOUT => 30,
						  CURLOPT_SSL_VERIFYHOST => 0,
						  CURLOPT_SSL_VERIFYPEER => 0,
						  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POSTFIELDS => http_build_query($params),
						  CURLOPT_HTTPHEADER => array(
							"content-type: application/x-www-form-urlencoded"
						  ),
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);

						curl_close($curl);

						if ($err) {
						  //$v_accion= "cURL Error #:" . $err;
						} else {
						  $v_accion= json_decode($response,true)["message"];
						}
					}
					
					if(isset($tel[1]) and strlen($tel[1])==10){
						$params=array(
						'token' => 'kpg0xr5v5ae5v8dt',
						'to' => '+57'.$tel[1],
						//'to' => '+573125627758',
						'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador).'.pdf',
						'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf1,
						'caption' => 'Buen dia sr '.utf8_encode($v_nombrefiador).' le saludamos de meyer motos yamaha en calidad de codeudor notificando el crédito en mora a nombre del señor '.utf8_encode($v_cliente).' obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
						);
						$curl = curl_init();
						curl_setopt_array($curl, array(
						  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_ENCODING => "",
						  CURLOPT_MAXREDIRS => 10,
						  CURLOPT_TIMEOUT => 30,
						  CURLOPT_SSL_VERIFYHOST => 0,
						  CURLOPT_SSL_VERIFYPEER => 0,
						  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POSTFIELDS => http_build_query($params),
						  CURLOPT_HTTPHEADER => array(
							"content-type: application/x-www-form-urlencoded"
						  ),
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);

						curl_close($curl);

						if ($err) {
						  //$v_accion= "cURL Error #:" . $err;
						} else {
						  $v_accion= json_decode($response,true)["message"];
						}
					}

					if(isset($tel[2]) and strlen($tel[2])==10){
						$params=array(
						'token' => 'kpg0xr5v5ae5v8dt',
						'to' => '+57'.$tel[2],
						//'to' => '+573125627758',
						'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador).'.pdf',
						'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf1,
						'caption' => 'Buen dia sr '.utf8_encode($v_nombrefiador).' le saludamos de meyer motos yamaha en calidad de codeudor notificando el crédito en mora a nombre del señor '.utf8_encode($v_cliente).' obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
						);
						$curl = curl_init();
						curl_setopt_array($curl, array(
						  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_ENCODING => "",
						  CURLOPT_MAXREDIRS => 10,
						  CURLOPT_TIMEOUT => 30,
						  CURLOPT_SSL_VERIFYHOST => 0,
						  CURLOPT_SSL_VERIFYPEER => 0,
						  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POSTFIELDS => http_build_query($params),
						  CURLOPT_HTTPHEADER => array(
							"content-type: application/x-www-form-urlencoded"
						  ),
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);

						curl_close($curl);

						if ($err) {
						  //$v_accion= "cURL Error #:" . $err;
						} else {
						  $v_accion= json_decode($response,true)["message"];
						}
					}
					
				} 
				
				if ($v_fiador2!=''){
					
					$tel = explode("-",$vtelfia2);
				
					if(strlen($tel[0])==10){
						$params=array(
						'token' => 'kpg0xr5v5ae5v8dt',
						'to' => '+57'.$tel[0],
						//'to' => '+573125627758',
						'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador2).'.pdf',
						'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf2,
						'caption' => 'Buen dia sr '.utf8_encode($v_nombrefiador).' le saludamos de meyer motos yamaha en calidad de codeudor notificando el crédito en mora a nombre del señor '.utf8_encode($v_cliente).' obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
						);
						$curl = curl_init();
						curl_setopt_array($curl, array(
						  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_ENCODING => "",
						  CURLOPT_MAXREDIRS => 10,
						  CURLOPT_TIMEOUT => 30,
						  CURLOPT_SSL_VERIFYHOST => 0,
						  CURLOPT_SSL_VERIFYPEER => 0,
						  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POSTFIELDS => http_build_query($params),
						  CURLOPT_HTTPHEADER => array(
							"content-type: application/x-www-form-urlencoded"
						  ),
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);

						curl_close($curl);

						if ($err) {
						  //$v_accion= "cURL Error #:" . $err;
						} else {
						  $v_accion= json_decode($response,true)["message"];
						}
					}
					
					if(isset($tel[1]) and strlen($tel[1])==10){
						$params=array(
						'token' => 'kpg0xr5v5ae5v8dt',
						'to' => '+57'.$tel[1],
						//'to' => '+573125627758',
						'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador2).'.pdf',
						'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf2,
						'caption' => 'Buen dia sr '.utf8_encode($v_nombrefiador).' le saludamos de meyer motos yamaha en calidad de codeudor notificando el crédito en mora a nombre del señor '.utf8_encode($v_cliente).' obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
						);
						$curl = curl_init();
						curl_setopt_array($curl, array(
						  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_ENCODING => "",
						  CURLOPT_MAXREDIRS => 10,
						  CURLOPT_TIMEOUT => 30,
						  CURLOPT_SSL_VERIFYHOST => 0,
						  CURLOPT_SSL_VERIFYPEER => 0,
						  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POSTFIELDS => http_build_query($params),
						  CURLOPT_HTTPHEADER => array(
							"content-type: application/x-www-form-urlencoded"
						  ),
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);

						curl_close($curl);

						if ($err) {
						  //$v_accion= "cURL Error #:" . $err;
						} else {
						  $v_accion= json_decode($response,true)["message"];
						}
					}

					if(isset($tel[2]) and strlen($tel[2])==10){
						$params=array(
						'token' => 'kpg0xr5v5ae5v8dt',
						'to' => '+57'.$tel[2],
						//'to' => '+573125627758',
						'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'_'.utf8_decode($v_fiador2).'.pdf',
						'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf2,
						'caption' => 'Buen dia sr '.utf8_encode($v_nombrefiador).' le saludamos de meyer motos yamaha en calidad de codeudor notificando el crédito en mora a nombre del señor '.utf8_encode($v_cliente).' obligación # '.utf8_decode($v_obligacion).' quedamos atentos a la realización del pago'
						);
						$curl = curl_init();
						curl_setopt_array($curl, array(
						  CURLOPT_URL => "https://api.ultramsg.com/instance42671/messages/document",
						  CURLOPT_RETURNTRANSFER => true,
						  CURLOPT_ENCODING => "",
						  CURLOPT_MAXREDIRS => 10,
						  CURLOPT_TIMEOUT => 30,
						  CURLOPT_SSL_VERIFYHOST => 0,
						  CURLOPT_SSL_VERIFYPEER => 0,
						  CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
						  CURLOPT_CUSTOMREQUEST => "POST",
						  CURLOPT_POSTFIELDS => http_build_query($params),
						  CURLOPT_HTTPHEADER => array(
							"content-type: application/x-www-form-urlencoded"
						  ),
						));

						$response = curl_exec($curl);
						$err = curl_error($curl);

						curl_close($curl);

						if ($err) {
						  //$v_accion= "cURL Error #:" . $err;
						} else {
						  $v_accion= json_decode($response,true)["message"];
						}
					}
					
				}
				
			
				//$v_accion="ok";
			}
			
		}
	}		
	
	
}


echo json_encode(array(

		"accion"=>$v_accion,
		"sql"=>$vsql
	));	


?>