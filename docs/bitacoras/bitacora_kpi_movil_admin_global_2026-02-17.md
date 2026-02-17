Fecha (America/Bogota): 2026-02-17
Hora: 15:00:47
Autor: Codex (GPT-5)

Descripcion corta:
Correccion de KPI movil conductor para usuario ADMIN en vista global.

Detalle tecnico:
- En `dashboard_inicio_ajax.php` (funcion `dashboardBuildMobileConductorData`) se elimino el retorno temprano para ADMIN.
- Se implemento filtro dinamico por conductor:
  - Usuario conductor: aplica `g.ID_CONDUCTOR = ?`.
  - Usuario ADMIN: no aplica filtro y calcula valores globales.
- Se ajustaron consultas de KPI y series para reutilizar el mismo bloque SQL con o sin filtro:
  - Guias pendientes.
  - Remisiones pendientes.
  - Entregadas hoy.
  - Incidencias hoy.
  - Serie de estados de remisiones.
  - Serie de guias (mes actual y fallback historico).
- Se mantuvo el comportamiento de seguridad:
  - Si no es ADMIN y no existe vinculacion usuario-conductor, el panel queda en cero con mensaje de configuracion.

Archivos afectados:
- dashboard_inicio_ajax.php

Motivo del cambio:
Evitar que en movil, al ingresar como ADMIN, el dashboard de conductor siempre mostrara ceros por salida anticipada del backend.

Impacto:
- ADMIN en movil ahora ve KPI reales agregados (vista global), no ceros por defecto.
- Conductores mantienen su vista filtrada individual.

Pendientes:
- Validar en navegador movil del usuario que el bloque Inicio refleja datos reales segun permisos y volumen operativo.
