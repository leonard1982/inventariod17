<?php
function checkPuerto($dominio,$puerto){
    
    $starttime = microtime(true);
    $file      = @fsockopen ($dominio, $puerto, $errno, $errstr, 10);
    $stoptime  = microtime(true);
    $status    = 0;
  
    if (!$file){    
        $status = -1;  // Sitio caído
    } else {
        fclose($file);
        $status = ($stoptime - $starttime) * 1000;
        $status = floor($status);
    }
     
    if ($status <> -1) {
        return true;
    } else {
        return false;
    }
     
}

function resolverRutaFirebird($rutaCruda){

    $ruta = trim((string)$rutaCruda, " \t\n\r\0\x0B\"'");

    if ($ruta === '') {
        return '';
    }

    // Normaliza separadores para Windows cuando vienen mezclados o duplicados
    if (preg_match('/^([A-Za-z]:|:)([\\\\\\/].*)$/', $ruta, $matches)) {
        $prefijo = $matches[1];
        $sufijo  = preg_replace('/[\\\\\\/]+/', '\\\\', $matches[2]);
        $ruta    = $prefijo . $sufijo;
    }

    // Ruta completa con unidad
    if (preg_match('/^[A-Za-z]:[\\\\\\/]/', $ruta)) {
        return $ruta;
    }

    // Ruta sin unidad, por ejemplo :\DATOS TNS\...
    if (preg_match('/^:[\\\\\\/]/', $ruta)) {
        $sufijoRuta = substr($ruta, 1);
        $unidadProyecto = '';

        if (preg_match('/^([A-Za-z]):/', __DIR__, $matches)) {
            $unidadProyecto = strtoupper($matches[1]) . ':';
            $candidata = $unidadProyecto . $sufijoRuta;
            if (file_exists($candidata)) {
                return $candidata;
            }
        }

        foreach (range('A', 'Z') as $unidad) {
            $candidata = $unidad . ':' . $sufijoRuta;
            if (file_exists($candidata)) {
                return $candidata;
            }
        }

        if ($unidadProyecto !== '') {
            return $unidadProyecto . $sufijoRuta;
        }
    }

    return $ruta;
}

class dbMysql{

	private $conexion;
	private $servidor;
	private $usuario;
	private $password;
	private $puerto;
	private $db;
	private $resultado;
	
	public function __construct($servidor,$usuario,$password,$db,$puerto=3306){

		$this->servidor=$servidor;
		$this->usuario=$usuario;
		$this->password=$password;
		$this->db=$db;
		$this->puerto=$puerto;
		$this->conexion = mysqli_connect("localhost", $this->usuario, $this->password,$this->db);
		$this->conexion->set_charset('utf8');
	}
	
	public function consulta($sql){
		
		return $this->resultado = mysqli_query($this->conexion,$sql);

	}	
	
	public function __destruct(){

		$this->conexion->close();
	}
}

class dbFirebirdPDO{

	private $conexion;
	private $servidor;
	private $db;
	private $resultado;
	
	public function __construct($servidor,$db){

		$this->servidor = $servidor;
		$this->db       = resolverRutaFirebird($db);
		$this->conexion = new PDO("firebird:dbname=".$this->servidor.":".$this->db, "SYSDBA", "masterkey");
	}
	
	public function consulta($sql){
		
		return $this->resultado = $this->conexion->query($sql);

	}	

	public function query($sql){
		
		return $this->resultado = $this->conexion->query($sql);

	}

	public function prepare($query) {
		return $this->conexion->prepare($query);
	}

	public function execute($stmt, $params) {
		return $stmt->execute($params);
	}

	public function fetchAll($stmt) {
		$results = [];
		while ($row = $stmt->fetch(PDO::FETCH_OBJ)) {
			$results[] = $row;
		}
		return $results;
	}
	
	public function __destruct(){

	}
}

function fCrearLogTNS($usuario,$descripcion,$bd)
{
	$vfecha     = date("Y-m-d H:i:s");
	$bd         = str_replace(".GDB",".LOG",$bd);
	$ip         = '127.0.0.1';

	if($ctnslog = new dbFirebird($ip,$bd))
    {
    	$vsql_log = "INSERT INTO LOGAUDI (FECHACREAC,USUARIO,TERMINAL,DOCUMENTO)VALUES('".$vfecha."','".$usuario."','INVENTARIOS_AUTO','".$descripcion."')";
    	//echo $vsql;
    	$ctnslog->consulta($vsql_log);
    }
}

class dbSqlite{

	private $ruta;
	private $conexion;
	private $idConsulta;
	
	public function __construct($ruta){
		$this->ruta=$ruta;
		$this->conexion=sqlite_open($ruta,'777',$error);
		if (!$this->$conexion)die($error);
	}
	public function consulta($query){
		$this->idConsulta=sqlite_query($this->conexion,$query);	
	}	
	public function arrayConsulta(){
		return sqlite_fetch_array($this->idConsulta);
	}
	public function consultarArray($ruta,$query){
		return sqlite_array_query($ruta,$query,SQLITE_ASSOC);
	}
}

class dbFirebird {

    private $servidor;
    private $usuario;
    private $password;
    private $ruta;
    private $conexion;
    private $transaccion;
    private $idConsulta;

    public function __construct($ip, $ruta) {
        $this->servidor = $ip;
        $this->usuario  = "SYSDBA";
        $this->password = "masterkey";
        $this->ruta     = resolverRutaFirebird($ruta);

        $this->conexion = ibase_connect($this->servidor . ":" . $this->ruta, $this->usuario, $this->password);
    }

    public function startTransaction() {
        $this->transaccion = ibase_trans(IBASE_WRITE | IBASE_CONCURRENCY, $this->conexion);
    }

    public function commit() {
        if ($this->transaccion) {
            ibase_commit($this->transaccion);
            $this->transaccion = null;
        }
    }

    public function rollback() {
        if ($this->transaccion) {
            ibase_rollback($this->transaccion);
            $this->transaccion = null;
        }
    }

    public function consulta($query) {
        if ($this->transaccion) {
            return $this->idConsulta = ibase_query($this->transaccion, $query);
        } else {
            return $this->idConsulta = ibase_query($this->conexion, $query);
        }
    }

    public function consulta_retorno($query) {
        return $this->idConsulta = ibase_prepare($this->conexion, $query) or die(ibase_errmsg());
    }

    public function prepare($query) {
        return ibase_prepare($this->conexion, $query);
    }

    public function execute($stmt, $params) {
        return ibase_execute($stmt, ...$params);
    }

    public function fetch($result) {
        return ibase_fetch_object($result);
    }

    public function close() {
        ibase_close($this->conexion);
    }
}


class dbFirebird2{

	private $servidor;
	private $usuario;
	private $password;
	private $ruta;
	private $conexion;
	private $idConsulta;
	
	public function __construct($ruta){

		$this->servidor = "localhost";
		$this->usuario  = "SYSDBA";
		$this->password = "masterkey";
		$this->ruta     = $ruta;
		
		$this->conexion = ibase_connect($this->servidor.":".$this->ruta,$this->usuario,$this->password);
	}
	public function consulta($query){
			return $this->idConsulta = ibase_query($this->conexion,$query);
	}
	
	public function consulta_retorno($query){
			return $this->idConsulta = ibase_prepare($this->conexion,$query) or die(ibase_errmsg());
	}
}
?>
