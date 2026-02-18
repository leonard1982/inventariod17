Fecha (America/Bogota): 2026-02-17
Hora: 19:06:30
Autor: Codex (GPT-5)

Descripcion corta:
Mejoras en modulo movil de despachos conductor: bloqueo pull-to-refresh, reset de filtros y flujo unificado de estado de entrega.

Detalle tecnico:
- UI conductor:
  - Se bloqueo gesto pull-to-refresh en movil para evitar recarga involuntaria.
  - Se agrego boton Reset para filtros de guias/remisiones.
  - Se reemplazaron 3 botones de estado por un solo boton de gestion.
  - Nuevo modal con select de estado: ENTREGADO, NO_ENTREGADO, ENTREGA_PARCIAL.
  - Justificacion opcional para ENTREGADO y obligatoria para los otros estados.
  - Guardado con confirmacion previa.
  - Si la remision ya tiene estado, boton queda bloqueado (no editable).
- Backend conductor:
  - Nueva accion unificada: guardar_estado_entrega.
  - Compatibilidad mantenida con acciones legacy.
  - Bloqueo de modificacion: si ya existe registro en SN_GUIAS_DETALLE_ESTADO, se rechaza el cambio.

Archivos afectados:
- despachos_conductor.php
- despachos_conductor_ajax.php

Motivo del cambio:
Requerimientos operativos en movil para evitar recargas accidentales y controlar el registro de entrega en una sola accion con reglas claras.

Impacto:
Flujo mas estable en movil y control de auditoria: una vez guardado el estado de una remision, no puede alterarse.

Pendientes:
- Validar en dispositivo Android real el bloqueo de pull-to-refresh segun navegador/webview usado.
