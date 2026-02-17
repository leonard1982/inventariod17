<?php
// Incluir archivos de conexión a la base de datos
require_once "conecta.php";

// Función para verificar el usuario y contraseña
function verificarUsuario($usuario, $password, $conexion) {
    // Consulta para verificar el usuario y contraseña usando un procedimiento almacenado
    $sql = "EXECUTE PROCEDURE TNS_WS_VERIFICAR_USUARIO(?, ?)";
    $stmt = $conexion->prepare($sql);
    $result = $conexion->execute($stmt, [$password, $usuario]);

    // Procesa el resultado de la consulta
    if ($row = $conexion->fetch($result)) {
        $mensaje = utf8_encode($row->OMENSAJE);
        $mensaje = str_replace("'", "", $mensaje);
        return trim($mensaje);
    }
    return null;
}

// Función para registrar el inicio de sesión en la base de datos
function registrarInicioSesion($usuario, $conexion) {
    // Registra el inicio de sesión en la base de datos
    fCrearLogTNS($usuario, 'EL USUARIO ' . $usuario . ' INICIO SESION EN LA PLATAFORMA WEB DE INVENTARIOS_AUTO', $conexion);
}

// Verifica si se ha enviado el formulario de inicio de sesión
if (isset($_POST["usuario"])) {
    // Inicializa variables
    $accion = "sinregistro"; // Acción por defecto: no registrado
    $usuario = htmlspecialchars($_POST["usuario"]); // Usuario ingresado
    $password = htmlspecialchars($_POST["password"]); // Contraseña ingresada
    $mensaje = 'error'; // Mensaje por defecto: error

    // Verifica el usuario y contraseña
    $resultado = verificarUsuario($usuario, $password, $conect_bd_actual);

    // Verifica si el mensaje indica un inicio de sesión exitoso
    if ($resultado === "Inicio de Sesion exitoso") {
        // Establece la acción como registrado y almacena el usuario en la sesión
        $mensaje = 'exitoso';
        $accion = "conregistro";
        $_SESSION["user"] = $usuario;
        $_SESSION["userdeslogueado"] = $usuario;

        // Registra el inicio de sesión en la base de datos
        registrarInicioSesion($usuario, $contenidoBdActual);
    } else {
        // Establece la acción como no registrado y el mensaje como error
        $mensaje = 'error';
        $accion = "sinregistro";
    }

    // Devuelve el resultado en formato JSON
    echo json_encode(array(
        "accion" => $accion,
        "mensaje" => $mensaje,
        "user" => $usuario
    ));
}
?>