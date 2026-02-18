Fecha (America/Bogota): 2026-02-17
Hora: 16:44:59
Autor: Codex (GPT-5)

Descripcion corta:
Ajustes del modulo GUIAS (Despachos): estado ENTREGADO, catalogo configurable, fecha automatica y vehiculo por placa.

Detalle tecnico:
- Se ajusto `guias_despachos.php`:
  - Estado FINALIZADO reemplazado visualmente por ENTREGADO.
  - Filtro/selector de estados ahora se carga desde catalogo dinamico (AJAX), no hardcodeado.
  - Fecha y hora de guia en formularios (nuevo/edicion) queda solo informativa y no editable.
  - Se agrego seleccion de vehiculo por placa en crear/editar guia.
  - Se amplio modal/panel de remisiones para mejor visibilidad de candidatas.
  - Se agrego panel de catalogo de estados (agregar, editar, eliminar con bloqueo visual cuando estan en uso).
- Se ajusto `guias_despachos_ajax.php`:
  - Nuevas acciones: `listar_estados_catalogo`, `agregar_estado_catalogo`, `editar_estado_catalogo`, `eliminar_estado_catalogo`.
  - Validacion para no editar/eliminar estados en uso (SN_GUIAS / SN_GUIAS_ESTADOS).
  - `crear_guia` asigna `FECHA_GUIA = CURRENT_TIMESTAMP` automaticamente.
  - `actualizar_guia` ya no permite modificar fecha/hora de guia.
  - Soporte de `ID_VEHICULO` cuando la columna existe en SN_GUIAS.
  - Normalizacion de estado: FINALIZADO -> ENTREGADO para presentacion/compatibilidad.
- Se ajusto `despachos_conductor_ajax.php`:
  - Cierre automatico de guia ahora marca estado ENTREGADO.
  - Filtros de pendientes consideran cerradas tanto FINALIZADO como ENTREGADO.
- Se ajusto `dashboard_inicio_ajax.php`:
  - KPI de pendientes considera cerradas FINALIZADO y ENTREGADO.
  - Serie de estados en dashboard PC unifica FINALIZADO dentro de ENTREGADO.
- Se ajusto `guia_despacho_print.php`:
  - Muestra estado ENTREGADO (mapeo de FINALIZADO).
  - Incluye placa de vehiculo cuando existe `SN_GUIAS.ID_VEHICULO`.
- SQL:
  - Se actualizo `00_create_sn_guias.sql` para incluir `ID_VEHICULO`, tabla `SN_GUIAS_ESTADOS_CFG` y estado base ENTREGADO.
  - Se creo `04_create_estados_guias_y_vehiculo.sql` para migracion en bases existentes (catalogo de estados, migracion FINALIZADO->ENTREGADO, columna/indice/FK de vehiculo y retiro de checks fijos).

Archivos afectados:
- guias_despachos.php
- guias_despachos_ajax.php
- despachos_conductor_ajax.php
- dashboard_inicio_ajax.php
- guia_despacho_print.php
- 00_create_sn_guias.sql
- 04_create_estados_guias_y_vehiculo.sql

Motivo del cambio:
Atender solicitud operativa: usar ENTREGADO en lugar de FINALIZADO, configurar estados desde formulario, bloquear cambios de estados en uso, fijar fecha/hora automatica de guia, ampliar seleccion de remisiones y permitir asignar vehiculo por placa.

Impacto:
- Mejora la usabilidad del modulo de guias.
- Permite administrar estados sin hardcode.
- Mantiene trazabilidad de estados y compatibilidad con datos legacy FINALIZADO.
- Habilita despacho con vehiculo asociado por placa.

Pendientes:
- Ejecutar en BD productiva el script `04_create_estados_guias_y_vehiculo.sql`.
- Validacion funcional final en UI (crear/editar guia, cambio de estado y flujo remisiones) en entorno de usuario.
