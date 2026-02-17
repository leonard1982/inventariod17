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

---

Fecha (America/Bogota): 2026-02-17
Hora: 08:50:16
Autor: Codex (Antigravity)

Descripcion corta:
Mejora visual y responsiva del menu principal en la pagina Principal.

Detalle tecnico:
Se rediseno el layout del menu en `Principal.php` con una barra superior profesional, sidebar modernizado y overlay para moviles.
Se agrego el stylesheet `css/menu_profesional.css` con paleta renovada, estados hover/active, tipografia mejorada y comportamiento responsive desktop/movil.
Se actualizo `js/scripts.js` para controlar apertura/cierre por clases CSS (`menu-open`), cierre con tecla Escape, resaltado de opcion activa y mejor manejo de errores AJAX.
Se mantuvo la logica funcional de IDs y cargas de modulos via AJAX.

Archivos afectados:
- Principal.php
- css/menu_profesional.css
- js/scripts.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Modernizar la experiencia del menu principal, mejorar usabilidad en pantallas pequenas y dar una apariencia mas profesional.

Impacto:
Interfaz de navegacion mas clara, consistente y adaptable a movil/escritorio sin alterar rutas ni funcionalidades de reportes.

Pendientes:
- Validacion visual final en navegador con uso real de cada opcion del menu.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:00:09
Autor: Codex (Antigravity)

Descripcion corta:
Implementacion de menu de usuario y panel de indicadores visuales en fondo de la aplicacion.

Detalle tecnico:
Se movieron las opciones `Configuraciones`, `Conexiones` y `Salir` desde el sidebar hacia un menu de usuario desplegable en la barra superior.
Se agrego un bloque de indicadores persistente (`hora local`, `estado de red` y `cantidad de modulos`) con actualizacion en tiempo real desde JavaScript.
Se mejoro el manejo de interacciones del topbar: apertura/cierre del menu de usuario, cierre por click fuera y cierre con tecla Escape.

Archivos afectados:
- Principal.php
- css/menu_profesional.css
- js/scripts.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Mejorar experiencia visual y organizacion de opciones del usuario en la navegacion principal.

Impacto:
Interfaz mas limpia y profesional; opciones de usuario centralizadas en topbar; indicadores visibles de estado sin alterar logica de negocio.

Pendientes:
- Validacion visual final con pruebas en resoluciones moviles reales.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:08:14
Autor: Codex (Antigravity)

Descripcion corta:
Implementacion de navegacion por pestanas internas con cierre individual por modulo.

Detalle tecnico:
Se incorporo un workspace de pestanas en `Principal.php` con barra (`tabBar`) y contenedor de paneles (`tabPanels`).
Cada opcion del menu ahora abre su modulo en una pestana independiente cargada en `iframe`, preservando estado entre pestanas.
Se agregaron funciones en `js/scripts.js` para crear, activar y cerrar pestanas (`abrirPestana`, `activarPestana`, `cerrarPestana`) con boton `X` por pestana.
Se incluyo una pestana base "Inicio" y se sincronizo el item activo del menu segun pestana seleccionada.

Archivos afectados:
- Principal.php
- css/menu_profesional.css
- js/scripts.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Permitir trabajo multitarea en la interfaz sin perder contexto de modulos previamente abiertos.

Impacto:
Los usuarios pueden abrir varios modulos a la vez, alternar entre ellos y cerrar cada uno con su boton `X`.
Mayor productividad y mejor control de navegacion en escritorio y movil.

Pendientes:
- Ajustes finos de usabilidad segun feedback (ej. duplicar misma opcion en varias pestanas).

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:13:04
Autor: Codex (Antigravity)

Descripcion corta:
Ajustes visuales y de usabilidad en workspace por pestanas.

Detalle tecnico:
Se elimino el texto explicativo de la pestana Inicio y se reemplazo por fondo neutro.
Se cambio la paleta de estados activos/hover para eliminar tonos naranjas, usando tonos azules.
Se corrigio desborde horizontal del area de trabajo con reglas de ancho maximo y `overflow-x: hidden`.
Se implemento ajuste dinamico de altura de `iframe` por contenido (`ajustarAlturaIframe`) para evitar truncamiento de formularios/modulos.
Tambien se limpio el temporizador de autoajuste al cerrar cada pestana para evitar procesos colgados.

Archivos afectados:
- css/menu_profesional.css
- js/scripts.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Corregir problemas visuales reportados: texto no deseado, color no aprobado y contenido interno truncado en pestanas.

Impacto:
Interfaz mas limpia y consistente; sin desbordes de ancho; contenido interno de modulos con altura dinamica y mejor lectura.

Pendientes:
- Validacion final en navegador con modulos pesados (tablas extensas y formularios largos).

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:19:59
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de fondo inicio, modo menu iconos/completo y fallback Swal en ItemsMenu.

Detalle tecnico:
Se cambio el fondo de la pestana Inicio a blanco solido (`home-empty`).
Se implemento modo de menu con dos versiones: completo e iconos, con boton de alternancia (`menuModeToggle`) y persistencia en `localStorage`.
Se ajusto comportamiento para que al seleccionar una opcion del menu en escritorio se encoja automaticamente a modo iconos.
En movil se mantiene cierre del sidebar tras seleccionar opcion.
Se agrego `alternarSidebar` para abrir/cerrar desde el boton superior.
Se corrigio `ItemsMenu.js` para evitar `Uncaught ReferenceError: Swal is not defined` usando fallback a `window.parent.Swal` o `confirm()` nativo.

Archivos afectados:
- Principal.php
- css/menu_profesional.css
- js/scripts.js
- js/ItemsMenu.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Atender ajustes de UX solicitados y eliminar error de ejecucion en modulos embebidos.

Impacto:
Interfaz mas consistente con modo de navegacion flexible, comportamiento automatico del menu tras seleccionar modulo y estabilidad en formularios que invocan confirmacion.

Pendientes:
- Validacion final en navegador (cache limpio) de menu iconos/completo y confirmaciones de reportes.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:23:55
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de cache-busting para carga de ItemsMenu.js en modulos embebidos.

Detalle tecnico:
Se actualizo la version del include `ItemsMenu.js` en `ListaSinMovSinExis.php` y `ListaSinMovConExis.php` de `v=05092024_01` a `v=20260217_02`.
Con esto se fuerza recarga del JS actualizado que ya incluye fallback cuando SweetAlert no esta disponible.

Archivos afectados:
- ListaSinMovSinExis.php
- ListaSinMovConExis.php
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Evitar uso de copia obsoleta en cache que seguia ejecutando codigo antiguo con `Swal.fire` directo.

Impacto:
Se elimina el error `Uncaught ReferenceError: Swal is not defined` al abrir modulos que usan `ItemsMenu.js`.

Pendientes:
- Confirmar en navegador con cache limpio que los iframes usan la nueva version del recurso.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:31:27
Autor: Codex (Antigravity)

Descripcion corta:
Ajuste de scroll para bloquear barras en la pagina principal y habilitar desplazamiento vertical en contenido de modulos.

Detalle tecnico:
Se reforzo el bloqueo de overflow global en `html`, `body` y `.layout-menu` para evitar barras vertical/horizontal en la pantalla principal.
Se ajusto `content-shell` con `width: 100%` y `min-width: 0` para prevenir desbordes laterales.
En el workspace de pestanas, cada panel ahora mantiene overflow oculto y el scroll vertical se delega al `iframe` del modulo.
En `js/scripts.js` se forzo `scrolling=\"yes\"` en iframes y, al cargar, se aplica `overflowY='auto'` y `overflowX='hidden'` al documento interno.

Archivos afectados:
- css/menu_profesional.css
- js/scripts.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Corregir el comportamiento solicitado: sin barras en la pagina contenedora y con scroll vertical funcional dentro del contenido de cada item del menu.

Impacto:
La interfaz principal permanece fija al alto/ancho del viewport sin scroll global.
Los modulos cargados en pestanas pueden desplazarse verticalmente dentro del area de contenido.

Pendientes:
- Validar en navegador los modulos mas largos para confirmar scroll interno uniforme en todos los formularios/reportes.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:36:39
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de altura util del workspace para que los modulos en pestanas ocupen todo el alto disponible.

Detalle tecnico:
Se detecto conflicto de especificidad con reglas antiguas de `#contenido` (alto `auto`) que anulaban la altura del nuevo layout por pestanas.
Se agrego override explicito en `css/menu_profesional.css` para `#contenido.content-shell` con altura fija por viewport y overflow oculto.
Tambien se reforzo la estructura vertical del workspace usando grid (`tab-workspace`) y altura completa en `tab-panels`.
En `js/scripts.js`, al cargar cada `iframe`, se forzo `html/body` internos a `height/min-height: 100%`, fondo blanco y scroll vertical interno.

Archivos afectados:
- css/menu_profesional.css
- js/scripts.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Corregir que el contenido de los items de menu se visualizara truncado en alto y no aprovechara toda el area vertical de trabajo.

Impacto:
Cada modulo abierto en pestana ahora usa toda la altura disponible del panel.
El scroll vertical queda dentro del contenido del modulo sin generar barras en la pagina principal.

Pendientes:
- Confirmar en navegador con `Ctrl+F5` que todos los modulos embebidos se comportan igual en escritorio y movil.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:42:24
Autor: Codex (Antigravity)

Descripcion corta:
Mejora visual del primer item del menu (`Lista sin Mov y sin Exis`) sin cambios funcionales.

Detalle tecnico:
Se actualizo la presentacion de `ListaSinMovSinExis.php` agregando clases semanticas de interfaz (`report-page`, `report-shell`, `report-card`, `report-title`, `report-form`, `action-buttons`, `result-panel`) sin modificar eventos `onclick`, consultas SQL ni endpoints AJAX.
Se implemento el stylesheet `css/ListaSinMovSinExis.css` con estilo responsivo profesional: contenedor tipo tarjeta, jerarquia tipografica, inputs/select con focus consistente, botones de accion mejorados y panel de resultados con cabecera sticky.
No se altero la logica de negocio ni flujo de datos.

Archivos afectados:
- ListaSinMovSinExis.php
- css/ListaSinMovSinExis.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Atender solicitud de mejorar el aspecto del primer item del menu manteniendo intactas sus funcionalidades.

Impacto:
Pantalla de consulta inicial mas limpia, profesional y usable en escritorio/movil, sin cambios en comportamiento funcional.

Pendientes:
- Validacion visual en navegador del primer item y ajuste fino antes de replicar el mismo patron al segundo item.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:46:25
Autor: Codex (Antigravity)

Descripcion corta:
Mejora adicional del primer item: tabla mas profesional, formulario refinado e iconografia en botones.

Detalle tecnico:
En `ListaSinMovSinExis.php` se agrego Font Awesome y se actualizaron los botones de accion para incluir iconos (`Generar`, `Excel`) manteniendo los mismos `onclick`.
En `ListaSinMovSinExis_ajax.php` se mejoraron clases de salida (`report-table-wrap`, `report-table`, `report-summary`, `report-pagination`) y se agregaron iconos en los controles de navegacion de paginas (primera, anterior, siguiente, ultima).
En `css/ListaSinMovSinExis.css` se reforzo el estilo del formulario (contenedor, jerarquia visual, focus states), la tabla (cabecera sticky, zebra, hover, legibilidad) y la paginacion/resumen para una apariencia mas profesional y consistente.
No se modificaron consultas SQL ni logica de negocio.

Archivos afectados:
- ListaSinMovSinExis.php
- ListaSinMovSinExis_ajax.php
- css/ListaSinMovSinExis.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Atender solicitud de mejorar el look del formulario y tabla, y aplicar iconos a todos los botones detectados en el flujo del primer item.

Impacto:
Interfaz del primer item mas moderna, clara y usable; botones y paginacion con iconografia; misma funcionalidad operativa.

Pendientes:
- Validar visualmente en navegador el comportamiento de tabla/paginacion tras generar reporte.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:49:07
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de visualizacion para asegurar aplicacion de estilos y centrado de paginacion.

Detalle tecnico:
Se agrego versionado al include de estilos del modulo `ListaSinMovSinExis.php` (`css/ListaSinMovSinExis.css?v=20260217_03`) para evitar cache viejo del navegador.
Se reforzo el centrado de paginacion en `ListaSinMovSinExis_ajax.php` con estilos inline de respaldo en `nav` y `ul`.
Tambien se ajusto `css/ListaSinMovSinExis.css` para que `.report-pagination` use `display:flex`, `justify-content:center` y `width:100%`.

Archivos afectados:
- ListaSinMovSinExis.php
- ListaSinMovSinExis_ajax.php
- css/ListaSinMovSinExis.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Resolver reporte de que el nuevo aspecto no se reflejaba y que la paginacion habia perdido el centrado.

Impacto:
La hoja de estilos se recarga correctamente y la paginacion vuelve a centrarse de forma consistente.

Pendientes:
- Confirmacion visual del usuario tras recarga dura del modulo.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:52:11
Autor: Codex (Antigravity)

Descripcion corta:
Ajuste de ancho del contenido del primer item para evitar expansion excesiva.

Detalle tecnico:
Se corrigio el contenedor `.report-shell` en `css/ListaSinMovSinExis.css` para que use ancho maximo en escritorio (`max-width: 1240px`) y centrado (`margin: 0 auto`).
En movil se conserva comportamiento fluido con `max-width: 100%`.

Archivos afectados:
- css/ListaSinMovSinExis.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Corregir que el contenido del item se mostraba demasiado ancho en pantalla grande.

Impacto:
El modulo queda visualmente mas equilibrado, centrado y legible sin afectar funcionalidades.

Pendientes:
- Confirmar en navegador que el ancho ahora es el esperado en escritorio.

---

Fecha (America/Bogota): 2026-02-17
Hora: 09:56:50
Autor: Codex (Antigravity)

Descripcion corta:
Implementacion de tema global para todos los items del menu sin tocar logica funcional.

Detalle tecnico:
Se agrego un sistema de tematizacion transversal para modulos cargados en pestanas:
1) `js/scripts.js` ahora inyecta automaticamente recursos globales en cada `iframe` al cargar (`css/modulo_tema_global.css` y `js/modulo_tema_global.js`), usando versionado para cache.
2) `css/modulo_tema_global.css` define estilo profesional comun para formularios, tablas, paginacion, botones, contenedores y modales.
3) `js/modulo_tema_global.js` aplica clase de tema al `body` del modulo, centra paginaciones y agrega iconografia de forma no intrusiva en botones detectados, manteniendo eventos y endpoints existentes.

Archivos afectados:
- js/scripts.js
- css/modulo_tema_global.css
- js/modulo_tema_global.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Aplicar el mismo look and feel del primer item al resto de opciones del menu, evitando refactor manual archivo por archivo y sin alterar funcionalidades.

Impacto:
Todos los modulos abiertos desde el menu en pestanas reciben tema visual consistente y botones con iconos, con menor riesgo de regresion funcional.

Pendientes:
- Validacion visual integral del usuario recorriendo varios items del menu en escritorio y movil.

---

Fecha (America/Bogota): 2026-02-17
Hora: 10:06:14
Autor: Codex (Antigravity)

Descripcion corta:
Actualizacion de identidad visual del sistema, mejora del login, ajuste de ancho en item con existencia y mejoras funcionales/visuales en ABC Costo Inventario.

Detalle tecnico:
Se actualizo la identidad visual del sistema en `Principal.php` y `index.php` con el nuevo nombre `GESTION DE INVENTARIOS Y DESPACHOS`, adicion de favicon SVG (`imagenes/favicon_gestion.svg`) e iconografia de marca.
Se rediseÃ±o el login (`index.php`, `css/index.css`) con layout moderno, iconos en campos/boton y compatibilidad del toggle de contrasena mediante alias JS en `js/index.js`.
En `ListaSinMovConExis.php` se aplico estructura visual de tarjeta y se ampliÃ³ el ancho util del modulo mediante `css/ListaSinMovConExis.css` (`report-shell-wide`).
En `ListaClasificacionCosto.php` se mejoro el bloque de totales con tarjetas KPI e iconos, se estilizaron filas de corte/totales y se implemento paginacion real con DataTables, manteniendo la exportacion a Excel y la logica de consulta.
Se agrego stylesheet dedicado `css/ListaClasificacionCosto.css`.

Archivos afectados:
- Principal.php
- css/menu_profesional.css
- index.php
- css/index.css
- js/index.js
- imagenes/favicon_gestion.svg
- ListaSinMovConExis.php
- css/ListaSinMovConExis.css
- ListaClasificacionCosto.php
- css/ListaClasificacionCosto.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Atender solicitud de mejora integral de apariencia sin tocar funcionalidad de negocio, junto con ajustes especificos de usabilidad por modulo.

Impacto:
Experiencia visual unificada y mas profesional en login, cabecera principal y modulos solicitados.
`PRODUCTOS SIN MOVIMIENTO Y CON EXISTENCIA` gana ancho util para columnas.
`ABC COSTO INVENTARIO` ahora presenta resumen visual mejorado y paginacion operativa.

Pendientes:
- Validar en navegador (cache limpio) que favicon/nombre, login y paginacion del ABC se comporten segun esperado.

---

Fecha (America/Bogota): 2026-02-17
Hora: 10:23:02
Autor: Codex (Antigravity)

Descripcion corta:
Correcciones UX/UI en modulos reportados: carga de log, backorder, rotacion, pedidos, configuracion de lineas y productos clasificados.

Detalle tecnico:
Se mejoro `log_maximos_minimos.php` con barra de progreso visual durante la generacion, bloqueo/desbloqueo consistente del `body` y manejo de timeout/error para procesos largos.
Se refactorizo `backorder.php` con estructura HTML valida, tabla responsiva/paginada, estado de vacio y modal de detalle estable.
En `rotacion_inventario.php` se rediseño el formulario y se agrego progreso de carga; en `rotacion_inventario_ajax.php` se agregaron contenedores responsivos, estilos de tabla, control de vacio y paginacion DataTables para evitar desborde de pantalla.
En `PedidosGeneradosAutomaticamente.php` se ajusto presentacion, filtro/paginacion, exportacion y se corrigio visualizacion del campo `Asentado` con fallback cuando viene nulo.
En `ListaConfiguracionLineas.php` se incorporo titulo visible, tabla profesional con paginacion y se corrigieron acciones Crear/Editar/Eliminar para flujo por URL dentro de la pestana.
En `listado_productos_clasificados.php` se limito ancho util, se agrego tabla responsiva con paginacion y busqueda integrada.
Tambien se ajusto `js/configuracionLineas.js` para navegacion por `window.location.href` y evitar llamadas AJAX a contenedores inexistentes en modo pestañas.

Archivos afectados:
- log_maximos_minimos.php
- backorder.php
- rotacion_inventario.php
- rotacion_inventario_ajax.php
- PedidosGeneradosAutomaticamente.php
- ListaConfiguracionLineas.php
- listado_productos_clasificados.php
- js/configuracionLineas.js
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Resolver incidencias funcionales/visuales reportadas por usuario en modulos del menu, manteniendo intacta la logica de negocio.

Impacto:
Mejor experiencia en carga de reportes, mejor legibilidad de tablas extensas, acciones de configuracion de lineas operativas y pantallas consistentes con el tema profesional.

Pendientes:
- Validacion funcional en navegador de cada modulo con datos reales y flujo completo de usuario.

---

Fecha (America/Bogota): 2026-02-17
Hora: 10:29:00
Autor: Codex (Antigravity)

Descripcion corta:
Ajuste de ancho visual en el item Productos Sin Movimiento y Con Existencia.

Detalle tecnico:
Se incremento el ancho maximo del contenedor `report-shell-wide` para `ListaSinMovConExis` a `1720px` con `width: 98vw`, agregando fallback para modo normal y para `modulo-theme-ready`.
Tambien se habilito `overflow-x: auto` en `result-panel` para evitar recorte de columnas cuando la tabla es mas ancha que el viewport.
Se actualizo el versionado del CSS en el modulo para invalidar cache del navegador.

Archivos afectados:
- ListaSinMovConExis.php
- css/ListaSinMovConExis.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Permitir que el contenido del reporte quepa mejor en pantalla y mejorar legibilidad de columnas amplias.

Impacto:
El modulo dispone de mayor ancho util y scroll horizontal controlado solo en la zona de resultados.

Pendientes:
- Validacion visual del usuario en navegador con datos reales.

---

Fecha (America/Bogota): 2026-02-17
Hora: 10:35:44
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de paginacion en modulo ABC Costo Inventario.

Detalle tecnico:
Se corrigio la estructura de filas de subtotales en `ListaClasificacionCosto.php` agregando la celda faltante para mantener la misma cantidad de columnas de la tabla principal.
Se removio `data-sortable` de la tabla para evitar conflicto con DataTables.
Se reforzo la inicializacion DataTables (`paging: true`, manejo de errores `error.dt`) y se mantuvo busqueda por campo personalizado.
Se ajusto versionado del CSS del modulo para invalidar cache.
En `css/ListaClasificacionCosto.css` se mejoro visualizacion de `dataTables_info` y `dataTables_paginate` para que la paginacion quede visible y centrada.

Archivos afectados:
- ListaClasificacionCosto.php
- css/ListaClasificacionCosto.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Resolver ausencia de paginacion visible en el item ABC Costo Inventario.

Impacto:
La tabla vuelve a paginar correctamente y la navegacion de paginas queda visible en el pie de la grilla.

Pendientes:
- Confirmacion visual del usuario tras recarga forzada del navegador en la pestana del modulo.

---

Fecha (America/Bogota): 2026-02-17
Hora: 10:38:10
Autor: Codex (Antigravity)

Descripcion corta:
Ajuste adicional de ancho y scroll horizontal en resultado del modulo Productos Sin Movimiento y Con Existencia.

Detalle tecnico:
Se amplio el contenedor principal del modulo a `1880px` (`99vw`) y se forzo en la tabla resultado (`ListaSinMovConExis_ajax.php`) un `min-width: 2050px` con `width: max-content`.
Se agrego clase `sinmovcexis-grid` al wrapper de resultados para habilitar scroll horizontal controlado.
En `css/ListaSinMovConExis.css` se incluyeron reglas para `overflow` y `white-space: nowrap` en celdas, ademas de ancho minimo mayor para la columna de producto.
Se actualizo versionado del CSS en `ListaSinMovConExis.php` para invalidar cache.

Archivos afectados:
- ListaSinMovConExis.php
- ListaSinMovConExis_ajax.php
- css/ListaSinMovConExis.css
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Evitar recorte visual de columnas cuando se genera el reporte con todos los campos.

Impacto:
La tabla ahora se visualiza completa con desplazamiento horizontal en el panel de resultados.

Pendientes:
- Validacion del usuario con juego de datos amplio en navegador.

---

Fecha (America/Bogota): 2026-02-17
Hora: 10:40:30
Autor: Codex (Antigravity)

Descripcion corta:
Correccion de fatal por funcion includeAssets no definida en rotacion_inventario.php.

Detalle tecnico:
Se agrego `require_once "conecta.php"` en `rotacion_inventario.php` para asegurar disponibilidad de helpers globales (incluyendo `includeAssets()`).
No se altero la logica de negocio del reporte ni sus consultas.

Archivos afectados:
- rotacion_inventario.php
- docs/bitacoras/bitacora_ajuste_rutas_bd_2026-02-17.md

Motivo del cambio:
Eliminar error fatal `Call to undefined function includeAssets()` al cargar el modulo.

Impacto:
El modulo vuelve a renderizar correctamente sus assets y pantalla.

Pendientes:
- Validacion en navegador del flujo completo de generacion del reporte.
