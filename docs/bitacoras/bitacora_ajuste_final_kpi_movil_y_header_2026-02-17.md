Fecha (America/Bogota): 2026-02-17
Hora: 14:49:24
Autor: Codex (GPT-5)

Descripcion corta:
Ajuste final para visibilidad de KPI movil conductor y eliminacion de franja superior sobre header.

Detalle tecnico:
- En `dashboard_inicio_ajax.php` se habilito el bloque `mobile_conductor` para todo usuario autenticado (sin depender de permiso de menu), con KPI en cero y mensaje si no existe relacion usuario-conductor.
- En `css/menu_profesional.css` se fijo `--safe-top: 0px` para evitar insercion de espacio superior no deseado en header.
- En `Principal.php` se incremento version del CSS `menu_profesional.css?v=20260217_05` para forzar recarga en cliente.

Archivos afectados:
- dashboard_inicio_ajax.php
- css/menu_profesional.css
- Principal.php

Motivo del cambio:
Resolver reporte de no visualizacion de KPI movil y persistencia de espacio en blanco sobre el encabezado.

Impacto:
- Inicio movil muestra siempre el bloque KPI conductor (con datos o mensaje contextual).
- Encabezado sin franja superior residual por safe area.

Pendientes:
- Verificar en movil real del usuario con Ctrl+F5.
