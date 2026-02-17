# Flujos Funcionales

## 1. Autenticación y entrada
1. Usuario abre `index.php`.
2. `js/index.js` envía `usuario/password` a `ValidaUser.php`.
3. `ValidaUser.php` ejecuta `EXECUTE PROCEDURE TNS_WS_VERIFICAR_USUARIO`.
4. Si es válido, se crea `$_SESSION["user"]` y redirige a `Principal.php`.

## 2. Navegación principal (single-page parcial)
1. `Principal.php` renderiza menú.
2. `js/scripts.js` captura clics del menú.
3. Cada clic hace `POST` AJAX y reemplaza `#contenido`.
4. Módulos cargan sus propios filtros, tablas y endpoints.

## 3. Reporte: Sin movimiento con existencia
Entradas:
- `ListaSinMovConExis.php` (filtros: grupo, línea, fecha, años, bodega, traslado).

Proceso:
1. UI llama `ListaSinMovConExis_ajax.php`.
2. Se consulta `MATERIAL`, `MATERIALSUC`, `SALMATERIAL`, `GRUPMAT`, `LINEAMAT`.
3. Si `traslado=SI`, inserta/actualiza `TRASLA` y `DETRASLA` de forma automática.

Salidas:
- Tabla paginada.
- Exportación Excel por parámetro `tipo=excel`.

## 4. Reporte: Sin movimiento y sin existencia
Entradas:
- `ListaSinMovSinExis.php`.

Proceso:
1. AJAX a `ListaSinMovSinExis_ajax.php`.
2. Filtra material con `existenc = 0`.
3. Opción `paraeliminar=SI` marca artículos con `MARCAART='PELIMINAR'` (update en `MATERIAL`).

Salidas:
- Tabla paginada.
- Exportación Excel.

## 5. Rotación inventario
Entradas:
- `rotacion_inventario.php` (fecha inicial, grupo, línea, registros).

Proceso:
- Consulta y cálculo en `rotacion_inventario_ajax.php` con cruce `KARDEX/DEKARDEX` y costos/rotación.

Salidas:
- Tabla con métricas de rotación.
- Exportación en `rotacion_inventario_excel.php`.

## 6. Configuraciones
Subflujo A: configuración general
1. Lista en `ListaConfiguraciones.php`.
2. Edición/alta en `configuraciones.php`.
3. Persistencia en `ActualizarConfiguracion.php`.
4. Eliminación en `ListaConfiguracionesEliminar.php`.

Subflujo B: configuración por líneas
1. Lista en `ListaConfiguracionLineas.php`.
2. Edición/alta en `configuracionLineas.php`.
3. Persistencia en `ActualizarConfiguracionLinea.php`.
4. Eliminación en `ListaConfiguracionLineasEliminar.php`.

## 7. Backorder
1. `backorder.php` lista pedidos de compra con diferencias.
2. `backorder_detalle.php` muestra detalle por `kardexid`.
3. Cambio de estado (`PENDIENTE`/`CERRADO`) en `backorder_actualizar_estado.php`.
4. Cierre automático adicional en `SCRIPT_CIERRE_BACKORDER.php` según `DIAS_PARA_CIERRE`.

## 8. Estados de pedido
1. UI en `estados_pedidos.php`.
2. Alta/edición en `AgregarEstado.php`.
3. Eliminación en `BorrarEstado.php`.
4. Trazabilidad en `LOG_ESTADOS_PEDIDOS`.

## 9. Procesos automáticos (batch)
- `index2_detalle_SCRIPT.php`: cálculo de máximos/mínimos e inserción en históricos.
- `ScriptPedidoAutomatico.php`: generación de pedidos automáticos (`KARDEX`/`DEKARDEX`) y actualización de `INICIAR_EN`.
- `ClasificacionProductosABCD_script.php`: reclasificación por marca/ABC.
- Scripts CMD en raíz y `bats/` para programación en Windows Task Scheduler.

