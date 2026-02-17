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
$v_nit = $_POST["cliente"];
$v_dias = $_POST["dias"];
$v_bd = $_POST["bd"];
$v_tel = $_POST["tel"];
if(isset($_POST["fia"])){
	$v_fiador = $_POST["fia"];
}else{
	$v_fiador = "";
}

$dias="";

if($v_dias <= 55){
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
	$vsql = "select t.nombre as nombre,t.direcc1 as direcc1,c.departamento as departamento,c.nombre as ciudad  from terceros as t left join ciudane as c on(t.ciudaneid=c.ciudaneid) where nittri='".$v_nit."'";
					
	if($co = $bd_desde->consulta($vsql))
	{
		while($r = ibase_fetch_object($co))
		{
			
			$v_cliente = $r->NOMBRE;
			$v_direccion = $r->DIRECC1;
			$v_ciudepa = $r->CIUDAD.' - '.$r->DEPARTAMENTO;
		}
	}
	
	
	if ($v_fiador!=''){
		$vsql = "select t.nombre as nombre,t.direcc1 as direcc1,c.departamento as departamento,c.nombre as ciudad  from terceros as t left join ciudane as c on(t.ciudaneid=c.ciudaneid) where nittri='".$v_fiador."'";
					
		if($co = $bd_desde->consulta($vsql))
		{
			while($r = ibase_fetch_object($co))
			{
				
				$v_nombrefiador= $r->NOMBRE;
				$v_direccionfiador = $r->DIRECC1;
				$v_ciudepafiador = $r->CIUDAD.' - '.$r->DEPARTAMENTO;
			}
		}
	}
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

	if($dias!="120"){
		$pdf = new FPDF('P','mm','letter');
		$pdf->SetMargins(30, 40, 30);
		$pdf->SetAutoPageBreak(true,10);
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
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
		$pdf->Cell(80,5,'Ref.: CARTERA CON '.$v_dias.' DIAS VENCIDOS',0,0,'L');
		$pdf->Ln(10);
		$pdf->MultiCell(155,5,utf8_decode("El registro de una buena hoja de vida crediticia que hable de su óptimo comportamiento de pago, resulta indispensable para el estudio y posterior acceso a beneficios del sistema financiero, tales como préstamos y referencias, los cuales se derivan en mejores condiciones de vida para usted y los suyos."),0,'J');
		$pdf->Ln(7);
		$pdf->MultiCell(155,5,utf8_decode("Teniendo en cuenta lo anterior, nos permitimos informarle que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, los cuales le invitamos a cancelar de manera inmediata, permitiendo de esta manera, la normalización de su crédito y su continuidad por el camino del éxito que le brinda MEYER MOTOS YAMAHA"),0,'J');
		$pdf->Ln(7);
		$pdf->MultiCell(155,5,utf8_decode("Recuerde que el no pago de su obligación a fecha de corte, generará el cobro de intereses moratorios y reportes inmediatos a las diferentes centrales de riesgo tales como DATACREDITO, BANCO DE LA MUJER, BANCAMIA, CIFIN, entre otros, reportes que naturalmente afectarán su imagen comercial y redundarán en sus futuras intenciones crediticias."),0,'J');
		$pdf->Ln(7);
		$pdf->MultiCell(155,5,utf8_decode("De esta manera le reiteramos el pago inmediato de su obligación, a través de los diferentes medios establecidos, de lo contrario agradecemos contactarse telefónicamente con nosotros en los teléfonos 5960066 ext. 117,118,142, ó personalmente en la Calle 7 N° 1-53 Barrio latino."),0,'J');
		$pdf->Ln(7);
		$pdf->Cell(80,5,utf8_decode("Si usted ya canceló, haga caso omiso a la presente comunicación."),0,0,'L');
		$pdf->Ln(10);
		$pdf->Cell(80,5,utf8_decode("Cordialmente,"),0,0,'L');
		$pdf->Ln(10);
		$pdf->Cell(80,5,utf8_decode("DEPARTAMENTO DE COBRANZAS"),0,0,'L');
		$pdf->Ln(5);
		$pdf->Cell(80,5,utf8_decode("MEYER MOTOS YAMAHA"),0,0,'L');
		$pdf->Ln(20);
		if ($v_fiador!=''){
			$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
			$pdf->Cell(25);
			$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
			$pdf->Ln(5);
			$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
		}else{
			$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
			$pdf->Cell(25);
			$pdf->Cell(50,5,'C.C. ',0,0,'L');
			$pdf->Ln(5);
			$pdf->Cell(50,5,'Direccion: ',0,0,'L');
		}
		
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
		$pdf = new FPDF('P','mm','letter');
		$pdf->SetMargins(30, 40, 30);
		$pdf->SetAutoPageBreak(true,10);
		$pdf->AddPage();
		$pdf->SetFont('Arial','',10);
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
		$pdf->Cell(80,5,utf8_decode('Ref.:CARTERA VENCIDA MÁS DE '.$v_dias.' DÍAS DE MORA'),0,0,'L');
		$pdf->Ln(10);
		$pdf->MultiCell(155,5,utf8_decode("Tomando en cuenta la renuencia de su parte a una negociación con el Área de cartera de MEYER MOTOS YAMAHA, se impulsará el cobro jurídico a su nombre y codeudores si tuviese, recuerde que todos los gastos de honorarios derivados del proceso jurídico y demás gastos procesales que correspondan correrán a su cargo y serán incluidos dentro del monto a cancelar."),0,'J');
		$pdf->Ln(7);
		$pdf->MultiCell(155,5,utf8_decode("De esta manera, nos permitimos reiterar que nuestros sistemas registran mora superior a ".$v_dias." días, en el pago de su obligación para con MEYER MOTOS YAMAHA, motivo por el cual le invitamos a cancelar de manera inmediata, evitando de esta manera la clasificación de su crédito en cartera JURÍDICA y la adjudicación de la misma a un Abogado de nuestra entidad, lo cual sólo le acarreará mayores perjuicios y sobrecostos para su patrimonio."),0,'J');
		$pdf->Ln(7);
		$pdf->MultiCell(155,5,utf8_decode("No obstante, lo anterior, le informamos que, de asistirle voluntad de pago, lo invitamos a contactarse con el teléfono 5960066 ext. 117-118- 142 o comparecer en la dirección Calle 7 N° 1-53 Barrio Latino, donde uno de nuestros funcionarios especializados le atenderá, permitiendo dar fin a la mora existente y a la normalización de su situación crediticia."),0,'J');
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
		if ($v_fiador!=''){
			$pdf->Cell(80,5,utf8_decode("Señor(a): ".$v_nombrefiador),0,0,'L');
			$pdf->Cell(25);
			$pdf->Cell(50,5,'C.C. '.$v_fiador,0,0,'L');
			$pdf->Ln(5);
			$pdf->Cell(50,5,'Direccion: '.$v_direccionfiador,0,0,'L');
		}else{
			$pdf->Cell(80,5,utf8_decode("Señor(a): "),0,0,'L');
			$pdf->Cell(25);
			$pdf->Cell(50,5,'C.C. ',0,0,'L');
			$pdf->Ln(5);
			$pdf->Cell(50,5,'Direccion: ',0,0,'L');
		}
		
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
	$pdf->Output('F','pdfs/ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf');
	
	
	
	$v_pdf   = 'pdfs/ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf';

	if(file_exists($v_pdf))
	{
		$params=array(
		'token' => 'kpg0xr5v5ae5v8dt',
		'to' => '+573125627758',
		'filename' => 'ESTADO_CUENTA_'.utf8_decode($v_nit).'.pdf',
		'document' => 'http://meyermotos.facilwebnube.com/evento_estadocuenta/'.$v_pdf,
		'caption' => 'document caption'
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
		  $v_accion="cURL Error #:" . $err;
		} else {
		  $v_accion= json_decode($response,true)["message"];
		}
	}
	
	echo json_encode(array(

		"accion"=>$v_accion,
	));
?>