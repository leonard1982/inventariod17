# Entorno Técnico

## 1. Plataforma objetivo
- Sistema operativo: Windows.
- Servidor web: Apache (XAMPP).
- PHP: 7.3 (32 bits).
- Motor BD: Firebird 2.5.
- Cliente BD en código: `ibase_*` (Interbase/Firebird) y algunos usos de PDO Firebird.

## 2. Dependencias PHP relevantes
- Extensión `interbase` (`ibase_connect`, `ibase_query`, `ibase_fetch_object`).
- Extensión `pdo_firebird` en módulos que usan `dbFirebirdPDO`.
- `vendor/autoload.php` para envío SMTP (PHPMailer).

## 3. Configuración de bases por archivos TXT (raíz)
El proyecto usa archivos TXT para resolver rutas y parámetros:
- `bd_actual.txt`: ruta de la BD operativa actual.
- `bd_anterior.txt`: ruta de la BD operativa histórica/anterior.
- `bd_inventarios.txt`: ruta de la BD de inventarios/configuración.
- `bd_actual_produccion.txt`: variante usada por `php/scripts/script_inventario_min_max.php`.
- `bd_admin.txt`: referencia adicional administrativa.
- `prefijos.txt`: lista de prefijos de documentos.
- `servidor_smtp.txt`: parámetros SMTP por líneas (remitente, nombre, servidor, puerto, usuario, clave).
- `fecha_inicio_inventario.txt`: fecha base de operación inventario.

## 4. Diferencia dev vs producción (configurable por TXT)
- Desarrollo/operación principal: `bd_actual.txt`.
- Producción histórica/controlada: `bd_actual_produccion.txt`.
- Comparativos: combinación de `bd_actual.txt` y `bd_anterior.txt`.

Conclusión: la estrategia principal para cambios de ambiente está en TXT, sin recompilar ni editar SQL embebido.

## 5. Observaciones de implementación actual
- `conecta.php` implementa búsqueda de TXT por unidades `A:` a `Z:` en ruta `facilweb_fe73_32/htdocs/evento_inventario/`.
- Existen módulos heredados que aún usan rutas absolutas directas (ejemplo: `f:/facilweb_fe73_32/...`) para los mismos TXT.
- `tns/conexion.php` existe pero está vacío (archivo de compatibilidad/legado en includes).

## 6. Recomendación operativa
- Mantener los TXT como única fuente de verdad para rutas de BD y parámetros de entorno.
- Evitar agregar nuevas rutas hardcodeadas; centralizar en `conecta.php`.

