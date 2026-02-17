# Mapa del Proyecto

## 1. Estructura de carpetas (raíz)

| Carpeta | Tipo | Propósito | Observación |
|---|---|---|---|
| `metadata/` | Soporte | DDL real de BD (`METADATA_INVENTARIOS.sql`, `METADATA_CMDISTRI17-2026.sql`) | Fuente oficial para documentación de datos |
| `php/` | Runtime/Soporte | Clases de conexión y script batch interno | Contiene `baseDeDatos.php` |
| `tns/` | Legado | Incluye heredado de conexión | `conexion.php` vacío |
| `js/` | Runtime UI | Lógica frontend y librerías | Incluye custom + terceros |
| `css/` | Runtime UI | Estilos de interfaz | |
| `imagenes/` | Runtime UI | Recursos visuales | |
| `DataTables/`, `fullcalendar/`, `sortable-master/`, `fpdf/`, `vendor/` | Soporte runtime | Librerías de terceros | |
| `bats/` | Soporte operación | Scripts para Task Scheduler | Ejecución programada |
| `log/` | Runtime temporal | Salidas/archivos de ejecución | Excluida de Git |
| `obsoletos/` | Histórico | Código legado no activo principal | Excluida de Git |
| `pdfs/` | Soporte | Recursos PDF | |

## 2. Archivos raíz más relevantes

| Archivo | Rol |
|---|---|
| `index.php` | Pantalla de inicio de sesión |
| `ValidaUser.php` | Endpoint de autenticación |
| `Principal.php` | Shell principal post-login |
| `conecta.php` | Bootstrap de sesión, TXT y conexiones Firebird |
| `php/baseDeDatos.php` | Clases de conexión (`dbFirebird`, `dbFirebirdPDO`) |
| `bd_actual.txt`, `bd_anterior.txt`, `bd_inventarios.txt` | Rutas de bases de datos |
| `bd_actual_produccion.txt` | Ruta alternativa para script batch |
| `prefijos.txt` | Prefijos de documentos |
| `servidor_smtp.txt` | Parámetros SMTP |
| `ScriptPedidoAutomatico.php` | Generación automática de pedidos |
| `index2_detalle.php` | Cálculo de máximos/mínimos (interactivo) |
| `index2_detalle_SCRIPT.php` | Cálculo de máximos/mínimos (batch) |
| `ListaSinMovConExis_ajax.php` | Reporte con traslados automáticos opcionales |
| `ListaSinMovSinExis_ajax.php` | Reporte con marcado para eliminación |
| `backorder.php`, `backorder_detalle.php` | Gestión de backorder |

## 3. Clasificación runtime vs soporte

| Clasificación | Elementos |
|---|---|
| Runtime web principal | `index.php`, `ValidaUser.php`, `Principal.php`, páginas `Lista*`, `rotacion_*`, `configuraciones*`, `conexiones.php`, `estados_pedidos.php` |
| Runtime batch | `*.cmd`, `bats/*.cmd`, `ScriptPedidoAutomatico.php`, `index2_detalle_SCRIPT.php`, `ClasificacionProductosABCD_script.php` |
| Soporte técnico | `metadata/`, `composer.json`, `vendor/`, librerías JS/CSS |
| Histórico/legado | `obsoletos/`, `Principal - copia.php`, `manual_scriptcase.txt` |

