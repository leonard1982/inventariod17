Fecha (America/Bogota): 2026-02-17
Hora: 08:02:18
Autor: Codex (Antigravity)

Descripcion corta:
Ajuste de conexion para soportar rutas de BD en TXT sin letra de unidad.

Detalle tecnico:
Se agrego la funcion `resolverRutaBaseDatos` en `conecta.php` para resolver rutas en formato `:\...` de manera dinamica.
La resolucion se realiza en este orden:
1. Si la ruta ya incluye unidad (`C:\` o `C:/`), se usa tal cual.
2. Si no tiene unidad (`:\...`), primero se prueba la unidad del proyecto actual.
3. Si no existe en esa unidad, se recorre de `A:` a `Z:` hasta encontrar la ruta existente.
Tambien se actualizo la validacion de BD para usar la ruta resuelta y evitar fallos por contenido crudo del TXT.

Archivos afectados:
- conecta.php
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Permitir configuracion mas portable entre entornos donde la carpeta de datos puede estar en distintas unidades de disco sin editar codigo PHP.

Impacto:
Los archivos `bd_actual.txt`, `bd_admin.txt`, `bd_anterior.txt` y `bd_inventarios.txt` ahora pueden contener rutas con o sin letra de unidad.
Se mantiene compatibilidad con la configuracion actual y con PHP 7.3.

Pendientes:
- Validar en ambiente real la lectura de cada TXT con rutas `:\...` y rutas completas.

---

Fecha (America/Bogota): 2026-02-17
Hora: 08:08:02
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de deteccion de TXT en ruta real del proyecto y control de conexiones opcionales.

Detalle tecnico:
Se ajusto `buscarArchivo` en `conecta.php` para priorizar la carpeta actual del proyecto (`__DIR__`) y mantener fallback a rutas heredadas (`/facilweb/...` y `/facilweb_fe73_32/...`).
Se evito el intento de conexion con ruta vacia y se redefinio el manejo de bases auxiliares:
- BD actual: obligatoria.
- BD anterior e inventarios: opcionales, solo conectan si la ruta existe.
Tambien se cambio `validarBaseDatos` para permitir validaciones sin salida de texto en bases opcionales y no contaminar respuestas JSON de login/AJAX.

Archivos afectados:
- conecta.php
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Corregir errores en ejecucion (`NO SE ENCUENTRA EL ARCHIVO...`, warnings de `ibase_connect` y fatal de `PDO`) causados por busqueda en ruta heredada y conexion con valores vacios.

Impacto:
El sistema encuentra los TXT en `C:\facilweb\htdocs\evento_inventario` y evita fallos fatales al iniciar.
El login deja de recibir texto extra por validaciones de bases opcionales.

Pendientes:
- Verificar desde interfaz web el ingreso en `index.php` -> `ValidaUser.php`.

---

Fecha (America/Bogota): 2026-02-17
Hora: 08:21:37
Autor: Codex (Antigravity)

Descripcion corta:
Homogeneizacion de rutas de configuracion en modulos con conexion duplicada.

Detalle tecnico:
Se actualizaron los modulos que mantenian rutas heredadas `facilweb_fe73_32` para usar la ruta de proyecto vigente `facilweb` al buscar TXT por unidad.
En archivos con hardcode absoluto (`f:/...`) se reemplazo por rutas locales basadas en `__DIR__` para mantener compatibilidad independiente de la unidad.
En `php/scripts/script_inventario_min_max.php` tambien se reemplazaron includes absolutos por includes relativos a `__DIR__`.

Archivos afectados:
- abc_precio.php
- abc_precios_ajax.php
- backorder.php
- backorder_actualizar_estado.php
- backorder_detalle.php
- comparativo.php
- comparativo1.php
- comparativo_excel.php
- index2_detalle.php
- index2_detalle_SCRIPT.php
- informe_pedido_mensual.php
- informe_pedido_mensual_detalle.php
- informe_pedido_mensual_excel.php
- ListaConMovSinExis.php
- ListaSinMovConExis_SCRIPT.php
- php/scripts/script_inventario_min_max.php
- rotacion_inventario.php
- rotacion_inventario_ajax.php
- rotacion_inventario_excel.php
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Evitar fallas de lectura de TXT y de conexion en modulos que aun tenian la estructura de ruta antigua.

Impacto:
Los modulos heredados ahora buscan configuracion en la estructura vigente del proyecto y no dependen de una unidad fija `F:`.
Se reduce el riesgo de errores por migracion de entorno.

Pendientes:
- Validacion funcional en navegador de los modulos actualizados (pantallas y exportaciones).

---

Fecha (America/Bogota): 2026-02-17
Hora: 08:28:23
Autor: Codex (Antigravity)

Descripcion corta:
Correccion global de lectura de rutas de BD desde TXT para evitar `:\...` invalido en conexiones.

Detalle tecnico:
Se agrego en `php/baseDeDatos.php` la funcion `resolverRutaFirebird` y se aplico en los constructores `dbFirebird` y `dbFirebirdPDO`.
Adicionalmente, en los modulos heredados se reemplazo la lectura `addslashes(fgets($fp))` por `resolverRutaFirebird(fgets($fp))`.
Con esto, cuando los TXT contienen rutas tipo `:\DATOS TNS\...`, el sistema resuelve dinamicamente la unidad correcta antes de validar/conectar.

Archivos afectados:
- php/baseDeDatos.php
- abc_precio.php
- abc_precios_ajax.php
- backorder.php
- backorder_actualizar_estado.php
- backorder_detalle.php
- comparativo.php
- comparativo1.php
- comparativo_excel.php
- estado_cuenta.php
- index2_detalle.php
- index2_detalle_SCRIPT.php
- informe_pedido_mensual.php
- informe_pedido_mensual_detalle.php
- informe_pedido_mensual_excel.php
- ListaConMovSinExis.php
- rotacion_inventario.php
- rotacion_inventario_ajax.php
- rotacion_inventario_excel.php
- php/scripts/script_inventario_min_max.php
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Eliminar errores de apertura de BD por rutas sin letra de unidad y evitar fatales PDO/ibase en modulos con conexion duplicada.

Impacto:
Las conexiones ya no usan rutas invalidas `:\...` y los mensajes de "NO SE ENCUENTRA LA BASE..." dejan de aparecer falsamente cuando la BD si existe.
`rotacion_inventario.php` validado sin fatal.

Pendientes:
- Probar en navegador los flujos de rotacion/comparativo/backorder con usuario real.
