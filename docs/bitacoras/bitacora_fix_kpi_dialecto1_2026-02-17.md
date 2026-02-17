Fecha (America/Bogota): 2026-02-17
Hora: 14:33:09
Autor: Codex (GPT-5)

Descripcion corta:
Correccion de consultas KPI para compatibilidad con Firebird SQL dialect 1.

Detalle tecnico:
- En `dashboard_inicio_ajax.php` se eliminaron comparaciones que usaban `CAST(... AS DATE)` y `CURRENT_DATE`.
- Se reemplazo por filtros de fecha con `EXTRACT(YEAR|MONTH|DAY ... CURRENT_TIMESTAMP)`:
  - KPI `guias_hoy`
  - KPI movil conductor `entregadas_hoy`
  - KPI movil conductor `incidencias_hoy`
- Este ajuste evita el error `Database SQL dialect 1 does not support reference to DATE datatype`.

Archivos afectados:
- dashboard_inicio_ajax.php

Motivo del cambio:
Resolver fallo de carga de indicadores por incompatibilidad del dialecto SQL en la BD Firebird.

Impacto:
- El endpoint de KPIs vuelve a responder sin error de dialecto.
- Inicio puede renderizar indicadores cuando existan datos de tablas SN_*.

Pendientes:
- Validar en interfaz que los KPIs ya cargan en escritorio y movil conductor.
