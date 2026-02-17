Fecha (America/Bogota): 2026-02-17
Hora: 13:33:11
Autor: Codex (GPT-5)

Descripcion corta:
Creacion y ajuste del item de menu Despachos conductor con flujo operativo de entrega.

Detalle tecnico:
Se implemento y conecto el nuevo modulo `despachos_conductor.php` con su backend `despachos_conductor_ajax.php`.

Alcance funcional aplicado:
- Listado de guias pendientes para entrega.
- Selector de guia (TODAS o una guia especifica).
- Listado de remisiones por guia con cliente, direccion, telefono y estado.
- Acciones por remision:
  - Marcar entregado.
  - Marcar no entregado con justificacion obligatoria.
  - Marcar entrega parcial con justificacion obligatoria.
  - Abrir ubicacion por Google Maps.
  - Abrir contacto por WhatsApp.
  - Abrir chat de entrega por remision.
- Filtro visual por estado de remision (por defecto `PENDIENTE`).

Ajustes de robustez:
- Respuesta JSON controlada con buffer para evitar parser errors por salida previa.
- Validacion de pertenencia `ID_GUIA + KARDEX_ID` antes de actualizar estados o chat.
- Manejo transaccional al guardar estado de remision.
- Cierre automatico de guia a `FINALIZADO` cuando todas las remisiones quedan `ENTREGADO`.
- Escape de contenido renderizado en HTML para evitar quiebres de atributos/texto.

Integracion de menu y permisos:
- Se incorporo `despachosconductor` al catalogo de menus y mapeo de archivos en `conecta.php`.
- Se agrego acceso en `Principal.php`.
- Se registro la accion en `js/scripts.js`.
- En `permisos_menu.php` se agrego el boton rapido `Perfil conductor` para marcar solo el menu `despachosconductor`.
- Se agrego gestion administrativa de variable `GVENDE<USUARIO>` (tabla `VARIOS`) para vincular usuario a conductor desde la interfaz:
  - Cargar valor actual.
  - Guardar valor (TERID o NIT/NITTRI).
  - Limpiar valor.
  - Visualizar validacion/resolucion del conductor en `TERCEROS`.

Archivos afectados:
- conecta.php
- Principal.php
- js/scripts.js
- despachos_conductor.php
- despachos_conductor_ajax.php
- permisos_menu.php
- permisos_menu_ajax.php
- 03_create_despachos_conductor.sql

Motivo del cambio:
Habilitar una interfaz operativa para conductores, enfocada en la entrega de remisiones con trazabilidad por estado y chat, controlada por permisos de menu.

Impacto:
- Nuevo flujo operativo de entrega disponible en menu.
- Mayor trazabilidad por remision (estado + mensajes).
- Mejor control de permisos para usuarios conductores con acceso restringido por modulo.

Pendientes:
- Ejecutar `03_create_despachos_conductor.sql` en la BD contable del ano en uso.
- Validar en entorno real el flujo completo con usuarios conductores.
- Configurar permisos por usuario para dejar visible solo `Despachos conductor` cuando aplique.
- Validar para cada conductor el valor correcto en `GVENDE<USUARIO>`.
