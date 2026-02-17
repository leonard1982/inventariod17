# Arquitectura Actual

## 1. Entradas principales
- `index.php`: formulario de login.
- `ValidaUser.php`: validación de credenciales (procedimiento almacenado) y creación de sesión.
- `Principal.php`: contenedor principal con menú y carga dinámica de módulos.

## 2. Núcleo de bootstrap y conexión
- `conecta.php`:
  - Inicia sesión si no está activa.
  - Carga `php/baseDeDatos.php`.
  - Localiza y lee TXT de configuración.
  - Construye conexiones:
    - `$conect_bd_actual`
    - `$conect_bd_anterior`
    - `$conect_bd_inventario`
    - `$conect_bd_actualPDO`
  - Define utilitarios (`includeAssets`, `fCrearLogTNS`, `enviarCorreoSMTP`).

- `php/baseDeDatos.php`:
  - Clases `dbFirebird`, `dbFirebirdPDO`, utilidades de transacción y consulta.
  - Logging a `LOGAUDI`.

## 3. Patrón de UI
- `Principal.php` + `js/scripts.js` implementan shell de navegación.
- Cada opción de menú ejecuta AJAX a una página PHP de módulo.
- Varios módulos tienen patrón:
  - Página base (filtros y tabla inicial).
  - Endpoint `*_ajax.php` para datos/paginación.
  - Endpoint `*_excel.php` para exportación.

## 4. Módulos funcionales relevantes
- Reportes inventario:
  - `ListaSinMovConExis.php` + `ListaSinMovConExis_ajax.php`.
  - `ListaSinMovSinExis.php` + `ListaSinMovSinExis_ajax.php`.
  - `rotacion_inventario.php` + `rotacion_inventario_ajax.php`.
  - `abc_precio.php`, `comparativo.php`, `informe_pedido_mensual.php`.
- Configuración:
  - `ListaConfiguraciones.php`, `configuraciones.php`, `ActualizarConfiguracion.php`.
  - `ListaConfiguracionLineas.php`, `configuracionLineas.php`, `ActualizarConfiguracionLinea.php`.
  - `ConfiguracionVencimientoPorProductos.php` + alta/baja asociadas.
- Operación y automatización:
  - `index2_detalle.php` / `index2_detalle_SCRIPT.php` (cálculo min/max).
  - `ScriptPedidoAutomatico.php`.
  - `SCRIPT_CIERRE_BACKORDER.php`.
  - `ClasificacionProductosABCD_script.php`.

## 5. Endpoints y archivos AJAX detectados
- `ValidaUser.php`
- `ListaSinMovConExis_ajax.php`
- `ListaSinMovSinExis_ajax.php`
- `ListaConMovSinExis_ajax.php`
- `rotacion_inventario_ajax.php`
- `abc_precios_ajax.php`
- `recalcularnumericas_ajax.php`
- `backorder_actualizar_estado.php`

## 6. Includes y modularidad
- Incluye base común: `require("conecta.php")` en gran parte del proyecto.
- Incluye heredado: `require("tns/conexion.php")` en algunos scripts; actualmente sin contenido.
- Librerías frontend centralizadas por `includeAssets()` y por includes directos en páginas antiguas.

## 7. Procesos batch / programación
- Scripts CMD en raíz:
  - `generar_punto_pedido.cmd` -> `index2_detalle.php`
  - `log_maximos_minimos.cmd` -> `index2_detalle_SCRIPT.php`
  - `ScriptPedidoAutomatico.cmd` -> `ScriptPedidoAutomatico.php`
  - `notificarVencimientoProductos.cmd` -> `NotificarConfiguracionVencimientoPorProducto_script.php`
  - `notificarProductoPuntoPedido.cmd` -> `NotificarProductoPuntoPedido.php`
  - `clasificar_productos_a_b_c_d.cmd` -> `ClasificacionProductosABCD_script.php`
- `bats/script_inventario_min_max.cmd` y XML de Task Scheduler para ejecución diaria.

