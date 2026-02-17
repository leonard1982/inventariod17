<?php
class Database {
    private $host;
    private $dbname;
    private $user;
    private $password;
    private $conn;
    private $lastError;

    /**
     * Constructor de la clase Database.
     * Inicializa los parámetros de conexión y establece la conexión a la base de datos.
     *
     * @param string $host Dirección IP o nombre del host del servidor MySQL.
     * @param string $dbname Nombre de la base de datos.
     * @param string $user Nombre de usuario para la conexión.
     * @param string $password Contraseña para la conexión.
     */
    public function __construct($host, $dbname, $user, $password) {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->user = $user;
        $this->password = $password;
        $this->connect();
    }

    /**
     * Establece la conexión a la base de datos utilizando PDO.
     * Maneja errores de conexión y los almacena en la propiedad $lastError.
     */
    private function connect() {
        try {
            $this->conn = new PDO("mysql:host={$this->host};dbname={$this->dbname}", $this->user, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->jsonResponse(false, "Error de conexión: " . $this->lastError);
            die();
        }
    }

    /**
     * Ejecuta una consulta SQL con parámetros opcionales.
     *
     * @param string $sql La consulta SQL a ejecutar.
     * @param array $params Parámetros opcionales para la consulta.
     * @return PDOStatement|false El objeto PDOStatement en caso de éxito, false en caso de error.
     */
    public function query($sql, $params = []) {
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            $this->lastError = $e->getMessage();
            $this->jsonResponse(false, "Error en la consulta: " . $this->lastError);
            return false;
        }
    }

    /**
     * Obtiene un solo resultado de una consulta SQL.
     *
     * @param string $sql La consulta SQL a ejecutar.
     * @param array $params Parámetros opcionales para la consulta.
     * @return array|false El resultado de la consulta en forma de array asociativo, false en caso de error.
     */
    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) {
                $this->jsonResponse(true, "Consulta exitosa", $result);
            } else {
                $this->jsonResponse(false, "No se encontraron resultados");
            }
            return $result;
        }
        return false;
    }

    /**
     * Obtiene todos los resultados de una consulta SQL.
     *
     * @param string $sql La consulta SQL a ejecutar.
     * @param array $params Parámetros opcionales para la consulta.
     * @return array|false Los resultados de la consulta en forma de array asociativo, false en caso de error.
     */
    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($results) {
                $this->jsonResponse(true, "Consulta exitosa", $results);
            } else {
                $this->jsonResponse(false, "No se encontraron resultados");
            }
            return $results;
        }
        return false;
    }

    /**
     * Obtiene el número de filas afectadas por una consulta SQL.
     *
     * @param string $sql La consulta SQL a ejecutar.
     * @param array $params Parámetros opcionales para la consulta.
     * @return int|false El número de filas afectadas, false en caso de error.
     */
    public function rowCount($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            $count = $stmt->rowCount();
            $this->jsonResponse(true, "Consulta exitosa", ["rowCount" => $count]);
            return $count;
        }
        return false;
    }

    /**
     * Devuelve el último error ocurrido.
     *
     * @return string El mensaje del último error.
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Cierra la conexión a la base de datos.
     */
    public function close() {
        $this->conn = null;
    }

    /**
     * Devuelve una respuesta JSON.
     *
     * @param bool $success Indica si la operación fue exitosa.
     * @param string $message Mensaje de la respuesta.
     * @param array $data Datos adicionales para la respuesta.
     */
    private function jsonResponse($success, $message, $data = []) {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => $success,
            'message' => $message,
            'data' => $data
        ]);
        exit;
    }
}

// Ejemplo de uso
$db = new Database('127.0.0.1', 'nombre_base_datos', 'usuario', 'contraseña');

// Ejecutar una consulta
$result = $db->query("SELECT * FROM tabla WHERE columna = :valor", ['valor' => 'ejemplo']);
if ($result) {
    $rows = $result->fetchAll(PDO::FETCH_ASSOC);
    $db->jsonResponse(true, "Consulta exitosa", $rows);
} else {
    $db->jsonResponse(false, "Error: " . $db->getLastError());
}

// Obtener un solo resultado
$row = $db->fetchOne("SELECT * FROM tabla WHERE id = :id", ['id' => 1]);

// Obtener todos los resultados
$rows = $db->fetchAll("SELECT * FROM tabla");

// Obtener el número de filas afectadas
$count = $db->rowCount("SELECT * FROM tabla");

// Cerrar la conexión
$db->close();
?>