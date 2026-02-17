Fecha (America/Bogota): 2026-02-17
Hora: 14:19:08
Autor: Codex (GPT-5)

Descripcion corta:
Inicio con indicadores operativos reales en PC y dashboard movil para conductor en Home.

Detalle tecnico:
- Se creo `dashboard_inicio_ajax.php` para consultar la BD contable y entregar JSON con:
  - KPIs operativos de escritorio: guias del dia, guias del mes, remisiones del mes, conductores activos, guias en ruta, pendientes, cumplimiento y promedio por guia.
  - Series de graficos: guias por dia, estados de guia y top conductores.
  - Bloque movil conductor: deteccion de usuario conductor (TERCEROSSELF y VARIOS GVENDE/GFVP), kpis y series de estado/pendientes.
- Se refactorizo `js/scripts.js`:
  - Se removio el dashboard referencial anterior.
  - Se agrego carga AJAX con cache corta para evitar consultas repetidas.
  - Se renderiza en escritorio el dashboard operativo.
  - En movil, si el usuario es conductor, se renderiza dashboard de conductor en la pestana Inicio.
  - Se mantuvo Chart.js con carga diferida.
- Se ajusto `css/menu_profesional.css` para nuevos bloques:
  - Estados de carga/error del inicio.
  - Grid de KPIs operativos y tarjetas de graficos.
  - Variante movil de indicadores conductor.

Archivos afectados:
- dashboard_inicio_ajax.php
- js/scripts.js
- css/menu_profesional.css

Motivo del cambio:
Cumplir la solicitud de mostrar indicadores de operacion en Inicio para PC y version movil orientada al conductor.

Impacto:
- Inicio en escritorio muestra informacion de despacho util para seguimiento diario y mensual.
- Inicio en movil muestra resumen accionable para conductor cuando aplica.
- No se altero la logica funcional de los modulos de negocio existentes.

Pendientes:
- Validar visualmente en navegador movil real y escritorio con datos reales.
- Ajustar catalogo de KPIs si el usuario define indicadores adicionales.
