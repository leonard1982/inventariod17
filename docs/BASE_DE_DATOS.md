# Base de Datos

## 1. Fuentes usadas en esta documentación
- `metadata/METADATA_INVENTARIOS.sql`
- `metadata/METADATA_CMDISTRI17-2026.sql`
- Cruce con consultas SQL presentes en PHP activo (sin `obsoletos/`).

No se ejecutaron cambios sobre bases de datos.

## 2. Modelo A: BD INVENTARIOS (configuración y logs)
Archivo metadata: `metadata/METADATA_INVENTARIOS.sql`

### 2.1 Tablas detectadas (8)
- `BD_ANIOS`
- `CONFIGURACIONES`
- `ELIMINADOS`
- `ESTADOS_PEDIDOS`
- `HISTORIOCO_MIN_MAX`
- `LOG_ESTADOS_PEDIDOS`
- `MINIMOS`
- `SN_LOG_CIERRE_BACKORDER`

### 2.2 Llaves
- Todas las tablas del metadata INVENTARIOS tienen llave primaria por `ID`.
- No se detectaron `FOREIGN KEY` declaradas en este metadata.

### 2.3 Tablas clave usadas por el código

| Tabla | Campos usados en código | Archivos principales |
|---|---|---|
| `CONFIGURACIONES` | `ID`, `GRUPO`, `PORCENTAJE_SEGURIDAD`, `TIEMPO_ENTREGA`, `DIAS_INVENTARIO`, `DIAS_PEDIDOS`, `TENDENCIA_MESES`, `PREFIJO_TRASLADO`, `PREFIJO_ORDEN_PEDIDO`, `CORREO_NOTIFICACION`, `DIAS_PARA_CIERRE`, `INICIAR_EN`, `NIT_PROVEEDOR` | `configuraciones.php`, `ActualizarConfiguracion.php`, `ListaConfiguraciones.php`, `ScriptPedidoAutomatico.php`, `index2_detalle.php`, `index2_detalle_SCRIPT.php` |
| `SN_LOG_CIERRE_BACKORDER` | `ID`, `FECHA`, `DEKARDEXID`, `MATID`, `PEDIDO` | `SCRIPT_CIERRE_BACKORDER.php` |
| `ESTADOS_PEDIDOS` | `ID`, `CODIGO`, `DESCRIPCION`, `ORDEN` | `estados_pedidos.php`, `AgregarEstado.php`, `BorrarEstado.php` |
| `LOG_ESTADOS_PEDIDOS` | `ID`, `FECHA`, `DATOS_ANT`, `DATOS_NUE`, `OPERACION`, `USUARIO` | `AgregarEstado.php`, `BorrarEstado.php` |
| `BD_ANIOS` | `ID`, `ANIO`, `RUTA_BD` | `conexiones.php`, `AgregarConexion.php`, `BorrarConexion.php` |
| `HISTORIOCO_MIN_MAX` | `ID`, `FECHAYHORA`, `ANIO`, `MES`, `CODIGO`, `DESCRIP`, `MINIMO`, `MAXIMO`, `EXISTENC`, `COSTO`, `PUNTO_PEDIDO` | `index2_detalle.php`, `index2_detalle_SCRIPT.php` |
| `MINIMOS` | columnas de históricos min/max y pedido | `log_maximos_minimos.php`, `index2_detalle.php` |
| `ELIMINADOS` | `CODIGO`, `DESCRIPCION`, `ULTIMO_MOVIMIENTO`, `MATID` | `SCRIPT_ELIMINADOS.php` |

## 3. Modelo B: BD CMDISTRI17-2026 (operación contable/comercial)
Archivo metadata: `metadata/METADATA_CMDISTRI17-2026.sql`

### 3.1 Dimensión general
- 385 tablas detectadas.
- El sistema Inventario D17 usa un subconjunto para inventario, compras, ventas y documentos.

### 3.2 Tablas operativas clave y relaciones

| Tabla | Llave principal | Relaciones relevantes |
|---|---|---|
| `MATERIAL` | `MATID` | FK a `GRUPMAT`, `LINEAMAT`, `TIPOIVA`, `MARCAART` |
| `MATERIALSUC` | `(MATID, SUCID)` | FK a `MATERIAL`; referencia terceros `ULTCLI/ULTPRO` |
| `SALMATERIAL` | `(MATID, BODID, SUCID)` | FK a `MATERIAL`, `BODEGA`, `SUCURSAL` |
| `GRUPMAT` | `GRUPMATID` | Catálogo de grupos/familias |
| `LINEAMAT` | `LINEAMATID` | Catálogo de líneas |
| `KARDEX` | `KARDEXID` | Documento cabecera; FK a múltiples catálogos y a `TERCEROS` |
| `DEKARDEX` | `DEKARDEXID` | Detalle documento; FK a `KARDEX`, `MATERIAL`, `BODEGA` |
| `KARDEXSELF` | `KARDEXID` | Extensión de cabecera; FK a `KARDEX` |
| `TERCEROS` | `TERID` | Maestros de proveedor/cliente/vendedor |
| `TIPOIVA` | `TIPOIVAID` | Tarifa IVA referenciada por `MATERIAL` |
| `MARCAART` | `MARCAARTID` | Clasificación de artículo usada en scripts ABC |
| `SN_INV_VENCE_GRUPO` | `ID` | Configuración de vencimiento por grupo (sin FK declarada en metadata) |
| `SN_PRESU_VEND_LINEAS` | `ID` | Configuración de presupuesto por línea/asesor (sin FK declarada en metadata) |

### 3.3 Campos críticos usados por módulos

| Tabla | Campos muy usados |
|---|---|
| `MATERIAL` | `MATID`, `CODIGO`, `DESCRIP`, `GRUPMATID`, `LINEAMATID`, `MARCAARTID`, `TIPOIVAID` |
| `MATERIALSUC` | `EXISTENC`, `EXISTMIN`, `EXISTMAX`, `COSTO`, `ULTCOSTPROM`, `FECULTCLI`, `FECULTPROV`, `SN_PUNTO_PEDIDO`, `SN_STOCK_MAXIMODIF` |
| `KARDEX` | `KARDEXID`, `CODCOMP`, `CODPREFIJO`, `NUMERO`, `FECHA`, `FECASENTAD`, `CLIENTE`, `SN_ORDEN_COMPRA`, `SN_ESTADO_INV`, `SN_IDESTADOPEDIDO` |
| `DEKARDEX` | `DEKARDEXID`, `KARDEXID`, `MATID`, `BODID`, `CANMAT`, `PRECIOBASE`, `PRECIOIVA`, `PRECIONETO`, `SN_ESTADO_BACKORDER` |
| `SN_INV_VENCE_GRUPO` | `ID`, `GRUPMATID`, `MESES_VENCIMIENTO` |
| `SN_PRESU_VEND_LINEAS` | `ID`, `TERID`, `LINEAID`, `PRESUPUESTO`, `LINEA`, `CANTIDAD` |

## 4. Mapa código -> tablas (principales)

| Área funcional | Tablas principales |
|---|---|
| Login y auditoría | `LOGAUDI`, procedimiento `TNS_WS_VERIFICAR_USUARIO` |
| Configuración general | `CONFIGURACIONES`, `GRUPMAT`, `PREFIJO`, `TERCEROS` |
| Configuración por línea | `SN_PRESU_VEND_LINEAS`, `LINEAMAT`, `TERCEROS` |
| Vencimiento por grupo | `SN_INV_VENCE_GRUPO`, `GRUPMAT`, `MATERIAL`, `MATERIALSUC` |
| Reportes de inventario | `MATERIAL`, `MATERIALSUC`, `SALMATERIAL`, `GRUPMAT`, `LINEAMAT`, `KARDEX`, `DEKARDEX` |
| Backorder | `KARDEX`, `DEKARDEX`, `MATERIAL`, `TERCEROS`, `SN_LOG_CIERRE_BACKORDER` |
| Pedidos automáticos | `CONFIGURACIONES`, `MATERIAL`, `MATERIALSUC`, `SALMATERIAL`, `KARDEX`, `DEKARDEX`, `KARDEXSELF`, `TERCEROS`, `TIPOIVA` |

## 5. Observaciones de integridad
- En INVENTARIOS no hay relaciones FK declaradas en metadata.
- En CMDISTRI sí existen PK/FK amplias para tablas core (`MATERIAL`, `KARDEX`, `DEKARDEX`, etc.).
- `SN_INV_VENCE_GRUPO` y `SN_PRESU_VEND_LINEAS` tienen PK y uso aplicativo, pero sin FK explícita en el metadata disponible.

