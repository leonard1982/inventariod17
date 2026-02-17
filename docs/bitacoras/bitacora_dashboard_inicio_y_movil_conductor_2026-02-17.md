Fecha (America/Bogota): 2026-02-17
Hora: 14:02:29
Autor: Codex (GPT-5)

Descripcion corta:
Implementacion de dashboards con graficos para Inicio en PC y para conductores en movil.

Detalle tecnico:
- Se agrego dashboard de Inicio para escritorio en la pestana Home usando Chart.js:
  - KPIs: modulos abiertos, menus principales, menus de usuario, estado de red.
  - Graficos: actividad semanal referencial y distribucion de menus visibles.
  - Carga condicional de Chart.js en `js/scripts.js`.
  - Render solo en escritorio (>= 992px), con fallback limpio en movil.
- En `despachos_conductor.php` se agrego dashboard movil (visible en <= 767px):
  - KPIs: total remisiones, pendientes, entregadas, no/parcial.
  - Graficos: estados de entrega (doughnut) y carga por guia (bar).
  - Datos alimentados con la lista visible filtrada del conductor.
  - Actualizacion en cambios de filtros y en resize.
- Se incluyeron estilos UI para los nuevos bloques en:
  - `css/menu_profesional.css` (dashboard inicio)
  - estilos internos de `despachos_conductor.php` (dashboard movil conductor)

Archivos afectados:
- js/scripts.js
- css/menu_profesional.css
- despachos_conductor.php

Motivo del cambio:
Mostrar indicadores graficos diferenciados por tipo de dispositivo, segun solicitud:
PC en Inicio y movil aplicado al flujo del conductor.

Impacto:
- Mayor visibilidad operativa desde Inicio en escritorio.
- Resumen accionable para conductores en pantalla movil.
- Sin cambios de logica de negocio de consultas/procesos.

Pendientes:
- Ajustar paleta/leyendas si se requiere linea grafica corporativa.
- Validar rendimiento en equipos moviles de baja gama.
