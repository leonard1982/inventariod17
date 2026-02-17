# Seguridad

## 1. Estado actual observado

### 1.1 Sesiones
- Se usa `session_start()` en login y módulos.
- `Principal.php` valida sesión (`$_SESSION["user"]`) antes de mostrar contenido principal.
- Varios endpoints aceptan peticiones sin validación explícita de sesión en la propia ruta.

### 1.2 Validación y sanitización
- Hay uso parcial de `htmlspecialchars`, `intval`, `floatval`, `addslashes`.
- Predomina SQL concatenado con variables de `$_GET`/`$_POST`.
- Solo algunos puntos usan `prepare/execute` de forma consistente.

### 1.3 Exposición de errores y depuración
- En múltiples módulos está activo `display_errors=1` y `error_reporting(E_ALL)`.
- Algunos endpoints devuelven SQL en la respuesta JSON (`"sql" => $vsql`).

### 1.4 Secretos y configuración sensible
- Parámetros de BD se leen desde TXT.
- SMTP se carga desde `servidor_smtp.txt`.
- En `php/baseDeDatos.php` existen credenciales Firebird fijas para conexión (`SYSDBA/masterkey`).
- `php/bd.php` contiene conexión MySQL legacy con credenciales embebidas.

### 1.5 Cliente web
- Login guarda credenciales en cookies del navegador cuando se activa “Recordar credenciales”.
- No se detectó protección CSRF en formularios/acciones críticas.

## 2. Riesgos principales
1. Inyección SQL por concatenación directa en múltiples endpoints.
2. Ejecución no autorizada de endpoints sin control central de sesión/rol.
3. Exposición de información sensible por errores en pantalla y respuestas con SQL.
4. Riesgo por secretos en texto plano (archivos TXT y credenciales embebidas).
5. Riesgo de robo de credenciales por almacenamiento en cookie de usuario/clave.

## 3. Recomendaciones priorizadas
1. Migrar consultas a `prepare + parámetros` en todas las rutas con input de usuario.
2. Implementar middleware/check central de autenticación para cada endpoint.
3. Desactivar `display_errors` en entornos no desarrollo y registrar en log interno.
4. Eliminar SQL de respuestas JSON de producción.
5. Reemplazar almacenamiento de contraseña en cookie por token temporal seguro.
6. Rotar y externalizar credenciales (BD/SMTP) en mecanismo seguro.
7. Añadir protección CSRF en formularios y acciones POST críticas.
8. Unificar lectura de configuración en `conecta.php` y eliminar rutas absolutas heredadas.

## 4. Alcance de esta auditoría
- Solo análisis y documentación.
- No se modificó la lógica de autenticación, SQL, sesiones ni conexiones.

