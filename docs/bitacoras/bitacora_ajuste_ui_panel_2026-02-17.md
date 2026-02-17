Fecha (America/Bogota): 2026-02-17
Hora: 13:53:05
Autor: Codex (GPT-5)

Descripcion corta:
Ajuste visual del panel principal y compactacion del encabezado/filtros en Despachos conductor.

Detalle tecnico:
- Se retiro del panel principal la seccion flotante de indicadores (Hora local, Estado red y Modulos).
- En el modulo `despachos_conductor.php` se ajusto el encabezado para que:
  - El boton de actualizar sea solo icono.
  - El icono de actualizar quede junto al titulo del modulo.
- Se compactaron los filtros superiores de conductor:
  - Selector de guia y selector de estado en una sola linea.
  - Tipografia y controles mas pequenos para mejor ajuste horizontal.

Archivos afectados:
- Principal.php
- despachos_conductor.php

Motivo del cambio:
Aplicar ajuste de interfaz solicitado para ganar espacio visual y mejorar legibilidad en cabeceras/filtros.

Impacto:
- Se elimina ruido visual del panel principal.
- Mejor aprovechamiento del ancho en el modulo de conductor.
- Mantiene la misma funcionalidad operativa sin cambios de logica.

Pendientes:
- Validar en resoluciones moviles muy estrechas que el nuevo acomodo conserve legibilidad.
