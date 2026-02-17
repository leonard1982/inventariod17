Fecha (America/Bogota): 2026-02-17
Hora: 14:37:09
Autor: Codex (GPT-5)

Descripcion corta:
KPI de conductor movidos al Inicio y ajuste de accesibilidad en cierre de sidebar.

Detalle tecnico:
- Se ajusto `js/scripts.js` para que, en Inicio de escritorio, si el usuario es conductor se muestren tambien sus KPI:
  - guias pendientes
  - remisiones pendientes
  - entregadas hoy
  - incidencias hoy
- Se corrigio el cierre del sidebar para evitar foco dentro de contenedor con `aria-hidden=true`:
  - al cerrar, si el foco esta dentro del sidebar se mueve al boton menu.
  - se usa `inert` cuando sidebar esta oculto y se remueve al abrir.
- En `despachos_conductor.php` se oculto de forma definitiva el bloque KPI/graficos moviles del propio modulo para que no aparezcan alli.

Archivos afectados:
- js/scripts.js
- css/menu_profesional.css
- despachos_conductor.php

Motivo del cambio:
Cumplir requerimiento de mostrar KPI del conductor en Inicio y no en modulo de Despachos conductor, y eliminar warning de accesibilidad por `aria-hidden`.

Impacto:
- Inicio ahora concentra los KPI de conductor.
- Modulo de Despachos conductor queda enfocado en listado operativo.
- Disminuyen advertencias de accesibilidad relacionadas con foco oculto.

Pendientes:
- Validar en navegacion real (PC/movil) el comportamiento final del foco y de los KPI en Inicio.
